<?php
/**
 * Items API (GET)
 * Returns all items for admin_items.js compatibility
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/supabase_client.php';

try {
    $rows = supabase_table_get('items', [
        'select' => '*',
        'order' => 'name.asc',
    ]);

    $items = [];
    foreach ($rows as $row) {
        // Parse requirements JSON if present
        if (!empty($row['requirements'])) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

