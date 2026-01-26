<?php
/**
 * Assign Lesser Harpy Position to Sabine, Sebastian, and Betty (CLI)
 * Creates position assignments for the Lesser Harpy position
 */

declare(strict_types=1);

// CLI-only tool
if (php_sapi_name() !== 'cli') {
    die("This tool must be run from the command line.\n");
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../includes/camarilla_positions_helper.php';

$position_id = 'lesser_harpy';
$position_name = 'Lesser Harpy';
$default_night = CAMARILLA_DEFAULT_NIGHT;

// Characters to assign
$character_names = ['Sabine', 'Sebastian', 'Betty'];

echo "Assigning Lesser Harpy Position...\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Verify position exists
    $position_check = db_fetch_one($conn, "SELECT position_id, name FROM camarilla_positions WHERE position_id = ?", "s", [$position_id]);
    
    if (!$position_check) {
        throw new Exception("Position '$position_name' (ID: $position_id) does not exist. Please create it first.");
    }
    
    echo "✅ Position found: {$position_check['name']}\n\n";
    
    $assigned = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($character_names as $character_name) {
        echo "Processing: $character_name\n";
        echo str_repeat("-", 50) . "\n";
        
        // Step 1: Find character (try multiple name variations)
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
            // Try searching by the transformed ID format
            $assignment_id_format = strtoupper(str_replace([' ', '-'], '_', $character_name));
            $character = db_fetch_one($conn, 
                "SELECT id, character_name FROM characters 
                 WHERE UPPER(REPLACE(character_name, ' ', '_')) = ? 
                    OR UPPER(REPLACE(REPLACE(character_name, ' ', '_'), '-', '_')) = ?",
                "ss", [$assignment_id_format, $assignment_id_format]);
        }
        
        if (!$character) {
            echo "  ❌ Character '$character_name' not found in database\n";
            $errors[] = $character_name;
            echo "\n";
            continue;
        }
        
        echo "  ✅ Found character: {$character['character_name']} (ID: {$character['id']})\n";
        
        // Step 2: Create character_id for assignment (transform name)
        $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character['character_name']));
        echo "  Assignment character_id: $assignment_character_id\n";
        
        // Step 3: Check if assignment already exists
        $existing_assignment = db_fetch_one($conn, 
            "SELECT id FROM camarilla_position_assignments 
             WHERE position_id = ? AND character_id = ? 
             AND start_night <= ? AND (end_night IS NULL OR end_night >= ?)",
            "ssss", [$position_id, $assignment_character_id, $default_night, $default_night]);
        
        if ($existing_assignment) {
            echo "  ⚠️  Assignment already exists (ID: {$existing_assignment['id']})\n";
            echo "  Current assignment is active. Skipping.\n";
            $skipped++;
        } else {
            // Step 4: Create assignment
            echo "  Creating new assignment...\n";
            $insert_assignment = "INSERT INTO camarilla_position_assignments 
                                (position_id, character_id, start_night, end_night, is_acting) 
                                VALUES (?, ?, ?, NULL, 0)";
            $assignment_result = db_execute($conn, $insert_assignment, "sss", [
                $position_id,
                $assignment_character_id,
                $default_night
            ]);
            
            if ($assignment_result === false) {
                $error_msg = "Failed to create assignment: " . mysqli_error($conn);
                echo "  ❌ $error_msg\n";
                $errors[] = "$character_name: $error_msg";
            } else {
                echo "  ✅ Created assignment (ID: $assignment_result)\n";
                echo "  Position: $position_name\n";
                echo "  Character: {$character['character_name']}\n";
                echo "  Start Night: $default_night\n";
                echo "  Status: Permanent\n";
                $assigned++;
            }
        }
        
        echo "\n";
    }
    
    // Summary
    echo str_repeat("=", 70) . "\n";
    echo "SUMMARY\n";
    echo str_repeat("=", 70) . "\n";
    echo "Successfully assigned: $assigned\n";
    echo "Already assigned (skipped): $skipped\n";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    if ($assigned > 0 || $skipped > 0) {
        echo "\n✅ Process complete!\n";
    } else {
        echo "\n⚠️  No assignments were created.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

mysqli_close($conn);
?>
