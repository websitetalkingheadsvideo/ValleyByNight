<?php
/**
 * Analyze Character Teasers
 * Compares teaser files with database characters to find missing teasers
 */

// Get project root (4 levels up from tracking/)
$project_root = dirname(dirname(dirname(dirname(__DIR__))));

// Use API endpoint to get characters (avoids direct DB connection issues)
$api_url = 'https://vbn.talkingheads.video/includes/api_get_character_names.php';
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'ignore_errors' => true
    ]
]);

$api_response = @file_get_contents($api_url, false, $context);
$db_characters = [];

if ($api_response) {
    $data = json_decode($api_response, true);
    if ($data && isset($data['characters']) && $data['success']) {
        foreach ($data['characters'] as $char) {
            $db_characters[] = trim($char['name']);
        }
    }
}

// Fallback: if API fails, try direct DB connection
if (empty($db_characters)) {
    $connect_file = $project_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'connect.php';
    if (file_exists($connect_file)) {
        require_once $connect_file;
        $query = "SELECT character_name FROM characters ORDER BY character_name ASC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $db_characters[] = trim($row['character_name']);
            }
            mysqli_close($conn);
        }
    }
}

// Get all teaser files
$teaser_dir = __DIR__ . '/../';
$files = glob($teaser_dir . '*.md');

$teaser_characters = [];
$teaser_files = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip non-character teaser files (but note Misfortune has a director version)
    if (in_array($filename, [
        'Valley_by_Night_Cinematic_Intro_Guide.md'
    ])) {
        continue;
    }
    
    // Handle Misfortune's director version file
    if ($filename === 'misfortune_director_version.md') {
        $teaser_characters[] = 'Misfortune';
        $teaser_files['Misfortune'] = $filename;
        continue;
    }
    
    // Extract character name from filename
    $name = $filename;
    
    // Remove common prefixes/suffixes
    $name = preg_replace('/^[0-9]+_/', '', $name); // Remove numbered prefixes like "01_"
    $name = preg_replace('/_Intro\.md$/', '', $name);
    $name = preg_replace('/_Cinematic_Intro\.md$/', '', $name);
    $name = preg_replace('/_Cinematic_Introduction\.md$/', '', $name);
    $name = preg_replace('/_teaser\.md$/', '', $name);
    $name = preg_replace('/_intro_script\.md$/', '', $name);
    $name = preg_replace('/_intros\.md$/', '', $name);
    $name = preg_replace('/_Ledger_of_the_Dead_Revised\.md$/', '', $name);
    $name = preg_replace('/\.md$/', '', $name);
    
    // Handle special cases and name variations
    $name_mappings = [
        'Core (Alexandra Chen)' => 'Core',
        'EddyValiant' => 'Eddy Valiant',
        'SarahHansen' => 'Sarah Hansen',
        'CW_Whitford' => 'Charles "C.W." Whitford',
        'James Whitford' => 'Charles "C.W." Whitford',
        'Lilith Nightshade' => 'Lilith Nightshade',
        'Lilith_Nightshade' => 'Lilith Nightshade',
        'Jax \'The Ghost Dealer\'' => 'Jax',
        'Jax' => 'Jax',
        'Violet \'The Confidence Queen\'' => 'Violet',
        'Violet' => 'Violet',
        'Marisol \'Roadrunner\' Vega' => 'Marisol "Roadrunner" Vega',
        'Kerry, the Gangrel' => 'Kerry, the Desert-Wandering Gangrel',
        'Barry' => 'Barry Washington',
        'warner_barry' => 'Barry Washington',
        'Roxanne Vega' => 'Roxanne Murphy', // Vega is an alias for Murphy
        'Roxanne Murphy' => 'Roxanne Murphy',
    ];
    
    // Check if we have a mapping for this name
    if (isset($name_mappings[$name])) {
        $name = $name_mappings[$name];
    }
    
    // Clean up the name
    $name = trim($name);
    
    if (!empty($name)) {
        $teaser_characters[] = $name;
        $teaser_files[$name] = $filename;
    }
    
}

// Also check for duplicates/variations
$teaser_variations = [
    'Eddy Valiant' => ['01_EddyValiant_Intro.md'],
    'Sarah Hansen' => ['02_SarahHansen_Intro.md', 'Sarah Hansen.md'],
    'Roland Cross' => ['Roland Cross.md', 'roland_teaser.md'],
    'Barry Warner' => ['barry_intro_script.md', 'warner_barry_intros.md'],
    'James Whitford' => ['CW_Whitford_Cinematic_Introduction.md'],
    'Lilith Nightshade' => ['Lilith_Nightshade_Cinematic_Intro.md'],
    'Jax' => ['Jax.md', 'Jax \'The Ghost Dealer\'.md'],
    'Violet' => ['Violet.md', 'Violet \'The Confidence Queen\'.md'],
    'Alessandro Vescari' => ['Alessandro Vescari.md', 'Alessandro_Ledger_of_the_Dead_Revised.md']
];

// Normalize database character names for comparison
function normalize_name($name) {
    // Convert to lowercase, remove extra spaces
    $name = strtolower(trim($name));
    $name = preg_replace('/\s+/', ' ', $name);
    // Remove quotes and special characters for better matching
    $name = str_replace(['"', "'", ',', '.'], '', $name);
    // Handle common variations
    $name = preg_replace('/\s*\([^)]*\)\s*/', '', $name); // Remove parentheticals
    return $name;
}

// Additional name matching for variations
function match_character_name($db_name, $teaser_names) {
    $db_normalized = normalize_name($db_name);
    
    // Direct match
    if (isset($teaser_names[$db_normalized])) {
        return true;
    }
    
    // Handle specific variations - check if any teaser name matches
    foreach ($teaser_names as $teaser_normalized => $teaser_original) {
        // Check if base names match (ignoring quotes, nicknames, etc.)
        $db_base = preg_replace('/["\'].*?["\']/', '', $db_normalized);
        $teaser_base = preg_replace('/["\'].*?["\']/', '', $teaser_normalized);
        
        // Remove common suffixes
        $db_base = preg_replace('/,?\s*(the|of|from).*$/', '', $db_base);
        $teaser_base = preg_replace('/,?\s*(the|of|from).*$/', '', $teaser_base);
        
        // Check if first names match
        $db_first = explode(' ', trim($db_base))[0];
        $teaser_first = explode(' ', trim($teaser_base))[0];
        
        if ($db_first === $teaser_first && strlen($db_first) > 2) {
            // For single-word names or when first names match, check if it's likely the same
            if (strlen($db_base) < 15 || $db_base === $teaser_base) {
                return true;
            }
        }
    }
    
    // Handle specific known variations
    $variations = [
        'eddy valiant' => ['eddyvaliant'],
        'sarah hansen' => ['sarahhansen'],
        'charles cw whitford' => ['cw whitford', 'james whitford'],
        'core alexandra chen' => ['core'],
        'barry washington' => ['barry'],
        'roxanne murphy' => ['roxanne vega'], // Vega is an alias
        'roxanne vega' => ['roxanne murphy'], // Treat as same character
        'marisol roadrunner vega' => ['marisol vega'],
        'kerry desert-wandering gangrel' => ['kerry gangrel', 'kerry the gangrel'],
        'jax ghost dealer' => ['jax'],
        'violet confidence queen' => ['violet'],
        'lilith nightshade' => ['lilith nightshade', 'lilith_nightshade'],
        'misfortune' => ['misfortune'],
    ];
    
    foreach ($variations as $canonical => $alts) {
        if (strpos($db_normalized, $canonical) !== false || strpos($canonical, $db_normalized) !== false) {
            foreach ($alts as $alt) {
                foreach ($teaser_names as $tn => $to) {
                    if (strpos($tn, $alt) !== false || strpos($alt, $tn) !== false) {
                        return true;
                    }
                }
            }
        }
    }
    
    return false;
}

// Create normalized lists for comparison
$db_normalized = [];
foreach ($db_characters as $char) {
    $normalized = normalize_name($char);
    $db_normalized[$normalized] = $char; // Keep original for display
}

$teaser_normalized = [];
foreach ($teaser_characters as $char) {
    $normalized = normalize_name($char);
    $teaser_normalized[$normalized] = $char;
}

// Find missing teasers
$missing = [];
$seen_aliases = []; // Track aliases to avoid duplicates
foreach ($db_normalized as $normalized => $original) {
    // Skip if this is a known alias we've already handled
    if (isset($seen_aliases[$normalized])) {
        continue;
    }
    
    // Check if this character has a teaser
    if (!isset($teaser_normalized[$normalized]) && !match_character_name($original, $teaser_normalized)) {
        $missing[] = $original;
        
        // Mark known aliases so we don't list them separately
        // Roxanne Vega is an alias for Roxanne Murphy
        if (stripos($original, 'Roxanne Vega') !== false) {
            $seen_aliases[normalize_name('Roxanne Murphy')] = true;
        } elseif (stripos($original, 'Roxanne Murphy') !== false) {
            $seen_aliases[normalize_name('Roxanne Vega')] = true;
        }
    }
}

// Sort missing list alphabetically
sort($missing);

// Output results
echo "=== TEASER ANALYSIS ===\n\n";
echo "Total characters in database: " . count($db_characters) . "\n";
echo "Total teaser files found: " . count($teaser_characters) . "\n";
echo "Missing teasers: " . count($missing) . "\n\n";

echo "=== MISSING TEASERS ===\n";
foreach ($missing as $char) {
    echo "- $char\n";
}

// Save to file
$output_file = __DIR__ . '/../Teasers_to_do.md';
$content = "# Character Teasers To Do\n\n";
$content .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";

if (empty($db_characters)) {
    $content .= "⚠️ **Note:** Database connection failed. Analysis based on teaser files only.\n";
    $content .= "Please run this script when database is accessible for full comparison.\n\n";
    $content .= "**Total teaser files found:** " . count($teaser_characters) . "\n\n";
    $content .= "## Teaser Files Found\n\n";
    sort($teaser_characters);
    foreach ($teaser_characters as $char) {
        $content .= "- $char\n";
    }
} else {
    $content .= "**Total characters in database:** " . count($db_characters) . "\n";
    $content .= "**Total teaser files found:** " . count($teaser_characters) . "\n";
    $content .= "**Missing teasers:** " . count($missing) . "\n\n";
    $content .= "---\n\n";
    $content .= "## Characters Missing Teasers\n\n";
    
    if (count($missing) > 0) {
        foreach ($missing as $index => $char) {
            $content .= ($index + 1) . ". $char\n";
        }
    } else {
        $content .= "All characters have teasers! 🎉\n";
    }
    
    $content .= "\n---\n\n";
    $content .= "## Characters With Teasers\n\n";
    $content .= "*(For reference)*\n\n";
    
    $with_teasers = array_diff($db_characters, $missing);
    sort($with_teasers);
    foreach ($with_teasers as $char) {
        $content .= "- $char\n";
    }
}

file_put_contents($output_file, $content);
echo "\n\nResults saved to: $output_file\n";

if (isset($conn)) {
    mysqli_close($conn);
}
?>

