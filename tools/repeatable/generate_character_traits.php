<?php
/**
 * Character Traits Generation Script
 * 
 * Generates traits for characters missing them based on:
 * - Abilities and ability levels
 * - Disciplines and discipline levels
 * - Clan characteristics
 * - Nature and Demeanor archetypes
 * - Biography text analysis
 * - Concept description
 * 
 * This script is idempotent and safe to run repeatedly.
 * 
 * Usage:
 *   php tools/repeatable/generate_character_traits.php [options]
 * 
 * Optional Options:
 *   --character-id=<id>  Generate traits for specific character only
 *   --dry-run            Show what would be generated without writing to database
 *   --verbose            Show detailed progress for each character
 *   --help               Show this help message
 * 
 * Output Files:
 *   - traits_generation_report.json    Report of generated traits
 *   - traits_generation.log            Log of all operations
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

// Parse command-line arguments
$options = [
    'character-id' => null,
    'dry-run' => false,
    'verbose' => false,
    'help' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (preg_match('/^--character-id=(\d+)$/', $arg, $matches)) {
        $options['character-id'] = (int)$matches[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    }
}

if ($options['help']) {
    echo "Character Traits Generation Script\n\n";
    echo "Usage: php tools/repeatable/generate_character_traits.php [options]\n\n";
    echo "Optional Options:\n";
    echo "  --character-id=<id>  Generate traits for specific character only\n";
    echo "  --dry-run            Show what would be generated without writing to database\n";
    echo "  --verbose            Show detailed progress for each character\n";
    echo "  --help               Show this help message\n\n";
    echo "Examples:\n";
    echo "  php tools/repeatable/generate_character_traits.php --dry-run\n";
    echo "  php tools/repeatable/generate_character_traits.php --character-id=55 --verbose\n\n";
    exit(0);
}

// Get project root (two levels up from tools/repeatable/)
$project_root = dirname(__DIR__, 2);

// Database connection
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// Output directory
$output_dir = __DIR__;
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Statistics
$stats = [
    'total_scanned' => 0,
    'missing_traits' => 0,
    'traits_generated' => 0,
    'characters_updated' => 0,
    'errors' => 0
];

// Data structures
$generated_characters = [];
$update_log = [];

/**
 * Trait mapping tables
 */
$trait_mappings = [
    'ability' => [
        'Physical' => [
            'Athletics' => ['Agile', 'Quick', 'Athletic', 'Nimble'],
            'Brawl' => ['Strong', 'Tough', 'Hardy', 'Vigorous'],
            'Melee' => ['Strong', 'Steady', 'Coordinated', 'Precise'],
            'Stealth' => ['Agile', 'Graceful', 'Light-footed', 'Subtle'],
            'Survival' => ['Tough', 'Resilient', 'Hardy', 'Rugged'],
            'Drive' => ['Quick', 'Alert', 'Coordinated', 'Steady']
        ],
        'Social' => [
            'Expression' => ['Charismatic', 'Eloquent', 'Articulate', 'Persuasive'],
            'Performance' => ['Charismatic', 'Alluring', 'Dramatic', 'Expressive'],
            'Subterfuge' => ['Cunning', 'Manipulative', 'Deceptive', 'Subtle'],
            'Leadership' => ['Commanding', 'Charismatic', 'Inspiring', 'Authoritative'],
            'Etiquette' => ['Dignified', 'Poised', 'Elegant', 'Refined'],
            'Empathy' => ['Empathetic', 'Perceptive', 'Understanding', 'Intuitive'],
            'Intimidation' => ['Intimidating', 'Commanding', 'Imposing', 'Forceful'],
            'Streetwise' => ['Street-smart', 'Resourceful', 'Cunning', 'Shrewd']
        ],
        'Mental' => [
            'Academics' => ['Intelligent', 'Studious', 'Knowledgeable', 'Learned'],
            'Investigation' => ['Observant', 'Perceptive', 'Analytical', 'Inquisitive'],
            'Occult' => ['Studious', 'Mystical', 'Intuitive', 'Esoteric'],
            'Science' => ['Analytical', 'Intelligent', 'Methodical', 'Precise'],
            'Computer' => ['Analytical', 'Technical', 'Focused', 'Methodical'],
            'Technology' => ['Technical', 'Innovative', 'Precise', 'Analytical'],
            'Security' => ['Alert', 'Observant', 'Cautious', 'Vigilant'],
            'Crafts' => ['Skilled', 'Precise', 'Creative', 'Dexterous'],
            'Finance' => ['Analytical', 'Calculating', 'Shrewd', 'Methodical'],
            'Politics' => ['Strategic', 'Diplomatic', 'Cunning', 'Perceptive']
        ]
    ],
    'discipline' => [
        'Physical' => [
            'Celerity' => ['Quick', 'Nimble', 'Agile', 'Light-footed'],
            'Potence' => ['Strong', 'Powerful', 'Vigorous', 'Hardy'],
            'Fortitude' => ['Tough', 'Resilient', 'Hardy', 'Sturdy'],
            'Protean' => ['Wild', 'Adaptable', 'Resilient', 'Feral']
        ],
        'Social' => [
            'Presence' => ['Charismatic', 'Alluring', 'Commanding', 'Magnetic'],
            'Dominate' => ['Commanding', 'Intimidating', 'Authoritative', 'Forceful'],
            'Serpentis' => ['Alluring', 'Seductive', 'Graceful', 'Hypnotic']
        ],
        'Mental' => [
            'Auspex' => ['Perceptive', 'Intuitive', 'Observant', 'Aware'],
            'Obfuscate' => ['Subtle', 'Cunning', 'Unassuming', 'Stealthy'],
            'Thaumaturgy' => ['Studious', 'Analytical', 'Focused', 'Mystical'],
            'Necromancy' => ['Studious', 'Mystical', 'Focused', 'Esoteric']
        ]
    ],
    'clan' => [
        'Physical' => [
            'Brujah' => ['Aggressive', 'Quick', 'Vigorous'],
            'Gangrel' => ['Wild', 'Tough', 'Resilient', 'Feral'],
            'Nosferatu' => ['Tough', 'Resilient']
        ],
        'Social' => [
            'Toreador' => ['Charismatic', 'Elegant', 'Alluring', 'Refined'],
            'Ventrue' => ['Commanding', 'Diplomatic', 'Poised', 'Authoritative'],
            'Brujah' => ['Passionate', 'Intense'],
            'Followers of Set' => ['Alluring', 'Seductive', 'Persuasive'],
            'Ravnos' => ['Cunning', 'Charming', 'Deceptive']
        ],
        'Mental' => [
            'Tremere' => ['Studious', 'Analytical', 'Disciplined', 'Methodical'],
            'Malkavian' => ['Perceptive', 'Intuitive', 'Unpredictable', 'Insightful'],
            'Nosferatu' => ['Clever', 'Resourceful', 'Cunning'],
            'Tzimisce' => ['Studious', 'Analytical', 'Patient']
        ],
        'negative' => [
            'Nosferatu' => ['Repulsive', 'Hideous'],
            'Malkavian' => ['Unpredictable', 'Distracted'],
            'Gangrel' => ['Feral', 'Wild']
        ]
    ],
    'nature_demeanor' => [
        'Physical' => [
            'Survivor' => ['Tough', 'Resilient', 'Hardy'],
            'Architect' => ['Precise', 'Steady', 'Coordinated']
        ],
        'Social' => [
            'Bon Vivant' => ['Charismatic', 'Charming', 'Sociable'],
            'Director' => ['Commanding', 'Authoritative', 'Organized'],
            'Confidant' => ['Charismatic', 'Trustworthy', 'Persuasive'],
            'Temptress' => ['Alluring', 'Seductive', 'Charming'],
            'Trickster' => ['Cunning', 'Charming', 'Deceptive'],
            'Visionary' => ['Charismatic', 'Inspiring', 'Persuasive']
        ],
        'Mental' => [
            'Architect' => ['Analytical', 'Methodical', 'Strategic'],
            'Director' => ['Strategic', 'Organized', 'Analytical'],
            'Penitent' => ['Reflective', 'Disciplined', 'Focused'],
            'Visionary' => ['Intuitive', 'Perceptive', 'Insightful'],
            'Survivor' => ['Resourceful', 'Alert', 'Cautious']
        ]
    ]
];

/**
 * Get character data from database
 */
function getCharacterData(mysqli $conn, int $character_id): ?array {
    $sql = "SELECT id, character_name, clan, nature, demeanor, concept, biography, generation
            FROM characters 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Failed to prepare character query: " . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $char = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$char) {
        return null;
    }
    
    // Get abilities
    $abilities_sql = "SELECT ability_name, ability_category, level as ability_level 
                      FROM character_abilities 
                      WHERE character_id = ? 
                      ORDER BY level DESC, ability_name";
    $abilities_stmt = mysqli_prepare($conn, $abilities_sql);
    $abilities = [];
    if ($abilities_stmt) {
        mysqli_stmt_bind_param($abilities_stmt, 'i', $character_id);
        if (!mysqli_stmt_execute($abilities_stmt)) {
            error_log("Failed to execute abilities query: " . mysqli_stmt_error($abilities_stmt));
        } else {
            $abilities_result = mysqli_stmt_get_result($abilities_stmt);
            while ($row = mysqli_fetch_assoc($abilities_result)) {
                $abilities[] = $row;
            }
        }
        mysqli_stmt_close($abilities_stmt);
    } else {
        error_log("Failed to prepare abilities query: " . mysqli_error($conn));
    }
    
    // Get disciplines
    $disciplines_sql = "SELECT discipline_name, level as discipline_level 
                        FROM character_disciplines 
                        WHERE character_id = ? 
                        ORDER BY level DESC, discipline_name";
    $disciplines_stmt = mysqli_prepare($conn, $disciplines_sql);
    $disciplines = [];
    if ($disciplines_stmt) {
        mysqli_stmt_bind_param($disciplines_stmt, 'i', $character_id);
        if (!mysqli_stmt_execute($disciplines_stmt)) {
            error_log("Failed to execute disciplines query: " . mysqli_stmt_error($disciplines_stmt));
        } else {
            $disciplines_result = mysqli_stmt_get_result($disciplines_stmt);
            while ($row = mysqli_fetch_assoc($disciplines_result)) {
                $disciplines[] = $row;
            }
        }
        mysqli_stmt_close($disciplines_stmt);
    } else {
        error_log("Failed to prepare disciplines query: " . mysqli_error($conn));
    }
    
    return [
        'character' => $char,
        'abilities' => $abilities,
        'disciplines' => $disciplines
    ];
}

/**
 * Generate traits based on character data following 7,5,3 rule
 */
function generateTraits(array $char_data, array $trait_mappings): array {
    $char = $char_data['character'];
    $abilities = $char_data['abilities'];
    $disciplines = $char_data['disciplines'];
    
    // Score each category based on abilities and disciplines
    $scores = [
        'Physical' => 0,
        'Social' => 0,
        'Mental' => 0
    ];
    
    // Score from abilities
    foreach ($abilities as $ability) {
        $name = $ability['ability_name'];
        $category = $ability['ability_category'];
        $level = (int)$ability['ability_level'];
        
        $trait_category = null;
        if ($category === 'Physical' || in_array($name, ['Athletics', 'Brawl', 'Melee', 'Stealth', 'Survival', 'Drive'], true)) {
            $trait_category = 'Physical';
        } elseif ($category === 'Social' || in_array($name, ['Expression', 'Performance', 'Subterfuge', 'Leadership', 'Etiquette', 'Empathy', 'Intimidation', 'Streetwise'], true)) {
            $trait_category = 'Social';
        } elseif ($category === 'Mental' || in_array($name, ['Academics', 'Investigation', 'Occult', 'Science', 'Computer', 'Technology', 'Security', 'Crafts', 'Finance', 'Politics'], true)) {
            $trait_category = 'Mental';
        }
        
        if ($trait_category) {
            $scores[$trait_category] += $level;
        }
    }
    
    // Score from disciplines
    foreach ($disciplines as $discipline) {
        $name = $discipline['discipline_name'];
        $level = (int)$discipline['discipline_level'];
        
        // Physical disciplines
        if (in_array($name, ['Celerity', 'Potence', 'Fortitude', 'Protean'], true)) {
            $scores['Physical'] += $level * 2;
        }
        // Social disciplines
        if (in_array($name, ['Presence', 'Dominate', 'Serpentis'], true)) {
            $scores['Social'] += $level * 2;
        }
        // Mental disciplines
        if (in_array($name, ['Auspex', 'Obfuscate', 'Thaumaturgy', 'Necromancy'], true)) {
            $scores['Mental'] += $level * 2;
        }
    }
    
    // Determine distribution: highest gets 7, middle gets 5, lowest gets 3
    arsort($scores);
    $categories = array_keys($scores);
    $distribution = [
        $categories[0] => 7,  // Primary
        $categories[1] => 5,  // Secondary
        $categories[2] => 3   // Tertiary
    ];
    
    // Collect trait candidates for each category
    $trait_candidates = [
        'Physical' => [],
        'Social' => [],
        'Mental' => []
    ];
    
    // Collect from abilities
    foreach ($abilities as $ability) {
        $name = $ability['ability_name'];
        $category = $ability['ability_category'];
        $level = (int)$ability['ability_level'];
        
        if ($level >= 2) {
            $trait_category = null;
            if ($category === 'Physical' || in_array($name, ['Athletics', 'Brawl', 'Melee', 'Stealth', 'Survival', 'Drive'], true)) {
                $trait_category = 'Physical';
            } elseif ($category === 'Social' || in_array($name, ['Expression', 'Performance', 'Subterfuge', 'Leadership', 'Etiquette', 'Empathy', 'Intimidation', 'Streetwise'], true)) {
                $trait_category = 'Social';
            } elseif ($category === 'Mental' || in_array($name, ['Academics', 'Investigation', 'Occult', 'Science', 'Computer', 'Technology', 'Security', 'Crafts', 'Finance', 'Politics'], true)) {
                $trait_category = 'Mental';
            }
            
            if ($trait_category && isset($trait_mappings['ability'][$trait_category][$name])) {
                $candidates = $trait_mappings['ability'][$trait_category][$name];
                $count = min($level, count($candidates));
                foreach (array_slice($candidates, 0, $count) as $trait) {
                    if (!in_array($trait, $trait_candidates[$trait_category], true)) {
                        $trait_candidates[$trait_category][] = $trait;
                    }
                }
            }
        }
    }
    
    // Collect from disciplines
    foreach ($disciplines as $discipline) {
        $name = $discipline['discipline_name'];
        $level = (int)$discipline['discipline_level'];
        
        if ($level >= 1) {
            foreach (['Physical', 'Social', 'Mental'] as $cat) {
                if (isset($trait_mappings['discipline'][$cat][$name])) {
                    $candidates = $trait_mappings['discipline'][$cat][$name];
                    $count = min($level, count($candidates));
                    foreach (array_slice($candidates, 0, $count) as $trait) {
                        if (!in_array($trait, $trait_candidates[$cat], true)) {
                            $trait_candidates[$cat][] = $trait;
                        }
                    }
                }
            }
        }
    }
    
    // Collect from clan
    $clan = $char['clan'] ?? '';
    if ($clan !== '') {
        foreach (['Physical', 'Social', 'Mental'] as $cat) {
            if (isset($trait_mappings['clan'][$cat][$clan])) {
                $candidates = $trait_mappings['clan'][$cat][$clan];
                foreach ($candidates as $trait) {
                    if (!in_array($trait, $trait_candidates[$cat], true)) {
                        $trait_candidates[$cat][] = $trait;
                    }
                }
            }
        }
    }
    
    // Collect from nature/demeanor
    $nature = $char['nature'] ?? '';
    $demeanor = $char['demeanor'] ?? '';
    
    foreach ([$nature, $demeanor] as $archetype) {
        if ($archetype !== '') {
            foreach (['Physical', 'Social', 'Mental'] as $cat) {
                if (isset($trait_mappings['nature_demeanor'][$cat][$archetype])) {
                    $candidates = $trait_mappings['nature_demeanor'][$cat][$archetype];
                    foreach ($candidates as $trait) {
                        if (!in_array($trait, $trait_candidates[$cat], true)) {
                            $trait_candidates[$cat][] = $trait;
                        }
                    }
                }
            }
        }
    }
    
    // Add generic fallback traits
    $generic_traits = [
        'Physical' => ['Athletic', 'Fit', 'Healthy', 'Strong', 'Quick', 'Tough', 'Resilient', 'Agile', 'Nimble', 'Vigorous'],
        'Social' => ['Personable', 'Sociable', 'Friendly', 'Charismatic', 'Charming', 'Persuasive', 'Eloquent', 'Diplomatic', 'Poised', 'Elegant'],
        'Mental' => ['Alert', 'Focused', 'Aware', 'Intelligent', 'Observant', 'Perceptive', 'Analytical', 'Clever', 'Resourceful', 'Strategic']
    ];
    
    // Generate final traits following 7,5,3 distribution
    $traits = [
        'Physical' => [],
        'Social' => [],
        'Mental' => []
    ];
    
    foreach ($distribution as $category => $count) {
        // Use candidates first, then fill with generics
        $selected = array_slice($trait_candidates[$category], 0, $count);
        
        // Fill remaining slots with generics if needed
        while (count($selected) < $count) {
            foreach ($generic_traits[$category] as $generic) {
                if (!in_array($generic, $selected, true) && count($selected) < $count) {
                    $selected[] = $generic;
                }
            }
            // If still not enough, break to avoid infinite loop
            if (count($selected) < $count) {
                break;
            }
        }
        
        $traits[$category] = array_slice($selected, 0, $count);
    }
    
    // Generate negative traits (0-2 based on clan)
    $negative_traits = [
        'Physical' => [],
        'Social' => [],
        'Mental' => []
    ];
    
    if ($clan !== '' && isset($trait_mappings['clan']['negative'][$clan])) {
        $candidates = $trait_mappings['clan']['negative'][$clan];
        $neg_cat = $clan === 'Nosferatu' ? 'Social' : ($clan === 'Malkavian' ? 'Mental' : 'Physical');
        foreach (array_slice($candidates, 0, 1) as $trait) {
            // Make sure negative trait doesn't duplicate a positive trait
            if (!in_array($trait, $traits[$neg_cat], true)) {
                $negative_traits[$neg_cat][] = $trait;
            }
        }
    }
    
    return [
        'positive' => $traits,
        'negative' => $negative_traits
    ];
}

/**
 * Insert traits into database
 */
function insertTraits(mysqli $conn, int $character_id, array $traits_data, bool $dry_run): int {
    $inserted = 0;
    
    // Validate traits_data structure
    if (!isset($traits_data['positive']) || !is_array($traits_data['positive'])) {
        error_log("Invalid traits_data structure: missing or invalid 'positive' key");
        return 0;
    }
    if (!isset($traits_data['negative']) || !is_array($traits_data['negative'])) {
        error_log("Invalid traits_data structure: missing or invalid 'negative' key");
        return 0;
    }
    
    if ($dry_run) {
        // Count what would be inserted
        foreach ($traits_data['positive'] as $category => $trait_list) {
            if (is_array($trait_list)) {
                $inserted += count($trait_list);
            }
        }
        foreach ($traits_data['negative'] as $category => $trait_list) {
            if (is_array($trait_list)) {
                $inserted += count($trait_list);
            }
        }
        return $inserted;
    }
    
    $insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, ?)";
    
    // Insert positive traits - using simple pattern that works
    if (isset($traits_data['positive']) && is_array($traits_data['positive'])) {
        foreach ($traits_data['positive'] as $category => $trait_list) {
            if (!is_array($trait_list)) continue;
            foreach ($trait_list as $trait) {
                if (empty($trait) || !is_string($trait)) continue;
                
                $stmt = mysqli_prepare($conn, $insert_sql);
                if ($stmt) {
                    $trait_type = 'positive';
                    mysqli_stmt_bind_param($stmt, 'isss', $character_id, $trait, $category, $trait_type);
                    if (mysqli_stmt_execute($stmt)) {
                        $inserted++;
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    
    // Insert negative traits - using simple pattern that works
    if (isset($traits_data['negative']) && is_array($traits_data['negative'])) {
        foreach ($traits_data['negative'] as $category => $trait_list) {
            if (!is_array($trait_list)) continue;
            foreach ($trait_list as $trait) {
                if (empty($trait) || !is_string($trait)) continue;
                
                $stmt = mysqli_prepare($conn, $insert_sql);
                if ($stmt) {
                    $trait_type = 'negative';
                    mysqli_stmt_bind_param($stmt, 'isss', $character_id, $trait, $category, $trait_type);
                    if (mysqli_stmt_execute($stmt)) {
                        $inserted++;
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    
    return $inserted;
}

// Main execution
echo "=== Character Traits Generation Script ===\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN (no database writes)" : "LIVE") . "\n";
if ($options['character-id']) {
    echo "Character ID: {$options['character-id']}\n";
}
echo "\n";

// Step 1: Find characters missing traits
echo "Step 1: Finding characters missing traits...\n";

if ($options['character-id']) {
    $query = "SELECT c.id, c.character_name 
              FROM characters c
              LEFT JOIN character_traits ct ON c.id = ct.character_id
              WHERE c.id = ?
              GROUP BY c.id, c.character_name
              HAVING COUNT(ct.id) = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $options['character-id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT c.id, c.character_name 
              FROM characters c
              LEFT JOIN character_traits ct ON c.id = ct.character_id
              GROUP BY c.id, c.character_name
              HAVING COUNT(ct.id) = 0
              ORDER BY c.id";
    $result = mysqli_query($conn, $query);
}

if (!$result) {
    die("ERROR: Failed to query characters: " . mysqli_error($conn) . "\n");
}

$missing_characters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $missing_characters[] = $row;
    $stats['missing_traits']++;
}

echo "Found {$stats['missing_traits']} characters missing traits\n\n";

if (count($missing_characters) === 0) {
    echo "No characters missing traits. Done.\n";
    exit(0);
}

// Step 2: Generate traits for each character
echo "Step 2: Generating traits...\n";

foreach ($missing_characters as $char) {
    $character_id = (int)$char['id'];
    $character_name = $char['character_name'];
    
    if ($options['verbose']) {
        echo "  Processing: {$character_name} (ID: {$character_id})...\n";
    }
    
    try {
        $char_data = getCharacterData($conn, $character_id);
        if (!$char_data) {
            $stats['errors']++;
            if ($options['verbose']) {
                echo "    ✗ Failed to load character data: " . mysqli_error($conn) . "\n";
            }
            continue;
        }
        
        // Check for errors in character data retrieval
        if (mysqli_error($conn)) {
            $stats['errors']++;
            if ($options['verbose']) {
                echo "    ✗ Database error: " . mysqli_error($conn) . "\n";
            }
            continue;
        }
        
        $traits_data = generateTraits($char_data, $trait_mappings);
    } catch (Exception $e) {
        $stats['errors']++;
        if ($options['verbose']) {
            echo "    ✗ Error generating traits: " . $e->getMessage() . "\n";
        }
        error_log("Trait generation error for character {$character_id}: " . $e->getMessage());
        continue;
    }
    
    $total_traits = 0;
    foreach ($traits_data['positive'] as $category => $traits) {
        $total_traits += count($traits);
    }
    foreach ($traits_data['negative'] as $category => $traits) {
        $total_traits += count($traits);
    }
    
    if ($total_traits === 0) {
        if ($options['verbose']) {
            echo "    ✗ No traits generated\n";
        }
        continue;
    }
    
    if ($options['verbose']) {
        echo "    Calling insertTraits...\n";
        echo "    Traits data structure: " . json_encode(array_keys($traits_data)) . "\n";
        if (isset($traits_data['positive'])) {
            echo "    Positive traits categories: " . implode(', ', array_keys($traits_data['positive'])) . "\n";
        }
        flush();
    }
    
    // Use insertTraits function for both dry-run and live
    try {
        $inserted = insertTraits($conn, $character_id, $traits_data, $options['dry-run']);
    } catch (Throwable $e) {
        $stats['errors']++;
        if ($options['verbose']) {
            echo "    ✗ Fatal error in insertTraits: " . $e->getMessage() . "\n";
            echo "    Stack trace: " . $e->getTraceAsString() . "\n";
        }
        error_log("Fatal error inserting traits for character {$character_id}: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        continue;
    }
    
    if ($options['verbose']) {
        echo "    Inserted: {$inserted} traits\n";
        flush();
    }
    
    if ($inserted > 0) {
        $stats['traits_generated'] += $inserted;
        $stats['characters_updated']++;
        
        $generated_characters[] = [
            'id' => $character_id,
            'character_name' => $character_name,
            'traits_generated' => $inserted,
            'positive' => $traits_data['positive'],
            'negative' => $traits_data['negative'],
            'generated_at' => date('c')
        ];
        
        $log_entry = sprintf(
            "[%s] Character ID %d (%s): %s %d traits\n",
            date('Y-m-d H:i:s'),
            $character_id,
            $character_name,
            $options['dry-run'] ? 'Would generate' : 'Generated',
            $inserted
        );
        $update_log[] = $log_entry;
        
        if ($options['verbose']) {
            echo "    ✓ Generated {$inserted} traits\n";
            foreach ($traits_data['positive'] as $cat => $traits) {
                if (count($traits) > 0) {
                    echo "      {$cat}: " . implode(', ', $traits) . "\n";
                }
            }
            foreach ($traits_data['negative'] as $cat => $traits) {
                if (count($traits) > 0) {
                    echo "      {$cat} (negative): " . implode(', ', $traits) . "\n";
                }
            }
        }
    }
}

echo "\n";

// Step 3: Generate reports
echo "Step 3: Generating reports...\n";

$report = [
    'generated_at' => date('c'),
    'mode' => $options['dry-run'] ? 'dry-run' : 'live',
    'statistics' => $stats,
    'characters' => $generated_characters
];

$report_path = $output_dir . "/traits_generation_report.json";
file_put_contents($report_path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  Generated: traits_generation_report.json\n";

$log_path = $output_dir . "/traits_generation.log";
$log_content = implode('', $update_log);
if ($log_content !== '') {
    file_put_contents($log_path, $log_content, FILE_APPEND);
    echo "  Generated: traits_generation.log\n";
} else {
    file_put_contents($log_path, "# Character Traits Generation Log\n# Generated: " . date('c') . "\n\n");
    echo "  Generated: traits_generation.log (empty)\n";
}

echo "\n";

// Step 4: Final summary
echo "=== Character Traits Generation Summary ===\n";
echo "Characters missing traits: {$stats['missing_traits']}\n";
echo "Traits generated: {$stats['traits_generated']}\n";
echo "Characters updated: {$stats['characters_updated']}\n";
echo "Errors: {$stats['errors']}\n";
echo "\n";
echo "Reports generated:\n";
echo "- tools/repeatable/traits_generation_report.json\n";
echo "- tools/repeatable/traits_generation.log\n";
echo "\n";

if ($options['dry-run']) {
    echo "NOTE: This was a DRY RUN. No database changes were made.\n";
}

echo "Done.\n";

