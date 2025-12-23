<?php
require_once __DIR__ . '/../includes/connect.php';

// Get character ID
$char_result = mysqli_query($conn, "SELECT id FROM characters WHERE character_name = 'Jennifer Torrance' LIMIT 1");
if ($char_result && $char_row = mysqli_fetch_assoc($char_result)) {
    $char_id = $char_row['id'];
    echo "Character ID: $char_id\n\n";
    
    // Check ghoul_overlays
    $overlay_result = mysqli_query($conn, "SELECT * FROM ghoul_overlays WHERE character_id = $char_id LIMIT 1");
    if ($overlay_result && $overlay = mysqli_fetch_assoc($overlay_result)) {
        echo "Ghoul Overlay Record:\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($overlay as $k => $v) {
            $display = $v === null ? 'NULL' : ($v === '' ? '(empty)' : $v);
            if (strlen($display) > 60) {
                $display = substr($display, 0, 60) . '...';
            }
            echo sprintf("%-25s: %s\n", $k, $display);
        }
    } else {
        echo "No ghoul_overlay record found for character_id=$char_id\n";
        echo "Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Character not found\n";
}

