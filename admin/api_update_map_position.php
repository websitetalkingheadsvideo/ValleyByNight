<?php
/**
 * API to update location map pixel coordinates
 * Allows admins to set marker positions on the map
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['location_id']) || !isset($input['map_pixel_x']) || !isset($input['map_pixel_y'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$location_id = intval($input['location_id']);
$map_pixel_x = floatval($input['map_pixel_x']);
$map_pixel_y = floatval($input['map_pixel_y']);

try {
    // Check if map_pixel_x and map_pixel_y columns exist, if not add them
    $check_columns = "SHOW COLUMNS FROM locations LIKE 'map_pixel_x'";
    $result = mysqli_query($conn, $check_columns);
    if (mysqli_num_rows($result) == 0) {
        // Add the columns
        $alter_query = "ALTER TABLE locations 
                       ADD COLUMN map_pixel_x DECIMAL(10,2) NULL,
                       ADD COLUMN map_pixel_y DECIMAL(10,2) NULL";
        mysqli_query($conn, $alter_query);
    }

    // Update the location
    $update_query = "UPDATE locations 
                     SET map_pixel_x = ?, map_pixel_y = ?
                     WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ddi", $map_pixel_x, $map_pixel_y, $location_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Marker position updated'
        ]);
    } else {
        throw new Exception('Update failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

