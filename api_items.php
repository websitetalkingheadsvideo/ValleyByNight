<?php
/**
 * Items API (GET)
 * Returns all items for admin_items.js compatibility
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/connect.php';

try {
    $query = "SELECT * FROM items ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Parse requirements JSON if present
        if (!empty($row['requirements'])) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

