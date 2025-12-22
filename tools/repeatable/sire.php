<?php
/**
 * Sire Field Normalization Script
 * 
 * Scans all characters in the database and replaces blank/null/0 sire values with "unknown".
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/sire.php [--dry-run] [--verbose]
 * 
 * Options:
 *   --dry-run          Show what would be updated without writing to database
 *   --verbose          Show detailed progress for each character
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
    'verbose' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Sire Field Normalization Script\n\n";
        echo "Usage: php tools/repeatable/sire.php [options]\n\n";
        echo "Options:\n";
        echo "  --dry-run          Show what would be updated without writing to database\n";
        echo "  --verbose          Show detailed progress for each character\n";
        echo "  --help, -h         Show this help message\n\n";
        echo "This script replaces blank, null, or '0' sire values with 'unknown'.\n";
        exit(0);
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

// Statistics
$stats = [
    'total_scanned' => 0,
    'needs_update' => 0,
    'updated' => 0,
    'errors' => 0
];

// Main execution
echo "=== Sire Field Normalization Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n\n";

// Step 1: Query all characters and find those with blank/null/0 sire
echo "Step 1: Scanning database for characters with blank/null/0 sire values...\n";

$query = "SELECT id, character_name, sire FROM characters ORDER BY id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$characters_to_update = [];

while ($row = mysqli_fetch_assoc($result)) {
    $stats['total_scanned']++;
    
    $character_id = (int)$row['id'];
    $character_name = $row['character_name'];
    $sire = $row['sire'];
    
    // Check if sire needs to be updated
    $needs_update = false;
    $old_value = $sire;
    
    if ($sire === null || $sire === '') {
        $needs_update = true;
        $old_value = $sire === null ? 'NULL' : '(empty string)';
    } elseif (trim($sire) === '0' || $sire === '0') {
        $needs_update = true;
        $old_value = '0';
    }
    
    if ($needs_update) {
        $stats['needs_update']++;
        $characters_to_update[] = [
            'id' => $character_id,
            'character_name' => $character_name,
            'old_sire' => $old_value
        ];
        
        if ($options['verbose']) {
            echo "  Found: ID {$character_id} ({$character_name}) - sire: {$old_value}\n";
        }
    }
}

echo "Found {$stats['total_scanned']} total characters\n";
echo "Found {$stats['needs_update']} characters that need sire updated\n\n";

// Step 2: Update characters
if (count($characters_to_update) > 0) {
    echo "Step 2: " . ($options['dry-run'] ? "Would update" : "Updating") . " sire values to 'unknown'...\n";
    
    foreach ($characters_to_update as $char) {
        $character_id = $char['id'];
        $character_name = $char['character_name'];
        $old_sire = $char['old_sire'];
        
        if ($options['dry-run']) {
            echo "  [DRY RUN] Would update ID {$character_id} ({$character_name}): sire from '{$old_sire}' → 'unknown'\n";
            $stats['updated']++;
        } else {
            // Update the sire field
            $update_query = "UPDATE characters SET sire = 'unknown' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            
            if (!$stmt) {
                echo "  ERROR: Failed to prepare update for ID {$character_id}: " . mysqli_error($conn) . "\n";
                $stats['errors']++;
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'i', $character_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                if ($affected_rows > 0) {
                    echo "  ✓ Updated ID {$character_id} ({$character_name}): sire from '{$old_sire}' → 'unknown'\n";
                    $stats['updated']++;
                } else {
                    echo "  ⚠ No rows affected for ID {$character_id} ({$character_name})\n";
                }
            } else {
                echo "  ✗ ERROR: Failed to update ID {$character_id}: " . mysqli_stmt_error($stmt) . "\n";
                $stats['errors']++;
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    echo "\n";
} else {
    echo "Step 2: No characters need updating.\n\n";
}

// Step 3: Final summary
echo "=== Sire Field Normalization Summary ===\n";
echo "Total characters scanned: {$stats['total_scanned']}\n";
echo "Characters needing update: {$stats['needs_update']}\n";
echo "Characters " . ($options['dry-run'] ? "would be " : "") . "updated: {$stats['updated']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
    echo "Run without --dry-run to apply changes.\n";
}

echo "Done.\n";

mysqli_close($conn);

