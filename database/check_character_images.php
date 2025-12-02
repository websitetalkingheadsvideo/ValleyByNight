<?php
/**
 * Check Character Images in Database
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$characters_to_check = [
    'Helena Crowly',
    'Charles "C.W." Whitford',
    'Naomi Blackbird',
    'Lilith Nightshade',
    'Butch Reed',
    'Alistaire'
];

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Character Image Check</title></head><body>";
echo "<h1>Character Image Status</h1>";
echo "<pre>";

foreach ($characters_to_check as $name) {
    $sql = "SELECT id, character_name, character_image FROM characters WHERE character_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $image = $row['character_image'] ?: '(empty)';
            echo sprintf("%-30s ID: %-5s Image: %s\n", $row['character_name'], $row['id'], $image);
        } else {
            echo sprintf("%-30s NOT FOUND\n", $name);
        }
        
        mysqli_stmt_close($stmt);
    }
}

echo "</pre></body></html>";
mysqli_close($conn);
?>

