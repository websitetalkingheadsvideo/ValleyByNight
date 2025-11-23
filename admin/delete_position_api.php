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

require_once __DIR__ . '/../includes/connect.php';

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
    $assignments_query = "SELECT COUNT(*) as count FROM camarilla_position_assignments WHERE position_id = ?";
    $assignments_result = db_fetch_one($conn, $assignments_query, 's', [$position_id]);
    
    if ($assignments_result && $assignments_result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete position with existing assignments. Please remove all assignments first.'
        ]);
        exit();
    }
    
    // Delete position
    $query = "DELETE FROM camarilla_positions WHERE position_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 's', $position_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
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

mysqli_close($conn);
?>

