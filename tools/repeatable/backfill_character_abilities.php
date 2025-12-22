<?php
/**
 * Character Abilities Backfill Script
 * 
 * Identifies characters with missing abilities, searches JSON files for ability data,
 * and updates the character_abilities table with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_abilities.php [options]
 * 
 * Optional Options:
 *   --category=<name>    Backfill specific category only (Physical, Social, Mental) or 'all' for all
 *   --dry-run           Show what would be updated without writing to database
 *   --verbose           Show detailed progress for each character
 *   --force             Replace existing abilities (default: only add missing)
 *   --help              Show this help message
 * 
 * Output Files:
 *   - missing_abilities_report.json    List of characters missing abilities
 *   - abilities_updates.log            Log of all database updates
 *   - abilities_not_found.json         Characters where no abilities were found
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
    echo "Character Abilities Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_abilities.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --category=<name>    Backfill specific category (Physical, Social, Mental) or 'all' (default)\n";
    echo "  --dry-run            Show what would be updated without writing to database\n";
    echo "  --verbose            Show detailed progress for each character\n";
    echo "  --force              Replace existing abilities (default: only add missing)\n";
    echo "  --help               Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_character_abilities.php --category=Physical --dry-run\n";
    echo "  php tools/repeatable/backfill_character_abilities.php --category=all --verbose\n";
    echo "  php tools/repeatable/backfill_character_abilities.php --force\n\n";
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

// Check if ability_category column exists
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$column_check = mysqli_query($conn, $check_column_sql);
$has_category_column = ($column_check && mysqli_num_rows($column_check) > 0);
if ($column_check) {
    mysqli_free_result($column_check);
}

// Output directory
$output_dir = __DIR__;
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Statistics
$stats = [
    'total_scanned' => 0,
    'missing_initially' => 0,
    'abilities_backfilled' => 0,
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
 * Clean ability name (remove specialization if present)
 */
function cleanAbilityName(string $name): string {
    $clean = trim($name);
    if (strpos($clean, ' (') !== false) {
        $clean = substr($clean, 0, strpos($clean, ' ('));
    }
    return $clean;
}

/**
 * Extract specialization from ability name
 */
function extractSpecialization(string $name): ?string {
    $clean = trim($name);
    if (strpos($clean, ' (') !== false) {
        $specStart = strpos($clean, ' (') + 2;
        $specEnd = strrpos($clean, ')');
        if ($specEnd > $specStart) {
            return substr($clean, $specStart, $specEnd - $specStart);
        }
    }
    return null;
}

/**
 * Extract abilities from JSON file
 * Handles two formats:
 * 1. Array of objects: [{name: "Athletics", category: "Physical", level: 3}, ...]
 * 2. Category-based: {Physical: ["Athletics", "Athletics", "Brawl"], ...}
 */
function extractAbilitiesFromJson(string $filepath, string $target_character_name, array $target_categories): ?array {
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
    
    $abilities = [];
    
    // Check for abilities array
    if (!isset($data['abilities']) || !is_array($data['abilities'])) {
        return null;
    }
    
    // Format 1: Array of objects [{name, category, level}, ...]
    if (count($data['abilities']) > 0 && isset($data['abilities'][0]) && is_array($data['abilities'][0])) {
        $first_item = $data['abilities'][0];
        if (isset($first_item['name']) && isset($first_item['category'])) {
            // This is format 1: array of objects
            foreach ($data['abilities'] as $ability) {
                if (!is_array($ability) || !isset($ability['name'])) {
                    continue;
                }
                
                $category = ucfirst(strtolower($ability['category'] ?? ''));
                if (!in_array($category, $target_categories, true)) {
                    continue;
                }
                
                $name = cleanAbilityName($ability['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                
                $level = isset($ability['level']) ? max(1, min(5, (int)$ability['level'])) : 1;
                $specialization = $ability['specialization'] ?? extractSpecialization($ability['name'] ?? '');
                
                $abilities[] = [
                    'name' => $name,
                    'category' => $category,
                    'level' => $level,
                    'specialization' => $specialization
                ];
            }
        }
    }
    
    // Format 2: Category-based {Physical: ["Athletics", "Athletics", "Brawl"], ...}
    // Also check specializations object
    $specializations = $data['specializations'] ?? [];
    if (!is_array($specializations)) {
        $specializations = [];
    }
    
    foreach ($target_categories as $category) {
        $category_key = $category; // Try exact match first
        if (!isset($data['abilities'][$category_key])) {
            $category_key = ucfirst(strtolower($category)); // Try normalized
        }
        if (!isset($data['abilities'][$category_key])) {
            $category_key = strtolower($category); // Try lowercase
        }
        
        if (isset($data['abilities'][$category_key]) && is_array($data['abilities'][$category_key])) {
            // Count occurrences to determine level
            $abilityCounts = [];
            foreach ($data['abilities'][$category_key] as $abilityName) {
                $cleanName = cleanAbilityName($abilityName);
                if ($cleanName !== '') {
                    $abilityCounts[$cleanName] = ($abilityCounts[$cleanName] ?? 0) + 1;
                }
            }
            
            // Create ability entries
            foreach ($abilityCounts as $abilityName => $level) {
                $level = max(1, min(5, (int)$level));
                
                // Check for specialization in specializations object or original name
                $specialization = null;
                if (isset($specializations[$abilityName]) && is_string($specializations[$abilityName])) {
                    $specialization = trim($specializations[$abilityName]);
                } else {
                    // Try to find specialization in original array
                    foreach ($data['abilities'][$category_key] as $origName) {
                        if (strpos($origName, $abilityName . ' (') === 0) {
                            $specialization = extractSpecialization($origName);
                            break;
                        }
                    }
                }
                
                $abilities[] = [
                    'name' => $abilityName,
                    'category' => $category,
                    'level' => $level,
                    'specialization' => $specialization
                ];
            }
        }
    }
    
    return count($abilities) > 0 ? $abilities : null;
}

/**
 * Search for abilities in JSON files
 */
function searchAbilityJsonFiles(string $project_root, string $character_name, array $target_categories): ?array {
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
                $abilities = extractAbilitiesFromJson($filepath, $character_name, $target_categories);
                
                if ($abilities !== null && count($abilities) > 0) {
                    return [
                        'source_file' => $filepath,
                        'abilities' => $abilities
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Get existing abilities for a character
 */
function getExistingAbilities(mysqli $conn, int $character_id, array $categories): array {
    $existing = [];
    
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $sql = "SELECT ability_name, ability_category, level, specialization 
            FROM character_abilities 
            WHERE character_id = ? AND ability_category IN ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $existing;
    }
    
    $types = 'i' . str_repeat('s', count($categories));
    $params = array_merge([$character_id], $categories);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $key = ($row['ability_category'] ?? '') . '|' . $row['ability_name'];
        $existing[$key] = [
            'level' => (int)$row['level'],
            'specialization' => $row['specialization']
        ];
    }
    
    mysqli_stmt_close($stmt);
    return $existing;
}

/**
 * Insert abilities into database
 */
function insertAbilities(mysqli $conn, int $character_id, array $abilities, bool $dry_run, bool $force, bool $has_category_column): int {
    if (count($abilities) === 0) {
        return 0;
    }
    
    $inserted = 0;
    
    // Get existing abilities if not forcing
    $existing = [];
    if (!$force) {
        $categories = array_unique(array_column($abilities, 'category'));
        $existing = getExistingAbilities($conn, $character_id, $categories);
    }
    
    if ($dry_run) {
        // Count what would be inserted
        foreach ($abilities as $ability) {
            $key = $ability['category'] . '|' . $ability['name'];
            if (!isset($existing[$key])) {
                $inserted++;
            }
        }
        return $inserted;
    }
    
    // Delete existing abilities if forcing
    if ($force) {
        $categories = array_unique(array_column($abilities, 'category'));
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $delete_sql = "DELETE FROM character_abilities WHERE character_id = ? AND ability_category IN ($placeholders)";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        if ($delete_stmt) {
            $types = 'i' . str_repeat('s', count($categories));
            $params = array_merge([$character_id], $categories);
            mysqli_stmt_bind_param($delete_stmt, $types, ...$params);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }
    }
    
    // Insert new abilities
    if ($has_category_column) {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?)";
    } else {
        $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
    }
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        return 0;
    }
    
    foreach ($abilities as $ability) {
        $key = $ability['category'] . '|' . $ability['name'];
        
        // Skip if already exists and not forcing
        if (!$force && isset($existing[$key])) {
            continue;
        }
        
        if ($has_category_column) {
            mysqli_stmt_bind_param($insert_stmt, 'issis', 
                $character_id,
                $ability['name'],
                $ability['category'],
                $ability['level'],
                $ability['specialization']
            );
        } else {
            mysqli_stmt_bind_param($insert_stmt, 'isis', 
                $character_id,
                $ability['name'],
                $ability['level'],
                $ability['specialization']
            );
        }
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $inserted++;
        }
    }
    
    mysqli_stmt_close($insert_stmt);
    return $inserted;
}

// Main execution
echo "=== Character Abilities Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Categories: " . implode(', ', $target_categories) . "\n";
echo "Force mode: " . ($options['force'] ? "YES (replace existing)" : "NO (add missing only)") . "\n";
echo "Has category column: " . ($has_category_column ? "YES" : "NO") . "\n\n";

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

// Step 2: Check for missing abilities and search JSON files
echo "Step 2: Checking for missing abilities and searching JSON files...\n";

foreach ($all_characters as $char) {
    $character_id = (int)$char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Check existing abilities
    $existing_abilities = getExistingAbilities($conn, $character_id, $target_categories);
    $has_abilities = count($existing_abilities) > 0;
    
    if (!$has_abilities) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => $character_id,
            'character_name' => $character_name,
            'detected_at' => date('c')
        ];
    }
    
    // Search for abilities in JSON files
    $json_result = searchAbilityJsonFiles($project_root, $character_name, $target_categories);
    
    if ($json_result !== null) {
        $source_file = $json_result['source_file'];
        $abilities_data = $json_result['abilities'];
        
        $abilities_count = insertAbilities($conn, $character_id, $abilities_data, $options['dry-run'], $options['force'], $has_category_column);
        
        if ($abilities_count > 0) {
            $stats['abilities_backfilled'] += $abilities_count;
            $stats['characters_updated']++;
            
            // Count by category
            $by_category = [];
            foreach ($abilities_data as $ability) {
                $cat = $ability['category'];
                $by_category[$cat] = ($by_category[$cat] ?? 0) + 1;
            }
            $category_summary = [];
            foreach ($by_category as $cat => $count) {
                $category_summary[] = "{$cat}:{$count}";
            }
            
            $updated_characters[] = [
                'id' => $character_id,
                'character_name' => $character_name,
                'source_file' => $source_file,
                'abilities_count' => $abilities_count,
                'by_category' => $by_category,
                'updated_at' => date('c')
            ];
            
            $log_entry = sprintf(
                "[%s] Character ID %d (%s): %s %d abilities (%s) from %s\n",
                date('Y-m-d H:i:s'),
                $character_id,
                $character_name,
                $options['dry-run'] ? 'Would add' : 'Added',
                $abilities_count,
                implode(', ', $category_summary),
                $source_file
            );
            $update_log[] = $log_entry;
            
            if ($options['verbose']) {
                echo "    ✓ Found abilities in JSON: {$source_file} ({$abilities_count} abilities)\n";
            }
        } else {
            if ($options['verbose']) {
                echo "    - Abilities found but already exist (use --force to replace)\n";
            }
        }
    } else {
        if (!$has_abilities) {
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
            echo "    ✗ No abilities found\n";
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

// Generate missing abilities report
$missing_report = [
    'generated_at' => date('c'),
    'categories' => $target_categories,
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_abilities_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_abilities_report.json\n";

// Generate updates log
$log_path = $output_dir . "/abilities_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: abilities_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Abilities Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: abilities_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'categories' => $target_categories,
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/abilities_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: abilities_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Abilities Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Abilities backfilled: {$stats['abilities_backfilled']}\n";
echo "Characters updated: {$stats['characters_updated']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_abilities_report.json\n";
echo "- tools/repeatable/abilities_updates.log\n";
echo "- tools/repeatable/abilities_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

