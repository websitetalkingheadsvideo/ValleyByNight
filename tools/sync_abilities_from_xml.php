<?php
/**
 * Sync Abilities from Grapevine XML to Database
 * 
 * Compares abilities in Grapevine Menus XML.gvm against the database
 * and adds any missing abilities with simple descriptions.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Define ability categories (based on standard VtM rules)
$ability_categories = [
    // Physical Abilities
    'Archery' => 'Physical',
    'Athletics' => 'Physical',
    'Blindfighting' => 'Physical',
    'Brawl' => 'Physical',
    'Demolitions' => 'Physical',
    'Dodge' => 'Physical',
    'Firearms' => 'Physical',
    'Melee' => 'Physical',
    'Security' => 'Physical',
    'Stealth' => 'Physical',
    'Survival' => 'Physical',
    'Throwing' => 'Physical',
    
    // Social Abilities
    'Animal Ken' => 'Social',
    'Disguise' => 'Social',
    'Empathy' => 'Social',
    'Etiquette' => 'Social',
    'Expression' => 'Social',
    'Intimidation' => 'Social',
    'Leadership' => 'Social',
    'Performance' => 'Social',
    'Streetwise' => 'Social',
    'Subterfuge' => 'Social',
    'Torture' => 'Social',
    
    // Mental Abilities
    'Academics' => 'Mental',
    'Bureaucracy' => 'Mental',
    'Computer' => 'Mental',
    'Enigmas' => 'Mental',
    'Finance' => 'Mental',
    'Investigation' => 'Mental',
    'Law' => 'Mental',
    'Linguistics' => 'Mental',
    'Medicine' => 'Mental',
    'Meditation' => 'Mental',
    'Occult' => 'Mental',
    'Politics' => 'Mental',
    'Repair' => 'Mental',
    'Science' => 'Mental',
    'Scrounge' => 'Mental',
    
    // Optional Abilities
    'Awareness' => 'Optional',
    'Crafts' => 'Optional',
    'Drive' => 'Optional',
    'Flight' => 'Optional',
];

// Simple descriptions for abilities
$ability_descriptions = [
    'Academics' => 'Scholarly knowledge and education',
    'Animal Ken' => 'Understanding and working with animals',
    'Archery' => 'Using bows and crossbows',
    'Athletics' => 'Physical fitness and sports',
    'Awareness' => 'Supernatural awareness and perception',
    'Blindfighting' => 'Combat without sight',
    'Brawl' => 'Unarmed combat',
    'Bureaucracy' => 'Navigating official systems and paperwork',
    'Computer' => 'Technology and programming',
    'Crafts' => 'Handicrafts and making things',
    'Demolitions' => 'Explosives and demolition',
    'Disguise' => 'Changing appearance and impersonation',
    'Dodge' => 'Evading attacks',
    'Drive' => 'Vehicle operation',
    'Empathy' => 'Reading emotions and understanding others',
    'Enigmas' => 'Solving puzzles and mysteries',
    'Etiquette' => 'Social graces and proper behavior',
    'Expression' => 'Artistic expression and communication',
    'Finance' => 'Money, economics, and financial systems',
    'Firearms' => 'Ranged weapons and guns',
    'Flight' => 'Flying and aerial movement',
    'Intimidation' => 'Frightening and coercing others',
    'Investigation' => 'Research, deduction, and detective work',
    'Law' => 'Legal knowledge and systems',
    'Leadership' => 'Commanding and inspiring others',
    'Linguistics' => 'Languages and communication',
    'Medicine' => 'Medical knowledge and treatment',
    'Meditation' => 'Mental focus and inner peace',
    'Melee' => 'Close combat weapons',
    'Occult' => 'Supernatural and mystical knowledge',
    'Performance' => 'Acting, singing, and entertainment',
    'Politics' => 'Political systems and maneuvering',
    'Repair' => 'Fixing and maintaining equipment',
    'Science' => 'Scientific knowledge and research',
    'Scrounge' => 'Finding useful items and resources',
    'Security' => 'Locks, alarms, and security systems',
    'Stealth' => 'Hiding and moving unseen',
    'Streetwise' => 'Urban knowledge and street smarts',
    'Subterfuge' => 'Deception, lies, and manipulation',
    'Survival' => 'Wilderness survival and outdoor skills',
    'Throwing' => 'Throwing weapons and objects',
    'Torture' => 'Extracting information through pain',
];

// Parse XML file to extract abilities
$xml_file = __DIR__ . '/../Grapevine/Grapevine Menus XML.gvm';
if (!file_exists($xml_file)) {
    die("Error: XML file not found at: $xml_file\n");
}

$xml_content = file_get_contents($xml_file);
if ($xml_content === false) {
    die("Error: Could not read XML file\n");
}

// Extract abilities from the Abilities menu
preg_match('/<menu name="Abilities"[^>]*>(.*?)<\/menu>/s', $xml_content, $matches);
if (empty($matches[1])) {
    die("Error: Could not find Abilities menu in XML file\n");
}

$menu_content = $matches[1];
preg_match_all('/<item name="([^"]+)"/', $menu_content, $item_matches);

$xml_abilities = [];
foreach ($item_matches[1] as $ability_name) {
    $xml_abilities[] = trim($ability_name);
}

echo "Found " . count($xml_abilities) . " abilities in XML file:\n";
foreach ($xml_abilities as $ability) {
    echo "  - $ability\n";
}
echo "\n";

// Get existing abilities from database
$existing_abilities = db_fetch_all($conn, "SELECT name FROM abilities");
$existing_names = array_map(function($row) {
    return $row['name'];
}, $existing_abilities);

echo "Found " . count($existing_names) . " abilities in database:\n";
foreach ($existing_names as $name) {
    echo "  - $name\n";
}
echo "\n";

// Find missing abilities
$missing_abilities = array_diff($xml_abilities, $existing_names);

if (empty($missing_abilities)) {
    echo "✅ All abilities from XML are already in the database.\n";
    exit(0);
}

echo "Found " . count($missing_abilities) . " missing abilities:\n";
foreach ($missing_abilities as $ability) {
    echo "  - $ability\n";
}
echo "\n";

// Get max display_order for each category to append new abilities
$max_orders = [];
foreach (['Physical', 'Social', 'Mental', 'Optional'] as $category) {
    $result = db_fetch_one($conn, 
        "SELECT MAX(display_order) as max_order FROM abilities WHERE category = ?",
        's', [$category]
    );
    $max_orders[$category] = $result ? (int)$result['max_order'] : 0;
}

// Insert missing abilities
$inserted = 0;
$errors = [];

foreach ($missing_abilities as $ability_name) {
    $category = $ability_categories[$ability_name] ?? 'Optional';
    $description = $ability_descriptions[$ability_name] ?? 'General ability';
    $display_order = $max_orders[$category] + 1;
    $max_orders[$category]++;
    
    $result = db_execute($conn,
        "INSERT INTO abilities (name, category, display_order, description, min_level, max_level) 
         VALUES (?, ?, ?, ?, 0, 5)",
        'ssis', [$ability_name, $category, $display_order, $description]
    );
    
    if ($result === false) {
        $error_msg = mysqli_error($conn);
        $errors[] = "Failed to insert $ability_name: $error_msg";
    } else {
        $inserted++;
        echo "✅ Added: $ability_name ($category) - $description\n";
    }
}

echo "\n";
if (!empty($errors)) {
    echo "⚠️ Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✅ Successfully added $inserted new abilities to the database.\n";

mysqli_close($conn);
?>
