<?php
/**
 * Database Migration: Create Derangements table
 * 
 * This script creates the Derangements table and seeds it with all derangements
 * from the Grapevine Menus XML.gvm file.
 * 
 * Run via browser: database/create_derangements_table.php
 * Or via CLI: php database/create_derangements_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'derangements'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'derangements' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS derangements;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create Derangements table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS derangements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    cost INT DEFAULT 2,
    description TEXT,
    display_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_cost (cost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_table_sql)) {
    echo "<h2>❌ Error: Failed to create table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'derangements' created successfully!</h2>";

// Descriptions based on generally accepted definitions
$derangement_descriptions = [
    'Acute Sanguinary Aversion' => 'Intense fear or disgust at the sight or thought of blood',
    'Acute Stress Disorder' => 'Severe anxiety and dissociation following a traumatic event',
    'Addiction' => 'Compulsive dependence on a substance or behavior',
    'Agoraphobia' => 'Fear of open spaces or situations where escape might be difficult',
    'Amnesia' => 'Loss of memory, partial or complete',
    'Angelism' => 'Belief that one is an angel or divine being',
    'Antisocial Disorder' => 'Persistent pattern of disregard for the rights of others',
    'Avoidant' => 'Extreme social inhibition and sensitivity to rejection',
    'Berserk' => 'Uncontrollable rage and violent behavior',
    'Bipolar Disorder' => 'Mood disorder with alternating periods of mania and depression',
    'Blood Addict' => 'Compulsive need to consume blood beyond normal requirements',
    'Blood Sweats' => 'Abnormal perspiration of blood',
    'Blood Taste' => 'Obsessive fixation on the taste or quality of blood',
    'Bloodthirst' => 'Intense craving for blood and violence',
    'Borderline' => 'Unstable relationships, self-image, and emotions',
    'Bulimia' => 'Eating disorder involving binge eating followed by purging',
    'Charmed Life Complex' => 'Belief that one is protected by luck or fate',
    'Circadian Rhythm Sleep Disorder' => 'Disruption of normal sleep-wake cycle',
    'Compulsive Lying' => 'Habitual and pathological lying without clear motive',
    'Compulsive-Aggressive Disorder' => 'Compulsive need to act aggressively',
    'Confusion' => 'Mental state of disorientation and unclear thinking',
    'Creation Memory' => 'Obsessive memory of one\'s creation or embrace',
    'Crimson Rage' => 'Vampire-specific berserker rage triggered by blood',
    'Delusional' => 'Holding false beliefs despite evidence to the contrary',
    'Delusional Identity' => 'Belief that one is someone else',
    'Delusions of Disease' => 'False belief that one has a serious illness',
    'Delusions of Grandeur' => 'Exaggerated sense of one\'s importance or power',
    'Delusions of Guilt' => 'False belief that one has committed terrible acts',
    'Delusions of Jealousy' => 'False belief that one\'s partner is unfaithful',
    'Delusions of Passivity' => 'Belief that one\'s actions are controlled by external forces',
    'Delusions of Persecution' => 'False belief that one is being persecuted',
    'Delusions of Poverty' => 'False belief that one is impoverished',
    'Delusions of Reference' => 'Belief that random events have personal significance',
    'Delusions of Thought-Control' => 'Belief that one\'s thoughts are controlled by others',
    'Demophobia' => 'Fear of crowds or large groups of people',
    'Densensitization' => 'Reduced emotional response to normally disturbing stimuli',
    'Dependent' => 'Excessive need to be taken care of by others',
    'Depersonalization Disorder' => 'Feeling detached from one\'s own body or thoughts',
    'Depressive' => 'Persistent feelings of sadness and hopelessness',
    'Dipsomania' => 'Compulsive craving for alcohol or intoxicants',
    'Disorganized Actions' => 'Inability to perform coordinated movements',
    'Disorganized Speech' => 'Incoherent or illogical speech patterns',
    'Dissociation' => 'Detachment from reality, thoughts, or identity',
    'Dissociative Amnesia' => 'Memory loss due to psychological trauma',
    'Dissociative Blood-Spending' => 'Compulsive spending of blood points without memory',
    'Dissociative Fugue' => 'Sudden travel away from home with amnesia',
    'Dissociative Identity Disorder' => 'Presence of two or more distinct personality states',
    'Dissociative Perceptions Syndrome' => 'Distorted perception of reality',
    'Erotomantic Delusions' => 'False belief that someone is in love with you',
    'Exhibitionism' => 'Compulsive need to expose oneself publicly',
    'Factitious Disorder' => 'Faking illness or symptoms for attention',
    'Fantasy' => 'Excessive daydreaming and escape into imaginary worlds',
    'Fugue' => 'Temporary amnesia with wandering or travel',
    'Generalized Anxiety Disorder' => 'Persistent and excessive worry about various things',
    'Gluttony' => 'Excessive consumption, especially of food or blood',
    'Hallucinations' => 'Perceiving things that are not actually present',
    'Handler' => 'Compulsive need to control or manipulate others',
    'Hangover Helper' => 'Dependency on others to manage one\'s problems',
    'Heirarchical Sociology Disorder' => 'Obsessive focus on social status and hierarchy',
    'Herbephrenia' => 'Disorganized schizophrenia with inappropriate emotions',
    'Histrionic' => 'Excessive emotionality and attention-seeking behavior',
    'Histrionics' => 'Dramatic and theatrical behavior',
    'Hunger' => 'Intense, uncontrollable craving for sustenance',
    'Hypersomnia' => 'Excessive sleepiness or prolonged sleep periods',
    'Hypochondria' => 'Excessive worry about having a serious illness',
    'Hysteria' => 'Uncontrolled emotional outbursts or fits',
    'Ideology Fanatic' => 'Extreme devotion to a particular belief system',
    'Immortal Fear' => 'Terror of eternal existence or immortality',
    'Immortal Terror' => 'Overwhelming fear related to one\'s immortal nature',
    'Insomnia' => 'Persistent difficulty falling or staying asleep',
    'Intellectualization' => 'Excessive focus on logic to avoid emotions',
    'Intermittent Explosive Disorder' => 'Recurrent episodes of aggressive outbursts',
    'Kleptomania' => 'Compulsive stealing of items not needed',
    'Manic-Depression' => 'Alternating periods of elevated mood and depression',
    'Masochism' => 'Deriving pleasure from one\'s own pain or humiliation',
    'Megalomania' => 'Delusional belief in one\'s power and importance',
    'Melancholia' => 'Severe depression with loss of interest in activities',
    'Memory Lapses' => 'Frequent gaps in memory or forgetfulness',
    'Mercenary' => 'Excessive focus on material gain and profit',
    'Multiple Personalities' => 'Presence of multiple distinct personality states',
    'Narcissistic' => 'Excessive self-love and lack of empathy for others',
    'Narcolepsy' => 'Sudden, uncontrollable episodes of sleep',
    'Nightmare Disorder' => 'Frequent disturbing dreams causing distress',
    'Obsession' => 'Persistent, unwanted thoughts or impulses',
    'Obsessive-Compulsion' => 'Repetitive behaviors performed to reduce anxiety',
    'Overcompensation' => 'Excessive effort to overcome perceived weaknesses',
    'Pack Feeding' => 'Compulsive need to feed in groups',
    'Panic Disorder' => 'Recurrent panic attacks with intense fear',
    'Panzaism' => 'Belief in the philosophy of Don Quixote, idealistic but impractical',
    'Paranoia' => 'Irrational suspicion and mistrust of others',
    'Paranoid of Ancients' => 'Extreme fear and suspicion of elder vampires',
    'Passion Player' => 'Obsessive pursuit of romantic or sexual conquests',
    'Path Lust' => 'Obsessive desire to follow a particular Path of Enlightenment',
    'Pathological Gambling' => 'Compulsive gambling despite negative consequences',
    'Pedophilia' => 'Sexual attraction to prepubescent children',
    'Perfection' => 'Obsessive need for flawlessness in all things',
    'Phobia' => 'Irrational fear of a specific object or situation',
    'Possession' => 'Belief that one is possessed by another entity',
    'Post-Traumatic Stress Disorder' => 'Anxiety disorder following traumatic events',
    'Power Madness' => 'Delusional obsession with gaining and using power',
    'Power-Object Fixation' => 'Obsessive attachment to specific objects',
    'Progenitor' => 'Excessive focus on creating childer or progeny',
    'Promise' => 'Obsessive need to keep or make promises',
    'Psychosis' => 'Loss of contact with reality',
    'Puppeteerism' => 'Compulsive need to control others like puppets',
    'Pyromania' => 'Compulsive desire to set fires',
    'Quixotism' => 'Idealistic but impractical behavior, like Don Quixote',
    'Regression' => 'Return to earlier developmental stage or behavior',
    'Ritual Freak' => 'Obsessive need to perform rituals or routines',
    'Sadism' => 'Deriving pleasure from inflicting pain on others',
    'Saint Vitus\'s Dance' => 'Involuntary jerky movements, also called chorea',
    'Sanguinary Animism' => 'Belief that blood has a spirit or consciousness',
    'Sanguinary Cryptography' => 'Obsessive belief that blood contains hidden messages',
    'Schizoid' => 'Detachment from social relationships and limited emotional expression',
    'Schizophrenia' => 'Severe mental disorder with distorted thinking and perception',
    'Schizotypal' => 'Eccentric behavior and odd beliefs or magical thinking',
    'Sect Fanatic' => 'Extreme devotion to one\'s sect or organization',
    'Self-Annihilation Impulse' => 'Compulsive desire for self-destruction',
    'Sexual Dysfunction' => 'Inability to experience sexual satisfaction',
    'Sexual Masochism' => 'Sexual arousal from being humiliated or hurt',
    'Sexual Sadism' => 'Sexual arousal from inflicting pain on others',
    'Sleep Terror Disorder' => 'Episodes of intense fear during sleep',
    'Sleepwalking Disorder' => 'Performing activities while asleep',
    'Social Phobia' => 'Intense fear of social situations',
    'Somatic Delusions' => 'False beliefs about one\'s body or physical health',
    'Strangler' => 'Compulsive need to strangle or choke others',
    'Syncophancy' => 'Excessive flattery and servile behavior',
    'Synesthesia' => 'Perception where one sense triggers another',
    'Thaumaturgical Glossolalia' => 'Speaking in tongues related to blood magic',
    'Trichotillomania' => 'Compulsive hair pulling',
    'Undying Remorse' => 'Persistent guilt over past actions',
    'Vengeful' => 'Obsessive desire for revenge',
    'Visions' => 'Seeing things that are not present, often prophetic',
    'Voyeurism' => 'Sexual pleasure from observing others without consent',
    'Wrist Slitter' => 'Compulsive self-harm through cutting',
];

// Parse XML file to extract derangements
$xml_file = __DIR__ . '/../Grapevine/Grapevine Menus XML.gvm';
if (!file_exists($xml_file)) {
    die("Error: XML file not found at: $xml_file\n");
}

$xml_content = file_get_contents($xml_file);
if ($xml_content === false) {
    die("Error: Could not read XML file\n");
}

// Extract derangements from the Derangements menu
preg_match('/<menu name="Derangements"[^>]*>(.*?)<\/menu>/s', $xml_content, $matches);
if (empty($matches[1])) {
    die("Error: Could not find Derangements menu in XML file\n");
}

$menu_content = $matches[1];
// Parse each line to extract item name and cost
$lines = explode("\n", $menu_content);
$item_matches = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/<item name="([^"]+)"(?:\s+cost="(\d+)"|)/', $line, $m)) {
        $item_matches[] = [null, $m[1], isset($m[2]) && $m[2] !== '' ? $m[2] : ''];
    }
}

$derangements_data = [];
$display_order = 1;

foreach ($item_matches as $match) {
    $name = trim($match[1]);
    $cost = isset($match[2]) && $match[2] !== '' ? (int)$match[2] : 2; // Default cost is 2
    $description = $derangement_descriptions[$name] ?? 'Mental or psychological disorder';
    
    $derangements_data[] = [
        'name' => $name,
        'cost' => $cost,
        'description' => $description,
        'display_order' => $display_order++
    ];
}

$insert_sql = "INSERT INTO derangements (name, cost, description, display_order) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);

if (!$stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_count = 0;
$errors = [];

foreach ($derangements_data as $derangement) {
    mysqli_stmt_bind_param($stmt, 'sisi', 
        $derangement['name'], 
        $derangement['cost'], 
        $derangement['description'], 
        $derangement['display_order']
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        $errors[] = "Failed to insert {$derangement['name']}: " . mysqli_stmt_error($stmt);
    } else {
        $inserted_count++;
    }
}

mysqli_stmt_close($stmt);

if (count($errors) > 0) {
    echo "<h3>⚠️ Warnings:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<p>✅ Successfully inserted {$inserted_count} derangements into the table.</p>";

// Show table structure
echo "<h3>Table Structure:</h3>";
$describe_sql = "DESCRIBE derangements";
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

// Show sample data
echo "<h3>Sample Data (first 10 derangements):</h3>";
$sample_sql = "SELECT id, name, cost, description FROM derangements ORDER BY display_order LIMIT 10";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Cost</th><th>Description</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

// Show count by cost
echo "<h3>Derangements Count by Cost:</h3>";
$count_sql = "SELECT cost, COUNT(*) as count FROM derangements GROUP BY cost ORDER BY cost";
$count_result = mysqli_query($conn, $count_sql);

if ($count_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Cost</th><th>Count</th></tr>";
    while ($row = mysqli_fetch_assoc($count_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['cost']) . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($count_result);
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS derangements;</pre>";

mysqli_close($conn);
?>
