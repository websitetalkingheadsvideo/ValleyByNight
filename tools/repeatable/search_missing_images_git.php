<?php
/**
 * Search Git for Missing Character Images
 * 
 * Searches git history and repository for missing character image files
 * and reports where they can be found.
 * 
 * Usage:
 *   php tools/repeatable/search_missing_images_git.php [options]
 * 
 * Optional Options:
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
    'verbose' => false,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Search Git for Missing Character Images\n\n";
    echo "Usage: php tools/repeatable/search_missing_images_git.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --verbose             Show detailed progress\n";
    echo "  --help                Show this help message\n\n";
    exit(0);
}

// Get project root
$project_root = dirname(__DIR__, 2);
chdir($project_root);

// Load missing images report
$missing_report_path = __DIR__ . '/missing_images_report.json';
if (!file_exists($missing_report_path)) {
    die("ERROR: Missing images report not found. Run analyze_character_images.php first.\n");
}

$missing_report = json_decode(file_get_contents($missing_report_path), true);
if (!$missing_report || !isset($missing_report['characters'])) {
    die("ERROR: Invalid missing images report format.\n");
}

echo "Searching git for missing character images...\n\n";

$found_images = [];
$not_found_images = [];
$search_locations = [
    'reference/Characters/Images',
    'archive/images',
    'uploads/characters',
    'images'
];

// Function to search for file in git history
function searchGitForFile(string $filename, string $project_root): array {
    $results = [];
    
    // Search git log for the filename
    $escaped_filename = escapeshellarg($filename);
    $command = "git log --all --full-history --name-only --pretty=format: -- {$escaped_filename} 2>&1";
    $output = shell_exec($command);
    
    if ($output) {
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && file_exists($project_root . '/' . $line)) {
                $results[] = $line;
            }
        }
    }
    
    // Also search for files with similar names (case-insensitive, different extensions)
    $base_name = pathinfo($filename, PATHINFO_FILENAME);
    $extensions = ['png', 'jpg', 'jpeg', 'webp'];
    
    foreach ($extensions as $ext) {
        $search_name = $base_name . '.' . $ext;
        $command = "git log --all --full-history --name-only --pretty=format: -- *{$search_name}* 2>&1";
        $output = shell_exec($command);
        
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && file_exists($project_root . '/' . $line)) {
                    $results[] = $line;
                }
            }
        }
    }
    
    return array_unique($results);
}

// Function to search filesystem for file
function searchFilesystemForFile(string $filename, string $project_root, array $search_locations): array {
    $results = [];
    $base_name = pathinfo($filename, PATHINFO_FILENAME);
    $extensions = ['png', 'jpg', 'jpeg', 'webp'];
    
    foreach ($search_locations as $location) {
        $full_path = $project_root . '/' . $location;
        if (!is_dir($full_path)) {
            continue;
        }
        
        // Check exact filename
        $exact_path = $full_path . '/' . $filename;
        if (file_exists($exact_path)) {
            $results[] = $location . '/' . $filename;
            continue;
        }
        
        // Check with different extensions
        foreach ($extensions as $ext) {
            $alt_path = $full_path . '/' . $base_name . '.' . $ext;
            if (file_exists($alt_path)) {
                $results[] = $location . '/' . basename($alt_path);
            }
        }
        
        // Recursive search for similar names
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $file_name = $file->getFilename();
                $file_base = pathinfo($file_name, PATHINFO_FILENAME);
                
                // Case-insensitive match
                if (strcasecmp($file_base, $base_name) === 0) {
                    $relative_path = str_replace($project_root . '/', '', $file->getPathname());
                    $results[] = $relative_path;
                }
            }
        }
    }
    
    return array_unique($results);
}

// Process each missing image
foreach ($missing_report['characters'] as $char) {
    if ($char['status'] === 'database_null' || $char['status'] === 'database_empty') {
        // Skip characters with no database entry
        continue;
    }
    
    $filename = $char['character_image_db'];
    $character_name = $char['character_name'];
    $character_id = $char['id'];
    
    if ($options['verbose']) {
        echo "Searching for: {$filename} (ID {$character_id}: {$character_name})...\n";
    }
    
    $git_results = searchGitForFile($filename, $project_root);
    $filesystem_results = searchFilesystemForFile($filename, $project_root, $search_locations);
    
    $all_results = array_unique(array_merge($git_results, $filesystem_results));
    
    if (!empty($all_results)) {
        $found_images[] = [
            'character_id' => $character_id,
            'character_name' => $character_name,
            'database_filename' => $filename,
            'found_locations' => $all_results
        ];
        
        if ($options['verbose']) {
            echo "  ✓ Found in:\n";
            foreach ($all_results as $location) {
                echo "    - {$location}\n";
            }
        }
    } else {
        $not_found_images[] = [
            'character_id' => $character_id,
            'character_name' => $character_name,
            'database_filename' => $filename
        ];
        
        if ($options['verbose']) {
            echo "  ✗ Not found\n";
        }
    }
}

// Generate report
$timestamp = date('Y-m-d H:i:s');
$output_dir = __DIR__;

$report = [
    'generated' => $timestamp,
    'summary' => [
        'total_searched' => count($missing_report['characters']) - 3, // Exclude NULL/empty
        'found' => count($found_images),
        'not_found' => count($not_found_images)
    ],
    'found_images' => $found_images,
    'not_found_images' => $not_found_images
];

$report_path = $output_dir . '/missing_images_git_search.json';
file_put_contents($report_path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Display summary
echo "\n";
echo "=== Search Summary ===\n";
echo "Total images searched: {$report['summary']['total_searched']}\n";
echo "Found in repository: {$report['summary']['found']}\n";
echo "Not found: {$report['summary']['not_found']}\n";
echo "\n";

if (count($found_images) > 0) {
    echo "=== Found Images ===\n";
    foreach ($found_images as $found) {
        echo "ID {$found['character_id']}: {$found['character_name']}\n";
        echo "  Database filename: {$found['database_filename']}\n";
        echo "  Found in:\n";
        foreach ($found['found_locations'] as $location) {
            echo "    - {$location}\n";
        }
        echo "\n";
    }
}

if (count($not_found_images) > 0) {
    echo "=== Not Found Images ===\n";
    foreach ($not_found_images as $not_found) {
        echo "ID {$not_found['character_id']}: {$not_found['character_name']} - {$not_found['database_filename']}\n";
    }
}

echo "\n";
echo "Report saved: {$report_path}\n";
?>

