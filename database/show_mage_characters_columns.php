<?php
/**
 * Show current mage_characters columns (diagnostic).
 * Run via browser: database/show_mage_characters_columns.php
 */
require_once __DIR__ . '/../includes/connect.php';
header('Content-Type: text/plain; charset=utf-8');
if (!$conn) {
    die("Database connection failed.");
}
$r = mysqli_query($conn, "SHOW COLUMNS FROM mage_characters");
if (!$r) {
    die("Table mage_characters not found or error: " . mysqli_error($conn));
}
echo "mage_characters columns:\n";
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
}
mysqli_free_result($r);
$fk = mysqli_query($conn, "SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = 'mage_characters' AND TABLE_SCHEMA = DATABASE()");
if ($fk && mysqli_num_rows($fk) > 0) {
    echo "\nForeign keys referencing mage_characters:\n";
    while ($row = mysqli_fetch_assoc($fk)) {
        echo $row['TABLE_NAME'] . '.' . $row['COLUMN_NAME'] . " -> " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . " (" . $row['CONSTRAINT_NAME'] . ")\n";
    }
    mysqli_free_result($fk);
}
mysqli_close($conn);
