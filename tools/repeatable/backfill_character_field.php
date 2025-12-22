<?php
/**
 * Generic Character Field Backfill Script
 * 
 * Identifies characters with missing fields, searches JSON and Markdown files
 * for character data, and updates database records with found content.
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/backfill_character_field.php --field=<field_name> [options]
 * 
 * Required Options:
 *   --field=<name>      Database field name to backfill (e.g., biography, appearance, notes, nature, demeanor, concept, sire, equipment)
 * 
 * Optional Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
 *   --min-length=N     Minimum field length threshold (default: auto-detected based on field)
 *   --json-path=<path> JSON field path (e.g., "nature" or "status.notes" or "appearance_detailed.detailed_description")
 *   --help             Show this help message
 * 
 * Output Files:
 *   - missing_<field>_report.json    List of characters missing the field
 *   - <field>_updates.log            Log of all database updates
 *   - <field>_not_found.json         Characters where no data was found
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

// Field configuration - defines extraction strategies for different fields
$field_configs = [
    'biography' => [
        'min_length' => 50,
        'json_paths' => ['biography'],
        'markdown_patterns' => [
            '/^#\s*Character\s+History:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
            '/^##\s*History\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Biography\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Backstory\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^#\s*History\s*\n+?(.*?)(?=\n#|\Z)/ims',
            '/^#\s*Biography\s*\n+?(.*?)(?=\n#|\Z)/ims'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added']
    ],
    'appearance' => [
        'min_length' => 30,
        'json_paths' => ['appearance', 'appearance_detailed.detailed_description', 'appearance_detailed.short_summary'],
        'markdown_patterns' => [
            '/^#\s*Character\s+Appearance:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
            '/^#\s*Appearance:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
            '/^##\s*Appearance\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Physical\s+Description\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Description\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^#\s*Physical\s+Description\s*\n+?(.*?)(?=\n#|\Z)/ims'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'N/A']
    ],
    'notes' => [
        'min_length' => 20,
        'json_paths' => ['notes', 'status.notes'],
        'markdown_patterns' => [
            '/^#\s*Character\s+Notes:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
            '/^#\s*Notes:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
            '/^##\s*Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Player\s+Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^##\s*Storyteller\s+Notes\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None']
    ],
    'nature' => [
        'min_length' => 3,
        'json_paths' => ['nature'],
        'markdown_patterns' => [
            '/^Nature:\s*([^\n]+)/im',
            '/^#\s*Nature:\s*([^\n]+)/im',
            '/^##\s*Nature\s*\n+?([^\n#]+)/im',
            '/^#\s*Nature\s*\n+?([^\n#]+)/im',
            '/Nature[:\s]+([A-Za-z][A-Za-z\s]+?)(?:\n|$)/im'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'Unknown'],
        'extract_first_word' => true // Nature is typically a single word
    ],
    'demeanor' => [
        'min_length' => 3,
        'json_paths' => ['demeanor'],
        'markdown_patterns' => [
            '/^Demeanor:\s*([^\n]+)/im',
            '/^#\s*Demeanor:\s*([^\n]+)/im',
            '/^##\s*Demeanor\s*\n+?([^\n#]+)/im',
            '/^#\s*Demeanor\s*\n+?([^\n#]+)/im',
            '/Demeanor[:\s]+([A-Za-z][A-Za-z\s]+?)(?:\n|$)/im'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'Unknown'],
        'extract_first_word' => true
    ],
    'concept' => [
        'min_length' => 10,
        'json_paths' => ['concept'],
        'markdown_patterns' => [
            '/^Concept:\s*([^\n]+)/im',
            '/^#\s*Concept:\s*([^\n]+)/im',
            '/^##\s*Concept\s*\n+?([^\n#]+)/im',
            '/^#\s*Concept\s*\n+?([^\n#]+)/im',
            '/Concept[:\s]+([^\n]+?)(?:\n|$)/im'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None']
    ],
    'sire' => [
        'min_length' => 3,
        'json_paths' => ['sire'],
        'markdown_patterns' => [
            '/^Sire:\s*([^\n]+)/im',
            '/^#\s*Sire:\s*([^\n]+)/im',
            '/^##\s*Sire\s*\n+?([^\n#]+)/im',
            '/^#\s*Sire\s*\n+?([^\n#]+)/im',
            '/Sire[:\s]+([^\n]+?)(?:\n|$)/im'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'Unknown']
    ],
    'equipment' => [
        'min_length' => 10,
        'json_paths' => ['equipment'],
        'markdown_patterns' => [
            '/^Equipment:\s*([^\n]+)/im',
            '/^#\s*Equipment:\s*([^\n]+)/im',
            '/^##\s*Equipment\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
            '/^#\s*Equipment\s*\n+?(.*?)(?=\n#|\Z)/ims'
        ],
        'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None']
    ]
];

// Parse command-line arguments
$options = [
    'field' => null,
    'dry-run' => false,
    'verbose' => false,
    'min-length' => null,
    'json-path' => null,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (preg_match('/^--field=(.+)$/', $arg, $matches)) {
        $options['field'] = $matches[1];
    } elseif (preg_match('/^--min-length=(\d+)$/', $arg, $matches)) {
        $options['min-length'] = (int)$matches[1];
    } elseif (preg_match('/^--json-path=(.+)$/', $arg, $matches)) {
        $options['json-path'] = $matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help'] || !$options['field']) {
    echo "Generic Character Field Backfill Script\n\n";
    echo "Usage: php tools/repeatable/backfill_character_field.php --field=<field_name> [options]\n\n";
    echo "Required Options:\n";
    echo "  --field=<name>      Database field name to backfill\n";
    echo "                      Supported fields: " . implode(', ', array_keys($field_configs)) . "\n";
    echo "                      Or any other field name (will use defaults)\n\n";
    echo "Optional Options:\n";
    echo "  --dry-run          Show what would be updated without writing to database\n";
    echo "  --verbose          Show detailed progress for each character\n";
    echo "  --min-length=N     Minimum field length threshold (default: auto-detected)\n";
    echo "  --json-path=<path> JSON field path (e.g., \"nature\" or \"status.notes\")\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/backfill_character_field.php --field=biography --dry-run\n";
    echo "  php tools/repeatable/backfill_character_field.php --field=demeanor --min-length=3\n";
    echo "  php tools/repeatable/backfill_character_field.php --field=custom_field --json-path=custom.field\n\n";
    exit(0);
}

$field_name = $options['field'];

// Get field configuration or use defaults
$field_config = $field_configs[$field_name] ?? [
    'min_length' => 10,
    'json_paths' => [$field_name],
    'markdown_patterns' => [
        '/^#\s*' . preg_quote(ucfirst($field_name), '/') . ':\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',
        '/^##\s*' . preg_quote(ucfirst($field_name), '/') . '\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
        '/^' . preg_quote(ucfirst($field_name), '/') . ':\s*([^\n]+)/im'
    ],
    'placeholders' => ['TBD', 'N/A', 'TODO', 'To be determined', 'TBA', 'To be added', 'None', 'Unknown']
];

// Override with command-line options
if ($options['min-length'] !== null) {
    $field_config['min_length'] = $options['min-length'];
}
if ($options['json-path'] !== null) {
    $field_config['json_paths'] = [$options['json-path']];
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
 * Check if field value is considered missing
 */
function isFieldMissing(?string $value, array $placeholders): bool {
    if ($value === null) {
        return true;
    }
    
    $trimmed = trim($value);
    
    if ($trimmed === '') {
        return true;
    }
    
    // Check for placeholder patterns
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
 * Get value from JSON using dot-notation path
 */
function getJsonValue(array $data, string $path): ?string {
    $parts = explode('.', $path);
    $current = $data;
    
    foreach ($parts as $part) {
        if (!is_array($current) || !isset($current[$part])) {
            return null;
        }
        $current = $current[$part];
    }
    
    if (is_string($current)) {
        return trim($current);
    }
    
    return null;
}

/**
 * Extract field value from JSON file
 */
function extractFieldFromJson(string $filepath, string $target_character_name, array $json_paths): ?string {
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
    
    // Try each JSON path
    foreach ($json_paths as $path) {
        $value = getJsonValue($data, $path);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    
    return null;
}

/**
 * Extract field value from Markdown file
 */
function extractFieldFromMarkdown(string $filepath, string $target_character_name, array $patterns, bool $extract_first_word = false): ?string {
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
    
    // Try each pattern
    $extracted_text = null;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $extracted_text = $matches[1] ?? $matches[0] ?? '';
            if (trim($extracted_text) !== '') {
                break;
            }
        }
    }
    
    if ($extracted_text === null || trim($extracted_text) === '') {
        return null;
    }
    
    // Clean markdown formatting
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
    
    // If extract_first_word is true, take only the first word/phrase
    if ($extract_first_word) {
        if (strpos($cleaned, "\n") !== false) {
            $cleaned = trim(explode("\n", $cleaned)[0]);
        }
        $cleaned = preg_split('/[\s,;]+/', $cleaned)[0];
        $cleaned = trim($cleaned);
    }
    
    return $cleaned;
}

/**
 * Search for field value in JSON files
 */
function searchJsonFiles(string $project_root, string $character_name, array $json_paths, int $min_length): ?array {
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
                $value = extractFieldFromJson($filepath, $character_name, $json_paths);
                
                if ($value !== null && strlen($value) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'value' => $value,
                        'length' => strlen($value)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Search for field value in Markdown files
 */
function searchMarkdownFiles(string $project_root, string $character_name, array $patterns, int $min_length, bool $extract_first_word = false): ?array {
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
                $value = extractFieldFromMarkdown($filepath, $character_name, $patterns, $extract_first_word);
                
                if ($value !== null && strlen($value) >= $min_length) {
                    return [
                        'source_file' => $filepath,
                        'value' => $value,
                        'length' => strlen($value)
                    ];
                }
            }
        }
    }
    
    return null;
}

/**
 * Update character field in database
 */
function updateCharacterField(mysqli $conn, string $field_name, int $character_id, string $value, bool $dry_run): bool {
    // Re-check that field is still empty (idempotency safeguard)
    $check_stmt = mysqli_prepare($conn, "SELECT `{$field_name}` FROM characters WHERE id = ?");
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
    
    // If field is no longer empty, skip update
    $current_value = $row[$field_name] ?? null;
    if ($current_value !== null && trim($current_value) !== '') {
        return false;
    }
    
    if ($dry_run) {
        return true; // Return true to indicate "would update"
    }
    
    // Update with safeguard condition
    $escaped_field = mysqli_real_escape_string($conn, $field_name);
    $update_stmt = mysqli_prepare($conn, 
        "UPDATE characters SET `{$escaped_field}` = ? WHERE id = ? AND (`{$escaped_field}` IS NULL OR `{$escaped_field}` = '' OR TRIM(`{$escaped_field}`) = '')"
    );
    
    if (!$update_stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($update_stmt, 'si', $value, $character_id);
    $success = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    return $success;
}

// Main execution
echo "=== Character {$field_name} Backfill Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
echo "Min length: {$field_config['min_length']} characters\n";
echo "JSON paths: " . implode(', ', $field_config['json_paths']) . "\n\n";

// Step 1: Query all characters and detect missing field
echo "Step 1: Scanning database for characters with missing {$field_name}...\n";

$escaped_field = mysqli_real_escape_string($conn, $field_name);
$query = "SELECT id, character_name, `{$escaped_field}` FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$all_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_characters[] = $row;
    $stats['total_scanned']++;
    
    $field_value = $row[$field_name] ?? null;
    if (isFieldMissing($field_value, $field_config['placeholders'])) {
        $stats['missing_initially']++;
        $missing_characters[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'reason' => $field_value === null ? 'null' : 
                       (trim($field_value) === '' ? 'empty_string' : 'placeholder'),
            'detected_at' => date('c')
        ];
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['missing_initially']} characters with missing {$field_name}\n\n";

// Step 2: Search for field value in files
echo "Step 2: Searching for {$field_name} in JSON and Markdown files...\n";

foreach ($missing_characters as $char) {
    $character_id = $char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    // Pass A: Search JSON files
    $json_result = searchJsonFiles($project_root, $character_name, $field_config['json_paths'], $field_config['min_length']);
    
    if ($json_result !== null) {
        $value = $json_result['value'];
        $source_file = $json_result['source_file'];
        $length = $json_result['length'];
        
        // Update database
        $update_success = updateCharacterField($conn, $field_name, $character_id, $value, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($value), 0, 8);
            
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
    $md_result = searchMarkdownFiles(
        $project_root, 
        $character_name, 
        $field_config['markdown_patterns'], 
        $field_config['min_length'],
        $field_config['extract_first_word'] ?? false
    );
    
    if ($md_result !== null) {
        $value = $md_result['value'];
        $source_file = $md_result['source_file'];
        $length = $md_result['length'];
        
        // Update database
        $update_success = updateCharacterField($conn, $field_name, $character_id, $value, $options['dry-run']);
        
        if ($update_success) {
            $stats['backfilled']++;
            $hash = substr(md5($value), 0, 8);
            
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
    
    // No value found
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
        echo "    ✗ No {$field_name} found\n";
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

// Generate missing field report
$missing_report = [
    'generated_at' => date('c'),
    'field_name' => $field_name,
    'total_characters' => $stats['total_scanned'],
    'missing_count' => $stats['missing_initially'],
    'characters' => $missing_characters
];

$missing_report_path = $output_dir . "/missing_{$field_name}_report.json";
file_put_contents($missing_report_path, json_encode($missing_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: missing_{$field_name}_report.json\n";

// Generate updates log
$log_path = $output_dir . "/{$field_name}_updates.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: {$field_name}_updates.log\n";
} else {
    // Create empty log file
    file_put_contents($log_path, "# Character {$field_name} Update Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: {$field_name}_updates.log (empty)\n";
}

// Generate not found report
$not_found_report = [
    'generated_at' => date('c'),
    'field_name' => $field_name,
    'characters' => $not_found_characters
];

$not_found_report_path = $output_dir . "/{$field_name}_not_found.json";
file_put_contents($not_found_report_path, json_encode($not_found_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: {$field_name}_not_found.json\n";

echo "\n";

// Step 4: Final summary
echo "=== Character {$field_name} Backfill Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Missing initially: {$stats['missing_initially']}\n";
echo ucfirst($field_name) . "s backfilled: {$stats['backfilled']}\n";
echo "Still missing: {$stats['still_missing']}\n";
echo "Skipped (already had {$field_name}): {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/missing_{$field_name}_report.json\n";
echo "- tools/repeatable/{$field_name}_updates.log\n";
echo "- tools/repeatable/{$field_name}_not_found.json\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

