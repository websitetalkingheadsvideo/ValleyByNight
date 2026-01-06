<?php
/**
 * NPC Character Export Script
 * 
 * Exports NPC characters from MySQL database to JSON files.
 * Database is the source of truth - this script reconstructs complete character JSON
 * from normalized database tables.
 * 
 * Usage:
 *   CLI: php database/export_npcs.php [--dry-run] [--limit=N] [--id=ID] [--out=DIR]
 *   Web: database/export_npcs.php?dry-run=1
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Parse command line arguments
$options = [
    'dry-run' => false,
    'limit' => null,
    'id' => null,
    'out' => null
];

if ($is_cli) {
    // Parse CLI arguments
    $argv = $GLOBALS['argv'] ?? [];
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if ($arg === '--dry-run') {
            $options['dry-run'] = true;
        } elseif (strpos($arg, '--limit=') === 0) {
            $options['limit'] = (int)substr($arg, 8);
        } elseif (strpos($arg, '--id=') === 0) {
            $options['id'] = (int)substr($arg, 5);
        } elseif (strpos($arg, '--out=') === 0) {
            $options['out'] = substr($arg, 6);
        }
    }
} else {
    // Parse web arguments
    header('Content-Type: text/html; charset=utf-8');
    $options['dry-run'] = isset($_GET['dry-run']) && $_GET['dry-run'] == '1';
    $options['limit'] = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $options['id'] = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $options['out'] = isset($_GET['out']) ? $_GET['out'] : null;
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Output directory
$project_root = dirname(__DIR__);
$output_dir = $options['out'] ?: ($project_root . '/reference/Characters/Added to Database');
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Statistics
$stats = [
    'processed' => 0,
    'exported' => 0,
    'skipped' => 0,
    'errors' => []
];

/**
 * Get all NPCs from database
 */
function getNPCs(mysqli $conn, ?int $limit = null, ?int $id = null): array {
    $query = "SELECT * FROM characters WHERE (pc = 0 OR player_name = 'NPC')";
    
    if ($id !== null) {
        $query .= " AND id = " . (int)$id;
    }
    
    $query .= " ORDER BY character_name";
    
    if ($limit !== null) {
        $query .= " LIMIT " . (int)$limit;
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Failed to query NPCs: " . mysqli_error($conn));
    }
    
    $npcs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $npcs[] = $row;
    }
    
    return $npcs;
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
    // Try with category first, fallback to without
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
    
    // Fallback if category column doesn't exist
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
        // Get powers for this discipline (if table exists)
        $powers = @db_fetch_all($conn,
            "SELECT power_name, level
             FROM character_discipline_powers
             WHERE character_id = ? AND discipline_name = ?
             ORDER BY level, power_name",
            'is', [$character_id, $disc['discipline_name']]
        );
        
        $discData = [
            'name' => $disc['discipline_name'],
            'level' => (int)$disc['level']
        ];
        
        if ($powers !== false && !empty($powers)) {
            $discData['powers'] = array_map(function($p) {
                return [
                    'level' => (int)$p['level'],
                    'power' => $p['power_name']
                ];
            }, $powers);
        }
        
        $disciplines[] = $discData;
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
 * Get character morality
 */
function getMorality(mysqli $conn, int $character_id): array {
    $result = db_fetch_one($conn,
        "SELECT * FROM character_morality WHERE character_id = ?",
        'i', [$character_id]
    );
    
    if (!$result) {
        // Default morality
        return [
            'path_name' => 'Humanity',
            'path_rating' => 7,
            'conscience' => 3,
            'self_control' => 3,
            'courage' => 3,
            'willpower_permanent' => 5,
            'willpower_current' => 5,
            'humanity' => 7
        ];
    }
    
    return [
        'path_name' => $result['path_name'] ?? 'Humanity',
        'path_rating' => (int)($result['path_rating'] ?? 7),
        'conscience' => (int)($result['conscience'] ?? 3),
        'self_control' => (int)($result['self_control'] ?? 3),
        'courage' => (int)($result['courage'] ?? 3),
        'willpower_permanent' => (int)($result['willpower_permanent'] ?? 5),
        'willpower_current' => (int)($result['willpower_current'] ?? 5),
        'humanity' => (int)($result['path_rating'] ?? 7)
    ];
}

/**
 * Get character merits and flaws
 */
function getMeritsFlaws(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT name, type, category, point_value, point_cost, description
         FROM character_merits_flaws
         WHERE character_id = ?
         ORDER BY type, name",
        'i', [$character_id]
    );
    
    $merits_flaws = [];
    foreach ($result as $mf) {
        $merits_flaws[] = [
            'name' => $mf['name'],
            'type' => ucfirst(strtolower($mf['type'] ?? 'Merit')),
            'category' => $mf['category'] ?? '',
            'cost' => (int)($mf['point_cost'] ?? $mf['point_value'] ?? 0),
            'description' => $mf['description'] ?? ''
        ];
    }
    
    return $merits_flaws;
}

/**
 * Get character coteries
 */
function getCoteries(mysqli $conn, int $character_id): array {
    // Try with notes column first, fallback to without
    $result = @db_fetch_all($conn,
        "SELECT coterie_name, coterie_type, role, description, notes
         FROM character_coteries
         WHERE character_id = ?
         ORDER BY coterie_name",
        'i', [$character_id]
    );
    
    // Fallback if notes column doesn't exist
    if ($result === false) {
        $result = db_fetch_all($conn,
            "SELECT coterie_name, coterie_type, role, description
             FROM character_coteries
             WHERE character_id = ?
             ORDER BY coterie_name",
            'i', [$character_id]
        );
        
        // Add empty notes field
        foreach ($result as &$row) {
            $row['notes'] = '';
        }
        unset($row);
    }
    
    return $result ?: [];
}

/**
 * Get character relationships
 */
function getRelationships(mysqli $conn, int $character_id): array {
    $result = db_fetch_all($conn,
        "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
         FROM character_relationships
         WHERE character_id = ?
         ORDER BY relationship_type, related_character_name",
        'i', [$character_id]
    );
    
    return $result;
}

/**
 * Reconstruct complete character JSON from database
 */
function reconstructCharacter(mysqli $conn, array $char): array {
    $character_id = (int)$char['id'];
    
    // Get all related data
    $traits = getTraits($conn, $character_id);
    $negativeTraits = getNegativeTraits($conn, $character_id);
    $abilityData = getAbilities($conn, $character_id);
    $disciplines = getDisciplines($conn, $character_id);
    $backgroundData = getBackgrounds($conn, $character_id);
    $morality = getMorality($conn, $character_id);
    $merits_flaws = getMeritsFlaws($conn, $character_id);
    $coteries = getCoteries($conn, $character_id);
    $relationships = getRelationships($conn, $character_id);
    
    // Reconstruct JSON structure matching character.json schema
    $json = [
        'id' => $character_id,
        'user_id' => (int)$char['user_id'],
        'character_name' => $char['character_name'] ?? '',
        'player_name' => $char['player_name'] ?? 'NPC',
        'chronicle' => $char['chronicle'] ?? 'Valley by Night',
        'nature' => $char['nature'] ?? '',
        'demeanor' => $char['demeanor'] ?? '',
        'concept' => $char['concept'] ?? '',
        'clan' => $char['clan'] ?? '',
        'generation' => (int)($char['generation'] ?? 13),
        'sire' => $char['sire'] ?? '',
        'pc' => (int)($char['pc'] ?? 0),
        'appearance' => $char['appearance'] ?? '',
        'biography' => $char['biography'] ?? '',
        'notes' => $char['notes'] ?? '',
        'equipment' => $char['equipment'] ?? '',
        'character_image' => $char['character_image'] ?? '',
        'status' => $char['status'] ?? 'active',
        'camarilla_status' => $char['camarilla_status'] ?? 'Unknown',
        'traits' => $traits,
        'negativeTraits' => $negativeTraits,
        'abilities' => $abilityData['abilities'],
        'specializations' => $abilityData['specializations'],
        'disciplines' => $disciplines,
        'backgrounds' => $backgroundData['backgrounds'],
        'backgroundDetails' => $backgroundData['backgroundDetails'],
        'morality' => $morality,
        'merits_flaws' => $merits_flaws,
        'status' => [
            'sect_status' => '',
            'clan_status' => '',
            'city_status' => '',
            'health_levels' => 7,
            'blood_pool_current' => null,
            'blood_pool_maximum' => null,
            'blood_per_turn' => 1,
            'xp_total' => (int)($char['experience_total'] ?? 0),
            'xp_spent' => (int)($char['experience_spent'] ?? 0),
            'xp_available' => (int)($char['experience_total'] ?? 0) - (int)($char['experience_spent'] ?? 0),
            'notes' => ''
        ],
        'coteries' => $coteries,
        'relationships' => $relationships,
        'rituals' => [],
        'custom_data' => $char['custom_data'] ? json_decode($char['custom_data'], true) : null
    ];
    
    // Add timestamps if available
    if (isset($char['created_at'])) {
        $json['created_at'] = $char['created_at'];
    }
    if (isset($char['updated_at'])) {
        $json['updated_at'] = $char['updated_at'];
    }
    
    return $json;
}

/**
 * Generate filename from character name
 */
function generateFilename(string $character_name, int $id): string {
    // Create slug from character name
    $slug = preg_replace('/[^a-z0-9]+/i', '_', $character_name);
    $slug = trim($slug, '_');
    $slug = strtolower($slug);
    
    return "npc__{$slug}__{$id}.json";
}

/**
 * Export single character
 */
function exportCharacter(mysqli $conn, array $char, string $output_dir, bool $dry_run, array &$stats): bool {
    try {
        $character_name = $char['character_name'] ?? '';
        if (empty($character_name)) {
            $stats['skipped']++;
            $stats['errors'][] = "Character ID {$char['id']}: Missing character_name";
            return false;
        }
        
        // Reconstruct JSON
        $json = reconstructCharacter($conn, $char);
        
        // Validate JSON structure
        if (empty($json['character_name'])) {
            $stats['skipped']++;
            $stats['errors'][] = "Character ID {$char['id']}: Invalid character data";
            return false;
        }
        
        // Generate filename
        $filename = generateFilename($character_name, (int)$char['id']);
        $filepath = $output_dir . '/' . $filename;
        
        if (!$dry_run) {
            // Write JSON file
            $json_content = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($filepath, $json_content) === false) {
                throw new Exception("Failed to write file: $filepath");
            }
        }
        
        $stats['exported']++;
        return true;
    } catch (Exception $e) {
        $stats['errors'][] = "Character ID {$char['id']}: " . $e->getMessage();
        $stats['skipped']++;
        return false;
    }
}

// Main execution
if ($is_cli) {
    echo "NPC Character Export Script\n";
    echo "==========================\n\n";
} else {
    echo "<!DOCTYPE html><html><head><title>NPC Export</title></head><body>";
    echo "<h1>NPC Character Export Script</h1>";
    echo "<pre>";
}

if ($options['dry-run']) {
    echo "DRY RUN MODE - No files will be written\n\n";
}

echo "Output directory: $output_dir\n";
echo "Options: " . json_encode($options, JSON_PRETTY_PRINT) . "\n\n";

try {
    // Get NPCs
    $npcs = getNPCs($conn, $options['limit'], $options['id']);
    
    echo "Found " . count($npcs) . " NPC(s) to export.\n\n";
    
    // Export each NPC
    foreach ($npcs as $npc) {
        $stats['processed']++;
        $name = $npc['character_name'] ?? "ID {$npc['id']}";
        echo "Processing: $name... ";
        
        if (exportCharacter($conn, $npc, $output_dir, $options['dry-run'], $stats)) {
            echo "OK\n";
        } else {
            echo "FAILED\n";
        }
    }
    
    // Output summary
    echo "\n";
    echo "=== Export Summary ===\n";
    echo "Processed: {$stats['processed']}\n";
    echo "Exported: {$stats['exported']}\n";
    echo "Skipped: {$stats['skipped']}\n";
    
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    $stats['errors'][] = $e->getMessage();
}

if (!$is_cli) {
    echo "</pre></body></html>";
}

mysqli_close($conn);

