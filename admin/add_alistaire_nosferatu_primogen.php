<?php
/**
 * Add Alistaire as Nosferatu Primogen
 * Creates Nosferatu Primogen position if needed and assigns Alistaire
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

$default_night = CAMARILLA_DEFAULT_NIGHT;
$position_id = 'nosferatu_primogen';
$position_name = 'Nosferatu Primogen';
$character_name = 'Alistaire Hawthorn'; // Based on earlier mention of ALISTAIRE_HAWTHORN

echo "<h1>Add Alistaire as Nosferatu Primogen</h1>\n";
echo "<pre>\n";

try {
    // Step 1: Check if Nosferatu Primogen position exists
    echo "Step 1: Checking if Nosferatu Primogen position exists...\n";
    $position_check = db_fetch_one($conn, "SELECT position_id, name FROM camarilla_positions WHERE position_id = ?", "s", [$position_id]);
    
    if (!$position_check) {
        echo "  Position doesn't exist. Creating it...\n";
        $insert_position = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                           VALUES (?, ?, 'Clan Representative', 'Nosferatu clan representative on the Primogen Council', 5)";
        $result = db_execute($conn, $insert_position, "ss", [$position_id, $position_name]);
        
        if ($result === false) {
            throw new Exception("Failed to create position: " . mysqli_error($conn));
        }
        echo "  ✅ Created Nosferatu Primogen position\n";
    } else {
        echo "  ✅ Nosferatu Primogen position already exists\n";
    }
    
    // Step 2: Find Alistaire character (try multiple name variations)
    echo "\nStep 2: Finding Alistaire character...\n";
    $character = db_fetch_one($conn, 
        "SELECT id, character_name FROM characters 
         WHERE character_name LIKE '%Alistaire%' OR character_name LIKE '%Alistair%'
         ORDER BY CASE WHEN character_name = 'Alistaire Hawthorn' THEN 1 
                      WHEN character_name LIKE 'Alistaire%' THEN 2 
                      ELSE 3 END
         LIMIT 1");
    
    if (!$character) {
        // Try searching by the transformed ID format
        echo "  Character not found by name. Checking assignment table format...\n";
        $assignment_id_format = 'ALISTAIRE_HAWTHORN';
        $character = db_fetch_one($conn, 
            "SELECT id, character_name FROM characters 
             WHERE UPPER(REPLACE(character_name, ' ', '_')) = ? 
                OR UPPER(REPLACE(REPLACE(character_name, ' ', '_'), '-', '_')) = ?",
            "ss", [$assignment_id_format, $assignment_id_format]);
    }
    
    if (!$character) {
        throw new Exception("Character 'Alistaire' not found in database. Please create the character first or specify the exact name.");
    }
    
    echo "  ✅ Found character: {$character['character_name']} (ID: {$character['id']})\n";
    
    // Step 3: Create character_id for assignment (transform name)
    $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character['character_name']));
    echo "  Assignment character_id will be: $assignment_character_id\n";
    
    // Step 4: Check if assignment already exists
    echo "\nStep 3: Checking for existing assignment...\n";
    $existing_assignment = db_fetch_one($conn, 
        "SELECT id FROM camarilla_position_assignments 
         WHERE position_id = ? AND character_id = ? 
         AND start_night <= ? AND (end_night IS NULL OR end_night >= ?)",
        "ssss", [$position_id, $assignment_character_id, $default_night, $default_night]);
    
    if ($existing_assignment) {
        echo "  ⚠️  Assignment already exists (ID: {$existing_assignment['id']})\n";
        echo "  Current assignment is active. No changes made.\n";
    } else {
        // Step 5: Create assignment
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
            throw new Exception("Failed to create assignment: " . mysqli_error($conn));
        }
        
        echo "  ✅ Created assignment (ID: $assignment_result)\n";
        echo "  Position: $position_name\n";
        echo "  Character: {$character['character_name']}\n";
        echo "  Start Night: $default_night\n";
        echo "  Status: Permanent\n";
    }
    
    echo "\n✅ Success!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>





