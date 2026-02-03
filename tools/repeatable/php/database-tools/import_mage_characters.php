<?php
/**
 * Mage Character JSON Import Script
 *
 * Imports Mage: The Ascension character JSON files from reference/Characters/Mage/
 * into the database. Upsert by character_name (update if exists, insert if new).
 *
 * Usage:
 *   CLI: php tools/repeatable/php/database-tools/import_mage_characters.php [filename.json]
 *   Web: ?file=filename.json or ?all=1
 *
 * All imported characters use user_id = 1 (admin/ST account).
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Mage Character Import</title><style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style></head><body>";
}

require_once __DIR__ . '/../../../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

define('IMPORT_USER_ID', 1);

$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => []
];

function cleanString($value) {
    if (is_array($value)) {
        return json_encode($value);
    }
    if (is_string($value)) {
        return trim($value);
    }
    if (is_null($value)) {
        return '';
    }
    return (string)$value;
}

function cleanInt($value, int $default = 0): int {
    if (is_null($value) || $value === '') {
        return $default;
    }
    return (int)$value;
}

function cleanJsonData($value) {
    if (empty($value)) {
        return null;
    }
    if (is_array($value) || is_object($value)) {
        return json_encode($value);
    }
    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return null;
    }
    $decoded = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $trimmed;
    }
    return json_encode(['text' => $trimmed]);
}

function findMageCharacterByName(mysqli $conn, string $character_name): ?int {
    $result = db_fetch_one($conn,
        "SELECT id FROM mage_characters WHERE character_name = ? LIMIT 1",
        's',
        [$character_name]
    );
    return $result ? (int)$result['id'] : null;
}

function importMageCharacter(mysqli $conn, array $data, string $filename): bool {
    global $stats;

    $character_name = cleanString($data['character_name'] ?? '');
    if (empty($character_name)) {
        $stats['errors'][] = "$filename: Missing character_name";
        return false;
    }

    $character_id = findMageCharacterByName($conn, $character_name);

    $cleanData = [
        'user_id' => IMPORT_USER_ID,
        'character_name' => $character_name,
        'player_name' => cleanString($data['player_name'] ?? 'NPC'),
        'chronicle' => cleanString($data['chronicle'] ?? 'Valley by Night'),
        'nature' => cleanString($data['nature'] ?? ''),
        'demeanor' => cleanString($data['demeanor'] ?? ''),
        'concept' => cleanString($data['concept'] ?? ''),
        'tradition' => cleanString($data['tradition'] ?? ''),
        'paradigm' => cleanString($data['paradigm'] ?? ''),
        'practice' => cleanString($data['practice'] ?? ''),
        'instruments' => cleanJsonData($data['instruments'] ?? null),
        'pc' => cleanInt($data['pc'] ?? 1),
        'appearance' => cleanString($data['appearance'] ?? ''),
        'biography' => cleanString($data['biography'] ?? ''),
        'notes' => cleanString($data['notes'] ?? ''),
        'equipment' => cleanString($data['equipment'] ?? ''),
        'character_image' => cleanString($data['character_image'] ?? ''),
        'status' => cleanString($data['status'] ?? 'active'),
        'arete' => cleanInt($data['arete'] ?? 1),
        'willpower_permanent' => cleanInt($data['willpower_permanent'] ?? 5),
        'willpower_current' => cleanInt($data['willpower_current'] ?? 5),
        'quintessence' => cleanJsonData($data['quintessence'] ?? null),
        'paradox' => cleanJsonData($data['paradox'] ?? null),
        'spheres' => cleanJsonData($data['spheres'] ?? null),
        'attributes' => cleanJsonData($data['attributes'] ?? null),
        'abilities' => cleanJsonData($data['abilities'] ?? null),
        'rotes' => cleanJsonData($data['rotes'] ?? null),
        'backgrounds' => cleanJsonData($data['backgrounds'] ?? null),
        'backgroundDetails' => cleanJsonData($data['backgroundDetails'] ?? null),
        'merits_flaws' => cleanJsonData($data['merits_flaws'] ?? null),
        'health_levels' => cleanJsonData($data['health_levels'] ?? null),
        'relationships' => cleanJsonData($data['relationships'] ?? null),
        'custom_data' => cleanJsonData($data['custom_data'] ?? null),
        'actingNotes' => cleanString($data['actingNotes'] ?? ''),
        'agentNotes' => cleanString($data['agentNotes'] ?? ''),
        'health_status' => cleanString($data['health_status'] ?? ''),
        'experience_total' => cleanInt($data['experience_total'] ?? 0),
        'spent_xp' => cleanInt($data['spent_xp'] ?? 0),
        'experience_unspent' => cleanInt(
            $data['experience_unspent'] ?? (cleanInt($data['experience_total'] ?? 0) - cleanInt($data['spent_xp'] ?? 0))
        )
    ];

    $validStates = ['active', 'inactive', 'archived', 'dead', 'missing'];
    $cleanData['status'] = strtolower($cleanData['status']);
    if (!in_array($cleanData['status'], $validStates, true)) {
        $cleanData['status'] = 'active';
    }

    db_begin_transaction($conn);

    try {
        $base_fields = [
            'user_id', 'character_name', 'player_name', 'chronicle',
            'nature', 'demeanor', 'concept', 'tradition', 'paradigm', 'practice', 'instruments',
            'pc', 'appearance', 'biography', 'notes', 'equipment', 'status',
            'arete', 'willpower_permanent', 'willpower_current',
            'quintessence', 'paradox', 'spheres', 'attributes', 'abilities', 'rotes',
            'backgrounds', 'backgroundDetails', 'merits_flaws', 'health_levels', 'relationships', 'custom_data',
            'actingNotes', 'agentNotes', 'health_status',
            'experience_total', 'spent_xp', 'experience_unspent'
        ];

        if ($character_id > 0) {
            $set_parts = [];
            foreach ($base_fields as $f) {
                if ($f === 'character_name') {
                    continue;
                }
                $set_parts[] = "`$f` = ?";
            }
            $update_sql = "UPDATE mage_characters SET " . implode(', ', $set_parts) . " WHERE id = ?";
            $params = [];
            $types = '';
            $int_fields = ['user_id', 'pc', 'arete', 'willpower_permanent', 'willpower_current', 'experience_total', 'spent_xp', 'experience_unspent'];
            foreach ($base_fields as $f) {
                if ($f === 'character_name') {
                    continue;
                }
                $params[] = $cleanData[$f];
                $types .= in_array($f, $int_fields, true) ? 'i' : 's';
            }
            $params[] = $character_id;
            $types .= 'i';

            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            $stats['updated']++;
        } else {
            $fields_with_image = $base_fields;
            if ($cleanData['character_image'] !== '') {
                $fields_with_image[] = 'character_image';
            }
            $field_list = implode(', ', $fields_with_image);
            $placeholders = implode(', ', array_fill(0, count($fields_with_image), '?'));
            $insert_sql = "INSERT INTO mage_characters ($field_list) VALUES ($placeholders)";

            $params = [];
            $types = '';
            $int_fields = ['user_id', 'pc', 'arete', 'willpower_permanent', 'willpower_current', 'experience_total', 'spent_xp', 'experience_unspent'];
            foreach ($fields_with_image as $f) {
                $params[] = $cleanData[$f] ?? '';
                $types .= in_array($f, $int_fields, true) ? 'i' : 's';
            }

            $stmt = mysqli_prepare($conn, $insert_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare insert: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to insert: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            $stats['inserted']++;
        }

        db_commit($conn);
        return true;
    } catch (Exception $e) {
        db_rollback($conn);
        $stats['errors'][] = "$filename: " . $e->getMessage();
        return false;
    }
}

$file_param = $is_cli ? ($argv[1] ?? '') : ($_GET['file'] ?? '');
$import_all = !$is_cli && isset($_GET['all']) && $_GET['all'] == '1';

$mages_dir = __DIR__ . '/../../../reference/Characters/Mage/';

if ($import_all) {
    $files = glob($mages_dir . '*.json');
    $files = array_filter($files, function ($f) {
        return basename($f) !== 'mage_character_template.json';
    });

    if (empty($files)) {
        echo $is_cli ? "No JSON files found in $mages_dir\n" : "<p class='warning'>No JSON files found in Mage directory.</p>";
    } else {
        foreach ($files as $file) {
            $filename = basename($file);
            $stats['processed']++;
            $json_content = file_get_contents($file);
            $data = json_decode($json_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $stats['errors'][] = "$filename: Invalid JSON - " . json_last_error_msg();
                $stats['skipped']++;
                continue;
            }
            if (importMageCharacter($conn, $data, $filename)) {
                echo $is_cli ? "✓ Imported: $filename\n" : "<p class='success'>✓ Imported: $filename</p>";
            } else {
                $stats['skipped']++;
            }
        }
    }
} elseif (!empty($file_param)) {
    $file_path = $mages_dir . $file_param;
    if (!file_exists($file_path)) {
        $file_path = $file_param;
    }
    if (!file_exists($file_path)) {
        die($is_cli ? "File not found: $file_param\n" : "<p class='error'>File not found: $file_param</p></body></html>");
    }
    $filename = basename($file_path);
    $stats['processed']++;
    $json_content = file_get_contents($file_path);
    $data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die($is_cli ? "Invalid JSON: " . json_last_error_msg() . "\n" : "<p class='error'>Invalid JSON: " . json_last_error_msg() . "</p></body></html>");
    }
    if (importMageCharacter($conn, $data, $filename)) {
        echo $is_cli ? "✓ Successfully imported: $filename\n" : "<p class='success'>✓ Successfully imported: $filename</p>";
    }
} else {
    die($is_cli ? "Usage: php import_mage_characters.php [filename.json]\n" : "<p class='error'>Usage: ?file=filename.json or ?all=1</p></body></html>");
}

echo $is_cli ? "\n" : "<hr><h3>Import Statistics</h3>";
echo $is_cli ? "Processed: {$stats['processed']}\n" : "<p>Processed: {$stats['processed']}</p>";
echo $is_cli ? "Inserted: {$stats['inserted']}\n" : "<p class='success'>Inserted: {$stats['inserted']}</p>";
echo $is_cli ? "Updated: {$stats['updated']}\n" : "<p class='success'>Updated: {$stats['updated']}</p>";
echo $is_cli ? "Skipped: {$stats['skipped']}\n" : "<p class='warning'>Skipped: {$stats['skipped']}</p>";

if (!empty($stats['errors'])) {
    echo $is_cli ? "\nErrors:\n" : "<h3 class='error'>Errors:</h3><ul>";
    foreach ($stats['errors'] as $error) {
        echo $is_cli ? "  - $error\n" : "<li class='error'>$error</li>";
    }
    echo $is_cli ? "" : "</ul>";
}

if (!$is_cli) {
    echo "</body></html>";
}

mysqli_close($conn);
