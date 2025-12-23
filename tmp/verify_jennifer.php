<?php
require_once __DIR__ . '/../includes/connect.php';

$result = mysqli_query($conn, "SELECT id, character_name, player_name, chronicle, clan, generation, nature, demeanor, sire, concept, pc, notes FROM characters WHERE character_name = 'Jennifer Torrance' LIMIT 1");

if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "Jennifer Torrance fields:\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($row as $key => $value) {
        if ($key === 'id') continue; // Skip ID in main display
        $display = $value === null ? 'NULL' : ($value === '' ? '(empty)' : $value);
        if (strlen($display) > 80) {
            $display = substr($display, 0, 80) . '...';
        }
        echo sprintf("%-20s: %s\n", $key, $display);
    }
    
    // Check ghoul_overlays
    echo "\nGhoul Overlay:\n";
    echo str_repeat("=", 60) . "\n";
    $char_id = $row['id'];
    $overlay_result = mysqli_query($conn, "SELECT character_id, regnant_id, blood_bond_stage FROM ghoul_overlays WHERE character_id = $char_id LIMIT 1");
    if ($overlay_result && $overlay = mysqli_fetch_assoc($overlay_result)) {
        foreach ($overlay as $k => $v) {
            echo sprintf("%-20s: %s\n", $k, $v ?? 'NULL');
        }
    } else {
        echo "No ghoul_overlay record found\n";
    }
} else {
    echo "Character not found\n";
}
