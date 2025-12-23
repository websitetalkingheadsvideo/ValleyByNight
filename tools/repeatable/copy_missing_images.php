<?php
/**
 * Copy Missing Character Images
 * 
 * Copies found character images from archive to uploads/characters/
 * 
 * Usage:
 *   php tools/repeatable/copy_missing_images.php [options]
 * 
 * Optional Options:
 *   --dry-run           Show what would be copied without actually copying
 *   --verbose           Show detailed progress
 *   --help              Show this help message
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
    echo "Copy Missing Character Images\n\n";
    echo "Usage: php tools/repeatable/copy_missing_images.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --dry-run             Show what would be copied without actually copying\n";
    echo "  --verbose             Show detailed progress\n";
    echo "  --help                Show this help message\n\n";
    exit(0);
}

// Get project root
$project_root = dirname(__DIR__, 2);
chdir($project_root);

// Load git search results
$search_report_path = __DIR__ . '/missing_images_git_search.json';
if (!file_exists($search_report_path)) {
    die("ERROR: Git search report not found. Run search_missing_images_git.php first.\n");
}

$search_report = json_decode(file_get_contents($search_report_path), true);
if (!$search_report || !isset($search_report['found_images'])) {
    die("ERROR: Invalid git search report format.\n");
}

// Load missing images report for character info
$missing_report_path = __DIR__ . '/missing_images_report.json';
$missing_report = [];
if (file_exists($missing_report_path)) {
    $missing_report = json_decode(file_get_contents($missing_report_path), true);
}

// Create character lookup by ID
$character_lookup = [];
if (isset($missing_report['characters'])) {
    foreach ($missing_report['characters'] as $char) {
        $character_lookup[$char['id']] = $char;
    }
}

$source_dir = $project_root . '/archive/images/duplicates/reference/Characters/Images';
$target_dir = $project_root . '/uploads/characters';

// Ensure target directory exists
if (!is_dir($target_dir)) {
    if (!$options['dry-run']) {
        mkdir($target_dir, 0755, true);
        echo "Created directory: {$target_dir}\n";
    } else {
        echo "Would create directory: {$target_dir}\n";
    }
}

$copied = [];
$failed = [];
$skipped = [];

echo "Copying character images...\n";
echo "Source: {$source_dir}\n";
echo "Target: {$target_dir}\n\n";

// Copy exact matches
foreach ($search_report['found_images'] as $found) {
    $character_id = $found['character_id'];
    $character_name = $found['character_name'];
    $database_filename = $found['database_filename'];
    
    // Get the first found location (normalize path separators)
    $source_path = $found['found_locations'][0];
    $source_path = str_replace('\\', '/', $source_path);
    $full_source_path = $project_root . '/' . $source_path;
    
    $target_path = $target_dir . '/' . $database_filename;
    
    if (!file_exists($full_source_path)) {
        $failed[] = [
            'character_id' => $character_id,
            'character_name' => $character_name,
            'database_filename' => $database_filename,
            'source_path' => $source_path,
            'error' => 'Source file does not exist'
        ];
        if ($options['verbose']) {
            echo "✗ ID {$character_id}: {$character_name} - Source file not found: {$source_path}\n";
        }
        continue;
    }
    
    if (file_exists($target_path)) {
        $skipped[] = [
            'character_id' => $character_id,
            'character_name' => $character_name,
            'database_filename' => $database_filename,
            'reason' => 'Target file already exists'
        ];
        if ($options['verbose']) {
            echo "⊘ ID {$character_id}: {$character_name} - Already exists: {$database_filename}\n";
        }
        continue;
    }
    
    if ($options['dry-run']) {
        echo "Would copy: ID {$character_id}: {$character_name}\n";
        echo "  From: {$source_path}\n";
        echo "  To: {$database_filename}\n\n";
        $copied[] = [
            'character_id' => $character_id,
            'character_name' => $character_name,
            'database_filename' => $database_filename,
            'source_path' => $source_path,
            'status' => 'would_copy'
        ];
    } else {
        if (copy($full_source_path, $target_path)) {
            echo "✓ ID {$character_id}: {$character_name} - Copied {$database_filename}\n";
            $copied[] = [
                'character_id' => $character_id,
                'character_name' => $character_name,
                'database_filename' => $database_filename,
                'source_path' => $source_path,
                'status' => 'copied'
            ];
        } else {
            $failed[] = [
                'character_id' => $character_id,
                'character_name' => $character_name,
                'database_filename' => $database_filename,
                'source_path' => $source_path,
                'error' => 'Copy operation failed'
            ];
            echo "✗ ID {$character_id}: {$character_name} - Failed to copy {$database_filename}\n";
        }
    }
}

// Handle potential matches by character name
$potential_matches = [
    ['filename' => 'Étienne Duvalier.png', 'character_id' => 68, 'database_filename' => '68_1761864977_09fd06ca.png'],
    ['filename' => 'Jennifer Kwan.png', 'character_id' => 71, 'database_filename' => '71_1761917569_5db6b141.png'],
    ['filename' => 'Marcus Webb.png', 'character_id' => 72, 'database_filename' => '72_1761811769_0fa12396.png'],
    ['filename' => 'Roland Cross.png', 'character_id' => 88, 'database_filename' => '88_1761917227_953c266e.png'],
    ['filename' => 'Tariq Ibrahim.png', 'character_id' => 104, 'database_filename' => '104_1763074210_6d9a3dd4.png'],
    ['filename' => 'Warner Jefferson.webp', 'character_id' => 123, 'database_filename' => '123_1762872252_22f90772.webp'],
    ['filename' => 'Roadrunner.png', 'character_id' => 124, 'database_filename' => '123_1762872252_22f90772.webp'],
];

echo "\n";
echo "Checking potential matches by character name...\n";

foreach ($potential_matches as $match) {
    $source_file = $match['filename'];
    $character_id = $match['character_id'];
    $database_filename = $match['database_filename'];
    
    $full_source_path = $source_dir . '/' . $source_file;
    $target_path = $target_dir . '/' . $database_filename;
    
    // Get character name from lookup
    $character_name = isset($character_lookup[$character_id]) 
        ? $character_lookup[$character_id]['character_name'] 
        : "ID {$character_id}";
    
    if (!file_exists($full_source_path)) {
        if ($options['verbose']) {
            echo "⊘ ID {$character_id}: {$character_name} - Potential match not found: {$source_file}\n";
        }
        continue;
    }
    
    if (file_exists($target_path)) {
        if ($options['verbose']) {
            echo "⊘ ID {$character_id}: {$character_name} - Already exists: {$database_filename}\n";
        }
        continue;
    }
    
    // Check if this character is in the missing list
    $is_missing = false;
    if (isset($character_lookup[$character_id])) {
        $char_data = $character_lookup[$character_id];
        if ($char_data['status'] === 'file_not_found' && $char_data['character_image_db'] === $database_filename) {
            $is_missing = true;
        }
    }
    
    if (!$is_missing) {
        if ($options['verbose']) {
            echo "⊘ ID {$character_id}: {$character_name} - Not in missing list or filename mismatch\n";
        }
        continue;
    }
    
    if ($options['dry-run']) {
        echo "Would copy (potential match): ID {$character_id}: {$character_name}\n";
        echo "  From: {$source_file}\n";
        echo "  To: {$database_filename}\n";
        echo "  NOTE: Filename differs - verify this is correct!\n\n";
    } else {
        // For potential matches, we need to verify - ask user or copy with warning
        // For now, we'll copy but note the filename difference
        if (copy($full_source_path, $target_path)) {
            echo "⚠ ID {$character_id}: {$character_name} - Copied {$source_file} as {$database_filename}\n";
            echo "  NOTE: Filename differs - verify this is correct!\n";
            $copied[] = [
                'character_id' => $character_id,
                'character_name' => $character_name,
                'database_filename' => $database_filename,
                'source_path' => 'archive/images/duplicates/reference/Characters/Images/' . $source_file,
                'status' => 'copied_potential_match',
                'warning' => 'Filename differs from source'
            ];
        } else {
            $failed[] = [
                'character_id' => $character_id,
                'character_name' => $character_name,
                'database_filename' => $database_filename,
                'source_path' => 'archive/images/duplicates/reference/Characters/Images/' . $source_file,
                'error' => 'Copy operation failed'
            ];
            echo "✗ ID {$character_id}: {$character_name} - Failed to copy\n";
        }
    }
}

// Summary
echo "\n";
echo "=== Copy Summary ===\n";
echo "Copied: " . count($copied) . "\n";
echo "Skipped (already exists): " . count($skipped) . "\n";
echo "Failed: " . count($failed) . "\n";

if (count($failed) > 0) {
    echo "\n=== Failed Copies ===\n";
    foreach ($failed as $fail) {
        echo "ID {$fail['character_id']}: {$fail['character_name']} - {$fail['error']}\n";
    }
}

// Save report
$timestamp = date('Y-m-d H:i:s');
$report = [
    'generated' => $timestamp,
    'dry_run' => $options['dry-run'],
    'summary' => [
        'copied' => count($copied),
        'skipped' => count($skipped),
        'failed' => count($failed)
    ],
    'copied' => $copied,
    'skipped' => $skipped,
    'failed' => $failed
];

$report_path = __DIR__ . '/copy_images_report.json';
file_put_contents($report_path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nReport saved: {$report_path}\n";
?>

