<?php
/**
 * Fix display orders to be sequential starting from 0
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all derangements ordered by current display_order
$derangements = db_fetch_all($conn, 
    "SELECT id, name FROM derangements ORDER BY display_order, name"
);

// Update each one with sequential display_order starting from 0
$order = 0;
foreach ($derangements as $derangement) {
    db_execute($conn,
        "UPDATE derangements SET display_order = ? WHERE id = ?",
        'ii', [$order, $derangement['id']]
    );
    $order++;
}

echo "✅ Fixed display orders - all derangements now have sequential order starting from 0\n";
echo "Total derangements: " . count($derangements) . "\n";

// Show first 10 to verify
echo "\nFirst 10 derangements by display order:\n";
$first_ten = db_fetch_all($conn, 
    "SELECT name, display_order FROM derangements ORDER BY display_order LIMIT 10"
);

foreach ($first_ten as $row) {
    echo "  {$row['display_order']}: {$row['name']}\n";
}

mysqli_close($conn);
?>
