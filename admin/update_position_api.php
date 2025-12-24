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
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

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
$current_holder_id = isset($input['current_holder']) ? intval($input['current_holder']) : null;
$is_acting = isset($input['is_acting']) ? (intval($input['is_acting']) ? 1 : 0) : 0;

if (empty($position_id) || empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Position ID, name, and category are required']);
    exit();
}

try {
    // Start transaction
    if (!db_begin_transaction($conn)) {
        throw new Exception('Failed to begin transaction: ' . mysqli_error($conn));
    }
    
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
    
    // Handle current holder assignment if provided
    if ($current_holder_id !== null) {
        $default_night = CAMARILLA_DEFAULT_NIGHT;
        
        // If current_holder_id is 0, it means the user selected "Vacant" in the dropdown.
        // For single-holder positions, this clears the position.
        // For multi-holder positions (Talon/Whip), we'll assume it means clearing ALL for now 
        // since the UI only supports a single selection.
        if ($current_holder_id === 0) {
            $end_assignments = "UPDATE camarilla_position_assignments 
                               SET end_night = ? 
                               WHERE position_id = ? 
                               AND (end_night IS NULL OR end_night >= ?)";
            db_execute($conn, $end_assignments, "sss", [$default_night, $position_id, $default_night]);
        } else {
            // Get character name for assignment
            $character = db_fetch_one($conn, "SELECT id, character_name FROM characters WHERE id = ?", "i", [$current_holder_id]);
            
            if ($character) {
                // Check if this position allows multiple holders (Talon, Whip)
                $allows_multiple = in_array(strtolower($position_id), ['talon', 'whip']);

                // Only end ALL existing active assignments if NOT a multi-holder position
                if (!$allows_multiple) {
                    $end_assignments = "UPDATE camarilla_position_assignments 
                                       SET end_night = ? 
                                       WHERE position_id = ? 
                                       AND (end_night IS NULL OR end_night >= ?)";
                    db_execute($conn, $end_assignments, "sss", [$default_night, $position_id, $default_night]);
                }
                
                // Create character_id string for assignment (e.g., "SABINE_TOREADOR")
                $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character['character_name']));
                
                // Always end any existing assignment for THIS specific character in THIS position
                // to avoid duplicates or to update their status (like switching from acting to permanent)
                $end_specific = "UPDATE camarilla_position_assignments 
                                SET end_night = ? 
                                WHERE position_id = ? 
                                AND character_id = ?
                                AND (end_night IS NULL OR end_night >= ?)";
                db_execute($conn, $end_specific, "ssss", [$default_night, $position_id, $assignment_character_id, $default_night]);
                
                // Create new assignment
                $insert_assignment = "INSERT INTO camarilla_position_assignments 
                                    (position_id, character_id, start_night, end_night, is_acting) 
                                    VALUES (?, ?, ?, NULL, ?)";
                db_execute($conn, $insert_assignment, "sssi", [
                    $position_id,
                    $assignment_character_id,
                    $default_night,
                    $is_acting
                ]);
            }
        }
    }
    
    // Commit transaction
    if (!db_commit($conn)) {
        throw new Exception('Failed to commit transaction: ' . mysqli_error($conn));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Position updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

