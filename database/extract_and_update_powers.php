<?php
/**
 * Extract and Update Power Mechanics
 * 
 * Searches rulebooks database for power mechanics, determines challenge types,
 * and updates path_powers table with system_text, challenge_type, and challenge_notes.
 * 
 * This script attempts to extract content from rulebooks, but some powers may
 * require manual research to ensure rules accuracy.
 * 
 * Usage:
 *   CLI: php database/extract_and_update_powers.php [--dry-run]
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';
$dry_run = ($is_cli && in_array('--dry-run', $argv)) || (isset($_GET['dry_run']) && $_GET['dry_run'] == '1');

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all powers needing updates
$powers_query = "SELECT pp.id, pp.path_id, pp.level, pp.power_name, pm.name as path_name, pm.type as path_type
                 FROM path_powers pp
                 INNER JOIN paths_master pm ON pp.path_id = pm.id
                 WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
                 AND (pp.system_text IS NULL OR pp.system_text = '' 
                      OR pp.challenge_type = 'unknown' OR pp.challenge_type IS NULL
                      OR pp.challenge_notes IS NULL OR pp.challenge_notes = '')
                 ORDER BY pm.type, pm.name, pp.level";
$powers_result = mysqli_query($conn, $powers_query);

$powers_to_update = [];
while ($row = mysqli_fetch_assoc($powers_result)) {
    $powers_to_update[] = [
        'id' => (int)$row['id'],
        'path_id' => (int)$row['path_id'],
        'level' => (int)$row['level'],
        'power_name' => $row['power_name'],
        'path_name' => $row['path_name'],
        'path_type' => $row['path_type']
    ];
}
mysqli_free_result($powers_result);

$stats = [
    'total' => count($powers_to_update),
    'system_text_found' => 0,
    'system_text_missing' => 0,
    'updated' => 0,
    'errors' => []
];

// Prepare update statement
$update_power_stmt = mysqli_prepare($conn,
    "UPDATE path_powers 
     SET system_text = ?, challenge_type = ?, challenge_notes = ?
     WHERE id = ?"
);

if (!$update_power_stmt) {
    die("Failed to prepare update statement: " . mysqli_error($conn) . "\n");
}

// Start transaction
if (!$dry_run) {
    mysqli_begin_transaction($conn);
}

echo ($is_cli ? "Processing " . count($powers_to_update) . " powers...\n\n" : 
      "<p>Processing " . count($powers_to_update) . " powers...</p>\n");

// For each power, search rulebooks and extract mechanics
foreach ($powers_to_update as $power) {
    $power_name = $power['power_name'];
    $path_name = $power['path_name'];
    
    // Clean power name (remove parentheticals for search)
    $clean_name = preg_replace('/\s*\([^)]*\)\s*/', '', $power_name);
    
    // Search rulebooks for this power
    $search_query = "SELECT rp.page_text, r.title as book_title, rp.page_number
                     FROM rulebook_pages rp
                     JOIN rulebooks r ON rp.rulebook_id = r.id
                     WHERE (rp.page_text LIKE ? OR rp.page_text LIKE ?)
                     AND (r.title LIKE '%Thaumaturgy%' OR r.title LIKE '%Necromancy%' 
                          OR r.title LIKE '%Blood Magic%' OR r.category LIKE '%Discipline%')
                     LIMIT 1";
    
    $pattern1 = '%' . mysqli_real_escape_string($conn, $power_name) . '%';
    $pattern2 = '%' . mysqli_real_escape_string($conn, $clean_name) . '%';
    
    $search_stmt = mysqli_prepare($conn, $search_query);
    $system_text = null;
    $challenge_type = 'narrative'; // Default fallback
    $challenge_notes = 'Mechanics require extraction from rulebooks. Defaulting to narrative resolution.';
    
    if ($search_stmt) {
        mysqli_stmt_bind_param($search_stmt, 'ss', $pattern1, $pattern2);
        mysqli_stmt_execute($search_stmt);
        $search_result = mysqli_stmt_get_result($search_stmt);
        
        if ($row = mysqli_fetch_assoc($search_result)) {
            $text = $row['page_text'];
            $pos = stripos($text, $power_name);
            if ($pos === false) {
                $pos = stripos($text, $clean_name);
            }
            
            if ($pos !== false) {
                // Extract larger context for power description
                $start = max(0, $pos - 300);
                $length = min(2000, strlen($text) - $start);
                $excerpt = substr($text, $start, $length);
                
                // Try to extract system text (look for "System:" or mechanics description)
                if (preg_match('/System:?\s*([^\.]+(?:\.[^\.]+)*)/i', $excerpt, $matches)) {
                    $system_text = trim($matches[1]);
                } else {
                    // Use excerpt as system text placeholder (needs refinement)
                    $system_text = "Extracted from " . $row['book_title'] . " (page " . $row['page_number'] . "). " . 
                                   "Full mechanics: " . substr($excerpt, 0, 500);
                }
                
                // Determine challenge type based on power mechanics
                $excerpt_lower = strtolower($excerpt);
                if (preg_match('/\b(contest|contested|opposed|versus|vs|resisted|resistance)\b/i', $excerpt)) {
                    $challenge_type = 'contested';
                    $challenge_notes = 'Contested challenge. Both parties roll - caster\'s Thaumaturgy/Necromancy + relevant ability vs. target\'s resistance (usually Willpower, or specific trait/ability as determined by ST).';
                } elseif (preg_match('/\b(difficulty|difficulty number|static|target number|TN)\b/i', $excerpt)) {
                    $challenge_type = 'static';
                    $challenge_notes = 'Static challenge. Caster rolls Thaumaturgy/Necromancy + relevant ability against a fixed difficulty number (determined by ST based on circumstances).';
                } else {
                    $challenge_type = 'narrative';
                    $challenge_notes = 'Narrative resolution. ST adjudicates based on circumstances, power level, and story needs. May require trait/ability use for effect quality.';
                }
                
                $stats['system_text_found']++;
            } else {
                $stats['system_text_missing']++;
                // Use placeholder that indicates need for manual research
                $system_text = "Power mechanics not found in rulebooks database. Requires manual research from source materials for accurate system text.";
                $challenge_notes = "Challenge mechanics require manual research from rulebooks to determine accurate resolution method.";
            }
        } else {
            $stats['system_text_missing']++;
            $system_text = "Power mechanics not found in rulebooks database. Requires manual research from source materials for accurate system text.";
            $challenge_notes = "Challenge mechanics require manual research from rulebooks to determine accurate resolution method.";
        }
        
        mysqli_stmt_close($search_stmt);
    }
    
    // Update the power
    if ($dry_run) {
        echo ($is_cli ? "Would update power #{$power['id']} ({$power['power_name']}) - Challenge Type: $challenge_type\n" :
              "<p>Would update power #{$power['id']} ({$power['power_name']}) - Challenge Type: $challenge_type</p>\n");
        $stats['updated']++;
    } else {
        mysqli_stmt_bind_param($update_power_stmt, 'sssi', 
            $system_text, $challenge_type, $challenge_notes, $power['id']);
        
        if (mysqli_stmt_execute($update_power_stmt)) {
            $stats['updated']++;
        } else {
            $stats['errors'][] = "Failed to update power #{$power['id']}: " . mysqli_stmt_error($update_power_stmt);
        }
    }
}

mysqli_stmt_close($update_power_stmt);

if (!$dry_run) {
    mysqli_commit($conn);
    echo ($is_cli ? "\nTransaction committed successfully.\n" : 
          "<p>Transaction committed successfully.</p>\n");
} else {
    echo ($is_cli ? "\nDRY RUN - No changes made.\n" : 
          "<p><strong>DRY RUN - No changes made.</strong></p>\n");
}

// Output results
if ($is_cli) {
    echo "\nUpdate Summary:\n";
    echo "Total powers processed: " . $stats['total'] . "\n";
    echo "System text found: " . $stats['system_text_found'] . "\n";
    echo "System text missing: " . $stats['system_text_missing'] . "\n";
    echo "Powers updated: " . $stats['updated'] . "\n";
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "<!DOCTYPE html><html><head><title>Power Updates</title></head><body>";
    echo "<h1>Power Update Summary</h1>";
    echo "<p>Total powers processed: <strong>" . $stats['total'] . "</strong></p>";
    echo "<p>System text found: <strong>" . $stats['system_text_found'] . "</strong></p>";
    echo "<p>System text missing: <strong>" . $stats['system_text_missing'] . "</strong></p>";
    echo "<p>Powers updated: <strong>" . $stats['updated'] . "</strong></p>";
    if (!empty($stats['errors'])) {
        echo "<h2>Errors</h2><ul>";
        foreach ($stats['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    echo "</body></html>";
}

mysqli_close($conn);
?>

