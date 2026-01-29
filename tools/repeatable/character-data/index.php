<?php
/**
 * Character Data Quality Blockers
 * 
 * Identifies missing/empty fields that prevent accurate character summaries.
 * Read-only tool - does not modify data.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Clear JSON search results if reset requested (must be before any output)
if (isset($_GET['reset_json'])) {
    unset($_SESSION['json_search_results']);
    unset($_SESSION['json_search_stats']);
    $clean_uri = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $clean_uri);
    exit();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

require_once __DIR__ . '/../../../includes/connect.php';
require_once __DIR__ . '/parse_abilities_helper.php';

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Page-specific CSS and JS
$extra_css = ['css/admin_panel.css'];
$extra_js = ['js/character-data-index.js'];
include __DIR__ . '/../../../includes/header.php';

// Fields to check for missing data (must match quick-edit.php)
$critical_fields = [
    'biography' => 'Biography',
    'appearance' => 'Appearance',
    'concept' => 'Concept',
    'nature' => 'Nature',
    'demeanor' => 'Demeanor'
];

// Initialize results
$results = [];
$stats = [
    'total_scanned' => 0,
    'with_missing_data' => 0,
    'field_counts' => []
];

// JSON search results
$json_search_results = [];
$json_search_stats = [
    'characters_checked' => 0,
    'characters_with_data' => 0,
    'fields_found' => []
];

// Update results
$update_results = [];
$update_stats = ['updated' => 0, 'errors' => 0];
$update_error_message = null;

// Clear session data on initial page load (not a POST request)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['character_quality_results']);
    unset($_SESSION['character_quality_stats']);
    unset($_SESSION['character_quality_characters']);
}

// Process search if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    // Clear updated IDs list when running fresh search
    unset($_SESSION['updated_character_ids']);
    
    try {
        // Build base select fields
        $select_fields = ['c.id', 'c.character_name', 'c.biography', 'c.appearance', 'c.concept', 'c.nature', 'c.demeanor', 'c.character_image'];
        
        // Use subqueries for counts to avoid Cartesian product performance issues
        $query = "SELECT " . implode(', ', $select_fields) . ",
                    (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) as abilities_count,
                    (SELECT COUNT(*) FROM character_disciplines WHERE character_id = c.id) as disciplines_count,
                    (SELECT COUNT(*) FROM character_backgrounds WHERE character_id = c.id) as backgrounds_count
                  FROM characters c
                  ORDER BY c.id";
        
        // Query all characters with counts in one go
        $characters = db_fetch_all($conn, $query);
        
        if ($characters === false) {
            throw new Exception("Query failed: " . mysqli_error($conn));
        }
    
    $stats['total_scanned'] = count($characters);
    
    foreach ($characters as $char) {
        $missing_fields = [];
        
        // Check each critical field
        foreach ($critical_fields as $field => $label) {
            // Skip if field doesn't exist in result
            if (!array_key_exists($field, $char)) {
                continue;
            }
            
            $value = $char[$field] ?? null;
            $is_missing = false;
            
            // Check for NULL, empty string, or whitespace-only
            if ($value === null || $value === '' || trim($value) === '') {
                $is_missing = true;
            }
            
            if ($is_missing) {
                $missing_fields[] = $label;
                
                // Track field counts
                if (!isset($stats['field_counts'][$label])) {
                    $stats['field_counts'][$label] = 0;
                }
                $stats['field_counts'][$label]++;
            }
        }
        
        // Check related tables for missing data (from single query)
        if ((int)$char['abilities_count'] === 0) {
            $missing_fields[] = 'Abilities';
            if (!isset($stats['field_counts']['Abilities'])) {
                $stats['field_counts']['Abilities'] = 0;
            }
            $stats['field_counts']['Abilities']++;
        }
        
        if ((int)$char['disciplines_count'] === 0) {
            $missing_fields[] = 'Disciplines';
            if (!isset($stats['field_counts']['Disciplines'])) {
                $stats['field_counts']['Disciplines'] = 0;
            }
            $stats['field_counts']['Disciplines']++;
        }
        
        if ((int)$char['backgrounds_count'] === 0) {
            $missing_fields[] = 'Backgrounds';
            if (!isset($stats['field_counts']['Backgrounds'])) {
                $stats['field_counts']['Backgrounds'] = 0;
            }
            $stats['field_counts']['Backgrounds']++;
        }
        
        // Check for missing character image
        $image_missing = false;
        $image_value = $char['character_image'] ?? null;
        if (empty($image_value) || trim($image_value) === '') {
            $image_missing = true;
        } else {
            // Check if image file actually exists
            $image_path = __DIR__ . '/../../../uploads/characters/' . $image_value;
            if (!file_exists($image_path)) {
                $image_missing = true;
            }
        }
        
        if ($image_missing) {
            $missing_fields[] = 'Image';
            if (!isset($stats['field_counts']['Image'])) {
                $stats['field_counts']['Image'] = 0;
            }
            $stats['field_counts']['Image']++;
        }
        
        // Only add to results if missing fields found
        if (!empty($missing_fields)) {
            $stats['with_missing_data']++;
            $results[] = [
                'id' => (int)$char['id'],
                'name' => $char['character_name'] ?? 'Unknown',
                'missing_fields' => $missing_fields,
                'missing_count' => count($missing_fields)
            ];
        }
    }
    
    // Sort field counts by frequency (descending)
    arsort($stats['field_counts']);
    
    // If show_all is checked, include complete characters too
    if (isset($_POST['show_all']) && $_POST['show_all'] == '1') {
        foreach ($characters as $char) {
            $missing_fields = [];
            
            // Check each critical field
            foreach ($critical_fields as $field => $label) {
                if (!array_key_exists($field, $char)) {
                    continue;
                }
                
                $value = $char[$field] ?? null;
                $is_missing = false;
                
                if ($value === null || $value === '' || trim($value) === '') {
                    $is_missing = true;
                }
                
                if ($is_missing) {
                    $missing_fields[] = $label;
                }
            }
            
            if ((int)$char['abilities_count'] === 0) {
                $missing_fields[] = 'Abilities';
            }
            if ((int)$char['disciplines_count'] === 0) {
                $missing_fields[] = 'Disciplines';
            }
            if ((int)$char['backgrounds_count'] === 0) {
                $missing_fields[] = 'Backgrounds';
            }
            
            // Check for missing character image
            $image_missing = false;
            $image_value = $char['character_image'] ?? null;
            if (empty($image_value) || trim($image_value) === '') {
                $image_missing = true;
            } else {
                // Check if image file actually exists
                $image_path = __DIR__ . '/../../../uploads/characters/' . $image_value;
                if (!file_exists($image_path)) {
                    $image_missing = true;
                }
            }
            
            if ($image_missing) {
                $missing_fields[] = 'Image';
            }
            
            // Check if already in results
            $found = false;
            foreach ($results as $existing) {
                if ($existing['id'] === (int)$char['id']) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $results[] = [
                    'id' => (int)$char['id'],
                    'name' => $char['character_name'] ?? 'Unknown',
                    'missing_fields' => $missing_fields,
                    'missing_count' => count($missing_fields)
                ];
            }
        }
    }
    
    // Sort results by missing count (descending), then by ID
    usort($results, function($a, $b) {
        if ($a['missing_count'] === $b['missing_count']) {
            return $a['id'] <=> $b['id'];
        }
        return $b['missing_count'] <=> $a['missing_count'];
    });
    
    // Store results in session for JSON search
    $_SESSION['character_quality_results'] = $results;
    $_SESSION['character_quality_stats'] = $stats;
    $_SESSION['character_quality_characters'] = $characters;
    
    } catch (Exception $e) {
        $error_message = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Process JSON search if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_json'])) {
    try {
        // Always query database fresh instead of using cached session data
        // Build base select fields
        $select_fields = ['c.id', 'c.character_name', 'c.biography', 'c.appearance', 'c.concept', 'c.nature', 'c.demeanor'];
        
        // Query all characters with counts
        $query = "SELECT " . implode(', ', $select_fields) . ",
                    (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) as abilities_count,
                    (SELECT COUNT(*) FROM character_disciplines WHERE character_id = c.id) as disciplines_count,
                    (SELECT COUNT(*) FROM character_backgrounds WHERE character_id = c.id) as backgrounds_count
                  FROM characters c
                  ORDER BY c.id";
        
        $all_characters = db_fetch_all($conn, $query);
        if ($all_characters === false) {
            throw new Exception("Query failed: " . mysqli_error($conn));
        }
        
        // Build fresh database results with missing fields
        $db_results = [];
        foreach ($all_characters as $char) {
            $missing_fields = [];
            
            // Check each critical field
            foreach ($critical_fields as $field => $label) {
                if (!array_key_exists($field, $char)) {
                    continue;
                }
                
                $value = $char[$field] ?? null;
                $is_missing = false;
                
                if ($value === null || $value === '' || trim($value) === '') {
                    $is_missing = true;
                }
                
                if ($is_missing) {
                    $missing_fields[] = $label;
                }
            }
            
            if ((int)$char['abilities_count'] === 0) {
                $missing_fields[] = 'Abilities';
            }
            if ((int)$char['disciplines_count'] === 0) {
                $missing_fields[] = 'Disciplines';
            }
            if ((int)$char['backgrounds_count'] === 0) {
                $missing_fields[] = 'Backgrounds';
            }
            
            // Check for missing character image
            $image_missing = false;
            $image_value = $char['character_image'] ?? null;
            if (empty($image_value) || trim($image_value) === '') {
                $image_missing = true;
            } else {
                $image_path = __DIR__ . '/../../../uploads/characters/' . $image_value;
                if (!file_exists($image_path)) {
                    $image_missing = true;
                }
            }
            
            if ($image_missing) {
                $missing_fields[] = 'Image';
            }
            
            if (!empty($missing_fields)) {
                $db_results[] = [
                    'id' => (int)$char['id'],
                    'name' => $char['character_name'] ?? 'Unknown',
                    'missing_fields' => $missing_fields
                ];
            }
        }
        
        if (empty($db_results)) {
            throw new Exception("No characters with missing data found. All characters are complete.");
        }
        
        // Exclude characters that were just updated in this session
        $excluded_ids = $_SESSION['updated_character_ids'] ?? [];
        if (!empty($excluded_ids)) {
            $db_results = array_filter($db_results, function($result) use ($excluded_ids) {
                return !in_array($result['id'], $excluded_ids);
            });
            $db_results = array_values($db_results); // Re-index array
            
            if (empty($db_results)) {
                throw new Exception("All characters with missing data have been updated. Please run a fresh database search to see current status.");
            }
        }
        
        $json_dir = __DIR__ . '/../../../reference/Characters/Added to Database';
        
        if (!is_dir($json_dir)) {
            throw new Exception("JSON directory not found: " . $json_dir);
        }
        
        $json_files = glob($json_dir . '/*.json');
        $json_search_stats['characters_checked'] = count($json_files);
        
        // Create lookup maps of database results by character ID and name
        $db_lookup_by_id = [];
        $db_lookup_by_name = [];
        foreach ($db_results as $result) {
            $db_lookup_by_id[$result['id']] = $result;
            $db_lookup_by_name[strtolower(trim($result['name']))] = $result;
        }
        
        // Process each JSON file
        foreach ($json_files as $json_file) {
            $json_content = file_get_contents($json_file);
            if ($json_content === false) {
                continue;
            }
            
            $json_data = json_decode($json_content, true);
            if ($json_data === null) {
                continue;
            }
            
            // Try to get ID from filename pattern: npc__*__[id].json
            $filename = basename($json_file);
            $char_id = null;
            if (preg_match('/npc__.+__(\d+)\.json$/', $filename, $matches)) {
                $char_id = (int)$matches[1];
            } elseif (isset($json_data['id'])) {
                $char_id = (int)$json_data['id'];
            }
            
            // Try to find matching character in database
            $db_result = null;
            if ($char_id !== null && $char_id > 0 && isset($db_lookup_by_id[$char_id])) {
                $db_result = $db_lookup_by_id[$char_id];
            } elseif (isset($json_data['character_name'])) {
                $json_name = strtolower(trim($json_data['character_name']));
                if (isset($db_lookup_by_name[$json_name])) {
                    $db_result = $db_lookup_by_name[$json_name];
                }
            }
            
            // Skip if no matching character found
            if ($db_result === null) {
                continue;
            }
            
            $db_missing = $db_result['missing_fields'];
            if (empty($db_missing)) {
                continue;
            }
            
            $found_fields = [];
            
            // Check each missing field in JSON
            foreach ($db_missing as $missing_field) {
                $has_data = false;
                
                switch ($missing_field) {
                    case 'Biography':
                        $has_data = !empty($json_data['biography']) && trim($json_data['biography']) !== '';
                        break;
                    case 'Appearance':
                        $has_data = !empty($json_data['appearance']) && trim($json_data['appearance']) !== '';
                        break;
                    case 'Concept':
                        $has_data = !empty($json_data['concept']) && trim($json_data['concept']) !== '';
                        break;
                    case 'Nature':
                        $has_data = !empty($json_data['nature']) && trim($json_data['nature']) !== '';
                        break;
                    case 'Demeanor':
                        $has_data = !empty($json_data['demeanor']) && trim($json_data['demeanor']) !== '';
                        break;
                    case 'Abilities':
                        $has_data = count(parse_abilities_from_character_json($json_data)) > 0;
                        break;
                    case 'Disciplines':
                        $has_data = !empty($json_data['disciplines']) && is_array($json_data['disciplines']) && count($json_data['disciplines']) > 0;
                        break;
                    case 'Backgrounds':
                        $has_data = !empty($json_data['backgrounds']) && 
                                   (is_array($json_data['backgrounds']) || is_object($json_data['backgrounds'])) &&
                                   (is_array($json_data['backgrounds']) ? count($json_data['backgrounds']) > 0 : count((array)$json_data['backgrounds']) > 0);
                        break;
                }
                
                if ($has_data) {
                    $found_fields[] = $missing_field;
                    if (!isset($json_search_stats['fields_found'][$missing_field])) {
                        $json_search_stats['fields_found'][$missing_field] = 0;
                    }
                    $json_search_stats['fields_found'][$missing_field]++;
                }
            }
            
            if (!empty($found_fields)) {
                $json_search_stats['characters_with_data']++;
                $json_search_results[] = [
                    'id' => $db_result['id'],
                    'name' => $json_data['character_name'] ?? 'Unknown',
                    'json_file' => basename($json_file),
                    'json_path' => $json_file,
                    'json_data' => $json_data, // Store full JSON for updates
                    'missing_in_db' => $db_missing,
                    'found_in_json' => $found_fields,
                    'still_missing' => array_diff($db_missing, $found_fields)
                ];
            }
        }
        
        // Sort field counts by frequency
        arsort($json_search_stats['fields_found']);
        
        // Store in session for display after page refresh
        $_SESSION['json_search_results'] = $json_search_results;
        $_SESSION['json_search_stats'] = $json_search_stats;
        
    } catch (Exception $e) {
        $json_error_message = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Process database update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_database'])) {
    $update_results = [];
    $update_stats = ['updated' => 0, 'errors' => 0];
    try {
        // Ensure autocommit is enabled (default, but make sure)
        mysqli_autocommit($conn, true);
        if (!isset($_SESSION['json_search_results']) || empty($_SESSION['json_search_results'])) {
            throw new Exception("No JSON search results found. Please run JSON search first.");
        }
        
        $json_results = $_SESSION['json_search_results'];
        
        // Debug: Check if JSON data is preserved
        if (empty($json_results)) {
            throw new Exception("JSON search results are empty. Please run JSON search again.");
        }
        
        // Log how many characters we're about to process
        error_log("Character Data Update: Starting update for " . count($json_results) . " characters");
        
        $processed_count = 0;
        foreach ($json_results as $result_index => $result) {
            $processed_count++;
            
            // Verify result has required data
            if (!isset($result['id'])) {
                $update_stats['errors']++;
                $update_results[] = [
                    'id' => 0,
                    'name' => 'Unknown',
                    'fields_updated' => [],
                    'status' => 'error',
                    'errors' => ['Missing character ID in result'],
                    'debug' => "Result #{$result_index} missing ID"
                ];
                continue;
            }
            $char_id = $result['id'];
            $char_name = $result['name'] ?? 'Unknown';
            $update_errors = []; // Initialize errors at start of each character
            
            // Re-read JSON file if json_data is missing (session might not preserve large arrays)
            if (!isset($result['json_data']) || empty($result['json_data'])) {
                if (isset($result['json_path']) && file_exists($result['json_path'])) {
                    $json_content = file_get_contents($result['json_path']);
                    $json_data = json_decode($json_content, true);
                    if ($json_data === null) {
                        $update_errors[] = "Failed to read JSON file: " . $result['json_file'];
                        $update_stats['errors']++;
                        $update_results[] = [
                            'id' => $char_id,
                            'name' => $char_name,
                            'fields_updated' => [],
                            'status' => 'error',
                            'errors' => $update_errors,
                            'debug' => "Failed to read JSON for character ID {$char_id}"
                        ];
                        continue;
                    }
                } else {
                    $update_errors[] = "JSON data missing and file path not available for character ID {$char_id}";
                    $update_stats['errors']++;
                    $update_results[] = [
                        'id' => $char_id,
                        'name' => $char_name,
                        'fields_updated' => [],
                        'status' => 'error',
                        'errors' => $update_errors,
                        'debug' => "JSON data unavailable for character ID {$char_id}"
                    ];
                    continue;
                }
            } else {
                $json_data = $result['json_data'];
            }
            
            $found_fields = $result['found_in_json'];
            
            // Update direct character table fields
            $update_fields = [];
            $update_values = [];
            
            foreach ($found_fields as $field) {
                switch ($field) {
                    case 'Biography':
                        if (!empty($json_data['biography']) && trim($json_data['biography']) !== '') {
                            $update_fields[] = 'biography = ?';
                            $update_values[] = trim($json_data['biography']);
                        }
                        break;
                    case 'Appearance':
                        if (!empty($json_data['appearance']) && trim($json_data['appearance']) !== '') {
                            $update_fields[] = 'appearance = ?';
                            $update_values[] = trim($json_data['appearance']);
                        }
                        break;
                    case 'Concept':
                        if (!empty($json_data['concept']) && trim($json_data['concept']) !== '') {
                            $update_fields[] = 'concept = ?';
                            $update_values[] = trim($json_data['concept']);
                        }
                        break;
                    case 'Nature':
                        if (!empty($json_data['nature']) && trim($json_data['nature']) !== '') {
                            $update_fields[] = 'nature = ?';
                            $update_values[] = trim($json_data['nature']);
                        }
                        break;
                    case 'Demeanor':
                        if (!empty($json_data['demeanor']) && trim($json_data['demeanor']) !== '') {
                            $update_fields[] = 'demeanor = ?';
                            $update_values[] = trim($json_data['demeanor']);
                        }
                        break;
                }
            }
            
            // Update character table if there are fields to update
            if (!empty($update_fields)) {
                // Verify character exists first
                $check_char = db_fetch_all($conn, "SELECT id, character_name FROM characters WHERE id = ?", 'i', [$char_id]);
                if (empty($check_char)) {
                    $update_errors[] = "Character ID {$char_id} not found in database";
                } else {
                    $update_values[] = $char_id; // For WHERE clause
                    $query = "UPDATE characters SET " . implode(', ', $update_fields) . " WHERE id = ?";
                    
                    // Debug: Log the query and values
                    $debug_query = $query;
                    foreach ($update_values as $idx => $val) {
                        $debug_query = str_replace('?', "'" . substr($val, 0, 50) . (strlen($val) > 50 ? '...' : '') . "'", $debug_query, 1);
                    }
                    $debug_info = "Query: " . $debug_query;
                    
                    $stmt = mysqli_prepare($conn, $query);
                    if ($stmt) {
                        $types = str_repeat('s', count($update_values) - 1) . 'i'; // All strings except last (id)
                        
                        // Bind by reference for mysqli_stmt_bind_param (required in PHP)
                        $bind_params = [];
                        $bind_params[] = $types;
                        for ($i = 0; $i < count($update_values); $i++) {
                            $bind_params[] = &$update_values[$i];
                        }
                        call_user_func_array([$stmt, 'bind_param'], $bind_params);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            $update_errors[] = "Failed to update character fields: " . mysqli_stmt_error($stmt) . " | " . mysqli_error($conn);
                        } else {
                            $affected = mysqli_stmt_affected_rows($stmt);
                            
                            // Force commit to ensure changes are persisted
                            mysqli_commit($conn);
                            
                            if ($affected === 0) {
                                $update_errors[] = "Update executed but no rows affected for character ID {$char_id}. Character may not exist or data is identical.";
                            } else {
                                // Wait a moment and verify the update actually persisted
                                usleep(100000); // 0.1 second delay
                                
                                // Verify the update actually worked by checking one field
                                $verify_field = str_replace(' = ?', '', $update_fields[0]);
                                $verify_query = "SELECT " . $verify_field . " FROM characters WHERE id = ?";
                                $verify_stmt = mysqli_prepare($conn, $verify_query);
                                if ($verify_stmt) {
                                    mysqli_stmt_bind_param($verify_stmt, 'i', $char_id);
                                    mysqli_stmt_execute($verify_stmt);
                                    $verify_result = mysqli_stmt_get_result($verify_stmt);
                                    $verify_row = mysqli_fetch_assoc($verify_result);
                                    if (empty($verify_row)) {
                                        $update_errors[] = "Update reported success but verification query returned no rows for character ID {$char_id}";
                                    } else {
                                        $updated_value = trim($verify_row[$verify_field] ?? '');
                                        // Get what we tried to set
                                        $expected_value = '';
                                        foreach ($found_fields as $field) {
                                            switch ($field) {
                                                case 'Biography':
                                                    $expected_value = trim($json_data['biography'] ?? '');
                                                    break;
                                                case 'Appearance':
                                                    $expected_value = trim($json_data['appearance'] ?? '');
                                                    break;
                                                case 'Concept':
                                                    $expected_value = trim($json_data['concept'] ?? '');
                                                    break;
                                                case 'Nature':
                                                    $expected_value = trim($json_data['nature'] ?? '');
                                                    break;
                                                case 'Demeanor':
                                                    $expected_value = trim($json_data['demeanor'] ?? '');
                                                    break;
                                            }
                                            if ($verify_field === strtolower($field)) {
                                                break;
                                            }
                                        }
                                        if (empty($updated_value) && !empty($expected_value)) {
                                            $update_errors[] = "Update reported success but field {$verify_field} is still empty for character ID {$char_id}. Expected: " . substr($expected_value, 0, 50) . "...";
                                        } elseif (!empty($updated_value) && $updated_value !== $expected_value && !empty($expected_value)) {
                                            $update_errors[] = "Update reported success but field {$verify_field} value doesn't match for character ID {$char_id}";
                                        }
                                    }
                                    mysqli_stmt_close($verify_stmt);
                                }
                            }
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $update_errors[] = "Failed to prepare update statement: " . mysqli_error($conn) . " | Query: " . $query;
                    }
                }
            }
            
            // Update related tables
            foreach ($found_fields as $field) {
                if ($field === 'Abilities') {
                    $parsed_abilities = parse_abilities_from_character_json($json_data);
                    if (count($parsed_abilities) > 0) {
                        $has_category_column = !empty(db_fetch_all($conn, "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'"));
                        $delete_result = mysqli_query($conn, "DELETE FROM character_abilities WHERE character_id = " . (int)$char_id);
                        if (!$delete_result) {
                            $update_errors[] = "Failed to delete abilities: " . mysqli_error($conn);
                        }
                        foreach ($parsed_abilities as $a) {
                            $name = trim($a['name'] ?? '');
                            if ($name === '') {
                                continue;
                            }
                            $category = trim($a['category'] ?? '');
                            $level = isset($a['level']) ? (int)$a['level'] : 0;
                            $specialization = trim($a['specialization'] ?? '');
                            if ($has_category_column) {
                                $lookup_row = db_fetch_one($conn, "SELECT category FROM abilities WHERE name COLLATE utf8mb4_unicode_ci = ? LIMIT 1", "s", [$name]);
                                if ($lookup_row && trim($lookup_row['category'] ?? '') !== '') {
                                    $category = trim($lookup_row['category']);
                                }
                            }
                            $name_esc = mysqli_real_escape_string($conn, $name);
                            $category_esc = mysqli_real_escape_string($conn, $category);
                            $spec_esc = mysqli_real_escape_string($conn, $specialization);
                            if ($has_category_column) {
                                $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES ({$char_id}, '{$name_esc}', '{$category_esc}', {$level}, '{$spec_esc}')";
                            } else {
                                $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES ({$char_id}, '{$name_esc}', {$level}, '{$spec_esc}')";
                            }
                            if (!mysqli_query($conn, $insert_sql)) {
                                $update_errors[] = "Failed to insert ability '{$name_esc}': " . mysqli_error($conn);
                            }
                        }
                        mysqli_commit($conn);
                    }
                }
                
                if ($field === 'Disciplines' && !empty($json_data['disciplines']) && is_array($json_data['disciplines'])) {
                    // Delete existing disciplines
                    $delete_result = mysqli_query($conn, "DELETE FROM character_disciplines WHERE character_id = " . (int)$char_id);
                    if (!$delete_result) {
                        $update_errors[] = "Failed to delete disciplines: " . mysqli_error($conn);
                    }
                    
                    // Insert new disciplines
                    foreach ($json_data['disciplines'] as $discipline) {
                        if (is_array($discipline) && isset($discipline['name'])) {
                            $name = mysqli_real_escape_string($conn, $discipline['name']);
                            $level = isset($discipline['level']) ? (int)$discipline['level'] : 0;
                            $insert_result = mysqli_query($conn, "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES ({$char_id}, '{$name}', {$level})");
                            if (!$insert_result) {
                                $update_errors[] = "Failed to insert discipline '{$name}': " . mysqli_error($conn);
                            }
                        }
                    }
                    // Commit disciplines updates
                    mysqli_commit($conn);
                }
                
                if ($field === 'Backgrounds' && !empty($json_data['backgrounds'])) {
                    // Delete existing backgrounds
                    $delete_result = mysqli_query($conn, "DELETE FROM character_backgrounds WHERE character_id = " . (int)$char_id);
                    if (!$delete_result) {
                        $update_errors[] = "Failed to delete backgrounds: " . mysqli_error($conn);
                    }
                    
                    // Insert new backgrounds
                    $backgrounds = is_array($json_data['backgrounds']) ? $json_data['backgrounds'] : (array)$json_data['backgrounds'];
                    foreach ($backgrounds as $bg_name => $bg_level) {
                        if (is_numeric($bg_level)) {
                            $name = mysqli_real_escape_string($conn, $bg_name);
                            $level = (int)$bg_level;
                            $insert_result = mysqli_query($conn, "INSERT INTO character_backgrounds (character_id, background_name, level) VALUES ({$char_id}, '{$name}', {$level})");
                            if (!$insert_result) {
                                $update_errors[] = "Failed to insert background '{$name}': " . mysqli_error($conn);
                            }
                        }
                    }
                    // Commit backgrounds updates
                    mysqli_commit($conn);
                }
            }
            
            // Final commit to ensure all changes are persisted
            mysqli_commit($conn);
            
            // Count how many operations actually succeeded
            $operations_count = 0;
            if (!empty($update_fields)) {
                $operations_count++;
            }
            foreach ($found_fields as $field) {
                if (in_array($field, ['Abilities', 'Disciplines', 'Backgrounds'])) {
                    $operations_count++;
                }
            }
            
            if (empty($update_errors)) {
                $update_stats['updated']++;
                $update_results[] = [
                    'id' => $char_id,
                    'name' => $char_name,
                    'fields_updated' => $found_fields,
                    'status' => 'success',
                    'debug' => (isset($debug_info) ? $debug_info : "Updated character ID {$char_id} ({$char_name})") . " | Operations: {$operations_count}"
                ];
            } else {
                $update_stats['errors']++;
                $update_results[] = [
                    'id' => $char_id,
                    'name' => $char_name,
                    'fields_updated' => $found_fields,
                    'status' => 'error',
                    'errors' => $update_errors,
                    'debug' => (isset($debug_info) ? $debug_info : "Failed to update character ID {$char_id} ({$char_name})") . " | Errors: " . implode('; ', $update_errors)
                ];
            }
        }
        
        // Log total characters processed
        $total_attempted = count($json_results);
        error_log("Character Data Update: Attempted {$total_attempted} characters, processed {$processed_count}, " . $update_stats['updated'] . " updated, " . $update_stats['errors'] . " errors");
        
        // Store summary for display
        $_SESSION['update_summary'] = [
            'total_attempted' => $total_attempted,
            'total_processed' => $processed_count,
            'updated' => $update_stats['updated'],
            'errors' => $update_stats['errors']
        ];
        
        // Store successfully updated character IDs to exclude from future JSON searches
        $successfully_updated_ids = [];
        foreach ($update_results as $result) {
            if ($result['status'] === 'success') {
                $successfully_updated_ids[] = $result['id'];
            }
        }
        
        // Clear ALL session data after update to force fresh search
        unset($_SESSION['json_search_results']);
        unset($_SESSION['json_search_stats']);
        unset($_SESSION['character_quality_results']);
        unset($_SESSION['character_quality_stats']);
        unset($_SESSION['character_quality_characters']);
        
        // Set flag to show success message
        $_SESSION['update_completed'] = true;
        $_SESSION['update_results'] = $update_results;
        $_SESSION['update_stats'] = $update_stats;
        $_SESSION['updated_character_ids'] = $successfully_updated_ids; // Track updated IDs
        
    } catch (Exception $e) {
        $update_error_message = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Load update results from session if update was completed
if (isset($_SESSION['update_completed']) && $_SESSION['update_completed']) {
    $update_results = $_SESSION['update_results'] ?? [];
    $update_stats = $_SESSION['update_stats'] ?? ['updated' => 0, 'errors' => 0];
    unset($_SESSION['update_completed']);
    unset($_SESSION['update_results']);
    unset($_SESSION['update_stats']);
}

?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <h1 class="h3 mb-0">Character Data Quality Blockers</h1>
                </div>
                <div class="card-body">
                    <p class="text-white mb-4">
                        This tool identifies missing or empty fields that prevent accurate character summaries.
                        Missing data includes: NULL values, empty strings, whitespace-only content, and empty JSON arrays/objects.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <button type="submit" name="search" class="btn btn-danger me-2">
                                Search Database
                            </button>
                            <label class="text-white">
                                <input type="checkbox" name="show_all" value="1" class="form-check-input me-2">
                                Show all characters (including complete ones)
                            </label>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-info h-100">
                <div class="card-header bg-info text-dark">
                    <h2 class="h4 mb-0">JSON File Search</h2>
                </div>
                <div class="card-body">
                    <p class="text-white mb-4">
                        Search JSON files in the reference directory to find missing data that exists in JSON files but is missing from the database.
                    </p>
                    
                    <form method="POST" action="">
                        <button type="submit" name="search_json" class="btn btn-info" 
                                <?php echo (!isset($_SESSION['character_quality_results']) || empty($_SESSION['character_quality_results'])) ? 'disabled' : ''; ?>>
                            Search JSON Files
                        </button>
                        <?php if (!isset($_SESSION['character_quality_results']) || empty($_SESSION['character_quality_results'])): ?>
                            <div class="mt-2">
                                <small class="text-white">Run database search first</small>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-secondary text-dark">
                    <h2 class="h5 mb-0">Sync Missing Abilities</h2>
                </div>
                <div class="card-body">
                    <p class="text-white mb-3">
                        Scan character JSON files for those missing abilities in the DB, then add abilities from JSON. Supports string and object formats.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="sync_abilities_from_json.php?dry_run=1" class="btn btn-outline-light">Dry run</a>
                        <form method="POST" action="sync_abilities_from_json.php" class="d-inline" onsubmit="return confirm('Add missing abilities to the database for matching characters?');">
                            <input type="hidden" name="execute" value="1">
                            <button type="submit" class="btn btn-success">Run sync</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($json_error_message)): ?>
                <div class="alert alert-danger">
                    <strong>JSON Search Error:</strong> <?php echo $json_error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($update_results) && !empty($update_results) && !isset($update_error_message)): ?>
                <div class="alert alert-success">
                    <strong>Database Updated Successfully!</strong><br>
                    Updated <?php echo number_format($update_stats['updated']); ?> character(s).<br>
                    <strong>Important:</strong> Please run a fresh "Search Database" to see the updated results. Characters that were just updated will be excluded from future JSON searches in this session.
                </div>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['abilities_sync_message'])) {
                $asm = $_SESSION['abilities_sync_message'];
                $alert_class = $asm['type'] === 'success' ? 'alert-success' : ($asm['type'] === 'warning' ? 'alert-warning' : 'alert-info');
                unset($_SESSION['abilities_sync_message']);
            ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($asm['text'], ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && !isset($error_message)): ?>
                <!-- Summary Panel -->
                <div class="card bg-dark border-warning mb-4">
                    <div class="card-header text-white" style="background-color: #4B0082;">
                        <h2 class="h4 mb-0">Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Total Characters Scanned:</strong><br>
                                    <span class="h5"><?php echo number_format($stats['total_scanned']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>With Missing Data:</strong><br>
                                    <span class="h5 text-warning"><?php echo number_format($stats['with_missing_data']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Complete Records:</strong><br>
                                    <span class="h5 text-success"><?php echo number_format($stats['total_scanned'] - $stats['with_missing_data']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($stats['field_counts'])): ?>
                            <hr class="border-secondary">
                            <h5 class="text-white mb-3">Top Missing Fields (Top 5)</h5>
                            <div class="row">
                                <?php 
                                $top_fields = array_slice($stats['field_counts'], 0, 5, true);
                                foreach ($top_fields as $field => $count): 
                                ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <span class="badge bg-danger me-2"><?php echo number_format($count); ?></span>
                                        <span class="text-white"><?php echo htmlspecialchars($field); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Results Table -->
                <?php if (!empty($results)): ?>
                    <div class="card bg-dark border-danger">
                        <div class="card-header bg-danger text-white">
                            <h2 class="h4 mb-0">Characters with Missing Data</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-striped mb-0" id="character-missing-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Character ID</th>
                                            <th scope="col">
                                                <button type="button" class="sortable-header-btn" data-sort="name">Character Name</button>
                                            </th>
                                            <th scope="col">
                                                <button type="button" class="sortable-header-btn" data-sort="missing_count">Missing Count</button>
                                            </th>
                                            <th>Missing Fields</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr class="<?php echo $result['missing_count'] === 0 ? 'table-success' : ''; ?>" data-name="<?php echo htmlspecialchars($result['name'], ENT_QUOTES, 'UTF-8'); ?>" data-missing-count="<?php echo (int)$result['missing_count']; ?>">
                                                <td><?php echo htmlspecialchars((string)$result['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($result['missing_count'] === 0): ?>
                                                        <span class="badge bg-success">Complete</span>
                                                    <?php else: ?>
                                                        <span class="badge text-white" style="background-color: #8B0000;"><?php echo $result['missing_count']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (empty($result['missing_fields'])): ?>
                                                        <span class="text-success">✓ All fields complete</span>
                                                    <?php else: ?>
                                                        <?php foreach ($result['missing_fields'] as $field): ?>
                                                            <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> No characters with missing data found. All scanned characters have complete records.
                    </div>
                <?php endif; ?>
                
                <!-- Field Filter Buttons -->
                <?php if (!empty($results)): ?>
                    <div class="card bg-dark border-secondary mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h3 class="h5 mb-0">Filter by Missing Field</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Biography">
                                        Biography
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Appearance">
                                        Appearance
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Nature">
                                        Nature
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Demeanor">
                                        Demeanor
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Abilities">
                                        Abilities
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Disciplines">
                                        Disciplines
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Backgrounds">
                                        Backgrounds
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="Image">
                                        Image
                                    </button>
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-secondary w-100" id="clear-filter">
                                        Show All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- JSON Search Results -->
            <?php if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_json']) && !isset($json_error_message)) || (isset($_SESSION['json_search_results']) && !empty($_SESSION['json_search_results']))): ?>
                <?php 
                // Use session data if available (for display after page refresh)
                if (isset($_SESSION['json_search_results'])) {
                    $json_search_results = $_SESSION['json_search_results'];
                    $json_search_stats = $_SESSION['json_search_stats'];
                }
                ?>
                <div class="card bg-dark border-info mb-4">
                    <div class="card-header bg-info text-dark d-flex justify-content-between align-items-center">
                        <h2 class="h4 mb-0">JSON File Search Results</h2>
                        <div>
                            <form method="POST" action="" id="update-database-form" style="display: inline;" onsubmit="return confirm('Are you sure you want to update the database with data from JSON files? This will overwrite existing data.');">
                                <button type="submit" name="update_database" class="btn btn-sm btn-success me-2" id="update-database-btn">
                                    Update Database
                                </button>
                            </form>
                            <a href="?reset_json=1" class="btn btn-sm btn-outline-light">Reset</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>JSON Files Checked:</strong><br>
                                    <span class="h5"><?php echo number_format($json_search_stats['characters_checked']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Characters with Data Found:</strong><br>
                                    <span class="h5 text-info"><?php echo number_format($json_search_stats['characters_with_data']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Fields Found in JSON:</strong><br>
                                    <span class="h5 text-success"><?php echo number_format(array_sum($json_search_stats['fields_found'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($json_search_stats['fields_found'])): ?>
                            <hr class="border-secondary">
                            <h5 class="text-white mb-3">Fields Found in JSON Files</h5>
                            <div class="row">
                                <?php foreach ($json_search_stats['fields_found'] as $field => $count): ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <span class="badge bg-info me-2"><?php echo number_format($count); ?></span>
                                        <span class="text-white"><?php echo htmlspecialchars($field); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($json_search_results)): ?>
                    <div class="card bg-dark border-info">
                        <div class="card-header bg-info text-dark">
                            <h2 class="h4 mb-0">Characters with Data Found in JSON</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Character ID</th>
                                            <th>Character Name</th>
                                            <th>JSON File</th>
                                            <th>Found in JSON</th>
                                            <th>Still Missing</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($json_search_results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)$result['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <code class="text-info"><?php echo htmlspecialchars($result['json_file']); ?></code>
                                                </td>
                                                <td>
                                                    <?php foreach ($result['found_in_json'] as $field): ?>
                                                        <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td>
                                                    <?php if (empty($result['still_missing'])): ?>
                                                        <span class="text-success">✓ All found</span>
                                                    <?php else: ?>
                                                        <?php foreach ($result['still_missing'] as $field): ?>
                                                            <span class="badge text-white me-1 mb-1" style="background-color: #4B0082;"><?php echo htmlspecialchars($field); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_json'])): ?>
                    <div class="alert alert-info">
                        <strong>No Data Found:</strong> No missing database fields were found in the JSON files.
                    </div>
                <?php endif; ?>
            <?php elseif (isset($_SESSION['json_search_results']) && empty($_SESSION['json_search_results'])): ?>
                <div class="alert alert-info">
                    <strong>No Data Found:</strong> No missing database fields were found in the JSON files.
                </div>
            <?php endif; ?>
            
            <!-- Database Update Results -->
            <?php if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_database'])) || (isset($update_results) && !empty($update_results))): ?>
                <?php if (isset($update_error_message)): ?>
                    <div class="alert alert-danger">
                        <strong>Update Error:</strong> <?php echo $update_error_message; ?>
                    </div>
                <?php else: ?>
                    <div class="card bg-dark border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h2 class="h4 mb-0">Database Update Results</h2>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="text-white">
                                        <strong>Characters Attempted:</strong><br>
                                        <span class="h5"><?php echo number_format(isset($_SESSION['update_summary']) ? $_SESSION['update_summary']['total_attempted'] : count($update_results)); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-white">
                                        <strong>Characters Updated:</strong><br>
                                        <span class="h5 text-success"><?php echo number_format($update_stats['updated']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-white">
                                        <strong>Errors:</strong><br>
                                        <span class="h5 <?php echo $update_stats['errors'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo number_format($update_stats['errors']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($update_results)): ?>
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover table-striped mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Character ID</th>
                                                <th>Character Name</th>
                                                <th>Fields Updated</th>
                                                <th>Status</th>
                                                <th>Debug Info</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($update_results as $result): ?>
                                                <tr class="<?php echo $result['status'] === 'success' ? 'table-success' : 'table-danger'; ?>">
                                                    <td><?php echo htmlspecialchars((string)$result['id']); ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php foreach ($result['fields_updated'] as $field): ?>
                                                            <span class="badge bg-info me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($result['status'] === 'success'): ?>
                                                            <span class="badge bg-success">Updated</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Error</span>
                                                            <?php if (isset($result['errors'])): ?>
                                                                <div class="mt-1">
                                                                    <?php foreach ($result['errors'] as $error): ?>
                                                                        <small class="text-danger d-block"><?php echo htmlspecialchars($error); ?></small>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($result['debug'])): ?>
                                                            <small class="<?php echo $result['status'] === 'success' ? 'text-dark' : 'text-white'; ?>"><?php echo htmlspecialchars($result['debug']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="card bg-dark border-success" style="max-width: 500px; width: 90%;">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Updating Database</h4>
        </div>
        <div class="card-body text-center">
            <div class="mb-3">
                <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p class="text-white mb-3">Processing character updates...</p>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" 
                     style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                    <span class="ms-2">Updating...</span>
                </div>
            </div>
            <p class="text-white mt-3 mb-0">
                <small>Please wait while we update the database. This may take a moment...</small>
            </p>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../../includes/footer.php';
?>
