<?php
/**
 * Delete Position API
 * Handles deleting positions
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$position_id = isset($input['position_id']) ? trim($input['position_id']) : '';

if (empty($position_id)) {
    echo json_encode(['success' => false, 'message' => 'Position ID is required']);
    exit();
}

try {
    // Check if position has assignments
    $assignmentRows = supabase_table_get('camarilla_position_assignments', [
        'select' => 'position_id',
        'position_id' => 'eq.' . $position_id,
        'limit' => '1'
    ]);

    if (!empty($assignmentRows)) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete position with existing assignments. Please remove all assignments first.'
        ]);
        exit();
    }
    
    // Delete position
    $deleteResult = supabase_rest_request(
        'DELETE',
        '/rest/v1/camarilla_positions',
        ['position_id' => 'eq.' . $position_id],
        null,
        ['Prefer: return=representation']
    );
    if ($deleteResult['error'] !== null) {
        throw new Exception('Failed to delete position: ' . $deleteResult['error']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Position deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

