<?php
/**
 * Check if blueprint and moodboard columns exist in locations table
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Checking for blueprint and moodboard columns...\n\n";

// Check blueprint column
$check_blueprint = mysqli_query($conn, "SHOW COLUMNS FROM locations LIKE 'blueprint'");
if (mysqli_num_rows($check_blueprint) > 0) {
    $row = mysqli_fetch_assoc($check_blueprint);
    echo "✓ blueprint column EXISTS\n";
    echo "  Type: " . $row['Type'] . "\n";
    echo "  Null: " . $row['Null'] . "\n";
} else {
    echo "✗ blueprint column NOT FOUND\n";
}

echo "\n";

// Check moodboard column
$check_moodboard = mysqli_query($conn, "SHOW COLUMNS FROM locations LIKE 'moodboard'");
if (mysqli_num_rows($check_moodboard) > 0) {
    $row = mysqli_fetch_assoc($check_moodboard);
    echo "✓ moodboard column EXISTS\n";
    echo "  Type: " . $row['Type'] . "\n";
    echo "  Null: " . $row['Null'] . "\n";
} else {
    echo "✗ moodboard column NOT FOUND\n";
}

mysqli_close($conn);
?>
