<?php
/**
 * Add Jennifer Torrance's traits, abilities, and disciplines from Jennifer_Info.md
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/connect.php';

// Find Jennifer's character ID
$result = mysqli_query($conn, "SELECT id, character_name FROM characters WHERE character_name LIKE '%Jennifer%Torrance%' OR character_name LIKE '%Jennifer Torrance%' LIMIT 1");
if (!$result || mysqli_num_rows($result) === 0) {
    die("ERROR: Jennifer Torrance not found in database.\n");
}

$char = mysqli_fetch_assoc($result);
$character_id = (int)$char['id'];
$character_name = $char['character_name'];

echo "Found character: {$character_name} (ID: {$character_id})\n\n";

// Data from Jennifer_Info.md
$traits = [
    ['category' => 'Physical', 'name' => 'Alert'],
    ['category' => 'Physical', 'name' => 'Quick'],
    ['category' => 'Social', 'name' => 'Calm'],
    ['category' => 'Social', 'name' => 'Persuasive'],
    ['category' => 'Mental', 'name' => 'Observant'],
    ['category' => 'Mental', 'name' => 'Methodical'],
];

$abilities = [
    ['name' => 'Steward', 'category' => 'Mental', 'level' => 3, 'specialization' => 'Liber des Ghouls'],
    ['name' => 'Empathy', 'category' => 'Mental', 'level' => 3, 'specialization' => null],
    ['name' => 'Awareness', 'category' => 'Mental', 'level' => 2, 'specialization' => null],
    ['name' => 'Subterfuge', 'category' => 'Social', 'level' => 3, 'specialization' => null],
    ['name' => 'Etiquette', 'category' => 'Social', 'level' => 2, 'specialization' => null],
    ['name' => 'Streetwise', 'category' => 'Physical', 'level' => 2, 'specialization' => null],
    ['name' => 'Alertness', 'category' => 'Physical', 'level' => 1, 'specialization' => null],
];

$disciplines = [
    ['name' => 'Celerity', 'level' => 1],
];

// Insert traits
echo "Inserting traits...\n";
$trait_inserted = 0;
$trait_stmt = mysqli_prepare($conn, "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, 'positive') ON DUPLICATE KEY UPDATE trait_name = trait_name");
if ($trait_stmt) {
    foreach ($traits as $trait) {
        mysqli_stmt_bind_param($trait_stmt, 'iss', $character_id, $trait['name'], $trait['category']);
        if (mysqli_stmt_execute($trait_stmt)) {
            $trait_inserted++;
            echo "  ✓ {$trait['category']}: {$trait['name']}\n";
        }
    }
    mysqli_stmt_close($trait_stmt);
} else {
    echo "ERROR preparing trait statement: " . mysqli_error($conn) . "\n";
}

// Insert abilities
echo "\nInserting abilities...\n";
$ability_inserted = 0;
// Check if ability_category column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'");
$has_category = mysqli_num_rows($check_col) > 0;

if ($has_category) {
    $ability_stmt = mysqli_prepare($conn, "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE ability_category = VALUES(ability_category), level = VALUES(level), specialization = VALUES(specialization)");
} else {
    $ability_stmt = mysqli_prepare($conn, "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE level = VALUES(level), specialization = VALUES(specialization)");
}

if ($ability_stmt) {
    foreach ($abilities as $ability) {
        if ($has_category) {
            mysqli_stmt_bind_param($ability_stmt, 'issis', $character_id, $ability['name'], $ability['category'], $ability['level'], $ability['specialization']);
        } else {
            mysqli_stmt_bind_param($ability_stmt, 'isis', $character_id, $ability['name'], $ability['level'], $ability['specialization']);
        }
        if (mysqli_stmt_execute($ability_stmt)) {
            $ability_inserted++;
            $spec = $ability['specialization'] ? " ({$ability['specialization']})" : '';
            echo "  ✓ {$ability['category']}: {$ability['name']} x{$ability['level']}{$spec}\n";
        }
    }
    mysqli_stmt_close($ability_stmt);
} else {
    echo "ERROR preparing ability statement: " . mysqli_error($conn) . "\n";
}

// Insert disciplines
echo "\nInserting disciplines...\n";
$discipline_inserted = 0;
$discipline_stmt = mysqli_prepare($conn, "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE level = VALUES(level)");
if ($discipline_stmt) {
    foreach ($disciplines as $disc) {
        mysqli_stmt_bind_param($discipline_stmt, 'isi', $character_id, $disc['name'], $disc['level']);
        if (mysqli_stmt_execute($discipline_stmt)) {
            $discipline_inserted++;
            echo "  ✓ {$disc['name']} x{$disc['level']}\n";
        }
    }
    mysqli_stmt_close($discipline_stmt);
} else {
    echo "ERROR preparing discipline statement: " . mysqli_error($conn) . "\n";
}

echo "\n=== Summary ===\n";
echo "Traits inserted: {$trait_inserted}/" . count($traits) . "\n";
echo "Abilities inserted: {$ability_inserted}/" . count($abilities) . "\n";
echo "Disciplines inserted: {$discipline_inserted}/" . count($disciplines) . "\n";
echo "\nDone!\n";

mysqli_close($conn);
?>

