<?php
/**
 * Assign Helena Crowly as Tremere Primogen
 * 
 * Assigns Helena Crowly to the Tremere Primogen position in the Camarilla Positions system.
 * 
 * Usage:
 *   CLI: php database/assign_helena_crowly_primogen.php
 *   Web: database/assign_helena_crowly_primogen.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Assign Helena Crowly Primogen</title></head><body>";
    echo "<h1>Assign Helena Crowly as Tremere Primogen</h1>";
    echo "<pre>";
} else {
    echo "Assign Helena Crowly as Tremere Primogen\n";
    echo "=========================================\n\n";
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Default night for assignments
$default_night = CAMARILLA_DEFAULT_NIGHT;

$character_name = 'Helena Crowly';
$position_name = 'Tremere Primogen';
$clan = 'Tremere';

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    echo "Processing: $character_name\n";
    echo str_repeat("-", 60) . "\n";
    
    // Step 1: Find character in database
    echo "Step 1: Finding character in database...\n";
    
    $character_query = "SELECT id, character_name, clan FROM characters WHERE character_name = ?";
    $character = db_fetch_one($conn, $character_query, "s", [$character_name]);
    
    if (!$character) {
        throw new Exception("Character not found in database: $character_name");
    }
    
    $character_id = $character['id'];
    $db_character_name = $character['character_name'];
    $db_clan = $character['clan'];
    
    echo "  ✅ Character found: $db_character_name (ID: $character_id, Clan: $db_clan)\n";
    
    // Verify clan matches
    if (strtolower($db_clan) !== strtolower($clan)) {
        throw new Exception("Character clan mismatch: Expected $clan, found $db_clan");
    }
    
    // Step 2: Find or create Tremere Primogen position
    echo "\nStep 2: Finding or creating Tremere Primogen position...\n";
    
    $position_query = "SELECT position_id, name FROM camarilla_positions WHERE name = ?";
    $position = db_fetch_one($conn, $position_query, "s", [$position_name]);
    
    if (!$position) {
        echo "  Position not found, creating new position...\n";
        
        // Create position following pattern: primogen_{clan_lowercase}
        $position_id_new = "primogen_" . strtolower($clan);
        $category = "primogen";
        
        // Check if position_id already exists
        $check_id = db_fetch_one($conn, "SELECT position_id FROM camarilla_positions WHERE position_id = ?", "s", [$position_id_new]);
        if ($check_id) {
            throw new Exception("Position ID '$position_id_new' already exists with different name. Please check database.");
        }
        
        // Create the position
        $create_query = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                        VALUES (?, ?, ?, ?, ?)";
        $description = "$clan Primogen position in the Camarilla court of Phoenix.";
        $importance_rank = 3; // Primogen are important but not as high as Prince/Seneschal
        
        $created = db_execute($conn, $create_query, "ssssi", [
            $position_id_new,
            $position_name,
            $category,
            $description,
            $importance_rank
        ]);
        
        if ($created === false) {
            throw new Exception("Failed to create position '$position_name': " . mysqli_error($conn));
        }
        
        echo "  ✅ Created position: $position_name (ID: $position_id_new)\n";
        $position = [
            'position_id' => $position_id_new,
            'name' => $position_name
        ];
    } else {
        echo "  ✅ Found existing position: {$position['name']} (ID: {$position['position_id']})\n";
    }
    
    $position_id = $position['position_id'];
    
    // Step 3: Assign character to position
    echo "\nStep 3: Assigning character to position...\n";
    
    // Format character_id for assignment table (UPPER with underscores)
    $assignment_character_id = strtoupper(str_replace(' ', '_', $db_character_name));
    echo "  Assignment character_id format: $assignment_character_id\n";
    
    // Check if assignment already exists
    $check_query = "SELECT id, start_night, end_night, is_acting 
                   FROM camarilla_position_assignments 
                   WHERE position_id = ? AND character_id = ? 
                   AND (end_night IS NULL OR end_night >= ?)";
    $existing = db_fetch_one($conn, $check_query, "sss", [$position_id, $assignment_character_id, $default_night]);
    
    if ($existing) {
        echo "  ⚠️  Assignment already exists (ID: {$existing['id']})\n";
        echo "     Start: {$existing['start_night']}, End: " . ($existing['end_night'] ?? 'NULL') . ", Acting: {$existing['is_acting']}\n";
        
        // Update existing assignment if needed
        if ($existing['end_night'] !== null || $existing['is_acting'] != 0) {
            echo "  Updating existing assignment...\n";
            $update_query = "UPDATE camarilla_position_assignments 
                            SET start_night = ?, end_night = NULL, is_acting = 0 
                            WHERE id = ?";
            $updated = db_execute($conn, $update_query, "si", [$default_night, $existing['id']]);
            
            if ($updated !== false) {
                echo "  ✅ Updated existing assignment\n";
                $assignment_id = $existing['id'];
                $status = 'updated';
            } else {
                throw new Exception("Failed to update assignment for $character_name");
            }
        } else {
            echo "  ✅ Assignment already active, no changes needed\n";
            $assignment_id = $existing['id'];
            $status = 'already_exists';
        }
    } else {
        // Create new assignment
        echo "  Creating new assignment...\n";
        $insert_query = "INSERT INTO camarilla_position_assignments 
                       (position_id, character_id, start_night, end_night, is_acting) 
                       VALUES (?, ?, ?, NULL, 0)";
        $assignment_id = db_execute($conn, $insert_query, "sss", [
            $position_id,
            $assignment_character_id,
            $default_night
        ]);
        
        if ($assignment_id === false) {
            throw new Exception("Failed to create assignment for $character_name: " . mysqli_error($conn));
        }
        
        echo "  ✅ Created assignment (ID: $assignment_id)\n";
        $status = 'created';
    }
    
    // Commit transaction
    if (!db_commit($conn)) {
        throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
    }
    
    echo "\n✅ Transaction committed successfully!\n\n";
    echo "=== Summary ===\n";
    echo str_repeat("=", 60) . "\n";
    echo sprintf("%-30s -> %-25s [%s]\n", 
        $character_name, 
        $position_name, 
        $status
    );
    echo "\n✅ Helena Crowly assigned as Tremere Primogen successfully!\n";
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);
?>

