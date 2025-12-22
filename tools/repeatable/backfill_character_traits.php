<?php
/**
 * Character Traits Backfill Script
 * 
 * Identifies characters with missing traits, searches JSON files for trait data,
 * and updates the character_traits table with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_traits.php [options]
 * 
 * Optional Options:
 *   --category=<name>    Backfill specific category only (Physical, Social, Mental) or 'all' for all
 *   --dry-run           Show what would be updated without writing to database
 *   --verbose           Show detailed progress for each character
 *   --force             Replace existing traits (default: only add missing)
 *   --help              Show this help message
 * 
 * Output Files:
 *   - missing_traits_report.json    List of characters missing traits
 *   - traits_updates.log            Log of all database updates
 *   - traits_not_found.json         Characters where no traits were found
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
    'category' => 'all',
    'dry-run' => false,
    'verbose' => false,
    'force' => false,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif (preg_match('/^--category=(.+)$/', $arg, $matches)) {
        $options['category'] = $matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Traits Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_traits.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --category=<name>    Backfill specific category (Physical, Social, Mental) or 'all' (default)\n";
    echo "  --dry-run            Show what would be updated without writing to database\n";
    echo "  --verbose            Show detailed progress for each character\n";
    echo "  --force              Replace existing traits (default: only add missing)\n";
    echo "  --help               Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_character_traits.php --category=Physical --dry-run\n";
    echo "  php tools/repeatable/backfill_character_traits.php --category=all --verbose\n";
    echo "  php tools/repeatable/backfill_character_traits.php --force\n\n";
    exit(0);
}

$allowed_categories = ['Physical', 'Social', 'Mental'];
$target_categories = [];

if ($options['category'] === 'all') {
    $target_categories = $allowed_categories;
} else {
    $normalized = ucfirst(strtolower($options['category']));
    if (in_array($normalized, $allowed_categories, true)) {
        $target_categories = [$normalized];
    } else {
        die("ERROR: Invalid category '{$options['category']}'. Must be one of: " . implode(', ', $allowed_categories) . ", or 'all'\n");
    }
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
    'traits_backfilled' => 0,
    'characters_updated' => 0,
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
 * Clean trait name (remove extra whitespace, normalize)
 */
function cleanTraitName(string $name): string {
    return trim($name);
}

/**
 * Extract traits from JSON file
 */
function extractTraitsFromJson(string $filepath, string $target_character_name): ?array {
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
    
    $result = [
        'positive' => [],
        'negative' => []
    ];
    
    // Extract positive traits
    if (isset($data['traits']) && is_array($data['traits'])) {
        foreach (['Physical', 'Social', 'Mental'] as $category) {
            $normalized_category = ucfirst(strtolower($category));
            $category_key = $category; // Try exact match first
            if (!isset($data['traits'][$category_key])) {
                $category_key = $normalized_category; // Try normalized
            }
            if (!isset($data['traits'][$category_key])) {
                $category_key = strtolower($category); // Try lowercase
            }
            
            if (isset($data['traits'][$category_key]) && is_array($data['traits'][$category_key])) {
                foreach ($data['traits'][$category_key] as $trait) {
                    $clean_trait = cleanTraitName($trait);
                    if ($clean_trait !== '') {
                        $result['positive'][] = [
                            'category' => $normalized_category,
                            'name' => $clean_trait
                        ];
                    }
                }
            }
        }
    }
    
    // Extract negative traits
    if (isset($data['negativeTraits']) && is_array($data['negativeTraits'])) {
        foreach (['Physical', 'Social', 'Mental'] as $category) {
            $normalized_category = ucfirst(strtolower($category));
            $category_key = $category; // Try exact match first
            if (!isset($data['negativeTraits'][$category_key])) {
                $category_key = $normalized_category; // Try normalized
            }
            if (!isset($data['negativeTraits'][$category_key])) {
                $category_key = strtolower($category); // Try lowercase
            }
            
            if (isset($data['negativeTraits'][$category_key]) && is_array($data['negativeTraits'][$category_key])) {
                foreach ($data['negativeTraits'][$category_key] as $trait) {
                    $clean_trait = cleanTraitName($trait);
                    if ($clean_trait !== '') {
                        $result['negative'][] = [
                            'category' => $normalized_category,
                            'name' => $clean_trait
                        ];
                    }
                }
            }
        }
    }
    
    return (count($result['positive']) > 0 || count($result['negative']) > 0) ? $result : null;
}

/**
 * Search for traits in JSON files
 */
function searchTraitJsonFiles(string $project_root, string $character_name, array $target_categories): ?array {
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
                $traits = extractTraitsFromJson($filepath, $character_name);
                
                if ($traits !== null) {
                    // Filter by target categories
                    $filtered = [
                        'positive' => array_filter($traits['positive'], function($trait) use ($target_categories) {
                            return in_array($trait['category'], $target_categories, true);
                        }),
                        'negative' => array_filter($traits['negative'], function($trait) use ($target_categories) {
                            return in_array($trait['category'], $target_categories, true);
                        })
                    ];
                    
                    if (count($filtered['positive']) > 0 || count($filtered['negative']) > 0) {
                        return [
                            'source_file' => $filepath,
                            'traits' => $filtered
                        ];
                    }
                }
            }
        }
    }
    
    return null;
}

/**
 * Get existing traits for a character
 */
function getExistingTraits(mysqli $conn, int $character_id, array $categories, string $trait_type = 'positive'): array {
    $existing = [];
    
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $sql = "SELECT trait_name, trait_category 
            FROM character_traits 
            WHERE character_id = ? AND trait_type = ? AND trait_category IN ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $existing;
    }
    
    $types = 'is' . str_repeat('s', count($categories));
    $params = array_merge([$character_id, $trait_type], $categories);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['trait_category'] . '|' . $row['trait_name'];
        $existing[$key] = true;
    }
    
    mysqli_stmt_close($stmt);
    return $existing;
}

/**
 * Insert traits into database
 */
function insertTraits(mysqli $conn, int $character_id, array $traits, string $trait_type, bool $dry_run, bool $force): int {
    if (count($traits) === 0) {
        return 0;
    }
    
    $inserted = 0;
    
    // Get existing traits if not forcing
    $existing = [];
    if (!$force) {
        $categories = array_unique(array_column($traits, 'category'));
        $existing = getExistingTraits($conn, $character_id, $categories, $trait_type);
    }
    
    if ($dry_run) {
        // Count what would be inserted
        foreach ($traits as $trait) {
            $key = $trait['category'] . '|' . $trait['name'];
            if (!isset($existing[$key])) {
                $inserted++;
            }
        }
        return $inserted;
    }
    
    // Delete existing traits if forcing
    if ($force) {
        $categories = array_unique(array_column($traits, 'category'));
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $delete_sql = "DELETE FROM character_traits WHERE character_id = ? AND trait_type = ? AND trait_category IN ($placeholders)";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        if ($delete_stmt) {
            $types = 'is' . str_repeat('s', count($categories));
            $params = array_merge([$character_id, $trait_type], $categories);
            mysqli_stmt_bind_param($delete_stmt, $types, ...$params);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }
    }
    
    // Insert new traits
    $insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        return 0;
    }
    
    foreach ($traits as $trait) {
        $key = $trait['category'] . '|' . $trait['name'];
        
        // Skip if already exists and not forcing
        if (!$force && isset($existing[$key])) {
            continue;
        }
        
        mysqli_stmt_bind_param($insert_stmt, 'isss', 
            $character_id,
            $trait['name'],
            $trait['category'],
            $trait_type
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $inserted++;
        }
    }
    
    mysqli_stmt_close($insert_stmt);
    return $inserted;
}

// Main execution
echo "=== Character Traits Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Categories: " . implode(', ', $target_categories) . "\n";
echo "Force mode: " . ($options['force'] ? "YES (replace existing)" : "NO (add missing only)") . "\n\n";

// Step 1: Query all characters
echo "Step 1: Scanning database for characters...\n";

$query = "SELECT id, character_name FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
}

echo "Found {$stats['total_scanned']} total characters\n\n";

// Step 2: Check for missing traits and search JSON files
echo "Step 2: Checking for missing traits and searching JSON files...\n";

foreach ($all_characters as $char) {
    $character_id = (int)$char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Check existing traits
    $existing_positive = getExistingTraits($conn, $character_id, $target_categories, 'positive');
    $existing_negative = getExistingTraits($conn, $character_id, $target_categories, 'negative');
    
    $has_traits = (count($existing_positive) > 0 || count($existing_negative) > 0);
    
    if (!$has_traits) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => $character_id,
            'character_name' => $character_name,
            'detected_at' => date('c')
        ];
    }
    
    // Search for traits in JSON files
    $json_result = searchTraitJsonFiles($project_root, $character_name, $target_categories);
    
    if ($json_result !== null) {
        $source_file = $json_result['source_file'];
        $traits_data = $json_result['traits'];
        
        $positive_traits = $traits_data['positive'] ?? [];
        $negative_traits = $traits_data['negative'] ?? [];
        
        $positive_count = insertTraits($conn, $character_id, $positive_traits, 'positive', $options['dry-run'], $options['force']);
        $negative_count = insertTraits($conn, $character_id, $negative_traits, 'negative', $options['dry-run'], $options['force']);
        
        $total_inserted = $positive_count + $negative_count;
        
        if ($total_inserted > 0) {
            $stats['traits_backfilled'] += $total_inserted;
            $stats['characters_updated']++;
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'positive_traits' => $positive_count,
                'negative_traits' => $negative_count,
                'total_traits' => $total_inserted,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s %d traits (%d positive, %d negative) from %s\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would add' : 'Added',
                $total_inserted,
                $positive_count,
                $negative_count,
                $source_file
            );
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found traits in JSON: {$source_file} ({$total_inserted} traits)\n";
            }
        } else {
            if ($options['verbose']) {
                echo "    - Traits found but already exist (use --force to replace)\n";
            }
        }
    } else {
        if (!$has_traits) {
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
        }
        
        if ($options['verbose']) {
            echo "    ✗ No traits found\n";
        }
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

// Generate missing traits report
$missing_report = [
    'generated_at' => date('c'),
    'categories' => $target_categories,
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_traits_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_traits_report.json\n";

// Generate updates log
$log_path = $output_dir . "/traits_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: traits_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Traits Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: traits_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'categories' => $target_categories,
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/traits_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: traits_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Traits Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Traits backfilled: {$stats['traits_backfilled']}\n";
echo "Characters updated: {$stats['characters_updated']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_traits_report.json\n";
echo "- tools/repeatable/traits_updates.log\n";
echo "- tools/repeatable/traits_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

