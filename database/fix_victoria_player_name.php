<?php
/**
 * Quick fix for Victoria Sterling's player_name
 * Updates player_name from "ST/NPC" to "NPC" in the database
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Fix Victoria Sterling Player Name</title></head><body>";
echo "<h1>Fix Victoria Sterling Player Name</h1>";
echo "<pre>";

// Update Victoria Sterling specifically
$update_sql = "UPDATE characters SET player_name = 'NPC' WHERE character_name = 'Victoria Sterling' AND (player_name = 'ST/NPC' OR player_name LIKE '%ST/NPC%')";

if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "✓ Successfully updated Victoria Sterling's player_name from 'ST/NPC' to 'NPC'\n";
        echo "Rows affected: $affected\n";
        
        // Verify the update
        $check_sql = "SELECT id, character_name, player_name FROM characters WHERE character_name = 'Victoria Sterling'";
        $result = mysqli_query($conn, $check_sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            echo "\nVerification:\n";
            echo "  Character: {$row['character_name']}\n";
            echo "  ID: {$row['id']}\n";
            echo "  Player Name: {$row['player_name']}\n";
        }
    } else {
        echo "No rows updated. Victoria Sterling may already be set to 'NPC' or the character may not exist in the database.\n";
        
        // Check what it currently is
        $check_sql = "SELECT id, character_name, player_name FROM characters WHERE character_name = 'Victoria Sterling'";
        $result = mysqli_query($conn, $check_sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            echo "\nCurrent value:\n";
            echo "  Character: {$row['character_name']}\n";
            echo "  ID: {$row['id']}\n";
            echo "  Player Name: '{$row['player_name']}'\n";
        } else {
            echo "\nVictoria Sterling not found in database.\n";
        }
    }
} else {
    echo "✗ Error updating: " . mysqli_error($conn) . "\n";
}

echo "</pre></body></html>";
mysqli_close($conn);
?>

