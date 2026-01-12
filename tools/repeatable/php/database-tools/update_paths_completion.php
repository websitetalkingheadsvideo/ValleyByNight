<?php
/**
 * Update Paths Database Completion
 * 
 * Updates paths_master.description and path_powers.system_text, challenge_type, challenge_notes
 * for all Necromancy and Thaumaturgy paths and powers.
 * 
 * Uses transaction safety and prepared statements.
 * 
 * Usage:
 *   CLI: php database/update_paths_completion.php [--dry-run]
 *   Web: database/update_paths_completion.php?dry_run=1
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';
$dry_run = ($is_cli && in_array('--dry-run', $argv)) || (isset($_GET['dry_run']) && $_GET['dry_run'] == '1');

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Thaumaturgy path descriptions
$path_descriptions = [
    'The Path of Blood' => 'The foundational path of Thaumaturgy, focusing on manipulating vitae (blood) and its properties. This path allows Tremere to control, enhance, and weaponize blood itself.',
    'Movement of the Mind' => 'A path of telekinetic manipulation, allowing the thaumaturge to move objects, create force effects, and manipulate the physical world through mental power.',
    'The Lure of Flames' => 'A destructive path specializing in fire manipulation, allowing the practitioner to create, control, and weaponize flames against enemies.',
    'Path of the Focused Mind' => 'Enhances mental clarity, focus, and cognitive abilities. This path allows the thaumaturge to improve their concentration, resist mental attacks, and process information more efficiently.',
    'Weather Control' => 'Manipulates atmospheric conditions, allowing the thaumaturge to create fog, rain, wind, storms, and even lightning. Particularly useful for battlefield control and dramatic effects.',
    'Mastery of the Mortal Shell' => 'Focuses on controlling and manipulating the bodies of mortals (and sometimes vampires). This path allows the thaumaturge to cause physical malfunctions, seizures, and even complete bodily control.',
    "Neptune's Might" => 'Manipulates water in all its forms. Practitioners can see through water, control its flow, transform blood to water, and create watery barriers or dehydration effects.',
    'Path of Technomancy' => 'Allows manipulation and control of electronic devices and technology. Practitioners can cause equipment failures, remotely control devices, encrypt/decrypt data, and even telecommute their consciousness into machines.',
    'Spirit Manipulation' => 'Interacts with and controls spirits from the spirit world. Allows the thaumaturge to see spirits, communicate with them, command them, trap them, and even merge with them.',
    'Path of Conjuring' => 'The art of creating objects from nothing or transforming existing materials. Practitioners can summon simple objects, make them permanent, forge magical items, reverse conjurations, and even affect life itself.'
];

// Note: Power system_text, challenge_type, and challenge_notes should be populated from rulebooks.
// This script provides a framework for updates. The actual content should be extracted from
// the rulebooks database or reference materials to ensure rules accuracy.

// For now, we'll update path descriptions and create a structure for power updates.
// Power updates will need to be populated separately with accurate rulebook content.

$stats = [
    'paths_updated' => 0,
    'powers_updated' => 0,
    'errors' => []
];

// Start transaction
if (!$dry_run) {
    mysqli_begin_transaction($conn);
}

try {
    // Update path descriptions
    $update_path_stmt = mysqli_prepare($conn, 
        "UPDATE paths_master SET description = ? WHERE id = ? AND type = 'Thaumaturgy'"
    );
    
    if (!$update_path_stmt) {
        throw new Exception("Failed to prepare path update statement: " . mysqli_error($conn));
    }
    
    // Get Thaumaturgy paths needing descriptions
    $paths_query = "SELECT id, name FROM paths_master 
                    WHERE type = 'Thaumaturgy' 
                    AND (description IS NULL OR description = '')
                    ORDER BY name";
    $paths_result = mysqli_query($conn, $paths_query);
    
    while ($row = mysqli_fetch_assoc($paths_result)) {
        $path_id = (int)$row['id'];
        $path_name = $row['name'];
        
        // Normalize for matching (handle curly quotes)
        $normalized_db_name = str_replace(["\xE2\x80\x99", "\xE2\x80\x98"], "'", $path_name);
        
        // Try exact match first, then normalized match
        $description = null;
        if (isset($path_descriptions[$path_name])) {
            $description = $path_descriptions[$path_name];
        } else {
            // Try normalized match
            foreach ($path_descriptions as $key => $desc) {
                $normalized_key = str_replace(["\xE2\x80\x99", "\xE2\x80\x98"], "'", $key);
                if (strcasecmp($normalized_key, $normalized_db_name) === 0) {
                    $description = $desc;
                    break;
                }
            }
        }
        
        if ($description) {
            mysqli_stmt_bind_param($update_path_stmt, 'si', $description, $path_id);
            
            if ($dry_run) {
                echo ($is_cli ? "Would update path #$path_id ($path_name) with description\n" : 
                      "<p>Would update path #$path_id ($path_name) with description</p>\n");
                $stats['paths_updated']++;
            } else {
                if (mysqli_stmt_execute($update_path_stmt)) {
                    $stats['paths_updated']++;
                } else {
                    $stats['errors'][] = "Failed to update path #$path_id: " . mysqli_stmt_error($update_path_stmt);
                }
            }
        } else {
            $stats['errors'][] = "No description found for path: $path_name (ID: $path_id)";
        }
    }
    
    mysqli_free_result($paths_result);
    mysqli_stmt_close($update_path_stmt);
    
    // For powers, we need to update system_text, challenge_type, and challenge_notes
    // This requires rulebook content. For now, we'll prepare the update statements
    // but note that the actual content needs to be populated.
    
    // Note: Power updates should be done separately after extracting content from rulebooks
    // The following is a placeholder structure showing how updates would work
    
    if ($dry_run) {
        echo ($is_cli ? "\nPower updates require rulebook content extraction.\n" : 
              "<p>Power updates require rulebook content extraction.</p>\n");
    }
    
    if (!$dry_run) {
        mysqli_commit($conn);
        echo ($is_cli ? "\nTransaction committed successfully.\n" : 
              "<p>Transaction committed successfully.</p>\n");
    } else {
        echo ($is_cli ? "\nDRY RUN - No changes made.\n" : 
              "<p><strong>DRY RUN - No changes made.</strong></p>\n");
    }
    
} catch (Exception $e) {
    if (!$dry_run) {
        mysqli_rollback($conn);
    }
    $stats['errors'][] = "Transaction failed: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\nUpdate Summary:\n";
    echo "Paths updated: " . $stats['paths_updated'] . "\n";
    echo "Powers updated: " . $stats['powers_updated'] . "\n";
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "<!DOCTYPE html><html><head><title>Paths Update</title></head><body>";
    echo "<h1>Paths Database Update</h1>";
    echo "<p>Paths updated: <strong>" . $stats['paths_updated'] . "</strong></p>";
    echo "<p>Powers updated: <strong>" . $stats['powers_updated'] . "</strong></p>";
    if (!empty($stats['errors'])) {
        echo "<h2>Errors</h2><ul>";
        foreach ($stats['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    echo "</body></html>";
}

mysqli_close($conn);
?>

