<?php
/**
 * Character Disciplines Backfill Script
 * 
 * Identifies characters with missing disciplines in the character_disciplines table,
 * searches JSON files for discipline data, and inserts them into the database.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_disciplines.php [options]
 * 
 * Optional Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
 *   --help             Show this help message
 * 
 * Output Files:
 *   - missing_disciplines_report.json    List of characters missing disciplines
 *   - disciplines_updates.log             Log of all database updates
 *   - disciplines_not_found.json         Characters where no data was found
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

// Parse command-line arguments
$options = [
    'dry-run' => false,
    'verbose' => false,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Disciplines Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_disciplines.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --dry-run          Show what would be updated without writing to database\n";
    echo "  --verbose          Show detailed progress for each character\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_disciplines.php --dry-run\n";
    echo "  php tools/repeatable/backfill_disciplines.php --verbose\n\n";
    exit(0);
}

// Get project root (two levels up from tools/repeatable/)
$project_root = dirname(__DIR__, 2);

// Database connection
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// Output directory
$output_dir = __DIR__;
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Statistics
$stats = [
    'total_scanned' => 0,
    'missing_initially' => 0,
    'backfilled' => 0,
    'still_missing' => 0,
    'errors' => 0
];

// Data structures
$missing_characters = [];
$updated_characters = [];
$not_found_characters = [];
$update_log = [];

/**
 * Normalize character name for matching (case-insensitive, trimmed)
 */
function normalizeCharacterName(string $name): string {
    return trim(strtolower($name));
}

/**
 * Fuzzy match character names (handles variations)
 */
function fuzzyMatchNames(string $name1, string $name2): bool {
    $norm1 = normalizeCharacterName($name1);
    $norm2 = normalizeCharacterName($name2);
    
    // Exact match after normalization
    if ($norm1 === $norm2) {
        return true;
    }
    
    // Handle underscore/space variations
    $norm1_alt = str_replace(['_', '-'], ' ', $norm1);
    $norm2_alt = str_replace(['_', '-'], ' ', $norm2);
    if ($norm1_alt === $norm2_alt) {
        return true;
    }
    
    // Check if one contains the other (for partial matches)
    if (strpos($norm1, $norm2) !== false || strpos($norm2, $norm1) !== false) {
        // Only allow if both are substantial (avoid false matches)
        if (strlen($norm1) >= 5 && strlen($norm2) >= 5) {
            return true;
        }
    }
    
    return false;
}

/**
 * Clean string (trim, capitalize first letter of each word)
 */
function cleanString(string $str): string {
    $str = trim($str);
    // Capitalize first letter of each word
    return ucwords(strtolower($str));
}

/**
 * Clean integer (ensure it's a valid integer)
 */
function cleanInt($value): int {
    return (int)$value;
}

/**
 * Extract disciplines from JSON file
 */
function extractDisciplinesFromJson(string $filepath, string $target_character_name): ?array {
    if (!file_exists($filepath)) {
        return null;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        return null;
    }
    
    $data = json_decode($content, true);
    if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    // Check if character name matches (try both 'character_name' and 'name' fields)
    $file_character_name = $data['character_name'] ?? $data['name'] ?? '';
    if (empty($file_character_name) || !fuzzyMatchNames($file_character_name, $target_character_name)) {
        return null;
    }
    
    // Check for disciplines array
    if (!isset($data['disciplines']) || empty($data['disciplines'])) {
        return null;
    }
    
    $disciplines = $data['disciplines'];
    $result = [];
    
    // Format 1: Array of strings ["Auspex 5", "Dominate 3"] or ["Serpentis", "Presence"] (without levels)
    if (is_array($disciplines) && isset($disciplines[0]) && is_string($disciplines[0])) {
        foreach ($disciplines as $discStr) {
            // Try to parse "Discipline Name X" format
            if (preg_match('/^(.+?)\s+(\d+)$/', $discStr, $matches)) {
                $name = cleanString($matches[1]);
                $level = cleanInt($matches[2]);
                $level = max(1, min(5, $level));
                $result[] = ['name' => $name, 'level' => $level];
            } else {
                // Just discipline name without level - default to level 1
                $name = cleanString($discStr);
                if (!empty($name)) {
                    $result[] = ['name' => $name, 'level' => 1];
                }
            }
        }
    }
    // Format 2: Array of objects [{"name": "Auspex", "level": 5, "powers": [...]}]
    elseif (is_array($disciplines) && isset($disciplines[0]) && is_array($disciplines[0]) && isset($disciplines[0]['name'])) {
        foreach ($disciplines as $disc) {
            $name = cleanString($disc['name'] ?? '');
            $level = cleanInt($disc['level'] ?? 1);
            $level = max(1, min(5, $level));
            
            if (!empty($name)) {
                $result[] = ['name' => $name, 'level' => $level];
            }
        }
    }
    // Format 3: Object format {"celerity": 1, "potence": 2}
    elseif (is_array($disciplines) && !isset($disciplines[0]) && !empty($disciplines)) {
        foreach ($disciplines as $name => $level) {
            if (is_numeric($level)) {
                $name = cleanString($name);
                $level = cleanInt($level);
                $level = max(1, min(5, $level));
                $result[] = ['name' => $name, 'level' => $level];
            }
        }
    }
    
    return !empty($result) ? $result : null;
}

/**
 * Search for disciplines in JSON files
 */
function searchDisciplinesInJsonFiles(string $project_root, string $character_name): ?array {
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'json') {
                    try {
                        $filepath = $file->getPathname();
                        $disciplines = extractDisciplinesFromJson($filepath, $character_name);
                        
                        if ($disciplines !== null && !empty($disciplines)) {
                            return [
                                'source_file' => $filepath,
                                'disciplines' => $disciplines
                            ];
                        }
                    } catch (Exception $e) {
                        // Skip files that cause errors
                        continue;
                    } catch (Error $e) {
                        // Skip files that cause fatal errors
                        continue;
                    }
                }
            }
        } catch (Exception $e) {
            // Skip directories that cause errors
            continue;
        } catch (Error $e) {
            // Skip directories that cause fatal errors
            continue;
        }
    }
    
    return null;
}

/**
 * Insert disciplines into database
 */
function insertDisciplines(mysqli $conn, int $character_id, array $disciplines, bool $dry_run): bool {
    if (empty($disciplines)) {
        return false;
    }
    
    if ($dry_run) {
        return true; // Return true to indicate "would insert"
    }
    
    // Delete existing disciplines for this character (idempotency)
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM character_disciplines WHERE character_id = ?");
    if (!$delete_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($delete_stmt, 'i', $character_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    // Insert disciplines
    $insert_sql = "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        return false;
    }
    
    foreach ($disciplines as $disc) {
        $name = $disc['name'];
        $level = $disc['level'];
        
        mysqli_stmt_bind_param($insert_stmt, 'isi', $character_id, $name, $level);
        $success = mysqli_stmt_execute($insert_stmt);
        
        if (!$success) {
            mysqli_stmt_close($insert_stmt);
            return false;
        }
    }
    
    mysqli_stmt_close($insert_stmt);
    return true;
}

// Main execution
echo "=== Character Disciplines Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n\n";

// Step 1: Query all characters and detect missing disciplines
echo "Step 1: Scanning database for characters with missing disciplines...\n";

$query = "SELECT c.id, c.character_name, 
          (SELECT COUNT(*) FROM character_disciplines cd WHERE cd.character_id = c.id) as discipline_count
          FROM characters c
          ORDER BY c.id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
    
    $discipline_count = (int)$row['discipline_count'];
    if ($discipline_count === 0) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'discipline_count' => 0,
            'detected_at' => date('c')
        ];
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['missing_initially']} characters with missing disciplines\n\n";

// Step 2: Search for disciplines in files
echo "Step 2: Searching for disciplines in JSON files...\n";

foreach ($missing_characters as $char) {
    $character_id = $char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Search JSON files
    $json_result = searchDisciplinesInJsonFiles($project_root, $character_name);
    
    if ($json_result !== null) {
        $disciplines = $json_result['disciplines'];
        $source_file = $json_result['source_file'];
        $discipline_count = count($disciplines);
        
        // Insert into database
        $insert_success = insertDisciplines($conn, $character_id, $disciplines, $options['dry-run']);
        
        if ($insert_success) {
            $stats['backfilled']++;
            
            $discipline_list = array_map(function($d) {
                return $d['name'] . ' x' . $d['level'];
            }, $disciplines);
            $discipline_summary = implode(', ', $discipline_list);
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'discipline_count' => $discipline_count,
                'disciplines' => $disciplines,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s %d discipline(s) from %s\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would insert' : 'Inserted',
                $discipline_count,
                $source_file
            );
            $log_entry .= "  Disciplines: {$discipline_summary}\n";
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found {$discipline_count} discipline(s) in JSON: {$source_file}\n";
                echo "      {$discipline_summary}\n";
            }
            
            continue; // Skip to next character
        } else {
            $stats['errors']++;
            if ($options['verbose']) {
                echo "    ✗ Failed to insert disciplines into database\n";
            }
        }
    }
    
    // No disciplines found
    $stats['still_missing']++;
    $not_found_characters[] = [
        'id' => $character_id,
        'character_name' => $character_name,
        'searched_files' => [
            'reference/Characters/**/*.json',
            'agents/character_agent/data/Characters/**/*.json'
        ],
        'reason' => 'no_matching_files_found'
    ];
    
    if ($options['verbose']) {
        echo "    ✗ No disciplines found\n";
    }
}

echo "\n";

// Step 3: Generate reports
echo "Step 3: Generating reports...\n";

// Sort arrays for deterministic output
usort($missing_characters, function($a, $b) {
    return $a['id'] <=> $b['id'];
});

usort($updated_characters, function($a, $b) {
    return $a['id'] <=> $b['id'];
});

usort($not_found_characters, function($a, $b) {
    return $a['id'] <=> $b['id'];
});

// Generate missing disciplines report
$missing_report = [
    'generated_at' => date('c'),
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_disciplines_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_disciplines_report.json\n";

// Generate updates log
$log_path = $output_dir . "/disciplines_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: disciplines_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Disciplines Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: disciplines_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/disciplines_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: disciplines_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Disciplines Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Disciplines backfilled: {$stats['backfilled']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_disciplines_report.json\n";
echo "- tools/repeatable/disciplines_updates.log\n";
echo "- tools/repeatable/disciplines_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

