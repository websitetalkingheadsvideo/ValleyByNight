<?php
/**
 * View Character API
 * Returns complete character data for modal display
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
    // Get main character data
    $char = db_fetch_one($conn, "SELECT * FROM characters WHERE id = ?", 'i', [$character_id]);
    
    if (!$char) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    // Get traits
    $traits = db_fetch_all($conn, 
        "SELECT trait_name, trait_category, trait_level 
         FROM character_traits 
         WHERE character_id = ?", 
        'i', [$character_id]
    );
    
    // Get abilities
    $abilities = db_fetch_all($conn,
        "SELECT ability_name, ability_category, level, specialization
         FROM character_abilities
         WHERE character_id = ?
         ORDER BY ability_category, ability_name",
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
        "SELECT name, type, category, point_value, xp_bonus, description
         FROM character_merits_flaws
         WHERE character_id = ?
         ORDER BY type, name",
        'i', [$character_id]
    );
    
    // Get status
    $status = db_fetch_one($conn,
        "SELECT * FROM character_status WHERE character_id = ?",
        'i', [$character_id]
    );
    
    // Get coteries
    $coteries = db_fetch_all($conn,
        "SELECT coterie_name, coterie_type, role, description, notes
         FROM character_coteries
         WHERE character_id = ?
         ORDER BY coterie_name",
        'i', [$character_id]
    );
    
    // Get relationships
    $relationships = db_fetch_all($conn,
        "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
         FROM character_relationships
         WHERE character_id = ?
         ORDER BY relationship_type, related_character_name",
        'i', [$character_id]
    );
    
    // Map database fields to expected format
    $response = [
        'success' => true,
        'character' => [
            'id' => $char['id'],
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
            'character_image' => $char['character_image'],
            'clan_logo_url' => $char['clan_logo_url'],
            'current_state' => $char['current_state'],
            'camarilla_status' => $char['camarilla_status'],
            'total_xp' => $char['experience_total'] ?? 0,
            'spent_xp' => $char['experience_spent'] ?? 0,
            'custom_data' => $char['custom_data'],
            'created_at' => $char['created_at'],
            'updated_at' => $char['updated_at']
        ],
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds,
        'morality' => $morality,
        'merits_flaws' => $merits_flaws,
        'status' => $status,
        'coteries' => $coteries,
        'relationships' => $relationships
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

