<?php
/**
 * Remove Talon Position from Sabine and Sebastian (CLI)
 * Ends their Talon position assignments by setting end_night
 */

declare(strict_types=1);

// CLI-only tool
if (php_sapi_name() !== 'cli') {
    die("This tool must be run from the command line.\n");
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../includes/camarilla_positions_helper.php';

$default_night = CAMARILLA_DEFAULT_NIGHT;

// Characters to remove Talon from
$character_names = ['Sabine', 'Sebastian'];

echo "Removing Talon Position...\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Step 1: Find Talon position (try multiple variations)
    echo "Step 1: Finding Talon position...\n";
    $position_variations = ['talon', 'Talon', 'TALON', 'twin_talon', 'twin_talon', 'Twin Talon'];
    $position = null;
    $position_id = null;
    
    foreach ($position_variations as $pos_var) {
        $position = db_fetch_one($conn, 
            "SELECT position_id, name FROM camarilla_positions 
             WHERE LOWER(position_id) = LOWER(?) OR LOWER(name) LIKE LOWER(?)",
            "ss", [$pos_var, "%$pos_var%"]);
        
        if ($position) {
            $position_id = $position['position_id'];
            echo "  ✅ Found position: {$position['name']} (ID: $position_id)\n";
            break;
        }
    }
    
    if (!$position) {
        // Try searching by name containing "talon"
        $position = db_fetch_one($conn, 
            "SELECT position_id, name FROM camarilla_positions 
             WHERE LOWER(name) LIKE '%talon%'",
            "");
        
        if ($position) {
            $position_id = $position['position_id'];
            echo "  ✅ Found position: {$position['name']} (ID: $position_id)\n";
        }
    }
    
    if (!$position) {
        throw new Exception("Talon position not found in database. Please verify the position exists.");
    }
    
    echo "\n";
    
    $removed = 0;
    $not_found = 0;
    $errors = [];
    
    foreach ($character_names as $character_name) {
        echo "Processing: $character_name\n";
        echo str_repeat("-", 50) . "\n";
        
        // Step 2: Find character
        $character = db_fetch_one($conn, 
            "SELECT id, character_name FROM characters 
             WHERE character_name LIKE ? OR character_name = ?
             ORDER BY CASE 
                WHEN character_name = ? THEN 1 
                WHEN character_name LIKE ? THEN 2 
                ELSE 3 
             END
             LIMIT 1",
            "ssss", [
                "$character_name%",
                $character_name,
                $character_name,
                "$character_name%"
            ]);
        
        if (!$character) {
            echo "  ❌ Character '$character_name' not found in database\n";
            $not_found++;
            echo "\n";
            continue;
        }
        
        echo "  ✅ Found character: {$character['character_name']} (ID: {$character['id']})\n";
        
        // Step 3: Create character_id for assignment lookup (transform name)
        $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character['character_name']));
        echo "  Assignment character_id: $assignment_character_id\n";
        
        // Step 4: Find active Talon assignment
        $assignment = db_fetch_one($conn, 
            "SELECT * FROM camarilla_position_assignments 
             WHERE position_id = ? AND character_id = ? 
             AND start_night <= ? AND (end_night IS NULL OR end_night >= ?)
             ORDER BY start_night DESC
             LIMIT 1",
            "ssss", [$position_id, $assignment_character_id, $default_night, $default_night]);
        
        if (!$assignment) {
            echo "  ⚠️  No active Talon assignment found for this character\n";
            $not_found++;
            echo "\n";
            continue;
        }
        
        echo "  Found assignment (started: {$assignment['start_night']})\n";
        
        // Step 5: End the assignment by setting end_night
        if ($assignment['end_night'] === null || $assignment['end_night'] >= $default_night) {
            echo "  Ending assignment...\n";
            
            // Use assignment's internal ID if available, otherwise use position_id + character_id + start_night
            $update_query = "UPDATE camarilla_position_assignments 
                            SET end_night = ? 
                            WHERE position_id = ? 
                            AND character_id = ? 
                            AND start_night = ?
                            AND (end_night IS NULL OR end_night >= ?)";
            
            $update_result = db_execute($conn, $update_query, "sssss", [
                $default_night,
                $position_id,
                $assignment_character_id,
                $assignment['start_night'],
                $default_night
            ]);
            
            if ($update_result === false) {
                $error_msg = "Failed to end assignment: " . mysqli_error($conn);
                echo "  ❌ $error_msg\n";
                $errors[] = "$character_name: $error_msg";
            } else {
                echo "  ✅ Ended Talon assignment\n";
                echo "  Position: {$position['name']}\n";
                echo "  Character: {$character['character_name']}\n";
                echo "  End Night: $default_night\n";
                $removed++;
            }
        } else {
            echo "  ⚠️  Assignment already ended (end_night: {$assignment['end_night']})\n";
            $not_found++;
        }
        
        echo "\n";
    }
    
    // Summary
    echo str_repeat("=", 70) . "\n";
    echo "SUMMARY\n";
    echo str_repeat("=", 70) . "\n";
    echo "Successfully removed: $removed\n";
    echo "Not found/already ended: $not_found\n";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    if ($removed > 0) {
        echo "\n✅ Process complete!\n";
    } else {
        echo "\n⚠️  No assignments were removed.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

mysqli_close($conn);
?>
