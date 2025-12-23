<?php
require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$result = mysqli_query($conn, 'DESCRIBE ghouls');
if ($result) {
    echo "Ghouls table structure:\n";
    echo str_repeat("=", 60) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf("%-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>

