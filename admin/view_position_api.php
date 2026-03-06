<?php
/**
 * View Position API
 * Returns complete position data for modal display
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

$position_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($position_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
    exit();
}

try {
    // Get main position data
    $positionRows = supabase_table_get('camarilla_positions', [
        'select' => '*',
        'position_id' => 'eq.' . $position_id,
        'limit' => '1'
    ]);
    $position = !empty($positionRows) ? $positionRows[0] : null;
    
    if (!$position) {
        echo json_encode(['success' => false, 'message' => 'Position not found']);
        exit();
    }
    
    // Get current holders (supports multiple holders like Talon)
    $default_night = CAMARILLA_DEFAULT_NIGHT;
    $current_holders = get_all_current_holders_for_position($position_id, $default_night);
    $current_holder = !empty($current_holders) ? $current_holders[0] : null; // First holder for backward compatibility
    
    // Get position history
    $history = get_position_history($position_id);
    
    // Map database fields to expected format
    $response = [
        'success' => true,
        'position' => [
            'position_id' => $position['position_id'],
            'name' => $position['name'],
            'category' => $position['category'],
            'description' => $position['description'] ?? null,
            'importance_rank' => $position['importance_rank'] ?? null
        ],
        'current_holders' => $current_holders, // Array of all holders
        'current_holder' => $current_holder, // First holder for backward compatibility
        'history' => $history
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

