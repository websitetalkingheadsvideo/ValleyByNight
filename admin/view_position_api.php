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

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

$position_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($position_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
    exit();
}

try {
    // Get main position data
    $position = db_fetch_one($conn, 
        "SELECT * FROM camarilla_positions WHERE position_id = ?", 
        's', 
        [$position_id]
    );
    
    if (!$position) {
        echo json_encode(['success' => false, 'message' => 'Position not found']);
        exit();
    }
    
    // Get current holder
    $default_night = CAMARILLA_DEFAULT_NIGHT;
    $current_holder = get_current_holder_for_position($position_id, $default_night);
    
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
        'current_holder' => $current_holder,
        'history' => $history
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

