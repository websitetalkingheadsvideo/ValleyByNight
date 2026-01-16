<?php
/**
 * Update Merits and Flaws Costs
 * 
 * This script updates the database with costs from a provided list
 * and documents which costs are still missing.
 * 
 * Run via browser: database/update_merits_flaws_costs.php
 * Or via CLI: php database/update_merits_flaws_costs.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Cost data from user's list - using EXACT costs provided by user
$cost_data = [
    // Psychological Merits
    'Code of Honor' => '1',
    'Higher Purpose' => '1',
    'Berserker' => '2',
    
    // Psychological Flaws
    'Compulsion' => '1',
    'Dark Secret' => '1',
    'Intolerance' => '1',
    'Nightmares' => '1',
    'Phobia' => '1', // User said "Phobia - Mild (1)"
    'Prey Exclusion' => '1',
    'Overconfident' => '1',
    'Shy' => '1',
    'Soft-Hearted' => '1',
    'Speech Impediment' => '1',
    'Low Self-Image' => '2',
    'Short Fuse' => '2',
    'Vengeance' => '2',
    'Driving Goal' => '3',
    'Hatred' => '3',
    
    // Mental Merits
    'Light Sleeper' => '2',
    'Calm Heart' => '3',
    'Iron Will' => '3',
    
    // Mental Flaws
    'Deep Sleeper' => '1',
    'Confused' => '2',
    'Weak-Willed' => '3',
    'Absent-Minded' => '3',
    
    // Awareness Merits - user listed separate senses, but DB has "Acute Sense"
    // We'll update "Acute Sense" if it exists, or try to match individual senses
    'Acute Sense' => '1', // Base cost for Acute Sense
    
    // Awareness Flaws
    'Hard of Hearing' => '1',
    'Bad Sight' => '2',
    'One Eye' => '2',
    'Deaf' => '3',
    
    // Aptitudes Merits
    'Eat Food' => '1',
    'Pitiable' => '1',
    'Daredevil' => '3',
    'Jack-of-All-Trades' => '5',
    
    // Aptitudes Flaws
    'Illiterate' => '1',
    
    // Supernatural Merits
    'Inoffensive to Animals' => '1',
    'True Love' => '1',
    'Medium' => '2',
    'Danger Sense' => '2',
    'Faerie Affinity' => '2',
    'Magic Resistance' => '2',
    'Occult Library' => '2',
    'Spirit Mentor' => '3',
    'Unbondable' => '3',
    'Luck' => '4',
    'Destiny' => '4',
    'Guardian Angel' => '6',
    'True Faith' => '7',
    
    // Supernatural Flaws
    'Cursed' => '1-5',
    'Repulsed by Garlic' => '1',
    'Magic Susceptibility' => '2',
    'Can\'t Cross Running Water' => '2',
    'Repelled by Crosses' => '3',
    'Haunted' => '3',
    'Dark Fate' => '5',
    'Light-Sensitive' => '5',
    
    // Kindred Ties Merits
    'Boon' => '1-3',
    'Prestigious Sire' => '1',
    'Reputation' => '2',
    'Clan Friendship' => '3',
    'Pawn' => '3',
    
    // Kindred Ties Flaws
    'Enemy' => '1-5',
    'Infamous Sire' => '1',
    'Insane Sire' => '1',
    'Mistaken Identity' => '1',
    'Sire\'s Resentment' => '1',
    'Twisted Upbringing' => '1',
    'Clan Enmity' => '2',
    'Diabolic Sire' => '2',
    'Notoriety' => '3',
    
    // Physical Merits
    'Baby Face' => '1',
    'Double-Jointed' => '1',
    'Efficient Digestion' => '3',
    'Huge Size' => '4',
    
    // Physical Flaws
    'Allergic' => '1-3', // User said "1-3 Trait Flaw"
    'Disfigured' => '2',
    'Child' => '3',
    'Deformity' => '3',
    'Lame' => '3',
    'Monstrous' => '3',
    'One Arm' => '3',
    'Permanent Wound' => '3',
    'Mute' => '4',
    'Thin Blood' => '4', // User said "Thin-blooded"
    'Paraplegic' => '5',
];

// Name variations mapping (user's names -> database names)
$name_variations = [
    'Double-jointed' => 'Double-Jointed',
    'Thin-blooded' => 'Thin Blood',
    'Soft Hearted' => 'Soft-Hearted',
    'Can\'t Cross Running Water' => 'Can\'t Cross Running Water',
    'Repelled by Crosses' => 'Repelled by Crosses',
    'Sire\'s Resentment' => 'Sire\'s Resentment',
];

// Get current NULL costs from database
$merits_null = mysqli_query($conn, "SELECT name FROM merits WHERE cost IS NULL ORDER BY name");
$flaws_null = mysqli_query($conn, "SELECT name FROM flaws WHERE cost IS NULL ORDER BY name");

$merits_null_list = [];
while ($row = mysqli_fetch_assoc($merits_null)) {
    $merits_null_list[] = $row['name'];
}

$flaws_null_list = [];
while ($row = mysqli_fetch_assoc($flaws_null)) {
    $flaws_null_list[] = $row['name'];
}

// Function to find name in database (handles variations)
function findNameInDb($conn, $name, $table) {
    // Try exact match first
    $escaped_name = mysqli_real_escape_string($conn, $name);
    $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE name = '{$escaped_name}'");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['name'];
    }
    
    // Try case-insensitive match
    $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE LOWER(name) = LOWER('{$escaped_name}')");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['name'];
    }
    
    // Try with variations (hyphens, spaces, etc.)
    $variations = [
        str_replace('-', ' ', $name),
        str_replace(' ', '-', $name),
        str_replace("'", "'", $name),
        str_replace("'", "'", $name),
    ];
    
    foreach ($variations as $variation) {
        $escaped = mysqli_real_escape_string($conn, $variation);
        $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE LOWER(name) = LOWER('{$escaped}')");
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['name'];
        }
    }
    
    return null;
}

// Update merits
$update_merits_sql = "UPDATE merits SET cost = ? WHERE name = ?";
$merits_stmt = mysqli_prepare($conn, $update_merits_sql);

$merits_updated = 0;
$merits_not_found = [];
$merits_errors = [];
$merits_updated_list = [];

foreach ($cost_data as $name => $cost) {
    $db_name = findNameInDb($conn, $name, 'merits');
    
    if ($db_name) {
        mysqli_stmt_bind_param($merits_stmt, 'ss', $cost, $db_name);
        if (mysqli_stmt_execute($merits_stmt)) {
            $merits_updated++;
            $merits_updated_list[] = "{$db_name} => {$cost}";
        } else {
            $merits_errors[] = "Failed to update {$db_name}: " . mysqli_stmt_error($merits_stmt);
        }
    } else {
        $merits_not_found[] = $name;
    }
}

mysqli_stmt_close($merits_stmt);

// Update flaws
$update_flaws_sql = "UPDATE flaws SET cost = ? WHERE name = ?";
$flaws_stmt = mysqli_prepare($conn, $update_flaws_sql);

$flaws_updated = 0;
$flaws_not_found = [];
$flaws_errors = [];
$flaws_updated_list = [];

foreach ($cost_data as $name => $cost) {
    $db_name = findNameInDb($conn, $name, 'flaws');
    
    if ($db_name) {
        mysqli_stmt_bind_param($flaws_stmt, 'ss', $cost, $db_name);
        if (mysqli_stmt_execute($flaws_stmt)) {
            $flaws_updated++;
            $flaws_updated_list[] = "{$db_name} => {$cost}";
        } else {
            $flaws_errors[] = "Failed to update {$db_name}: " . mysqli_stmt_error($flaws_stmt);
        }
    } else {
        // Only add to not_found if it's not a merit
        if (!findNameInDb($conn, $name, 'merits')) {
            $flaws_not_found[] = $name;
        }
    }
}

mysqli_stmt_close($flaws_stmt);

// Get remaining NULL costs
$merits_still_null = mysqli_query($conn, "SELECT name FROM merits WHERE cost IS NULL ORDER BY name");
$flaws_still_null = mysqli_query($conn, "SELECT name FROM flaws WHERE cost IS NULL ORDER BY name");

$merits_still_null_list = [];
while ($row = mysqli_fetch_assoc($merits_still_null)) {
    $merits_still_null_list[] = $row['name'];
}

$flaws_still_null_list = [];
while ($row = mysqli_fetch_assoc($flaws_still_null)) {
    $flaws_still_null_list[] = $row['name'];
}

// Display results
echo "<h2>✅ Database Cost Update Complete</h2>";
echo "<p>✅ Successfully updated {$merits_updated} merits with costs.</p>";
echo "<p>✅ Successfully updated {$flaws_updated} flaws with costs.</p>";

if (count($merits_not_found) > 0) {
    echo "<h3>⚠️ Merits from your list not found in database:</h3>";
    echo "<ul>";
    foreach ($merits_not_found as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
}

if (count($flaws_not_found) > 0) {
    echo "<h3>⚠️ Flaws from your list not found in database:</h3>";
    echo "<ul>";
    foreach ($flaws_not_found as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
}

if (count($merits_errors) > 0 || count($flaws_errors) > 0) {
    echo "<h3>⚠️ Errors:</h3>";
    echo "<ul>";
    foreach (array_merge($merits_errors, $flaws_errors) as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Display remaining NULL costs
echo "<h3>💰 Merits Still Missing Costs (" . count($merits_still_null_list) . "):</h3>";
if (count($merits_still_null_list) > 0) {
    echo "<ul>";
    foreach ($merits_still_null_list as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All merits now have costs!</p>";
}

echo "<h3>💰 Flaws Still Missing Costs (" . count($flaws_still_null_list) . "):</h3>";
if (count($flaws_still_null_list) > 0) {
    echo "<ul>";
    foreach ($flaws_still_null_list as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All flaws now have costs!</p>";
}

// Save updated lists to files
$output_dir = __DIR__ . '/../tmp';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

file_put_contents($output_dir . '/merits_still_missing_costs.txt', implode("\n", $merits_still_null_list));
file_put_contents($output_dir . '/flaws_still_missing_costs.txt', implode("\n", $flaws_still_null_list));

// Create detailed update log
$update_log = "MERITS UPDATED:\n" . implode("\n", $merits_updated_list) . "\n\n";
$update_log .= "FLAWS UPDATED:\n" . implode("\n", $flaws_updated_list) . "\n\n";
$update_log .= "MERITS NOT FOUND:\n" . implode("\n", $merits_not_found) . "\n\n";
$update_log .= "FLAWS NOT FOUND:\n" . implode("\n", $flaws_not_found) . "\n\n";
$update_log .= "MERITS STILL MISSING COSTS:\n" . implode("\n", $merits_still_null_list) . "\n\n";
$update_log .= "FLAWS STILL MISSING COSTS:\n" . implode("\n", $flaws_still_null_list) . "\n";

file_put_contents($output_dir . '/cost_update_log.txt', $update_log);

echo "<h3>💾 Updated lists saved to:</h3>";
echo "<ul>";
echo "<li>tmp/merits_still_missing_costs.txt</li>";
echo "<li>tmp/flaws_still_missing_costs.txt</li>";
echo "<li>tmp/cost_update_log.txt (detailed log)</li>";
echo "</ul>";

// Show summary
echo "<h3>📊 Summary:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Category</th><th>Updated</th><th>Still Missing</th></tr>";
echo "<tr><td>Merits</td><td>{$merits_updated}</td><td>" . count($merits_still_null_list) . "</td></tr>";
echo "<tr><td>Flaws</td><td>{$flaws_updated}</td><td>" . count($flaws_still_null_list) . "</td></tr>";
echo "</table>";

mysqli_close($conn);
?>
