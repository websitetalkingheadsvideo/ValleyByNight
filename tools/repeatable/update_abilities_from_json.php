<?php
/**
 * Update character abilities from JSON file
 * 
 * Usage: php tools/repeatable/update_abilities_from_json.php --json="Dorikhan Caine2015.json" --character="Dorikhan Caine"
 *        php tools/repeatable/update_abilities_from_json.php --json="Dorikhan Caine2015.json" --character-id=123
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Parse command line arguments
$json_file = null;
$character_name = null;
$character_id = null;

if (php_sapi_name() === 'cli') {
    // CLI mode
    foreach ($argv as $arg) {
        if (strpos($arg, '--json=') === 0) {
            $json_file = substr($arg, 7);
        } elseif (strpos($arg, '--character=') === 0) {
            $character_name = substr($arg, 12);
        } elseif (strpos($arg, '--character-id=') === 0) {
            $character_id = (int)substr($arg, 15);
        }
    }
} else {
    // Web mode - URL decode the filename
    $json_file = isset($_GET['json']) ? urldecode($_GET['json']) : null;
    $character_name = isset($_GET['character']) ? urldecode($_GET['character']) : null;
    $character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : null;
}

if (!$json_file && $character_name) {
    // Try to find JSON file by character name
    $json_dir = __DIR__ . '/../../reference/Characters/Added to Database/';
    $possible_files = [
        $character_name . '.json',
        $character_name . '2015.json',
        'npc__' . strtolower(str_replace(' ', '_', $character_name)) . '__*.json'
    ];
    
    foreach ($possible_files as $pattern) {
        if (strpos($pattern, '*') !== false) {
            $matches = glob($json_dir . $pattern);
            if (!empty($matches)) {
                $json_file = basename($matches[0]);
                break;
            }
        } else {
            $test_path = $json_dir . $pattern;
            if (file_exists($test_path)) {
                $json_file = $pattern;
                break;
            }
        }
    }
}

if (!$json_file) {
    die("Error: Must provide --json=\"filename.json\" or provide character name\n");
}

if (!$character_name && !$character_id) {
    die("Error: Must provide either --character=\"Name\" or --character-id=123\n");
}

// Load JSON file
$json_path = __DIR__ . '/../../reference/Characters/Added to Database/' . $json_file;
if (!file_exists($json_path)) {
    die("Error: JSON file not found: {$json_path}\n");
}

$json_content = file_get_contents($json_path);
$json_data = json_decode($json_content, true);

if (!$json_data || !isset($json_data['abilities'])) {
    die("Error: Invalid JSON or missing abilities array\n");
}

// Find character ID if name provided
if ($character_name && !$character_id) {
    $find_sql = "SELECT id FROM characters WHERE character_name = ? LIMIT 1";
    $find_stmt = mysqli_prepare($conn, $find_sql);
    if ($find_stmt) {
        mysqli_stmt_bind_param($find_stmt, 's', $character_name);
        mysqli_stmt_execute($find_stmt);
        $result = mysqli_stmt_get_result($find_stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $character_id = (int)$row['id'];
        } else {
            die("Error: Character '{$character_name}' not found in database\n");
        }
        mysqli_stmt_close($find_stmt);
    } else {
        die("Error: Failed to prepare query: " . mysqli_error($conn) . "\n");
    }
}

// Check if ability_category column exists
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$col_check = db_fetch_all($conn, $check_column_sql);
$has_category_column = !empty($col_check);

// Delete existing abilities
$delete_sql = "DELETE FROM character_abilities WHERE character_id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);
if ($delete_stmt) {
    mysqli_stmt_bind_param($delete_stmt, 'i', $character_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
}

// Insert new abilities
$inserted_count = 0;
$errors = [];

foreach ($json_data['abilities'] as $ability) {
    $name = '';
    $category = '';
    $level = 0;
    $specialization = '';
    
    // Handle different ability formats
    if (is_string($ability)) {
        // String format: "Ability Name 3" or "Ability Name (Specialization: ...)"
        if (preg_match('/^(.+?)\s+(\d+)(?:\s*\([^)]+\))?$/', $ability, $matches)) {
            $name = trim($matches[1]);
            $level = (int)$matches[2];
            // Try to extract specialization
            if (preg_match('/\(([^)]+)\)/', $ability, $spec_matches)) {
                $specialization = trim($spec_matches[1]);
                // Remove "Specialization: " prefix if present
                $specialization = preg_replace('/^Specialization:\s*/i', '', $specialization);
            }
        } else {
            // Just ability name, no level
            $name = trim($ability);
        }
    } elseif (is_array($ability)) {
        // Object format
        $name = $ability['name'] ?? $ability['ability_name'] ?? '';
        $category = $ability['category'] ?? $ability['ability_category'] ?? '';
        $level = isset($ability['level']) ? (int)$ability['level'] : 0;
        $specialization = $ability['specialization'] ?? '';
    }
    
    if (empty($name)) {
        continue;
    }
    
    $raw_name = trim($name);
    
    // Always look up category from abilities table (source of truth) if category column exists
    if ($has_category_column) {
        $lookup_sql = "SELECT category FROM abilities WHERE name COLLATE utf8mb4_unicode_ci = ? LIMIT 1";
        $lookup_stmt = mysqli_prepare($conn, $lookup_sql);
        if ($lookup_stmt) {
            mysqli_stmt_bind_param($lookup_stmt, 's', $raw_name);
            mysqli_stmt_execute($lookup_stmt);
            $lookup_result = mysqli_stmt_get_result($lookup_stmt);
            if ($lookup_row = mysqli_fetch_assoc($lookup_result)) {
                // Use category from abilities table (source of truth)
                $category = $lookup_row['category'];
            }
            mysqli_stmt_close($lookup_stmt);
        }
    }
    
    // Escape for direct SQL
    $name = mysqli_real_escape_string($conn, $name);
    $category = mysqli_real_escape_string($conn, $category);
    $specialization = mysqli_real_escape_string($conn, $specialization);
    
    // Insert ability
    if ($has_category_column) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES ({$character_id}, '{$name}', '{$category}', {$level}, '{$specialization}')";
    } else {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES ({$character_id}, '{$name}', {$level}, '{$specialization}')";
    }
    
    $insert_result = mysqli_query($conn, $insert_sql);
    if ($insert_result) {
        $inserted_count++;
    } else {
        $errors[] = "Failed to insert ability '{$raw_name}': " . mysqli_error($conn);
    }
}

echo "Successfully inserted {$inserted_count} abilities for character ID {$character_id}\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

mysqli_close($conn);
?>
