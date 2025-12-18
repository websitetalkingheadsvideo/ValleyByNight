<?php
/**
 * Character JSON Import Script
 * 
 * Imports character JSON files from reference/Characters/ into the database.
 * Supports upsert operations (insert if new, update if exists) based on character_name.
 * 
 * Usage:
 *   CLI: php database/import_characters.php [filename.json]
 *   Web: https://vbn.talkingheads.video/database/import_characters.php?file=filename.json
 *        https://vbn.talkingheads.video/database/import_characters.php?all=1
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
        // If it's an array, try to extract a string value
        if (isset($value['detailed_description'])) {
            return trim((string)$value['detailed_description']);
        }
        if (isset($value['description'])) {
            return trim((string)$value['description']);
        }
        if (isset($value['short_summary'])) {
            return trim((string)$value['short_summary']);
        }
        // If no string field found, convert to JSON
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
 * Clean JSON data for custom_data column
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
 * Normalize JSON data - handle field name inconsistencies and nested structures
 */
function normalizeCharacterData(array $data): array {
    $normalized = $data;
    
    // Normalize character_name (handle 'name' -> 'character_name')
    if (isset($normalized['name']) && !isset($normalized['character_name'])) {
        $normalized['character_name'] = $normalized['name'];
        unset($normalized['name']);
    }
    
    // Normalize status (handle nested status objects)
    if (isset($normalized['status']) && is_array($normalized['status'])) {
        $statusObj = $normalized['status'];
        // Extract status string
        if (isset($statusObj['current_state'])) {
            $normalized['status'] = $statusObj['current_state'];
        } elseif (isset($statusObj['status'])) {
            $normalized['status'] = $statusObj['status'];
        } else {
            $normalized['status'] = 'active';
        }
        // Flatten status fields to root level
        if (isset($statusObj['xp_total'])) {
            $normalized['xp_total'] = $statusObj['xp_total'];
        }
        if (isset($statusObj['xp_available'])) {
            $normalized['xp_available'] = $statusObj['xp_available'];
        }
        if (isset($statusObj['xp_spent'])) {
            $normalized['xp_spent'] = $statusObj['xp_spent'];
        }
        if (isset($statusObj['blood_pool_current'])) {
            $normalized['blood_pool_current'] = $statusObj['blood_pool_current'];
        } elseif (isset($statusObj['blood_pool'])) {
            $normalized['blood_pool_current'] = $statusObj['blood_pool'];
        }
    }
    
    // Handle status_details nested object
    if (isset($normalized['status_details']) && is_array($normalized['status_details'])) {
        $statusDetails = $normalized['status_details'];
        if (isset($statusDetails['current_state']) && !isset($normalized['status'])) {
            $normalized['status'] = $statusDetails['current_state'];
        }
        if (isset($statusDetails['camarilla_status']) && !isset($normalized['camarilla_status'])) {
            $normalized['camarilla_status'] = $statusDetails['camarilla_status'];
        }
        if (isset($statusDetails['xp_total'])) {
            $normalized['xp_total'] = $statusDetails['xp_total'];
        }
        if (isset($statusDetails['xp_available'])) {
            $normalized['xp_available'] = $statusDetails['xp_available'];
        }
        if (isset($statusDetails['blood_pool_current'])) {
            $normalized['blood_pool_current'] = $statusDetails['blood_pool_current'];
        }
    }
    
    // Normalize experience fields (handle total_xp -> xp_total)
    if (isset($normalized['total_xp']) && !isset($normalized['xp_total'])) {
        $normalized['xp_total'] = $normalized['total_xp'];
    }
    if (isset($normalized['spent_xp']) && !isset($normalized['xp_spent'])) {
        $normalized['xp_spent'] = $normalized['spent_xp'];
    }
    
    // Normalize camarilla_status (handle 'affiliation' -> 'camarilla_status')
    if (isset($normalized['affiliation']) && !isset($normalized['camarilla_status'])) {
        $normalized['camarilla_status'] = $normalized['affiliation'];
    }
    
    // Normalize appearance (handle object -> string)
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
    
    // Handle description -> appearance if appearance is missing
    if (empty($normalized['appearance']) && isset($normalized['description']) && is_string($normalized['description'])) {
        $normalized['appearance'] = $normalized['description'];
    }
    
    // Normalize current_state -> status
    if (isset($normalized['current_state']) && !isset($normalized['status'])) {
        $normalized['status'] = $normalized['current_state'];
    }
    
    // Normalize blood_pool at root level
    if (isset($normalized['blood_pool']) && !isset($normalized['blood_pool_current'])) {
        $normalized['blood_pool_current'] = $normalized['blood_pool'];
    }
    
    // Extract sire from embrace_info if needed
    if (empty($normalized['sire']) && isset($normalized['embrace_info']['sire'])) {
        $normalized['sire'] = $normalized['embrace_info']['sire'];
    }
    
    return $normalized;
}

/**
 * Validate character JSON structure
 */
function validateCharacterData(array $data, string $filename): array {
    $errors = [];
    
    // Required fields
    if (empty($data['character_name']) && empty($data['name'])) {
        $errors[] = "Missing required field: character_name";
    }
    
    // Validate status
    if (isset($data['status']) && is_string($data['status'])) {
        $validStates = ['active', 'inactive', 'archived'];
        if (!in_array(strtolower($data['status']), $validStates, true)) {
            $errors[] = "Invalid status value: {$data['status']} (must be: active, inactive, or archived)";
        }
    }
    
    // Validate camarilla_status
    if (isset($data['camarilla_status']) && is_string($data['camarilla_status'])) {
        $validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
        $normalized = ucfirst(strtolower($data['camarilla_status']));
        if (!in_array($normalized, $validCamarilla, true)) {
            $errors[] = "Invalid camarilla_status value: {$data['camarilla_status']}";
        }
    }
    
    return $errors;
}

/**
 * Find existing character by character_name
 */
function findCharacterByName(mysqli $conn, string $character_name): ?int {
    $result = db_fetch_one($conn, 
        "SELECT id FROM characters WHERE character_name = ? LIMIT 1",
        's',
        [$character_name]
    );
    return $result ? (int)$result['id'] : null;
}

/**
 * Insert or update main character record
 */
function upsertCharacter(mysqli $conn, array $data): int {
    $character_name = cleanString($data['character_name'] ?? '');
    if (empty($character_name)) {
        throw new Exception("Character name is required");
    }
    
    // Find existing character
    $character_id = findCharacterByName($conn, $character_name);
    
    // Prepare clean data
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
    
    // Validate status
    $validStates = ['active', 'inactive', 'archived'];
    $cleanData['status'] = strtolower($cleanData['status']);
    if (!in_array($cleanData['status'], $validStates, true)) {
        $cleanData['status'] = 'active';
    }
    
    // Validate camarilla_status
    $validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
    $camarillaValue = $cleanData['camarilla_status'] ? ucfirst(strtolower($cleanData['camarilla_status'])) : 'Unknown';
    if (!in_array($camarillaValue, $validCamarilla, true)) {
        $camarillaValue = 'Unknown';
    }
    $cleanData['camarilla_status'] = $camarillaValue;
    
    if ($character_id) {
        // Update existing
        $update_sql = "UPDATE characters SET 
            player_name = ?, chronicle = ?, nature = ?, demeanor = ?, concept = ?, 
            clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, 
            notes = ?, equipment = ?, custom_data = ?, status = ?, camarilla_status = ?" .
            ($cleanData['character_image'] !== '' ? ", character_image = ?" : "") .
            " WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
        }
        
        $params = [
            $cleanData['player_name'],
            $cleanData['chronicle'],
            $cleanData['nature'],
            $cleanData['demeanor'],
            $cleanData['concept'],
            $cleanData['clan'],
            $cleanData['generation'],
            $cleanData['sire'],
            $cleanData['pc'],
            $cleanData['appearance'],
            $cleanData['biography'],
            $cleanData['notes'],
            $cleanData['equipment'],
            $cleanData['custom_data'],
            $cleanData['status'],
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
    } else {
        // Insert new (minimal fields, matching save_character.php pattern)
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
        
        // Now update with all the other fields (matching save_character.php pattern)
        $update_sql = "UPDATE characters SET nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, equipment = ?, custom_data = ? WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $update_sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update after insert: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'sssssissssssi',
            $cleanData['nature'],
            $cleanData['demeanor'],
            $cleanData['concept'],
            $cleanData['clan'],
            $cleanData['generation'],
            $cleanData['sire'],
            $cleanData['pc'],
            $cleanData['appearance'],
            $cleanData['biography'],
            $cleanData['notes'],
            $cleanData['equipment'],
            $cleanData['custom_data'],
            $character_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('Failed to update character after insert: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }
    
    return $character_id;
}

/**
 * Import abilities from JSON
 */
function importAbilities(mysqli $conn, int $character_id, array $data): void {
    // Delete existing abilities
    db_execute($conn, "DELETE FROM character_abilities WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['abilities']) || empty($data['abilities'])) {
        return;
    }
    
    $abilities = $data['abilities'];
    $specializations = $data['specializations'] ?? [];
    
    // Handle different ability formats
    if (is_object($abilities)) {
        $abilities = (array)$abilities;
    }
    
    // Format 1: Array of objects [{"name": "...", "category": "...", "level": X}]
    if (is_array($abilities) && isset($abilities[0]) && is_array($abilities[0]) && isset($abilities[0]['name'])) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            foreach ($abilities as $ability) {
                $name = cleanString($ability['name'] ?? '');
                $level = cleanInt($ability['level'] ?? 1);
                $level = max(1, min(5, $level));
                $specialization = cleanString($specializations[$name] ?? null);
                
                if (empty($name)) {
                    continue;
                }
                
                mysqli_stmt_bind_param($stmt, 'isis', $character_id, $name, $level, $specialization);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Format 2: Array of strings ["Ability (Specialization) 4", "Ability 2"]
    elseif (isset($abilities[0]) && is_string($abilities[0])) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            foreach ($abilities as $abilityStr) {
                // Parse "Ability (Specialization) 4" or "Ability 4"
                if (preg_match('/^(.+?)(?:\s+\(([^)]+)\))?\s+(\d+)$/', $abilityStr, $matches)) {
                    $name = cleanString($matches[1]);
                    $specialization = isset($matches[2]) ? cleanString($matches[2]) : null;
                    $level = cleanInt($matches[3]);
                    $level = max(1, min(5, $level));
                    
                    // Override with specializations object if available
                    if (isset($specializations[$name])) {
                        $specialization = cleanString($specializations[$name]);
                    }
                    
                    mysqli_stmt_bind_param($stmt, 'isis', $character_id, $name, $level, $specialization);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Format 3: Object with categories {Physical: [...], Social: [...], Mental: [...]}
    elseif (is_array($abilities) && !isset($abilities[0]) && (isset($abilities['Physical']) || isset($abilities['Social']) || isset($abilities['Mental']))) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            $allowedCategories = ['Physical', 'Social', 'Mental'];
            foreach ($abilities as $category => $abilityNames) {
                if (!in_array($category, $allowedCategories, true) || !is_array($abilityNames)) {
                    continue;
                }
                
                // Count occurrences to get level
                $abilityCounts = [];
                foreach ($abilityNames as $abilityName) {
                    $cleanName = trim($abilityName);
                    if (strpos($cleanName, ' (') !== false) {
                        $cleanName = substr($cleanName, 0, strpos($cleanName, ' ('));
                    }
                    $abilityCounts[$cleanName] = ($abilityCounts[$cleanName] ?? 0) + 1;
                }
                
                foreach ($abilityCounts as $abilityName => $level) {
                    $level = max(1, min(5, (int)$level));
                    $specialization = cleanString($specializations[$abilityName] ?? null);
                    
                    mysqli_stmt_bind_param($stmt, 'isis', $character_id, $abilityName, $level, $specialization);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Import disciplines from JSON
 */
function importDisciplines(mysqli $conn, int $character_id, array $data): void {
    // Delete existing disciplines
    db_execute($conn, "DELETE FROM character_disciplines WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['disciplines']) || empty($data['disciplines'])) {
        return;
    }
    
    $disciplines = $data['disciplines'];
    
    // Format 1: Array of strings ["Auspex 5", "Dominate 3"]
    if (is_array($disciplines) && isset($disciplines[0]) && is_string($disciplines[0])) {
        $insert_sql = "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            foreach ($disciplines as $discStr) {
                if (preg_match('/^(.+?)\s+(\d+)$/', $discStr, $matches)) {
                    $name = cleanString($matches[1]);
                    $level = cleanInt($matches[2]);
                    $level = max(1, min(5, $level));
                    
                    mysqli_stmt_bind_param($stmt, 'isi', $character_id, $name, $level);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Format 2: Array of objects [{"name": "Auspex", "level": 5, "powers": [...]}]
    elseif (is_array($disciplines) && isset($disciplines[0]) && is_array($disciplines[0]) && isset($disciplines[0]['name'])) {
        $insert_sql = "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            foreach ($disciplines as $disc) {
                $name = cleanString($disc['name'] ?? '');
                $level = cleanInt($disc['level'] ?? 1);
                $level = max(1, min(5, $level));
                
                if (empty($name)) {
                    continue;
                }
                
                mysqli_stmt_bind_param($stmt, 'isi', $character_id, $name, $level);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Format 3: Object format {"celerity": 1, "potence": 2}
    elseif (is_array($disciplines) && !isset($disciplines[0]) && !empty($disciplines)) {
        $insert_sql = "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if ($stmt) {
            foreach ($disciplines as $name => $level) {
                if (is_numeric($level)) {
                    $name = cleanString($name);
                    $level = cleanInt($level);
                    $level = max(1, min(5, $level));
                    
                    mysqli_stmt_bind_param($stmt, 'isi', $character_id, $name, $level);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Import traits (positive)
 */
function importTraits(mysqli $conn, int $character_id, array $data): void {
    // Delete existing positive traits
    db_execute($conn, "DELETE FROM character_traits WHERE character_id = ? AND (trait_type IS NULL OR trait_type = 'positive')", 'i', [$character_id]);
    
    if (!isset($data['traits']) || !is_array($data['traits'])) {
        return;
    }
    
    $traits = $data['traits'];
    $allowedCategories = ['Physical', 'Social', 'Mental'];
    
    $insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, 'positive')";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($traits as $category => $traitNames) {
            $normalizedCategory = ucfirst(strtolower($category));
            if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
                continue;
            }
            
            foreach ($traitNames as $traitName) {
                $cleanName = cleanString($traitName);
                if (empty($cleanName)) {
                    continue;
                }
                
                mysqli_stmt_bind_param($stmt, 'iss', $character_id, $cleanName, $normalizedCategory);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import negative traits
 */
function importNegativeTraits(mysqli $conn, int $character_id, array $data): void {
    // Delete existing negative traits
    db_execute($conn, "DELETE FROM character_negative_traits WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['negativeTraits']) || !is_array($data['negativeTraits'])) {
        return;
    }
    
    $traits = $data['negativeTraits'];
    $allowedCategories = ['Physical', 'Social', 'Mental'];
    
    $insert_sql = "INSERT INTO character_negative_traits (character_id, trait_category, trait_name) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($traits as $category => $traitNames) {
            $normalizedCategory = ucfirst(strtolower($category));
            if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
                continue;
            }
            
            foreach ($traitNames as $traitName) {
                $cleanName = cleanString($traitName);
                if (empty($cleanName)) {
                    continue;
                }
                
                mysqli_stmt_bind_param($stmt, 'iss', $character_id, $normalizedCategory, $cleanName);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import coteries
 */
function importCoteries(mysqli $conn, int $character_id, array $data): void {
    // Delete existing coteries
    db_execute($conn, "DELETE FROM character_coteries WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['coteries']) || !is_array($data['coteries'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_coteries (character_id, coterie_name, coterie_type, role, description, notes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($data['coteries'] as $coterie) {
            if (empty($coterie['coterie_name'])) {
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'isssss',
                $character_id,
                cleanString($coterie['coterie_name'] ?? ''),
                cleanString($coterie['coterie_type'] ?? ''),
                cleanString($coterie['role'] ?? ''),
                cleanString($coterie['description'] ?? ''),
                cleanString($coterie['notes'] ?? '')
            );
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import relationships
 */
function importRelationships(mysqli $conn, int $character_id, array $data): void {
    // Delete existing relationships
    db_execute($conn, "DELETE FROM character_relationships WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['relationships']) || !is_array($data['relationships'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_relationships (character_id, related_character_name, relationship_type, relationship_subtype, strength, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
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
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import backgrounds
 */
function importBackgrounds(mysqli $conn, int $character_id, array $data): void {
    // Delete existing backgrounds
    db_execute($conn, "DELETE FROM character_backgrounds WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['backgrounds']) || !is_array($data['backgrounds'])) {
        return;
    }
    
    $backgrounds = $data['backgrounds'];
    $backgroundDetails = $data['backgroundDetails'] ?? [];
    
    $insert_sql = "INSERT INTO character_backgrounds (character_id, background_name, level, description) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($backgrounds as $name => $level) {
            if (is_numeric($level)) {
                $name = cleanString($name);
                $level = cleanInt($level);
                $description = cleanString($backgroundDetails[$name] ?? null);
                
                mysqli_stmt_bind_param($stmt, 'isis', $character_id, $name, $level, $description);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import merits and flaws
 */
function importMeritsFlaws(mysqli $conn, int $character_id, array $data): void {
    // Delete existing merits/flaws
    db_execute($conn, "DELETE FROM character_merits_flaws WHERE character_id = ?", 'i', [$character_id]);
    
    if (!isset($data['merits_flaws']) || !is_array($data['merits_flaws'])) {
        return;
    }
    
    $insert_sql = "INSERT INTO character_merits_flaws (character_id, name, type, category, point_value, point_cost, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($data['merits_flaws'] as $item) {
            if (empty($item['name'])) {
                continue;
            }
            
            $type = ucfirst(cleanString($item['type'] ?? 'Merit'));
            if ($type !== 'Merit' && $type !== 'Flaw') {
                $type = 'Merit';
            }
            
            mysqli_stmt_bind_param($stmt, 'isssiis',
                $character_id,
                cleanString($item['name'] ?? ''),
                $type,
                cleanString($item['category'] ?? ''),
                cleanInt($item['point_value'] ?? $item['cost'] ?? 0),
                cleanInt($item['point_cost'] ?? $item['cost'] ?? 0),
                cleanString($item['description'] ?? '')
            );
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * Import a single character JSON file
 */
function importCharacterFile(mysqli $conn, string $filepath, array &$stats): bool {
    $filename = basename($filepath);
    
    try {
        // Read and parse JSON
        $jsonContent = file_get_contents($filepath);
        if ($jsonContent === false) {
            throw new Exception("Failed to read file: $filepath");
        }
        
        // Fix unescaped newlines in string values (common issue with multi-line text fields)
        // This regex finds string values and escapes newlines within them
        $jsonContent = preg_replace_callback(
            '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/',
            function($matches) {
                // Escape newlines, carriage returns, and tabs in string values
                $str = $matches[1];
                $str = str_replace(["\n", "\r", "\t"], ['\\n', '\\r', '\\t'], $str);
                return '"' . $str . '"';
            },
            $jsonContent
        );
        
        // Clean control characters that can cause JSON parsing issues
        // Remove control characters except those we've already escaped
        $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonContent);
        
        // Try to decode with error handling
        $data = json_decode($jsonContent, true);
        if ($data === null) {
            $errorMsg = json_last_error_msg();
            $errorCode = json_last_error();
            
            // Try with UTF-8 encoding fix
            if ($errorCode === JSON_ERROR_UTF8) {
                $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
                $data = json_decode($jsonContent, true);
                if ($data === null) {
                    throw new Exception("Invalid JSON (encoding issue): " . json_last_error_msg());
                }
            } else {
                throw new Exception("Invalid JSON: $errorMsg (code: $errorCode)");
            }
        }
        
        // Normalize data
        $data = normalizeCharacterData($data);
        
        // Validate
        $errors = validateCharacterData($data, $filename);
        if (!empty($errors)) {
            throw new Exception("Validation errors: " . implode(", ", $errors));
        }
        
        // Start transaction
        db_begin_transaction($conn);
        
        try {
            // Check if character exists
            $character_name = cleanString($data['character_name'] ?? '');
            $existing_id = findCharacterByName($conn, $character_name);
            $is_update = $existing_id !== null;
            
            // Upsert main character record
            $character_id = upsertCharacter($conn, $data);
            
            // Import related tables
            importAbilities($conn, $character_id, $data);
            importDisciplines($conn, $character_id, $data);
            importTraits($conn, $character_id, $data);
            importNegativeTraits($conn, $character_id, $data);
            importCoteries($conn, $character_id, $data);
            importRelationships($conn, $character_id, $data);
            importBackgrounds($conn, $character_id, $data);
            importMeritsFlaws($conn, $character_id, $data);
            
            // Commit transaction
            db_commit($conn);
            
            $stats['processed']++;
            if ($is_update) {
                $stats['updated']++;
            } else {
                $stats['inserted']++;
            }
            
            return true;
        } catch (Exception $e) {
            db_rollback($conn);
            throw $e;
        }
    } catch (Exception $e) {
        $stats['errors'][] = "$filename: " . $e->getMessage();
        $stats['skipped']++;
        return false;
    }
}

/**
 * Get list of JSON files to import
 * Only imports the 5 specific character files
 */
function getJsonFiles(string $directory): array {
    // Only import these specific character files
    $allowedFiles = [
        'alistaire.json',
        'Ardvark.json',
        'Butch Reed.json',
        'lilith_nightshade.json',
        'Misfortune.json'
    ];
    
    $files = [];
    foreach ($allowedFiles as $filename) {
        $filepath = $directory . '/' . $filename;
        if (is_file($filepath)) {
            $files[] = $filepath;
        }
    }
    
    return $files;
}

// Main execution - only run if not being included as a library
if (!defined('IMPORT_CHARACTERS_AS_LIBRARY')) {
// Main execution
$characters_dir = __DIR__ . '/../reference/Characters';
$files_to_import = [];

// Determine which files to import
if ($is_cli) {
    // CLI mode
    if (isset($argv[1])) {
        // Specific file
        $filepath = $argv[1];
        if (!is_file($filepath)) {
            // Try relative to characters directory
            $filepath = $characters_dir . '/' . $filepath;
        }
        if (is_file($filepath)) {
            $files_to_import[] = $filepath;
        } else {
            die("File not found: {$argv[1]}\n");
        }
    } else {
        // All files
        $files_to_import = getJsonFiles($characters_dir);
    }
} else {
    // Web mode
    if (isset($_GET['file'])) {
        // Specific file
        $filename = basename($_GET['file']);
        $filepath = $characters_dir . '/' . $filename;
        if (is_file($filepath)) {
            $files_to_import[] = $filepath;
        } else {
            die("File not found: $filename");
        }
    } elseif (isset($_GET['all']) && $_GET['all'] == '1') {
        // All files
        $files_to_import = getJsonFiles($characters_dir);
    } else {
        die("Usage: ?file=filename.json or ?all=1");
    }
}

// Output header
if ($is_cli) {
    echo "Character Import Script\n";
    echo "======================\n\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Character Import</title></head><body>";
    echo "<h1>Character Import Script</h1>";
    echo "<pre>";
}

if (empty($files_to_import)) {
    echo "No JSON files found to import.\n";
    if (!$is_cli) {
        echo "</pre></body></html>";
    }
    exit;
}

echo "Found " . count($files_to_import) . " file(s) to import.\n\n";

// Import each file
foreach ($files_to_import as $filepath) {
    $filename = basename($filepath);
    echo "Processing: $filename... ";
    
    if (importCharacterFile($conn, $filepath, $stats)) {
        echo "OK\n";
    } else {
        echo "FAILED\n";
    }
}

// Output summary
echo "\n";
echo "=== Import Summary ===\n";
echo "Processed: {$stats['processed']}\n";
echo "Inserted: {$stats['inserted']}\n";
echo "Updated: {$stats['updated']}\n";
echo "Skipped: {$stats['skipped']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);
} // End of main execution check
?>

