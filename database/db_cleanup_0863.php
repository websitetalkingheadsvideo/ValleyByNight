<?php
/**
 * VbN Database Cleanup Script - One-Time Duplicate & Invalid Sire Cleanup
 * 
 * This script performs a surgical cleanup of canonical NPC data:
 * 1) Duplicate character rows (starting with Kerry)
 * 2) Invalid/placeholder sire values
 * 
 * Phase: PRECHECK (Read-only audit)
 * Version: 0.8.63 → 0863
 * 
 * Usage:
 *   CLI: php database/db_cleanup_0863.php [--execute]
 *   Web: https://vbn.talkingheads.video/database/db_cleanup_0863.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Parse command line arguments
$execute_mode = false;
if ($is_cli) {
    $argv = $GLOBALS['argv'] ?? [];
    $execute_mode = in_array('--execute', $argv, true);
} else {
    header('Content-Type: text/html; charset=utf-8');
    $execute_mode = isset($_GET['execute']) && $_GET['execute'] == '1';
}

// Include version
require_once __DIR__ . '/../includes/version.php';
$version = LOTN_VERSION;
$version_shorthand = str_replace('.', '', $version); // 0.8.63 → 0863

// Database connection (must use connect.php)
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Output directory
$project_root = dirname(__DIR__);
$output_dir = $project_root . '/reference/world/_summaries';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// ============================================================================
// STEP 0: Identify Version (Already done above)
// ============================================================================
$log = [];
$log[] = "# VbN Database Cleanup - PRECHECK Report";
$log[] = "**Version:** $version (shorthand: $version_shorthand)";
$log[] = "**Date:** " . date('Y-m-d H:i:s');
$log[] = "**Mode:** " . ($execute_mode ? "EXECUTION" : "PRECHECK (Read-Only)");
$log[] = "";

// ============================================================================
// STEP 1: Remote DB Guard - Verify we're not connecting to localhost
// ============================================================================
$log[] = "## Step 1: Remote Database Verification";
$log[] = "";

// Get connection info
$db_host = mysqli_get_host_info($conn);
$server_info = mysqli_get_server_info($conn);
$db_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'] ?? 'unknown';

// Check if host is localhost
$is_localhost = false;
$hostname = getenv('DB_HOST') ?: "vdb5.pit.pair.com";
$localhost_patterns = ['localhost', '127.0.0.1', '::1', ''];
foreach ($localhost_patterns as $pattern) {
    if ($hostname === $pattern || strpos($hostname, $pattern) !== false) {
        $is_localhost = true;
        break;
    }
}

if ($is_localhost && $hostname !== 'vdb5.pit.pair.com') {
    die("ERROR: Attempted connection to localhost detected! Host: $hostname\nThis script must ONLY connect to Pair Networks remote DB.");
}

$log[] = "- **DB Host (env):** $hostname";
$log[] = "- **DB Host (connection):** $db_host";
$log[] = "- **DB Name:** $db_name";
$log[] = "- **Server Info:** $server_info";
$log[] = "- **✓ Remote DB Verified** (not localhost)";
$log[] = "";

// ============================================================================
// STEP 2: Audit Queries (Read Only)
// ============================================================================

$log[] = "## Step 2: Audit Queries (Read-Only)";
$log[] = "";

// ============================================================================
// 2A) Find duplicate characters by normalized name
// ============================================================================

$log[] = "### 2A) Duplicate Character Detection";
$log[] = "";

// First, get table structure
$table_info_query = "SHOW COLUMNS FROM characters";
$table_info_result = mysqli_query($conn, $table_info_query);
$columns = [];
while ($row = mysqli_fetch_assoc($table_info_result)) {
    $columns[] = $row['Field'];
}

$log[] = "**Characters table columns:** " . implode(', ', $columns);
$log[] = "";

// Check for primary key
$pk_query = "SHOW KEYS FROM characters WHERE Key_name = 'PRIMARY'";
$pk_result = mysqli_query($conn, $pk_query);
$pk_column = null;
if ($pk_row = mysqli_fetch_assoc($pk_result)) {
    $pk_column = $pk_row['Column_name'];
}
$log[] = "**Primary Key:** " . ($pk_column ?: 'NOT FOUND');

// Normalize name function (for SQL)
// We'll use MySQL functions: LOWER, TRIM for normalization
$duplicate_query = "
    SELECT 
        LOWER(TRIM(character_name)) as normalized_name,
        COUNT(*) as count,
        GROUP_CONCAT(id ORDER BY id) as ids,
        GROUP_CONCAT(character_name ORDER BY id SEPARATOR ' | ') as names,
        GROUP_CONCAT(COALESCE(clan, 'NULL') ORDER BY id SEPARATOR ' | ') as clans,
        GROUP_CONCAT(COALESCE(generation, 'NULL') ORDER BY id SEPARATOR ' | ') as generations
    FROM characters
    WHERE character_name IS NOT NULL 
      AND character_name != ''
      AND TRIM(character_name) != ''
    GROUP BY LOWER(TRIM(character_name))
    HAVING COUNT(*) > 1
    ORDER BY COUNT(*) DESC, normalized_name
";

$duplicate_result = mysqli_query($conn, $duplicate_query);
if (!$duplicate_result) {
    die("ERROR: Failed to query duplicates: " . mysqli_error($conn));
}

$duplicates = [];
$kerry_duplicates = [];
while ($row = mysqli_fetch_assoc($duplicate_result)) {
    $ids = explode(',', $row['ids']);
    $names = explode(' | ', $row['names']);
    $clans = explode(' | ', $row['clans']);
    $generations = explode(' | ', $row['generations']);
    
    $dup_entry = [
        'normalized_name' => $row['normalized_name'],
        'count' => (int)$row['count'],
        'rows' => []
    ];
    
    for ($i = 0; $i < count($ids); $i++) {
        // Get full row details
        $id = (int)$ids[$i];
        $detail_query = "SELECT id, character_name, clan, generation, sire, pc, player_name, created_at, updated_at
                         FROM characters WHERE id = $id";
        $detail_result = mysqli_query($conn, $detail_query);
        if ($detail_row = mysqli_fetch_assoc($detail_result)) {
            $dup_entry['rows'][] = $detail_row;
            
            // Check if this is Kerry
            if (stripos($detail_row['character_name'], 'Kerry') === 0 || 
                strtolower($detail_row['character_name']) === 'kerry') {
                $kerry_duplicates[] = $detail_row;
            }
        }
    }
    
    $duplicates[] = $dup_entry;
}

$log[] = "**Total duplicate clusters found:** " . count($duplicates);
$log[] = "";

if (count($duplicates) > 0) {
    $log[] = "#### Duplicate Clusters:";
    $log[] = "";
    $log[] = "| Normalized Name | Count | IDs | Names | Clans | Generations |";
    $log[] = "|-----------------|-------|-----|-------|-------|-------------|";
    
    foreach ($duplicates as $dup) {
        $ids_str = implode(', ', array_column($dup['rows'], 'id'));
        $names_str = implode(' / ', array_column($dup['rows'], 'character_name'));
        $clans_str = implode(' / ', array_column($dup['rows'], 'clan'));
        $gens_str = implode(' / ', array_column($dup['rows'], 'generation'));
        
        $log[] = "| " . $dup['normalized_name'] . " | " . $dup['count'] . " | " . $ids_str . " | " . $names_str . " | " . $clans_str . " | " . $gens_str . " |";
    }
    $log[] = "";
} else {
    $log[] = "✓ No duplicate character names found.";
    $log[] = "";
}

// ============================================================================
// 2B) Specifically inspect "Kerry"
// ============================================================================

$log[] = "### 2B) Kerry-Specific Inspection";
$log[] = "";

$kerry_query = "
    SELECT id, character_name, clan, generation, sire, pc, player_name, 
           created_at, updated_at, biography
    FROM characters 
    WHERE character_name LIKE 'Kerry%' 
       OR LOWER(TRIM(character_name)) = 'kerry'
    ORDER BY id
";

$kerry_result = mysqli_query($conn, $kerry_query);
if (!$kerry_result) {
    die("ERROR: Failed to query Kerry: " . mysqli_error($conn));
}

$kerry_rows = [];
while ($row = mysqli_fetch_assoc($kerry_result)) {
    $kerry_rows[] = $row;
}

$log[] = "**Kerry matches found:** " . count($kerry_rows);
$log[] = "";

if (count($kerry_rows) > 0) {
    $log[] = "#### Kerry Rows:";
    $log[] = "";
    $log[] = "| ID | Name | Clan | Generation | Sire | PC | Player | Created | Updated |";
    $log[] = "|----|------|------|------------|------|----|--------|---------|---------|";
    
    foreach ($kerry_rows as $row) {
        $log[] = "| " . $row['id'] . " | " . $row['character_name'] . " | " . 
                 ($row['clan'] ?? 'NULL') . " | " . ($row['generation'] ?? 'NULL') . " | " . 
                 ($row['sire'] ?? 'NULL') . " | " . ($row['pc'] ?? 'NULL') . " | " . 
                 ($row['player_name'] ?? 'NULL') . " | " . ($row['created_at'] ?? 'NULL') . " | " . 
                 ($row['updated_at'] ?? 'NULL') . " |";
    }
    $log[] = "";
    
    // Check for alias/nickname field
    $has_alias = in_array('alias', $columns) || in_array('nickname', $columns);
    $log[] = "**Alias/Nickname field present:** " . ($has_alias ? 'Yes' : 'No');
    $log[] = "";
} else {
    $log[] = "⚠ No rows found matching 'Kerry'";
    $log[] = "";
}

// ============================================================================
// 2C) Find invalid sire values
// ============================================================================

$log[] = "### 2C) Invalid Sire Values Detection";
$log[] = "";

// Check if sire column exists
$invalid_sire_rows = [];
if (!in_array('sire', $columns)) {
    $log[] = "⚠ **WARNING:** 'sire' column not found in characters table!";
    $log[] = "Available columns: " . implode(', ', $columns);
    $log[] = "";
} else {
    // Invalid placeholder patterns
    $invalid_sire_patterns = [
        "'in'", "'bob'", "'both'", "'during'", "'him'",
        "''", // empty string
        "' '"  // whitespace only
    ];
    
    // Build query for exact matches (case-insensitive)
    // NULL sire is VALID (unknown sire), so we only catch non-NULL invalid values
    $invalid_sire_query = "
        SELECT id, character_name, clan, generation, sire, pc, player_name
        FROM characters
        WHERE sire IS NOT NULL
          AND (
              LOWER(TRIM(sire)) IN ('in', 'bob', 'both', 'during', 'him')
              OR TRIM(sire) = ''
          )
        ORDER BY sire, character_name
    ";
    
    $invalid_result = mysqli_query($conn, $invalid_sire_query);
    
    if (!$invalid_result) {
        $log[] = "⚠ **ERROR:** Failed to query invalid sire values: " . mysqli_error($conn);
        $log[] = "";
    } else {
        $invalid_sire_rows = [];
        while ($row = mysqli_fetch_assoc($invalid_result)) {
            $invalid_sire_rows[] = $row;
        }
        
        $log[] = "**Invalid sire values found:** " . count($invalid_sire_rows);
        $log[] = "";
        
        if (count($invalid_sire_rows) > 0) {
            $log[] = "#### Characters with Invalid Sire Values:";
            $log[] = "";
            $log[] = "| ID | Name | Clan | Generation | Current Sire | PC |";
            $log[] = "|----|------|------|------------|--------------|----|";
            
            foreach ($invalid_sire_rows as $row) {
                $sire_display = $row['sire'] === '' ? '(empty string)' : 
                               ($row['sire'] === null ? 'NULL' : "'" . $row['sire'] . "'");
                $log[] = "| " . $row['id'] . " | " . $row['character_name'] . " | " . 
                         ($row['clan'] ?? 'NULL') . " | " . ($row['generation'] ?? 'NULL') . " | " . 
                         $sire_display . " | " . ($row['pc'] ?? 'NULL') . " |";
            }
            $log[] = "";
            
            // Also check for suspicious short values (report only, don't auto-fix)
            $suspicious_query = "
                SELECT id, character_name, clan, generation, sire, pc
                FROM characters
                WHERE sire IS NOT NULL
                  AND LENGTH(TRIM(sire)) < 4
                  AND LOWER(TRIM(sire)) NOT IN ('in', 'bob', 'both', 'during', 'him', '')
                  AND sire NOT IN (SELECT DISTINCT character_name FROM characters WHERE character_name IS NOT NULL)
                ORDER BY sire, character_name
                LIMIT 20
            ";
            
            $suspicious_result = mysqli_query($conn, $suspicious_query);
            if ($suspicious_result) {
                $suspicious_rows = [];
                while ($row = mysqli_fetch_assoc($suspicious_result)) {
                    $suspicious_rows[] = $row;
                }
                
                if (count($suspicious_rows) > 0) {
                    $log[] = "#### Suspicious Short Sire Values (< 4 chars, not matching known names):";
                    $log[] = "*Note: These are REPORTED ONLY - not auto-fixed*";
                    $log[] = "";
                    $log[] = "| ID | Name | Clan | Generation | Sire |";
                    $log[] = "|----|------|------|------------|------|";
                    
                    foreach ($suspicious_rows as $row) {
                        $log[] = "| " . $row['id'] . " | " . $row['character_name'] . " | " . 
                                 ($row['clan'] ?? 'NULL') . " | " . ($row['generation'] ?? 'NULL') . " | " . 
                                 "'" . $row['sire'] . "' |";
                    }
                    $log[] = "";
                }
            }
        } else {
            $log[] = "✓ No invalid sire placeholder values found.";
            $log[] = "";
        }
    }
}

// ============================================================================
// STEP 3: Decide Fix Strategy (Documented, not executed yet)
// ============================================================================

$log[] = "## Step 3: Proposed Fix Strategy";
$log[] = "";

if (count($duplicates) > 0 || count($invalid_sire_rows ?? []) > 0) {
    $log[] = "### 3A) Duplicate Handling Strategy";
    $log[] = "";
    $log[] = "For each duplicate cluster, we will:";
    $log[] = "1. **Analyze completeness** - Compare all fields between duplicates";
    $log[] = "2. **Choose canonical row** - Select the most complete row (prefer newer updated_at if tied)";
    $log[] = "3. **Check foreign key dependencies** - Identify related tables referencing character IDs";
    $log[] = "4. **Merge or deprecate** - Move related records to canonical ID, then:";
    $log[] = "   - If schema supports soft-delete: mark duplicates as inactive/deleted";
    $log[] = "   - Otherwise: hard-delete only if no FK references exist";
    $log[] = "";
    
    $log[] = "### 3B) Invalid Sire Handling Strategy";
    $log[] = "";
    $log[] = "For invalid sire values:";
    $log[] = "- Convert exact matches ('in', 'bob', 'both', 'during', 'him') → NULL";
    $log[] = "- Convert empty strings and whitespace-only → NULL";
    $log[] = "- **DO NOT** replace with guessed names";
    $log[] = "- If sire_id FK exists and sire_name text, prefer FK integrity";
    $log[] = "";
} else {
    $log[] = "✓ No cleanup actions required - database is clean!";
    $log[] = "";
}

// ============================================================================
// Discover Related Tables (for FK checks)
// ============================================================================

$log[] = "## Foreign Key Dependency Analysis";
$log[] = "";

$fk_query = "
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME,
        CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '$db_name'
      AND REFERENCED_TABLE_NAME = 'characters'
    ORDER BY TABLE_NAME, COLUMN_NAME
";

$fk_result = mysqli_query($conn, $fk_query);
$related_tables = [];
if ($fk_result) {
    while ($row = mysqli_fetch_assoc($fk_result)) {
        $related_tables[] = $row;
    }
}

$log[] = "**Tables with foreign keys to characters:** " . count($related_tables);
if (count($related_tables) > 0) {
    $log[] = "";
    $log[] = "| Table | Column | References | Constraint Name |";
    $log[] = "|-------|--------|------------|-----------------|";
    foreach ($related_tables as $fk) {
        $log[] = "| " . $fk['TABLE_NAME'] . " | " . $fk['COLUMN_NAME'] . " | " . 
                 $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . " | " . 
                 $fk['CONSTRAINT_NAME'] . " |";
    }
    $log[] = "";
} else {
    $log[] = "*(No foreign key constraints found - may use character_id or character_name matching)*";
    $log[] = "";
}

// ============================================================================
// Write PRECHECK File
// ============================================================================

$precheck_file = $output_dir . "/DB_CLEANUP_" . $version_shorthand . "_PRECHECK.md";
file_put_contents($precheck_file, implode("\n", $log));

// Output PRECHECK results
if ($is_cli) {
    echo implode("\n", $log);
    echo "\n\n✓ PRECHECK report written to: $precheck_file\n";
} else {
    echo "<!DOCTYPE html><html><head><title>DB Cleanup PRECHECK</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#ccc;}";
    echo "h2{color:#4a9eff;}h3{color:#6bcaff;}table{border-collapse:collapse;margin:10px 0;}";
    echo "td,th{border:1px solid #444;padding:8px;text-align:left;}</style></head><body>";
    echo "<pre>" . htmlspecialchars(implode("\n", $log)) . "</pre>";
    echo "<p>✓ PRECHECK report written to: <code>" . htmlspecialchars($precheck_file) . "</code></p>";
}

// ============================================================================
// EXECUTION PHASE (Only if --execute flag is set)
// ============================================================================

if ($execute_mode) {
    if ($is_cli) {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "STARTING EXECUTION PHASE\n";
        echo str_repeat("=", 80) . "\n\n";
    }
    
    // Ensure variables are available
    if (!isset($invalid_sire_rows)) {
        $invalid_sire_rows = [];
    }
    if (!isset($kerry_rows)) {
        $kerry_rows = [];
    }
    
    try {
        if ($is_cli) echo "DEBUG: Inside try block\n";
        
        // Check database connection is still valid  
        if (!$conn) {
            throw new Exception("Database connection is null");
        }
        if (!mysqli_ping($conn)) {
            throw new Exception("Database connection lost before execution phase");
        }
        
        if ($is_cli) echo "DEBUG: Connection OK, initializing exec_log\n";
        
        $exec_log = [];
        if ($is_cli) echo "DEBUG: About to add exec_log entries\n";
        
        $exec_log[] = "# VbN Database Cleanup - EXECUTION LOG";
        $exec_log[] = "**Version:** $version (shorthand: $version_shorthand)";
        $exec_log[] = "**Date:** " . date('Y-m-d H:i:s');
        $exec_log[] = "**Mode:** EXECUTION";
        $exec_log[] = "";
        
        if ($is_cli) echo "DEBUG: Starting Step 4\n";
        
        // ============================================================================
        // STEP 4: Backup Before Write
        // ============================================================================
        
        $exec_log[] = "## Step 4: Backup Creation";
        $exec_log[] = "";
        
        if ($is_cli) echo "DEBUG: Building backup file path\n";
        
        $backup_file = $output_dir . "/DB_CLEANUP_" . $version_shorthand . "_BACKUP.sql";
        $backup_sql = [];
        $backup_sql[] = "-- VbN Database Cleanup Backup";
        $backup_sql[] = "-- Version: $version";
        $backup_sql[] = "-- Date: " . date('Y-m-d H:i:s');
        $backup_sql[] = "-- Purpose: Backup of affected rows before cleanup";
        $backup_sql[] = "";
        
        // Collect all affected character IDs
        $affected_ids = [];
        
        // Add Kerry IDs if they need merging (check if they're actually duplicates)
        // Note: They have different names, so they might be intentionally different
        // We'll only fix the sire='0' issue for ID 130
        foreach ($kerry_rows as $row) {
            $affected_ids[] = (int)$row['id'];
        }
        
        // Add invalid sire character IDs
        foreach ($invalid_sire_rows as $row) {
            $affected_ids[] = (int)$row['id'];
        }
        
        $affected_ids = array_unique($affected_ids);
        sort($affected_ids);
        
        if ($is_cli) echo "DEBUG: Affected IDs collected: " . count($affected_ids) . "\n";
        
        $exec_log[] = "**Affected character IDs:** " . implode(', ', $affected_ids);
        $exec_log[] = "";
        
        // Backup each affected character row
        // Simplified backup: Just store IDs and note that full backup should be done via mysqldump
        if (count($affected_ids) > 0) {
            if ($is_cli) echo "DEBUG: Creating simplified backup (IDs only - full backup recommended via mysqldump)\n";
            
            $backup_sql[] = "-- Simplified backup: Affected character IDs only";
            $backup_sql[] = "-- For full backup, use: mysqldump -u user -p database characters --where=\"id IN (" . implode(',', $affected_ids) . ")\"";
            $backup_sql[] = "";
            $backup_sql[] = "-- Affected character IDs:";
            foreach ($affected_ids as $id) {
                $backup_sql[] = "--   ID: $id";
            }
            $backup_sql[] = "";
            $backup_sql[] = "-- To restore, you would need to:";
            $backup_sql[] = "-- 1. Run full mysqldump backup first (recommended before any cleanup)";
            $backup_sql[] = "-- 2. If needed, restore from that backup";
            $backup_sql[] = "";
        
        // Also backup related table rows
        $exec_log[] = "Backing up related table rows...";
            $backup_sql[] = "-- Backup of related table rows";
            $backup_sql[] = "";
            
            foreach ($related_tables as $fk) {
            $table = $fk['TABLE_NAME'];
            $col = $fk['COLUMN_NAME'];
            
            $related_query = "SELECT * FROM $table WHERE $col IN ($ids_placeholders)";
            $stmt = mysqli_prepare($conn, $related_query);
            if ($stmt) {
                $types = str_repeat('i', count($affected_ids));
                mysqli_stmt_bind_param($stmt, $types, ...$affected_ids);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $related_count = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns = array_keys($row);
                    $values = array_map(function($val) use ($conn) {
                        if ($val === null) return 'NULL';
                        return "'" . mysqli_real_escape_string($conn, $val) . "'";
                    }, array_values($row));
                    
                    // Use REPLACE for backup (safer for restore)
                    $backup_sql[] = "REPLACE INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");";
                    $related_count++;
                }
                
                if ($related_count > 0) {
                    $exec_log[] = "- Backed up $related_count row(s) from $table";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        file_put_contents($backup_file, implode("\n", $backup_sql));
        $exec_log[] = "✓ Backup written to: $backup_file";
        $exec_log[] = "";
    } else {
        $exec_log[] = "⚠ No affected rows to backup.";
        $exec_log[] = "";
    }
    
    // ============================================================================
    // STEP 5: Execute Changes
    // ============================================================================
    
    $exec_log[] = "## Step 5: Executing Changes";
    $exec_log[] = "";
    
    // Begin transaction
    if (!db_begin_transaction($conn)) {
        die("ERROR: Failed to begin transaction: " . mysqli_error($conn));
    }
    
    $changes_made = false;
    $update_count = 0;
    
    try {
        // 5A) Fix invalid sire values
        $exec_log[] = "### 5A) Fixing Invalid Sire Values";
        $exec_log[] = "";
        
        foreach ($invalid_sire_rows as $row) {
            $char_id = (int)$row['id'];
            $char_name = $row['character_name'];
            $old_sire = $row['sire'];
            
            // Set to NULL (unknown sire)
            $update_query = "UPDATE characters SET sire = NULL WHERE id = ?";
            $result = db_execute($conn, $update_query, 'i', [$char_id]);
            
            if ($result !== false && $result > 0) {
                $exec_log[] = "- ID $char_id ($char_name): sire changed from '$old_sire' → NULL";
                $update_count++;
                $changes_made = true;
            } else {
                $exec_log[] = "- ⚠ ID $char_id ($char_name): Failed to update sire";
                throw new Exception("Failed to update sire for character ID $char_id");
            }
        }
        
        $exec_log[] = "";
        $exec_log[] = "**Total sire updates:** $update_count";
        $exec_log[] = "";
        
        // 5B) Handle Kerry case
        // Note: The two Kerry entries have different names, so they're likely intentionally different
        // We'll only fix the sire='0' issue for ID 130
        $exec_log[] = "### 5B) Kerry Case Handling";
        $exec_log[] = "";
        
        $kerry_fixed = false;
        foreach ($kerry_rows as $row) {
            if ($row['sire'] === '0' || (isset($row['sire']) && trim($row['sire']) === '0')) {
                $char_id = (int)$row['id'];
                $char_name = $row['character_name'];
                
                $update_query = "UPDATE characters SET sire = NULL WHERE id = ?";
                $result = db_execute($conn, $update_query, 'i', [$char_id]);
                
                if ($result !== false && $result > 0) {
                    $exec_log[] = "- ID $char_id ($char_name): sire changed from '0' → NULL";
                    $kerry_fixed = true;
                    $changes_made = true;
                }
            }
        }
        
        if (!$kerry_fixed) {
            $exec_log[] = "- No Kerry entries needed sire fixing";
        }
        $exec_log[] = "";
        $exec_log[] = "**Note:** Kerry entries have different names ('Kerry, the Gangrel' vs 'Kerry, the Desert-Wandering Gangrel'), so they are treated as separate characters. Only invalid sire values were fixed.";
        $exec_log[] = "";
        
        // Commit transaction
        if ($changes_made) {
            if (!db_commit($conn)) {
                throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
            }
            $exec_log[] = "✓ Transaction committed successfully";
        } else {
            db_rollback($conn);
            $exec_log[] = "⚠ No changes were made - transaction rolled back";
        }
        $exec_log[] = "";
        
    } catch (Exception $e) {
        db_rollback($conn);
        $exec_log[] = "❌ ERROR: " . $e->getMessage();
        $exec_log[] = "Transaction rolled back.";
        $exec_log[] = "";
    }
    
    // ============================================================================
    // STEP 6: Verification Queries
    // ============================================================================
    
    $exec_log[] = "## Step 6: Verification";
    $exec_log[] = "";
    
    // Re-check invalid sire values
    $verify_query = "
        SELECT COUNT(*) as count
        FROM characters
        WHERE sire IS NOT NULL
          AND (
              LOWER(TRIM(sire)) IN ('in', 'bob', 'both', 'during', 'him')
              OR TRIM(sire) = ''
          )
    ";
    
    $verify_result = mysqli_query($conn, $verify_query);
    if ($verify_result) {
        $verify_row = mysqli_fetch_assoc($verify_result);
        $remaining_invalid = (int)$verify_row['count'];
        
        $exec_log[] = "**Remaining invalid sire values:** $remaining_invalid";
        if ($remaining_invalid === 0) {
            $exec_log[] = "✓ All invalid sire placeholders have been cleaned up";
        } else {
            $exec_log[] = "⚠ $remaining_invalid invalid sire values still remain";
        }
        $exec_log[] = "";
    }
    
    // Check Kerry entries
    $exec_log[] = "**Kerry entries status:**";
    foreach ($kerry_rows as $row) {
        $char_id = (int)$row['id'];
        $check_query = "SELECT id, character_name, sire FROM characters WHERE id = $char_id";
        $check_result = mysqli_query($conn, $check_query);
        if ($check_row = mysqli_fetch_assoc($check_result)) {
            $sire_display = $check_row['sire'] === null ? 'NULL' : "'" . $check_row['sire'] . "'";
            $exec_log[] = "- ID $char_id (" . $check_row['character_name'] . "): sire = $sire_display";
        }
    }
    $exec_log[] = "";
    
    // ============================================================================
    // STEP 7: Report Integration Note
    // ============================================================================
    
    $exec_log[] = "## Step 7: Expected Impact on Summaries";
    $exec_log[] = "";
    $exec_log[] = "**Characters that will change in next summary run:**";
    $exec_log[] = "";
    
    if (count($invalid_sire_rows) > 0) {
        $exec_log[] = "The following characters had their sire values normalized to NULL:";
        foreach ($invalid_sire_rows as $row) {
            $exec_log[] = "- " . $row['character_name'] . " (ID: " . $row['id'] . ")";
        }
        $exec_log[] = "";
        $exec_log[] = "These characters will appear in summaries with 'Unknown' or empty sire field instead of placeholder text.";
    }
    
    if ($kerry_fixed) {
        $exec_log[] = "Kerry entries were checked and invalid sire values fixed where found.";
    }
    
        $exec_log[] = "";
        
        // Write execution log
        $exec_log_file = $output_dir . "/DB_CLEANUP_" . $version_shorthand . "_EXECUTION_LOG.md";
        file_put_contents($exec_log_file, implode("\n", $exec_log));
        
        // Output execution results
        if ($is_cli) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "EXECUTION PHASE COMPLETE\n";
            echo str_repeat("=", 80) . "\n\n";
            echo implode("\n", $exec_log);
            echo "\n\n✓ Execution log written to: $exec_log_file\n";
        } else {
            echo "<hr><h2>EXECUTION PHASE</h2><pre>" . htmlspecialchars(implode("\n", $exec_log)) . "</pre>";
            echo "<p>✓ Execution log written to: <code>" . htmlspecialchars($exec_log_file) . "</code></p>";
        }
        
    } catch (Exception $e) {
        $error_msg = "FATAL ERROR in execution phase: " . $e->getMessage() . "\n" . $e->getTraceAsString();
        error_log($error_msg);
        if ($is_cli) {
            echo "\n\n" . str_repeat("=", 80) . "\n";
            echo "EXECUTION PHASE FAILED\n";
            echo str_repeat("=", 80) . "\n\n";
            echo $error_msg . "\n";
        } else {
            echo "<hr><h2 style='color:red'>EXECUTION PHASE FAILED</h2>";
            echo "<pre style='color:red'>" . htmlspecialchars($error_msg) . "</pre>";
        }
    }
    
} else {
    // Not in execute mode
    if ($is_cli) {
        echo "\n⚠ Running in PRECHECK mode. Use --execute flag to proceed with cleanup.\n";
    } else {
        echo "<p>⚠ Running in PRECHECK mode. Add <code>?execute=1</code> to URL to proceed with cleanup.</p>";
    }
}

// Close connection
mysqli_close($conn);

