<?php
/**
 * Extract Paths Content from Rulebooks
 * 
 * Searches rulebooks database for path descriptions and power system_text,
 * then structures the findings for review before database updates.
 * 
 * Usage:
 *   CLI: php database/extract_paths_content.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Load baseline audit to know what we need
$baseline_file = __DIR__ . '/../tmp/paths_audit_baseline.json';
if (!file_exists($baseline_file)) {
    die("Error: Baseline audit file not found. Run audit_paths_completion.php first.\n");
}

$baseline = json_decode(file_get_contents($baseline_file), true);
if (!$baseline) {
    die("Error: Failed to parse baseline audit file.\n");
}

// Initialize extraction results
$extractions = [
    'path_descriptions' => [],
    'power_mechanics' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

// Get all paths needing descriptions (Thaumaturgy paths only)
$paths_query = "SELECT id, name, type FROM paths_master 
                WHERE type = 'Thaumaturgy' 
                AND (description IS NULL OR description = '')
                ORDER BY name";
$paths_result = mysqli_query($conn, $paths_query);

$paths_to_extract = [];
while ($row = mysqli_fetch_assoc($paths_result)) {
    $paths_to_extract[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'type' => $row['type']
    ];
}
mysqli_free_result($paths_result);

// Get all powers needing system_text
$powers_query = "SELECT pp.id, pp.path_id, pp.level, pp.power_name, pm.name as path_name, pm.type as path_type
                 FROM path_powers pp
                 INNER JOIN paths_master pm ON pp.path_id = pm.id
                 WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
                 AND (pp.system_text IS NULL OR pp.system_text = '')
                 ORDER BY pm.type, pm.name, pp.level";
$powers_result = mysqli_query($conn, $powers_query);

$powers_to_extract = [];
while ($row = mysqli_fetch_assoc($powers_result)) {
    $powers_to_extract[] = [
        'id' => (int)$row['id'],
        'path_id' => (int)$row['path_id'],
        'level' => (int)$row['level'],
        'power_name' => $row['power_name'],
        'path_name' => $row['path_name'],
        'path_type' => $row['path_type']
    ];
}
mysqli_free_result($powers_result);

// Extract path descriptions from rulebooks
echo ($is_cli ? "Extracting path descriptions...\n" : "<p>Extracting path descriptions...</p>\n");

foreach ($paths_to_extract as $path) {
    $path_name = $path['name'];
    
    // Search for path description - try various search patterns
    $search_patterns = [
        "Path of " . $path_name,
        $path_name . " path",
        $path_name,
        str_replace("Path of ", "", $path_name)
    ];
    
    $best_match = null;
    $best_relevance = 0;
    
    foreach ($search_patterns as $pattern) {
        $search_query = "SELECT 
                            r.id AS rulebook_id,
                            r.title AS book_title,
                            rp.page_number,
                            rp.page_text
                         FROM rulebook_pages rp
                         JOIN rulebooks r ON rp.rulebook_id = r.id
                         WHERE rp.page_text LIKE ?
                         AND (r.title LIKE '%Thaumaturgy%' OR r.title LIKE '%Blood Magic%' OR r.category LIKE '%Discipline%')
                         ORDER BY 
                             CASE 
                                 WHEN rp.page_text LIKE ? THEN 1
                                 WHEN rp.page_text LIKE ? THEN 2
                                 ELSE 3
                             END
                         LIMIT 3";
        
        $pattern1 = '%' . $pattern . '%';
        $pattern2 = '%Path of ' . $pattern . '%';
        $pattern3 = '%' . $pattern . ' Path%';
        
        $stmt = mysqli_prepare($conn, $search_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $pattern1, $pattern2, $pattern3);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Extract a paragraph around the path mention
                $text = $row['page_text'];
                $pos = stripos($text, $pattern);
                
                if ($pos !== false) {
                    $start = max(0, $pos - 300);
                    $length = min(600, strlen($text) - $start);
                    $excerpt = substr($text, $start, $length);
                    
                    if (!$best_match || strlen($excerpt) > $best_relevance) {
                        $best_match = [
                            'path_id' => $path['id'],
                            'path_name' => $path_name,
                            'book_title' => $row['book_title'],
                            'page_number' => $row['page_number'],
                            'excerpt' => trim($excerpt),
                            'description' => null // To be filled manually or by AI
                        ];
                        $best_relevance = strlen($excerpt);
                    }
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if ($best_match) {
        $extractions['path_descriptions'][] = $best_match;
    } else {
        // Mark as needing manual research
        $extractions['path_descriptions'][] = [
            'path_id' => $path['id'],
            'path_name' => $path_name,
            'status' => 'not_found',
            'note' => 'Needs manual research or extraction from reference files'
        ];
    }
}

// Extract power mechanics from rulebooks
echo ($is_cli ? "Extracting power mechanics...\n" : "<p>Extracting power mechanics...</p>\n");

foreach ($powers_to_extract as $power) {
    $power_name = $power['power_name'];
    $path_name = $power['path_name'];
    
    // Clean power name for search (remove parentheticals)
    $clean_power_name = preg_replace('/\s*\([^)]*\)\s*/', '', $power_name);
    
    // Search for power mechanics
    $search_query = "SELECT 
                        r.id AS rulebook_id,
                        r.title AS book_title,
                        rp.page_number,
                        rp.page_text
                     FROM rulebook_pages rp
                     JOIN rulebooks r ON rp.rulebook_id = r.id
                     WHERE (rp.page_text LIKE ? OR rp.page_text LIKE ?)
                     AND (r.title LIKE '%Thaumaturgy%' OR r.title LIKE '%Necromancy%' 
                          OR r.title LIKE '%Blood Magic%' OR r.category LIKE '%Discipline%')
                     LIMIT 3";
    
    $pattern1 = '%' . mysqli_real_escape_string($conn, $power_name) . '%';
    $pattern2 = '%' . mysqli_real_escape_string($conn, $clean_power_name) . '%';
    
    $stmt = mysqli_prepare($conn, $search_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $pattern1, $pattern2);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $best_match = null;
        $best_length = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $text = $row['page_text'];
            $pos = stripos($text, $power_name);
            if ($pos === false) {
                $pos = stripos($text, $clean_power_name);
            }
            
            if ($pos !== false) {
                // Extract larger excerpt for power mechanics (more detailed)
                $start = max(0, $pos - 500);
                $length = min(1500, strlen($text) - $start);
                $excerpt = substr($text, $start, $length);
                
                if (!$best_match || strlen($excerpt) > $best_length) {
                    $best_match = [
                        'power_id' => $power['id'],
                        'power_name' => $power_name,
                        'path_id' => $power['path_id'],
                        'path_name' => $path_name,
                        'path_type' => $power['path_type'],
                        'level' => $power['level'],
                        'book_title' => $row['book_title'],
                        'page_number' => $row['page_number'],
                        'excerpt' => trim($excerpt),
                        'system_text' => null // To be extracted/cleaned from excerpt
                    ];
                    $best_length = strlen($excerpt);
                }
            }
        }
        
        if ($best_match) {
            $extractions['power_mechanics'][] = $best_match;
        } else {
            // Mark as needing manual research
            $extractions['power_mechanics'][] = [
                'power_id' => $power['id'],
                'power_name' => $power_name,
                'path_id' => $power['path_id'],
                'path_name' => $path_name,
                'level' => $power['level'],
                'status' => 'not_found',
                'note' => 'Needs manual research or extraction from reference files'
            ];
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Save extractions
$descriptions_file = __DIR__ . '/../tmp/path_descriptions.json';
$mechanics_file = __DIR__ . '/../tmp/power_system_text.json';

$tmp_dir = dirname($descriptions_file);
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}

file_put_contents($descriptions_file, json_encode([
    'path_descriptions' => $extractions['path_descriptions'],
    'timestamp' => $extractions['timestamp']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

file_put_contents($mechanics_file, json_encode([
    'power_mechanics' => $extractions['power_mechanics'],
    'timestamp' => $extractions['timestamp']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($is_cli) {
    echo "\nExtraction Complete\n";
    echo "Path descriptions saved to: $descriptions_file\n";
    echo "Power mechanics saved to: $mechanics_file\n";
    echo "\nFound " . count(array_filter($extractions['path_descriptions'], function($p) { return !isset($p['status']); })) . " path descriptions\n";
    echo "Found " . count(array_filter($extractions['power_mechanics'], function($p) { return !isset($p['status']); })) . " power mechanics\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Extraction Results</title></head><body>";
    echo "<h1>Path Content Extraction</h1>";
    echo "<p>Extraction complete. Results saved to JSON files.</p>";
    echo "</body></html>";
}

mysqli_close($conn);
?>

