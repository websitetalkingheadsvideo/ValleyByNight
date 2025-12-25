<?php
/**
 * Database Migration: Create influence_effects table and populate it
 * 
 * This script creates the influence_effects table and seeds it with all effects
 * for the 15 types of Influence from Laws of the Night (1st Edition).
 * 
 * Run via browser: https://vbn.talkingheads.video/database/create_influence_effects_table.php
 * Or via CLI: php database/create_influence_effects_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'influence_effects_lookup'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'influence_effects_lookup' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS influence_effects_lookup;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create influence_effects_lookup table
// Using influence_name as foreign key to match existing influence_types table structure
// Matching collation utf8mb4_0900_ai_ci to match existing table
$create_effects_sql = "
CREATE TABLE IF NOT EXISTS influence_effects_lookup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    influence_name VARCHAR(64) NOT NULL,
    level TINYINT NOT NULL,
    effects_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (influence_name) REFERENCES influence_types(influence_name) ON DELETE CASCADE,
    UNIQUE KEY uk_influence_level (influence_name, level),
    INDEX idx_influence_name (influence_name),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";

if (!mysqli_query($conn, $create_effects_sql)) {
    echo "<h2>❌ Error: Failed to create influence_effects table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_effects_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'influence_effects' created successfully!</h2>";

// Get all influence types from database
$types_query = "SELECT influence_name FROM influence_types WHERE is_active = 1 ORDER BY sort_order";
$types_result = mysqli_query($conn, $types_query);

if (!$types_result || mysqli_num_rows($types_result) == 0) {
    echo "<h2>❌ Error: No influence types found in database</h2>";
    echo "<p>The influence_types table exists but has no active records.</p>";
    mysqli_close($conn);
    exit;
}

// Influence effects data - matching the structure from influences.md
$influence_effects_data = [
    'Bureaucracy' => [
        1 => 'Trace utility bills; Fake a birth certificate or driver\'s license',
        2 => 'Learn about general economic trends',
        3 => 'Learn real motivations for many financial actions of others',
        4 => 'Disconnect a single small residence\'s utilities; Close a small road or park; Get public aid ($250); Fake a death certificate, passport, or green card; Close a public school for a single day; Shut down a minor business on a violation',
        5 => 'Initiate a phone tap; Fake land deeds; Initiate a department-wide investigation; Start, stop, or alter a city-wide program or policy; Shut down a big business on a violation; Rezone areas; Obliterate records of a person on a city or county level'
    ],
    'Church' => [
        1 => 'Identify most secular members of a given faith in the local area; Pass as a member of the clergy',
        2 => 'Perform ceremonies (marriage, burial, etc.); Identify higher church members; Track regular church members; Suspend lay members',
        3 => 'Open or close a single church; Find the average church-associated hunter; Dip into the collection plate ($250); Access private information and archives of a church',
        4 => 'Discredit or suspend high-level church members; Manipulate regional branches of the church',
        5 => 'Organize major protests; Access ancient church lore and knowledge'
    ],
    'Finance' => [
        1 => 'Learn about major transactions and financial events; Raise capital ($1,000)',
        2 => 'Learn about general economic trends',
        3 => 'Learn real motivations for many financial actions of others',
        4 => 'Trace an unsecured small account; Raise capital to purchase a small business (single, small store); Purchase a large business (a few small branches or a single large store or service); Manipulate local banking (delay deposits, some credit rating alterations); Ruin a small business',
        5 => 'Control an aspect of city-wide banking (shut off ATMs, arrange a bank \'holiday\'); Ruin a large business; Purchase a major company'
    ],
    'Health' => [
        1 => 'Access a person\'s health records; Fake vaccination records and the like; Use public functions of health centers at your leisure; Get a single Blood Trait of mortal blood',
        2 => 'Access some medical research records; Have minor lab work done; Get a copy of a coroner\'s report; Instigate minor quarantines',
        3 => 'Corrupt results of tests or inspections',
        4 => 'Alter medical records; Acquire a body; Completely rewrite medical records; Abuse grants for personal use ($250); Have minor medical research performed on a subject; Institute large-scale quarantines; Shut down businesses for \'health code violations\'',
        5 => 'Have special research projects performed'
    ],
    'High Society' => [
        1 => 'Learn what is trendy; Obtain hard-to-get tickets for shows; Learn about concerts, shows, or plays well before they are made public',
        2 => 'Track most celebrities and luminaries; Be a local voice in the entertainment field; \'Borrow\' idle cash from rich friends ($1,000)',
        3 => 'Crush promising careers; Hobnob well above your station; Minor celebrity status; Get a brief appearance on a talk show that\'s not about to be canceled',
        4 => 'Ruin a new club, gallery, festival, or other posh gathering',
        5 => '[Not detailed in source material]'
    ],
    'Industry' => [
        1 => 'Learn about industrial projects and movements',
        2 => 'Have minor projects performed',
        3 => 'Organize minor strikes; Appropriate machinery for a short time',
        4 => 'Close down a small plant; Revitalize a small plant',
        5 => 'Manipulate large local industry'
    ],
    'Legal' => [
        1 => 'Get free representation for minor cases',
        2 => 'Avoid bail for some charge',
        3 => 'Have minor charges dropped; Manipulate legal procedures (minor wills and contracts, court dates); Access public or court funds ($250)',
        4 => 'Get representation in most court cases; Issue subpoenas; Tie up court cases',
        5 => 'Close down all but the most serious investigations; Have deportation proceedings held against someone'
    ],
    'Media' => [
        1 => 'Learn about breaking stories early',
        2 => 'Submit small articles (within reason); Suppress (but not stop) small articles or reports; Get hold of investigative reporting information',
        3 => 'Initiate news investigations and reports; Get project funding and waste it ($250); Ground stories and projects',
        4 => '[Not detailed in source material]',
        5 => 'Broadcast fake stories (local only); Kill small local articles or reports completely'
    ],
    'Occult' => [
        1 => 'Contact and make use of common occult groups and their practices; Know some of the more visible occult figures',
        2 => 'Know and contact some of the more obscure occult figures',
        3 => 'Access resources for most rituals and rites; Know the general vicinity of certain supernatural entities and (possibly) contact them; Access vital or rare material components; Milk impressionable wannabes for bucks ($250)',
        4 => 'Research an Intermediate ritual from your sect',
        5 => 'Access minor magic items; Unearth an Advanced ritual from your sect'
    ],
    'Police' => [
        1 => 'Learn police procedures',
        2 => 'Have license plates checked; Avoid minor violations (first conviction); Get \'inside information\'',
        3 => 'Get copies of an investigation report; Have police hassle, detain, or harass someone',
        4 => 'Find bureau secrets; Access confiscated weapons or contraband; Have some serious charges dropped; Start an investigation; Get money, either from the evidence room or as an appropriation ($1,000)',
        5 => 'Institute major investigations; Arrange setups; Instigate bureau investigations; Have officers fired'
    ],
    'Political' => [
        1 => 'Minor lobbying; Identify real platforms of politicians and parties',
        2 => 'Be in the know; Meet small-time politicians; Garner inside information on processes, laws, and the like; Use a slush fund or fund-raiser ($1,000)',
        3 => 'Sway or alter political projects (local parks, renovations, small construction)',
        4 => 'Enact minor legislation',
        5 => 'Get your candidate in a minor office; Enact encompassing legislature'
    ],
    'Street' => [
        1 => 'Open an ear for the word on the street',
        2 => 'Identify most gangs and know their turfs and habits; Live mostly without fear on the underside of society; Keep a contact or two in most aspects of street life; Access small-time contraband',
        3 => 'Get insight into other areas of Influence; Arrange some services from street people or gangs; Get pistols or uncommon melee weapons',
        4 => 'Mobilize groups of homeless; Panhandle or hold a \'collection\' ($250); Get hold of a shotgun, rifle, or SMG; Have a word in almost all aspects of gang operations',
        5 => 'Control a single medium-sized gang; Arrange impressive protests by street people'
    ],
    'Transportation' => [
        1 => 'Know what goes where, when, and why',
        2 => 'Travel locally quickly and freely; Track an unwary target if he uses public transportation; Arrange passage safe (or at least concealed) from mundane threats (robbery, terrorism, sunlight, etc.)',
        3 => 'Seriously hamper an individual\'s ability to travel; Avoid most supernatural dangers when traveling (such as Lupines)',
        4 => 'Shut down one form of transportation (bus lines, ships, planes, trains, etc.) temporarily; Route money your way ($500)',
        5 => 'Reroute major modes of travel; Smuggle with impunity'
    ],
    'Underworld' => [
        1 => 'Locate minor contraband (knives, small-time drugs, petty gambling, scalped tickets)',
        2 => 'Obtain pistols, serious drugs, stolen cars; Hire muscle to rough someone up; Fence stolen loot',
        3 => 'Prove that crime pays (and score $1,000); Obtain a rifle, shotgun, or SMG; Arrange a minor \'hit\'; Meet someone in \'the Family\'',
        4 => 'Make white-collar crime connections',
        5 => 'Arrange gangland assassinations; Hire a demolition man or firebug; Supply local drug needs'
    ],
    'University' => [
        1 => 'Know layout and policy of local schools; Have access to low-level university resources; Get records up to the high school level',
        2 => 'Know a contact or two with useful knowledge or Abilities; Have minor access to facilities; Fake high school records; Obtain college records',
        3 => 'Call in faculty favors; Cancel a class; Fix grades',
        4 => 'Discredit a student; Organize student protests and rallies; Discredit faculty members',
        5 => 'Falsify an undergraduate degree'
    ]
];

// Insert effects for each influence type
$insert_effect_sql = "INSERT INTO influence_effects_lookup (influence_name, level, effects_text) VALUES (?, ?, ?)";
$effect_stmt = mysqli_prepare($conn, $insert_effect_sql);

if (!$effect_stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_effects = 0;
$errors = [];

// Get list of existing influence names from database
$existing_influences = [];
while ($row = mysqli_fetch_assoc($types_result)) {
    $existing_influences[] = $row['influence_name'];
}

foreach ($influence_effects_data as $influence_name => $effects) {
    if (!in_array($influence_name, $existing_influences)) {
        $errors[] = "Influence type '{$influence_name}' not found in database";
        continue;
    }
    
    // Insert effects for each level
    foreach ($effects as $level => $effects_text) {
        mysqli_stmt_bind_param($effect_stmt, 'sis', $influence_name, $level, $effects_text);
        
        if (!mysqli_stmt_execute($effect_stmt)) {
            $errors[] = "Failed to insert effect for {$influence_name} level {$level}: " . mysqli_stmt_error($effect_stmt);
        } else {
            $inserted_effects++;
        }
    }
}

mysqli_stmt_close($effect_stmt);

if (count($errors) > 0) {
    echo "<h3>⚠️ Warnings:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<p>✅ Successfully inserted {$inserted_effects} effects into the table.</p>";

// Test the lookup
echo "<h3>Test Lookup: Bureaucracy Level 3</h3>";
$test_query = "
SELECT it.influence_name, ie.level, ie.effects_text
FROM influence_types it
JOIN influence_effects_lookup ie ON it.influence_name = ie.influence_name
WHERE it.influence_name = 'Bureaucracy' AND ie.level = 3
";
$test_result = mysqli_query($conn, $test_query);

if ($test_result && mysqli_num_rows($test_result) > 0) {
    $row = mysqli_fetch_assoc($test_result);
    echo "<p><strong>{$row['influence_name']} Level {$row['level']}:</strong> {$row['effects_text']}</p>";
    mysqli_free_result($test_result);
} else {
    echo "<p>❌ Test lookup failed</p>";
}

// Show count
$count_sql = "SELECT COUNT(*) as effect_count FROM influence_effects_lookup";
$count_result = mysqli_query($conn, $count_sql);
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    echo "<p>Total Influence Effects: {$row['effect_count']}</p>";
    mysqli_free_result($count_result);
}

mysqli_close($conn);
?>

