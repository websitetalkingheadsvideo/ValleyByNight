<?php
/**
 * Import New Characters Script
 * 
 * Imports new characters from reference/Characters directory into the database.
 * 
 * Characters to import:
 * - Travis Adelson
 * - Victoria Sterling
 * - Rey Gonzalez
 * - Barry Horowitz
 * - Dorikhan Caine
 * - Evan Mercer
 * - Lila Moreno
 * 
 * Usage:
 *   CLI: php database/import_new_characters.php
 *   Web: database/import_new_characters.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Import New Characters</title></head><body>";
    echo "<h1>Import New Characters</h1>";
    echo "<pre>";
} else {
    echo "Import New Characters Script\n";
    echo "============================\n\n";
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Characters to import
$characters = [
    [
        'filename' => 'Travis Adelson.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Travis Adelson.json'
    ],
    [
        'filename' => 'Victoria_Sterling.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Victoria_Sterling.json'
    ],
    [
        'filename' => 'Rey_Gonzalez.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Rey_Gonzalez.json'
    ],
    [
        'filename' => 'Barry Horowitz.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Barry Horowitz.json'
    ],
    [
        'filename' => 'Dorikhan Caine2015.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Dorikhan Caine2015.json'
    ],
    [
        'filename' => 'Evan Mercer.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Evan Mercer.json'
    ],
    [
        'filename' => 'Lila Moreno.json',
        'path' => __DIR__ . '/../reference/Characters/Added to Database/Lila Moreno.json'
    ]
];

define('IMPORT_USER_ID', 1);

// Helper functions
function cleanString($value): string {
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
    
    // Normalize record_status -> status
    if (isset($normalized['record_status']) && !isset($normalized['status'])) {
        $normalized['status'] = $normalized['record_status'];
    }
    
    // Handle appearance object
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
    
    // Handle status object
    if (isset($normalized['status']) && is_array($normalized['status'])) {
        $statusObj = $normalized['status'];
        if (isset($statusObj['current_state'])) {
            $normalized['status'] = $statusObj['current_state'];
        } elseif (isset($statusObj['status'])) {
            $normalized['status'] = $statusObj['status'];
        } else {
            $normalized['status'] = 'active';
        }
    }
    
    // Handle custom_data status_details
    if (isset($normalized['custom_data']) && is_array($normalized['custom_data'])) {
        if (isset($normalized['custom_data']['status_details'])) {
            $statusDetails = $normalized['custom_data']['status_details'];
            if (isset($statusDetails['current_state']) && !isset($normalized['status'])) {
                $normalized['status'] = $statusDetails['current_state'];
            }
            if (isset($statusDetails['camarilla_status']) && !isset($normalized['camarilla_status'])) {
                $normalized['camarilla_status'] = $statusDetails['camarilla_status'];
            }
        }
    }
    
    return $normalized;
}

function findCharacterByName(mysqli $conn, string $character_name): ?int {
    $result = db_fetch_one($conn, "SELECT id FROM characters WHERE character_name = ? LIMIT 1", 's', [$character_name]);
    return $result ? (int)$result['id'] : null;
}

function upsertCharacter(mysqli $conn, array $data): int {
    $character_name = cleanString($data['character_name'] ?? '');
    if (empty($character_name)) {
        throw new Exception("Character name is required");
    }
    
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
    if (!in_array($cleanData['status'], $validStates, true)) {
        $cleanData['status'] = 'active';
    }
    
    $validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
    $camarillaValue = $cleanData['camarilla_status'] ? ucfirst(strtolower($cleanData['camarilla_status'])) : 'Unknown';
    if (!in_array($camarillaValue, $validCamarilla, true)) {
        $camarillaValue = 'Unknown';
    }
    $cleanData['camarilla_status'] = $camarillaValue;
    
    if ($character_id) {
        // Update existing character
        $update_sql = "UPDATE characters SET player_name = ?, chronicle = ?, nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, equipment = ?, custom_data = ?, status = ?, camarilla_status = ?" . ($cleanData['character_image'] !== '' ? ", character_image = ?" : "") . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
        }
        
        $params = [
            $cleanData['player_name'], $cleanData['chronicle'], $cleanData['nature'], 
            $cleanData['demeanor'], $cleanData['concept'], $cleanData['clan'], 
            $cleanData['generation'], $cleanData['sire'], $cleanData['pc'], 
            $cleanData['appearance'], $cleanData['biography'], $cleanData['notes'], 
            $cleanData['equipment'], $cleanData['custom_data'], $cleanData['status'], 
            $cleanData['camarilla_status']
        ];
        if ($cleanData['character_image'] !== '') {
            $params[] = $cleanData['character_image'];
        }
        $params[] = $character_id;
        
        $types = 'sssssssissssssss' . ($cleanData['character_image'] !== '' ? 's' : '') . 'i';
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('Failed to update character: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        echo "  ✅ Updated existing character: $character_name (ID: $character_id)\n";
    } else {
        // Insert new character
        $insert_sql = "INSERT INTO characters (user_id, character_name, player_name, chronicle, character_image, status, camarilla_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare insert: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'issssss', 
            $cleanData['user_id'], 
            $cleanData['character_name'], 
            $cleanData['player_name'], 
            $cleanData['chronicle'], 
            $cleanData['character_image'], 
            $cleanData['status'], 
            $cleanData['camarilla_status']
        );
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('Failed to insert character: ' . mysqli_stmt_error($stmt));
        }
        $character_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Update with remaining fields
        $update_sql = "UPDATE characters SET nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, equipment = ?, custom_data = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update after insert: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'sssssissssssi', 
            $cleanData['nature'], $cleanData['demeanor'], $cleanData['concept'], 
            $cleanData['clan'], $cleanData['generation'], $cleanData['sire'], 
            $cleanData['pc'], $cleanData['appearance'], $cleanData['biography'], 
            $cleanData['notes'], $cleanData['equipment'], $cleanData['custom_data'], 
            $character_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('Failed to update character after insert: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        echo "  ✅ Inserted new character: $character_name (ID: $character_id)\n";
    }
    
    return $character_id;
}

$results = [];
$errors = [];

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    echo "Transaction started\n";
    
    foreach ($characters as $char_data) {
        $filename = $char_data['filename'];
        $filepath = $char_data['path'];
        
        echo "\nProcessing: $filename\n";
        echo str_repeat("-", 60) . "\n";
        
        if (!file_exists($filepath)) {
            $error = "Character file not found: $filepath";
            echo "  ❌ ERROR: $error\n";
            $errors[] = $error;
            continue;
        }
        
        $jsonContent = file_get_contents($filepath);
        if ($jsonContent === false) {
            $error = "Failed to read file: $filepath";
            echo "  ❌ ERROR: $error\n";
            $errors[] = $error;
            continue;
        }
        
        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = "JSON decode error: " . json_last_error_msg() . " in $filename";
            echo "  ❌ ERROR: $error\n";
            $errors[] = $error;
            continue;
        }
        
        $data = normalizeCharacterData($data);
        
        try {
            $character_id = upsertCharacter($conn, $data);
            $results[] = [
                'filename' => $filename,
                'character_name' => $data['character_name'],
                'character_id' => $character_id,
                'status' => 'success'
            ];
        } catch (Exception $e) {
            $error = "Failed to import $filename: " . $e->getMessage();
            echo "  ❌ ERROR: $error\n";
            $errors[] = $error;
            $results[] = [
                'filename' => $filename,
                'character_name' => $data['character_name'] ?? 'Unknown',
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    if (empty($errors)) {
        if (!db_commit($conn)) {
            throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
        }
        
        // Verify all characters were actually added to the database
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Verifying characters in database...\n";
        echo str_repeat("=", 60) . "\n";
        
        $verification_errors = [];
        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $character_name = $result['character_name'];
                $character_id = $result['character_id'];
                
                // Verify character exists in database
                $verify_result = db_fetch_one($conn, 
                    "SELECT id, character_name FROM characters WHERE id = ? AND character_name = ? LIMIT 1",
                    'is',
                    [$character_id, $character_name]
                );
                
                if (!$verify_result) {
                    $verification_error = "VERIFICATION FAILED: Character '$character_name' (ID: $character_id) not found in database after import";
                    echo "  ❌ $verification_error\n";
                    $verification_errors[] = $verification_error;
                    $result['status'] = 'verification_failed';
                } else {
                    echo "  ✅ Verified: $character_name (ID: $character_id)\n";
                }
            }
        }
        
        if (!empty($verification_errors)) {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "❌ VERIFICATION FAILED: Some characters were not added to the database!\n";
            echo str_repeat("=", 60) . "\n";
            foreach ($verification_errors as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "✅ SUCCESS: All characters imported and verified successfully!\n";
        echo str_repeat("=", 60) . "\n";
    } else {
        if (!db_rollback($conn)) {
            throw new Exception("Failed to rollback transaction: " . mysqli_error($conn));
        }
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "❌ ERRORS OCCURRED: Transaction rolled back\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        exit(1);
    }
    
    // Force output flush
    if ($is_cli) {
        flush();
    }
    
} catch (Exception $e) {
    if (!db_rollback($conn)) {
        echo "WARNING: Failed to rollback transaction: " . mysqli_error($conn) . "\n";
    }
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

if (!$is_cli) {
    echo "</pre></body></html>";
}
