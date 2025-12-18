<?php
/**
 * Location Assignments API
 * Handles GET (fetch assignments) and POST (assign characters) for locations
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
        // Get all characters assigned to a specific location
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        
        if ($location_id <= 0) {
            throw new Exception('Invalid location ID');
        }
        
        $query = "SELECT 
                    lo.id,
                    lo.character_id,
                    lo.ownership_type,
                    lo.notes,
                    c.character_name,
                    c.clan,
                    c.player_name
                  FROM location_ownership lo
                  LEFT JOIN characters c ON lo.character_id = c.id
                  WHERE lo.location_id = ?
                  ORDER BY c.character_name ASC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $location_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = [
                'id' => intval($row['id']),
                'character_id' => intval($row['character_id']),
                'character_name' => $row['character_name'],
                'clan' => $row['clan'],
                'player_name' => $row['player_name'],
                'ownership_type' => $row['ownership_type'],
                'notes' => $row['notes']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'assignments' => $assignments,
            'count' => count($assignments)
        ]);
        
    } elseif ($method === 'POST') {
        // Assign characters to location
        $input = json_decode(file_get_contents('php://input'), true);
        
        $location_id = intval($input['location_id'] ?? 0);
        $character_ids = $input['character_ids'] ?? [];
        $ownership_type = trim($input['ownership_type'] ?? 'Resident');
        
        if ($location_id <= 0 || empty($character_ids)) {
            throw new Exception('Invalid location_id or character_ids');
        }
        
        // Delete existing assignments for this location
        $delete_query = "DELETE FROM location_ownership WHERE location_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, 'i', $location_id);
        mysqli_stmt_execute($stmt);
        
        // Insert new assignments
        $insert_query = "INSERT INTO location_ownership (location_id, character_id, ownership_type, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_query);
        
        foreach ($character_ids as $character_id) {
            $char_id = intval($character_id);
            if ($char_id > 0) {
                mysqli_stmt_bind_param($stmt, 'iis', $location_id, $char_id, $ownership_type);
                mysqli_stmt_execute($stmt);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Characters assigned successfully',
            'count' => count($character_ids)
        ]);
        
    } elseif ($method === 'DELETE') {
        // Remove assignment
        $input = json_decode(file_get_contents('php://input'), true);
        
        $assignment_id = intval($input['assignment_id'] ?? 0);
        
        if ($assignment_id <= 0) {
            throw new Exception('Invalid assignment ID');
        }
        
        $delete_query = "DELETE FROM location_ownership WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, 'i', $assignment_id);
        mysqli_stmt_execute($stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Assignment removed successfully'
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

