<?php
/**
 * Equipment API
 * Returns all equipment items (from items table)
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

try {
    $items = supabase_table_get('items', [
        'select' => 'id,name,type,category,damage,range,rarity,price,description,requirements,image,notes,created_at',
        'order' => 'id.desc'
    ]);
    foreach ($items as &$row) {
        if (!empty($row['requirements'])) {
            $decoded = json_decode($row['requirements'], true);
            $row['requirements'] = ($decoded !== null && json_last_error() === JSON_ERROR_NONE) ? $decoded : $row['requirements'];
        }
    }
    unset($row);
    echo json_encode([
        'success' => true,
        'items' => $items,
        'equipment' => $items
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

