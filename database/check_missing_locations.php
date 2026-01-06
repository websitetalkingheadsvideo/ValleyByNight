<?php
/**
 * Check for missing main locations and their PC Haven relationships
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Optional: Check if admin (but allow direct access for debugging)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     die('Unauthorized');
// }

require_once __DIR__ . '/../includes/connect.php';

header('Content-Type: text/html; charset=utf-8');
echo "<pre>\n";
echo "Checking for missing main locations and PC Haven relationships:\n\n";

// Check for locations with these names
$search_terms = [
    'Hawthorne Estate',
    'Hawthorne',
    'The Chantry',
    'Chantry',
    'Roosevelt Row',
    'Roosevelt Row Artist\'s Loft',
    'Dunlap Apartments'
];

echo "=== All locations matching search terms ===\n\n";
foreach ($search_terms as $term) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type, pc_haven, status, district FROM locations WHERE name = ? OR name LIKE ? ORDER BY id");
    $search_pattern = "%{$term}%";
    mysqli_stmt_bind_param($stmt, "ss", $term, $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $isPCHaven = ($row['type'] === 'Haven' && ($row['pc_haven'] == 1 || $row['pc_haven'] === true));
            echo "ID: {$row['id']}\n";
            echo "Name: {$row['name']}\n";
            echo "Type: {$row['type']}\n";
            echo "PC Haven: " . ($row['pc_haven'] ? 'YES' : 'NO') . "\n";
            echo "Status: {$row['status']}\n";
            echo "District: " . ($row['district'] ?: 'N/A') . "\n";
            echo "---\n";
        }
    }
    mysqli_stmt_close($stmt);
}

echo "\n\n=== Checking for main locations that should exist ===\n\n";

// Check if main locations exist (not PC Havens)
$main_locations = [
    ['name' => 'Hawthorne Estate', 'type' => 'Domain'],
    ['name' => 'The Chantry', 'type' => 'Chantry'],
    ['name' => 'Dunlap Apartments', 'type' => 'Business'],
    ['name' => 'Roosevelt Row', 'type' => 'Domain']
];

foreach ($main_locations as $loc) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type, pc_haven, status FROM locations WHERE name = ? AND type = ?");
    mysqli_stmt_bind_param($stmt, "ss", $loc['name'], $loc['type']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "✓ FOUND: {$loc['name']} (ID: {$row['id']}, Type: {$row['type']})\n";
    } else {
        echo "✗ MISSING: {$loc['name']} (Type: {$loc['type']})\n";
    }
    mysqli_stmt_close($stmt);
}

echo "\n\n=== Checking for PC Havens that might have replaced main locations ===\n\n";

// Check for PC Havens with similar names
$pc_haven_checks = [
    'Hawthorne',
    'Chantry',
    'Roosevelt'
];

foreach ($pc_haven_checks as $term) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type, pc_haven, status FROM locations WHERE (name LIKE ? OR name LIKE ?) AND type = 'Haven' AND pc_haven = 1");
    $pattern1 = "%{$term}%";
    $pattern2 = "{$term}%";
    mysqli_stmt_bind_param($stmt, "ss", $pattern1, $pattern2);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "PC Haven found: {$row['name']} (ID: {$row['id']})\n";
            echo "  This might have replaced a main location!\n";
            echo "---\n";
        }
    }
    mysqli_stmt_close($stmt);
}

echo "</pre>";

mysqli_close($conn);
?>
