<?php
/**
 * Get User Characters API
 * Returns list of characters for the logged-in user
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/connect.php';

$response = ['success' => false, 'characters' => [], 'error' => ''];

try {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT id, character_name, clan, player_name, status, current_state, generation, concept, nature 
              FROM characters 
              WHERE user_id = ? 
              ORDER BY character_name ASC";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $characters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $characters[] = [
            'id' => $row['id'],
            'character_name' => $row['character_name'],
            'clan' => $row['clan'] ?? '',
            'player_name' => $row['player_name'] ?? '',
            'status' => $row['status'] ?? 'active',
            'current_state' => $row['current_state'] ?? 'active',
            'generation' => $row['generation'] ?? '',
            'concept' => $row['concept'] ?? '',
            'nature' => $row['nature'] ?? ''
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

