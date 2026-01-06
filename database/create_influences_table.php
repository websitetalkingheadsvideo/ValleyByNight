<?php
/**
 * Database Migration: Create influences lookup tables
 * 
 * This script creates the influence_types and influence_effects tables
 * and seeds them with all 15 types of Influence and their effects by level
 * from Laws of the Night (1st Edition).
 * 
 * Run via browser: database/create_influences_table.php
 * Or via CLI: php database/create_influences_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if tables already exist
$check_table = "SHOW TABLES LIKE 'influence_types'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'influence_types' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS influence_effects;\nDROP TABLE IF EXISTS influence_types;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create influence_types table
$create_types_sql = "
CREATE TABLE IF NOT EXISTS influence_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    contacts_allies TEXT,
    reference_pages VARCHAR(255),
    display_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name (name),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_types_sql)) {
    echo "<h2>❌ Error: Failed to create influence_types table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_types_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'influence_types' created successfully!</h2>";

// Create influence_effects table
$create_effects_sql = "
CREATE TABLE IF NOT EXISTS influence_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    influence_type_id INT NOT NULL,
    level TINYINT NOT NULL CHECK (level BETWEEN 1 AND 5),
    effects_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (influence_type_id) REFERENCES influence_types(id) ON DELETE CASCADE,
    UNIQUE KEY uk_influence_level (influence_type_id, level),
    INDEX idx_influence_type (influence_type_id),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_effects_sql)) {
    echo "<h2>❌ Error: Failed to create influence_effects table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_effects_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'influence_effects' created successfully!</h2>";

// Influence types data from influences.md
$influence_types_data = [
    [
        'name' => 'Bureaucracy',
        'description' => 'You can manage various government agencies and bureaus. By dealing with social programs and public servants, you can spin red tape, bypass rules and regulations, or twist bureaucratic regimentation to your advantage. Bureaucracy is useful in operating or shutting down businesses, faking or acquiring permits and identification papers, and manipulating public utilities and facilities.',
        'contacts_allies' => 'Government clerks at city and county level, utility workers, road crews, surveyors, and other civil servants.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 584-590',
        'display_order' => 1,
        'effects' => [
            1 => 'Trace utility bills; Fake a birth certificate or driver\'s license',
            2 => 'Learn about general economic trends',
            3 => 'Learn real motivations for many financial actions of others',
            4 => 'Disconnect a single small residence\'s utilities; Close a small road or park; Get public aid ($250); Fake a death certificate, passport, or green card; Close a public school for a single day; Shut down a minor business on a violation',
            5 => 'Initiate a phone tap; Fake land deeds; Initiate a department-wide investigation; Start, stop, or alter a city-wide program or policy; Shut down a big business on a violation; Rezone areas; Obliterate records of a person on a city or county level'
        ]
    ],
    [
        'name' => 'Church',
        'description' => 'Though the modern church has arguably less control over temporal society than in the Middle Ages, its policies still exert considerable influence over politics and communities. Knowing the appropriate people allows insight into many mainstream religions (Christianity, Judaism, Islam, Hinduism, Shinto, Buddhism). Fringe or alternative groups (such as Scientology) are considered Occult.',
        'contacts_allies' => 'Ministers, priests, bishops, Church-sponsored witch-hunters, holy orders, various attendees and assistants.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 591-598',
        'display_order' => 2,
        'effects' => [
            1 => 'Identify most secular members of a given faith in the local area; Pass as a member of the clergy',
            2 => 'Perform ceremonies (marriage, burial, etc.); Identify higher church members; Track regular church members; Suspend lay members',
            3 => 'Open or close a single church; Find the average church-associated hunter; Dip into the collection plate ($250); Access private information and archives of a church',
            4 => 'Discredit or suspend high-level church members; Manipulate regional branches of the church',
            5 => 'Organize major protests; Access ancient church lore and knowledge'
        ]
    ],
    [
        'name' => 'Finance',
        'description' => 'Manipulating markets, stock reports, and investments is a hobby of many Cainites, especially those who use their knowledge to keep hidden wealth. Though your actual available money is a function of Resources, you can use Finance Influence to start or smother businesses, crush or support banking institutions, and alter credit records.',
        'contacts_allies' => 'CEOs, bankers, stockbrokers, bank tellers, yes-men, financiers, loan agents.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 600-612',
        'display_order' => 3,
        'effects' => [
            1 => 'Learn about major transactions and financial events; Raise capital ($1,000)',
            2 => 'Learn about general economic trends',
            3 => 'Learn real motivations for many financial actions of others',
            4 => 'Trace an unsecured small account; Raise capital to purchase a small business (single, small store); Purchase a large business (a few small branches or a single large store or service); Manipulate local banking (delay deposits, some credit rating alterations); Ruin a small business',
            5 => 'Control an aspect of city-wide banking (shut off ATMs, arrange a bank \'holiday\'); Ruin a large business; Purchase a major company'
        ]
    ],
    [
        'name' => 'Health',
        'description' => 'Some vampires rely on connections in the medical community to acquire blood. Necromancers and practitioners of arcane arts may also require body parts or medical data. Furthermore, maintaining the Masquerade often calls for alteration of medical records or faking diseases. Some Cainites specialize in the study of blood-borne ailments.',
        'contacts_allies' => 'Coroners, doctors, lab workers, therapists, pharmacists, specialists.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 616-633',
        'display_order' => 4,
        'effects' => [
            1 => 'Access a person\'s health records; Fake vaccination records and the like; Use public functions of health centers at your leisure; Get a single Blood Trait of mortal blood',
            2 => 'Access some medical research records; Have minor lab work done; Get a copy of a coroner\'s report; Instigate minor quarantines',
            3 => 'Corrupt results of tests or inspections',
            4 => 'Alter medical records; Acquire a body; Completely rewrite medical records; Abuse grants for personal use ($250); Have minor medical research performed on a subject; Institute large-scale quarantines; Shut down businesses for \'health code violations\'',
            5 => 'Have special research projects performed'
        ]
    ],
    [
        'name' => 'High Society',
        'description' => 'The glitterati at the top of society move in circles of wealth and elegance. Many Kindred find such positions alluring and indulge in the passions of the famous and wealthy. Access to famous actors, celebrities, and the idle rich grants sway over fashion trends. Combined with Fame, a modicum of High Society Influence turns a vampire into a debonair darling of the most exclusive social circles.',
        'contacts_allies' => 'Dilettantes, artists of almost any stripe, old money families, models, rock stars, sports figures, jetsetters.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 635-644',
        'display_order' => 5,
        'effects' => [
            1 => 'Learn what is trendy; Obtain hard-to-get tickets for shows; Learn about concerts, shows, or plays well before they are made public',
            2 => 'Track most celebrities and luminaries; Be a local voice in the entertainment field; \'Borrow\' idle cash from rich friends ($1,000)',
            3 => 'Crush promising careers; Hobnob well above your station; Minor celebrity status; Get a brief appearance on a talk show that\'s not about to be canceled',
            4 => 'Ruin a new club, gallery, festival, or other posh gathering',
            5 => '[Not detailed in source material]'
        ]
    ],
    [
        'name' => 'Industry',
        'description' => 'The grinding wheels of labor fuel the economies and markets of the world. Machines, factories, and blue-collar workers line up in endless drudgery, churning out the staples of everyday living. Control over Industry Influence sways the formation of unions, movements of work projects, locations for factories, and the product of manufacturing concerns.',
        'contacts_allies' => 'Union workers, foremen, engineers, construction workers, manual laborers, all manner of blue-collar workers.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 645-654',
        'display_order' => 6,
        'effects' => [
            1 => 'Learn about industrial projects and movements',
            2 => 'Have minor projects performed',
            3 => 'Organize minor strikes; Appropriate machinery for a short time',
            4 => 'Close down a small plant; Revitalize a small plant',
            5 => 'Manipulate large local industry'
        ]
    ],
    [
        'name' => 'Legal',
        'description' => 'Since many operations that Cainites undertake are at least marginally illegal, sway over judges and lawyers is indispensable. Those Kindred who dabble in law often pull strings in the courts to ensure questionable practices go unnoticed and unpunished. Legal Influence is also excellent for harassing an enemy\'s assets.',
        'contacts_allies' => 'Law schools and firms, lawyers, judges, DAs, clerks, public defenders.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 656-661',
        'display_order' => 7,
        'effects' => [
            1 => 'Get free representation for minor cases',
            2 => 'Avoid bail for some charge',
            3 => 'Have minor charges dropped; Manipulate legal procedures (minor wills and contracts, court dates); Access public or court funds ($250)',
            4 => 'Get representation in most court cases; Issue subpoenas; Tie up court cases',
            5 => 'Close down all but the most serious investigations; Have deportation proceedings held against someone'
        ]
    ],
    [
        'name' => 'Media',
        'description' => 'Directing media attention away from vampire activities is key to the Masquerade. Putting specific emphasis on certain events can place an enemy in an uncomfortable spotlight or discredit a rival. With Media, you can crush or alter news stories, control operations of news stations and reporters, and sway public opinion.',
        'contacts_allies' => 'DJs, editors of all varieties, reporters, cameramen, photographers, broadcasters. At Storyteller discretion, Media Influence may also allow access to technical areas of television, radio, or movies.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 663-673',
        'display_order' => 8,
        'effects' => [
            1 => 'Learn about breaking stories early',
            2 => 'Submit small articles (within reason); Suppress (but not stop) small articles or reports; Get hold of investigative reporting information',
            3 => 'Initiate news investigations and reports; Get project funding and waste it ($250); Ground stories and projects',
            4 => '[Not detailed in source material]',
            5 => 'Broadcast fake stories (local only); Kill small local articles or reports completely'
        ]
    ],
    [
        'name' => 'Occult',
        'description' => 'The hidden world of the supernatural teems with secrets, conspiracies, and unusual factions. Obviously, a vampire is aware that strange things exist, but hard knowledge is a function of Abilities. By using Occult Influence, you can dig up information to improve knowledge, get inside the occult community, and find rare components for magical rituals. Even parts of the elusive Book of Nod are available to those with the right connections.',
        'contacts_allies' => 'Cult leaders, alternative religious groups, charlatans, occultists, New Agers, and a few more dangerous elements.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 675-682',
        'display_order' => 9,
        'effects' => [
            1 => 'Contact and make use of common occult groups and their practices; Know some of the more visible occult figures',
            2 => 'Know and contact some of the more obscure occult figures',
            3 => 'Access resources for most rituals and rites; Know the general vicinity of certain supernatural entities and (possibly) contact them; Access vital or rare material components; Milk impressionable wannabes for bucks ($250)',
            4 => 'Research an Intermediate ritual from your sect',
            5 => 'Access minor magic items; Unearth an Advanced ritual from your sect'
        ]
    ],
    [
        'name' => 'Police',
        'description' => '\'To protect and serve\' is the motto of the police, but these days, Kindred and kine alike may wonder who is being protected and served. Police Influence can be very handy to assist with the Masquerade, protect holdings, or raid the assets of another.',
        'contacts_allies' => 'Police of all ranks, detectives, clerical staff, dispatchers, prison guards, special divisions (SWAT, homicide), local highway patrol.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 684-712',
        'display_order' => 10,
        'effects' => [
            1 => 'Learn police procedures',
            2 => 'Have license plates checked; Avoid minor violations (first conviction); Get \'inside information\'',
            3 => 'Get copies of an investigation report; Have police hassle, detain, or harass someone',
            4 => 'Find bureau secrets; Access confiscated weapons or contraband; Have some serious charges dropped; Start an investigation; Get money, either from the evidence room or as an appropriation ($1,000)',
            5 => 'Institute major investigations; Arrange setups; Instigate bureau investigations; Have officers fired'
        ]
    ],
    [
        'name' => 'Political',
        'description' => 'Deal-making is second nature to most vampires, so they get along very well with politicians. Altering party platforms, controlling local elections, changing appointed offices, and calling in favors all fall under Political Influence. Well-timed blackmail, bribery, spin doctoring, or any sundry tricks are stock in trade.',
        'contacts_allies' => 'Pollsters, lobbyists, activists, party members, spin doctors, politicians from rural zoning committees to mayors of major cities or Congressional representatives.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 691-699',
        'display_order' => 11,
        'effects' => [
            1 => 'Minor lobbying; Identify real platforms of politicians and parties',
            2 => 'Be in the know; Meet small-time politicians; Garner inside information on processes, laws, and the like; Use a slush fund or fund-raiser ($1,000)',
            3 => 'Sway or alter political projects (local parks, renovations, small construction)',
            4 => 'Enact minor legislation',
            5 => 'Get your candidate in a minor office; Enact encompassing legislature'
        ]
    ],
    [
        'name' => 'Street',
        'description' => 'Ignored and often spat on by their \'betters,\' those in the dark alleys and slums have created their own culture. When calling on Street Influence, you use connections on the underside of the city to find the homeless, gang members, street buskers, petty criminals, prostitutes, residents of slums or barrios, and fringe elements of \'deviant\' cultures.',
        'contacts_allies' => 'Homeless, gang members of all sorts, street buskers, petty criminals, prostitutes, residents of slums or barrios, fringe elements.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 701-708',
        'display_order' => 12,
        'effects' => [
            1 => 'Open an ear for the word on the street',
            2 => 'Identify most gangs and know their turfs and habits; Live mostly without fear on the underside of society; Keep a contact or two in most aspects of street life; Access small-time contraband',
            3 => 'Get insight into other areas of Influence; Arrange some services from street people or gangs; Get pistols or uncommon melee weapons',
            4 => 'Mobilize groups of homeless; Panhandle or hold a \'collection\' ($250); Get hold of a shotgun, rifle, or SMG; Have a word in almost all aspects of gang operations',
            5 => 'Control a single medium-sized gang; Arrange impressive protests by street people'
        ]
    ],
    [
        'name' => 'Transportation',
        'description' => 'Most Cainites make their havens in defensible parts of cities. Traveling across wilderness is difficult, with problems of daylight and marauding Lupines. Without this Influence, the vampiric world shrinks into islands of \'civilization\' with dangerous wastelands in between. Getting access to special supplies and services can also take Transportation Influence.',
        'contacts_allies' => 'Truckers, harbors, railroads, airports, taxis, border guards, pilots, untold hundreds, as well as mundane aspects like shipping and travel arrangements.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 714-721',
        'display_order' => 13,
        'effects' => [
            1 => 'Know what goes where, when, and why',
            2 => 'Travel locally quickly and freely; Track an unwary target if he uses public transportation; Arrange passage safe (or at least concealed) from mundane threats (robbery, terrorism, sunlight, etc.)',
            3 => 'Seriously hamper an individual\'s ability to travel; Avoid most supernatural dangers when traveling (such as Lupines)',
            4 => 'Shut down one form of transportation (bus lines, ships, planes, trains, etc.) temporarily; Route money your way ($500)',
            5 => 'Reroute major modes of travel; Smuggle with impunity'
        ]
    ],
    [
        'name' => 'Underworld',
        'description' => 'The world of crime offers lucrative possibilities to strong-willed or subtle leaders. Guns, money, drugs, and vice - such delicious pastimes can be led by anyone talented or simply vicious enough to take them. Underworld Influence lets you call on favors for all manner of illegal dealings.',
        'contacts_allies' => 'Mafia, La Cosa Nostra, drug dealers, bookies, Yakuza, tongs, hitmen, fences, criminal gangs.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 723-730',
        'display_order' => 14,
        'effects' => [
            1 => 'Locate minor contraband (knives, small-time drugs, petty gambling, scalped tickets)',
            2 => 'Obtain pistols, serious drugs, stolen cars; Hire muscle to rough someone up; Fence stolen loot',
            3 => 'Prove that crime pays (and score $1,000); Obtain a rifle, shotgun, or SMG; Arrange a minor \'hit\'; Meet someone in \'the Family\'',
            4 => 'Make white-collar crime connections',
            5 => 'Arrange gangland assassinations; Hire a demolition man or firebug; Supply local drug needs'
        ]
    ],
    [
        'name' => 'University',
        'description' => 'Institutions of learning and research are the purview of University Influence. Access to halls of learning can help with any number of resources, from ancient languages to research assistance to many impressionable young minds.',
        'contacts_allies' => 'School boards, students from kindergarten through college, graduate students, professors, teachers, deans, Greek orders, variety of staff.',
        'reference_pages' => 'Laws of the Night (1st Edition), pages 732-739',
        'display_order' => 15,
        'effects' => [
            1 => 'Know layout and policy of local schools; Have access to low-level university resources; Get records up to the high school level',
            2 => 'Know a contact or two with useful knowledge or Abilities; Have minor access to facilities; Fake high school records; Obtain college records',
            3 => 'Call in faculty favors; Cancel a class; Fix grades',
            4 => 'Discredit a student; Organize student protests and rallies; Discredit faculty members',
            5 => 'Falsify an undergraduate degree'
        ]
    ]
];

// Insert influence types and their effects
$insert_type_sql = "INSERT INTO influence_types (name, description, contacts_allies, reference_pages, display_order) VALUES (?, ?, ?, ?, ?)";
$insert_effect_sql = "INSERT INTO influence_effects (influence_type_id, level, effects_text) VALUES (?, ?, ?)";

$type_stmt = mysqli_prepare($conn, $insert_type_sql);
$effect_stmt = mysqli_prepare($conn, $insert_effect_sql);

if (!$type_stmt || !$effect_stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statements</h2>";
    echo "<p>Type Error: " . ($type_stmt ? 'OK' : mysqli_error($conn)) . "</p>";
    echo "<p>Effect Error: " . ($effect_stmt ? 'OK' : mysqli_error($conn)) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_types = 0;
$inserted_effects = 0;
$errors = [];

foreach ($influence_types_data as $influence) {
    // Insert influence type
    mysqli_stmt_bind_param($type_stmt, 'ssssi', 
        $influence['name'],
        $influence['description'],
        $influence['contacts_allies'],
        $influence['reference_pages'],
        $influence['display_order']
    );
    
    if (!mysqli_stmt_execute($type_stmt)) {
        $errors[] = "Failed to insert influence type {$influence['name']}: " . mysqli_stmt_error($type_stmt);
        continue;
    }
    
    $influence_type_id = mysqli_insert_id($conn);
    $inserted_types++;
    
    // Insert effects for each level
    foreach ($influence['effects'] as $level => $effects_text) {
        mysqli_stmt_bind_param($effect_stmt, 'iis', $influence_type_id, $level, $effects_text);
        
        if (!mysqli_stmt_execute($effect_stmt)) {
            $errors[] = "Failed to insert effect for {$influence['name']} level {$level}: " . mysqli_stmt_error($effect_stmt);
        } else {
            $inserted_effects++;
        }
    }
}

mysqli_stmt_close($type_stmt);
mysqli_stmt_close($effect_stmt);

if (count($errors) > 0) {
    echo "<h3>⚠️ Warnings:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<p>✅ Successfully inserted {$inserted_types} influence types and {$inserted_effects} effects into the tables.</p>";

// Show sample lookup query
echo "<h3>Sample Lookup Query:</h3>";
echo "<pre>";
echo "SELECT it.name, ie.level, ie.effects_text\n";
echo "FROM influence_types it\n";
echo "JOIN influence_effects ie ON it.id = ie.influence_type_id\n";
echo "WHERE it.name = 'Bureaucracy' AND ie.level = 3;\n";
echo "</pre>";

// Test the lookup
echo "<h3>Test Lookup: Bureaucracy Level 3</h3>";
$test_query = "
SELECT it.name, ie.level, ie.effects_text
FROM influence_types it
JOIN influence_effects ie ON it.id = ie.influence_type_id
WHERE it.name = 'Bureaucracy' AND ie.level = 3
";
$test_result = mysqli_query($conn, $test_query);

if ($test_result && mysqli_num_rows($test_result) > 0) {
    $row = mysqli_fetch_assoc($test_result);
    echo "<p><strong>{$row['name']} Level {$row['level']}:</strong> {$row['effects_text']}</p>";
    mysqli_free_result($test_result);
} else {
    echo "<p>❌ Test lookup failed</p>";
}

// Show table structure
echo "<h3>Table Structure: influence_types</h3>";
$describe_sql = "DESCRIBE influence_types";
$describe_result = mysqli_query($conn, $describe_sql);

if ($describe_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($describe_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($describe_result);
}

// Show count
echo "<h3>Data Summary:</h3>";
$count_sql = "SELECT COUNT(*) as type_count FROM influence_types";
$count_result = mysqli_query($conn, $count_sql);
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    echo "<p>Influence Types: {$row['type_count']}</p>";
    mysqli_free_result($count_result);
}

$count_sql = "SELECT COUNT(*) as effect_count FROM influence_effects";
$count_result = mysqli_query($conn, $count_sql);
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    echo "<p>Influence Effects: {$row['effect_count']}</p>";
    mysqli_free_result($count_result);
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS influence_effects;\nDROP TABLE IF EXISTS influence_types;</pre>";

mysqli_close($conn);
?>

