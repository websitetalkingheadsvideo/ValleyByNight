<?php
/**
 * Add ritual_id foreign key to character_rituals table
 * 
 * TM-04: Character Rituals FK Migration
 * 
 * Adds a nullable ritual_id column to character_rituals, backfills it by matching
 * on (type, level, name), and creates a foreign key constraint to rituals_master.id.
 * Legacy fields (ritual_name, ritual_type) are preserved as fallback.
 * 
 * Usage: 
 *   CLI: php database/add_character_rituals_fk.php
 *   Web: https://vbn.talkingheads.video/database/add_character_rituals_fk.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Character Rituals FK Migration</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Character Rituals FK Migration (TM-04)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$warnings = [];
$stats = [
    'total_rows' => 0,
    'matched_rows' => 0,
    'unmatched_rows' => 0,
    'ambiguous_matches' => 0
];

try {
    // Verify tables exist
    $tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'character_rituals'");
    if (mysqli_num_rows($tables_check) === 0) {
        die("Error: character_rituals table does not exist.");
    }
    mysqli_free_result($tables_check);
    
    $tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'rituals_master'");
    if (mysqli_num_rows($tables_check) === 0) {
        die("Error: rituals_master table does not exist.");
    }
    mysqli_free_result($tables_check);
    
    // Step 1: Check if ritual_id column already exists
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM character_rituals LIKE 'ritual_id'");
    
    if (mysqli_num_rows($check_column) == 0) {
        // Add nullable ritual_id column
        $alter_query = "ALTER TABLE character_rituals ADD COLUMN ritual_id INT NULL COMMENT 'FK to rituals_master.id'";
        
        if (mysqli_query($conn, $alter_query)) {
            $success[] = "Added ritual_id column to character_rituals table";
        } else {
            $errors[] = "Failed to add ritual_id column: " . mysqli_error($conn);
            mysqli_close($conn);
            exit(1);
        }
    } else {
        $success[] = "Column ritual_id already exists";
    }
    mysqli_free_result($check_column);
    
    // Step 2: Add index on ritual_id for performance
    $check_index = mysqli_query($conn, "SHOW INDEX FROM character_rituals WHERE Key_name = 'idx_ritual_id'");
    if (mysqli_num_rows($check_index) == 0) {
        $index_query = "CREATE INDEX idx_ritual_id ON character_rituals(ritual_id)";
        if (mysqli_query($conn, $index_query)) {
            $success[] = "Created index on ritual_id";
        } else {
            $warnings[] = "Failed to create index (may already exist): " . mysqli_error($conn);
        }
    } else {
        $success[] = "Index idx_ritual_id already exists";
    }
    mysqli_free_result($check_index);
    
    // Step 3a: Backfill ritual_id by exact matching on (type, level, name)
    // Use case-insensitive, trimmed matching for robustness
    $backfill_exact_query = "
        UPDATE character_rituals cr
        INNER JOIN (
            SELECT 
                rm.id,
                LOWER(TRIM(rm.type)) as type_normalized,
                rm.level,
                LOWER(TRIM(rm.name)) as name_normalized
            FROM rituals_master rm
        ) rm_normalized ON (
            LOWER(TRIM(cr.ritual_type)) = rm_normalized.type_normalized
            AND cr.level = rm_normalized.level
            AND LOWER(TRIM(cr.ritual_name)) = rm_normalized.name_normalized
        )
        INNER JOIN (
            SELECT 
                LOWER(TRIM(type)) as type_normalized,
                level,
                LOWER(TRIM(name)) as name_normalized,
                MIN(id) as min_id
            FROM rituals_master
            GROUP BY type_normalized, level, name_normalized
        ) rm_min ON (
            rm_normalized.type_normalized = rm_min.type_normalized
            AND rm_normalized.level = rm_min.level
            AND rm_normalized.name_normalized = rm_min.name_normalized
            AND rm_normalized.id = rm_min.min_id
        )
        SET cr.ritual_id = rm_normalized.id
        WHERE cr.ritual_id IS NULL
    ";
    
    if (mysqli_query($conn, $backfill_exact_query)) {
        $matched_exact = mysqli_affected_rows($conn);
        $success[] = "Backfilled ritual_id (exact match) for $matched_exact rows";
    } else {
        $errors[] = "Failed to backfill ritual_id (exact match): " . mysqli_error($conn);
    }
    
    // Step 3b: Backfill remaining rows by matching on (type, name) only (ignore level)
    // This handles cases where level was incorrectly stored or changed
    $backfill_fuzzy_query = "
        UPDATE character_rituals cr
        INNER JOIN (
            SELECT 
                rm.id,
                LOWER(TRIM(rm.type)) as type_normalized,
                LOWER(TRIM(rm.name)) as name_normalized,
                rm.level as master_level
            FROM rituals_master rm
        ) rm_normalized ON (
            LOWER(TRIM(cr.ritual_type)) = rm_normalized.type_normalized
            AND LOWER(TRIM(cr.ritual_name)) = rm_normalized.name_normalized
        )
        INNER JOIN (
            SELECT 
                LOWER(TRIM(type)) as type_normalized,
                LOWER(TRIM(name)) as name_normalized,
                MIN(id) as min_id
            FROM rituals_master
            GROUP BY type_normalized, name_normalized
        ) rm_min ON (
            rm_normalized.type_normalized = rm_min.type_normalized
            AND rm_normalized.name_normalized = rm_min.name_normalized
            AND rm_normalized.id = rm_min.min_id
        )
        SET cr.ritual_id = rm_normalized.id
        WHERE cr.ritual_id IS NULL
    ";
    
    if (mysqli_query($conn, $backfill_fuzzy_query)) {
        $matched_fuzzy = mysqli_affected_rows($conn);
        if ($matched_fuzzy > 0) {
            $success[] = "Backfilled ritual_id (type+name match, level ignored) for $matched_fuzzy rows";
            $warnings[] = "Note: Some rituals matched by type+name only (level mismatch detected)";
        }
    } else {
        $warnings[] = "Fuzzy backfill query had issues (non-critical): " . mysqli_error($conn);
    }
    
    // Step 3c: Backfill with name normalization (vs -> versus, etc.)
    // This handles common abbreviation variations
    $backfill_normalized_query = "
        UPDATE character_rituals cr
        INNER JOIN (
            SELECT 
                rm.id,
                LOWER(TRIM(rm.type)) as type_normalized,
                LOWER(REPLACE(REPLACE(REPLACE(TRIM(rm.name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus ')) as name_normalized,
                rm.level as master_level
            FROM rituals_master rm
        ) rm_normalized ON (
            LOWER(TRIM(cr.ritual_type)) = rm_normalized.type_normalized
            AND LOWER(REPLACE(REPLACE(REPLACE(TRIM(cr.ritual_name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus ')) = rm_normalized.name_normalized
        )
        INNER JOIN (
            SELECT 
                LOWER(TRIM(type)) as type_normalized,
                LOWER(REPLACE(REPLACE(REPLACE(TRIM(name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus ')) as name_normalized,
                MIN(id) as min_id
            FROM rituals_master
            GROUP BY type_normalized, name_normalized
        ) rm_min ON (
            rm_normalized.type_normalized = rm_min.type_normalized
            AND rm_normalized.name_normalized = rm_min.name_normalized
            AND rm_normalized.id = rm_min.min_id
        )
        SET cr.ritual_id = rm_normalized.id
        WHERE cr.ritual_id IS NULL
    ";
    
    if (mysqli_query($conn, $backfill_normalized_query)) {
        $matched_normalized = mysqli_affected_rows($conn);
        if ($matched_normalized > 0) {
            $success[] = "Backfilled ritual_id (normalized name match: vs->versus) for $matched_normalized rows";
        }
    } else {
        $warnings[] = "Normalized backfill query had issues (non-critical): " . mysqli_error($conn);
    }
    
    // Step 3d: Try matching with singular/plural normalization (spirits <-> spirit, etc.)
    // This handles cases like "Ward vs Spirits" -> "Ward versus Spirit"
    $backfill_plural_query = "
        UPDATE character_rituals cr
        INNER JOIN (
            SELECT 
                rm.id,
                LOWER(TRIM(rm.type)) as type_normalized,
                LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(rm.name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus '), ' spirits', ' spirit'), ' ghouls', ' ghoul'), ' demons', ' demon'), ' ghosts', ' ghost'), ' magi', ' magus'), ' kindred', ' kindred'), ' fae', ' fae'), ' lupines', ' lupine'), ' cathayans', ' cathayan')) as name_normalized,
                rm.level as master_level
            FROM rituals_master rm
        ) rm_normalized ON (
            LOWER(TRIM(cr.ritual_type)) = rm_normalized.type_normalized
            AND (
                LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(cr.ritual_name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus '), ' spirits', ' spirit'), ' ghouls', ' ghoul'), ' demons', ' demon'), ' ghosts', ' ghost'), ' magi', ' magus'), ' kindred', ' kindred'), ' fae', ' fae'), ' lupines', ' lupine'), ' cathayans', ' cathayan')) = rm_normalized.name_normalized
                OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(cr.ritual_name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus '), ' spirit', ' spirits'), ' ghoul', ' ghouls'), ' demon', ' demons'), ' ghost', ' ghosts'), ' magus', ' magi'), ' kindred', ' kindred'), ' fae', ' fae'), ' lupine', ' lupines'), ' cathayan', ' cathayans')) = rm_normalized.name_normalized
            )
        )
        INNER JOIN (
            SELECT 
                LOWER(TRIM(type)) as type_normalized,
                LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus '), ' spirits', ' spirit'), ' ghouls', ' ghoul'), ' demons', ' demon'), ' ghosts', ' ghost'), ' magi', ' magus'), ' kindred', ' kindred'), ' fae', ' fae'), ' lupines', ' lupine'), ' cathayans', ' cathayan')) as name_normalized,
                MIN(id) as min_id
            FROM rituals_master
            GROUP BY type_normalized, name_normalized
        ) rm_min ON (
            rm_normalized.type_normalized = rm_min.type_normalized
            AND rm_normalized.name_normalized = rm_min.name_normalized
            AND rm_normalized.id = rm_min.min_id
        )
        SET cr.ritual_id = rm_normalized.id
        WHERE cr.ritual_id IS NULL
    ";
    
    if (mysqli_query($conn, $backfill_plural_query)) {
        $matched_plural = mysqli_affected_rows($conn);
        if ($matched_plural > 0) {
            $success[] = "Backfilled ritual_id (plural/singular normalization) for $matched_plural rows";
        }
    } else {
        $warnings[] = "Plural normalization backfill query had issues (non-critical): " . mysqli_error($conn);
    }
    
    // Step 4: Check for ambiguous matches (multiple rituals with same signature)
    $ambiguity_check = "
        SELECT 
            LOWER(TRIM(type)) as type_normalized,
            level,
            LOWER(TRIM(name)) as name_normalized,
            COUNT(*) as match_count,
            GROUP_CONCAT(id ORDER BY id) as ids
        FROM rituals_master
        GROUP BY type_normalized, level, name_normalized
        HAVING COUNT(*) > 1
    ";
    
    $ambiguity_result = mysqli_query($conn, $ambiguity_check);
    if ($ambiguity_result) {
        $ambiguous_count = mysqli_num_rows($ambiguity_result);
        $stats['ambiguous_matches'] = $ambiguous_count;
        if ($ambiguous_count > 0) {
            $warnings[] = "Found $ambiguous_count ambiguous ritual signatures (multiple rituals with same type/level/name)";
            // Log first few examples
            $examples = [];
            $count = 0;
            while ($row = mysqli_fetch_assoc($ambiguity_result) && $count < 5) {
                $examples[] = "Type: {$row['type_normalized']}, Level: {$row['level']}, Name: {$row['name_normalized']}, IDs: {$row['ids']}";
                $count++;
            }
            if (!empty($examples)) {
                $warnings[] = "Examples: " . implode("; ", $examples);
            }
        }
        mysqli_free_result($ambiguity_result);
    }
    
    // Step 5: Get statistics
    $stats_query = "SELECT COUNT(*) as total FROM character_rituals";
    $stats_result = mysqli_query($conn, $stats_query);
    if ($stats_result) {
        $row = mysqli_fetch_assoc($stats_result);
        $stats['total_rows'] = (int)$row['total'];
        mysqli_free_result($stats_result);
    }
    
    $linked_query = "SELECT COUNT(*) as linked FROM character_rituals WHERE ritual_id IS NOT NULL";
    $linked_result = mysqli_query($conn, $linked_query);
    if ($linked_result) {
        $row = mysqli_fetch_assoc($linked_result);
        $stats['matched_rows'] = (int)$row['linked'];
        mysqli_free_result($linked_result);
    }
    
    // Calculate level mismatches (where ritual matched but level differs)
    $level_mismatch_query = "
        SELECT COUNT(*) as mismatches
        FROM character_rituals cr
        INNER JOIN rituals_master rm ON cr.ritual_id = rm.id
        WHERE cr.ritual_id IS NOT NULL
        AND cr.level != rm.level
    ";
    $level_mismatch_result = mysqli_query($conn, $level_mismatch_query);
    if ($level_mismatch_result) {
        $row = mysqli_fetch_assoc($level_mismatch_result);
        $level_mismatches = (int)$row['mismatches'];
        if ($level_mismatches > 0) {
            $warnings[] = "Found $level_mismatches rituals with level mismatches (matched by type+name, level differs)";
        }
        mysqli_free_result($level_mismatch_result);
    }
    
    $unmatched_query = "SELECT COUNT(*) as unmatched FROM character_rituals WHERE ritual_id IS NULL";
    $unmatched_result = mysqli_query($conn, $unmatched_query);
    if ($unmatched_result) {
        $row = mysqli_fetch_assoc($unmatched_result);
        $stats['unmatched_rows'] = (int)$row['unmatched'];
        mysqli_free_result($unmatched_result);
    }
    
    $linkage_percentage = $stats['total_rows'] > 0 
        ? round(($stats['matched_rows'] / $stats['total_rows']) * 100, 2) 
        : 0;
    
    // Step 6: Add foreign key constraint (only if we have matches)
    // Check if FK already exists
    $fk_check = mysqli_query($conn, "
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'character_rituals' 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
        AND CONSTRAINT_NAME = 'fk_character_rituals_ritual_id'
    ");
    
    if (mysqli_num_rows($fk_check) == 0) {
        // Only add FK if we have some matches (avoid constraint violations)
        if ($stats['matched_rows'] > 0) {
            $fk_query = "
                ALTER TABLE character_rituals 
                ADD CONSTRAINT fk_character_rituals_ritual_id 
                FOREIGN KEY (ritual_id) 
                REFERENCES rituals_master(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE
            ";
            
            if (mysqli_query($conn, $fk_query)) {
                $success[] = "Created foreign key constraint fk_character_rituals_ritual_id";
            } else {
                $warnings[] = "Failed to create foreign key (may need to clean data first): " . mysqli_error($conn);
            }
        } else {
            $warnings[] = "Skipping foreign key creation: no matched rows found";
        }
    } else {
        $success[] = "Foreign key constraint fk_character_rituals_ritual_id already exists";
    }
    mysqli_free_result($fk_check);
    
    // Display results
    if ($is_cli) {
        echo "\n=== Migration Results ===\n\n";
        foreach ($success as $msg) {
            echo "✓ $msg\n";
        }
        foreach ($warnings as $msg) {
            echo "⚠ $msg\n";
        }
        foreach ($errors as $msg) {
            echo "✗ $msg\n";
        }
        
        echo "\n=== Statistics ===\n";
        echo "Total character_rituals rows: {$stats['total_rows']}\n";
        echo "Rows with ritual_id: {$stats['matched_rows']}\n";
        echo "Rows without ritual_id: {$stats['unmatched_rows']}\n";
        echo "Linkage percentage: {$linkage_percentage}%\n";
        echo "Ambiguous signatures: {$stats['ambiguous_matches']}\n";
        
        if ($linkage_percentage >= 95) {
            echo "\n✓ Migration successful: ≥95% linkage achieved\n";
        } else {
            echo "\n⚠ Warning: Linkage below 95% threshold\n";
        }
    } else {
        echo "<h2>Migration Results</h2>";
        if (!empty($success)) {
            echo "<ul>";
            foreach ($success as $msg) {
                echo "<li class='success'>✓ " . htmlspecialchars($msg) . "</li>";
            }
            echo "</ul>";
        }
        if (!empty($warnings)) {
            echo "<ul>";
            foreach ($warnings as $msg) {
                echo "<li class='info'>⚠ " . htmlspecialchars($msg) . "</li>";
            }
            echo "</ul>";
        }
        if (!empty($errors)) {
            echo "<ul>";
            foreach ($errors as $msg) {
                echo "<li class='error'>✗ " . htmlspecialchars($msg) . "</li>";
            }
            echo "</ul>";
        }
        
        echo "<h2>Statistics</h2>";
        echo "<ul>";
        echo "<li>Total character_rituals rows: <strong>{$stats['total_rows']}</strong></li>";
        echo "<li>Rows with ritual_id: <strong>{$stats['matched_rows']}</strong></li>";
        echo "<li>Rows without ritual_id: <strong>{$stats['unmatched_rows']}</strong></li>";
        echo "<li>Linkage percentage: <strong>{$linkage_percentage}%</strong></li>";
        echo "<li>Ambiguous signatures: <strong>{$stats['ambiguous_matches']}</strong></li>";
        echo "</ul>";
        
        if ($linkage_percentage >= 95) {
            echo "<p class='success'><strong>✓ Migration successful: ≥95% linkage achieved</strong></p>";
        } else {
            echo "<p class='info'><strong>⚠ Warning: Linkage below 95% threshold</strong></p>";
        }
        
        echo "</body></html>";
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
    if ($is_cli) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    } else {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p></body></html>";
    }
}

mysqli_close($conn);
?>

