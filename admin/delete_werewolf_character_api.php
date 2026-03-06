<?php
/**
 * Delete Werewolf (Garou) Character API
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$input = json_decode(file_get_contents('php://input'), true);
$character_id = isset($input['character_id']) ? (int)$input['character_id'] : 0;
if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit;
}

$result = supabase_rest_request('DELETE', '/rest/v1/werewolf_characters', ['id' => 'eq.' . $character_id], null, ['Prefer: return=representation']);
if ($result['error'] !== null) {
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}
$deleted = is_array($result['data']) ? count($result['data']) : 0;
echo json_encode($deleted > 0 ? ['success' => true] : ['success' => false, 'message' => 'Not found']);
