<?php
/**
 * Equipment CRUD API (Supabase)
 * Handles Create, Read, Update, Delete for equipment (items table)
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
    if ($method === 'GET' && isset($_GET['check_assignments'])) {
        $equipment_id = (int) $_GET['check_assignments'];
        $rows = supabase_table_get('character_equipment', ['select' => 'id', 'item_id' => 'eq.' . $equipment_id]);
        echo json_encode(['success' => true, 'assignment_count' => count($rows)]);
        exit;
    }

    $input = json_decode((string) file_get_contents('php://input'), true) ?? [];

    if ($method === 'POST') {
        $payload = [
            'name' => $input['name'] ?? '',
            'type' => $input['type'] ?? '',
            'category' => $input['category'] ?? '',
            'damage' => $input['damage'] ?? null,
            'range' => $input['range'] ?? null,
            'rarity' => $input['rarity'] ?? '',
            'price' => (int) ($input['price'] ?? 0),
            'description' => $input['description'] ?? '',
            'requirements' => isset($input['requirements']) ? (is_string($input['requirements']) ? $input['requirements'] : json_encode($input['requirements'])) : null,
            'image' => $input['image'] ?? null,
            'notes' => $input['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $res = supabase_rest_request('POST', '/rest/v1/items', [], $payload, ['Prefer: return=representation']);
        if ($res['error'] !== null) {
            throw new Exception('Failed to create equipment: ' . $res['error']);
        }
        $data = is_array($res['data']) && isset($res['data'][0]) ? $res['data'][0] : (is_array($res['data']) ? $res['data'] : null);
        $id = $data['id'] ?? null;
        echo json_encode(['success' => true, 'message' => 'Equipment created successfully', 'id' => $id]);
    } elseif ($method === 'PUT') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        $payload = [
            'name' => $input['name'] ?? '',
            'type' => $input['type'] ?? '',
            'category' => $input['category'] ?? '',
            'damage' => $input['damage'] ?? null,
            'range' => $input['range'] ?? null,
            'rarity' => $input['rarity'] ?? '',
            'price' => (int) ($input['price'] ?? 0),
            'description' => $input['description'] ?? '',
            'requirements' => isset($input['requirements']) ? (is_string($input['requirements']) ? $input['requirements'] : json_encode($input['requirements'])) : null,
            'image' => $input['image'] ?? null,
            'notes' => $input['notes'] ?? null,
        ];
        $res = supabase_rest_request('PATCH', '/rest/v1/items', ['id' => 'eq.' . $id], $payload, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            throw new Exception('Failed to update equipment: ' . $res['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Equipment updated successfully']);
    } elseif ($method === 'DELETE') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        $assignRows = supabase_table_get('character_equipment', ['select' => 'id', 'item_id' => 'eq.' . $id]);
        if (count($assignRows) > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete equipment that is assigned to characters']);
            exit;
        }
        $res = supabase_rest_request('DELETE', '/rest/v1/items', ['id' => 'eq.' . $id], null, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            throw new Exception('Failed to delete equipment: ' . $res['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
