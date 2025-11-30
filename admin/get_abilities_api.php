<?php
/**
 * Get Abilities API
 * Returns all available abilities grouped by category
 */
session_start();
header('Content-Type: application/json');

// Check authentication (optional - abilities are public data, but following pattern)
// Uncomment if you want to restrict access
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit();
// }

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Optional category filter
    $category_filter = isset($_GET['category']) ? trim($_GET['category']) : null;
    
    // Build query
    if ($category_filter && in_array($category_filter, ['Physical', 'Social', 'Mental', 'Optional'])) {
        $abilities = db_fetch_all($conn,
            "SELECT id, name, category, display_order, description, min_level, max_level
             FROM abilities
             WHERE category = ?
             ORDER BY display_order ASC",
            's', [$category_filter]
        );
        
        // Return single category
        echo json_encode([
            'success' => true,
            'category' => $category_filter,
            'abilities' => $abilities
        ], JSON_PRETTY_PRINT);
    } else {
        // Get all abilities grouped by category
        $all_abilities = db_fetch_all($conn,
            "SELECT id, name, category, display_order, description, min_level, max_level
             FROM abilities
             ORDER BY category, display_order ASC"
        );
        
        // Group by category
        $grouped = [
            'Physical' => [],
            'Social' => [],
            'Mental' => [],
            'Optional' => []
        ];
        
        foreach ($all_abilities as $ability) {
            $cat = $ability['category'];
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $ability;
            }
        }
        
        echo json_encode([
            'success' => true,
            'abilities' => $grouped,
            'counts' => [
                'Physical' => count($grouped['Physical']),
                'Social' => count($grouped['Social']),
                'Mental' => count($grouped['Mental']),
                'Optional' => count($grouped['Optional'])
            ]
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    error_log("Get Abilities API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching abilities: ' . $e->getMessage()
    ]);
}
?>

