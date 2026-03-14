<?php
/**
 * Add Equipment to Character API (Supabase)
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$input = json_decode((string) file_get_contents('php://input'), true) ?? [];
$item_id = (int) ($input['item_id'] ?? 0);
$character_id = (int) ($input['character_id'] ?? 0);
$quantity = (int) ($input['quantity'] ?? 1);

if ($item_id <= 0 || $character_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item_id or character_id']);
    exit;
}

try {
    $existing = supabase_table_get('character_equipment', [
        'select' => 'id,quantity',
        'item_id' => 'eq.' . $item_id,
        'character_id' => 'eq.' . $character_id,
        'limit' => '1'
    ]);
    if (!empty($existing)) {
        $row = $existing[0];
        $new_quantity = (int) ($row['quantity'] ?? 0) + $quantity;
        supabase_rest_request('PATCH', '/rest/v1/character_equipment', ['id' => 'eq.' . $row['id']], ['quantity' => $new_quantity], ['Prefer: return=minimal']);
        echo json_encode(['success' => true, 'message' => 'Equipment quantity updated', 'quantity' => $new_quantity]);
    } else {
        supabase_rest_request('POST', '/rest/v1/character_equipment', [], [
            'character_id' => $character_id,
            'item_id' => $item_id,
            'quantity' => $quantity
        ], ['Prefer: return=minimal']);
        echo json_encode(['success' => true, 'message' => 'Equipment assigned successfully', 'quantity' => $quantity]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
