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

require_once __DIR__ . '/../includes/supabase_client.php';

try {
    $category_filter = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $query = [
        'select' => 'id,name,category,display_order,description,min_level,max_level',
        'order' => 'category.asc,display_order.asc'
    ];
    if ($category_filter && in_array($category_filter, ['Physical', 'Social', 'Mental', 'Optional'], true)) {
        $query['category'] = 'eq.' . $category_filter;
    }
    $abilities = supabase_table_get('abilities', $query);
    if ($category_filter) {
        echo json_encode([
            'success' => true,
            'category' => $category_filter,
            'abilities' => $abilities
        ], JSON_PRETTY_PRINT);
    } else {
        $grouped = ['Physical' => [], 'Social' => [], 'Mental' => [], 'Optional' => []];
        foreach ($abilities as $ability) {
            $cat = $ability['category'] ?? '';
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

