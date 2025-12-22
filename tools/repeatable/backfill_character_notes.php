<?php
/**
 * Character Notes Backfill Script
 * 
 * Identifies characters with missing notes fields, searches JSON and Markdown files
 * for character notes, and updates database records with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_notes.php [options]
 * 
 * Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
 *   --min-length=N     Minimum notes length threshold (default: 20)
 *   --help             Show this help message
 * 
 * Output Files:
 *   - missing_notes_report.json    List of characters missing notes
 *   - notes_updates.log             Log of all database updates
 *   - notes_not_found.json          Characters where no notes were found
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
    'min-length' => 20,
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
    echo "Character Notes Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_notes.php [options]\n\n";
    echo "Options:\n";
    echo "  --dry-run          Show what would be updated without writing to database\n";
    echo "  --verbose          Show detailed progress for each character\n";
    echo "  --min-length=N     Minimum notes length threshold (default: 20)\n";
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
 * Check if notes is considered missing
 */
function isNotesMissing(?string $notes): bool {
    if ($notes === null) {
        return true;
    }
    
    $trimmed = trim($notes);
    
    if ($trimmed === '') {
        return true;
    }
    
    // Check for placeholder patterns
    $placeholders = ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None'];
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
 * Extract notes from JSON file
 */
function extractNotesFromJson(string $filepath, string $target_character_name): ?string {
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
    
    // Extract notes - check multiple possible fields
    $notes = null;
    
    // 1. Check simple 'notes' field
    if (isset($data['notes']) && is_string($data['notes']) && trim($data['notes']) !== '') {
        $notes = trim($data['notes']);
    }
    
    // 2. Check 'status.notes' (if status is an object)
    if ($notes === null && isset($data['status']) && is_array($data['status']) && isset($data['status']['notes'])) {
        $status_notes = $data['status']['notes'];
        if (is_string($status_notes) && trim($status_notes) !== '') {
            $notes = trim($status_notes);
        }
    }
    
    return $notes;
}

/**
 * Extract notes from Markdown file
 */
function extractNotesFromMarkdown(string $filepath, string $target_character_name): ?string {
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
    
    // Search for notes sections
    $patterns = [
        // Pattern 1: "# Character Notes: Name\n\nContent" or "# Notes: Name\n\nContent"
        ['pattern' => '/^#\s*Character\s+Notes:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims', 'group' => 1],
        ['pattern' => '/^#\s*Notes:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims', 'group' => 1],
        // Pattern 2-3: "## Section\n\nContent"
        ['pattern' => '/^##\s*Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims', 'group' => 1],
        ['pattern' => '/^##\s*Player\s+Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims', 'group' => 1],
        ['pattern' => '/^##\s*Storyteller\s+Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims', 'group' => 1]
    ];
    
    $extracted_text = null;
    foreach ($patterns as $pattern_info) {
        if (preg_match($pattern_info['pattern'], $content, $matches)) {
            $group_index = $pattern_info['group'];
            $extracted_text = $matches[$group_index] ?? '';
            if (trim($extracted_text) !== '') {
                break;
            }
        }
    }
    
    // If no section header found, don't use entire content (notes are usually in specific sections)
    if ($extracted_text === null || trim($extracted_text) === '') {
        return null;
    }
    
    // Clean markdown formatting (basic cleanup)
    $cleaned = $extracted_text;
    
    // Remove markdown headers
    $cleaned = preg_replace('/^#{1,6}\s+.*$/m', '', $cleaned);
    
    // Remove markdown links [text](url) -> text
    $cleaned = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $cleaned);
    
    // Remove markdown images
    $cleaned = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '', $cleaned);
    
    // Remove markdown bold/italic markers (keep text)
    $cleaned = preg_replace('/\*\*([^\*]+)\*\*/', '$1', $cleaned);
    $cleaned = preg_replace('/\*([^\*]+)\*/', '$1', $cleaned);
    $cleaned = preg_replace('/_([^_]+)_/', '$1', $cleaned);
    
    // Clean up excessive whitespace
    $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);
    $cleaned = trim($cleaned);
    
    return $cleaned;
}

/**
 * Search for notes in JSON files
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
                $notes = extractNotesFromJson($filepath, $character_name);
                
                if ($notes !== null && strlen($notes) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'notes' => $notes,
                        'length' => strlen($notes)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Search for notes in Markdown files
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
                $notes = extractNotesFromMarkdown($filepath, $character_name);
                
                if ($notes !== null && strlen($notes) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'notes' => $notes,
                        'length' => strlen($notes)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Update character notes in database
 */
function updateCharacterNotes(mysqli $conn, int $character_id, string $notes, bool $dry_run): bool {
    // Re-check that notes is still empty (idempotency safeguard)
    $check_stmt = mysqli_prepare($conn, "SELECT notes FROM characters WHERE id = ?");
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
    
    // If notes is no longer empty, skip update
    if (!isNotesMissing($row['notes'])) {
        return false;
    }
    
    if ($dry_run) {
        return true; // Return true to indicate "would update"
    }
    
    // Update with safeguard condition
    $update_stmt = mysqli_prepare($conn, 
        "UPDATE characters SET notes = ? WHERE id = ? AND (notes IS NULL OR notes = '' OR TRIM(notes) = '')"
    );
    
    if (!$update_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($update_stmt, 'si', $notes, $character_id);
    $success = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    return $success;
}

// Main execution
echo "=== Character Notes Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Min length: {$options['min-length']} characters\n\n";

// Step 1: Query all characters and detect missing notes
echo "Step 1: Scanning database for characters with missing notes...\n";

$query = "SELECT id, character_name, notes FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
    
    if (isNotesMissing($row['notes'])) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'reason' => $row['notes'] === null ? 'null' : 
                       (trim($row['notes']) === '' ? 'empty_string' : 'placeholder'),
            'detected_at' => date('c')
        ];
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['missing_initially']} characters with missing notes\n\n";

// Step 2: Search for notes in files
echo "Step 2: Searching for notes in JSON and Markdown files...\n";

foreach ($missing_characters as $char) {
    $character_id = $char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Pass A: Search JSON files
    $json_result = searchJsonFiles($project_root, $character_name, $options['min-length']);
    
    if ($json_result !== null) {
        $notes = $json_result['notes'];
        $source_file = $json_result['source_file'];
        $length = $json_result['length'];
        
        // Update database
        $update_success = updateCharacterNotes($conn, $character_id, $notes, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($notes), 0, 8);
            
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
        $notes = $md_result['notes'];
        $source_file = $md_result['source_file'];
        $length = $md_result['length'];
        
        // Update database
        $update_success = updateCharacterNotes($conn, $character_id, $notes, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($notes), 0, 8);
            
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
    
    // No notes found
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
        echo "    ✗ No notes found\n";
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

// Generate missing_notes_report.json
$missing_report = [
    'generated_at' => date('c'),
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . '/missing_notes_report.json';
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_notes_report.json\n";

// Generate notes_updates.log
$log_path = $output_dir . '/notes_updates.log';
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: notes_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character Notes Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: notes_updates.log (empty)\n";
}

// Generate notes_not_found.json
$not_found_report = [
    'generated_at' => date('c'),
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . '/notes_not_found.json';
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: notes_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character Notes Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo "Notes backfilled: {$stats['backfilled']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Skipped (already had notes): {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_notes_report.json\n";
echo "- tools/repeatable/notes_updates.log\n";
echo "- tools/repeatable/notes_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

