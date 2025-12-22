<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_id = 125; // Roxanne Murphy

// Check database
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM character_abilities WHERE character_id = $character_id");
$row = mysqli_fetch_assoc($result);
echo "Roxanne Murphy (ID: 125) - Abilities in database: " . $row['count'] . "\n\n";

// Check character name
$result = mysqli_query($conn, "SELECT character_name FROM characters WHERE id = $character_id");
$char = mysqli_fetch_assoc($result);
echo "Character name: " . ($char['character_name'] ?? 'NOT FOUND') . "\n\n";

// Check JSON files
$json_files = [
    dirname(__DIR__, 2) . '/reference/Characters/Added to Database/npc__roxanne_murphy__125.json',
    dirname(__DIR__, 2) . '/reference/Characters/Added to Database/Roxanne_Murphy.json'
];

foreach ($json_files as $file) {
    if (file_exists($file)) {
        echo "Checking: " . basename($file) . "\n";
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            echo "  Character name in JSON: " . ($data['character_name'] ?? 'NOT FOUND') . "\n";
            if (isset($data['abilities'])) {
                if (is_array($data['abilities'])) {
                    if (count($data['abilities']) > 0) {
                        echo "  Abilities found: " . count($data['abilities']) . "\n";
                        if (isset($data['abilities'][0]) && is_array($data['abilities'][0])) {
                            echo "  Format: Array of objects\n";
                            echo "  Sample: " . json_encode($data['abilities'][0]) . "\n";
                        } else {
                            echo "  Format: Category-based\n";
                            foreach (['Physical', 'Social', 'Mental'] as $cat) {
                                if (isset($data['abilities'][$cat]) && is_array($data['abilities'][$cat])) {
                                    echo "    $cat: " . count($data['abilities'][$cat]) . " abilities\n";
                                }
                            }
                        }
                    } else {
                        echo "  Abilities array is empty\n";
                    }
                } else {
                    echo "  Abilities is not an array\n";
                }
            } else {
                echo "  No abilities field found\n";
            }
        }
        echo "\n";
    }
}

