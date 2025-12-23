<?php
/**
 * Character Image Analysis Script
 * 
 * Analyzes the database for character images and identifies which characters
 * are missing image files (showing clan logo fallback instead).
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/analyze_character_images.php [options]
 * 
 * Optional Options:
 *   --dry-run           Show what would be updated without writing to missing.md
 *   --verbose           Show detailed progress for each character
 *   --help              Show this help message
 * 
 * Output Files:
 *   - character_images_report.json    Detailed report of all character image statuses
 *   - missing_images_report.json     List of characters missing images
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
    echo "Character Image Analysis Script\n\n";
    echo "Usage: php tools/repeatable/analyze_character_images.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --dry-run             Show what would be updated without writing to missing.md\n";
    echo "  --verbose             Show detailed progress for each character\n";
    echo "  --help                Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/analyze_character_images.php --dry-run\n";
    echo "  php tools/repeatable/analyze_character_images.php --verbose\n\n";
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

// Image directory
$image_dir = $project_root . '/uploads/characters';
if (!is_dir($image_dir)) {
    echo "WARNING: Image directory does not exist: {$image_dir}\n";
    echo "Creating directory...\n";
    mkdir($image_dir, 0755, true);
}

// Statistics
$stats = [
    'total_characters' => 0,
    'with_image_field' => 0,
    'with_image_file' => 0,
    'missing_image' => 0,
    'database_null' => 0,
    'database_empty' => 0,
    'file_not_found' => 0
];

// Data structures
$all_characters = [];
$missing_images = [];

echo "Analyzing character images...\n";
echo "Image directory: {$image_dir}\n\n";

// Query all characters
$query = "SELECT id, character_name, character_image, clan FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Query failed: " . mysqli_error($conn) . "\n");
}

while ($row = mysqli_fetch_assoc($result)) {
    $stats['total_characters']++;
    
    $character_id = (int)$row['id'];
    $character_name = $row['character_name'] ?? '';
    $character_image = $row['character_image'] ?? null;
    $clan = $row['clan'] ?? '';
    
    $character_data = [
        'id' => $character_id,
        'character_name' => $character_name,
        'clan' => $clan,
        'character_image_db' => $character_image,
        'has_image_field' => false,
        'has_image_file' => false,
        'image_path' => null,
        'status' => 'unknown'
    ];
    
    // Check if character_image field is set in database
    if ($character_image === null) {
        $stats['database_null']++;
        $character_data['status'] = 'database_null';
        $character_data['reason'] = 'character_image field is NULL in database';
    } elseif (trim($character_image) === '') {
        $stats['database_empty']++;
        $character_data['status'] = 'database_empty';
        $character_data['reason'] = 'character_image field is empty string in database';
    } else {
        $stats['with_image_field']++;
        $character_data['has_image_field'] = true;
        $character_data['image_path'] = $image_dir . '/' . $character_image;
        
        // Check if file exists
        if (file_exists($character_data['image_path'])) {
            $stats['with_image_file']++;
            $character_data['has_image_file'] = true;
            $character_data['status'] = 'has_image';
        } else {
            $stats['file_not_found']++;
            $character_data['status'] = 'file_not_found';
            $character_data['reason'] = "Database has image filename '{$character_image}' but file does not exist";
        }
    }
    
    // Add to missing list if image is missing
    if ($character_data['status'] !== 'has_image') {
        $stats['missing_image']++;
        $missing_images[] = $character_data;
    }
    
    $all_characters[] = $character_data;
    
    if ($options['verbose']) {
        $status_icon = $character_data['status'] === 'has_image' ? '✓' : '✗';
        echo "  {$status_icon} ID {$character_id}: {$character_name}";
        if ($character_data['status'] !== 'has_image') {
            echo " - {$character_data['reason']}";
        }
        echo "\n";
    }
}

mysqli_free_result($result);

// Generate reports
$timestamp = date('Y-m-d H:i:s');

// Full report
$full_report = [
    'generated' => $timestamp,
    'summary' => $stats,
    'characters' => $all_characters
];

// Missing images report
$missing_report = [
    'generated' => $timestamp,
    'total_missing' => count($missing_images),
    'characters' => $missing_images
];

// Save JSON reports
$full_report_path = $output_dir . '/character_images_report.json';
$missing_report_path = $output_dir . '/missing_images_report.json';

file_put_contents($full_report_path, json_encode($full_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Display summary
echo "\n";
echo "=== Analysis Summary ===\n";
echo "Total characters analyzed: {$stats['total_characters']}\n";
echo "Characters with image field set: {$stats['with_image_field']}\n";
echo "Characters with image file: {$stats['with_image_file']}\n";
echo "Characters missing images: {$stats['missing_image']}\n";
echo "\n";
echo "Breakdown:\n";
echo "  - Database NULL: {$stats['database_null']}\n";
echo "  - Database empty string: {$stats['database_empty']}\n";
echo "  - File not found: {$stats['file_not_found']}\n";
echo "\n";

// Update missing.md
if (!$options['dry-run']) {
    $missing_md_path = $project_root . '/To-Do Lists/missing.md';
    
    if (!file_exists($missing_md_path)) {
        echo "WARNING: missing.md not found at {$missing_md_path}\n";
        echo "Creating new file...\n";
        $missing_md_content = "# Character Missing Data Report\n\n";
        $missing_md_content .= "Generated: {$timestamp}\n\n";
    } else {
        $missing_md_content = file_get_contents($missing_md_path);
    }
    
    // Find or create Character Images section
    $images_section = "\n## Character Images\n\n";
    $images_section .= "Generated: {$timestamp}\n\n";
    $images_section .= "Total characters missing images: {$stats['missing_image']}\n\n";
    
    if (count($missing_images) > 0) {
        $images_section .= "### Characters Missing Images\n\n";
        
        foreach ($missing_images as $char) {
            $images_section .= "### ID: {$char['id']} - {$char['character_name']}\n\n";
            $images_section .= "**Status:** {$char['status']}\n";
            $images_section .= "**Reason:** {$char['reason']}\n";
            if ($char['character_image_db']) {
                $images_section .= "**Database value:** `{$char['character_image_db']}`\n";
            }
            if ($char['clan']) {
                $images_section .= "**Clan:** {$char['clan']}\n";
            }
            $images_section .= "\n";
        }
    } else {
        $images_section .= "All characters have images! ✓\n\n";
    }
    
    // Append or replace Character Images section
    if (strpos($missing_md_content, '## Character Images') !== false) {
        // Replace existing section
        $pattern = '/## Character Images.*?(?=\n## |$)/s';
        $missing_md_content = preg_replace($pattern, $images_section, $missing_md_content);
    } else {
        // Append new section
        $missing_md_content .= $images_section;
    }
    
    file_put_contents($missing_md_path, $missing_md_content);
    echo "Updated: {$missing_md_path}\n";
} else {
    echo "DRY RUN: Would update missing.md with " . count($missing_images) . " missing images\n";
}

echo "\n";
echo "Reports saved:\n";
echo "  - {$full_report_path}\n";
echo "  - {$missing_report_path}\n";
echo "\n";

$conn->close();
?>

