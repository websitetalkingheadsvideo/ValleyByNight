<?php
/**
 * tools/repeatable/import_ghoul.php
 *
 * Repeatable ghoul importer (canonical model):
 *   - characters = single source of truth for full character sheets (including ghouls)
 *   - ghouls = ghoul-only extension data (domitor, bond, vitae cadence, etc.)
 *
 * Usage (CLI):
 *   php tools/repeatable/import_ghoul.php path/to/ghoul.json
 *
 * Defaults:
 *   - domitor_character_id defaults to 139 (Julian) unless JSON provides domitor_character_id
 *   - player_name defaults to "NPC" if missing
 *   - chronicle defaults to "Valley by Night" if missing
 *   - pc defaults to 0 if missing
 *   - clan is forced to "Ghoul"
 *
 * Requirements:
 *   - project root has connect.php which defines $conn as mysqli
 *   - tables: characters, ghouls
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "ERROR: Could not resolve project root.\n");
    exit(1);
}

require_once $root . '/includes/connect.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    fwrite(STDERR, "ERROR: connect.php must define \$conn as a mysqli connection.\n");
    exit(1);
}

$conn->set_charset('utf8mb4');

function die_err(string $msg, int $code = 1): void {
    fwrite(STDERR, "ERROR: {$msg}\n");
    exit($code);
}

function read_json_file(string $path): array {
    if (!is_file($path)) die_err("JSON file not found: {$path}");
    $raw = file_get_contents($path);
    if ($raw === false) die_err("Unable to read JSON file: {$path}");
    $data = json_decode($raw, true);
    if (!is_array($data)) die_err("Invalid JSON in file: {$path}");
    return $data;
}

function get_table_columns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) die_err("SHOW COLUMNS failed for {$table}: " . $conn->error);
    while ($row = $res->fetch_assoc()) {
        $cols[$row['Field']] = $row; // includes Type, Null, Key, Default, Extra
    }
    $res->free();
    return $cols;
}

function json_or_scalar(mixed $v): mixed {
    if (is_array($v) || is_object($v)) {
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (is_bool($v)) return $v ? 1 : 0;
    return $v;
}

/**
 * Build and execute a prepared INSERT/UPDATE with only columns that exist.
 * $data should already be filtered to existing columns.
 */
function exec_prepared(mysqli $conn, string $sql, string $types, array $values): void {
    $stmt = $conn->prepare($sql);
    if (!$stmt) die_err("Prepare failed: " . $conn->error . " | SQL: " . $sql);

    // mysqli requires references for bind_param in older PHP; the splat works in modern PHP.
    $stmt->bind_param($types, ...$values);

    if (!$stmt->execute()) die_err("Execute failed: " . $stmt->error . " | SQL: " . $sql);
    $stmt->close();
}

function infer_bind_type(mixed $v): string {
    if (is_int($v)) return 'i';
    if (is_float($v)) return 'd';
    return 's';
}

function upsert_character(mysqli $conn, array $charactersCols, array $json): int {
    if (empty($json['character_name'])) die_err("character_name is required in the ghoul JSON.");

    // Defaults
    $json['player_name'] = $json['player_name'] ?? 'NPC';
    $json['chronicle']   = $json['chronicle']   ?? 'Valley by Night';
    $json['pc']          = $json['pc']          ?? 0;

    // Force clan
    $json['clan'] = 'Ghoul';

    // Handle notes specially - extract text content from nested structure
    if (isset($json['notes']) && is_array($json['notes'])) {
        $notesParts = [];
        if (isset($json['notes']['one_liner']) && !empty($json['notes']['one_liner'])) {
            $notesParts[] = $json['notes']['one_liner'];
        }
        if (isset($json['notes']['expanded']) && !empty($json['notes']['expanded'])) {
            $notesParts[] = $json['notes']['expanded'];
        }
        if (isset($json['notes']['st_only']) && !empty($json['notes']['st_only'])) {
            $notesParts[] = "[ST Only] " . $json['notes']['st_only'];
        }
        // Store as plain text, not JSON
        $json['notes'] = !empty($notesParts) ? implode("\n\n", $notesParts) : null;
    }

    // Flatten: store nested blocks into JSON columns only if such columns exist.
    $candidate = [];
    foreach ($json as $k => $v) {
        // Notes is already processed as text above, don't JSON-encode it
        if ($k === 'notes' && is_string($v)) {
            $candidate[$k] = $v;
        } else {
            $candidate[$k] = json_or_scalar($v);
        }
    }

    // Keep only columns that exist on characters
    $data = [];
    foreach ($candidate as $k => $v) {
        if (array_key_exists($k, $charactersCols)) {
            // Avoid clobbering timestamps unless explicitly provided
            if (($k === 'created_at' || $k === 'updated_at') && ($v === '' || $v === null)) continue;
            $data[$k] = $v;
        }
    }

    // Ensure required-ish columns (if present)
    if (isset($charactersCols['character_name'])) $data['character_name'] = (string)$json['character_name'];
    if (isset($charactersCols['clan']))           $data['clan'] = 'Ghoul';
    if (isset($charactersCols['user_id']))        $data['user_id'] = (int)($json['user_id'] ?? 1);

    // Find existing by name + clan (preferred)
    $stmt = null;
    if (isset($charactersCols['clan'])) {
        $stmt = $conn->prepare("SELECT id FROM characters WHERE character_name = ? AND clan = 'Ghoul' LIMIT 1");
        if (!$stmt) die_err("Prepare failed: " . $conn->error);
        $stmt->bind_param('s', $json['character_name']);
    } else {
        $stmt = $conn->prepare("SELECT id FROM characters WHERE character_name = ? LIMIT 1");
        if (!$stmt) die_err("Prepare failed: " . $conn->error);
        $stmt->bind_param('s', $json['character_name']);
    }

    if (!$stmt->execute()) die_err("Execute failed: " . $stmt->error);
    $res = $stmt->get_result();
    $existing = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($existing && isset($existing['id'])) {
        $id = (int)$existing['id'];

        if (count($data) === 0) return $id;

        // Build UPDATE
        $fields = array_keys($data);
        $setParts = [];
        $types = '';
        $values = [];
        foreach ($fields as $f) {
            $setParts[] = "`{$f}` = ?";
            $types .= infer_bind_type($data[$f]);
            $values[] = $data[$f];
        }
        $sql = "UPDATE characters SET " . implode(', ', $setParts) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $id;
        exec_prepared($conn, $sql, $types, $values);

        return $id;
    }

    // Build INSERT
    if (count($data) === 0) {
        die_err("No insertable fields matched the characters schema. Check your characters table columns.");
    }

    $fields = array_keys($data);
    $colsSql = implode(', ', array_map(fn($f) => "`{$f}`", $fields));
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = "INSERT INTO characters ({$colsSql}) VALUES ({$placeholders})";

    $types = '';
    $values = [];
    foreach ($fields as $f) {
        $types .= infer_bind_type($data[$f]);
        $values[] = $data[$f];
    }
    exec_prepared($conn, $sql, $types, $values);

    return (int)$conn->insert_id;
}

function upsert_ghoul_overlay(mysqli $conn, array $ghoulOverlaysCols, int $characterId, int $domitorId, array $json): void {
    if (!isset($ghoulOverlaysCols['character_id'])) {
        die_err("ghouls table must have a character_id column.");
    }

    // Identify domitor column name (prioritize domitor_character_id)
    $domitorCol = null;
    if (isset($ghoulOverlaysCols['domitor_character_id'])) {
        $domitorCol = 'domitor_character_id';
    } elseif (isset($ghoulOverlaysCols['domitor_id'])) {
        $domitorCol = 'domitor_id';
    } elseif (isset($ghoulOverlaysCols['regnant_id'])) {
        // Fallback for legacy data
        $domitorCol = 'regnant_id';
    }

    if ($domitorCol === null) {
        die_err("ghouls table must have one of: domitor_character_id, domitor_id, regnant_id.");
    }

    // Build overlay data
    $overlay = ['character_id' => $characterId, $domitorCol => $domitorId];

    // Extract ghoul-specific fields from JSON if columns exist
    $candidateKeys = [
        'blood_bond_stage', 'vitae_last_fed_at', 'vitae_frequency', 'first_fed_at',
        'is_active', 'is_family', 'retainer_level', 'loyalty', 'independent_will',
        'escape_risk', 'risk_level', 'discipline_cap_override', 'addiction_severity',
        'withdrawal_effects', 'domitor_control_style', 'handler_notes',
        'masquerade_liability', 'notes', 'custom_data'
    ];

    foreach ($candidateKeys as $k) {
        if (array_key_exists($k, $json) && isset($ghoulOverlaysCols[$k])) {
            $overlay[$k] = json_or_scalar($json[$k]);
        }
    }
    $ghoulNotNullDefaults = [
        'retainer_level' => 0,
        'loyalty' => 0,
        'is_active' => 1,
        'is_family' => 0,
        'independent_will' => 0,
        'escape_risk' => 0,
        'risk_level' => 0,
    ];
    foreach ($ghoulNotNullDefaults as $col => $default) {
        if (isset($ghoulOverlaysCols[$col])) {
            $v = $overlay[$col] ?? null;
            if ($v === null || $v === '') {
                $overlay[$col] = $default;
            }
        }
    }

    // Check if overlay exists
    $stmt = $conn->prepare("SELECT id FROM ghouls WHERE character_id = ? LIMIT 1");
    if (!$stmt) die_err("Prepare failed (SELECT ghouls): " . $conn->error);
    $stmt->bind_param('i', $characterId);
    if (!$stmt->execute()) die_err("Execute failed (SELECT ghouls): " . $stmt->error);
    $res = $stmt->get_result();
    $existing = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($existing && isset($existing['id'])) {
        // UPDATE
        $id = (int)$existing['id'];
        $fields = array_keys($overlay);
        $setParts = [];
        $types = '';
        $values = [];
        foreach ($fields as $f) {
            $setParts[] = "`{$f}` = ?";
            $types .= infer_bind_type($overlay[$f]);
            $values[] = $overlay[$f];
        }
        $sql = "UPDATE ghouls SET " . implode(', ', $setParts) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $id;
        exec_prepared($conn, $sql, $types, $values);
    } else {
        // INSERT
        $fields = array_keys($overlay);
        $colsSql = implode(', ', array_map(fn($f) => "`{$f}`", $fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO ghouls ({$colsSql}) VALUES ({$placeholders})";

        $types = '';
        $values = [];
        foreach ($fields as $f) {
            $types .= infer_bind_type($overlay[$f]);
            $values[] = $overlay[$f];
        }
        exec_prepared($conn, $sql, $types, $values);
    }
}

$jsonPath = $argv[1] ?? '';
if ($jsonPath === '') {
    die_err("Usage: php tools/repeatable/import_ghoul.php path/to/ghoul.json");
}

$ghoulJson = read_json_file($jsonPath);

// Handle nested name.full structure
if (empty($ghoulJson['character_name']) && isset($ghoulJson['name']['full'])) {
    $ghoulJson['character_name'] = $ghoulJson['name']['full'];
}

// Extract nested mechanics fields to top level
if (isset($ghoulJson['mechanics']) && is_array($ghoulJson['mechanics'])) {
    $mechanics = $ghoulJson['mechanics'];
    if (isset($mechanics['nature'])) $ghoulJson['nature'] = $mechanics['nature'];
    if (isset($mechanics['demeanor'])) $ghoulJson['demeanor'] = $mechanics['demeanor'];
    if (isset($mechanics['concept'])) $ghoulJson['concept'] = $mechanics['concept'];
}

// Get domitor ID from nested structure or top level (prioritize domitor_character_id)
$domitorId = 139; // Default to Julian
if (isset($ghoulJson['domitor']['domitor_character_id']) && is_numeric($ghoulJson['domitor']['domitor_character_id'])) {
    $domitorId = (int)$ghoulJson['domitor']['domitor_character_id'];
} elseif (isset($ghoulJson['domitor_character_id']) && is_numeric($ghoulJson['domitor_character_id'])) {
    $domitorId = (int)$ghoulJson['domitor_character_id'];
} elseif (isset($ghoulJson['domitor_id']) && is_numeric($ghoulJson['domitor_id'])) {
    $domitorId = (int)$ghoulJson['domitor_id'];
} elseif (isset($ghoulJson['regnant_id']) && is_numeric($ghoulJson['regnant_id'])) {
    // Fallback for legacy data
    $domitorId = (int)$ghoulJson['regnant_id'];
}

// Extract blood_bond_stage from nested bond structure
if (isset($ghoulJson['bond']['blood_bond_stage'])) {
    $ghoulJson['blood_bond_stage'] = $ghoulJson['bond']['blood_bond_stage'];
}

$charactersCols = get_table_columns($conn, 'characters');
$ghoulOverlaysCols = get_table_columns($conn, 'ghouls');

$conn->begin_transaction();

try {
    $characterId = upsert_character($conn, $charactersCols, $ghoulJson);
    upsert_ghoul_overlay($conn, $ghoulOverlaysCols, $characterId, $domitorId, $ghoulJson);
    $conn->commit();
    fwrite(STDOUT, "OK: Imported ghoul '{$ghoulJson['character_name']}' (character_id={$characterId}) with domitor_character_id={$domitorId}\n");
} catch (Throwable $e) {
    $conn->rollback();
    die_err("Transaction failed: " . $e->getMessage());
}
