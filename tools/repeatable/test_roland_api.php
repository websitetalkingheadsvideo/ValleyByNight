<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_id = 88; // Roland Cross

// Get abilities using the same query as the API
$abilities = db_fetch_all($conn,
    "SELECT 
        ca.ability_name,
        COALESCE(ca.ability_category, a.category) as ability_category,
        ca.level,
        ca.specialization
     FROM character_abilities ca
     LEFT JOIN abilities a ON ca.ability_name COLLATE utf8mb4_unicode_ci = a.name COLLATE utf8mb4_unicode_ci
     WHERE ca.character_id = ?
     ORDER BY COALESCE(ca.ability_category, a.category), ca.ability_name",
    'i', [$character_id]
);

echo "Roland Cross (ID: 88) - API Query Results:\n";
echo str_repeat('=', 60) . "\n";
echo "Total abilities returned: " . count($abilities) . "\n\n";

if (count($abilities) === 0) {
    echo "ERROR: No abilities found!\n";
    echo "\nChecking raw database query...\n";
    $raw = mysqli_query($conn, "SELECT * FROM character_abilities WHERE character_id = 88 LIMIT 5");
    if ($raw) {
        echo "Raw query found " . mysqli_num_rows($raw) . " rows\n";
        while ($row = mysqli_fetch_assoc($raw)) {
            print_r($row);
        }
    }
} else {
    $by_category = [];
    foreach ($abilities as $ability) {
        $cat = $ability['ability_category'] ?? 'NULL';
        if (!isset($by_category[$cat])) {
            $by_category[$cat] = [];
        }
        $by_category[$cat][] = $ability;
    }
    
    foreach ($by_category as $category => $abs) {
        echo "\n$category (" . count($abs) . "):\n";
        foreach ($abs as $a) {
            $display = $a['ability_name'];
            if ($a['level']) $display .= ' x' . $a['level'];
            if ($a['specialization']) $display .= ' (' . $a['specialization'] . ')';
            echo "  - $display\n";
        }
    }
}

