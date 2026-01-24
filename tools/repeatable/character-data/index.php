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

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Page-specific CSS
$extra_css = ['css/admin_panel.css'];
include __DIR__ . '/../../../includes/header.php';

// Fields to check for missing data
$critical_fields = [
    'biography' => 'Biography',
    'appearance' => 'Appearance',
    'histories' => 'Histories',
    'history' => 'History',
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
    try {
        // Check which history column exists
        $history_column = null;
        $columns_check = db_fetch_all($conn, "SHOW COLUMNS FROM characters LIKE 'histories'");
        if (!empty($columns_check)) {
            $history_column = 'histories';
        } else {
            $columns_check = db_fetch_all($conn, "SHOW COLUMNS FROM characters LIKE 'history'");
            if (!empty($columns_check)) {
                $history_column = 'history';
            }
        }
        
        // Build base select fields
        $select_fields = ['c.id', 'c.character_name', 'c.biography', 'c.appearance', 'c.concept', 'c.nature', 'c.demeanor', 'c.character_image'];
        if ($history_column) {
            $select_fields[] = 'c.' . $history_column;
        }
        
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
            // Skip if field doesn't exist in result (e.g., histories vs history)
            if (!array_key_exists($field, $char)) {
                continue;
            }
            
            $value = $char[$field] ?? null;
            $is_missing = false;
            
            // Check for NULL, empty string, or whitespace-only
            if ($value === null || $value === '' || trim($value) === '') {
                $is_missing = true;
            }
            // Check for empty JSON arrays/objects
            elseif (in_array($field, ['histories', 'history']) && 
                    (trim($value) === '[]' || trim($value) === '{}')) {
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
                } elseif (in_array($field, ['histories', 'history']) && 
                        (trim($value) === '[]' || trim($value) === '{}')) {
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
        if (!isset($_SESSION['character_quality_results']) || empty($_SESSION['character_quality_results'])) {
            throw new Exception("Please run the database search first to get character data.");
        }
        
        $db_results = $_SESSION['character_quality_results'];
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
                    case 'History':
                    case 'Histories':
                        $history_field = isset($json_data['histories']) ? 'histories' : 'history';
                        $history_value = $json_data[$history_field] ?? '';
                        $has_data = !empty($history_value) && trim($history_value) !== '' && 
                                   trim($history_value) !== '[]' && trim($history_value) !== '{}';
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
                        $has_data = !empty($json_data['abilities']) && is_array($json_data['abilities']) && count($json_data['abilities']) > 0;
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
        if (!isset($_SESSION['json_search_results']) || empty($_SESSION['json_search_results'])) {
            throw new Exception("No JSON search results found. Please run JSON search first.");
        }
        
        $json_results = $_SESSION['json_search_results'];
        
        foreach ($json_results as $result) {
            $char_id = $result['id'];
            $json_data = $result['json_data'];
            $found_fields = $result['found_in_json'];
            
            $update_errors = [];
            
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
                    case 'History':
                    case 'Histories':
                        $history_field = isset($json_data['histories']) ? 'histories' : 'history';
                        $history_value = $json_data[$history_field] ?? '';
                        if (!empty($history_value) && trim($history_value) !== '' && 
                            trim($history_value) !== '[]' && trim($history_value) !== '{}') {
                            // Check which column exists in database
                            $col_check = db_fetch_all($conn, "SHOW COLUMNS FROM characters LIKE 'histories'");
                            $db_field = !empty($col_check) ? 'histories' : 'history';
                            $update_fields[] = $db_field . ' = ?';
                            $update_values[] = trim($history_value);
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
                $update_values[] = $char_id; // For WHERE clause
                $query = "UPDATE characters SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                if ($stmt) {
                    $types = str_repeat('s', count($update_values) - 1) . 'i'; // All strings except last (id)
                    mysqli_stmt_bind_param($stmt, $types, ...$update_values);
                    if (!mysqli_stmt_execute($stmt)) {
                        $update_errors[] = "Failed to update character fields: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $update_errors[] = "Failed to prepare update statement: " . mysqli_error($conn);
                }
            }
            
            // Update related tables
            foreach ($found_fields as $field) {
                if ($field === 'Abilities' && !empty($json_data['abilities']) && is_array($json_data['abilities'])) {
                    // Delete existing abilities
                    mysqli_query($conn, "DELETE FROM character_abilities WHERE character_id = " . (int)$char_id);
                    
                    // Insert new abilities
                    foreach ($json_data['abilities'] as $ability) {
                        if (is_array($ability) && isset($ability['name'])) {
                            $name = mysqli_real_escape_string($conn, $ability['name']);
                            $category = isset($ability['category']) ? mysqli_real_escape_string($conn, $ability['category']) : '';
                            $level = isset($ability['level']) ? (int)$ability['level'] : 0;
                            mysqli_query($conn, "INSERT INTO character_abilities (character_id, ability_name, category, level) VALUES ({$char_id}, '{$name}', '{$category}', {$level})");
                        }
                    }
                }
                
                if ($field === 'Disciplines' && !empty($json_data['disciplines']) && is_array($json_data['disciplines'])) {
                    // Delete existing disciplines
                    mysqli_query($conn, "DELETE FROM character_disciplines WHERE character_id = " . (int)$char_id);
                    
                    // Insert new disciplines
                    foreach ($json_data['disciplines'] as $discipline) {
                        if (is_array($discipline) && isset($discipline['name'])) {
                            $name = mysqli_real_escape_string($conn, $discipline['name']);
                            $level = isset($discipline['level']) ? (int)$discipline['level'] : 0;
                            mysqli_query($conn, "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES ({$char_id}, '{$name}', {$level})");
                        }
                    }
                }
                
                if ($field === 'Backgrounds' && !empty($json_data['backgrounds'])) {
                    // Delete existing backgrounds
                    mysqli_query($conn, "DELETE FROM character_backgrounds WHERE character_id = " . (int)$char_id);
                    
                    // Insert new backgrounds
                    $backgrounds = is_array($json_data['backgrounds']) ? $json_data['backgrounds'] : (array)$json_data['backgrounds'];
                    foreach ($backgrounds as $bg_name => $bg_level) {
                        if (is_numeric($bg_level)) {
                            $name = mysqli_real_escape_string($conn, $bg_name);
                            $level = (int)$bg_level;
                            mysqli_query($conn, "INSERT INTO character_backgrounds (character_id, background_name, level) VALUES ({$char_id}, '{$name}', {$level})");
                        }
                    }
                }
            }
            
            if (empty($update_errors)) {
                $update_stats['updated']++;
                $update_results[] = [
                    'id' => $char_id,
                    'name' => $result['name'],
                    'fields_updated' => $found_fields,
                    'status' => 'success'
                ];
            } else {
                $update_stats['errors']++;
                $update_results[] = [
                    'id' => $char_id,
                    'name' => $result['name'],
                    'fields_updated' => $found_fields,
                    'status' => 'error',
                    'errors' => $update_errors
                ];
            }
        }
        
        // Clear JSON search results after update
        unset($_SESSION['json_search_results']);
        unset($_SESSION['json_search_stats']);
        
    } catch (Exception $e) {
        $update_error_message = "Error: " . htmlspecialchars($e->getMessage());
    }
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
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && !isset($error_message)): ?>
                <!-- Summary Panel -->
                <div class="card bg-dark border-warning mb-4">
                    <div class="card-header bg-warning text-dark">
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
                                <table class="table table-dark table-hover table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Character ID</th>
                                            <th>Character Name</th>
                                            <th>Missing Count</th>
                                            <th>Missing Fields</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr class="<?php echo $result['missing_count'] === 0 ? 'table-success' : ''; ?>">
                                                <td><?php echo htmlspecialchars((string)$result['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($result['missing_count'] === 0): ?>
                                                        <span class="badge bg-success">Complete</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning"><?php echo $result['missing_count']; ?></span>
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
                                    <button type="button" class="btn btn-outline-danger w-100 filter-btn" data-field="History">
                                        History
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
                                                            <span class="badge bg-warning me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
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
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_database'])): ?>
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
                                <div class="col-md-6 mb-3">
                                    <div class="text-white">
                                        <strong>Characters Updated:</strong><br>
                                        <span class="h5 text-success"><?php echo number_format($update_stats['updated']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const clearFilterBtn = document.getElementById('clear-filter');
    const tableRows = document.querySelectorAll('table tbody tr');
    
    // Only initialize if filter elements exist
    if (filterButtons.length === 0 || !clearFilterBtn) {
        return;
    }
    
    let activeFilter = null;
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.getAttribute('data-field');
            
            // Toggle active state
            if (activeFilter === field) {
                // If clicking the same button, clear filter
                clearFilter();
                return;
            }
            
            activeFilter = field;
            
            // Update button states
            filterButtons.forEach(b => {
                b.classList.remove('active');
                b.classList.remove('btn-danger');
                b.classList.add('btn-outline-danger');
            });
            this.classList.add('active');
            this.classList.remove('btn-outline-danger');
            this.classList.add('btn-danger');
            
            // Filter rows
            tableRows.forEach(row => {
                const missingFieldsCell = row.querySelector('td:last-child');
                if (!missingFieldsCell) return;
                
                const badges = missingFieldsCell.querySelectorAll('.badge.bg-danger');
                let hasField = false;
                
                badges.forEach(badge => {
                    if (badge.textContent.trim() === field) {
                        hasField = true;
                    }
                });
                
                if (hasField) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    clearFilterBtn.addEventListener('click', function() {
        clearFilter();
    });
    
    function clearFilter() {
        activeFilter = null;
        
        // Reset button states
        filterButtons.forEach(b => {
            b.classList.remove('active');
            b.classList.remove('btn-danger');
            b.classList.add('btn-outline-danger');
        });
        
        // Show all rows
        tableRows.forEach(row => {
            row.style.display = '';
        });
    }
});

// Loading overlay for database update
document.addEventListener('DOMContentLoaded', function() {
    const updateForm = document.getElementById('update-database-form');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    if (updateForm) {
        updateForm.addEventListener('submit', function() {
            // Show loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }
        });
    }
});
</script>

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
