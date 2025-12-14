<?php
/**
 * Locations API
 * Returns all locations from the locations table
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

try {
    // SECURITY: Using prepared statement helper for consistency
    // Note: This query is static (no user input), but using helpers maintains security standards
    $locations = db_fetch_all($conn, 
        "SELECT id, name, type, status, district, owner_type, faction, access_control, security_level, description, summary, notes, pc_haven, created_at 
         FROM locations 
         ORDER BY id DESC"
    );
    
    echo json_encode([
        'success' => true,
        'locations' => $locations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

