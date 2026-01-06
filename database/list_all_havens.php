<?php
/**
 * List all havens from the database
 * Shows which havens exist in DB and checks for corresponding JSON files
 */

require_once __DIR__ . '/../includes/connect.php';

header('Content-Type: text/plain; charset=utf-8');

$conn = getDbConnection();
if (!$conn) {
    die("Database connection failed\n");
}

// Also try direct connection if getDbConnection fails
if (!$conn) {
    require_once __DIR__ . '/../includes/connect.php';
    $conn = getDbConnection();
}

// Get all havens from database
$query = "SELECT id, name, type, district, owner_type, faction, pc_haven 
          FROM locations 
          WHERE type = 'Haven' 
          ORDER BY name";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn) . "\n");
}

$havens = [];
while ($row = mysqli_fetch_assoc($result)) {
    $havens[] = $row;
}

echo "=== ALL HAVENS IN DATABASE ===\n\n";
echo "Total: " . count($havens) . "\n\n";

foreach ($havens as $haven) {
    echo "ID: {$haven['id']}\n";
    echo "Name: {$haven['name']}\n";
    echo "Type: {$haven['type']}\n";
    echo "District: {$haven['district']}\n";
    echo "Owner: {$haven['owner_type']}\n";
    echo "Faction: {$haven['faction']}\n";
    echo "PC Haven: " . ($haven['pc_haven'] ? 'Yes' : 'No') . "\n";
    echo "---\n";
}

mysqli_close($conn);

// Also write to file
file_put_contents(__DIR__ . '/all_havens_list.txt', ob_get_contents());
