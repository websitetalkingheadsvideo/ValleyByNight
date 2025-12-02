<?php
/**
 * Update Character Images Script
 * Updates character_image field in database for characters with matching names
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

// Character image mappings - try multiple name variations
$character_images = [
    'Helena Crowly' => 'Helena_Crowly.png',
    'Helena_Crowly' => 'Helena_Crowly.png',
    'Charles "C.W." Whitford' => 'CW_Whitford.png',
    'C.W. Whitford' => 'CW_Whitford.png',
    'CW Whitford' => 'CW_Whitford.png',
    'Charles Whitford' => 'CW_Whitford.png',
    'Naomi Blackbird' => 'Naomi_Blackbird.jpg',
    'Lilith Nightshade' => 'Lilith_Nightshade.png',
    'Alistaire' => 'Alistaire.png',
    'Butch Reed' => 'Butch_Reed.png'
];

// Wraith characters (stored in separate table)
$wraith_images = [
    'Vechij Oksdagi' => 'Vechij_Oksdagi.png',
    'Vechij_Oksdagi' => 'Vechij_Oksdagi.png'
];

// Output header
if ($is_cli) {
    echo "Character Image Update Script\n";
    echo "============================\n\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Character Image Update</title></head><body>";
    echo "<h1>Character Image Update Script</h1>";
    echo "<pre>";
}

$updated = 0;
$not_found = 0;
$errors = [];

// Group by image filename to avoid duplicates
$image_to_names = [];
foreach ($character_images as $character_name => $image_filename) {
    if (!isset($image_to_names[$image_filename])) {
        $image_to_names[$image_filename] = [];
    }
    $image_to_names[$image_filename][] = $character_name;
}

foreach ($image_to_names as $image_filename => $name_variations) {
    $found = false;
    
    // Try each name variation
    foreach ($name_variations as $character_name) {
        // Try exact match first
        $find_sql = "SELECT id, character_name FROM characters WHERE character_name = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $find_sql);
        
        if (!$stmt) {
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, 's', $character_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $character_id = $row['id'];
            $actual_name = $row['character_name'];
            
            // Update character_image
            $update_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'si', $image_filename, $character_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "Updated: $actual_name -> $image_filename\n";
                    $updated++;
                    $found = true;
                } else {
                    $errors[] = "Failed to update $actual_name: " . mysqli_stmt_error($update_stmt);
                }
                
                mysqli_stmt_close($update_stmt);
            }
            
            mysqli_stmt_close($stmt);
            break; // Found and updated, move to next image
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!$found) {
        // Try LIKE query for partial matches
        $search_name = str_replace(['"', 'C.W.', 'C. W.'], '', $name_variations[0]);
        $search_name = trim($search_name);
        $like_pattern = "%$search_name%";
        
        $find_sql = "SELECT id, character_name FROM characters WHERE character_name LIKE ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $find_sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $like_pattern);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $character_id = $row['id'];
                $actual_name = $row['character_name'];
                
                $update_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'si', $image_filename, $character_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        echo "Updated (LIKE match): $actual_name -> $image_filename\n";
                        $updated++;
                        $found = true;
                    }
                    
                    mysqli_stmt_close($update_stmt);
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if (!$found) {
        echo "Not found: " . implode(' / ', $name_variations) . " -> $image_filename\n";
        $not_found++;
    }
}

// Update wraith characters
foreach ($wraith_images as $wraith_name => $image_filename) {
    // Check if wraith_characters table exists
    $check_table = "SHOW TABLES LIKE 'wraith_characters'";
    $table_result = mysqli_query($conn, $check_table);
    
    if (!$table_result || mysqli_num_rows($table_result) === 0) {
        echo "Wraith table not found, skipping wraith updates\n";
        break;
    }
    
    // Find wraith by name
    $find_sql = "SELECT id, character_name FROM wraith_characters WHERE character_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $find_sql);
    
    if (!$stmt) {
        $errors[] = "Failed to prepare statement for wraith $wraith_name: " . mysqli_error($conn);
        continue;
    }
    
    mysqli_stmt_bind_param($stmt, 's', $wraith_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $wraith_id = $row['id'];
        $actual_name = $row['character_name'];
        
        // Check if character_image column exists
        $check_column = "SHOW COLUMNS FROM wraith_characters LIKE 'character_image'";
        $column_result = mysqli_query($conn, $check_column);
        
        if ($column_result && mysqli_num_rows($column_result) > 0) {
            // Update character_image
            $update_sql = "UPDATE wraith_characters SET character_image = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'si', $image_filename, $wraith_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "Updated (wraith): $actual_name -> $image_filename\n";
                    $updated++;
                } else {
                    $errors[] = "Failed to update wraith $actual_name: " . mysqli_stmt_error($update_stmt);
                }
                
                mysqli_stmt_close($update_stmt);
            }
        } else {
            echo "Wraith table doesn't have character_image column, skipping\n";
        }
    } else {
        echo "Wraith not found: $wraith_name\n";
        $not_found++;
    }
    
    mysqli_stmt_close($stmt);
}

// Output summary
echo "\n";
echo "=== Update Summary ===\n";
echo "Updated: $updated\n";
echo "Not found: $not_found\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);
?>

