<?php
/**
 * Cleanup Duplicate Positions
 * Finds and removes duplicate positions in the camarilla_positions table
 * Consolidates assignments and keeps the best version of each position
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

echo "<h1>Cleanup Duplicate Positions</h1>\n";
echo "<pre>\n";

// Step 1: Find all duplicates by name
echo "Step 1: Finding duplicate positions by name...\n";
echo str_repeat("=", 60) . "\n";

$duplicates_query = "SELECT name, COUNT(*) as count, GROUP_CONCAT(position_id ORDER BY position_id) as position_ids
                     FROM camarilla_positions 
                     GROUP BY name 
                     HAVING COUNT(*) > 1
                     ORDER BY name";

$duplicate_groups = db_fetch_all($conn, $duplicates_query);

if (empty($duplicate_groups)) {
    echo "✅ No duplicates found by name!\n";
    echo "</pre>\n";
    echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
    exit;
}

echo "Found " . count($duplicate_groups) . " position(s) with duplicates:\n\n";

$to_delete = [];
$to_keep = [];

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    foreach ($duplicate_groups as $group) {
        $name = $group['name'];
        $position_ids = explode(',', $group['position_ids']);
        $count = count($position_ids);
        
        echo "Processing: {$name} ({$count} duplicates)\n";
        echo "  Position IDs: " . implode(', ', $position_ids) . "\n";
        
        // Get full details for each duplicate
        $positions = [];
        foreach ($position_ids as $pos_id) {
            $pos = db_fetch_one($conn, "SELECT * FROM camarilla_positions WHERE position_id = ?", "s", [$pos_id]);
            if ($pos) {
                $assign_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?", "s", [$pos_id]);
                $pos['assignment_count'] = (int)($assign_count['count'] ?? 0);
                $positions[] = $pos;
            }
        }
        
        // Sort by: category quality first, then naming pattern, then assignments, then importance_rank
        usort($positions, function($a, $b) {
            // First: prefer "Clan Representative" over "primogen" category (HIGHEST PRIORITY)
            $cat_priority = ['Clan Representative' => 10, 'Leadership' => 8, 'Law Enforcement' => 7, 'Social' => 6, 'Support' => 5, 'primogen' => 1];
            $a_cat = $cat_priority[$a['category']] ?? 3;
            $b_cat = $cat_priority[$b['category']] ?? 3;
            if ($a_cat != $b_cat) return $b_cat - $a_cat;
            
            // Second: prefer [clan]_primogen over primogen_[clan] pattern
            $a_is_clan_primogen = (strpos($a['position_id'], '_primogen') !== false && strpos($a['position_id'], 'primogen_') !== 0);
            $b_is_clan_primogen = (strpos($b['position_id'], '_primogen') !== false && strpos($b['position_id'], 'primogen_') !== 0);
            $a_is_primogen_clan = (strpos($a['position_id'], 'primogen_') === 0);
            $b_is_primogen_clan = (strpos($b['position_id'], 'primogen_') === 0);
            
            if ($a_is_clan_primogen && $b_is_primogen_clan) return -1;
            if ($b_is_clan_primogen && $a_is_primogen_clan) return 1;
            
            // Third: prefer lower importance_rank (more important)
            if ($a['importance_rank'] != $b['importance_rank']) {
                return ($a['importance_rank'] ?? 999) - ($b['importance_rank'] ?? 999);
            }
            
            // Fourth: prefer positions with assignments (but only as tiebreaker)
            if ($a['assignment_count'] > 0 && $b['assignment_count'] == 0) return -1;
            if ($a['assignment_count'] == 0 && $b['assignment_count'] > 0) return 1;
            
            // Finally: prefer shorter position_id (cleaner)
            return strlen($a['position_id']) - strlen($b['position_id']);
        });
        
        $keep_position = $positions[0];
        $delete_positions = array_slice($positions, 1);
        
        echo "  ✅ Keeping: {$keep_position['position_id']} (category: {$keep_position['category']}, assignments: {$keep_position['assignment_count']}, rank: " . ($keep_position['importance_rank'] ?? 'NULL') . ")\n";
        $to_keep[] = $keep_position;
        
        // Migrate assignments and delete duplicates
        foreach ($delete_positions as $delete_pos) {
            echo "  🗑️  Deleting: {$delete_pos['position_id']} (category: {$delete_pos['category']}, assignments: {$delete_pos['assignment_count']})\n";
            
            // Migrate assignments if any
            if ($delete_pos['assignment_count'] > 0) {
                echo "    Migrating {$delete_pos['assignment_count']} assignment(s) to {$keep_position['position_id']}...\n";
                $migrate_query = "UPDATE camarilla_position_assignments SET position_id = ? WHERE position_id = ?";
                $migrated = db_execute($conn, $migrate_query, "ss", [$keep_position['position_id'], $delete_pos['position_id']]);
                
                if ($migrated !== false && $migrated >= 0) {
                    echo "    ✅ Migrated {$migrated} assignment(s)\n";
                } else {
                    echo "    ⚠️  Migration returned: " . var_export($migrated, true) . "\n";
                }
            }
            
            // Update the keep position if needed (better category, description, etc.)
            $updates = [];
            if (empty($keep_position['category']) && !empty($delete_pos['category'])) {
                $updates[] = "category = '{$delete_pos['category']}'";
            }
            if (empty($keep_position['description']) && !empty($delete_pos['description'])) {
                $desc = mysqli_real_escape_string($conn, $delete_pos['description']);
                $updates[] = "description = '{$desc}'";
            }
            if (($keep_position['importance_rank'] ?? 999) > ($delete_pos['importance_rank'] ?? 999)) {
                $updates[] = "importance_rank = " . ($delete_pos['importance_rank'] ?? 'NULL');
            }
            
            if (!empty($updates)) {
                $update_query = "UPDATE camarilla_positions SET " . implode(', ', $updates) . " WHERE position_id = ?";
                db_execute($conn, $update_query, "s", [$keep_position['position_id']]);
                echo "    ✅ Updated keep position with better data\n";
            }
            
            // Delete the duplicate
            $delete_query = "DELETE FROM camarilla_positions WHERE position_id = ?";
            $deleted = db_execute($conn, $delete_query, "s", [$delete_pos['position_id']]);
            
            if ($deleted !== false && $deleted > 0) {
                echo "    ✅ Deleted\n";
                $to_delete[] = $delete_pos;
            } else {
                echo "    ⚠️  Delete returned: " . var_export($deleted, true) . " - " . mysqli_error($conn) . "\n";
            }
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
    echo "Kept: " . count($to_keep) . " position(s)\n";
    echo "Deleted: " . count($to_delete) . " duplicate(s)\n\n";
    
    if (!empty($to_keep)) {
        echo "Positions kept:\n";
        foreach ($to_keep as $pos) {
            echo "  - {$pos['position_id']}: {$pos['name']} [{$pos['category']}]\n";
        }
        echo "\n";
    }
    
    if (!empty($to_delete)) {
        echo "Duplicates deleted:\n";
        foreach ($to_delete as $pos) {
            echo "  - {$pos['position_id']}: {$pos['name']} [{$pos['category']}]\n";
        }
        echo "\n";
    }
    
    // Final verification
    echo "Final verification - Checking for remaining duplicates...\n";
    echo str_repeat("=", 60) . "\n";
    $remaining_duplicates = db_fetch_all($conn, $duplicates_query);
    if (empty($remaining_duplicates)) {
        echo "✅ No duplicates remaining!\n";
    } else {
        echo "⚠️  Still found " . count($remaining_duplicates) . " duplicate group(s):\n";
        foreach ($remaining_duplicates as $dup) {
            echo "  - {$dup['name']}: {$dup['position_ids']}\n";
        }
    }
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "</pre>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>

