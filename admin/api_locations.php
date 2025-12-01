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
    $query = "SELECT id, name, type, status, district, owner_type, faction, access_control, security_level, description, summary, notes, created_at 
              FROM locations 
              ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $locations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $locations[] = $row;
    }
    
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

