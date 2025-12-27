<?php
/**
 * Import character from JSON
 * Direct import from Added to Database subdirectory
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Include database connection first
require_once __DIR__ . '/../../includes/connect.php';

// Include only the functions we need from import_characters.php
// Define flag to prevent main execution
define('IMPORT_CHARACTERS_AS_LIBRARY', true);
require_once __DIR__ . '/../../database/import_characters.php';

// Get character name from query parameter or default to Misfortune
$character_name = $_GET['character'] ?? 'Misfortune';
$json_file = __DIR__ . '/../../reference/Characters/Added to Database/' . $character_name . '.json';

if (!file_exists($json_file)) {
    die("Error: {$character_name}.json not found at: $json_file");
}

echo "<!DOCTYPE html><html><head><title>Import Character</title></head><body>";
echo "<h1>Importing {$character_name}</h1>";
echo "<pre>";

$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => [],
    'import_issues' => []
];

echo "Importing: {$character_name}.json\n";
echo "File path: $json_file\n\n";

if (importCharacterFile($conn, $json_file, $stats)) {
    echo "\n✅ SUCCESS!\n";
    echo "Character imported/updated successfully.\n";
} else {
    echo "\n❌ FAILED\n";
    echo "Check errors above.\n";
}

echo "\n=== Import Summary ===\n";
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

echo "</pre>";
echo "<p><a href='../../admin/camarilla_positions.php' class='btn btn-primary'>Back to Positions</a></p>";
echo "</body></html>";

mysqli_close($conn);
?>




