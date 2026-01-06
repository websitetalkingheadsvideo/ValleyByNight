<?php
/**
 * Verify Boons Table Structure
 * Checks if Harpy integration fields exist in the boons table
 * 
 * Usage: Run via web browser
 * URL: database/check_boons_table.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Boons Table Verification</title>\n";
echo "<style>
body { font-family: monospace; background: #1a0f0f; color: #f5e6d3; padding: 20px; }
h1 { color: #8B0000; }
pre { background: #2a1515; padding: 15px; border: 2px solid #8B0000; border-radius: 5px; }
.check { color: #1a6b3a; }
.error { color: #b22222; }
.warning { color: #8B6508; }
</style></head><body>\n";

echo "<h1>💎 Boons Table Structure Verification</h1>\n";
echo "<pre>\n";

// Get all columns from boons table
$query = "SHOW COLUMNS FROM boons";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error: Could not retrieve table structure. " . mysqli_error($conn) . "\n");
}

$columns = [];
$requiredFields = [
    'id',
    'creditor_id',
    'debtor_id',
    'boon_type',
    'status',
    'description',
    'created_date',
    'created_by'
];

$harpyFields = [
    'registered_with_harpy',
    'date_registered',
    'harpy_notes'
];

$optionalFields = [
    'fulfilled_date',
    'due_date',
    'notes',
    'updated_at'
];

echo "Current columns in 'boons' table:\n";
echo str_repeat("=", 70) . "\n";

while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
    printf("%-30s %-25s %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'] === 'YES' ? '(nullable)' : '(required)'
    );
}

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "\nChecking required fields...\n";

$missingRequired = [];
foreach ($requiredFields as $field) {
    if (in_array($field, $columns)) {
        echo "<span class='check'>✓ {$field} - EXISTS</span>\n";
    } else {
        echo "<span class='error'>✗ {$field} - MISSING</span>\n";
        $missingRequired[] = $field;
    }
}

echo "\nChecking Harpy integration fields...\n";

$missingHarpy = [];
foreach ($harpyFields as $field) {
    if (in_array($field, $columns)) {
        echo "<span class='check'>✓ {$field} - EXISTS</span>\n";
    } else {
        echo "<span class='error'>✗ {$field} - MISSING</span>\n";
        $missingHarpy[] = $field;
    }
}

echo "\nChecking optional fields...\n";

foreach ($optionalFields as $field) {
    if (in_array($field, $columns)) {
        echo "<span class='check'>✓ {$field} - EXISTS</span>\n";
    } else {
        echo "<span style='color: #8B6508;'>○ {$field} - OPTIONAL (not present)</span>\n";
    }
}

echo "\n";
echo str_repeat("=", 70) . "\n";

if (empty($missingRequired) && empty($missingHarpy)) {
    echo "\n<span class='check'>✅ SUCCESS: All required and Harpy fields are present!</span>\n";
    echo "\nDatabase schema is correct. The Boon Agent code needs to be updated to match this structure.\n";
    echo "\nNote: The schema uses:\n";
    echo "  - 'id' instead of 'boon_id'\n";
    echo "  - 'creditor_id'/'debtor_id' (integers) instead of 'giver_name'/'receiver_name' (strings)\n";
    echo "  - 'created_date' instead of 'date_created'\n";
    echo "  - lowercase enum values: 'trivial','minor','major','life' and 'active','fulfilled','cancelled','disputed'\n";
} else {
    if (!empty($missingRequired)) {
        echo "\n<span class='error'>⚠️  WARNING: Missing required fields: " . implode(', ', $missingRequired) . "</span>\n";
    }
    if (!empty($missingHarpy)) {
        echo "\n<span class='warning'>⚠️  WARNING: Missing Harpy fields: " . implode(', ', $missingHarpy) . "</span>\n";
        echo "These need to be added for full Harpy integration.\n";
    }
}

echo "</pre>\n";
echo "<p><a href='/admin/boon_agent_viewer.php' style='color: #8B0000;'>← Back to Boon Agent</a></p>\n";
echo "</body></html>\n";

mysqli_close($conn);
?>

