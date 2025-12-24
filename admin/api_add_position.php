<?php
/**
 * Add Position API
 * Handles adding new positions to the camarilla_positions table
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
    // Check if position already exists
    $check_query = "SELECT position_id FROM camarilla_positions WHERE position_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 's', $position_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_fetch_assoc($check_result)) {
        echo json_encode(['success' => false, 'message' => 'Position ID already exists']);
        exit();
    }
    
    // Insert new position
    $query = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
             VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'ssssi', $position_id, $name, $category, $description, $importance_rank);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Position added successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

