<?php
/**
 * Check location types and pc_haven values for specific locations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

$location_names = [
    'Hawthorne Estate',
    'Hawthorne',
    'The Chantry',
    'Chantry',
    'Dunlap Apartments',
    'Roosevelt Row',
    'Roosevelt Row Artist\'s Loft',
    'Rooseverlt Row' // Also check the misspelled version
];

header('Content-Type: text/html; charset=utf-8');
echo "<pre>\n";
echo "Checking location types and pc_haven values:\n\n";

foreach ($location_names as $name) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type, pc_haven, status FROM locations WHERE name = ? OR name LIKE ?");
    $search_pattern = "%{$name}%";
    mysqli_stmt_bind_param($stmt, "ss", $name, $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $isEarnableHaven = ($row['type'] === 'Haven' && ($row['pc_haven'] == 1 || $row['pc_haven'] === true));
            echo "ID: {$row['id']}\n";
            echo "Name: {$row['name']}\n";
            echo "Type: {$row['type']}\n";
            echo "pc_haven: " . ($row['pc_haven'] ? '1' : '0') . "\n";
            echo "Status: {$row['status']}\n";
            echo "Is Earnable Haven: " . ($isEarnableHaven ? 'YES' : 'NO') . "\n";
            echo "Would be hidden by 'Hide Earnable': " . ($isEarnableHaven ? 'YES' : 'NO') . "\n";
            echo "---\n\n";
        }
    } else {
        echo "No match found for: {$name}\n\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "</pre>";

mysqli_close($conn);
?>
