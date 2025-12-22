<?php
/**
 * Generate Abilities for Character Script
 * 
 * Generates appropriate abilities for a character based on their concept,
 * disciplines, biography, and role.
 * 
 * Usage:
 *   php tools/repeatable/generate_abilities_for_character.php --character-id=<id> [options]
 * 
 * Options:
 *   --character-id=<id>  Character ID to generate abilities for
 *   --dry-run           Show what would be added without writing to database
 *   --verbose           Show detailed reasoning
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

$options = [
    'character-id' => null,
    'dry-run' => false,
    'verbose' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (preg_match('/^--character-id=(\d+)$/', $arg, $matches)) {
        $options['character-id'] = (int)$matches[1];
    }
}

if (!$options['character-id']) {
    die("ERROR: --character-id is required\nUsage: php tools/repeatable/generate_abilities_for_character.php --character-id=<id> [--dry-run] [--verbose]\n");
}

$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

mysqli_set_charset($conn, 'utf8mb4');

// Check if ability_category column exists
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$column_check = mysqli_query($conn, $check_column_sql);
$has_category_column = ($column_check && mysqli_num_rows($column_check) > 0);
if ($column_check) {
    mysqli_free_result($column_check);
}

$character_id = $options['character-id'];

// Get character data
$char = db_fetch_one($conn, "SELECT * FROM characters WHERE id = ?", 'i', [$character_id]);

if (!$char) {
    die("ERROR: Character not found\n");
}

echo "=== Generate Abilities for Character ===\n";
echo "Character: {$char['character_name']} (ID: {$character_id})\n";
echo "Concept: " . ($char['concept'] ?? 'N/A') . "\n";
echo "Clan: " . ($char['clan'] ?? 'N/A') . "\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE") . "\n\n";

// Get disciplines
$disciplines = db_fetch_all($conn,
    "SELECT discipline_name FROM character_disciplines WHERE character_id = ?",
    'i', [$character_id]
);

$discipline_names = array_column($disciplines, 'discipline_name');
echo "Disciplines: " . implode(', ', $discipline_names) . "\n\n";

// Generate abilities based on character data
$abilities = [];

// Analyze concept and biography
$concept = strtolower($char['concept'] ?? '');
$biography = strtolower($char['biography'] ?? '');
$notes = strtolower($char['notes'] ?? '');

$text = $concept . ' ' . $biography . ' ' . $notes;

// Check for key indicators
$indicators = [
    'occult' => ['occult', 'ritual', 'sorcery', 'akhu', 'priestess', 'priest', 'temple', 'worship', 'magic', 'necromancy', 'thaumaturgy'],
    'leadership' => ['leader', 'runs', 'operates', 'manages', 'commands', 'rules', 'controls', 'nightclub', 'business', 'establishment'],
    'security' => ['security', 'defense', 'defenses', 'layered', 'protection', 'guards', 'monitoring', 'surveillance'],
    'subterfuge' => ['hiding', 'concealed', 'hidden', 'secret', 'front', 'disguise', 'deception', 'cover'],
    'performance' => ['performance', 'entertainment', 'nightclub', 'club', 'venue', 'show', 'stage'],
    'investigation' => ['investigates', 'monitors', 'watches', 'tracks', 'surveillance', 'awareness'],
    'intimidation' => ['intimidating', 'threat', 'menace', 'fear', 'powerful'],
    'streetwise' => ['street', 'underworld', 'criminal', 'gang', 'network'],
    'finance' => ['money', 'financial', 'resources', 'wealth', 'funds', 'profit'],
    'law' => ['legal', 'law', 'lawyer', 'attorney', 'court', 'legal system'],
    'politics' => ['political', 'politics', 'government', 'influence', 'status'],
    'academics' => ['scholar', 'research', 'study', 'academic', 'university', 'education'],
    'science' => ['science', 'scientist', 'research', 'laboratory', 'experiment'],
    'medicine' => ['doctor', 'medical', 'healing', 'medicine', 'hospital', 'clinic'],
    'technology' => ['computer', 'technology', 'hacking', 'programming', 'tech', 'digital'],
    'driving' => ['drives', 'driver', 'vehicle', 'car', 'transport'],
    'firearms' => ['gun', 'firearm', 'weapon', 'shooting', 'marksman'],
    'melee' => ['sword', 'blade', 'melee', 'close combat', 'fighting'],
    'brawl' => ['fighting', 'brawling', 'combat', 'physical', 'strength'],
    'athletics' => ['athletic', 'running', 'climbing', 'physical fitness'],
    'stealth' => ['stealth', 'sneaking', 'hidden', 'invisible', 'unseen'],
    'survival' => ['survival', 'wilderness', 'outdoors', 'nature'],
    'animal_ken' => ['animal', 'beast', 'creature', 'pet', 'companion'],
    'empathy' => ['empathy', 'understanding', 'emotions', 'feelings', 'reading people'],
    'expression' => ['art', 'creative', 'artistic', 'expression', 'writing', 'poetry'],
    'larceny' => ['theft', 'stealing', 'burglary', 'lockpicking', 'breaking'],
    'crafts' => ['craft', 'making', 'building', 'construction', 'artisan'],
    'linguistics' => ['language', 'linguistics', 'speaks', 'translation', 'multilingual']
];

foreach ($indicators as $ability => $keywords) {
    $matches = 0;
    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $matches++;
        }
    }
    if ($matches > 0) {
        // Determine level based on matches and context
        $level = min(5, max(2, $matches + 1));
        $ability_name = ucfirst(str_replace('_', ' ', $ability));
        $abilities[] = [
            'name' => $ability_name,
            'category' => getAbilityCategory($ability_name),
            'level' => $level,
            'reason' => "Found {$matches} keyword match(es) in character description"
        ];
    }
}

// Add abilities based on disciplines
$discipline_abilities = [
    'Serpentis' => ['Occult', 'Subterfuge', 'Stealth'],
    'Presence' => ['Performance', 'Leadership', 'Expression', 'Intimidation'],
    'Obfuscate' => ['Stealth', 'Subterfuge', 'Security'],
    'Akhu' => ['Occult', 'Investigation', 'Academics'],
    'Thaumaturgy' => ['Occult', 'Academics', 'Investigation'],
    'Necromancy' => ['Occult', 'Investigation', 'Academics'],
    'Dominate' => ['Leadership', 'Intimidation', 'Subterfuge'],
    'Auspex' => ['Investigation', 'Alertness', 'Empathy'],
    'Celerity' => ['Athletics', 'Dodge', 'Firearms'],
    'Fortitude' => ['Survival', 'Resistance'],
    'Potence' => ['Athletics', 'Brawl', 'Melee'],
    'Protean' => ['Survival', 'Animal Ken', 'Stealth']
];

foreach ($discipline_names as $discipline) {
    if (isset($discipline_abilities[$discipline])) {
        foreach ($discipline_abilities[$discipline] as $ability_name) {
            // Check if already added
            $exists = false;
            foreach ($abilities as $existing) {
                if ($existing['name'] === $ability_name) {
                    $exists = true;
                    // Increase level if discipline-related
                    $existing['level'] = min(5, $existing['level'] + 1);
                    break;
                }
            }
            if (!$exists) {
                $abilities[] = [
                    'name' => $ability_name,
                    'category' => getAbilityCategory($ability_name),
                    'level' => 3,
                    'reason' => "Related to {$discipline} discipline"
                ];
            }
        }
    }
}

// Ensure we have a reasonable set (3-5 per category minimum for important characters)
$by_category = [];
foreach ($abilities as $ability) {
    $cat = $ability['category'];
    if (!isset($by_category[$cat])) {
        $by_category[$cat] = [];
    }
    $by_category[$cat][] = $ability;
}

// Add some defaults if we have very few
if (count($abilities) < 6) {
    // Add common abilities for a Kindred
    $defaults = [
        ['name' => 'Alertness', 'category' => 'Mental', 'level' => 2],
        ['name' => 'Subterfuge', 'category' => 'Social', 'level' => 2],
        ['name' => 'Streetwise', 'category' => 'Social', 'level' => 2]
    ];
    
    foreach ($defaults as $default) {
        $exists = false;
        foreach ($abilities as $existing) {
            if ($existing['name'] === $default['name']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $abilities[] = array_merge($default, ['reason' => 'Default Kindred ability']);
        }
    }
}

// Remove duplicates, keeping highest level
$unique_abilities = [];
foreach ($abilities as $ability) {
    $key = $ability['name'];
    if (!isset($unique_abilities[$key])) {
        $unique_abilities[$key] = $ability;
    } else {
        // Keep the one with higher level
        if ($ability['level'] > $unique_abilities[$key]['level']) {
            $unique_abilities[$key] = $ability;
        }
    }
}
$abilities = array_values($unique_abilities);

// Display what will be added
echo "Generated Abilities:\n";
echo str_repeat('-', 60) . "\n";
foreach (['Physical', 'Social', 'Mental'] as $category) {
    $cat_abilities = array_filter($abilities, function($a) use ($category) {
        return $a['category'] === $category;
    });
    if (count($cat_abilities) > 0) {
        echo "\n{$category}:\n";
        foreach ($cat_abilities as $ability) {
            echo "  - {$ability['name']} x{$ability['level']}";
            if ($options['verbose']) {
                echo " ({$ability['reason']})";
            }
            echo "\n";
        }
    }
}

echo "\nTotal: " . count($abilities) . " abilities\n\n";

if ($options['dry-run']) {
    echo "[DRY RUN] No changes made.\n";
    exit(0);
}

// Insert abilities
$inserted = 0;
if ($has_category_column) {
    $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?)";
} else {
    $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)";
}

echo "Preparing insert statement...\n";
$stmt = mysqli_prepare($conn, $insert_sql);
if (!$stmt) {
    die("ERROR: Failed to prepare statement: " . mysqli_error($conn) . "\n");
}
echo "Inserting " . count($abilities) . " abilities...\n";

foreach ($abilities as $ability) {
    try {
        if ($has_category_column) {
            $spec = $ability['specialization'] ?? null;
            mysqli_stmt_bind_param($stmt, 'issis',
                $character_id,
                $ability['name'],
                $ability['category'],
                $ability['level'],
                $spec
            );
        } else {
            $spec = $ability['specialization'] ?? null;
            mysqli_stmt_bind_param($stmt, 'isis',
                $character_id,
                $ability['name'],
                $ability['level'],
                $spec
            );
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $inserted++;
            if ($options['verbose']) {
                echo "  ✓ Inserted {$ability['name']} x{$ability['level']} ({$ability['category']})\n";
            }
        } else {
            echo "Warning: Failed to insert {$ability['name']}: " . mysqli_stmt_error($stmt) . "\n";
        }
    } catch (Exception $e) {
        echo "Error inserting {$ability['name']}: " . $e->getMessage() . "\n";
    }
}

mysqli_stmt_close($stmt);

echo "✓ Successfully inserted {$inserted} abilities\n";
echo "Done.\n";

/**
 * Get ability category based on ability name
 */
function getAbilityCategory(string $ability): string {
    $ability_lower = strtolower($ability);
    
    $physical = ['athletics', 'brawl', 'dodge', 'firearms', 'melee', 'stealth', 'survival', 'drive', 'larceny', 'crafts'];
    $social = ['leadership', 'performance', 'expression', 'intimidation', 'subterfuge', 'streetwise', 'empathy', 'animal ken', 'etiquette'];
    $mental = ['academics', 'computer', 'finance', 'investigation', 'law', 'linguistics', 'medicine', 'occult', 'politics', 'science', 'technology', 'alertness', 'awareness'];
    
    if (in_array($ability_lower, $physical)) {
        return 'Physical';
    } elseif (in_array($ability_lower, $social)) {
        return 'Social';
    } elseif (in_array($ability_lower, $mental)) {
        return 'Mental';
    }
    
    // Default based on common patterns
    if (strpos($ability_lower, 'occult') !== false || strpos($ability_lower, 'magic') !== false) {
        return 'Mental';
    }
    if (strpos($ability_lower, 'leadership') !== false || strpos($ability_lower, 'performance') !== false) {
        return 'Social';
    }
    if (strpos($ability_lower, 'athletic') !== false || strpos($ability_lower, 'combat') !== false) {
        return 'Physical';
    }
    
    return 'Mental'; // Default fallback
}

