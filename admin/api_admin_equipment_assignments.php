<?php
/**
 * Equipment Assignment API (Supabase)
 * GET: fetch assignments for equipment; POST: add; DELETE: remove
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $equipment_id = isset($_GET['equipment_id']) ? (int) $_GET['equipment_id'] : 0;
        if ($equipment_id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        $rows = supabase_table_get('character_equipment', [
            'select' => 'character_id',
            'item_id' => 'eq.' . $equipment_id
        ]);
        $character_ids = array_map(static function ($r) {
            return (int) $r['character_id'];
        }, $rows);
        echo json_encode(['success' => true, 'character_ids' => $character_ids]);
    } elseif ($method === 'POST') {
        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $equipment_id = (int) ($input['equipment_id'] ?? 0);
        $character_id = (int) ($input['character_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);
        if ($equipment_id <= 0 || $character_id <= 0) {
            throw new Exception('Invalid equipment or character ID');
        }
        $existing = supabase_table_get('character_equipment', [
            'select' => 'id,quantity',
            'item_id' => 'eq.' . $equipment_id,
            'character_id' => 'eq.' . $character_id,
            'limit' => '1'
        ]);
        if (!empty($existing)) {
            $row = $existing[0];
            $new_qty = (int) ($row['quantity'] ?? 0) + $quantity;
            $res = supabase_rest_request('PATCH', '/rest/v1/character_equipment', ['id' => 'eq.' . $row['id']], ['quantity' => $new_qty], ['Prefer: return=minimal']);
            if ($res['error'] !== null) {
                throw new Exception('Failed to update assignment: ' . $res['error']);
            }
            echo json_encode(['success' => true, 'message' => 'Equipment quantity updated']);
        } else {
            $res = supabase_rest_request('POST', '/rest/v1/character_equipment', [], [
                'character_id' => $character_id,
                'item_id' => $equipment_id,
                'quantity' => $quantity
            ], ['Prefer: return=minimal']);
            if ($res['error'] !== null) {
                throw new Exception('Failed to assign equipment: ' . $res['error']);
            }
            echo json_encode(['success' => true, 'message' => 'Equipment assigned to character']);
        }
    } elseif ($method === 'DELETE') {
        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $equipment_id = (int) ($input['equipment_id'] ?? 0);
        $character_id = (int) ($input['character_id'] ?? 0);
        if ($equipment_id <= 0 || $character_id <= 0) {
            throw new Exception('Invalid equipment or character ID');
        }
        $res = supabase_rest_request('DELETE', '/rest/v1/character_equipment', [
            'item_id' => 'eq.' . $equipment_id,
            'character_id' => 'eq.' . $character_id
        ], null, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            throw new Exception('Failed to remove assignment: ' . $res['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Equipment removed from character']);
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
