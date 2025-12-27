<?php
/**
 * Add Eddy Valiant as Scourge
 * Creates Scourge position if needed and assigns Eddy Valiant
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
$position_id = 'scourge';
$position_name = 'Scourge';
$character_name = 'Eddy Valiant';

echo "<h1>Add Eddy Valiant as Scourge</h1>\n";
echo "<pre>\n";

try {
    // Step 1: Check if Scourge position exists
    echo "Step 1: Checking if Scourge position exists...\n";
    $position_check = db_fetch_one($conn, "SELECT position_id, name FROM camarilla_positions WHERE position_id = ?", "s", [$position_id]);
    
    if (!$position_check) {
        echo "  Position doesn't exist. Creating it...\n";
        $insert_position = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                           VALUES (?, ?, 'Law Enforcement', 'Hunts down unauthorized vampires and threats to the Masquerade', 3)";
        $result = db_execute($conn, $insert_position, "ss", [$position_id, $position_name]);
        
        if ($result === false) {
            throw new Exception("Failed to create position: " . mysqli_error($conn));
        }
        echo "  ✅ Created Scourge position\n";
    } else {
        echo "  ✅ Scourge position already exists\n";
    }
    
    // Step 2: Find Eddy Valiant character
    echo "\nStep 2: Finding Eddy Valiant character...\n";
    $character = db_fetch_one($conn, "SELECT id, character_name FROM characters WHERE character_name = ?", "s", [$character_name]);
    
    if (!$character) {
        throw new Exception("Character 'Eddy Valiant' not found in database. Please create the character first.");
    }
    
    echo "  ✅ Found character: {$character['character_name']} (ID: {$character['id']})\n";
    
    // Step 3: Create character_id for assignment (transform name)
    $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character_name));
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




