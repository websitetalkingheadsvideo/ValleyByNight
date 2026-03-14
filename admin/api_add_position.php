<?php
/**
 * Add Position API
 * Handles adding new positions to the camarilla_positions table
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input'], JSON_UNESCAPED_UNICODE);
    exit();
}

$position_id = isset($input['position_id']) ? trim($input['position_id']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$category = isset($input['category']) ? trim($input['category']) : '';
$description = isset($input['description']) ? trim($input['description']) : null;
$importance_rank = isset($input['importance_rank']) && $input['importance_rank'] !== '' ? intval($input['importance_rank']) : null;

if (empty($position_id) || empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Position ID, name, and category are required'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Check if position already exists
    $existingRows = supabase_table_get('camarilla_positions', [
        'select' => 'position_id',
        'position_id' => 'eq.' . $position_id,
        'limit' => '1'
    ]);

    if (!empty($existingRows)) {
        echo json_encode(['success' => false, 'error' => 'Position ID already exists'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Insert new position
    $insertResult = supabase_rest_request(
        'POST',
        '/rest/v1/camarilla_positions',
        [],
        [[
            'position_id' => $position_id,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'importance_rank' => $importance_rank
        ]],
        ['Prefer: return=minimal']
    );
    if ($insertResult['error'] !== null) {
        throw new Exception('Failed to insert position: ' . $insertResult['error']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Position added successfully'
    ], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>

