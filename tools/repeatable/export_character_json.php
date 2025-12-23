<?php
/**
 * Export Character to JSON
 * 
 * Exports a single character from the database to JSON format
 * 
 * Usage:
 *   php tools/repeatable/export_character_json.php --id=42 --name="Rembrandt Jones" [--out=DIR]
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    die("This script must be run from the command line.\n");
}

// Parse command line arguments
$options = [
    'id' => null,
    'name' => null,
    'out' => null
];

foreach ($argv as $arg) {
    if (strpos($arg, '--id=') === 0) {
        $options['id'] = (int)substr($arg, 5);
    } elseif (strpos($arg, '--name=') === 0) {
        $options['name'] = substr($arg, 7);
    } elseif (strpos($arg, '--out=') === 0) {
        $options['out'] = substr($arg, 6);
    }
}

if ($options['id'] === null && $options['name'] === null) {
    die("ERROR: Must specify either --id=ID or --name=\"Character Name\"\n");
}

// Database connection
$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

// Output directory
$output_dir = $options['out'] ?: ($project_root . '/To-Do Lists/characters');
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
    echo "Created directory: {$output_dir}\n";
}

/**
 * Get character by ID or name
 */
function getCharacter(mysqli $conn, ?int $id = null, ?string $name = null): ?array {
    if ($id !== null) {
        $char = db_fetch_one($conn, "SELECT * FROM characters WHERE id = ?", 'i', [$id]);
    } elseif ($name !== null) {
        $char = db_fetch_one($conn, "SELECT * FROM characters WHERE character_name = ?", 's', [$name]);
    } else {
        return null;
    }
    
    return $char;
}

/**
 * Get character traits
 */
function getTraits(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT trait_name, trait_category, trait_type 
         FROM character_traits 
         WHERE character_id = ? AND (trait_type IS NULL OR trait_type = 'positive')
         ORDER BY trait_category, trait_name",
        'i', [$character_id]
    );
    
    $traits = ['Physical' => [], 'Social' => [], 'Mental' => []];
    foreach ($result as $trait) {
        $category = ucfirst(strtolower($trait['trait_category'] ?? 'Physical'));
        if (isset($traits[$category])) {
            $traits[$category][] = $trait['trait_name'];
        }
    }
    
    return $traits;
}

/**
 * Get character negative traits
 */
function getNegativeTraits(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT trait_category, trait_name 
         FROM character_negative_traits 
         WHERE character_id = ?
         ORDER BY trait_category, trait_name",
        'i', [$character_id]
    );
    
    $traits = ['Physical' => [], 'Social' => [], 'Mental' => []];
    foreach ($result as $trait) {
        $category = ucfirst(strtolower($trait['trait_category'] ?? 'Physical'));
        if (isset($traits[$category])) {
            $traits[$category][] = $trait['trait_name'];
        }
    }
    
    return $traits;
}

/**
 * Get character abilities
 */
function getAbilities(mysqli $conn, int $character_id): array {
    $result = @db_fetch_all($conn,
        "SELECT 
            ca.ability_name,
            COALESCE(ca.ability_category, a.category) as ability_category,
            ca.level,
            ca.specialization
         FROM character_abilities ca
         LEFT JOIN abilities a ON ca.ability_name = a.name
         WHERE ca.character_id = ?
         ORDER BY COALESCE(ca.ability_category, a.category), ca.ability_name",
        'i', [$character_id]
    );
    
    if ($result === false) {
        $result = db_fetch_all($conn,
            "SELECT 
                ca.ability_name,
                a.category as ability_category,
                ca.level,
                ca.specialization
             FROM character_abilities ca
             LEFT JOIN abilities a ON ca.ability_name = a.name
             WHERE ca.character_id = ?
             ORDER BY a.category, ca.ability_name",
            'i', [$character_id]
        );
    }
    
    $abilities = [];
    $specializations = [];
    
    foreach ($result as $ability) {
        $abilities[] = [
            'name' => $ability['ability_name'],
            'category' => $ability['ability_category'],
            'level' => (int)$ability['level']
        ];
        
        if (!empty($ability['specialization'])) {
            $specializations[$ability['ability_name']] = $ability['specialization'];
        }
    }
    
    return ['abilities' => $abilities, 'specializations' => $specializations];
}

/**
 * Get character disciplines
 */
function getDisciplines(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT discipline_name, level
         FROM character_disciplines
         WHERE character_id = ?
         ORDER BY discipline_name",
        'i', [$character_id]
    );
    
    $disciplines = [];
    foreach ($result as $disc) {
        // Get powers for this discipline
        $powers = db_fetch_all($conn,
            "SELECT power_name, level
             FROM character_discipline_powers
             WHERE character_id = ? AND discipline_name = ?
             ORDER BY level, power_name",
            'is', [$character_id, $disc['discipline_name']]
        );
        
        $disc_data = [
            'name' => $disc['discipline_name'],
            'level' => (int)$disc['level']
        ];
        
        if (!empty($powers)) {
            $disc_data['powers'] = array_map(function($p) {
                return [
                    'level' => (int)$p['level'],
                    'power' => $p['power_name']
                ];
            }, $powers);
        }
        
        $disciplines[] = $disc_data;
    }
    
    return $disciplines;
}

/**
 * Get character backgrounds
 */
function getBackgrounds(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT background_name, level, description
         FROM character_backgrounds
         WHERE character_id = ?
         ORDER BY background_name",
        'i', [$character_id]
    );
    
    $backgrounds = [];
    $backgroundDetails = [];
    
    foreach ($result as $bg) {
        $backgrounds[$bg['background_name']] = (int)$bg['level'];
        if (!empty($bg['description'])) {
            $backgroundDetails[$bg['background_name']] = $bg['description'];
        }
    }
    
    return ['backgrounds' => $backgrounds, 'backgroundDetails' => $backgroundDetails];
}

/**
 * Get character merits/flaws
 */
function getMeritsFlaws(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT name, type, category, cost, description
         FROM character_merits_flaws
         WHERE character_id = ?
         ORDER BY type, category, name",
        'i', [$character_id]
    );
    
    $merits_flaws = [];
    foreach ($result as $mf) {
        $merits_flaws[] = [
            'name' => $mf['name'],
            'type' => $mf['type'],
            'category' => $mf['category'],
            'cost' => (int)$mf['cost'],
            'description' => $mf['description']
        ];
    }
    
    return $merits_flaws;
}

/**
 * Get character status
 */
function getStatus(mysqli $conn, int $character_id): array {
    $char = db_fetch_one($conn, 
        "SELECT experience_total, experience_unspent, blood_pool_current, blood_pool_max, notes
         FROM characters
         WHERE id = ?",
        'i', [$character_id]
    );
    
    if (!$char) {
        return [
            'xp_total' => 0,
            'xp_spent' => 0,
            'xp_available' => 0,
            'blood_pool_current' => null,
            'blood_pool_maximum' => null,
            'notes' => ''
        ];
    }
    
    // Get health levels from character_health table if it exists
    $health_levels = null;
    $health_result = @db_fetch_all($conn,
        "SELECT health_status FROM character_health WHERE character_id = ?",
        'i', [$character_id]
    );
    if ($health_result && !empty($health_result)) {
        $health_levels = count($health_result);
    }
    
    return [
        'sect_status' => '',
        'clan_status' => '',
        'city_status' => '',
        'health_levels' => $health_levels,
        'blood_pool_current' => $char['blood_pool_current'],
        'blood_pool_maximum' => $char['blood_pool_max'],
        'blood_per_turn' => 1,
        'xp_total' => (int)($char['experience_total'] ?? 0),
        'xp_spent' => 0,
        'xp_available' => (int)($char['experience_unspent'] ?? 0),
        'notes' => $char['notes'] ?? ''
    ];
}

/**
 * Get character coteries
 */
function getCoteries(mysqli $conn, int $character_id): array {
    $result = @db_fetch_all($conn,
        "SELECT coterie_name, coterie_type, role, description, notes
         FROM character_coteries
         WHERE character_id = ?
         ORDER BY coterie_name",
        'i', [$character_id]
    );
    
    return $result ?: [];
}

/**
 * Get character relationships
 */
function getRelationships(mysqli $conn, int $character_id): array {
    $result = @db_fetch_all($conn,
        "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
         FROM character_relationships
         WHERE character_id = ?
         ORDER BY relationship_type, related_character_name",
        'i', [$character_id]
    );
    
    return $result ?: [];
}

// Get character
$char = getCharacter($conn, $options['id'], $options['name']);

if (!$char) {
    die("ERROR: Character not found\n");
}

echo "Exporting character: {$char['character_name']} (ID: {$char['id']})\n";

// Get all related data
$traits = getTraits($conn, $char['id']);
$negativeTraits = getNegativeTraits($conn, $char['id']);
$abilities_data = getAbilities($conn, $char['id']);
$disciplines = getDisciplines($conn, $char['id']);
$backgrounds_data = getBackgrounds($conn, $char['id']);
$merits_flaws = getMeritsFlaws($conn, $char['id']);
$status = getStatus($conn, $char['id']);
$coteries = getCoteries($conn, $char['id']);
$relationships = getRelationships($conn, $char['id']);

// Build JSON structure
$json_data = [
    'id' => (int)$char['id'],
    'user_id' => (int)$char['user_id'],
    'character_name' => $char['character_name'],
    'player_name' => $char['player_name'],
    'chronicle' => $char['chronicle'],
    'nature' => $char['nature'],
    'demeanor' => $char['demeanor'],
    'concept' => $char['concept'],
    'clan' => $char['clan'],
    'generation' => $char['generation'] ? (int)$char['generation'] : null,
    'sire' => $char['sire'],
    'pc' => (int)$char['pc'],
    'appearance' => $char['appearance'] ?? '',
    'biography' => $char['biography'] ?? '',
    'notes' => $char['notes'] ?? '',
    'equipment' => $char['equipment'] ?? '',
    'character_image' => $char['character_image'] ?? '',
    'status' => $status,
    'camarilla_status' => $char['camarilla_status'] ?? '',
    'traits' => $traits,
    'negativeTraits' => $negativeTraits,
    'abilities' => $abilities_data['abilities'],
    'specializations' => $abilities_data['specializations'],
    'disciplines' => $disciplines,
    'backgrounds' => $backgrounds_data['backgrounds'],
    'backgroundDetails' => $backgrounds_data['backgroundDetails'],
    'morality' => [
        'path_name' => $char['morality_path'] ?? 'Humanity',
        'path_rating' => $char['path_rating'] ? (int)$char['path_rating'] : null,
        'conscience' => $char['conscience'] ? (int)$char['conscience'] : null,
        'self_control' => $char['self_control'] ? (int)$char['self_control'] : null,
        'courage' => $char['courage'] ? (int)$char['courage'] : null,
        'willpower_permanent' => $char['willpower_permanent'] ? (int)$char['willpower_permanent'] : null,
        'willpower_current' => $char['willpower_current'] ? (int)$char['willpower_current'] : null,
        'humanity' => $char['path_rating'] ? (int)$char['path_rating'] : null
    ],
    'merits_flaws' => $merits_flaws,
    'coteries' => $coteries,
    'relationships' => $relationships,
    'rituals' => [],
    'custom_data' => $char['custom_data'] ? json_decode($char['custom_data'], true) : null,
    'created_at' => $char['created_at'] ?? '',
    'updated_at' => $char['updated_at'] ?? ''
];

// Generate filename
$safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $char['character_name']);
$filename = $safe_name . '_' . $char['id'] . '.json';
$filepath = $output_dir . '/' . $filename;

// Write JSON file
$json = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents($filepath, $json);

echo "Exported to: {$filepath}\n";
echo "File size: " . number_format(filesize($filepath)) . " bytes\n";

$conn->close();
?>

