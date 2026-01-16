<?php
/**
 * Add "None" derangement as default option
 * 
 * This script adds a "None" entry to the derangements table
 * and ensures it's set as the default option.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if "None" already exists
$existing = db_fetch_one($conn, "SELECT id FROM derangements WHERE name = ?", 's', ['None']);

if ($existing) {
    echo "✅ 'None' derangement already exists (ID: {$existing['id']})\n";
    echo "Updating it to be the default...\n";
    
    // Update existing "None" to have display_order 0 and ensure description
    db_execute($conn,
        "UPDATE derangements SET display_order = 0, description = ? WHERE name = 'None'",
        's', ['No derangement - character is mentally stable']
    );
    echo "✅ Updated 'None' derangement\n";
} else {
    // Insert "None" with display_order 0 (first)
    $result = db_execute($conn,
        "INSERT INTO derangements (name, cost, description, display_order) 
         VALUES (?, 0, ?, 0)",
        'ss', ['None', 'No derangement - character is mentally stable']
    );
    
    if ($result === false) {
        die("❌ Error: Failed to insert 'None' derangement: " . mysqli_error($conn) . "\n");
    }
    
    echo "✅ Added 'None' derangement (ID: $result)\n";
}

// Shift all other derangements' display_order by 1 to make room for None at 0
db_execute($conn, "UPDATE derangements SET display_order = display_order + 1 WHERE name != 'None'");

echo "✅ Adjusted display orders - 'None' is now first\n";

// Verify the result
$none_entry = db_fetch_one($conn, "SELECT id, name, cost, description, display_order FROM derangements WHERE name = 'None'");

if ($none_entry) {
    echo "\n✅ Verification - 'None' derangement:\n";
    echo "  ID: {$none_entry['id']}\n";
    echo "  Name: {$none_entry['name']}\n";
    echo "  Cost: {$none_entry['cost']}\n";
    echo "  Display Order: {$none_entry['display_order']}\n";
    echo "  Description: {$none_entry['description']}\n";
} else {
    echo "❌ Warning: Could not verify 'None' derangement was created\n";
}

// Show first 5 derangements to confirm order
echo "\nFirst 5 derangements by display order:\n";
$first_five = db_fetch_all($conn, 
    "SELECT name, display_order FROM derangements ORDER BY display_order LIMIT 5"
);

foreach ($first_five as $row) {
    echo "  {$row['display_order']}: {$row['name']}\n";
}

mysqli_close($conn);
?>
