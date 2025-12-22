<?php
/**
 * Fix Ability Categories Script
 * 
 * Adds ability_category column if missing and updates existing records
 * with categories from the abilities reference table or from JSON files.
 * 
 * Usage:
 *   php tools/repeatable/fix_ability_categories.php [options]
 * 
 * Options:
 *   --dry-run    Show what would be updated without making changes
 *   --verbose    Show detailed progress
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

$options = [
    'dry-run' => false,
    'verbose' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    }
}

$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

mysqli_set_charset($conn, 'utf8mb4');

echo "=== Fix Ability Categories Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE") . "\n\n";

// Step 1: Check if ability_category column exists
echo "Step 1: Checking for ability_category column...\n";
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$column_check = mysqli_query($conn, $check_column_sql);
$has_category_column = ($column_check && mysqli_num_rows($column_check) > 0);
if ($column_check) {
    mysqli_free_result($column_check);
}

if (!$has_category_column) {
    echo "  Column doesn't exist. Adding ability_category column...\n";
    if (!$options['dry-run']) {
        $alter_sql = "ALTER TABLE character_abilities ADD COLUMN ability_category VARCHAR(20) NULL AFTER ability_name";
        if (mysqli_query($conn, $alter_sql)) {
            echo "  ✓ Column added successfully\n";
        } else {
            die("ERROR: Failed to add column: " . mysqli_error($conn) . "\n");
        }
    } else {
        echo "  [DRY RUN] Would add ability_category column\n";
    }
} else {
    echo "  ✓ Column already exists\n";
}

echo "\n";

// Step 2: Update existing records with categories from abilities reference table
echo "Step 2: Updating ability categories from reference table...\n";

$update_sql = "UPDATE character_abilities ca
               INNER JOIN abilities a ON ca.ability_name COLLATE utf8mb4_unicode_ci = a.name COLLATE utf8mb4_unicode_ci
               SET ca.ability_category = a.category
               WHERE ca.ability_category IS NULL";
               
if ($options['dry-run']) {
    $check_sql = "SELECT COUNT(*) as count 
                  FROM character_abilities ca
                  INNER JOIN abilities a ON ca.ability_name = a.name
                  WHERE ca.ability_category IS NULL";
    $result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'] ?? 0;
    echo "  [DRY RUN] Would update {$count} records\n";
} else {
    $result = mysqli_query($conn, $update_sql);
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        echo "  ✓ Updated {$affected} records from reference table\n";
    } else {
        echo "  ✗ Error updating: " . mysqli_error($conn) . "\n";
    }
}

echo "\n";

// Step 3: For records still without categories, try to get from JSON files
echo "Step 3: Checking JSON files for remaining records...\n";

$missing_sql = "SELECT DISTINCT ca.character_id, c.character_name
                FROM character_abilities ca
                INNER JOIN characters c ON ca.character_id = c.id
                WHERE ca.ability_category IS NULL
                LIMIT 50";
$result = mysqli_query($conn, $missing_sql);

if ($result && mysqli_num_rows($result) > 0) {
    $characters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $characters[] = $row;
    }
    
    echo "  Found " . count($characters) . " characters with uncategorized abilities\n";
    
    // Search JSON files for these characters
    $search_dirs = [
        $project_root . '/reference/Characters',
        $project_root . '/agents/character_agent/data/Characters'
    ];
    
    $updated = 0;
    foreach ($characters as $char) {
        $character_id = (int)$char['character_id'];
        $character_name = $char['character_name'];
        
        // Search for JSON file
        $found = false;
        foreach ($search_dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'json') {
                    $content = file_get_contents($file->getPathname());
                    $data = json_decode($content, true);
                    
                    if ($data && isset($data['character_name'])) {
                        // Simple name matching
                        if (strtolower(trim($data['character_name'])) === strtolower(trim($character_name))) {
                            // Found matching JSON, extract abilities
                            if (isset($data['abilities']) && is_array($data['abilities'])) {
                                // Try format 1: array of objects
                                if (count($data['abilities']) > 0 && isset($data['abilities'][0]['name'])) {
                                    foreach ($data['abilities'] as $ability) {
                                        if (isset($ability['name']) && isset($ability['category'])) {
                                            $update_ability_sql = "UPDATE character_abilities 
                                                                   SET ability_category = ? 
                                                                   WHERE character_id = ? AND ability_name = ? AND ability_category IS NULL";
                                            if (!$options['dry-run']) {
                                                $stmt = mysqli_prepare($conn, $update_ability_sql);
                                                if ($stmt) {
                                                    $category = ucfirst(strtolower($ability['category']));
                                                    mysqli_stmt_bind_param($stmt, 'sis', $category, $character_id, $ability['name']);
                                                    if (mysqli_stmt_execute($stmt)) {
                                                        $updated++;
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                }
                                            } else {
                                                $updated++;
                                            }
                                        }
                                    }
                                }
                                // Try format 2: category-based
                                foreach (['Physical', 'Social', 'Mental'] as $category) {
                                    $category_key = $category;
                                    if (!isset($data['abilities'][$category_key])) {
                                        $category_key = ucfirst(strtolower($category));
                                    }
                                    if (!isset($data['abilities'][$category_key])) {
                                        $category_key = strtolower($category);
                                    }
                                    
                                    if (isset($data['abilities'][$category_key]) && is_array($data['abilities'][$category_key])) {
                                        foreach ($data['abilities'][$category_key] as $abilityName) {
                                            $cleanName = trim($abilityName);
                                            if (strpos($cleanName, ' (') !== false) {
                                                $cleanName = substr($cleanName, 0, strpos($cleanName, ' ('));
                                            }
                                            
                                            $update_ability_sql = "UPDATE character_abilities 
                                                                   SET ability_category = ? 
                                                                   WHERE character_id = ? AND ability_name = ? AND ability_category IS NULL";
                                            if (!$options['dry-run']) {
                                                $stmt = mysqli_prepare($conn, $update_ability_sql);
                                                if ($stmt) {
                                                    mysqli_stmt_bind_param($stmt, 'sis', $category, $character_id, $cleanName);
                                                    if (mysqli_stmt_execute($stmt)) {
                                                        $updated++;
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                }
                                            } else {
                                                $updated++;
                                            }
                                        }
                                    }
                                }
                            }
                            $found = true;
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    if ($options['dry-run']) {
        echo "  [DRY RUN] Would update {$updated} ability records from JSON files\n";
    } else {
        echo "  ✓ Updated {$updated} ability records from JSON files\n";
    }
} else {
    echo "  ✓ No characters with uncategorized abilities found\n";
}

echo "\n";
echo "Done.\n";

