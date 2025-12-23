<?php
/**
 * Update Jennifer Torrance Character Image
 * Sets character_image field for Jennifer Torrance
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$character_name = 'Jennifer Torrance';
$image_filename = 'Jennifer Torrance.png';

// Find character by name
$find_sql = "SELECT id, character_name, character_image FROM characters WHERE character_name = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $find_sql);

if (!$stmt) {
    die("Failed to prepare statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 's', $character_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $character_id = $row['id'];
    $actual_name = $row['character_name'];
    $current_image = $row['character_image'] ?? '(none)';
    
    echo "Found character: $actual_name (ID: $character_id)\n";
    echo "Current image: $current_image\n";
    echo "Setting image to: $image_filename\n\n";
    
    // Update character_image
    $update_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, 'si', $image_filename, $character_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo "✓ Successfully updated character image!\n";
            echo "  $actual_name -> $image_filename\n";
        } else {
            echo "✗ Failed to update: " . mysqli_stmt_error($update_stmt) . "\n";
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        echo "✗ Failed to prepare update statement: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✗ Character not found: $character_name\n";
    echo "Trying case-insensitive search...\n";
    
    // Try case-insensitive search
    $find_sql = "SELECT id, character_name, character_image FROM characters WHERE LOWER(character_name) = LOWER(?) LIMIT 1";
    $stmt = mysqli_prepare($conn, $find_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $character_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $character_id = $row['id'];
            $actual_name = $row['character_name'];
            $current_image = $row['character_image'] ?? '(none)';
            
            echo "Found character: $actual_name (ID: $character_id)\n";
            echo "Current image: $current_image\n";
            echo "Setting image to: $image_filename\n\n";
            
            // Update character_image
            $update_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'si', $image_filename, $character_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "✓ Successfully updated character image!\n";
                    echo "  $actual_name -> $image_filename\n";
                } else {
                    echo "✗ Failed to update: " . mysqli_stmt_error($update_stmt) . "\n";
                }
                
                mysqli_stmt_close($update_stmt);
            }
        } else {
            echo "✗ Character still not found.\n";
        }
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

