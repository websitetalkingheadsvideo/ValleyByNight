<?php
require_once __DIR__ . '/../includes/connect.php';

$result = mysqli_query($conn, "SELECT notes FROM characters WHERE character_name = 'Jennifer Torrance' LIMIT 1");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $notes = $row['notes'];
    echo "Notes type: " . gettype($notes) . "\n";
    echo "Notes length: " . strlen($notes) . "\n";
    echo "\nFirst 300 characters:\n";
    echo substr($notes, 0, 300) . "\n";
    
    // Check if it's JSON
    $decoded = json_decode($notes, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\n⚠️  WARNING: Notes is still JSON-encoded!\n";
    } else {
        echo "\n✅ Notes is plain text\n";
    }
} else {
    echo "Character not found\n";
}

