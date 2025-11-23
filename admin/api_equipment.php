<?php
/**
 * Equipment API
 * Returns all equipment items (from items table)
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
    $query = "SELECT id, name, type, category, damage, `range`, rarity, price, description, requirements, image, notes, created_at 
              FROM items 
              ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode JSON requirements if present
        if ($row['requirements']) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'equipment' => $items // Alias for compatibility
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

