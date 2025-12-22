<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$character_id = 125; // Roxanne Murphy

$result = mysqli_query($conn, 
    "SELECT ability_name, ability_category, level, specialization 
     FROM character_abilities 
     WHERE character_id = $character_id 
     ORDER BY ability_category, ability_name"
);

echo "Roxanne Murphy (ID: 125) - Current Abilities:\n\n";

$total_points = 0;
$abilities_by_category = [];

while ($row = mysqli_fetch_assoc($result)) {
    $category = $row['ability_category'] ?? 'Unknown';
    $name = $row['ability_name'];
    $level = $row['level'] ?? 1;
    $spec = $row['specialization'] ? ' (' . $row['specialization'] . ')' : '';
    
    if (!isset($abilities_by_category[$category])) {
        $abilities_by_category[$category] = [];
    }
    
    $abilities_by_category[$category][] = [
        'name' => $name,
        'level' => $level,
        'spec' => $spec
    ];
    
    // Calculate XP cost (assuming standard VtM costs)
    // 0→1: 3, 1→2: 2, 2→3: 2, 3→4: 3, 4→5: 3
    $cost = 0;
    for ($i = 1; $i <= $level; $i++) {
        if ($i == 1) $cost += 3;
        elseif ($i <= 3) $cost += 2;
        else $cost += 3;
    }
    $total_points += $cost;
}

foreach ($abilities_by_category as $category => $abilities) {
    echo "$category:\n";
    foreach ($abilities as $ability) {
        echo "  - {$ability['name']} x{$ability['level']}{$ability['spec']}\n";
    }
    echo "\n";
}

echo "Total XP spent on abilities: ~$total_points\n";

