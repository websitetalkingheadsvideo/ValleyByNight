<?php
/**
 * Add Primogen Assignments Script
 * Adds Primogen assignments for:
 * - Misfortune as Malkavian Primogen
 * - Étienne Duvalier as Toreador Primogen
 * - Alistaire as Nosferatu Primogen
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

// Default night for assignments
$default_night = CAMARILLA_DEFAULT_NIGHT;

echo "<h1>Adding Primogen Assignments</h1>\n";
echo "<pre>\n";

// Optional: List all positions if requested
if (isset($_GET['list_positions'])) {
    echo "All Camarilla Positions:\n";
    echo str_repeat("=", 60) . "\n";
    $all_positions = db_fetch_all($conn, "SELECT position_id, name, category FROM camarilla_positions ORDER BY category, name");
    foreach ($all_positions as $pos) {
        echo sprintf("%-40s [%s] (ID: %s)\n", $pos['name'], $pos['category'], $pos['position_id']);
    }
    echo "\n";
    exit;
}

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    // Define assignments
    $assignments = [
        [
            'position_name' => 'Malkavian Primogen',
            'character_name' => 'Misfortune',
            'clan' => 'Malkavian'
        ],
        [
            'position_name' => 'Toreador Primogen',
            'character_name' => 'Étienne Duvalier',
            'clan' => 'Toreador'
        ],
        [
            'position_name' => 'Nosferatu Primogen',
            'character_name' => 'Alistaire',
            'clan' => 'Nosferatu'
        ]
    ];
    
    $results = [];
    
    foreach ($assignments as $assignment) {
        $position_name = $assignment['position_name'];
        $character_name = $assignment['character_name'];
        $clan = $assignment['clan'];
        
        echo "Processing: $character_name -> $position_name\n";
        
        // Find position_id - try exact match first, then partial match
        $position_query = "SELECT position_id, name FROM camarilla_positions WHERE name = ?";
        $position = db_fetch_one($conn, $position_query, "s", [$position_name]);
        
        // If exact match fails, try searching for positions containing "Primogen" and the clan name
        if (!$position) {
            echo "  Exact match not found, searching for Primogen positions...\n";
            $search_query = "SELECT position_id, name FROM camarilla_positions 
                            WHERE name LIKE ? AND name LIKE ? 
                            ORDER BY name";
            $positions = db_fetch_all($conn, $search_query, "ss", ["%Primogen%", "%$clan%"]);
            
            if (empty($positions)) {
                // Position doesn't exist - create it following the pattern: primogen_{clan_lowercase}
                echo "  Position not found, creating new position...\n";
                $position_id_new = "primogen_" . strtolower($clan);
                $category = "primogen";
                
                // Check if position_id already exists (shouldn't, but be safe)
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
            } elseif (count($positions) === 1) {
                $position = $positions[0];
                echo "  Found matching position: {$position['name']}\n";
            } else {
                echo "  Multiple matching positions found:\n";
                foreach ($positions as $p) {
                    echo "    - {$p['name']} (ID: {$p['position_id']})\n";
                }
                throw new Exception("Multiple positions match '$position_name'. Please specify exact position name.");
            }
        }
        
        $position_id = $position['position_id'];
        echo "  Using position_id: $position_id\n";
        
        // Find character in database
        $character_query = "SELECT id, character_name FROM characters WHERE character_name = ?";
        $character = db_fetch_one($conn, $character_query, "s", [$character_name]);
        
        if (!$character) {
            throw new Exception("Character not found in database: $character_name");
        }
        
        $db_character_name = $character['character_name'];
        echo "  Found character: $db_character_name (ID: {$character['id']})\n";
        
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
                    $results[] = [
                        'character' => $character_name,
                        'position' => $position_name,
                        'status' => 'updated',
                        'assignment_id' => $existing['id']
                    ];
                } else {
                    throw new Exception("Failed to update assignment for $character_name");
                }
            } else {
                echo "  ✅ Assignment already active, no changes needed\n";
                $results[] = [
                    'character' => $character_name,
                    'position' => $position_name,
                    'status' => 'already_exists'
                ];
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
            $results[] = [
                'character' => $character_name,
                'position' => $position_name,
                'status' => 'created',
                'assignment_id' => $assignment_id
            ];
        }
        
        echo "\n";
    }
    
    // Commit transaction
    if (!db_commit($conn)) {
        throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
    }
    
    echo "✅ Transaction committed successfully!\n\n";
    echo "Summary:\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($results as $result) {
        echo sprintf("%-25s -> %-25s [%s]\n", 
            $result['character'], 
            $result['position'], 
            $result['status']
        );
    }
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}

echo "</pre>\n";
mysqli_close($conn);
?>

