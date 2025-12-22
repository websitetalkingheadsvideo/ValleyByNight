<?php
/**
 * Character Backgrounds Backfill Script
 * 
 * Identifies characters with missing backgrounds in the character_backgrounds table,
 * searches JSON and Markdown files for background data, and updates database records.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_backgrounds.php [options]
 * 
 * Optional Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
 *   --min-level=N      Minimum background level to import (default: 1, use 0 to include all)
 *   --help             Show this help message
 * 
 * Output Files:
 *   - missing_backgrounds_report.json    List of characters missing backgrounds
 *   - backgrounds_updates.log            Log of all database updates
 *   - backgrounds_not_found.json         Characters where no backgrounds were found
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
    'min-level' => 1,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (preg_match('/^--min-level=(\d+)$/', $arg, $matches)) {
        $options['min-level'] = (int)$matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Backgrounds Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_backgrounds.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --dry-run          Show what would be updated without writing to database\n";
    echo "  --verbose          Show detailed progress for each character\n";
    echo "  --min-level=N      Minimum background level to import (default: 1, use 0 to include all)\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_character_backgrounds.php --dry-run\n";
    echo "  php tools/repeatable/backfill_character_backgrounds.php --verbose --min-level=0\n\n";
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
    'skipped' => 0,
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
 * Extract backgrounds from JSON file
 */
function extractBackgroundsFromJson(string $filepath, string $target_character_name): ?array {
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
    
    // Check if character name matches (try both character_name and name fields)
    $file_character_name = $data['character_name'] ?? $data['name'] ?? '';
    if (empty($file_character_name) || !fuzzyMatchNames($file_character_name, $target_character_name)) {
        return null;
    }
    
    // Extract backgrounds
    $backgrounds = $data['backgrounds'] ?? null;
    
    // Handle both array and object formats
    if ($backgrounds === null) {
        return null;
    }
    
    // If it's an array, check if it's empty or convert to object format
    if (is_array($backgrounds) && isset($backgrounds[0])) {
        // It's a numeric array (like [] or [{...}])
        if (empty($backgrounds)) {
            return null;
        }
        // If it has elements, try to parse as array of objects
        // For now, treat numeric arrays as empty (they're usually empty arrays from database exports)
        return null;
    }
    
    // It should be an object/associative array (name => level pairs)
    if (!is_array($backgrounds) || empty($backgrounds)) {
        return null;
    }
    
    // Extract background details
    $backgroundDetails = $data['backgroundDetails'] ?? [];
    
    // Handle backgroundDetails being an array instead of object
    if (is_array($backgroundDetails) && isset($backgroundDetails[0])) {
        // It's a numeric array, convert to empty object
        $backgroundDetails = [];
    }
    
    // Build result array
    $result = [];
    foreach ($backgrounds as $name => $level) {
        if (is_numeric($level)) {
            $level = (int)$level;
            $result[] = [
                'name' => trim((string)$name),
                'level' => $level,
                'description' => isset($backgroundDetails[$name]) && is_string($backgroundDetails[$name]) ? trim((string)$backgroundDetails[$name]) : null
            ];
        }
    }
    
    if (empty($result)) {
        return null;
    }
    
    return [
        'source_file' => $filepath,
        'backgrounds' => $result
    ];
}

/**
 * Extract backgrounds from Markdown file (basic pattern matching)
 */
function extractBackgroundsFromMarkdown(string $filepath, string $target_character_name): ?array {
    if (!file_exists($filepath)) {
        return null;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        return null;
    }
    
    // Check filename match (without extension)
    $filename = basename($filepath, '.md');
    $filename_variations = [
        $filename,
        str_replace('_', ' ', $filename),
        str_replace('-', ' ', $filename),
        preg_replace('/^character_history_/i', '', $filename),
        preg_replace('/^character_/i', '', $filename),
        preg_replace('/_history$/i', '', $filename)
    ];
    
    $filename_matches = false;
    foreach ($filename_variations as $variation) {
        if (fuzzyMatchNames($variation, $target_character_name)) {
            $filename_matches = true;
            break;
        }
    }
    
    // Also check for character name in content
    $content_lower = strtolower($content);
    $target_lower = strtolower($target_character_name);
    $content_matches = strpos($content_lower, $target_lower) !== false;
    
    if (!$filename_matches && !$content_matches) {
        return null;
    }
    
    // Try to extract backgrounds from markdown
    // Look for patterns like "Backgrounds:", "## Backgrounds", etc.
    $backgrounds = [];
    
    // Pattern 1: Look for "Backgrounds:" section
    if (preg_match('/^#+\s*Backgrounds:?\s*\n+?(.*?)(?=\n#+|\Z)/ims', $content, $section_match)) {
        $section_content = $section_match[1];
        
        // Extract individual backgrounds (name: level format)
        if (preg_match_all('/(?:^|\n)\s*([A-Za-z][A-Za-z\s]+?):\s*(\d+)/m', $section_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = trim($match[1]);
                $level = (int)$match[2];
                $backgrounds[] = [
                    'name' => $name,
                    'level' => $level,
                    'description' => null
                ];
            }
        }
    }
    
    if (empty($backgrounds)) {
        return null;
    }
    
    return [
        'source_file' => $filepath,
        'backgrounds' => $backgrounds
    ];
}

/**
 * Search for backgrounds in JSON files
 */
function searchJsonFiles(string $project_root, string $character_name): ?array {
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $filepath = $file->getPathname();
                $result = extractBackgroundsFromJson($filepath, $character_name);
                
                if ($result !== null) {
                    return $result;
                }
            }
        }
    }
    
    return null;
}

/**
 * Search for backgrounds in Markdown files
 */
function searchMarkdownFiles(string $project_root, string $character_name): ?array {
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $filepath = $file->getPathname();
                $result = extractBackgroundsFromMarkdown($filepath, $character_name);
                
                if ($result !== null) {
                    return $result;
                }
            }
        }
    }
    
    return null;
}

/**
 * Update character backgrounds in database
 */
function updateCharacterBackgrounds(mysqli $conn, int $character_id, array $backgrounds, int $min_level, bool $dry_run): bool {
    if (empty($backgrounds)) {
        return false;
    }
    
    // Re-check that character has no backgrounds (idempotency safeguard)
    $check_query = "SELECT COUNT(*) as count FROM character_backgrounds WHERE character_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    if (!$check_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($check_stmt, 'i', $character_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check_stmt);
    
    if ($row === null || (int)$row['count'] > 0) {
        // Character already has backgrounds, skip
        return false;
    }
    
    if ($dry_run) {
        return true; // Return true to indicate "would update"
    }
    
    // Insert backgrounds
    $insert_sql = "INSERT INTO character_backgrounds (character_id, background_name, level, description) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$stmt) {
        return false;
    }
    
    $success = true;
    foreach ($backgrounds as $bg) {
        // Filter by min_level
        if ($bg['level'] < $min_level) {
            continue;
        }
        
        $name = $bg['name'];
        $level = $bg['level'];
        $description = $bg['description'] ?? null;
        
        mysqli_stmt_bind_param($stmt, 'isis', $character_id, $name, $level, $description);
        if (!mysqli_stmt_execute($stmt)) {
            $success = false;
            break;
        }
    }
    
    mysqli_stmt_close($stmt);
    
    return $success;
}

// Main execution
echo "=== Character Backgrounds Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Min level: {$options['min-level']}\n\n";

// Step 1: Query all characters and detect missing backgrounds
echo "Step 1: Scanning database for characters with missing backgrounds...\n";

// Get all characters and check which ones have no backgrounds
$query = "SELECT c.id, c.character_name, COUNT(cb.background_name) as background_count
          FROM characters c
          LEFT JOIN character_backgrounds cb ON c.id = cb.character_id
          GROUP BY c.id, c.character_name
          ORDER BY c.id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
    
    $background_count = (int)$row['background_count'];
    if ($background_count === 0) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'reason' => 'no_backgrounds_in_table',
            'detected_at' => date('c')
        ];
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['missing_initially']} characters with missing backgrounds\n\n";

// Step 2: Search for backgrounds in files
echo "Step 2: Searching for backgrounds in JSON and Markdown files...\n";

foreach ($missing_characters as $char) {
    $character_id = $char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Pass A: Search JSON files
    $json_result = searchJsonFiles($project_root, $character_name);
    
    if ($json_result !== null) {
        $backgrounds = $json_result['backgrounds'];
        $source_file = $json_result['source_file'];
        
        // Filter backgrounds by min_level for counting
        $filtered_backgrounds = array_filter($backgrounds, function($bg) use ($options) {
            return $bg['level'] >= $options['min-level'];
        });
        
        if (!empty($filtered_backgrounds)) {
            // Update database
            $update_success = updateCharacterBackgrounds($conn, $character_id, $backgrounds, $options['min-level'], $options['dry-run']);
            
            if ($update_success) {
                $bg_count = count($filtered_backgrounds);
                $stats['backfilled']++;
                
                $updated_characters[] = [
                    'id' => $character_id,
                    'character_name' => $character_name,
                    'source_file' => $source_file,
                    'background_count' => $bg_count,
                    'updated_at' => date('c')
                ];
                
                $bg_list = array_map(function($bg) {
                    return "{$bg['name']} ({$bg['level']})";
                }, $filtered_backgrounds);
                $bg_list_str = implode(', ', $bg_list);
                
                $log_entry = sprintf(
                    "[%s] Character ID %d (%s): %s %d backgrounds from %s (%s)\n",
                    date('Y-m-d H:i:s'),
                    $character_id,
                    $character_name,
                    $options['dry-run'] ? 'Would update' : 'Updated',
                    $bg_count,
                    $source_file,
                    $bg_list_str
                );
                $update_log[] = $log_entry;
                
                if ($options['verbose']) {
                    echo "    ✓ Found {$bg_count} backgrounds in JSON: {$source_file}\n";
                    echo "      Backgrounds: {$bg_list_str}\n";
                }
                
                continue; // Skip to next character
            } else {
                $stats['errors']++;
                if ($options['verbose']) {
                    echo "    ✗ Failed to update database\n";
                }
            }
        }
    }
    
    // Pass B: Search Markdown files
    $md_result = searchMarkdownFiles($project_root, $character_name);
    
    if ($md_result !== null) {
        $backgrounds = $md_result['backgrounds'];
        $source_file = $md_result['source_file'];
        
        // Filter backgrounds by min_level for counting
        $filtered_backgrounds = array_filter($backgrounds, function($bg) use ($options) {
            return $bg['level'] >= $options['min-level'];
        });
        
        if (!empty($filtered_backgrounds)) {
            // Update database
            $update_success = updateCharacterBackgrounds($conn, $character_id, $backgrounds, $options['min-level'], $options['dry-run']);
            
            if ($update_success) {
                $bg_count = count($filtered_backgrounds);
                $stats['backfilled']++;
                
                $updated_characters[] = [
                    'id' => $character_id,
                    'character_name' => $character_name,
                    'source_file' => $source_file,
                    'background_count' => $bg_count,
                    'updated_at' => date('c')
                ];
                
                $bg_list = array_map(function($bg) {
                    return "{$bg['name']} ({$bg['level']})";
                }, $filtered_backgrounds);
                $bg_list_str = implode(', ', $bg_list);
                
                $log_entry = sprintf(
                    "[%s] Character ID %d (%s): %s %d backgrounds from %s (%s)\n",
                    date('Y-m-d H:i:s'),
                    $character_id,
                    $character_name,
                    $options['dry-run'] ? 'Would update' : 'Updated',
                    $bg_count,
                    $source_file,
                    $bg_list_str
                );
                $update_log[] = $log_entry;
                
                if ($options['verbose']) {
                    echo "    ✓ Found {$bg_count} backgrounds in Markdown: {$source_file}\n";
                    echo "      Backgrounds: {$bg_list_str}\n";
                }
                
                continue; // Skip to next character
            } else {
                $stats['errors']++;
                if ($options['verbose']) {
                    echo "    ✗ Failed to update database\n";
                }
            }
        }
    }
    
    // No backgrounds found
    $stats['still_missing']++;
    $not_found_characters[] = [
        'id' => $character_id,
        'character_name' => $character_name,
        'searched_files' => [
            'reference/Characters/**/*.json',
            'reference/Characters/**/*.md',
            'agents/character_agent/data/Characters/**/*.json',
            'agents/character_agent/data/Characters/**/*.md'
        ],
        'reason' => 'no_backgrounds_found_in_files'
    ];
    
    if ($options['verbose']) {
        echo "    ✗ No backgrounds found\n";
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

// Generate missing backgrounds report
$missing_report = [
    'generated_at' => date('c'),
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_backgrounds_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_backgrounds_report.json\n";

// Generate updates log
$log_path = $output_dir . "/backgrounds_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: backgrounds_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Backgrounds Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: backgrounds_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/backgrounds_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: backgrounds_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Backgrounds Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Backgrounds backfilled: {$stats['backfilled']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Skipped (already had backgrounds): {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_backgrounds_report.json\n";
echo "- tools/repeatable/backgrounds_updates.log\n";
echo "- tools/repeatable/backgrounds_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

