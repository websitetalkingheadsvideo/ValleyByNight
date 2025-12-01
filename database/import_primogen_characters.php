<?php
/**
 * Import Primogen Characters Script
 * 
 * Imports CW Whitford (Ventrue Primogen) and Naomi Blackbird (Gangrel Primogen)
 * into the database and assigns their primogen positions.
 * 
 * Usage:
 *   CLI: php database/import_primogen_characters.php
 *   Web: https://vbn.talkingheads.video/database/import_primogen_characters.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Import Primogen Characters</title></head><body>";
    echo "<h1>Import Primogen Characters</h1>";
    echo "<pre>";
} else {
    echo "Import Primogen Characters Script\n";
    echo "==================================\n\n";
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Default night for assignments
$default_night = CAMARILLA_DEFAULT_NIGHT;

// Characters to import
$characters = [
    [
        'filename' => 'CW Whitford.json',
        'character_name' => 'Charles "C.W." Whitford',
        'position_name' => 'Ventrue Primogen',
        'clan' => 'Ventrue'
    ],
    [
        'filename' => 'Naomi Blackbird.json',
        'character_name' => 'Naomi Blackbird',
        'position_name' => 'Gangrel Primogen',
        'clan' => 'Gangrel'
    ]
];

$results = [];

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    $characters_dir = __DIR__ . '/../reference/Characters';
    define('IMPORT_USER_ID', 1);
    
    // Helper functions (copied from import_characters.php to avoid executing its main code)
    function cleanString($value) {
        if (is_array($value)) {
            if (isset($value['detailed_description'])) return trim((string)$value['detailed_description']);
            if (isset($value['description'])) return trim((string)$value['description']);
            if (isset($value['short_summary'])) return trim((string)$value['short_summary']);
            return json_encode($value);
        }
        if (is_string($value)) return trim($value);
        if (is_null($value)) return '';
        return (string)$value;
    }
    
    function cleanInt($value, int $default = 0): int {
        if (is_null($value) || $value === '') return $default;
        return (int)$value;
    }
    
    function cleanJsonData($value) {
        if (empty($value)) return null;
        if (is_array($value) || is_object($value)) return json_encode($value);
        $trimmed = trim((string)$value);
        if ($trimmed === '') return null;
        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) return $trimmed;
        return json_encode(['text' => $trimmed]);
    }
    
    function normalizeCharacterData(array $data): array {
        $normalized = $data;
        if (isset($normalized['name']) && !isset($normalized['character_name'])) {
            $normalized['character_name'] = $normalized['name'];
            unset($normalized['name']);
        }
        if (isset($normalized['status']) && is_array($normalized['status'])) {
            $statusObj = $normalized['status'];
            if (isset($statusObj['current_state'])) $normalized['status'] = $statusObj['current_state'];
            elseif (isset($statusObj['status'])) $normalized['status'] = $statusObj['status'];
            else $normalized['status'] = 'active';
        }
        if (isset($normalized['status_details']) && is_array($normalized['status_details'])) {
            $statusDetails = $normalized['status_details'];
            if (isset($statusDetails['current_state']) && !isset($normalized['status'])) {
                $normalized['status'] = $statusDetails['current_state'];
            }
            if (isset($statusDetails['camarilla_status']) && !isset($normalized['camarilla_status'])) {
                $normalized['camarilla_status'] = $statusDetails['camarilla_status'];
            }
        }
        if (isset($normalized['appearance']) && is_array($normalized['appearance'])) {
            if (isset($normalized['appearance']['detailed_description'])) {
                $normalized['appearance'] = $normalized['appearance']['detailed_description'];
            } elseif (isset($normalized['appearance']['description'])) {
                $normalized['appearance'] = $normalized['appearance']['description'];
            } elseif (isset($normalized['appearance']['short_summary'])) {
                $normalized['appearance'] = $normalized['appearance']['short_summary'];
            } else {
                $normalized['appearance'] = json_encode($normalized['appearance']);
            }
        }
        if (empty($normalized['appearance']) && isset($normalized['description']) && is_string($normalized['description'])) {
            $normalized['appearance'] = $normalized['description'];
        }
        if (isset($normalized['current_state']) && !isset($normalized['status'])) {
            $normalized['status'] = $normalized['current_state'];
        }
        return $normalized;
    }
    
    function findCharacterByName(mysqli $conn, string $character_name): ?int {
        $result = db_fetch_one($conn, "SELECT id FROM characters WHERE character_name = ? LIMIT 1", 's', [$character_name]);
        return $result ? (int)$result['id'] : null;
    }
    
    function upsertCharacter(mysqli $conn, array $data): int {
        $character_name = cleanString($data['character_name'] ?? '');
        if (empty($character_name)) throw new Exception("Character name is required");
        
        $character_id = findCharacterByName($conn, $character_name);
        
        $cleanData = [
            'user_id' => IMPORT_USER_ID,
            'character_name' => $character_name,
            'player_name' => cleanString($data['player_name'] ?? 'NPC'),
            'chronicle' => cleanString($data['chronicle'] ?? 'Valley by Night'),
            'nature' => cleanString($data['nature'] ?? ''),
            'demeanor' => cleanString($data['demeanor'] ?? ''),
            'concept' => cleanString($data['concept'] ?? ''),
            'clan' => cleanString($data['clan'] ?? ''),
            'generation' => cleanInt($data['generation'] ?? 13),
            'sire' => cleanString($data['sire'] ?? ''),
            'pc' => cleanInt($data['pc'] ?? 0),
            'appearance' => cleanString($data['appearance'] ?? ''),
            'biography' => cleanString($data['biography'] ?? ''),
            'notes' => cleanString($data['notes'] ?? ''),
            'equipment' => cleanString($data['equipment'] ?? ''),
            'character_image' => cleanString($data['character_image'] ?? ''),
            'status' => cleanString($data['status'] ?? 'active'),
            'camarilla_status' => cleanString($data['camarilla_status'] ?? 'Unknown'),
            'custom_data' => cleanJsonData($data['custom_data'] ?? null)
        ];
        
        $validStates = ['active', 'inactive', 'archived'];
        $cleanData['status'] = strtolower($cleanData['status']);
        if (!in_array($cleanData['status'], $validStates, true)) $cleanData['status'] = 'active';
        
        $validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
        $camarillaValue = $cleanData['camarilla_status'] ? ucfirst(strtolower($cleanData['camarilla_status'])) : 'Unknown';
        if (!in_array($camarillaValue, $validCamarilla, true)) $camarillaValue = 'Unknown';
        $cleanData['camarilla_status'] = $camarillaValue;
        
        if ($character_id) {
            $update_sql = "UPDATE characters SET player_name = ?, chronicle = ?, nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, equipment = ?, custom_data = ?, status = ?, camarilla_status = ?" . ($cleanData['character_image'] !== '' ? ", character_image = ?" : "") . " WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
            
            $params = [$cleanData['player_name'], $cleanData['chronicle'], $cleanData['nature'], $cleanData['demeanor'], $cleanData['concept'], $cleanData['clan'], $cleanData['generation'], $cleanData['sire'], $cleanData['pc'], $cleanData['appearance'], $cleanData['biography'], $cleanData['notes'], $cleanData['equipment'], $cleanData['custom_data'], $cleanData['status'], $cleanData['camarilla_status']];
            if ($cleanData['character_image'] !== '') $params[] = $cleanData['character_image'];
            $params[] = $character_id;
            
            $types = 'sssssssissssssss' . ($cleanData['character_image'] !== '' ? 's' : '') . 'i';
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new Exception('Failed to update character: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $insert_sql = "INSERT INTO characters (user_id, character_name, player_name, chronicle, character_image, status, camarilla_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            if (!$stmt) throw new Exception('Failed to prepare insert: ' . mysqli_error($conn));
            
            mysqli_stmt_bind_param($stmt, 'issssss', $cleanData['user_id'], $cleanData['character_name'], $cleanData['player_name'], $cleanData['chronicle'], $cleanData['character_image'], $cleanData['status'], $cleanData['camarilla_status']);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new Exception('Failed to insert character: ' . mysqli_stmt_error($stmt));
            }
            $character_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            $update_sql = "UPDATE characters SET nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, equipment = ?, custom_data = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) throw new Exception('Failed to prepare update after insert: ' . mysqli_error($conn));
            
            mysqli_stmt_bind_param($stmt, 'sssssissssssi', $cleanData['nature'], $cleanData['demeanor'], $cleanData['concept'], $cleanData['clan'], $cleanData['generation'], $cleanData['sire'], $cleanData['pc'], $cleanData['appearance'], $cleanData['biography'], $cleanData['notes'], $cleanData['equipment'], $cleanData['custom_data'], $character_id);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new Exception('Failed to update character after insert: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
        return $character_id;
    }
    
    foreach ($characters as $char_data) {
        $filename = $char_data['filename'];
        $filepath = $characters_dir . '/' . $filename;
        $character_name = $char_data['character_name'];
        $position_name = $char_data['position_name'];
        $clan = $char_data['clan'];
        
        echo "Processing: $character_name\n";
        echo str_repeat("-", 60) . "\n";
        
        // Step 1: Import character JSON
        echo "Step 1: Importing character from $filename...\n";
        
        if (!file_exists($filepath)) {
            throw new Exception("Character file not found: $filepath");
        }
        
        $jsonContent = file_get_contents($filepath);
        if ($jsonContent === false) throw new Exception("Failed to read file: $filepath");
        
        $jsonContent = preg_replace_callback('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', function($matches) {
            $str = $matches[1];
            $str = str_replace(["\n", "\r", "\t"], ['\\n', '\\r', '\\t'], $str);
            return '"' . $str . '"';
        }, $jsonContent);
        
        $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonContent);
        
        $data = json_decode($jsonContent, true);
        if ($data === null) {
            if (json_last_error() === JSON_ERROR_UTF8) {
                $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
                $data = json_decode($jsonContent, true);
                if ($data === null) throw new Exception("Invalid JSON (encoding issue): " . json_last_error_msg());
            } else {
                throw new Exception("Invalid JSON: " . json_last_error_msg());
            }
        }
        
        $data = normalizeCharacterData($data);
        
        if (empty($data['character_name']) && empty($data['name'])) {
            throw new Exception("Missing required field: character_name");
        }
        
        $character_id = upsertCharacter($conn, $data);
        
        echo "  ✅ Character imported successfully (ID: $character_id)\n";
        
        // Step 1.2: Verify character exists in database
        $character_query = "SELECT id, character_name, clan FROM characters WHERE character_name = ?";
        $character = db_fetch_one($conn, $character_query, "s", [$character_name]);
        
        if (!$character) {
            throw new Exception("Character not found in database after import: $character_name");
        }
        
        $character_id = $character['id'];
        $db_character_name = $character['character_name'];
        $db_clan = $character['clan'];
        
        echo "  ✅ Character verified: $db_character_name (ID: $character_id, Clan: $db_clan)\n";
        
        // Step 2: Assign primogen position
        echo "\nStep 2: Assigning primogen position...\n";
        
        // Find or create position
        $position_query = "SELECT position_id, name FROM camarilla_positions WHERE name = ?";
        $position = db_fetch_one($conn, $position_query, "s", [$position_name]);
        
        if (!$position) {
            echo "  Position not found, creating new position...\n";
            
            // Create position following pattern: primogen_{clan_lowercase}
            $position_id_new = "primogen_" . strtolower($clan);
            $category = "primogen";
            
            // Check if position_id already exists
            $check_id = db_fetch_one($conn, "SELECT position_id FROM camarilla_positions WHERE position_id = ?", "s", [$position_id_new]);
            if ($check_id) {
                throw new Exception("Position ID '$position_id_new' already exists with different name. Please check database.");
            }
            
            // Create the position
            $create_query = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                            VALUES (?, ?, ?, ?, ?)";
            $description = "$clan Primogen position in the Camarilla court of Phoenix.";
            $importance_rank = 3; // Primogen are important but not as high as Prince/Seneschal
            
            $created = db_execute($conn, $create_query, "ssssi", [
                $position_id_new,
                $position_name,
                $category,
                $description,
                $importance_rank
            ]);
            
            if ($created === false) {
                throw new Exception("Failed to create position '$position_name': " . mysqli_error($conn));
            }
            
            echo "  ✅ Created position: $position_name (ID: $position_id_new)\n";
            $position = [
                'position_id' => $position_id_new,
                'name' => $position_name
            ];
        } else {
            echo "  ✅ Found existing position: {$position['name']} (ID: {$position['position_id']})\n";
        }
        
        $position_id = $position['position_id'];
        
        // Format character_id for assignment table (UPPER with underscores)
        $assignment_character_id = strtoupper(str_replace(' ', '_', $db_character_name));
        echo "  Assignment character_id format: $assignment_character_id\n";
        
        // Check if assignment already exists
        $check_query = "SELECT id, start_night, end_night, is_acting 
                       FROM camarilla_position_assignments 
                       WHERE position_id = ? AND character_id = ? 
                       AND (end_night IS NULL OR end_night >= ?)";
        $existing = db_fetch_one($conn, $check_query, "sss", [$position_id, $assignment_character_id, $default_night]);
        
        if ($existing) {
            echo "  ⚠️  Assignment already exists (ID: {$existing['id']})\n";
            echo "     Start: {$existing['start_night']}, End: " . ($existing['end_night'] ?? 'NULL') . ", Acting: {$existing['is_acting']}\n";
            
            // Update existing assignment if needed
            if ($existing['end_night'] !== null || $existing['is_acting'] != 0) {
                echo "  Updating existing assignment...\n";
                $update_query = "UPDATE camarilla_position_assignments 
                                SET start_night = ?, end_night = NULL, is_acting = 0 
                                WHERE id = ?";
                $updated = db_execute($conn, $update_query, "si", [$default_night, $existing['id']]);
                
                if ($updated !== false) {
                    echo "  ✅ Updated existing assignment\n";
                    $results[] = [
                        'character' => $character_name,
                        'position' => $position_name,
                        'status' => 'updated',
                        'assignment_id' => $existing['id']
                    ];
                } else {
                    throw new Exception("Failed to update assignment for $character_name");
                }
            } else {
                echo "  ✅ Assignment already active, no changes needed\n";
                $results[] = [
                    'character' => $character_name,
                    'position' => $position_name,
                    'status' => 'already_exists'
                ];
            }
        } else {
            // Create new assignment
            echo "  Creating new assignment...\n";
            $insert_query = "INSERT INTO camarilla_position_assignments 
                           (position_id, character_id, start_night, end_night, is_acting) 
                           VALUES (?, ?, ?, NULL, 0)";
            $assignment_id = db_execute($conn, $insert_query, "sss", [
                $position_id,
                $assignment_character_id,
                $default_night
            ]);
            
            if ($assignment_id === false) {
                throw new Exception("Failed to create assignment for $character_name: " . mysqli_error($conn));
            }
            
            echo "  ✅ Created assignment (ID: $assignment_id)\n";
            $results[] = [
                'character' => $character_name,
                'position' => $position_name,
                'status' => 'created',
                'assignment_id' => $assignment_id
            ];
        }
        
        echo "\n";
    }
    
    // Commit transaction
    if (!db_commit($conn)) {
        throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
    }
    
    echo "✅ Transaction committed successfully!\n\n";
    echo "=== Summary ===\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($results as $result) {
        echo sprintf("%-30s -> %-25s [%s]\n", 
            $result['character'], 
            $result['position'], 
            $result['status']
        );
    }
    
    echo "\n✅ All primogen characters imported and assigned successfully!\n";
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);
?>

