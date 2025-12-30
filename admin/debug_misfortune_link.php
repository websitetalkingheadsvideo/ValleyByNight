<?php
/**
 * Debug why Misfortune isn't showing as a link
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

echo "<h1>Debug Misfortune Link Issue</h1>\n";
echo "<pre>\n";

// Check what's in the assignment table
echo "1. Checking assignment table for Malkavian Primogen...\n";
$assignment = db_fetch_all($conn, 
    "SELECT * FROM camarilla_position_assignments 
     WHERE position_id LIKE '%malkavian%primogen%' OR position_id LIKE '%primogen%malkavian%'
     ORDER BY start_night DESC");
echo "Found " . count($assignment) . " assignment(s):\n";
foreach ($assignment as $a) {
    echo "  Position ID: " . ($a['position_id'] ?? 'N/A') . "\n";
    echo "  Character ID (assignment): " . ($a['character_id'] ?? 'N/A') . "\n";
    echo "  Start Night: " . ($a['start_night'] ?? 'N/A') . "\n";
    echo "  End Night: " . ($a['end_night'] ?? 'N/A') . "\n";
    echo "\n";
}

// Check what's in the characters table
echo "\n2. Checking characters table for Misfortune...\n";
$characters = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters 
     WHERE character_name LIKE '%Misfortune%' OR character_name LIKE '%misfortune%'");
echo "Found " . count($characters) . " character(s):\n";
foreach ($characters as $c) {
    echo "  ID: " . $c['id'] . "\n";
    echo "  Name: " . $c['character_name'] . "\n";
    echo "  Clan: " . ($c['clan'] ?? 'N/A') . "\n";
    echo "  Transformed (space to underscore): " . strtoupper(str_replace(' ', '_', $c['character_name'])) . "\n";
    echo "  Transformed (UPPER only): " . strtoupper($c['character_name']) . "\n";
    echo "\n";
}

// Test the JOIN manually
echo "\n3. Testing JOIN manually...\n";
if (!empty($assignment) && !empty($characters)) {
    $assignment_char_id = $assignment[0]['character_id'];
    $character_name = $characters[0]['character_name'];
    
    echo "Assignment character_id: '$assignment_char_id'\n";
    echo "Character name: '$character_name'\n";
    echo "\n";
    echo "Testing transformations:\n";
    echo "  UPPER(REPLACE('$character_name', ' ', '_')) = " . strtoupper(str_replace(' ', '_', $character_name)) . "\n";
    echo "  Match? " . (strtoupper(str_replace(' ', '_', $character_name)) === $assignment_char_id ? 'YES' : 'NO') . "\n";
    echo "\n";
    echo "  UPPER('$character_name') = " . strtoupper($character_name) . "\n";
    echo "  Match? " . (strtoupper($character_name) === $assignment_char_id ? 'YES' : 'NO') . "\n";
    
    // Try the actual query
    echo "\n4. Running actual JOIN query...\n";
    $test_query = "SELECT 
                    cpa.position_id,
                    cpa.character_id as assignment_character_id,
                    c.character_name,
                    c.id as character_id
                  FROM camarilla_position_assignments cpa
                  LEFT JOIN characters c ON (
                    UPPER(REPLACE(c.character_name, ' ', '_')) = cpa.character_id
                    OR UPPER(REPLACE(REPLACE(c.character_name, ' ', '_'), '-', '_')) = cpa.character_id
                    OR UPPER(c.character_name) = cpa.character_id
                  )
                  WHERE cpa.position_id = ?";
    
    $test_result = db_fetch_all($conn, $test_query, "s", [$assignment[0]['position_id']]);
    echo "JOIN result:\n";
    foreach ($test_result as $r) {
        echo "  Assignment ID: " . ($r['assignment_character_id'] ?? 'N/A') . "\n";
        echo "  Character Name: " . ($r['character_name'] ?? 'NULL - NO MATCH!') . "\n";
        echo "  Character ID: " . ($r['character_id'] ?? 'NULL - NO MATCH!') . "\n";
        echo "\n";
    }
}

echo "</pre>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>










