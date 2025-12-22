<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_id = 88; // Roland Cross

$result = mysqli_query($conn, "SELECT ability_name, ability_category, level FROM character_abilities WHERE character_id = $character_id ORDER BY ability_category, ability_name");

echo "Roland Cross (ID: 88) Abilities:\n";
echo str_repeat('-', 50) . "\n";

$by_category = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cat = $row['ability_category'] ?? 'NULL';
    if (!isset($by_category[$cat])) {
        $by_category[$cat] = [];
    }
    $by_category[$cat][] = $row['ability_name'] . ' x' . $row['level'];
}

foreach ($by_category as $category => $abilities) {
    echo "\n$category:\n";
    foreach ($abilities as $ability) {
        echo "  - $ability\n";
    }
}

echo "\nTotal: " . array_sum(array_map('count', $by_category)) . " abilities\n";

