<?php
/**
 * Get User Characters API
 * Returns list of characters for the logged-in user
 * If user is admin, also includes NPCs
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/supabase_client.php';
require_once __DIR__ . '/includes/verify_role.php';

$response = ['success' => false, 'characters' => [], 'error' => ''];

try {
    $user_id = $_SESSION['user_id'];
    $user_role = verifyUserRole(null, $user_id);
    $is_admin = isAdminUser($user_role);

    $query = [
        'select' => 'id,character_name,clan,player_name,status,generation,concept,nature,demeanor,pc,user_id',
    ];
    if ($is_admin) {
        $query['or'] = '(user_id.eq.' . (string) $user_id . ',player_name.eq.NPC,pc.eq.0)';
        $query['order'] = 'player_name.desc,character_name.asc';
    } else {
        $query['user_id'] = 'eq.' . (string) $user_id;
        $query['order'] = 'character_name.asc';
    }

    $rows = supabase_table_get('characters', $query);

    $characters = [];
    foreach ($rows as $row) {
        $is_npc = ($row['player_name'] === 'NPC') || (isset($row['pc']) && (int) $row['pc'] === 0);
        $characters[] = [
            'id' => $row['id'] ?? null,
            'character_name' => $row['character_name'] ?? '',
            'clan' => $row['clan'] ?? '',
            'player_name' => $row['player_name'] ?? '',
            'is_npc' => $is_npc,
            'status' => $row['status'] ?? 'active',
            'generation' => $row['generation'] ?? '',
            'concept' => $row['concept'] ?? '',
            'nature' => $row['nature'] ?? '',
            'demeanor' => $row['demeanor'] ?? ''
        ];
    }
    
    $response['success'] = true;
    $response['characters'] = $characters;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>

