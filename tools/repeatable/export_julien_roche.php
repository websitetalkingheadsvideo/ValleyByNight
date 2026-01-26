<?php
/**
 * Export Julien Roche from database to JSON template format
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/connect.php';

$character_id = 151; // Julien Roche
$character_name = 'Julien Roche';

// Get main character data
$char = db_fetch_one($conn, "SELECT * FROM characters WHERE id = ?", 'i', [$character_id]);

if (!$char) {
    die("Character not found: {$character_name} (ID: {$character_id})\n");
}

// Get traits
$traits_raw = db_fetch_all($conn, 
    "SELECT trait_name, trait_category, trait_type 
     FROM character_traits 
     WHERE character_id = ?", 
    'i', [$character_id]
);

$traits = [
    'Physical' => [],
    'Social' => [],
    'Mental' => []
];
$negativeTraits = [
    'Physical' => [],
    'Social' => [],
    'Mental' => []
];

foreach ($traits_raw as $trait) {
    $category = $trait['trait_category'] ?? 'Physical';
    $type = $trait['trait_type'] ?? 'positive';
    
    if ($type === 'negative') {
        $negativeTraits[$category][] = $trait['trait_name'];
    } else {
        $traits[$category][] = $trait['trait_name'];
    }
}

// Get abilities
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

// Get disciplines
$disciplines = db_fetch_all($conn,
    "SELECT discipline_name, level
     FROM character_disciplines
     WHERE character_id = ?
     ORDER BY discipline_name",
    'i', [$character_id]
);

// Get backgrounds
$backgrounds_raw = db_fetch_all($conn,
    "SELECT background_name, level
     FROM character_backgrounds
     WHERE character_id = ?
     ORDER BY background_name",
    'i', [$character_id]
);

$backgrounds = [];
foreach ($backgrounds_raw as $bg) {
    $backgrounds[$bg['background_name']] = (int)$bg['level'];
}

// Get morality
$morality = db_fetch_one($conn,
    "SELECT * FROM character_morality WHERE character_id = ?",
    'i', [$character_id]
);

// Get merits & flaws
$merits_flaws = db_fetch_all($conn,
    "SELECT name, type, category, point_value, point_cost, description
     FROM character_merits_flaws
     WHERE character_id = ?
     ORDER BY type, name",
    'i', [$character_id]
);

// Get status
$status = db_fetch_one($conn,
    "SELECT * FROM character_status WHERE character_id = ?",
    'i', [$character_id]
);

if (!$status) {
    $status = [
        'sect_status' => $char['camarilla_status'] ?? '',
        'clan_status' => '',
        'city_status' => '',
        'health_levels' => 7,
        'blood_pool_current' => null,
        'blood_pool_maximum' => null,
        'blood_per_turn' => 1,
        'xp_total' => $char['experience_total'] ?? 0,
        'xp_spent' => $char['experience_spent'] ?? 0,
        'xp_available' => ($char['experience_total'] ?? 0) - ($char['experience_spent'] ?? 0),
        'notes' => ''
    ];
}

// Get coteries
$coteries = db_fetch_all($conn,
    "SELECT coterie_name, coterie_type, role, description
     FROM character_coteries
     WHERE character_id = ?",
    'i', [$character_id]
) ?: [];

// Get relationships
$relationships = db_fetch_all($conn,
    "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
     FROM character_relationships
     WHERE character_id = ?
     ORDER BY relationship_type, related_character_name",
    'i', [$character_id]
) ?: [];

// Build JSON structure
$json_data = [
    'id' => (int)$char['id'],
    'user_id' => (int)$char['user_id'],
    'character_name' => $char['character_name'],
    'player_name' => $char['player_name'] ?? 'NPC',
    'chronicle' => $char['chronicle'] ?? 'Valley by Night',
    'nature' => $char['nature'] ?? '',
    'demeanor' => $char['demeanor'] ?? '',
    'concept' => $char['concept'] ?? '',
    'clan' => $char['clan'] ?? '',
    'generation' => $char['generation'] ? (int)$char['generation'] : null,
    'sire' => $char['sire'] ?? '',
    'pc' => (int)($char['pc'] ?? 0),
    'appearance' => $char['appearance'] ?? '',
    'biography' => $char['biography'] ?? '',
    'notes' => $char['notes'] ?? '',
    'equipment' => $char['equipment'] ?? '',
    'character_image' => $char['character_image'] ?? null,
    'status' => $status,
    'camarilla_status' => $char['camarilla_status'] ?? 'Unknown',
    'traits' => $traits,
    'negativeTraits' => $negativeTraits,
    'abilities' => $abilities,
    'specializations' => [],
    'disciplines' => array_map(function($d) {
        return ['name' => $d['discipline_name'], 'level' => (int)$d['level']];
    }, $disciplines),
    'backgrounds' => $backgrounds,
    'backgroundDetails' => [],
    'morality' => $morality ?: null,
    'merits_flaws' => array_map(function($mf) {
        return [
            'name' => $mf['name'],
            'type' => $mf['type'],
            'category' => $mf['category'] ?? '',
            'cost' => (int)($mf['point_cost'] ?? $mf['point_value'] ?? 0),
            'description' => $mf['description'] ?? ''
        ];
    }, $merits_flaws),
    'coteries' => $coteries,
    'relationships' => $relationships,
    'rituals' => [],
    'custom_data' => $char['custom_data'] ? json_decode($char['custom_data'], true) : null,
    'created_at' => $char['created_at'] ?? '',
    'updated_at' => $char['updated_at'] ?? ''
];

// Generate filename
$safe_name = strtolower(str_replace([' ', "'"], ['_', ''], $character_name));
$filename = "npc__{$safe_name}__{$character_id}.json";
$output_path = __DIR__ . '/../../reference/Characters/Added to Database/' . $filename;

// Write JSON file
$json_output = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents($output_path, $json_output);

echo "Exported {$character_name} to: {$filename}\n";
echo "Path: {$output_path}\n";
echo "Done!\n";
