<?php
/**
 * Add Equipment to Character API
 * Wrapper for api_admin_equipment_assignments.php for admin_items.js compatibility
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$item_id = intval($input['item_id'] ?? 0);
$character_id = intval($input['character_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);

if ($item_id <= 0 || $character_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item_id or character_id']);
    exit();
}

try {
    // Check if assignment already exists
    $check_query = "SELECT id, quantity FROM character_equipment WHERE item_id = ? AND character_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, 'ii', $item_id, $character_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($result);
    
    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        $update_query = "UPDATE character_equipment SET quantity = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $new_quantity, $existing['id']);
        mysqli_stmt_execute($stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Equipment quantity updated',
            'quantity' => $new_quantity
        ]);
    } else {
        // Insert new assignment
        $insert_query = "INSERT INTO character_equipment (character_id, item_id, quantity) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, 'iii', $character_id, $item_id, $quantity);
        mysqli_stmt_execute($stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Equipment assigned successfully',
            'quantity' => $quantity
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

