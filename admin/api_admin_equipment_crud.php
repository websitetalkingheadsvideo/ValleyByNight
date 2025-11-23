<?php
/**
 * Equipment CRUD API
 * Handles Create, Read, Update, Delete operations for equipment (items table)
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
    // Check assignments (GET with check_assignments parameter)
    if ($method === 'GET' && isset($_GET['check_assignments'])) {
        $equipment_id = intval($_GET['check_assignments']);
        
        $query = "SELECT COUNT(*) as count FROM character_equipment WHERE item_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $equipment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'assignment_count' => intval($row['count'])
        ]);
        exit();
    }
    
    // Handle request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        // Create new equipment
        $name = mysqli_real_escape_string($conn, $input['name'] ?? '');
        $type = mysqli_real_escape_string($conn, $input['type'] ?? '');
        $category = mysqli_real_escape_string($conn, $input['category'] ?? '');
        $damage = mysqli_real_escape_string($conn, $input['damage'] ?? null);
        $range = mysqli_real_escape_string($conn, $input['range'] ?? null);
        $rarity = mysqli_real_escape_string($conn, $input['rarity'] ?? '');
        $price = intval($input['price'] ?? 0);
        $description = mysqli_real_escape_string($conn, $input['description'] ?? '');
        $requirements = isset($input['requirements']) ? json_encode($input['requirements']) : null;
        $image = mysqli_real_escape_string($conn, $input['image'] ?? null);
        $notes = mysqli_real_escape_string($conn, $input['notes'] ?? null);
        
        $query = "INSERT INTO items (name, type, category, damage, `range`, rarity, price, description, requirements, image, notes, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssissss', $name, $type, $category, $damage, $range, $rarity, $price, $description, $requirements, $image, $notes);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Equipment created successfully',
                'id' => mysqli_insert_id($conn)
            ]);
        } else {
            throw new Exception('Failed to create equipment: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'PUT') {
        // Update existing equipment
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        
        $name = mysqli_real_escape_string($conn, $input['name'] ?? '');
        $type = mysqli_real_escape_string($conn, $input['type'] ?? '');
        $category = mysqli_real_escape_string($conn, $input['category'] ?? '');
        $damage = mysqli_real_escape_string($conn, $input['damage'] ?? null);
        $range = mysqli_real_escape_string($conn, $input['range'] ?? null);
        $rarity = mysqli_real_escape_string($conn, $input['rarity'] ?? '');
        $price = intval($input['price'] ?? 0);
        $description = mysqli_real_escape_string($conn, $input['description'] ?? '');
        $requirements = isset($input['requirements']) ? json_encode($input['requirements']) : null;
        $image = mysqli_real_escape_string($conn, $input['image'] ?? null);
        $notes = mysqli_real_escape_string($conn, $input['notes'] ?? null);
        
        $query = "UPDATE items SET 
                  name = ?, type = ?, category = ?, damage = ?, `range` = ?, rarity = ?, price = ?, 
                  description = ?, requirements = ?, image = ?, notes = ?
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssissssi', $name, $type, $category, $damage, $range, $rarity, $price, $description, $requirements, $image, $notes, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Equipment updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update equipment: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'DELETE') {
        // Delete equipment
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid equipment ID');
        }
        
        // Check if equipment is assigned to characters
        $check_query = "SELECT COUNT(*) as count FROM character_equipment WHERE item_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'i', $id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if (intval($check_row['count']) > 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot delete equipment that is assigned to characters'
            ]);
            exit();
        }
        
        $query = "DELETE FROM items WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Equipment deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete equipment: ' . mysqli_error($conn));
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

