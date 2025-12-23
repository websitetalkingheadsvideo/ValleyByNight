<?php
/**
 * Coterie CRUD API
 * Handles Create, Read, Update, Delete operations for character coterie associations
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../includes/connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Handle request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'GET') {
        // Get all coterie associations
        // Try with notes column first, fallback to without if column doesn't exist
        $query = "SELECT 
                    character_coteries.character_id, 
                    character_coteries.coterie_name, 
                    character_coteries.coterie_type, 
                    character_coteries.role, 
                    character_coteries.description, 
                    character_coteries.notes,
                    characters.character_name, 
                    characters.clan, 
                    characters.player_name
                  FROM character_coteries
                  LEFT JOIN characters ON character_coteries.character_id = characters.id
                  ORDER BY character_coteries.coterie_name, characters.character_name";
        
        $result = mysqli_query($conn, $query);
        
        // If query failed and it's because notes column doesn't exist, try without it
        if (!$result && strpos(mysqli_error($conn), 'notes') !== false) {
            $query = "SELECT 
                        character_coteries.character_id, 
                        character_coteries.coterie_name, 
                        character_coteries.coterie_type, 
                        character_coteries.role, 
                        character_coteries.description,
                        characters.character_name, 
                        characters.clan, 
                        characters.player_name
                      FROM character_coteries
                      LEFT JOIN characters ON character_coteries.character_id = characters.id
                      ORDER BY character_coteries.coterie_name, characters.character_name";
            
            $result = mysqli_query($conn, $query);
        }
        
        if (!$result) {
            $error = mysqli_error($conn);
            throw new Exception('Database query failed: ' . $error);
        }
        
        $coteries = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Ensure notes field exists even if column doesn't
            if (!isset($row['notes'])) {
                $row['notes'] = '';
            }
            $coteries[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $coteries
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($method === 'POST') {
        // Create new coterie association
        $character_id = intval($input['character_id'] ?? 0);
        $coterie_name = trim($input['coterie_name'] ?? '');
        $coterie_type = trim($input['coterie_type'] ?? '');
        $role = trim($input['role'] ?? '');
        $description = trim($input['description'] ?? '');
        $notes = trim($input['notes'] ?? '');
        
        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        
        if (empty($coterie_name)) {
            throw new Exception('Coterie name is required');
        }
        
        // Check if notes column exists, use appropriate INSERT
        $check_notes = mysqli_query($conn, "SHOW COLUMNS FROM character_coteries LIKE 'notes'");
        $has_notes = ($check_notes && mysqli_num_rows($check_notes) > 0);
        
        if ($has_notes) {
            $query = "INSERT INTO character_coteries (character_id, coterie_name, coterie_type, role, description, notes) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'isssss', $character_id, $coterie_name, $coterie_type, $role, $description, $notes);
        } else {
            $query = "INSERT INTO character_coteries (character_id, coterie_name, coterie_type, role, description) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'issss', $character_id, $coterie_name, $coterie_type, $role, $description);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Coterie association created successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Failed to create coterie association: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'PUT') {
        // Update existing coterie association
        $character_id = intval($input['character_id'] ?? 0);
        $old_coterie_name = trim($input['old_coterie_name'] ?? '');
        $coterie_name = trim($input['coterie_name'] ?? '');
        $coterie_type = trim($input['coterie_type'] ?? '');
        $role = trim($input['role'] ?? '');
        $description = trim($input['description'] ?? '');
        $notes = trim($input['notes'] ?? '');
        
        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        
        if (empty($old_coterie_name)) {
            throw new Exception('Old coterie name is required for update');
        }
        
        if (empty($coterie_name)) {
            throw new Exception('Coterie name is required');
        }
        
        // Check if notes column exists, use appropriate UPDATE
        $check_notes = mysqli_query($conn, "SHOW COLUMNS FROM character_coteries LIKE 'notes'");
        $has_notes = ($check_notes && mysqli_num_rows($check_notes) > 0);
        
        if ($has_notes) {
            $query = "UPDATE character_coteries SET 
                      coterie_name = ?, coterie_type = ?, role = ?, description = ?, notes = ?
                      WHERE character_id = ? AND coterie_name = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'sssssis', $coterie_name, $coterie_type, $role, $description, $notes, $character_id, $old_coterie_name);
        } else {
            $query = "UPDATE character_coteries SET 
                      coterie_name = ?, coterie_type = ?, role = ?, description = ?
                      WHERE character_id = ? AND coterie_name = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssssis', $coterie_name, $coterie_type, $role, $description, $character_id, $old_coterie_name);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Coterie association updated successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Failed to update coterie association: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'DELETE') {
        // Delete coterie association
        $character_id = intval($input['character_id'] ?? 0);
        $coterie_name = trim($input['coterie_name'] ?? '');
        
        if ($character_id <= 0) {
            throw new Exception('Invalid character ID');
        }
        
        if (empty($coterie_name)) {
            throw new Exception('Coterie name is required');
        }
        
        $query = "DELETE FROM character_coteries WHERE character_id = ? AND coterie_name = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'is', $character_id, $coterie_name);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Coterie association deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Failed to delete coterie association: ' . mysqli_error($conn));
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
