<?php
/**
 * Character Nature Backfill Script
 * 
 * Identifies characters with missing nature fields, searches JSON and Markdown files
 * for character nature, and updates database records with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_nature.php [options]
 * 
 * Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
 *   --min-length=N     Minimum nature length threshold (default: 3)
 *   --help             Show this help message
 * 
 * Output Files:
 *   - missing_nature_report.json    List of characters missing nature
 *   - nature_updates.log             Log of all database updates
 *   - nature_not_found.json          Characters where no nature was found
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
    'min-length' => 3,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (preg_match('/^--min-length=(\d+)$/', $arg, $matches)) {
        $options['min-length'] = (int)$matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Nature Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_nature.php [options]\n\n";
    echo "Options:\n";
    echo "  --dry-run          Show what would be updated without writing to database\n";
    echo "  --verbose          Show detailed progress for each character\n";
    echo "  --min-length=N     Minimum nature length threshold (default: 3)\n";
    echo "  --help             Show this help message\n\n";
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
 * Check if nature is considered missing
 */
function isNatureMissing(?string $nature): bool {
    if ($nature === null) {
        return true;
    }
    
    $trimmed = trim($nature);
    
    if ($trimmed === '') {
        return true;
    }
    
    // Check for placeholder patterns
    $placeholders = ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'Unknown'];
    $upper_trimmed = strtoupper($trimmed);
    foreach ($placeholders as $placeholder) {
        if ($upper_trimmed === strtoupper($placeholder)) {
            return true;
        }
    }
    
    return false;
}

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
 * Extract nature from JSON file
 */
function extractNatureFromJson(string $filepath, string $target_character_name): ?string {
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
    
    // Check if character name matches
    $file_character_name = $data['character_name'] ?? '';
    if (!fuzzyMatchNames($file_character_name, $target_character_name)) {
        return null;
    }
    
    // Extract nature - simple string field
    $nature = null;
    
    if (isset($data['nature']) && is_string($data['nature']) && trim($data['nature']) !== '') {
        $nature = trim($data['nature']);
    }
    
    return $nature;
}

/**
 * Extract nature from Markdown file
 */
function extractNatureFromMarkdown(string $filepath, string $target_character_name): ?string {
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
    
    // Search for nature sections - look for patterns like "Nature: X" or "## Nature\nX"
    $patterns = [
        // Pattern 1: "Nature: X" or "Nature: X\n" on a line
        ['pattern' => '/^Nature:\s*([^\n]+)/im', 'group' => 1],
        ['pattern' => '/^#\s*Nature:\s*([^\n]+)/im', 'group' => 1],
        // Pattern 2: "## Nature\nX" where X is on the next line
        ['pattern' => '/^##\s*Nature\s*\n+?([^\n#]+)/im', 'group' => 1],
        ['pattern' => '/^#\s*Nature\s*\n+?([^\n#]+)/im', 'group' => 1],
        // Pattern 3: Look for "Nature" followed by colon and value
        ['pattern' => '/Nature[:\s]+([A-Za-z][A-Za-z\s]+?)(?:\n|$)/im', 'group' => 1]
    ];
    
    $extracted_text = null;
    foreach ($patterns as $pattern_info) {
        if (preg_match($pattern_info['pattern'], $content, $matches)) {
            $group_index = $pattern_info['group'];
            $extracted_text = $matches[$group_index] ?? '';
            $extracted_text = trim($extracted_text);
            // Nature is typically a single word or short phrase, so take first line/word
            if (strpos($extracted_text, "\n") !== false) {
                $extracted_text = trim(explode("\n", $extracted_text)[0]);
            }
            if (trim($extracted_text) !== '') {
                break;
            }
        }
    }
    
    if ($extracted_text === null || trim($extracted_text) === '') {
        return null;
    }
    
    // Clean up - nature should be a single word or short phrase
    $cleaned = trim($extracted_text);
    // Remove any markdown formatting
    $cleaned = preg_replace('/\*\*([^\*]+)\*\*/', '$1', $cleaned);
    $cleaned = preg_replace('/\*([^\*]+)\*/', '$1', $cleaned);
    $cleaned = preg_replace('/_([^_]+)_/', '$1', $cleaned);
    // Take only the first word/phrase (nature is typically one word)
    $cleaned = preg_split('/[\s,;]+/', $cleaned)[0];
    $cleaned = trim($cleaned);
    
    return $cleaned;
}

/**
 * Search for nature in JSON files
 */
function searchJsonFiles(string $project_root, string $character_name, int $min_length): ?array {
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        // Use RecursiveDirectoryIterator for subdirectories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $filepath = $file->getPathname();
                $nature = extractNatureFromJson($filepath, $character_name);
                
                if ($nature !== null && strlen($nature) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'nature' => $nature,
                        'length' => strlen($nature)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Search for nature in Markdown files
 */
function searchMarkdownFiles(string $project_root, string $character_name, int $min_length): ?array {
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        // Use RecursiveDirectoryIterator for subdirectories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $filepath = $file->getPathname();
                $nature = extractNatureFromMarkdown($filepath, $character_name);
                
                if ($nature !== null && strlen($nature) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'nature' => $nature,
                        'length' => strlen($nature)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Update character nature in database
 */
function updateCharacterNature(mysqli $conn, int $character_id, string $nature, bool $dry_run): bool {
    // Re-check that nature is still empty (idempotency safeguard)
    $check_stmt = mysqli_prepare($conn, "SELECT nature FROM characters WHERE id = ?");
    if (!$check_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($check_stmt, 'i', $character_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check_stmt);
    
    if ($row === null) {
        return false;
    }
    
    // If nature is no longer empty, skip update
    if (!isNatureMissing($row['nature'])) {
        return false;
    }
    
    if ($dry_run) {
        return true; // Return true to indicate "would update"
    }
    
    // Update with safeguard condition
    $update_stmt = mysqli_prepare($conn, 
        "UPDATE characters SET nature = ? WHERE id = ? AND (nature IS NULL OR nature = '' OR TRIM(nature) = '')"
    );
    
    if (!$update_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($update_stmt, 'si', $nature, $character_id);
    $success = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    return $success;
}

// Main execution
echo "=== Character Nature Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Min length: {$options['min-length']} characters\n\n";

// Step 1: Query all characters and detect missing nature
echo "Step 1: Scanning database for characters with missing nature...\n";

$query = "SELECT id, character_name, nature FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
    
    if (isNatureMissing($row['nature'])) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'reason' => $row['nature'] === null ? 'null' : 
                       (trim($row['nature']) === '' ? 'empty_string' : 'placeholder'),
            'detected_at' => date('c')
        ];
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['missing_initially']} characters with missing nature\n\n";

// Step 2: Search for nature in files
echo "Step 2: Searching for nature in JSON and Markdown files...\n";

foreach ($missing_characters as $char) {
    $character_id = $char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Pass A: Search JSON files
    $json_result = searchJsonFiles($project_root, $character_name, $options['min-length']);
    
    if ($json_result !== null) {
        $nature = $json_result['nature'];
        $source_file = $json_result['source_file'];
        $length = $json_result['length'];
        
        // Update database
        $update_success = updateCharacterNature($conn, $character_id, $nature, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($nature), 0, 8);
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'length' => $length,
                'hash' => $hash,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s from %s (length: %d chars, hash: %s)\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would update' : 'Updated',
                $source_file,
                $length,
                $hash
            );
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found in JSON: {$source_file}\n";
            }
            
            continue; // Skip to next character
        } else {
            $stats['errors']++;
            if ($options['verbose']) {
                echo "    ✗ Failed to update database\n";
            }
        }
    }
    
    // Pass B: Search Markdown files
    $md_result = searchMarkdownFiles($project_root, $character_name, $options['min-length']);
    
    if ($md_result !== null) {
        $nature = $md_result['nature'];
        $source_file = $md_result['source_file'];
        $length = $md_result['length'];
        
        // Update database
        $update_success = updateCharacterNature($conn, $character_id, $nature, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($nature), 0, 8);
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'length' => $length,
                'hash' => $hash,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s from %s (length: %d chars, hash: %s)\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would update' : 'Updated',
                $source_file,
                $length,
                $hash
            );
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found in Markdown: {$source_file}\n";
            }
            
            continue; // Skip to next character
        } else {
            $stats['errors']++;
            if ($options['verbose']) {
                echo "    ✗ Failed to update database\n";
            }
        }
    }
    
    // No nature found
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
        'reason' => 'no_matching_files_found'
    ];
    
    if ($options['verbose']) {
        echo "    ✗ No nature found\n";
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

// Generate missing_nature_report.json
$missing_report = [
    'generated_at' => date('c'),
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . '/missing_nature_report.json';
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_nature_report.json\n";

// Generate nature_updates.log
$log_path = $output_dir . '/nature_updates.log';
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: nature_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Nature Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: nature_updates.log (empty)\n";
}

// Generate nature_not_found.json
$not_found_report = [
    'generated_at' => date('c'),
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . '/nature_not_found.json';
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: nature_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Nature Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Natures backfilled: {$stats['backfilled']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Skipped (already had nature): {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_nature_report.json\n";
echo "- tools/repeatable/nature_updates.log\n";
echo "- tools/repeatable/nature_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

