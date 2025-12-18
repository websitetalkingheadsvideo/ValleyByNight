<?php
/**
 * Verify Character Rituals FK Migration
 * 
 * TM-04: Verification queries for character_rituals FK migration
 * 
 * Provides detailed verification of the FK migration including:
 * - Linkage rate statistics
 * - Unmatched rows sample
 * - Ambiguity detection
 * 
 * Usage:
 *   CLI: php database/verify_character_rituals_fk.php
 *   Web: https://vbn.talkingheads.video/database/verify_character_rituals_fk.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Character Rituals FK Verification</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;} .warning{color:#ff0;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #444;padding:8px;text-align:left;} th{background:#333;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Character Rituals FK Verification (TM-04)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

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

// Check if ritual_id column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM character_rituals LIKE 'ritual_id'");
if (mysqli_num_rows($check_column) == 0) {
    die("Error: ritual_id column does not exist. Run migration first: database/add_character_rituals_fk.php");
}
mysqli_free_result($check_column);

$results = [];

// 1. Linkage Rate Statistics
$linkage_query = "
    SELECT 
        COUNT(*) as total_rows,
        SUM(CASE WHEN ritual_id IS NOT NULL THEN 1 ELSE 0 END) as linked_rows,
        SUM(CASE WHEN ritual_id IS NULL THEN 1 ELSE 0 END) as unmatched_rows
    FROM character_rituals
";

$linkage_result = mysqli_query($conn, $linkage_query);
if ($linkage_result) {
    $row = mysqli_fetch_assoc($linkage_result);
    $total = (int)$row['total_rows'];
    $linked = (int)$row['linked_rows'];
    $unmatched = (int)$row['unmatched_rows'];
    $percentage = $total > 0 ? round(($linked / $total) * 100, 2) : 0;
    
    $results['linkage'] = [
        'total_rows' => $total,
        'linked_rows' => $linked,
        'unmatched_rows' => $unmatched,
        'linkage_percentage' => $percentage
    ];
    mysqli_free_result($linkage_result);
}

// 2. Unmatched Rows Sample (Top 10)
$unmatched_query = "
    SELECT 
        cr.id,
        cr.character_id,
        cr.ritual_name,
        cr.ritual_type,
        cr.level,
        cr.is_custom,
        (
            SELECT COUNT(*) 
            FROM rituals_master rm 
            WHERE LOWER(TRIM(rm.type)) = LOWER(TRIM(cr.ritual_type))
            AND rm.level = cr.level
            AND LOWER(TRIM(rm.name)) = LOWER(TRIM(cr.ritual_name))
        ) as potential_matches
    FROM character_rituals cr
    WHERE cr.ritual_id IS NULL
    ORDER BY cr.character_id, cr.ritual_type, cr.level, cr.ritual_name
    LIMIT 10
";

$unmatched_result = mysqli_query($conn, $unmatched_query);
$unmatched_samples = [];
if ($unmatched_result) {
    while ($row = mysqli_fetch_assoc($unmatched_result)) {
        $unmatched_samples[] = $row;
    }
    mysqli_free_result($unmatched_result);
}
$results['unmatched_samples'] = $unmatched_samples;

// 3. Ambiguity Detection: Cases where (type, level, name) maps to multiple rituals_master rows
$ambiguity_query = "
    SELECT 
        LOWER(TRIM(rm.type)) as type_normalized,
        rm.level,
        LOWER(TRIM(rm.name)) as name_normalized,
        COUNT(*) as match_count,
        GROUP_CONCAT(rm.id ORDER BY rm.id SEPARATOR ', ') as ritual_ids,
        GROUP_CONCAT(rm.name ORDER BY rm.id SEPARATOR ' | ') as ritual_names
    FROM rituals_master rm
    GROUP BY type_normalized, level, name_normalized
    HAVING COUNT(*) > 1
    ORDER BY match_count DESC, type_normalized, level, name_normalized
    LIMIT 20
";

$ambiguity_result = mysqli_query($conn, $ambiguity_query);
$ambiguities = [];
if ($ambiguity_result) {
    while ($row = mysqli_fetch_assoc($ambiguity_result)) {
        $ambiguities[] = $row;
    }
    mysqli_free_result($ambiguity_result);
}
$results['ambiguities'] = $ambiguities;

// 4. Foreign Key Constraint Status
$fk_check = mysqli_query($conn, "
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'character_rituals'
    AND CONSTRAINT_NAME = 'fk_character_rituals_ritual_id'
");

$fk_status = null;
if ($fk_check) {
    $fk_row = mysqli_fetch_assoc($fk_check);
    if ($fk_row) {
        $fk_status = $fk_row;
    }
    mysqli_free_result($fk_check);
}
$results['fk_status'] = $fk_status;

// 5. Data Integrity Check: Verify all non-NULL ritual_id values reference valid rituals_master.id
$integrity_query = "
    SELECT COUNT(*) as invalid_fks
    FROM character_rituals cr
    LEFT JOIN rituals_master rm ON cr.ritual_id = rm.id
    WHERE cr.ritual_id IS NOT NULL
    AND rm.id IS NULL
";

$integrity_result = mysqli_query($conn, $integrity_query);
$integrity_issue = null;
if ($integrity_result) {
    $row = mysqli_fetch_assoc($integrity_result);
    $invalid_count = (int)$row['invalid_fks'];
    if ($invalid_count > 0) {
        $integrity_issue = [
            'invalid_fk_count' => $invalid_count,
            'message' => "WARNING: Found $invalid_count character_rituals rows with ritual_id that don't reference valid rituals_master.id"
        ];
    }
    mysqli_free_result($integrity_result);
}
$results['integrity'] = $integrity_issue;

// Display results
if ($is_cli) {
    echo "\n=== Character Rituals FK Verification ===\n\n";
    
    // Linkage Statistics
    if (isset($results['linkage'])) {
        $l = $results['linkage'];
        echo "1. LINKAGE RATE:\n";
        echo "   Total rows: {$l['total_rows']}\n";
        echo "   Linked rows: {$l['linked_rows']}\n";
        echo "   Unmatched rows: {$l['unmatched_rows']}\n";
        echo "   Linkage percentage: {$l['linkage_percentage']}%\n";
        if ($l['linkage_percentage'] >= 95) {
            echo "   Status: ✓ PASS (≥95%)\n";
        } else {
            echo "   Status: ✗ FAIL (<95%)\n";
        }
        echo "\n";
    }
    
    // Unmatched Samples
    if (!empty($results['unmatched_samples'])) {
        echo "2. UNMATCHED ROWS SAMPLE (Top 10):\n";
        foreach ($results['unmatched_samples'] as $sample) {
            echo "   ID: {$sample['id']}, Character: {$sample['character_id']}, ";
            echo "Ritual: {$sample['ritual_name']} ({$sample['ritual_type']}, Level {$sample['level']})";
            if ($sample['potential_matches'] > 0) {
                echo " [{$sample['potential_matches']} potential matches found]";
            }
            echo "\n";
        }
        echo "\n";
    } else {
        echo "2. UNMATCHED ROWS: None found\n\n";
    }
    
    // Ambiguities
    if (!empty($results['ambiguities'])) {
        echo "3. AMBIGUITY DETECTION: Found " . count($results['ambiguities']) . " ambiguous signatures\n";
        foreach ($results['ambiguities'] as $amb) {
            echo "   Type: {$amb['type_normalized']}, Level: {$amb['level']}, Name: {$amb['name_normalized']}\n";
            echo "   Matches: {$amb['match_count']} rituals (IDs: {$amb['ritual_ids']})\n";
            echo "   Names: {$amb['ritual_names']}\n\n";
        }
    } else {
        echo "3. AMBIGUITY DETECTION: No ambiguous signatures found\n\n";
    }
    
    // FK Status
    if ($results['fk_status']) {
        echo "4. FOREIGN KEY CONSTRAINT:\n";
        echo "   Status: ✓ EXISTS\n";
        echo "   Constraint: {$results['fk_status']['CONSTRAINT_NAME']}\n";
        echo "   References: {$results['fk_status']['REFERENCED_TABLE_NAME']}.{$results['fk_status']['REFERENCED_COLUMN_NAME']}\n\n";
    } else {
        echo "4. FOREIGN KEY CONSTRAINT: ✗ NOT FOUND\n\n";
    }
    
    // Integrity
    if ($results['integrity']) {
        echo "5. DATA INTEGRITY:\n";
        echo "   ✗ {$results['integrity']['message']}\n\n";
    } else {
        echo "5. DATA INTEGRITY: ✓ All FK references valid\n\n";
    }
    
} else {
    // HTML output
    echo "<h2>1. Linkage Rate Statistics</h2>";
    if (isset($results['linkage'])) {
        $l = $results['linkage'];
        echo "<table>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";
        echo "<tr><td>Total rows</td><td>{$l['total_rows']}</td></tr>";
        echo "<tr><td>Linked rows</td><td>{$l['linked_rows']}</td></tr>";
        echo "<tr><td>Unmatched rows</td><td>{$l['unmatched_rows']}</td></tr>";
        echo "<tr><td>Linkage percentage</td><td><strong>{$l['linkage_percentage']}%</strong></td></tr>";
        echo "</table>";
        if ($l['linkage_percentage'] >= 95) {
            echo "<p class='success'>✓ PASS: Linkage ≥95%</p>";
        } else {
            echo "<p class='error'>✗ FAIL: Linkage <95%</p>";
        }
    }
    
    echo "<h2>2. Unmatched Rows Sample (Top 10)</h2>";
    if (!empty($results['unmatched_samples'])) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Character ID</th><th>Ritual Name</th><th>Type</th><th>Level</th><th>Is Custom</th><th>Potential Matches</th></tr>";
        foreach ($results['unmatched_samples'] as $sample) {
            echo "<tr>";
            echo "<td>{$sample['id']}</td>";
            echo "<td>{$sample['character_id']}</td>";
            echo "<td>" . htmlspecialchars($sample['ritual_name']) . "</td>";
            echo "<td>" . htmlspecialchars($sample['ritual_type']) . "</td>";
            echo "<td>{$sample['level']}</td>";
            echo "<td>" . ($sample['is_custom'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$sample['potential_matches']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>No unmatched rows found.</p>";
    }
    
    echo "<h2>3. Ambiguity Detection</h2>";
    if (!empty($results['ambiguities'])) {
        echo "<p class='warning'>Found " . count($results['ambiguities']) . " ambiguous signatures (multiple rituals with same type/level/name)</p>";
        echo "<table>";
        echo "<tr><th>Type</th><th>Level</th><th>Name</th><th>Match Count</th><th>Ritual IDs</th><th>Ritual Names</th></tr>";
        foreach ($results['ambiguities'] as $amb) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($amb['type_normalized']) . "</td>";
            echo "<td>{$amb['level']}</td>";
            echo "<td>" . htmlspecialchars($amb['name_normalized']) . "</td>";
            echo "<td>{$amb['match_count']}</td>";
            echo "<td>{$amb['ritual_ids']}</td>";
            echo "<td>" . htmlspecialchars($amb['ritual_names']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>No ambiguous signatures found.</p>";
    }
    
    echo "<h2>4. Foreign Key Constraint Status</h2>";
    if ($results['fk_status']) {
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>Constraint Name</td><td>{$results['fk_status']['CONSTRAINT_NAME']}</td></tr>";
        echo "<tr><td>Table</td><td>{$results['fk_status']['TABLE_NAME']}</td></tr>";
        echo "<tr><td>Column</td><td>{$results['fk_status']['COLUMN_NAME']}</td></tr>";
        echo "<tr><td>References</td><td>{$results['fk_status']['REFERENCED_TABLE_NAME']}.{$results['fk_status']['REFERENCED_COLUMN_NAME']}</td></tr>";
        echo "</table>";
        echo "<p class='success'>✓ Foreign key constraint exists</p>";
    } else {
        echo "<p class='error'>✗ Foreign key constraint not found</p>";
    }
    
    echo "<h2>5. Data Integrity Check</h2>";
    if ($results['integrity']) {
        echo "<p class='error'>✗ {$results['integrity']['message']}</p>";
    } else {
        echo "<p class='success'>✓ All foreign key references are valid</p>";
    }
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

