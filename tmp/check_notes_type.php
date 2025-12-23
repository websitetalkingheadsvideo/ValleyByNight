<?php
require_once __DIR__ . '/../includes/connect.php';

$result = mysqli_query($conn, "SHOW COLUMNS FROM characters WHERE Field = 'notes'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "Notes column type: " . $row['Type'] . "\n";
    echo "Null: " . $row['Null'] . "\n";
} else {
    echo "Column not found\n";
}

