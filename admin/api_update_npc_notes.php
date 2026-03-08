<?php
/**
 * Update NPC Notes API (Supabase)
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$input = json_decode((string) file_get_contents('php://input'), true) ?? [];
$character_id = (int) ($input['character_id'] ?? 0);
$agent_notes = trim((string) ($input['agentNotes'] ?? ''));
$acting_notes = trim((string) ($input['actingNotes'] ?? ''));

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid character ID']);
    exit;
}

try {
    $res = supabase_rest_request('PATCH', '/rest/v1/characters', ['id' => 'eq.' . $character_id], [
        'agent_notes' => $agent_notes,
        'acting_notes' => $acting_notes,
        'updated_at' => date('Y-m-d H:i:s')
    ], ['Prefer: return=minimal']);
    if ($res['error'] !== null) {
        throw new Exception($res['error']);
    }
    echo json_encode(['success' => true, 'message' => 'Notes updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

