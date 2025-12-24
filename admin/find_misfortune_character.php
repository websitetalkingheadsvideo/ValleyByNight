<?php
/**
 * Find Misfortune character - check if it exists with different name
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

echo "<h1>Find Misfortune Character</h1>\n";
echo "<pre>\n";

// Search for Malkavian characters
echo "1. Searching for Malkavian characters...\n";
$malkavians = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters 
     WHERE clan = 'Malkavian' 
     ORDER BY character_name");
echo "Found " . count($malkavians) . " Malkavian character(s):\n";
foreach ($malkavians as $c) {
    echo "  ID: {$c['id']} - {$c['character_name']}\n";
}

// Search for characters with "misfortune" in name (case insensitive)
echo "\n2. Searching for characters with 'misfortune' in name...\n";
$misfortune_search = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters 
     WHERE LOWER(character_name) LIKE '%misfortune%' 
     ORDER BY character_name");
echo "Found " . count($misfortune_search) . " character(s):\n";
foreach ($misfortune_search as $c) {
    echo "  ID: {$c['id']} - {$c['character_name']}\n";
}

// Check if there's a character with ID 108 (from the JSON file reference)
echo "\n3. Checking for character with ID 108...\n";
$char_108 = db_fetch_one($conn, "SELECT id, character_name, clan FROM characters WHERE id = 108");
if ($char_108) {
    echo "  Found: ID {$char_108['id']} - {$char_108['character_name']} ({$char_108['clan']})\n";
} else {
    echo "  No character with ID 108\n";
}

// Check JSON file to see what the character should be
echo "\n4. Checking reference JSON file...\n";
$json_path = __DIR__ . '/../reference/Characters/Added to Database/npc__misfortune__108.json';
if (file_exists($json_path)) {
    echo "  JSON file exists: $json_path\n";
    $json_data = json_decode(file_get_contents($json_path), true);
    if ($json_data) {
        echo "  Character name in JSON: " . ($json_data['character_name'] ?? 'N/A') . "\n";
        echo "  Character ID in JSON: " . ($json_data['id'] ?? 'N/A') . "\n";
    }
} else {
    echo "  JSON file not found\n";
}

echo "\n</pre>\n";
echo "<p><strong>Solution:</strong> If Misfortune doesn't exist, you need to either:</p>\n";
echo "<ol>\n";
echo "<li>Create the character in the database</li>\n";
echo "<li>Or update the assignment to use an existing character's name</li>\n";
echo "</ol>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>

