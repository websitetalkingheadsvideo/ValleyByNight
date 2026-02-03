<?php
/**
 * Fix mage_characters: add id and user_id if missing (table existed with older schema).
 * Run once via browser or CLI: php database/fix_mage_characters_add_id_user_id.php
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM mage_characters");
if (!$result) {
    die("mage_characters table not found: " . mysqli_error($conn));
}

$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[$row['Field']] = true;
}
mysqli_free_result($result);

$has_id = isset($columns['id']);
$has_user_id = isset($columns['user_id']);

if ($has_id && $has_user_id) {
    echo "Table mage_characters already has id and user_id. No change needed.";
    mysqli_close($conn);
    exit;
}

$done = [];

if (!$has_id) {
    // Add id as AUTO_INCREMENT UNIQUE so we don't have to drop existing PRIMARY KEY (may be referenced by FK)
    if (!mysqli_query($conn, "ALTER TABLE mage_characters ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST")) {
        die("Failed to add id: " . mysqli_error($conn));
    }
    $done[] = "Added id INT AUTO_INCREMENT UNIQUE";
}

if (!$has_user_id) {
    $after = $has_id ? 'id' : 'id';
    if (!mysqli_query($conn, "ALTER TABLE mage_characters ADD COLUMN user_id INT NOT NULL DEFAULT 0 AFTER id")) {
        die("Failed to add user_id: " . mysqli_error($conn));
    }
    $done[] = "Added user_id INT NOT NULL DEFAULT 0";
}

echo "Done: " . implode("; ", $done);
mysqli_close($conn);
