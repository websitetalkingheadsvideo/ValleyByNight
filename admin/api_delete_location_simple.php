<?php
/**
 * Delete Location API (Simple)
 * Handles DELETE method for location deletion
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$location_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($location_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid location ID']);
    exit();
}

try {
    // Start transaction
    db_begin_transaction($conn);
    
    // Delete location ownership assignments first (foreign key constraint)
    $delete_ownership = db_execute($conn, 
        "DELETE FROM location_ownership WHERE location_id = ?", 
        'i', 
        [$location_id]
    );
    
    // Delete location items assignments
    $delete_items = db_execute($conn,
        "DELETE FROM location_items WHERE location_id = ?",
        'i',
        [$location_id]
    );
    
    // Delete the location itself
    $affected = db_execute($conn,
        "DELETE FROM locations WHERE id = ?",
        'i',
        [$location_id]
    );
    
    if ($affected > 0) {
        db_commit($conn);
        echo json_encode([
            'success' => true,
            'message' => 'Location deleted successfully'
        ]);
    } else {
        db_rollback($conn);
        echo json_encode([
            'success' => false,
            'error' => 'Location not found'
        ]);
    }
    
} catch (Exception $e) {
    db_rollback($conn);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

