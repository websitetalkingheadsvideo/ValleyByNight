<?php
/**
 * Fix Player Names Script
 * Updates player_name field to "NPC" for characters with incorrect values
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Fix Player Names</title></head><body>";
echo "<h1>Fix Player Names</h1>";
echo "<pre>";

// Values that should be changed to "NPC"
$incorrect_values = [
    'ST/NPC',
    'Player Name or ST/NPC',
    'ST/NPC ',
    ' ST/NPC',
    'Player Name or ST/NPC ',
    ' Player Name or ST/NPC'
];

$updated = 0;
$errors = [];

// First, find all characters with incorrect values
$find_sql = "SELECT id, character_name, player_name FROM characters WHERE player_name IN (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $find_sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ssssss', 
        $incorrect_values[0],
        $incorrect_values[1],
        $incorrect_values[2],
        $incorrect_values[3],
        $incorrect_values[4],
        $incorrect_values[5]
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $characters_to_fix = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $characters_to_fix[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    echo "Found " . count($characters_to_fix) . " character(s) with incorrect player_name values:\n\n";
    
    // Update each character
    $update_sql = "UPDATE characters SET player_name = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    
    if ($update_stmt) {
        foreach ($characters_to_fix as $char) {
            echo sprintf("Updating: %-40s (ID: %-5s) from '%s' to 'NPC'\n", 
                $char['character_name'], 
                $char['id'], 
                $char['player_name']
            );
            
            mysqli_stmt_bind_param($update_stmt, 'si', $new_value, $char['id']);
            $new_value = 'NPC';
            
            if (mysqli_stmt_execute($update_stmt)) {
                $updated++;
            } else {
                $errors[] = "Failed to update {$char['character_name']}: " . mysqli_stmt_error($update_stmt);
            }
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $errors[] = "Failed to prepare update statement: " . mysqli_error($conn);
    }
} else {
    $errors[] = "Failed to prepare find statement: " . mysqli_error($conn);
}

// Also check for any variations using LIKE
$like_patterns = [
    '%ST/NPC%',
    '%Player Name or ST/NPC%'
];

foreach ($like_patterns as $pattern) {
    $find_sql = "SELECT id, character_name, player_name FROM characters WHERE player_name LIKE ? AND player_name != 'NPC'";
    $stmt = mysqli_prepare($conn, $find_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $pattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $update_sql = "UPDATE characters SET player_name = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        
        if ($update_stmt) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo sprintf("Updating (LIKE match): %-40s (ID: %-5s) from '%s' to 'NPC'\n", 
                    $row['character_name'], 
                    $row['id'], 
                    $row['player_name']
                );
                
                $new_value = 'NPC';
                mysqli_stmt_bind_param($update_stmt, 'si', $new_value, $row['id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated++;
                } else {
                    $errors[] = "Failed to update {$row['character_name']}: " . mysqli_stmt_error($update_stmt);
                }
            }
            mysqli_stmt_close($update_stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

echo "\n";
echo "=== Update Summary ===\n";
echo "Updated: $updated character(s)\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "</pre></body></html>";
mysqli_close($conn);
?>

