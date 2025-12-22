<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_ids = [110, 125]; // Roxanne Vega and Roxanne Murphy

foreach ($character_ids as $character_id) {
    $char = db_fetch_one($conn, "SELECT id, character_name FROM characters WHERE id = ?", 'i', [$character_id]);
    
    if (!$char) {
        echo "Character ID $character_id not found\n\n";
        continue;
    }
    
    echo "=== " . $char['character_name'] . " (ID: $character_id) ===\n\n";
    
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
    
    if (count($abilities) == 0) {
        echo "No abilities found in database.\n\n";
    } else {
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
        echo "Total abilities: " . count($abilities) . "\n\n";
    }
    
    echo str_repeat("=", 50) . "\n\n";
}

