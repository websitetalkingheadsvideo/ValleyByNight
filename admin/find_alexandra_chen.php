<?php
require_once __DIR__ . '/../includes/connect.php';

// Find character
$query = "SELECT id, character_name, clan, pc, generation FROM characters WHERE character_name LIKE '%Alexandra%' OR character_name LIKE '%Core%' OR character_name LIKE '%Chen%'";
$result = mysqli_query($conn, $query);

echo "=== Characters Found ===\n\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}\n";
    echo "Name: {$row['character_name']}\n";
    echo "Clan: {$row['clan']}\n";
    echo "PC: {$row['pc']}\n";
    echo "Generation: {$row['generation']}\n";
    echo "---\n";
}

// Check ghouls table structure
echo "\n=== Ghouls Table Structure ===\n";
$desc_query = "DESCRIBE ghouls";
$desc_result = mysqli_query($conn, $desc_query);
if ($desc_result) {
    while ($row = mysqli_fetch_assoc($desc_result)) {
        echo "{$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']}\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
    echo "Checking if table exists...\n";
    $table_check = "SHOW TABLES LIKE 'ghouls'";
    $table_result = mysqli_query($conn, $table_check);
    if (mysqli_num_rows($table_result) == 0) {
        echo "Ghouls table does not exist.\n";
    }
}

mysqli_close($conn);
?>


