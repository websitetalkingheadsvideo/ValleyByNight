<?php
/**
 * Get User Characters API
 * Returns list of characters for the logged-in user
 * If user is admin, also includes NPCs
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/verify_role.php';

$response = ['success' => false, 'characters' => [], 'error' => ''];

try {
    $user_id = $_SESSION['user_id'];
    $user_role = verifyUserRole($conn, $user_id);
    $is_admin = isAdminUser($user_role);
    
    // Build query - include NPCs if user is admin
    if ($is_admin) {
        $query = "SELECT id, character_name, clan, player_name, status, generation, concept, nature, demeanor
                  FROM characters 
                  WHERE user_id = ? OR player_name = 'NPC'
                  ORDER BY player_name DESC, character_name ASC";
    } else {
        $query = "SELECT id, character_name, clan, player_name, status, generation, concept, nature, demeanor
                  FROM characters 
                  WHERE user_id = ? 
                  ORDER BY character_name ASC";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($conn));
    }
    
    if ($is_admin) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $characters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $is_npc = ($row['player_name'] === 'NPC');
        $characters[] = [
            'id' => $row['id'],
            'character_name' => $row['character_name'],
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
mysqli_close($conn);
?>

