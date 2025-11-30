<?php
/**
 * Wraith Character JSON Import Script
 * 
 * Imports Wraith character JSON files from reference/Characters/Wraiths/ into the database.
 * Supports upsert operations (insert if new, update if exists) based on character_name.
 * 
 * Usage:
 *   CLI: php database/import_wraith_characters.php [filename.json]
 *   Web: https://vbn.talkingheads.video/database/import_wraith_characters.php?file=filename.json
 *        https://vbn.talkingheads.video/database/import_wraith_characters.php?all=1
 * 
 * All imported characters use user_id = 1 (admin/ST account)
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Wraith Character Import</title><style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style></head><body>";
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Import user_id (admin/ST account)
define('IMPORT_USER_ID', 1);

// Statistics
$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => []
];

/**
 * Clean string value
 */
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

/**
 * Clean integer value
 */
function cleanInt($value, int $default = 0): int {
    if (is_null($value) || $value === '') {
        return $default;
    }
    return (int)$value;
}

/**
 * Clean JSON data
 */
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

/**
 * Find existing character by character_name
 */
function findWraithCharacterByName(mysqli $conn, string $character_name): ?int {
    $result = db_fetch_one($conn, 
        "SELECT id FROM wraith_characters WHERE character_name = ? LIMIT 1",
        's',
        [$character_name]
    );
    return $result ? (int)$result['id'] : null;
}

/**
 * Import a single Wraith character
 */
function importWraithCharacter(mysqli $conn, array $data, string $filename): bool {
    global $stats;
    
    $character_name = cleanString($data['character_name'] ?? '');
    if (empty($character_name)) {
        $stats['errors'][] = "$filename: Missing character_name";
        return false;
    }
    
    // Find existing character
    $character_id = findWraithCharacterByName($conn, $character_name);
    
    // Prepare clean data
    $cleanData = [
        'user_id' => IMPORT_USER_ID,
        'character_name' => $character_name,
        'shadow_name' => cleanString($data['shadow_name'] ?? ''),
        'player_name' => cleanString($data['player_name'] ?? 'NPC'),
        'chronicle' => cleanString($data['chronicle'] ?? 'Valley by Night'),
        'nature' => cleanString($data['nature'] ?? ''),
        'demeanor' => cleanString($data['demeanor'] ?? ''),
        'concept' => cleanString($data['concept'] ?? ''),
        'circle' => cleanString($data['circle'] ?? ''),
        'guild' => cleanString($data['guild'] ?? ''),
        'legion_at_death' => cleanString($data['legion_at_death'] ?? ''),
        'date_of_death' => !empty($data['date_of_death']) ? cleanString($data['date_of_death']) : null,
        'cause_of_death' => cleanString($data['cause_of_death'] ?? ''),
        'pc' => cleanInt($data['pc'] ?? 0),
        'appearance' => cleanString($data['appearance'] ?? ''),
        'ghostly_appearance' => cleanString($data['ghostly_appearance'] ?? ''),
        'biography' => cleanString($data['biography'] ?? ''),
        'notes' => cleanString($data['notes'] ?? ''),
        'equipment' => cleanString($data['equipment'] ?? ''),
        'character_image' => cleanString($data['character_image'] ?? ''),
        'status' => cleanString($data['status'] ?? 'active'),
        'timeline' => cleanJsonData($data['timeline'] ?? null),
        'personality' => cleanJsonData($data['personality'] ?? null),
        'traits' => cleanJsonData($data['traits'] ?? null),
        'negativeTraits' => cleanJsonData($data['negativeTraits'] ?? null),
        'abilities' => cleanJsonData($data['abilities'] ?? null),
        'specializations' => cleanJsonData($data['specializations'] ?? null),
        'fetters' => cleanJsonData($data['fetters'] ?? null),
        'passions' => cleanJsonData($data['passions'] ?? null),
        'arcanoi' => cleanJsonData($data['arcanoi'] ?? null),
        'backgrounds' => cleanJsonData($data['backgrounds'] ?? null),
        'backgroundDetails' => cleanJsonData($data['backgroundDetails'] ?? null),
        'willpower_permanent' => cleanInt($data['willpower_permanent'] ?? 5),
        'willpower_current' => cleanInt($data['willpower_current'] ?? 5),
        'pathos_corpus' => cleanJsonData($data['pathos_corpus'] ?? null),
        'shadow' => cleanJsonData($data['shadow'] ?? null),
        'harrowing' => cleanJsonData($data['harrowing'] ?? null),
        'merits_flaws' => cleanJsonData($data['merits_flaws'] ?? null),
        'status_details' => cleanJsonData($data['status_details'] ?? null),
        'relationships' => cleanJsonData($data['relationships'] ?? null),
        'artifacts' => cleanJsonData($data['artifacts'] ?? null),
        'custom_data' => cleanJsonData($data['custom_data'] ?? null),
        'actingNotes' => cleanString($data['actingNotes'] ?? ''),
        'agentNotes' => cleanString($data['agentNotes'] ?? ''),
        'health_status' => cleanString($data['health_status'] ?? ''),
        'experience_total' => cleanInt($data['experience_total'] ?? 0),
        'spent_xp' => cleanInt($data['spent_xp'] ?? 0),
        'experience_unspent' => cleanInt($data['experience_unspent'] ?? ($cleanData['experience_total'] - $cleanData['spent_xp'])),
        'shadow_xp_total' => cleanInt($data['shadow_xp_total'] ?? 0),
        'shadow_xp_spent' => cleanInt($data['shadow_xp_spent'] ?? 0),
        'shadow_xp_available' => cleanInt($data['shadow_xp_available'] ?? ($cleanData['shadow_xp_total'] - $cleanData['shadow_xp_spent']))
    ];
    
    // Validate status
    $validStates = ['active', 'inactive', 'archived', 'dead', 'missing'];
    $cleanData['status'] = strtolower($cleanData['status']);
    if (!in_array($cleanData['status'], $validStates, true)) {
        $cleanData['status'] = 'active';
    }
    
    // Start transaction
    db_begin_transaction($conn);
    
    try {
        if ($character_id > 0) {
            // Update existing character
            $update_sql = "UPDATE wraith_characters SET 
                user_id = ?, shadow_name = ?, player_name = ?, chronicle = ?, 
                nature = ?, demeanor = ?, concept = ?, circle = ?, guild = ?, 
                legion_at_death = ?, date_of_death = ?, cause_of_death = ?, 
                pc = ?, appearance = ?, ghostly_appearance = ?, biography = ?, 
                notes = ?, equipment = ?, custom_data = ?, status = ?, 
                timeline = ?, personality = ?, traits = ?, negativeTraits = ?, 
                abilities = ?, specializations = ?, fetters = ?, passions = ?, 
                arcanoi = ?, backgrounds = ?, backgroundDetails = ?, 
                willpower_permanent = ?, willpower_current = ?, 
                pathos_corpus = ?, shadow = ?, harrowing = ?, 
                merits_flaws = ?, status_details = ?, relationships = ?, 
                artifacts = ?, actingNotes = ?, agentNotes = ?, health_status = ?,
                experience_total = ?, spent_xp = ?, experience_unspent = ?,
                shadow_xp_total = ?, shadow_xp_spent = ?, shadow_xp_available = ?" .
                ($cleanData['character_image'] !== '' ? ", character_image = ?" : "") .
                " WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
            }
            
            $params = [
                $cleanData['user_id'],
                $cleanData['shadow_name'],
                $cleanData['player_name'],
                $cleanData['chronicle'],
                $cleanData['nature'],
                $cleanData['demeanor'],
                $cleanData['concept'],
                $cleanData['circle'],
                $cleanData['guild'],
                $cleanData['legion_at_death'],
                $cleanData['date_of_death'],
                $cleanData['cause_of_death'],
                $cleanData['pc'],
                $cleanData['appearance'],
                $cleanData['ghostly_appearance'],
                $cleanData['biography'],
                $cleanData['notes'],
                $cleanData['equipment'],
                $cleanData['custom_data'],
                $cleanData['status'],
                $cleanData['timeline'],
                $cleanData['personality'],
                $cleanData['traits'],
                $cleanData['negativeTraits'],
                $cleanData['abilities'],
                $cleanData['specializations'],
                $cleanData['fetters'],
                $cleanData['passions'],
                $cleanData['arcanoi'],
                $cleanData['backgrounds'],
                $cleanData['backgroundDetails'],
                $cleanData['willpower_permanent'],
                $cleanData['willpower_current'],
                $cleanData['pathos_corpus'],
                $cleanData['shadow'],
                $cleanData['harrowing'],
                $cleanData['merits_flaws'],
                $cleanData['status_details'],
                $cleanData['relationships'],
                $cleanData['artifacts'],
                $cleanData['actingNotes'],
                $cleanData['agentNotes'],
                $cleanData['health_status'],
                $cleanData['experience_total'],
                $cleanData['spent_xp'],
                $cleanData['experience_unspent'],
                $cleanData['shadow_xp_total'],
                $cleanData['shadow_xp_spent'],
                $cleanData['shadow_xp_available']
            ];
            
            $types = 'isssssssssssisssssssssssssssssssssssssssssssssssssiiiiiii';
            
            if ($cleanData['character_image'] !== '') {
                $params[] = $cleanData['character_image'];
                $types .= 's';
            }
            
            $params[] = $character_id;
            $types .= 'i';
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update character: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            $stats['updated']++;
        } else {
            // Insert new character
            $base_fields = [
                'user_id', 'character_name', 'shadow_name', 'player_name', 'chronicle', 
                'nature', 'demeanor', 'concept', 'circle', 'guild', 
                'legion_at_death', 'date_of_death', 'cause_of_death', 
                'pc', 'appearance', 'ghostly_appearance', 'biography', 
                'notes', 'equipment', 'custom_data', 'status', 
                'timeline', 'personality', 'traits', 'negativeTraits', 
                'abilities', 'specializations', 'fetters', 'passions', 
                'arcanoi', 'backgrounds', 'backgroundDetails', 
                'willpower_permanent', 'willpower_current', 
                'pathos_corpus', 'shadow', 'harrowing', 
                'merits_flaws', 'status_details', 'relationships', 
                'artifacts', 'actingNotes', 'agentNotes', 'health_status',
                'experience_total', 'spent_xp', 'experience_unspent',
                'shadow_xp_total', 'shadow_xp_spent', 'shadow_xp_available'
            ];
            
            if ($cleanData['character_image'] !== '') {
                $base_fields[] = 'character_image';
            }
            
            $field_list = implode(', ', $base_fields);
            $placeholder_list = str_repeat('?,', count($base_fields) - 1) . '?';
            
            $insert_sql = "INSERT INTO wraith_characters ($field_list) VALUES ($placeholder_list)";
            
            $stmt = mysqli_prepare($conn, $insert_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare insert: ' . mysqli_error($conn));
            }
            
            // Build params array dynamically based on field list
            $params = [];
            $types = '';
            foreach ($base_fields as $field) {
                $params[] = $cleanData[$field];
                // Determine type based on field name
                if ($field === 'user_id' || $field === 'pc' || 
                    $field === 'willpower_permanent' || $field === 'willpower_current' ||
                    $field === 'experience_total' || $field === 'spent_xp' || 
                    $field === 'experience_unspent' || $field === 'shadow_xp_total' || 
                    $field === 'shadow_xp_spent' || $field === 'shadow_xp_available') {
                    $types .= 'i';
                } elseif ($field === 'date_of_death') {
                    $types .= 's'; // DATE fields are strings in MySQL
                } else {
                    $types .= 's';
                }
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to insert character: ' . mysqli_stmt_error($stmt));
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

// Get file parameter
$file_param = $is_cli ? ($argv[1] ?? '') : ($_GET['file'] ?? '');
$import_all = !$is_cli && isset($_GET['all']) && $_GET['all'] == '1';

$wraiths_dir = __DIR__ . '/../reference/Characters/Wraiths/';

if ($import_all) {
    // Import all JSON files in Wraiths directory
    $files = glob($wraiths_dir . '*.json');
    $files = array_filter($files, function($f) {
        return basename($f) !== 'wraith_character_template.json';
    });
    
    if (empty($files)) {
        echo $is_cli ? "No JSON files found in $wraiths_dir\n" : "<p class='warning'>No JSON files found in Wraiths directory.</p>";
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
            
            if (importWraithCharacter($conn, $data, $filename)) {
                echo $is_cli ? "✓ Imported: $filename\n" : "<p class='success'>✓ Imported: $filename</p>";
            } else {
                $stats['skipped']++;
            }
        }
    }
} elseif (!empty($file_param)) {
    // Import single file
    $file_path = $wraiths_dir . $file_param;
    if (!file_exists($file_path)) {
        // Try direct path
        $file_path = $file_param;
        if (!file_exists($file_path)) {
            die($is_cli ? "File not found: $file_param\n" : "<p class='error'>File not found: $file_param</p></body></html>");
        }
    }
    
    $filename = basename($file_path);
    $stats['processed']++;
    
    $json_content = file_get_contents($file_path);
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die($is_cli ? "Invalid JSON: " . json_last_error_msg() . "\n" : "<p class='error'>Invalid JSON: " . json_last_error_msg() . "</p></body></html>");
    }
    
    if (importWraithCharacter($conn, $data, $filename)) {
        echo $is_cli ? "✓ Successfully imported: $filename\n" : "<p class='success'>✓ Successfully imported: $filename</p>";
    }
} else {
    die($is_cli ? "Usage: php import_wraith_characters.php [filename.json]\n" : "<p class='error'>Usage: ?file=filename.json or ?all=1</p></body></html>");
}

// Print statistics
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
?>

