<?php
/**
 * Character Merits & Flaws Backfill Script
 * 
 * Identifies characters with missing merits/flaws, searches JSON files for merit/flaw data,
 * and updates the character_merits_flaws table with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_merits_flaws.php [options]
 * 
 * Optional Options:
 *   --type=<name>        Backfill specific type only (Merit, Flaw) or 'all' for all
 *   --category=<name>    Backfill specific category only (Physical, Social, Mental, Supernatural) or 'all' for all
 *   --dry-run           Show what would be updated without writing to database
 *   --verbose           Show detailed progress for each character
 *   --force             Replace existing merits/flaws (default: only add missing)
 *   --help              Show this help message
 * 
 * Output Files:
 *   - missing_merits_flaws_report.json    List of characters missing merits/flaws
 *   - merits_flaws_updates.log            Log of all database updates
 *   - merits_flaws_not_found.json         Characters where no merits/flaws were found
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
    'type' => 'all',
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
    } elseif (preg_match('/^--type=(.+)$/', $arg, $matches)) {
        $options['type'] = $matches[1];
    } elseif (preg_match('/^--category=(.+)$/', $arg, $matches)) {
        $options['category'] = $matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Merits & Flaws Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_merits_flaws.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --type=<name>         Backfill specific type (Merit, Flaw) or 'all' (default)\n";
    echo "  --category=<name>     Backfill specific category (Physical, Social, Mental, Supernatural) or 'all' (default)\n";
    echo "  --dry-run             Show what would be updated without writing to database\n";
    echo "  --verbose             Show detailed progress for each character\n";
    echo "  --force               Replace existing merits/flaws (default: only add missing)\n";
    echo "  --help                Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_character_merits_flaws.php --type=Merit --dry-run\n";
    echo "  php tools/repeatable/backfill_character_merits_flaws.php --category=Physical --verbose\n";
    echo "  php tools/repeatable/backfill_character_merits_flaws.php --force\n\n";
    exit(0);
}

$allowed_types = ['Merit', 'Flaw'];
$target_types = [];

if ($options['type'] === 'all') {
    $target_types = $allowed_types;
} else {
    $normalized = ucfirst(strtolower($options['type']));
    if (in_array($normalized, $allowed_types, true)) {
        $target_types = [$normalized];
    } else {
        die("ERROR: Invalid type '{$options['type']}'. Must be one of: " . implode(', ', $allowed_types) . ", or 'all'\n");
    }
}

$allowed_categories = ['Physical', 'Social', 'Mental', 'Supernatural'];
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
    'merits_flaws_backfilled' => 0,
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
 * Extract merits/flaws from JSON file
 * Format: Array of objects: [{name, type, category, cost, description}, ...]
 */
function extractMeritsFlawsFromJson(string $filepath, string $target_character_name, array $target_types, array $target_categories): ?array {
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
    
    $merits_flaws = [];
    
    // Check for merits_flaws array
    if (!isset($data['merits_flaws']) || !is_array($data['merits_flaws'])) {
        return null;
    }
    
    foreach ($data['merits_flaws'] as $item) {
        if (!is_array($item) || !isset($item['name'])) {
            continue;
        }
        
        // Normalize type (handle lowercase "merit"/"flaw")
        $type = ucfirst(strtolower($item['type'] ?? 'Merit'));
        if ($type !== 'Merit' && $type !== 'Flaw') {
            $type = 'Merit';
        }
        
        // Filter by type
        if (!in_array($type, $target_types, true)) {
            continue;
        }
        
        // Normalize category
        $category = ucfirst(strtolower($item['category'] ?? ''));
        if ($category === '') {
            continue;
        }
        
        // Filter by category
        if (!in_array($category, $target_categories, true)) {
            continue;
        }
        
        $name = trim($item['name'] ?? '');
        if ($name === '') {
            continue;
        }
        
        // Get cost/point_value (handle both field names)
        $cost = isset($item['cost']) ? (int)$item['cost'] : (isset($item['point_value']) ? (int)$item['point_value'] : 0);
        $point_value = isset($item['point_value']) ? (int)$item['point_value'] : $cost;
        $point_cost = isset($item['point_cost']) ? (int)$item['point_cost'] : $cost;
        
        $description = trim($item['description'] ?? '');
        
        $merits_flaws[] = [
            'name' => $name,
            'type' => $type,
            'category' => $category,
            'point_value' => $point_value,
            'point_cost' => $point_cost,
            'description' => $description
        ];
    }
    
    return count($merits_flaws) > 0 ? $merits_flaws : null;
}

/**
 * Search for merits/flaws in JSON files
 */
function searchMeritsFlawsJsonFiles(string $project_root, string $character_name, array $target_types, array $target_categories): ?array {
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
                $merits_flaws = extractMeritsFlawsFromJson($filepath, $character_name, $target_types, $target_categories);
                
                if ($merits_flaws !== null && count($merits_flaws) > 0) {
                    return [
                        'source_file' => $filepath,
                        'merits_flaws' => $merits_flaws
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Get existing merits/flaws for a character
 */
function getExistingMeritsFlaws(mysqli $conn, int $character_id, array $types, array $categories): array {
    $existing = [];
    
    $type_placeholders = str_repeat('?,', count($types) - 1) . '?';
    $category_placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $sql = "SELECT name, type, category 
            FROM character_merits_flaws 
            WHERE character_id = ? AND type IN ($type_placeholders) AND category IN ($category_placeholders)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $existing;
    }
    
    $types_str = 'i' . str_repeat('s', count($types)) . str_repeat('s', count($categories));
    $params = array_merge([$character_id], $types, $categories);
    mysqli_stmt_bind_param($stmt, $types_str, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $key = ($row['type'] ?? '') . '|' . ($row['category'] ?? '') . '|' . $row['name'];
        $existing[$key] = true;
    }
    
    mysqli_stmt_close($stmt);
    return $existing;
}

/**
 * Insert merits/flaws into database
 */
function insertMeritsFlaws(mysqli $conn, int $character_id, array $merits_flaws, bool $dry_run, bool $force): int {
    if (count($merits_flaws) === 0) {
        return 0;
    }
    
    $inserted = 0;
    
    // Get existing merits/flaws if not forcing
    $existing = [];
    if (!$force) {
        $types = array_unique(array_column($merits_flaws, 'type'));
        $categories = array_unique(array_column($merits_flaws, 'category'));
        $existing = getExistingMeritsFlaws($conn, $character_id, $types, $categories);
    }
    
    if ($dry_run) {
        // Count what would be inserted
        foreach ($merits_flaws as $mf) {
            $key = $mf['type'] . '|' . $mf['category'] . '|' . $mf['name'];
            if (!isset($existing[$key])) {
                $inserted++;
            }
        }
        return $inserted;
    }
    
    // Delete existing merits/flaws if forcing
    if ($force) {
        $types = array_unique(array_column($merits_flaws, 'type'));
        $categories = array_unique(array_column($merits_flaws, 'category'));
        $type_placeholders = str_repeat('?,', count($types) - 1) . '?';
        $category_placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $delete_sql = "DELETE FROM character_merits_flaws WHERE character_id = ? AND type IN ($type_placeholders) AND category IN ($category_placeholders)";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        if ($delete_stmt) {
            $types_str = 'i' . str_repeat('s', count($types)) . str_repeat('s', count($categories));
            $params = array_merge([$character_id], $types, $categories);
            mysqli_stmt_bind_param($delete_stmt, $types_str, ...$params);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }
    }
    
    // Insert new merits/flaws
    $insert_sql = "INSERT INTO character_merits_flaws (character_id, name, type, category, point_value, point_cost, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        return 0;
    }
    
    foreach ($merits_flaws as $mf) {
        $key = $mf['type'] . '|' . $mf['category'] . '|' . $mf['name'];
        
        // Skip if already exists and not forcing
        if (!$force && isset($existing[$key])) {
            continue;
        }
        
        mysqli_stmt_bind_param($insert_stmt, 'isssiis', 
            $character_id,
            $mf['name'],
            $mf['type'],
            $mf['category'],
            $mf['point_value'],
            $mf['point_cost'],
            $mf['description']
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $inserted++;
        }
    }
    
    mysqli_stmt_close($insert_stmt);
    return $inserted;
}

// Main execution
echo "=== Character Merits & Flaws Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Types: " . implode(', ', $target_types) . "\n";
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

// Step 2: Check for missing merits/flaws and search JSON files
echo "Step 2: Checking for missing merits/flaws and searching JSON files...\n";

foreach ($all_characters as $char) {
    $character_id = (int)$char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Check existing merits/flaws
    $existing_merits_flaws = getExistingMeritsFlaws($conn, $character_id, $target_types, $target_categories);
    $has_merits_flaws = count($existing_merits_flaws) > 0;
    
    if (!$has_merits_flaws) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => $character_id,
            'character_name' => $character_name,
            'detected_at' => date('c')
        ];
    }
    
    // Search for merits/flaws in JSON files
    $json_result = searchMeritsFlawsJsonFiles($project_root, $character_name, $target_types, $target_categories);
    
    if ($json_result !== null) {
        $source_file = $json_result['source_file'];
        $merits_flaws_data = $json_result['merits_flaws'];
        
        $merits_flaws_count = insertMeritsFlaws($conn, $character_id, $merits_flaws_data, $options['dry-run'], $options['force']);
        
        if ($merits_flaws_count > 0) {
            $stats['merits_flaws_backfilled'] += $merits_flaws_count;
            $stats['characters_updated']++;
            
            // Count by type and category
            $by_type = [];
            $by_category = [];
            foreach ($merits_flaws_data as $mf) {
                $type = $mf['type'];
                $cat = $mf['category'];
                $by_type[$type] = ($by_type[$type] ?? 0) + 1;
                $by_category[$cat] = ($by_category[$cat] ?? 0) + 1;
            }
            
            $type_summary = [];
            foreach ($by_type as $type => $count) {
                $type_summary[] = "{$type}:{$count}";
            }
            
            $category_summary = [];
            foreach ($by_category as $cat => $count) {
                $category_summary[] = "{$cat}:{$count}";
            }
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'merits_flaws_count' => $merits_flaws_count,
                'by_type' => $by_type,
                'by_category' => $by_category,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s %d merits/flaws (Types: %s, Categories: %s) from %s\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would add' : 'Added',
                $merits_flaws_count,
                implode(', ', $type_summary),
                implode(', ', $category_summary),
                $source_file
            );
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found merits/flaws in JSON: {$source_file} ({$merits_flaws_count} items)\n";
            }
        } else {
            if ($options['verbose']) {
                echo "    - Merits/flaws found but already exist (use --force to replace)\n";
            }
        }
    } else {
        if (!$has_merits_flaws) {
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
            echo "    ✗ No merits/flaws found\n";
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

// Generate missing merits/flaws report
$missing_report = [
    'generated_at' => date('c'),
    'types' => $target_types,
    'categories' => $target_categories,
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_merits_flaws_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_merits_flaws_report.json\n";

// Generate updates log
$log_path = $output_dir . "/merits_flaws_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: merits_flaws_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Merits & Flaws Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: merits_flaws_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'types' => $target_types,
    'categories' => $target_categories,
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/merits_flaws_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: merits_flaws_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Merits & Flaws Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Merits/flaws backfilled: {$stats['merits_flaws_backfilled']}\n";
echo "Characters updated: {$stats['characters_updated']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_merits_flaws_report.json\n";
echo "- tools/repeatable/merits_flaws_updates.log\n";
echo "- tools/repeatable/merits_flaws_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

