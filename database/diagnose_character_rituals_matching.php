<?php
/**
 * Diagnostic script to analyze character_rituals matching issues
 * 
 * Helps identify why rows aren't matching to rituals_master
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Character Rituals Matching Diagnosis</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #444;padding:8px;text-align:left;} th{background:#333;} .match{color:#0f0;} .nomatch{color:#f00;} .partial{color:#ff0;}</style></head><body><h1>Character Rituals Matching Diagnosis</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all unmatched character_rituals
$unmatched_query = "
    SELECT 
        cr.id,
        cr.character_id,
        cr.ritual_name,
        cr.ritual_type,
        cr.level,
        cr.is_custom,
        cr.ritual_id
    FROM character_rituals cr
    WHERE cr.ritual_id IS NULL
    ORDER BY cr.character_id, cr.ritual_type, cr.level, cr.ritual_name
";

$unmatched_result = mysqli_query($conn, $unmatched_query);
$unmatched_rows = [];
if ($unmatched_result) {
    while ($row = mysqli_fetch_assoc($unmatched_result)) {
        $unmatched_rows[] = $row;
    }
    mysqli_free_result($unmatched_result);
}

// For each unmatched row, try to find potential matches
$diagnostics = [];
foreach ($unmatched_rows as $cr_row) {
    $cr_name = trim($cr_row['ritual_name']);
    $cr_type = trim($cr_row['ritual_type']);
    $cr_level = (int)$cr_row['level'];
    
    // Try exact match first
    $exact_match = "
        SELECT id, name, type, level
        FROM rituals_master
        WHERE LOWER(TRIM(type)) = LOWER(?)
        AND level = ?
        AND LOWER(TRIM(name)) = LOWER(?)
    ";
    $stmt = mysqli_prepare($conn, $exact_match);
    mysqli_stmt_bind_param($stmt, 'sis', $cr_type, $cr_level, $cr_name);
    mysqli_stmt_execute($stmt);
    $exact_result = mysqli_stmt_get_result($stmt);
    $exact_matches = [];
    while ($row = mysqli_fetch_assoc($exact_result)) {
        $exact_matches[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // Try type + level match (name might be different)
    $type_level_match = "
        SELECT id, name, type, level
        FROM rituals_master
        WHERE LOWER(TRIM(type)) = LOWER(?)
        AND level = ?
        ORDER BY id
        LIMIT 5
    ";
    $stmt = mysqli_prepare($conn, $type_level_match);
    mysqli_stmt_bind_param($stmt, 'si', $cr_type, $cr_level);
    mysqli_stmt_execute($stmt);
    $type_level_result = mysqli_stmt_get_result($stmt);
    $type_level_matches = [];
    while ($row = mysqli_fetch_assoc($type_level_result)) {
        $type_level_matches[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // Try name similarity (fuzzy match)
    $name_similar = "
        SELECT id, name, type, level
        FROM rituals_master
        WHERE LOWER(TRIM(name)) LIKE LOWER(?)
        OR LOWER(TRIM(name)) LIKE LOWER(?)
        ORDER BY 
            CASE 
                WHEN LOWER(TRIM(name)) = LOWER(?) THEN 1
                WHEN LOWER(TRIM(name)) LIKE LOWER(?) THEN 2
                ELSE 3
            END,
            id
        LIMIT 5
    ";
    $name_pattern = '%' . $cr_name . '%';
    $name_pattern_start = $cr_name . '%';
    $stmt = mysqli_prepare($conn, $name_similar);
    mysqli_stmt_bind_param($stmt, 'ssss', $name_pattern, $name_pattern_start, $cr_name, $name_pattern_start);
    mysqli_stmt_execute($stmt);
    $name_similar_result = mysqli_stmt_get_result($stmt);
    $name_similar_matches = [];
    while ($row = mysqli_fetch_assoc($name_similar_result)) {
        $name_similar_matches[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    $diagnostics[] = [
        'character_ritual' => $cr_row,
        'exact_matches' => $exact_matches,
        'type_level_matches' => $type_level_matches,
        'name_similar_matches' => $name_similar_matches
    ];
}

// Display results
if ($is_cli) {
    echo "\n=== Unmatched Character Rituals Analysis ===\n\n";
    foreach ($diagnostics as $diag) {
        $cr = $diag['character_ritual'];
        echo "Character Ritual ID: {$cr['id']}\n";
        echo "  Character ID: {$cr['character_id']}\n";
        echo "  Name: '{$cr['ritual_name']}'\n";
        echo "  Type: '{$cr['ritual_type']}'\n";
        echo "  Level: {$cr['level']}\n";
        echo "  Is Custom: " . ($cr['is_custom'] ? 'Yes' : 'No') . "\n";
        
        if (!empty($diag['exact_matches'])) {
            echo "  ✓ EXACT MATCHES FOUND:\n";
            foreach ($diag['exact_matches'] as $match) {
                echo "    - ID: {$match['id']}, Name: '{$match['name']}', Type: '{$match['type']}', Level: {$match['level']}\n";
            }
        } else {
            echo "  ✗ No exact matches\n";
        }
        
        if (!empty($diag['type_level_matches'])) {
            echo "  Type+Level matches (different name):\n";
            foreach ($diag['type_level_matches'] as $match) {
                echo "    - ID: {$match['id']}, Name: '{$match['name']}', Type: '{$match['type']}', Level: {$match['level']}\n";
            }
        }
        
        if (!empty($diag['name_similar_matches'])) {
            echo "  Similar name matches:\n";
            foreach ($diag['name_similar_matches'] as $match) {
                echo "    - ID: {$match['id']}, Name: '{$match['name']}', Type: '{$match['type']}', Level: {$match['level']}\n";
            }
        }
        
        echo "\n";
    }
} else {
    echo "<h2>Unmatched Character Rituals Analysis</h2>";
    echo "<p>Total unmatched: " . count($diagnostics) . "</p>";
    
    foreach ($diagnostics as $diag) {
        $cr = $diag['character_ritual'];
        echo "<h3>Character Ritual ID: {$cr['id']}</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Character ID</td><td>{$cr['character_id']}</td></tr>";
        echo "<tr><td>Ritual Name</td><td>" . htmlspecialchars($cr['ritual_name']) . "</td></tr>";
        echo "<tr><td>Ritual Type</td><td>" . htmlspecialchars($cr['ritual_type']) . "</td></tr>";
        echo "<tr><td>Level</td><td>{$cr['level']}</td></tr>";
        echo "<tr><td>Is Custom</td><td>" . ($cr['is_custom'] ? 'Yes' : 'No') . "</td></tr>";
        echo "</table>";
        
        if (!empty($diag['exact_matches'])) {
            echo "<p class='match'>✓ EXACT MATCHES FOUND:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Level</th></tr>";
            foreach ($diag['exact_matches'] as $match) {
                echo "<tr>";
                echo "<td>{$match['id']}</td>";
                echo "<td>" . htmlspecialchars($match['name']) . "</td>";
                echo "<td>" . htmlspecialchars($match['type']) . "</td>";
                echo "<td>{$match['level']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='nomatch'>✗ No exact matches</p>";
        }
        
        if (!empty($diag['type_level_matches'])) {
            echo "<p class='partial'>Type+Level matches (different name):</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Level</th></tr>";
            foreach ($diag['type_level_matches'] as $match) {
                echo "<tr>";
                echo "<td>{$match['id']}</td>";
                echo "<td>" . htmlspecialchars($match['name']) . "</td>";
                echo "<td>" . htmlspecialchars($match['type']) . "</td>";
                echo "<td>{$match['level']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        if (!empty($diag['name_similar_matches'])) {
            echo "<p class='partial'>Similar name matches:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Level</th></tr>";
            foreach ($diag['name_similar_matches'] as $match) {
                echo "<tr>";
                echo "<td>{$match['id']}</td>";
                echo "<td>" . htmlspecialchars($match['name']) . "</td>";
                echo "<td>" . htmlspecialchars($match['type']) . "</td>";
                echo "<td>{$match['level']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<hr>";
    }
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

