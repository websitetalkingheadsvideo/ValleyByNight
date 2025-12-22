<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_id = 125; // Roxanne Murphy

// Get abilities using the same query as view_character_api.php
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

echo "Roxanne Murphy (ID: 125) - Abilities as returned by view_character_api.php:\n\n";

$by_category = [];
foreach ($abilities as $a) {
    $cat = $a['ability_category'] ?? 'Unknown';
    if (!isset($by_category[$cat])) {
        $by_category[$cat] = [];
    }
    $by_category[$cat][] = $a;
}

foreach ($by_category as $category => $abilities_list) {
    echo "$category:\n";
    foreach ($abilities_list as $a) {
        $spec = $a['specialization'] ? ' (' . $a['specialization'] . ')' : '';
        echo "  - {$a['ability_name']} x{$a['level']}$spec\n";
    }
    echo "\n";
}

echo "Total abilities: " . count($abilities) . "\n";

