<?php
/**
 * Paths JSON Import Script
 * 
 * Imports Necromancy and Thaumaturgy paths from JSON files into the database.
 * Supports upsert operations (insert if new, update if exists) based on (type, name) for paths
 * and (path_id, level) for powers.
 * 
 * Usage:
 *   CLI: php agents/paths_agent/import_paths_json.php
 *   Web: agents/paths_agent/import_paths_json.php
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
require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Verify tables exist
$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'paths_master'");
if (mysqli_num_rows($tables_check) === 0) {
    die("Error: paths_master table does not exist. Please create the table first.");
}
mysqli_free_result($tables_check);

$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'path_powers'");
if (mysqli_num_rows($tables_check) === 0) {
    die("Error: path_powers table does not exist. Please create the table first.");
}
mysqli_free_result($tables_check);

// Statistics
$stats = [
    'paths_inserted' => 0,
    'paths_updated' => 0,
    'powers_inserted' => 0,
    'powers_updated' => 0,
    'errors' => []
];

// Get initial counts for reference
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM paths_master");
$initial_paths = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
if ($result) mysqli_free_result($result);

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM path_powers");
$initial_powers = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
if ($result) mysqli_free_result($result);

/**
 * Import paths from a JSON file
 */
function importPathsFromFile($conn, $filepath, $type, &$stats) {
    if (!file_exists($filepath)) {
        $stats['errors'][] = "File not found: $filepath";
        return false;
    }
    
    $json_content = file_get_contents($filepath);
    if ($json_content === false) {
        $stats['errors'][] = "Failed to read file: $filepath";
        return false;
    }
    
    $data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $stats['errors'][] = "JSON parse error in $filepath: " . json_last_error_msg();
        return false;
    }
    
    if (!isset($data['paths']) || !is_array($data['paths'])) {
        $stats['errors'][] = "Invalid JSON structure in $filepath: missing 'paths' array";
        return false;
    }
    
    $path_count = count($data['paths']);
    if ($path_count === 0) {
        $stats['errors'][] = "No paths found in $filepath";
        return false;
    }
    
    $filename = basename($filepath);
    
    // Prepare statements
    $path_insert_sql = "INSERT INTO paths_master (name, type, description, source, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                            description = VALUES(description),
                            source = VALUES(source)";
    
    $path_insert_stmt = mysqli_prepare($conn, $path_insert_sql);
    if (!$path_insert_stmt) {
        $stats['errors'][] = "Failed to prepare path insert statement: " . mysqli_error($conn);
        return false;
    }
    
    $path_select_sql = "SELECT id FROM paths_master WHERE type = ? AND name = ?";
    $path_select_stmt = mysqli_prepare($conn, $path_select_sql);
    if (!$path_select_stmt) {
        $stats['errors'][] = "Failed to prepare path select statement: " . mysqli_error($conn);
        mysqli_stmt_close($path_insert_stmt);
        return false;
    }
    
    $power_insert_sql = "INSERT INTO path_powers (path_id, level, power_name, system_text, challenge_type, challenge_notes)
                         VALUES (?, ?, ?, NULL, 'unknown', NULL)
                         ON DUPLICATE KEY UPDATE
                             power_name = VALUES(power_name)";
    
    $power_insert_stmt = mysqli_prepare($conn, $power_insert_sql);
    if (!$power_insert_stmt) {
        $stats['errors'][] = "Failed to prepare power insert statement: " . mysqli_error($conn);
        mysqli_stmt_close($path_insert_stmt);
        mysqli_stmt_close($path_select_stmt);
        return false;
    }
    
    // Process each path
    foreach ($data['paths'] as $path) {
        if (!isset($path['name']) || !isset($path['powers'])) {
            $stats['errors'][] = "Invalid path structure: missing 'name' or 'powers'";
            continue;
        }
        
        $path_name = trim($path['name']);
        $path_description = isset($path['description']) ? trim($path['description']) : null;
        
        // Insert or update path
        mysqli_stmt_bind_param($path_insert_stmt, 'ssss',
            $path_name,
            $type,
            $path_description,
            $filename
        );
        
        if (!mysqli_stmt_execute($path_insert_stmt)) {
            $stats['errors'][] = "Failed to insert/update path '$path_name': " . mysqli_stmt_error($path_insert_stmt);
            continue;
        }
        
        // Check if this was an insert or update
        // ON DUPLICATE KEY UPDATE: returns 1 for insert, 2 for update
        $affected_rows = mysqli_stmt_affected_rows($path_insert_stmt);
        if ($affected_rows === 1) {
            $stats['paths_inserted']++;
        } elseif ($affected_rows === 2) {
            $stats['paths_updated']++;
        }
        
        // Get path_id
        mysqli_stmt_bind_param($path_select_stmt, 'ss', $type, $path_name);
        if (!mysqli_stmt_execute($path_select_stmt)) {
            $stats['errors'][] = "Failed to select path_id for '$path_name': " . mysqli_stmt_error($path_select_stmt);
            continue;
        }
        
        $result = mysqli_stmt_get_result($path_select_stmt);
        $path_row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        
        if (!$path_row) {
            $stats['errors'][] = "Failed to retrieve path_id for '$path_name'";
            continue;
        }
        
        $path_id = (int)$path_row['id'];
        
        // Process powers
        $powers = $path['powers'];
        foreach ($powers as $level_key => $power_name) {
            // Extract numeric level from level_X format
            if (preg_match('/^level_(\d+)$/i', $level_key, $matches)) {
                $level = (int)$matches[1];
                
                if ($level < 1 || $level > 5) {
                    $stats['errors'][] = "Invalid power level for '$path_name': $level (must be 1-5)";
                    continue;
                }
                
                $power_name_clean = trim($power_name);
                
                // Insert or update power
                mysqli_stmt_bind_param($power_insert_stmt, 'iis',
                    $path_id,
                    $level,
                    $power_name_clean
                );
                
                if (!mysqli_stmt_execute($power_insert_stmt)) {
                    $stats['errors'][] = "Failed to insert/update power for '$path_name' level $level: " . mysqli_stmt_error($power_insert_stmt);
                    continue;
                }
                
                // Check if this was an insert or update
                // Note: ON DUPLICATE KEY UPDATE returns 2 for updates, 1 for inserts
                $power_affected = mysqli_stmt_affected_rows($power_insert_stmt);
                if ($power_affected > 0) {
                    if ($power_affected === 2) {
                        $stats['powers_updated']++;
                    } else {
                        $stats['powers_inserted']++;
                    }
                }
            } else {
                $stats['errors'][] = "Invalid power level key format for '$path_name': $level_key (expected level_1 through level_5)";
            }
        }
    }
    
    // Clean up statements
    mysqli_stmt_close($path_insert_stmt);
    mysqli_stmt_close($path_select_stmt);
    mysqli_stmt_close($power_insert_stmt);
    
    return true;
}

// Main execution
$base_dir = __DIR__ . '/../../reference/mechanics/paths/';

// Debug: Check if directory exists
if (!is_dir($base_dir)) {
    $stats['errors'][] = "Directory not found: $base_dir";
}

$files = [
    ['file' => $base_dir . 'Necromancy_Paths.json', 'type' => 'Necromancy'],
    ['file' => $base_dir . 'Thaumaturgy_Paths.json', 'type' => 'Thaumaturgy']
];

foreach ($files as $file_info) {
    if (!file_exists($file_info['file'])) {
        $stats['errors'][] = "File not found: " . $file_info['file'];
        continue;
    }
    
    $result = importPathsFromFile($conn, $file_info['file'], $file_info['type'], $stats);
    
    if (!$result) {
        if (empty($stats['errors'])) {
            $stats['errors'][] = "Import failed for " . basename($file_info['file']) . " (no error details)";
        }
    }
}

// Output summary
if ($is_cli) {
    echo "Paths Import Summary\n";
    echo "====================\n";
    // Get final counts
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM paths_master");
    $final_paths = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
    if ($result) mysqli_free_result($result);
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM path_powers");
    $final_powers = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
    if ($result) mysqli_free_result($result);
    
    echo "Paths inserted: " . $stats['paths_inserted'] . "\n";
    echo "Paths updated: " . $stats['paths_updated'] . "\n";
    echo "Powers inserted: " . $stats['powers_inserted'] . "\n";
    echo "Powers updated: " . $stats['powers_updated'] . "\n";
    echo "\nTotal paths in database: $final_paths (was $initial_paths)\n";
    echo "Total powers in database: $final_powers (was $initial_powers)\n";
    
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "<!DOCTYPE html>\n<html><head><title>Paths Import</title></head><body>\n";
    echo "<h1>Paths Import Summary</h1>\n";
    // Get final counts
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM paths_master");
    $final_paths = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
    if ($result) mysqli_free_result($result);
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM path_powers");
    $final_powers = $result ? (int)mysqli_fetch_assoc($result)['count'] : 0;
    if ($result) mysqli_free_result($result);
    
    echo "<p>Paths inserted: <strong>" . htmlspecialchars((string)$stats['paths_inserted']) . "</strong></p>\n";
    echo "<p>Paths updated: <strong>" . htmlspecialchars((string)$stats['paths_updated']) . "</strong></p>\n";
    echo "<p>Powers inserted: <strong>" . htmlspecialchars((string)$stats['powers_inserted']) . "</strong></p>\n";
    echo "<p>Powers updated: <strong>" . htmlspecialchars((string)$stats['powers_updated']) . "</strong></p>\n";
    echo "<p><em>Total paths in database: $final_paths (was $initial_paths)</em></p>\n";
    echo "<p><em>Total powers in database: $final_powers (was $initial_powers)</em></p>\n";
    
    if (!empty($stats['errors'])) {
        echo "<h2>Errors</h2>\n<ul>\n";
        foreach ($stats['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p><em>No errors encountered.</em></p>\n";
    }
    echo "</body></html>\n";
}

mysqli_close($conn);
?>

