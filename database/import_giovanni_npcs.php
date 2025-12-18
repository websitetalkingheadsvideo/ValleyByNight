<?php
/**
 * Import Giovanni NPCs from array JSON file
 * 
 * Processes Giovanni_NPC_database_ready.json which contains an array of characters
 * and imports each one using the existing import functions.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Import user_id (admin/ST account) - check if already defined
if (!defined('IMPORT_USER_ID')) {
    define('IMPORT_USER_ID', 1);
}

// Include the import functions from import_characters.php as a library
define('IMPORT_CHARACTERS_AS_LIBRARY', true);
require_once __DIR__ . '/import_characters.php';

// Statistics
$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => []
];

// File to import
$json_file = __DIR__ . '/../reference/Characters/Giovanni_NPC_database_ready.json';

if (!file_exists($json_file)) {
    die("File not found: $json_file\n");
}

// Output header
if ($is_cli) {
    echo "Giovanni NPC Import Script\n";
    echo "=========================\n\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Giovanni NPC Import</title></head><body>";
    echo "<h1>Giovanni NPC Import Script</h1>";
    echo "<pre>";
}

echo "Reading file: " . basename($json_file) . "\n\n";

try {
    // Read and parse JSON
    $jsonContent = file_get_contents($json_file);
    if ($jsonContent === false) {
        throw new Exception("Failed to read file: $json_file");
    }
    
    // Parse JSON
    $characters = json_decode($jsonContent, true);
    if ($characters === null) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    if (!is_array($characters)) {
        throw new Exception("Expected array of characters, got: " . gettype($characters));
    }
    
    echo "Found " . count($characters) . " character(s) to import.\n\n";
    
    // Process each character
    foreach ($characters as $index => $characterData) {
        $character_name = $characterData['character_name'] ?? "Character #" . ($index + 1);
        echo "Processing: $character_name... ";
        
        try {
            // Normalize data
            $characterData = normalizeCharacterData($characterData);
            
            // Handle status field - extract character_status if present
            if (isset($characterData['character_status'])) {
                $characterData['status'] = $characterData['character_status'];
            } elseif (isset($characterData['status']) && is_array($characterData['status'])) {
                // Status is an object, extract string status
                $characterData['status'] = 'active'; // Default
            }
            
            // Validate
            $errors = validateCharacterData($characterData, basename($json_file));
            if (!empty($errors)) {
                throw new Exception("Validation errors: " . implode(", ", $errors));
            }
            
            // Start transaction
            db_begin_transaction($conn);
            
            try {
                // Check if character exists
                $character_name = cleanString($characterData['character_name'] ?? '');
                $existing_id = findCharacterByName($conn, $character_name);
                $is_update = $existing_id !== null;
                
                // Upsert main character record
                $character_id = upsertCharacter($conn, $characterData);
                
                // Import related tables
                importAbilities($conn, $character_id, $characterData);
                importDisciplines($conn, $character_id, $characterData);
                importTraits($conn, $character_id, $characterData);
                importNegativeTraits($conn, $character_id, $characterData);
                importCoteries($conn, $character_id, $characterData);
                importRelationships($conn, $character_id, $characterData);
                importBackgrounds($conn, $character_id, $characterData);
                importMeritsFlaws($conn, $character_id, $characterData);
                
                // Commit transaction
                db_commit($conn);
                
                $stats['processed']++;
                if ($is_update) {
                    $stats['updated']++;
                    echo "UPDATED\n";
                } else {
                    $stats['inserted']++;
                    echo "INSERTED\n";
                }
            } catch (Exception $e) {
                db_rollback($conn);
                throw $e;
            }
        } catch (Exception $e) {
            $stats['errors'][] = "$character_name: " . $e->getMessage();
            $stats['skipped']++;
            echo "FAILED - " . $e->getMessage() . "\n";
        }
    }
    
    // Output summary
    echo "\n";
    echo "=== Import Summary ===\n";
    echo "Processed: {$stats['processed']}\n";
    echo "Inserted: {$stats['inserted']}\n";
    echo "Updated: {$stats['updated']}\n";
    echo "Skipped: {$stats['skipped']}\n";
    
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    $stats['errors'][] = "Fatal: " . $e->getMessage();
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);
?>

