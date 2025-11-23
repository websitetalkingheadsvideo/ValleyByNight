<?php
/**
 * Equipment Assignment API
 * Handles GET (fetch assignments), POST (add assignment), DELETE (remove assignment)
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all characters assigned to a specific equipment
        $equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;
        
        if ($equipment_id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        
        $query = "SELECT character_id FROM character_equipment WHERE item_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $equipment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $character_ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $character_ids[] = intval($row['character_id']);
        }
        
        echo json_encode([
            'success' => true,
            'character_ids' => $character_ids
        ]);
        
    } elseif ($method === 'POST') {
        // Add equipment to character
        $input = json_decode(file_get_contents('php://input'), true);
        
        $equipment_id = intval($input['equipment_id'] ?? 0);
        $character_id = intval($input['character_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if ($equipment_id <= 0 || $character_id <= 0) {
            throw new Exception('Invalid equipment or character ID');
        }
        
        // Check if assignment already exists
        $check_query = "SELECT id, quantity FROM character_equipment WHERE item_id = ? AND character_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'ii', $equipment_id, $character_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $existing = mysqli_fetch_assoc($check_result);
        
        if ($existing) {
            // Update quantity
            $update_query = "UPDATE character_equipment SET quantity = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            $new_quantity = intval($existing['quantity']) + $quantity;
            mysqli_stmt_bind_param($update_stmt, 'ii', $new_quantity, $existing['id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Equipment quantity updated'
                ]);
            } else {
                throw new Exception('Failed to update assignment: ' . mysqli_error($conn));
            }
        } else {
            // Create new assignment
            $insert_query = "INSERT INTO character_equipment (character_id, item_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'iii', $character_id, $equipment_id, $quantity);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Equipment assigned to character'
                ]);
            } else {
                throw new Exception('Failed to assign equipment: ' . mysqli_error($conn));
            }
        }
        
    } elseif ($method === 'DELETE') {
        // Remove equipment from character
        $input = json_decode(file_get_contents('php://input'), true);
        
        $equipment_id = intval($input['equipment_id'] ?? 0);
        $character_id = intval($input['character_id'] ?? 0);
        
        if ($equipment_id <= 0 || $character_id <= 0) {
            throw new Exception('Invalid equipment or character ID');
        }
        
        $query = "DELETE FROM character_equipment WHERE item_id = ? AND character_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $equipment_id, $character_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Equipment removed from character'
            ]);
        } else {
            throw new Exception('Failed to remove assignment: ' . mysqli_error($conn));
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

