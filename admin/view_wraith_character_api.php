<?php
/**
 * View Wraith Character API
 * Returns complete Wraith character data for modal display
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
    $rows = supabase_table_get('wraith_characters', ['select' => '*', 'id' => 'eq.' . $character_id, 'limit' => '1']);
    $char = !empty($rows) ? $rows[0] : null;

    if (!$char) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    // Decode JSON fields
    $jsonFields = [
        'timeline', 'personality', 'traits', 'negativeTraits', 'abilities', 
        'specializations', 'fetters', 'passions', 'arcanoi', 'backgrounds', 
        'backgroundDetails', 'pathos_corpus', 'shadow', 'harrowing', 
        'merits_flaws', 'status_details', 'relationships', 'artifacts', 'custom_data'
    ];
    
    foreach ($jsonFields as $field) {
        if (isset($char[$field]) && !empty($char[$field])) {
            $decoded = json_decode($char[$field], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $char[$field] = $decoded;
            }
        } else {
            $char[$field] = null;
        }
    }
    
    // Map database fields to expected format
    $response = [
        'success' => true,
        'character' => [
            'id' => $char['id'],
            'user_id' => $char['user_id'],
            'character_name' => $char['character_name'],
            'shadow_name' => $char['shadow_name'],
            'player_name' => $char['player_name'],
            'chronicle' => $char['chronicle'],
            'nature' => $char['nature'],
            'demeanor' => $char['demeanor'],
            'concept' => $char['concept'],
            'circle' => $char['circle'],
            'guild' => $char['guild'],
            'legion_at_death' => $char['legion_at_death'],
            'date_of_death' => $char['date_of_death'],
            'cause_of_death' => $char['cause_of_death'],
            'pc' => $char['pc'],
            'appearance' => $char['appearance'],
            'ghostly_appearance' => $char['ghostly_appearance'],
            'biography' => $char['biography'],
            'notes' => $char['notes'],
            'equipment' => $char['equipment'],
            'character_image' => $char['character_image'],
            'status' => $char['status'],
            'willpower_permanent' => $char['willpower_permanent'],
            'willpower_current' => $char['willpower_current'],
            'experience_total' => $char['experience_total'] ?? 0,
            'spent_xp' => $char['spent_xp'] ?? 0,
            'experience_unspent' => $char['experience_unspent'] ?? 0,
            'shadow_xp_total' => $char['shadow_xp_total'] ?? 0,
            'shadow_xp_spent' => $char['shadow_xp_spent'] ?? 0,
            'shadow_xp_available' => $char['shadow_xp_available'] ?? 0,
            'actingNotes' => $char['actingNotes'],
            'agentNotes' => $char['agentNotes'],
            'health_status' => $char['health_status'],
            'custom_data' => $char['custom_data'],
            'created_at' => $char['created_at'],
            'updated_at' => $char['updated_at'],
            // JSON fields
            'timeline' => $char['timeline'],
            'personality' => $char['personality'],
            'traits' => $char['traits'],
            'negativeTraits' => $char['negativeTraits'],
            'abilities' => $char['abilities'],
            'specializations' => $char['specializations'],
            'fetters' => $char['fetters'],
            'passions' => $char['passions'],
            'arcanoi' => $char['arcanoi'],
            'backgrounds' => $char['backgrounds'],
            'backgroundDetails' => $char['backgroundDetails'],
            'pathos_corpus' => $char['pathos_corpus'],
            'shadow' => $char['shadow'],
            'harrowing' => $char['harrowing'],
            'merits_flaws' => $char['merits_flaws'],
            'status_details' => $char['status_details'],
            'relationships' => $char['relationships'],
            'artifacts' => $char['artifacts']
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading character: ' . $e->getMessage()
    ]);
}

