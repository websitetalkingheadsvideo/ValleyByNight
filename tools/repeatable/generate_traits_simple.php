<?php
/**
 * Simple trait generation for one character
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed\n");
}

mysqli_set_charset($conn, 'utf8mb4');

$character_id = 123; // Warner Jefferson

echo "Generating traits for character ID {$character_id}...\n";

// Get character data
$sql = "SELECT id, character_name, clan, nature, demeanor, concept, biography, generation
        FROM characters 
        WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $character_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$char = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

echo "Character: " . $char['character_name'] . "\n";
echo "Clan: " . ($char['clan'] ?? 'N/A') . "\n";

// Simple traits based on clan
$traits = [];
if ($char['clan'] === 'Ventrue') {
    $traits = [
        ['Commanding', 'Social', 'positive'],
        ['Diplomatic', 'Social', 'positive'],
        ['Poised', 'Social', 'positive'],
        ['Strategic', 'Mental', 'positive'],
        ['Analytical', 'Mental', 'positive']
    ];
}

echo "Generated " . count($traits) . " traits\n";

// Insert traits
$insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, ?)";
$inserted = 0;

foreach ($traits as $trait_data) {
    list($trait_name, $category, $type) = $trait_data;
    
    echo "Inserting: {$trait_name} ({$category}, {$type})\n";
    
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    if (!$insert_stmt) {
        echo "ERROR preparing statement: " . mysqli_error($conn) . "\n";
        continue;
    }
    
    $bind_result = mysqli_stmt_bind_param($insert_stmt, 'isss', $character_id, $trait_name, $category, $type);
    if (!$bind_result) {
        echo "ERROR binding parameters: " . mysqli_stmt_error($insert_stmt) . "\n";
        mysqli_stmt_close($insert_stmt);
        continue;
    }
    
    $execute_result = mysqli_stmt_execute($insert_stmt);
    if ($execute_result) {
        $inserted++;
        echo "  ✓ Inserted\n";
    } else {
        echo "  ✗ ERROR: " . mysqli_stmt_error($insert_stmt) . "\n";
    }
    
    mysqli_stmt_close($insert_stmt);
}

echo "\nDone. Inserted {$inserted} traits.\n";

