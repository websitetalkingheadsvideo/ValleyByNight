<?php
/**
 * Import Trace Element Character Script
 * 
 * Imports Trace Element from reference/Characters/Trace Element.json into the database.
 * Handles all related data: abilities, disciplines, traits, backgrounds, coteries, relationships.
 * 
 * Usage:
 *   CLI: php database/import_trace_element.php
 *   Web: database/import_trace_element.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Import Trace Element</title></head><body>";
    echo "<h1>Import Trace Element</h1>";
    echo "<pre>";
} else {
    echo "Import Trace Element Script\n";
    echo "==========================\n\n";
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

define('IMPORT_USER_ID', 1);

// Helper functions (from import_new_characters.php)
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
    
    // Handle status_details
    if (isset($normalized['status_details']) && is_array($normalized['status_details'])) {
        $statusDetails = $normalized['status_details'];
        if (isset($statusDetails['current_state']) && !isset($normalized['status'])) {
            $normalized['status'] = $statusDetails['current_state'];
        }
        if (isset($statusDetails['camarilla_status']) && !isset($normalized['camarilla_status'])) {
            $normalized['camarilla_status'] = $statusDetails['camarilla_status'];
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

// Import related data functions
function importAbilities(mysqli $conn, int $character_id, array $data): void {
    // Delete existing abilities
    db_execute($conn, "DELETE FROM character_abilities WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['abilities']) || !is_array($data['abilities'])) {
        return;
    }
    
    $specializations = $data['specializations'] ?? [];
    
    // Check if ability_category column exists
    $check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
    $column_check = mysqli_query($conn, $check_column_sql);
    $has_category_column = ($column_check && mysqli_num_rows($column_check) > 0);
    if ($column_check) {
        mysqli_free_result($column_check);
    }
    
    if ($has_category_column) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?)";
    } else {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
    }
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare ability insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    foreach ($data['abilities'] as $ability) {
        if (!is_array($ability) || !isset($ability['name'])) {
            continue;
        }
        
        $name = cleanString($ability['name']);
        $category = isset($ability['category']) ? cleanString($ability['category']) : null;
        $level = max(1, min(5, cleanInt($ability['level'] ?? 1)));
        $specialization = isset($specializations[$name]) ? cleanString($specializations[$name]) : null;
        
        if ($has_category_column) {
            mysqli_stmt_bind_param($stmt, 'issis', $character_id, $name, $category, $level, $specialization);
        } else {
            mysqli_stmt_bind_param($stmt, 'isis', $character_id, $name, $level, $specialization);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    
    mysqli_stmt_close($stmt);
    echo "  ✅ Imported $count abilities\n";
}

function importDisciplines(mysqli $conn, int $character_id, array $data): void {
    // Delete existing disciplines
    db_execute($conn, "DELETE FROM character_disciplines WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['disciplines']) || !is_array($data['disciplines'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare discipline insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    foreach ($data['disciplines'] as $discipline) {
        if (!is_array($discipline) || !isset($discipline['name'])) {
            continue;
        }
        
        $name = cleanString($discipline['name']);
        $level = max(1, min(5, cleanInt($discipline['level'] ?? 1)));
        
        mysqli_stmt_bind_param($stmt, 'isi', $character_id, $name, $level);
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    
    mysqli_stmt_close($stmt);
    echo "  ✅ Imported $count disciplines\n";
}

function importTraits(mysqli $conn, int $character_id, array $data): void {
    // Delete existing positive traits
    db_execute($conn, "DELETE FROM character_traits WHERE character_id = ? AND (trait_type IS NULL OR trait_type = 'positive')", 'i', [$character_id]);
    
    if (!isset($data['traits']) || !is_array($data['traits'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, 'positive')";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare trait insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    $allowedCategories = ['Physical', 'Social', 'Mental'];
    
    foreach ($data['traits'] as $category => $traitNames) {
        $normalizedCategory = ucfirst(strtolower($category));
        if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
            continue;
        }
        
        foreach ($traitNames as $traitName) {
            $cleanName = cleanString($traitName);
            if ($cleanName === '') {
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'iss', $character_id, $cleanName, $normalizedCategory);
            if (mysqli_stmt_execute($stmt)) {
                $count++;
            }
        }
    }
    
    mysqli_stmt_close($stmt);
    echo "  ✅ Imported $count positive traits\n";
}

function importNegativeTraits(mysqli $conn, int $character_id, array $data): void {
    // Delete existing negative traits
    db_execute($conn, "DELETE FROM character_negative_traits WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['negativeTraits']) || !is_array($data['negativeTraits'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_negative_traits (character_id, trait_category, trait_name) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare negative trait insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    $allowedCategories = ['Physical', 'Social', 'Mental'];
    
    foreach ($data['negativeTraits'] as $category => $traitNames) {
        $normalizedCategory = ucfirst(strtolower($category));
        if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
            continue;
        }
        
        foreach ($traitNames as $traitName) {
            $cleanName = cleanString($traitName);
            if ($cleanName === '') {
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'iss', $character_id, $normalizedCategory, $cleanName);
            if (mysqli_stmt_execute($stmt)) {
                $count++;
            }
        }
    }
    
    mysqli_stmt_close($stmt);
    if ($count > 0) {
        echo "  ✅ Imported $count negative traits\n";
    }
}

function importBackgrounds(mysqli $conn, int $character_id, array $data): void {
    // Delete existing backgrounds
    db_execute($conn, "DELETE FROM character_backgrounds WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['backgrounds']) || !is_array($data['backgrounds'])) {
        return;
    }
    
    $backgroundDetails = $data['backgroundDetails'] ?? [];
    
    $insert_sql = "INSERT INTO character_backgrounds (character_id, background_name, level, description) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare background insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    foreach ($data['backgrounds'] as $name => $level) {
        $cleanName = cleanString($name);
        $cleanLevel = max(1, min(5, cleanInt($level)));
        $description = isset($backgroundDetails[$name]) ? cleanString($backgroundDetails[$name]) : null;
        
        mysqli_stmt_bind_param($stmt, 'isis', $character_id, $cleanName, $cleanLevel, $description);
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    
    mysqli_stmt_close($stmt);
    echo "  ✅ Imported $count backgrounds\n";
}

function importCoteries(mysqli $conn, int $character_id, array $data): void {
    // Delete existing coteries
    db_execute($conn, "DELETE FROM character_coteries WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['coteries']) || !is_array($data['coteries'])) {
        return;
    }
    
    // Check if notes column exists
    $check_column_sql = "SHOW COLUMNS FROM character_coteries LIKE 'notes'";
    $column_check = mysqli_query($conn, $check_column_sql);
    $has_notes_column = ($column_check && mysqli_num_rows($column_check) > 0);
    if ($column_check) {
        mysqli_free_result($column_check);
    }
    
    if ($has_notes_column) {
        $insert_sql = "INSERT INTO character_coteries (character_id, coterie_name, coterie_type, role, description, notes) VALUES (?, ?, ?, ?, ?, ?)";
    } else {
        $insert_sql = "INSERT INTO character_coteries (character_id, coterie_name, coterie_type, role, description) VALUES (?, ?, ?, ?, ?)";
    }
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare coterie insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    foreach ($data['coteries'] as $coterie) {
        if (empty($coterie['coterie_name'])) {
            continue;
        }
        
        if ($has_notes_column) {
            mysqli_stmt_bind_param($stmt, 'isssss',
                $character_id,
                cleanString($coterie['coterie_name'] ?? ''),
                cleanString($coterie['coterie_type'] ?? ''),
                cleanString($coterie['role'] ?? ''),
                cleanString($coterie['description'] ?? ''),
                cleanString($coterie['notes'] ?? '')
            );
        } else {
            mysqli_stmt_bind_param($stmt, 'issss',
                $character_id,
                cleanString($coterie['coterie_name'] ?? ''),
                cleanString($coterie['coterie_type'] ?? ''),
                cleanString($coterie['role'] ?? ''),
                cleanString($coterie['description'] ?? '')
            );
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    
    mysqli_stmt_close($stmt);
    if ($count > 0) {
        echo "  ✅ Imported $count coteries\n";
    }
}

function importRelationships(mysqli $conn, int $character_id, array $data): void {
    // Delete existing relationships
    db_execute($conn, "DELETE FROM character_relationships WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['relationships']) || !is_array($data['relationships'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_relationships (character_id, related_character_name, relationship_type, relationship_subtype, strength, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare relationship insert: ' . mysqli_error($conn));
    }
    
    $count = 0;
    foreach ($data['relationships'] as $relationship) {
        if (empty($relationship['related_character_name'])) {
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, 'isssss',
            $character_id,
            cleanString($relationship['related_character_name'] ?? ''),
            cleanString($relationship['relationship_type'] ?? ''),
            cleanString($relationship['relationship_subtype'] ?? ''),
            cleanString($relationship['strength'] ?? ''),
            cleanString($relationship['description'] ?? '')
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    
    mysqli_stmt_close($stmt);
    if ($count > 0) {
        echo "  ✅ Imported $count relationships\n";
    }
}

// Main import logic
$character_file = __DIR__ . '/../reference/Characters/Trace Element.json';

if (!file_exists($character_file)) {
    $error = "Character file not found: $character_file";
    echo "❌ ERROR: $error\n";
    if (!$is_cli) {
        echo "</pre></body></html>";
    }
    exit(1);
}

echo "Processing: Trace Element.json\n";
echo str_repeat("-", 60) . "\n";

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    // Read and parse JSON
    $jsonContent = file_get_contents($character_file);
    if ($jsonContent === false) {
        throw new Exception("Failed to read file: $character_file");
    }
    
    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }
    
    // Normalize data
    $data = normalizeCharacterData($data);
    
    // Upsert main character record
    $character_id = upsertCharacter($conn, $data);
    
    // Import related data
    importAbilities($conn, $character_id, $data);
    importDisciplines($conn, $character_id, $data);
    importTraits($conn, $character_id, $data);
    importNegativeTraits($conn, $character_id, $data);
    importBackgrounds($conn, $character_id, $data);
    importCoteries($conn, $character_id, $data);
    importRelationships($conn, $character_id, $data);
    
    // Commit transaction
    if (!db_commit($conn)) {
        throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ SUCCESS: Trace Element imported successfully!\n";
    echo "Character ID: $character_id\n";
    echo str_repeat("=", 60) . "\n";
    
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

mysqli_close($conn);
?>
