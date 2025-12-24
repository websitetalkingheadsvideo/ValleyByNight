<?php
/**
 * Broad search for Misfortune character
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

echo "<h1>Broad Search for Misfortune</h1>\n";
echo "<pre>\n";

// Search with various patterns
echo "1. Searching all characters for 'misfortune' (case insensitive, partial match)...\n";
$search1 = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters 
     WHERE LOWER(character_name) LIKE '%misfortune%' 
     ORDER BY character_name");
echo "Found " . count($search1) . " character(s):\n";
foreach ($search1 as $c) {
    echo "  ID: {$c['id']} - '{$c['character_name']}' (Clan: {$c['clan']})\n";
}

// Search for exact match case-insensitive
echo "\n2. Searching for exact match (case insensitive)...\n";
$search2 = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters 
     WHERE LOWER(character_name) = 'misfortune' 
     ORDER BY character_name");
echo "Found " . count($search2) . " character(s):\n";
foreach ($search2 as $c) {
    echo "  ID: {$c['id']} - '{$c['character_name']}' (Clan: {$c['clan']})\n";
}

// Check ID 108 specifically
echo "\n3. Checking character with ID 108...\n";
$char_108 = db_fetch_one($conn, "SELECT id, character_name, clan FROM characters WHERE id = 108");
if ($char_108) {
    echo "  ✅ Found: ID {$char_108['id']} - '{$char_108['character_name']}' ({$char_108['clan']})\n";
    echo "  This should be Misfortune!\n";
} else {
    echo "  ❌ No character with ID 108\n";
}

// Check all characters with similar transformations
echo "\n4. Checking what 'MISFORTUNE' would match...\n";
$all_chars = db_fetch_all($conn, "SELECT id, character_name, clan FROM characters ORDER BY character_name");
echo "Testing transformations against all characters:\n";
$assignment_id = 'MISFORTUNE';
foreach ($all_chars as $c) {
    $name = $c['character_name'];
    $trans1 = strtoupper(str_replace(' ', '_', $name));
    $trans2 = strtoupper(str_replace([' ', '-'], '_', $name));
    $trans3 = strtoupper($name);
    
    if ($trans1 === $assignment_id || $trans2 === $assignment_id || $trans3 === $assignment_id) {
        echo "  ✅ MATCH FOUND: ID {$c['id']} - '{$name}' ({$c['clan']})\n";
        echo "     Transformation matches: ";
        if ($trans1 === $assignment_id) echo "space-to-underscore ";
        if ($trans2 === $assignment_id) echo "space-and-dash-to-underscore ";
        if ($trans3 === $assignment_id) echo "uppercase-only ";
        echo "\n";
    }
}

// Check JSON file
echo "\n5. Checking JSON reference file...\n";
$json_path = __DIR__ . '/../reference/Characters/Added to Database/npc__misfortune__108.json';
if (file_exists($json_path)) {
    $json_data = json_decode(file_get_contents($json_path), true);
    if ($json_data) {
        echo "  JSON character_name: '" . ($json_data['character_name'] ?? 'N/A') . "'\n";
        echo "  JSON id: " . ($json_data['id'] ?? 'N/A') . "\n";
        echo "  JSON clan: " . ($json_data['clan'] ?? 'N/A') . "\n";
        
        // Check if this exact name exists
        $json_name = $json_data['character_name'] ?? '';
        if ($json_name) {
            $check_json_name = db_fetch_one($conn, 
                "SELECT id, character_name, clan FROM characters WHERE character_name = ?", 
                "s", [$json_name]);
            if ($check_json_name) {
                echo "  ✅ Character with JSON name exists: ID {$check_json_name['id']}\n";
            } else {
                echo "  ❌ Character with JSON name does NOT exist in database\n";
            }
        }
    }
} else {
    echo "  JSON file not found\n";
}

echo "\n</pre>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>

