<?php
/**
 * Coterie CRUD API
 * Handles Create, Read, Update, Delete operations for character coterie associations (Supabase)
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../includes/supabase_client.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

try {
    if ($method === 'GET') {
        $rows = supabase_table_get('character_coteries', [
            'select' => 'character_id,coterie_name,coterie_type,role,description,notes',
            'order' => 'coterie_name.asc'
        ]);
        $rows = is_array($rows) ? $rows : [];
        $charIds = array_unique(array_column($rows, 'character_id'));
        $charIds = array_filter($charIds);
        $chars = [];
        if (!empty($charIds)) {
            $charList = supabase_table_get('characters', [
                'select' => 'id,character_name,clan,player_name',
                'id' => 'in.(' . implode(',', array_map('intval', $charIds)) . ')'
            ]);
            foreach (is_array($charList) ? $charList : [] as $c) {
                $chars[(int) ($c['id'] ?? 0)] = $c;
            }
        }
        $coteries = [];
        foreach ($rows as $r) {
            $cid = (int) ($r['character_id'] ?? 0);
            $c = $chars[$cid] ?? [];
            $coteries[] = array_merge($r, [
                'character_name' => $c['character_name'] ?? '',
                'clan' => $c['clan'] ?? '',
                'player_name' => $c['player_name'] ?? '',
                'notes' => $r['notes'] ?? ''
            ]);
        }
        usort($coteries, function ($a, $b) {
            $x = strcmp($a['coterie_name'] ?? '', $b['coterie_name'] ?? '');
            return $x !== 0 ? $x : strcmp($a['character_name'] ?? '', $b['character_name'] ?? '');
        });
        echo json_encode(['success' => true, 'data' => $coteries], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        $character_id = (int) ($input['character_id'] ?? 0);
        $coterie_name = trim((string) ($input['coterie_name'] ?? ''));
        $coterie_type = trim((string) ($input['coterie_type'] ?? ''));
        $role = trim((string) ($input['role'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        if ($coterie_name === '') {
            throw new Exception('Coterie name is required');
        }

        $payload = [
            'character_id' => $character_id,
            'coterie_name' => $coterie_name,
            'coterie_type' => $coterie_type,
            'role' => $role,
            'description' => $description,
            'notes' => $notes
        ];
        $result = supabase_rest_request('POST', '/rest/v1/character_coteries', [], $payload);
        if ($result['error'] !== null) {
            throw new Exception('Failed to create coterie association: ' . $result['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Coterie association created successfully'], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $character_id = (int) ($input['character_id'] ?? 0);
        $old_coterie_name = trim((string) ($input['old_coterie_name'] ?? ''));
        $coterie_name = trim((string) ($input['coterie_name'] ?? ''));
        $coterie_type = trim((string) ($input['coterie_type'] ?? ''));
        $role = trim((string) ($input['role'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        if ($old_coterie_name === '') {
            throw new Exception('Old coterie name is required for update');
        }
        if ($coterie_name === '') {
            throw new Exception('Coterie name is required');
        }

        $filter = 'character_id=eq.' . $character_id . '&coterie_name=eq.' . rawurlencode($old_coterie_name);
        $payload = [
            'coterie_name' => $coterie_name,
            'coterie_type' => $coterie_type,
            'role' => $role,
            'description' => $description,
            'notes' => $notes
        ];
        $result = supabase_rest_request('PATCH', '/rest/v1/character_coteries?' . $filter, [], $payload);
        if ($result['error'] !== null) {
            throw new Exception('Failed to update coterie association: ' . $result['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Coterie association updated successfully'], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'DELETE') {
        $character_id = (int) ($input['character_id'] ?? 0);
        $coterie_name = trim((string) ($input['coterie_name'] ?? ''));

        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        if ($coterie_name === '') {
            throw new Exception('Coterie name is required');
        }

        $filter = 'character_id=eq.' . $character_id . '&coterie_name=eq.' . rawurlencode($coterie_name);
        $result = supabase_rest_request('DELETE', '/rest/v1/character_coteries?' . $filter);
        if ($result['error'] !== null) {
            throw new Exception('Failed to delete coterie association: ' . $result['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Coterie association deleted successfully'], JSON_UNESCAPED_UNICODE);

    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
