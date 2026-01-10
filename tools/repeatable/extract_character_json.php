<?php
/**
 * Extract Character Sheet from Database to JSON
 * 
 * Exports a complete character sheet from the database to JSON format
 * Can be run from CLI or web browser
 * 
 * Usage (CLI):
 *   php tools/repeatable/extract_character_json.php --id=88
 *   php tools/repeatable/extract_character_json.php --name="Roland Cross"
 * 
 * Usage (Web):
 *   http://192.168.0.155/tools/repeatable/extract_character_json.php?id=88
 *   http://192.168.0.155/tools/repeatable/extract_character_json.php?name=Roland%20Cross
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI
$is_cli = php_sapi_name() === 'cli';

// Get project root (2 levels up from tools/repeatable)
$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

// Parse parameters
$character_id = null;
$character_name = null;
$output_dir = null;

if ($is_cli) {
    // Parse command line arguments
    foreach ($argv as $arg) {
        if (strpos($arg, '--id=') === 0) {
            $character_id = (int)substr($arg, 5);
        } elseif (strpos($arg, '--name=') === 0) {
            $character_name = substr($arg, 7);
        } elseif (strpos($arg, '--out=') === 0) {
            $output_dir = substr($arg, 6);
        }
    }
} else {
    // Parse GET parameters
    $character_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $character_name = isset($_GET['name']) ? $_GET['name'] : null;
    $output_dir = isset($_GET['out']) ? $_GET['out'] : null;
}

// Validate parameters
if ($character_id === null && $character_name === null) {
    $error_msg = "Error: Must specify either --id=ID or --name=\"Character Name\"";
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => $error_msg]));
    }
}

// Set default output directory
if (!$output_dir) {
    $output_dir = $project_root . '/reference/Characters/Added to Database';
}

// Ensure output directory exists
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

try {
    // Get main character data
    if ($character_id !== null) {
        $char = db_fetch_one($conn, "SELECT * FROM characters WHERE id = ?", 'i', [$character_id]);
    } else {
        $char = db_fetch_one($conn, "SELECT * FROM characters WHERE character_name = ?", 's', [$character_name]);
    }
    
    if (!$char) {
        $error_msg = "Error: Character not found in database.";
        if ($is_cli) {
            die($error_msg . "\n");
        } else {
            header('Content-Type: application/json');
            die(json_encode(['success' => false, 'error' => $error_msg]));
        }
    }
    
    $character_id = $char['id'];
    if ($is_cli) {
        echo "Found character: {$char['character_name']} (ID: $character_id)\n";
    }
    
    // Get traits (positive)
    $traits = db_fetch_all($conn, 
        "SELECT trait_name, trait_category, trait_type, trait_level
         FROM character_traits 
         WHERE character_id = ? AND (trait_type IS NULL OR trait_type = 'positive')", 
        'i', [$character_id]
    );
    
    // Get negative traits
    $negative_traits = db_fetch_all($conn,
        "SELECT trait_name, trait_category, trait_type, trait_level
         FROM character_traits
         WHERE character_id = ? AND trait_type = 'negative'",
        'i', [$character_id]
    );
    
    // Get abilities
    $abilities = db_fetch_all($conn,
        "SELECT 
            ca.ability_name,
            COALESCE(ca.ability_category, a.category) as ability_category,
            ca.level,
            ca.specialization
         FROM character_abilities ca
         LEFT JOIN abilities a ON ca.ability_name COLLATE utf8mb4_unicode_ci = a.name COLLATE utf8mb4_unicode_ci
         WHERE ca.character_id = ?
         ORDER BY COALESCE(ca.ability_category, a.category), ca.ability_name",
        'i', [$character_id]
    );
    
    // Get disciplines with powers
    $disciplines = db_fetch_all($conn,
        "SELECT discipline_name, level, is_custom
         FROM character_disciplines
         WHERE character_id = ?
         ORDER BY discipline_name",
        'i', [$character_id]
    );
    
    // Add is_custom flag (default to false if column doesn't exist)
    foreach ($disciplines as &$disc) {
        if (!isset($disc['is_custom'])) {
            $disc['is_custom'] = false;
        }
    }
    unset($disc);
    
    // Get powers for each discipline
    foreach ($disciplines as &$disc) {
        $powers = db_fetch_all($conn,
            "SELECT power_name, level
             FROM character_discipline_powers
             WHERE character_id = ? AND discipline_name = ?
             ORDER BY level, power_name",
            'is', [$character_id, $disc['discipline_name']]
        );
        $disc['powers'] = $powers;
        $disc['power_count'] = count($powers);
    }
    unset($disc);
    
    // Get backgrounds
    $backgrounds = db_fetch_all($conn,
        "SELECT background_name, level
         FROM character_backgrounds
         WHERE character_id = ?
         ORDER BY background_name",
        'i', [$character_id]
    );
    
    // Get morality
    $morality = db_fetch_one($conn,
        "SELECT * FROM character_morality WHERE character_id = ?",
        'i', [$character_id]
    );
    
    // Get merits & flaws
    $merits_flaws = db_fetch_all($conn,
        "SELECT name, type, category, point_value, point_cost, description, xp_bonus
         FROM character_merits_flaws
         WHERE character_id = ?
         ORDER BY type, name",
        'i', [$character_id]
    );
    
    // Get status
    $status = null;
    $status_result = @db_fetch_one($conn,
        "SELECT * FROM character_status WHERE character_id = ?",
        'i', [$character_id]
    );
    if ($status_result) {
        $status = $status_result;
    } else {
        // Create empty status object if table doesn't exist or no record
        $status = [
            'health_levels' => null,
            'blood_pool_current' => null,
            'blood_pool_maximum' => null,
            'sect_status' => null,
            'clan_status' => null,
            'city_status' => null
        ];
    }
    
    // Get coteries
    $coteries_from_json = @db_fetch_all($conn,
        "SELECT coterie_name, coterie_type, role, description
         FROM character_coteries
         WHERE character_id = ?",
        'i', [$character_id]
    ) ?: [];
    
    // Also check coterie_members table (coterie agent system)
    $hasDescriptionColumn = false;
    $columnCheck = @mysqli_query($conn, "SHOW COLUMNS FROM coteries LIKE 'description'");
    if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
        $hasDescriptionColumn = true;
    }
    if ($columnCheck) mysqli_free_result($columnCheck);
    
    $coteries_from_agent = @db_fetch_all($conn,
        "SELECT c.name as coterie_name, 
                " . ($hasDescriptionColumn ? "COALESCE(NULLIF(c.description, ''), '')" : "''") . " as coterie_type,
                cm.role,
                '' as description
         FROM coterie_members cm
         INNER JOIN coteries c ON cm.coterie_id = c.id
         WHERE cm.character_id = ?",
        'i', [$character_id]
    ) ?: [];
    
    // Merge both sources
    $coterie_map = [];
    foreach ($coteries_from_json as $coterie) {
        $coterie_map[$coterie['coterie_name']] = $coterie;
    }
    foreach ($coteries_from_agent as $member) {
        $coterie_map[$member['coterie_name']] = $member;
    }
    $coteries = array_values($coterie_map);
    
    // Get relationships
    $relationships = db_fetch_all($conn,
        "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
         FROM character_relationships
         WHERE character_id = ?
         ORDER BY relationship_type, related_character_name",
        'i', [$character_id]
    );
    
    // Get ghoul overlay data if this is a ghoul
    $ghoul_overlay = null;
    $is_ghoul = ($char['clan'] && strtolower($char['clan']) === 'ghoul');
    if ($is_ghoul) {
        $ghoul_overlay = @db_fetch_one($conn,
            "SELECT * FROM ghouls WHERE character_id = ?",
            'i', [$character_id]
        );
        
        // Get domitor name if domitor_character_id exists
        if ($ghoul_overlay && !empty($ghoul_overlay['domitor_character_id'])) {
            $domitor = db_fetch_one($conn,
                "SELECT character_name FROM characters WHERE id = ?",
                'i', [(int)$ghoul_overlay['domitor_character_id']]
            );
            if ($domitor) {
                $ghoul_overlay['domitor_name'] = $domitor['character_name'];
            }
        }
    }
    
    // Check if character image file actually exists
    $character_image = null;
    if (!empty($char['character_image'])) {
        $image_path = $project_root . '/uploads/characters/' . $char['character_image'];
        if (file_exists($image_path)) {
            $character_image = $char['character_image'];
        }
    }
    
    // Build complete character data structure
    $character_data = [
        'id' => $char['id'],
        'user_id' => $char['user_id'],
        'character_name' => $char['character_name'],
        'player_name' => $char['player_name'],
        'chronicle' => $char['chronicle'],
        'clan' => $char['clan'],
        'generation' => $char['generation'],
        'nature' => $char['nature'],
        'demeanor' => $char['demeanor'],
        'sire' => $char['sire'],
        'concept' => $char['concept'],
        'biography' => $char['biography'],
        'appearance' => $char['appearance'],
        'notes' => $char['notes'],
        'equipment' => $char['equipment'],
        'character_image' => $character_image,
        'clan_logo_url' => $char['clan_logo_url'] ?? null,
        'status' => $char['status'] ?? 'active',
        'camarilla_status' => $char['camarilla_status'],
        'pc' => $char['pc'],
        'xp_total' => $char['experience_total'] ?? 0,
        'xp_spent' => $char['experience_spent'] ?? 0,
        'xp_available' => ($char['experience_total'] ?? 0) - ($char['experience_spent'] ?? 0),
        'custom_data' => $char['custom_data'] ? json_decode($char['custom_data'], true) : null,
        'created_at' => $char['created_at'],
        'updated_at' => $char['updated_at'],
        'traits' => $traits,
        'negative_traits' => $negative_traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds,
        'morality' => $morality,
        'merits_flaws' => $merits_flaws,
        'status' => $status,
        'coteries' => $coteries,
        'relationships' => $relationships,
        'ghoul_overlay' => $ghoul_overlay
    ];
    
    // Convert to JSON with pretty formatting
    $json_output = json_encode($character_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Generate filename
    $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $char['character_name']);
    $filename = $output_dir . '/' . $safe_name . '.json';
    
    // Save to file
    file_put_contents($filename, $json_output);
    
    if ($is_cli) {
        echo "Character sheet exported successfully to: $filename\n";
        echo "File size: " . number_format(filesize($filename)) . " bytes\n";
        echo "\nCharacter: {$char['character_name']}\n";
        echo "ID: {$char['id']}\n";
        echo "Traits: " . count($traits) . " positive, " . count($negative_traits) . " negative\n";
        echo "Abilities: " . count($abilities) . "\n";
        echo "Disciplines: " . count($disciplines) . "\n";
        echo "Backgrounds: " . count($backgrounds) . "\n";
    } else {
        // Generate relative URL path
        $relative_path = str_replace($project_root, '', $filename);
        $relative_path = str_replace('\\', '/', $relative_path);
        $relative_path = ltrim($relative_path, '/');
        $file_url = 'http://192.168.0.155/' . $relative_path;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Character sheet exported successfully",
            'filename' => $filename,
            'file_size' => filesize($filename),
            'character' => $char['character_name'],
            'character_id' => $char['id'],
            'url' => $file_url
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $error_msg = "Error: " . $e->getMessage();
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => $error_msg]));
    }
}

mysqli_close($conn);
?>
