<?php
/**
 * Sync Archetypes from Grapevine XML to Nature_Demeanor Database
 * 
 * Compares archetypes in Grapevine Menus XML.gvm against the Nature_Demeanor table
 * and adds any missing archetypes with simple descriptions.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Simple descriptions for archetypes
$archetype_descriptions = [
    'Addict' => 'One who is dependent on substances or behaviors',
    'Alpha' => 'One who leads and dominates others',
    'Analyst' => 'One who examines and interprets data',
    'Arbitrator' => 'One who mediates disputes',
    'Architect' => 'One who builds structures, systems, or organizations',
    'Au Courant' => 'One who is current and fashionable',
    'Autocrat' => 'One who rules with absolute power',
    'Avant-Garde' => 'One who is innovative and experimental',
    'Barbarian' => 'One who is uncivilized and brutal',
    'Bon Vivant' => 'One who enjoys life and its pleasures',
    'Bravo' => 'One who uses intimidation and violence',
    'Bully' => 'One who intimidates and oppresses others',
    'Bureaucrat' => 'One who follows rules and procedures',
    'Capitalist' => 'One who values wealth and commerce',
    'Caregiver' => 'One who nurtures and protects others',
    'Caretaker' => 'One who maintains and preserves',
    'Celebrant' => 'One who celebrates and honors',
    'Chameleon' => 'One who adapts and blends in',
    'Child' => 'One who is innocent and playful',
    'Commando' => 'One who is a skilled military operative',
    'Competitor' => 'One who seeks to win and excel',
    'Confidant' => 'One who is trusted with secrets',
    'Conformist' => 'One who follows established rules and norms',
    'Conniver' => 'One who manipulates through schemes',
    'Crackerjack' => 'One who is exceptionally skilled',
    'Creep Show' => 'One who is disturbing and unsettling',
    'Critic' => 'One who evaluates and judges',
    'Crusader' => 'One who fights for a cause',
    'Cub' => 'One who is young and inexperienced',
    'Curmudgeon' => 'One who is grumpy and pessimistic',
    'Dabbler' => 'One who experiments with many things',
    'Daredevil' => 'One who takes dangerous risks',
    'Dark Pioneer' => 'One who explores dark paths',
    'Dark Poet' => 'One who expresses dark themes',
    'Defender' => 'One who protects and guards',
    'Deputy' => 'One who serves authority',
    'Deviant' => 'One who rejects social norms',
    'Director' => 'One who leads and coordinates',
    'Dreamer' => 'One who imagines possibilities',
    'Drunk Uncle' => 'One who is inappropriate and embarrassing',
    'Eccentric' => 'One who is unconventional and quirky',
    'Elitist' => 'One who believes in superiority',
    'Engine' => 'One who drives and powers systems',
    'Enigma' => 'One who is mysterious and puzzling',
    'Evangelist' => 'One who spreads beliefs and ideas',
    'Explorer' => 'One who seeks new experiences',
    'Eye of the Storm' => 'One who remains calm in chaos',
    'Fanatic' => 'One who is obsessively devoted to a cause',
    'Follower' => 'One who follows others',
    'Gallant' => 'One who is chivalrous and honorable',
    'Gambler' => 'One who takes risks and bets',
    'Guru' => 'One who teaches and guides spiritually',
    'Hedonist' => 'One who pursues pleasure',
    'Hunter' => 'One who tracks and pursues prey',
    'Idealist' => 'One who believes in ideals',
    'Innovator' => 'One who creates new solutions',
    'Interrogator' => 'One who extracts information',
    'Jester' => 'One who entertains and mocks',
    'Judge' => 'One who evaluates and passes judgment',
    'Leader' => 'One who guides and commands',
    'Listener' => 'One who pays attention and understands',
    'Lone Wolf' => 'One who operates independently',
    'Loner' => 'One who prefers solitude',
    'Loyalist' => 'One who is devoted and faithful',
    'Manipulator' => 'One who controls others',
    'Martyr' => 'One who sacrifices for others',
    'Masochist' => 'One who endures pain or suffering',
    'Mediator' => 'One who resolves conflicts',
    'Monster' => 'One who embraces their dark nature',
    'Nonpartisan' => 'One who remains neutral',
    'Omega' => 'One who is at the bottom of hierarchy',
    'Paladin' => 'One who is righteous and heroic',
    'Paragon' => 'One who is a perfect example',
    'Patrician' => 'One who is aristocratic and refined',
    'Peacock' => 'One who shows off and displays',
    'Pedagogue' => 'One who teaches and educates',
    'Penitent' => 'One who seeks redemption',
    'Perfectionist' => 'One who demands flawlessness',
    'Pragmatist' => 'One who is practical and realistic',
    'Predator' => 'One who hunts and preys',
    'Progressive' => 'One who advocates change',
    'Provider' => 'One who supplies and supports',
    'Rebel' => 'One who resists authority',
    'Recruiter' => 'One who finds and enlists others',
    'Reformer' => 'One who improves and changes',
    'Reluctant Garou' => 'One who is unwilling werewolf',
    'Reveler' => 'One who celebrates and parties',
    'Rogue' => 'One who operates outside the law',
    'Rumormonger' => 'One who spreads gossip',
    'Sadist' => 'One who enjoys inflicting pain',
    'Sage' => 'One who is wise and knowledgeable',
    'Scientist' => 'One who studies and experiments',
    'Scoundrel' => 'One who is dishonest and unscrupulous',
    'Sensualist' => 'One who pursues physical pleasure',
    'Shamanist' => 'One who connects with spirits',
    'Showoff' => 'One who displays and boasts',
    'Sociopath' => 'One who lacks empathy',
    'Soldier' => 'One who serves in military',
    'Sorority Sister' => 'One who belongs to sisterhood',
    'Stalker' => 'One who obsessively follows',
    'Stoic' => 'One who endures without emotion',
    'Supplicant' => 'One who begs and pleads',
    'Survivor' => 'One who endures and adapts',
    'Swindler' => 'One who deceives and cheats',
    'Teacher' => 'One who instructs and educates',
    'Thrill-Seeker' => 'One who pursues excitement and danger',
    'Torturer' => 'One who inflicts pain',
    'Traditionalist' => 'One who values established customs',
    'Trickster' => 'One who deceives and plays tricks',
    'True Believer' => 'One who is completely devoted',
    'Tyrant' => 'One who rules oppressively',
    'Visionary' => 'One who sees future possibilities',
];

// Parse XML file to extract archetypes
$xml_file = __DIR__ . '/../Grapevine/Grapevine Menus XML.gvm';
if (!file_exists($xml_file)) {
    die("Error: XML file not found at: $xml_file\n");
}

$xml_content = file_get_contents($xml_file);
if ($xml_content === false) {
    die("Error: Could not read XML file\n");
}

// Extract archetypes from the Archetypes menu
preg_match('/<menu name="Archetypes"[^>]*>(.*?)<\/menu>/s', $xml_content, $matches);
if (empty($matches[1])) {
    die("Error: Could not find Archetypes menu in XML file\n");
}

$menu_content = $matches[1];
preg_match_all('/<item name="([^"]+)"/', $menu_content, $item_matches);

$xml_archetypes = [];
foreach ($item_matches[1] as $archetype_name) {
    $archetype_name = trim($archetype_name);
    // Skip submenu items (Seelie, Unseelie)
    if ($archetype_name !== 'Seelie' && $archetype_name !== 'Unseelie') {
        $xml_archetypes[] = $archetype_name;
    }
}

echo "Found " . count($xml_archetypes) . " archetypes in XML file:\n";
foreach ($xml_archetypes as $archetype) {
    echo "  - $archetype\n";
}
echo "\n";

// Get existing archetypes from database
$existing_archetypes = db_fetch_all($conn, "SELECT name FROM Nature_Demeanor");
$existing_names = array_map(function($row) {
    return $row['name'];
}, $existing_archetypes);

echo "Found " . count($existing_names) . " archetypes in database:\n";
foreach ($existing_names as $name) {
    echo "  - $name\n";
}
echo "\n";

// Find missing archetypes
$missing_archetypes = array_diff($xml_archetypes, $existing_names);

if (empty($missing_archetypes)) {
    echo "✅ All archetypes from XML are already in the database.\n";
    exit(0);
}

echo "Found " . count($missing_archetypes) . " missing archetypes:\n";
foreach ($missing_archetypes as $archetype) {
    echo "  - $archetype\n";
}
echo "\n";

// Get max display_order to append new archetypes
$result = db_fetch_one($conn, "SELECT MAX(display_order) as max_order FROM Nature_Demeanor");
$max_order = $result ? (int)$result['max_order'] : 0;

// Insert missing archetypes
$inserted = 0;
$errors = [];

foreach ($missing_archetypes as $archetype_name) {
    $description = $archetype_descriptions[$archetype_name] ?? 'Character archetype';
    $display_order = $max_order + 1;
    $max_order++;
    
    $result = db_execute($conn,
        "INSERT INTO Nature_Demeanor (name, display_order, description) 
         VALUES (?, ?, ?)",
        'sis', [$archetype_name, $display_order, $description]
    );
    
    if ($result === false) {
        $error_msg = mysqli_error($conn);
        $errors[] = "Failed to insert $archetype_name: $error_msg";
    } else {
        $inserted++;
        echo "✅ Added: $archetype_name - $description\n";
    }
}

echo "\n";
if (!empty($errors)) {
    echo "⚠️ Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✅ Successfully added $inserted new archetypes to the database.\n";

mysqli_close($conn);
?>
