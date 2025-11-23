<?php
/**
 * Update Position API
 * Handles updating position data
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
$name = isset($input['name']) ? trim($input['name']) : '';
$category = isset($input['category']) ? trim($input['category']) : '';
$description = isset($input['description']) ? trim($input['description']) : null;
$importance_rank = isset($input['importance_rank']) && $input['importance_rank'] !== '' ? intval($input['importance_rank']) : null;

if (empty($position_id) || empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Position ID, name, and category are required']);
    exit();
}

try {
    // Update position
    $query = "UPDATE camarilla_positions 
             SET name = ?, category = ?, description = ?, importance_rank = ?
             WHERE position_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'sssis', $name, $category, $description, $importance_rank, $position_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Position updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

