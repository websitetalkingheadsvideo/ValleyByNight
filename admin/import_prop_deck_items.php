<?php
/**
 * Import Prop Deck Items from JSON
 * Imports items from reference/Books_md_ready_fixed/Decks/prop_deck_items.json into the items table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized. Admin access required.');
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die('Database connection failed');
}

// Read JSON file
$jsonFile = __DIR__ . '/../reference/Books_md_ready_fixed/Decks/prop_deck_items.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile");
}

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (!$data || !isset($data['items']) || !is_array($data['items'])) {
    die('Invalid JSON structure or no items found');
}

$items = $data['items'];
$imported = 0;
$skipped = 0;
$errors = [];

// Function to convert rarity to lowercase
function normalizeRarity($rarity) {
    if (empty($rarity)) {
        return 'common'; // Default
    }
    return strtolower($rarity);
}

// Function to check if item already exists
function itemExists($conn, $name) {
    $query = "SELECT id FROM items WHERE name = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

echo "<h2>Importing Prop Deck Items</h2>";
echo "<p>Total items in JSON: " . count($items) . "</p>";
echo "<pre>";

foreach ($items as $item) {
    $name = trim($item['name'] ?? '');
    
    // Skip if name is empty or item already exists
    if (empty($name)) {
        $skipped++;
        $errors[] = "Skipped item with empty name";
        continue;
    }
    
    if (itemExists($conn, $name)) {
        $skipped++;
        echo "⏭️  Skipped (already exists): $name\n";
        continue;
    }
    
    // Prepare data
    $type = mysqli_real_escape_string($conn, $item['type'] ?? 'Misc');
    $category = mysqli_real_escape_string($conn, $item['category'] ?? '');
    $damage = !empty($item['damage']) ? mysqli_real_escape_string($conn, $item['damage']) : null;
    $range = !empty($item['range']) ? mysqli_real_escape_string($conn, $item['range']) : null;
    $rarity = normalizeRarity($item['rarity'] ?? 'common');
    $price = isset($item['price']) && $item['price'] !== null ? intval($item['price']) : 0;
    $description = mysqli_real_escape_string($conn, $item['description'] ?? '');
    $requirements = null; // JSON field - keep as null for now
    $image = !empty($item['image']) ? mysqli_real_escape_string($conn, $item['image']) : null;
    $notes = !empty($item['notes']) ? mysqli_real_escape_string($conn, $item['notes']) : null;
    
    // Insert item
    $query = "INSERT INTO items (name, type, category, damage, `range`, rarity, price, description, requirements, image, notes, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        $errors[] = "Prepare failed for $name: " . mysqli_error($conn);
        $skipped++;
        continue;
    }
    
    mysqli_stmt_bind_param($stmt, 'ssssssissss', 
        $name, $type, $category, $damage, $range, $rarity, $price, 
        $description, $requirements, $image, $notes
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $imported++;
        echo "✅ Imported: $name\n";
    } else {
        $skipped++;
        $errors[] = "Failed to import $name: " . mysqli_stmt_error($stmt);
        echo "❌ Failed: $name - " . mysqli_stmt_error($stmt) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "</pre>";

echo "<h3>Summary</h3>";
echo "<p><strong>Imported:</strong> $imported</p>";
echo "<p><strong>Skipped:</strong> $skipped</p>";

if (!empty($errors)) {
    echo "<h3>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

mysqli_close($conn);
?>

