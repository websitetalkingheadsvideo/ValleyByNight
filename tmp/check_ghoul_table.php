<?php
require_once __DIR__ . '/../includes/connect.php';

$result = mysqli_query($conn, "SHOW TABLES LIKE '%ghoul%'");
echo "Ghoul-related tables:\n";
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "\n";
}

// Check if ghoul_overlays exists and has records
$check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM ghoul_overlays WHERE character_id = 143");
if ($check) {
    $row = mysqli_fetch_assoc($check);
    echo "\nRecords in ghoul_overlays for character_id=143: " . $row['cnt'] . "\n";
} else {
    echo "\nError checking ghoul_overlays: " . mysqli_error($conn) . "\n";
}

