<?php
/**
 * Locations CRUD API
 * Handles Create, Read, Update, Delete operations for locations
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
    // Handle request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        // Create new location
        $name = mysqli_real_escape_string($conn, $input['name'] ?? '');
        $type = mysqli_real_escape_string($conn, $input['type'] ?? '');
        $summary = mysqli_real_escape_string($conn, $input['summary'] ?? '');
        $description = mysqli_real_escape_string($conn, $input['description'] ?? '');
        $notes = mysqli_real_escape_string($conn, $input['notes'] ?? '');
        $status = mysqli_real_escape_string($conn, $input['status'] ?? 'Active');
        $district = mysqli_real_escape_string($conn, $input['district'] ?? '');
        $owner_type = mysqli_real_escape_string($conn, $input['owner_type'] ?? '');
        $faction = mysqli_real_escape_string($conn, $input['faction'] ?? '');
        $access_control = mysqli_real_escape_string($conn, $input['access_control'] ?? '');
        $security_level = intval($input['security_level'] ?? 3);
        $pc_haven = ($input['pc_haven'] == 1 || $input['pc_haven'] === true || $input['pc_haven'] === '1') ? 1 : 0;
        
        // Only set pc_haven if type is Haven
        if ($type !== 'Haven') {
            $pc_haven = 0;
        }
        
        $query = "INSERT INTO locations (name, type, summary, description, notes, status, district, owner_type, faction, access_control, security_level, pc_haven, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssssssii', $name, $type, $summary, $description, $notes, $status, $district, $owner_type, $faction, $access_control, $security_level, $pc_haven);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location created successfully',
                'id' => mysqli_insert_id($conn)
            ]);
        } else {
            throw new Exception('Failed to create location: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'PUT') {
        // Update existing location
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid location ID');
        }
        
        $name = mysqli_real_escape_string($conn, $input['name'] ?? '');
        $type = mysqli_real_escape_string($conn, $input['type'] ?? '');
        $summary = mysqli_real_escape_string($conn, $input['summary'] ?? '');
        $description = mysqli_real_escape_string($conn, $input['description'] ?? '');
        $notes = mysqli_real_escape_string($conn, $input['notes'] ?? '');
        $status = mysqli_real_escape_string($conn, $input['status'] ?? 'Active');
        $district = mysqli_real_escape_string($conn, $input['district'] ?? '');
        $owner_type = mysqli_real_escape_string($conn, $input['owner_type'] ?? '');
        $faction = mysqli_real_escape_string($conn, $input['faction'] ?? '');
        $access_control = mysqli_real_escape_string($conn, $input['access_control'] ?? '');
        $security_level = intval($input['security_level'] ?? 3);
        $pc_haven = ($input['pc_haven'] == 1 || $input['pc_haven'] === true || $input['pc_haven'] === '1') ? 1 : 0;
        
        // Only set pc_haven if type is Haven
        if ($type !== 'Haven') {
            $pc_haven = 0;
        }
        
        $query = "UPDATE locations SET 
                  name = ?, type = ?, summary = ?, description = ?, notes = ?, status = ?, 
                  district = ?, owner_type = ?, faction = ?, access_control = ?, security_level = ?, 
                  pc_haven = ?, updated_at = NOW()
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssssssiii', $name, $type, $summary, $description, $notes, $status, $district, $owner_type, $faction, $access_control, $security_level, $pc_haven, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update location: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'DELETE') {
        // Delete location
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid location ID');
        }
        
        // Check if location has assignments (you may want to add this check)
        // For now, just delete
        
        $query = "DELETE FROM locations WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete location: ' . mysqli_error($conn));
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

