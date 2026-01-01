<?php
/**
 * Update New Character Images Script
 * Updates character_image field for 3 newly added character images
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

// Character image mappings for the 3 new images
// Based on filename similarity to character names
$character_images = [
    'Betty' => 'Betty.webp',
    'Dr. Margaret Ashford' => 'Dr. Margaret Ashford.png',
    'Barry Horowitz' => 'Barry Horowitz Neo-Noir Scene.png'  // May need adjustment if this is a scene image
];

// Output header
if ($is_cli) {
    echo "New Character Image Update Script\n";
    echo "==================================\n\n";
} else {
    echo "<!DOCTYPE html><html><head><title>New Character Image Update</title></head><body>";
    echo "<h1>New Character Image Update Script</h1>";
    echo "<pre>";
}

$updated = 0;
$not_found = 0;
$errors = [];

foreach ($character_images as $character_name => $image_filename) {
    $found = false;
    
    // Try exact match first
    $find_sql = "SELECT id, character_name FROM characters WHERE character_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $find_sql);
    
    if (!$stmt) {
        $errors[] = "Failed to prepare statement for $character_name: " . mysqli_error($conn);
        continue;
    }
    
    mysqli_stmt_bind_param($stmt, 's', $character_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $character_id = $row['id'];
        $actual_name = $row['character_name'];
        
        // Verify image file exists
        $image_path = __DIR__ . '/../uploads/characters/' . $image_filename;
        if (!file_exists($image_path)) {
            echo "Warning: Image file not found: $image_filename (skipping $actual_name)\n";
            $not_found++;
            mysqli_stmt_close($stmt);
            continue;
        }
        
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
    } else {
        // Try LIKE query for partial matches
        $like_pattern = "%$character_name%";
        
        $find_sql = "SELECT id, character_name FROM characters WHERE character_name LIKE ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $find_sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $like_pattern);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $character_id = $row['id'];
                $actual_name = $row['character_name'];
                
                // Verify image file exists
                $image_path = __DIR__ . '/../uploads/characters/' . $image_filename;
                if (!file_exists($image_path)) {
                    echo "Warning: Image file not found: $image_filename (skipping $actual_name)\n";
                    $not_found++;
                    mysqli_stmt_close($stmt);
                    continue;
                }
                
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
        }
    }
    
    if (!$found) {
        echo "Character not found: $character_name -> $image_filename\n";
        $not_found++;
    }
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
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
