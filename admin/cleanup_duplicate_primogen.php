<?php
/**
 * Cleanup Duplicate Primogen Positions
 * Removes duplicate Primogen positions and standardizes on [clan]_primogen pattern
 * with "Clan Representative" category
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$clans = ['brujah', 'gangrel', 'malkavian', 'nosferatu', 'toreador', 'tremere', 'ventrue'];
$deleted = [];
$updated = [];
$errors = [];
$kept = [];

echo "<h1>Cleanup Duplicate Primogen Positions</h1>\n";
echo "<pre>\n";

// Debug: Show what exists before cleanup
echo "Current Primogen positions in database:\n";
echo str_repeat("=", 60) . "\n";
$all_primogen = db_fetch_all($conn, "SELECT position_id, name, category FROM camarilla_positions WHERE name LIKE '%Primogen%' ORDER BY position_id");
foreach ($all_primogen as $pos) {
    $assign_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?", "s", [$pos['position_id']]);
    echo sprintf("%-30s | %-25s | %-20s | %d assignments\n", 
        $pos['position_id'], 
        $pos['name'], 
        $pos['category'],
        $assign_count['count'] ?? 0
    );
}
echo "\n";

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($conn));
    }
    
    foreach ($clans as $clan) {
        $clan_capitalized = ucfirst($clan);
        $pattern_old = "primogen_{$clan}";  // primogen_gangrel
        $pattern_new = "{$clan}_primogen";  // gangrel_primogen
        
        echo "Processing {$clan_capitalized} Primogen...\n";
        
        // Check both patterns
        $old_pos = db_fetch_one($conn, "SELECT position_id, name, category FROM camarilla_positions WHERE position_id = ?", "s", [$pattern_old]);
        $new_pos = db_fetch_one($conn, "SELECT position_id, name, category FROM camarilla_positions WHERE position_id = ?", "s", [$pattern_new]);
        
        // Check for assignments on both
        $old_assignments_result = db_fetch_one($conn, "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?", "s", [$pattern_old]);
        $new_assignments_result = db_fetch_one($conn, "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?", "s", [$pattern_new]);
        
        $old_count = (int)($old_assignments_result['count'] ?? 0);
        $new_count = (int)($new_assignments_result['count'] ?? 0);
        
        if ($old_pos && $new_pos) {
            // Both exist - need to consolidate
            echo "  Both patterns exist:\n";
            echo "    - {$pattern_old} ({$old_pos['category']}) - {$old_count} assignments\n";
            echo "    - {$pattern_new} ({$new_pos['category']}) - {$new_count} assignments\n";
            
            if ($old_count > 0 && $new_count == 0) {
                // Old pattern has assignments, new doesn't - migrate assignments to new
                echo "  Migrating {$old_count} assignment(s) from {$pattern_old} to {$pattern_new}...\n";
                $migrate_query = "UPDATE camarilla_position_assignments SET position_id = ? WHERE position_id = ?";
                $migrated = db_execute($conn, $migrate_query, "ss", [$pattern_new, $pattern_old]);
                
                if ($migrated !== false) {
                    echo "  ✅ Migrated assignments\n";
                    $updated[] = "Migrated {$old_count} assignment(s) for {$clan_capitalized} Primogen";
                } else {
                    throw new Exception("Failed to migrate assignments for {$clan_capitalized}");
                }
            } elseif ($old_count > 0 && $new_count > 0) {
                // Both have assignments - this is a problem
                echo "  ⚠️  WARNING: Both patterns have assignments! Manual review needed.\n";
                $errors[] = "{$clan_capitalized}: Both patterns have assignments ({$old_count} and {$new_count})";
                continue;
            }
            
            // Update new position to have correct category if needed
            if ($new_pos['category'] !== 'Clan Representative') {
                echo "  Updating category for {$pattern_new} to 'Clan Representative'...\n";
                $update_query = "UPDATE camarilla_positions SET category = 'Clan Representative' WHERE position_id = ?";
                $updated_cat = db_execute($conn, $update_query, "s", [$pattern_new]);
                if ($updated_cat !== false) {
                    echo "  ✅ Updated category\n";
                }
            }
            
            // Delete old pattern
            echo "  Deleting duplicate {$pattern_old}...\n";
            $delete_query = "DELETE FROM camarilla_positions WHERE position_id = ?";
            $deleted_count = db_execute($conn, $delete_query, "s", [$pattern_old]);
            
            if ($deleted_count !== false && $deleted_count >= 0) {
                if ($deleted_count > 0) {
                    echo "  ✅ Deleted {$pattern_old} ({$deleted_count} row(s))\n";
                    $deleted[] = "{$clan_capitalized} Primogen ({$pattern_old})";
                } else {
                    echo "  ⚠️  No rows deleted (may have been deleted already)\n";
                }
            } else {
                throw new Exception("Failed to delete {$pattern_old}: " . mysqli_error($conn));
            }
            
        } elseif ($old_pos && !$new_pos) {
            // Only old pattern exists - rename it
            echo "  Only {$pattern_old} exists, renaming to {$pattern_new}...\n";
            
            // First update assignments
            if ($old_count > 0) {
                echo "  Updating {$old_count} assignment(s)...\n";
                $update_assignments = db_execute($conn, "UPDATE camarilla_position_assignments SET position_id = ? WHERE position_id = ?", "ss", [$pattern_new, $pattern_old]);
                if ($update_assignments === false) {
                    throw new Exception("Failed to update assignments");
                }
            }
            
            // Rename position - can't directly UPDATE position_id if it's a primary key
            // Need to INSERT new and DELETE old
            echo "  Creating new position with correct ID...\n";
            $insert_query = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                           SELECT ?, name, 'Clan Representative', description, importance_rank 
                           FROM camarilla_positions WHERE position_id = ?";
            $inserted = db_execute($conn, $insert_query, "ss", [$pattern_new, $pattern_old]);
            
            if ($inserted !== false) {
                echo "  Deleting old position...\n";
                $delete_query = "DELETE FROM camarilla_positions WHERE position_id = ?";
                $deleted_count = db_execute($conn, $delete_query, "s", [$pattern_old]);
                
                if ($deleted_count !== false && $deleted_count > 0) {
                    echo "  ✅ Renamed to {$pattern_new}\n";
                    $updated[] = "Renamed {$clan_capitalized} Primogen from {$pattern_old} to {$pattern_new}";
                } else {
                    throw new Exception("Failed to delete old position {$pattern_old}");
                }
            } else {
                throw new Exception("Failed to create new position {$pattern_new}: " . mysqli_error($conn));
            }
            
        } elseif (!$old_pos && $new_pos) {
            // Only new pattern exists - just ensure category is correct
            echo "  Only {$pattern_new} exists\n";
            if ($new_pos['category'] !== 'Clan Representative') {
                echo "  Updating category to 'Clan Representative'...\n";
                $update_query = "UPDATE camarilla_positions SET category = 'Clan Representative' WHERE position_id = ?";
                $updated_cat = db_execute($conn, $update_query, "s", [$pattern_new]);
                if ($updated_cat !== false) {
                    echo "  ✅ Updated category\n";
                    $updated[] = "Updated category for {$clan_capitalized} Primogen";
                }
            } else {
                echo "  ✅ Already correct\n";
                $kept[] = "{$clan_capitalized} Primogen ({$pattern_new})";
            }
        } else {
            // Neither exists
            echo "  ⚠️  Neither pattern exists - will be created by add_missing_positions.php\n";
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
    
    if (!empty($deleted)) {
        echo "Deleted duplicates (" . count($deleted) . "):\n";
        foreach ($deleted as $item) {
            echo "  - $item\n";
        }
        echo "\n";
    }
    
    if (!empty($updated)) {
        echo "Updated (" . count($updated) . "):\n";
        foreach ($updated as $item) {
            echo "  - $item\n";
        }
        echo "\n";
    }
    
    if (!empty($kept)) {
        echo "Kept as-is (" . count($kept) . "):\n";
        foreach ($kept as $item) {
            echo "  - $item\n";
        }
        echo "\n";
    }
    
    if (!empty($errors)) {
        echo "⚠️  Errors/Warnings (" . count($errors) . "):\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}

    // Final verification
    echo "\n";
    echo "Final verification - Primogen positions after cleanup:\n";
    echo str_repeat("=", 60) . "\n";
    $final_primogen = db_fetch_all($conn, "SELECT position_id, name, category FROM camarilla_positions WHERE name LIKE '%Primogen%' ORDER BY position_id");
    if (empty($final_primogen)) {
        echo "No Primogen positions found!\n";
    } else {
        foreach ($final_primogen as $pos) {
            $assign_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?", "s", [$pos['position_id']]);
            echo sprintf("%-30s | %-25s | %-20s | %d assignments\n", 
                $pos['position_id'], 
                $pos['name'], 
                $pos['category'],
                $assign_count['count'] ?? 0
            );
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

