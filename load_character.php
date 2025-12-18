<?php
/**
 * Load Character API (Player-accessible)
 * Wrapper that allows players to load their own characters
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/connect.php';

$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
    // Verify character belongs to user (unless admin)
    $char = db_fetch_one($conn, 
        "SELECT * FROM characters WHERE id = ?", 
        'i', 
        [$character_id]
    );
    
    if (!$char) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    // Check ownership or admin role
    $is_owner = ($char['user_id'] == $_SESSION['user_id']);
    $is_admin = (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'storyteller'));
    
    if (!$is_owner && !$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    
    // Check if character image file actually exists
    $character_image = null;
    if (!empty($char['character_image'])) {
        $image_path = __DIR__ . '/uploads/characters/' . $char['character_image'];
        if (file_exists($image_path)) {
            $character_image = $char['character_image'];
        }
    }
    
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
            'character_image' => $character_image,
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
        'status' => $status
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

