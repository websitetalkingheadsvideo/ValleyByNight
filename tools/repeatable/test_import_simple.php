<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database connected\n";

// Test finding character
$name = "Jennifer Torrance";
$result = db_fetch_one($conn, 
    "SELECT id FROM characters WHERE character_name = ? LIMIT 1",
    's',
    [$name]
);

if ($result) {
    echo "Found character ID: " . $result['id'] . "\n";
} else {
    echo "Character not found\n";
}

// Test reading JSON
$json_file = __DIR__ . '/../../reference/Characters/Ghouls/Jennifer Torrance.json';
if (file_exists($json_file)) {
    echo "JSON file exists\n";
    $json_content = file_get_contents($json_file);
    $data = json_decode($json_content, true);
    if ($data) {
        echo "JSON parsed successfully\n";
        echo "Character name: " . ($data['name']['full'] ?? 'NOT FOUND') . "\n";
        echo "Domitor ID: " . ($data['domitor']['domitor_character_id'] ?? 'NOT FOUND') . "\n";
    } else {
        echo "JSON parse error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "JSON file not found: $json_file\n";
}

mysqli_close($conn);
?>

