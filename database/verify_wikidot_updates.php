<?php
/**
 * Quick verification script to check if Wikidot updates were applied
 * 
 * Run via browser: database/verify_wikidot_updates.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Output HTML header immediately so title shows even if errors occur
echo "<!DOCTYPE html>";
echo "<html><head><title>Verify Wikidot Updates</title></head><body>";

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>" . htmlspecialchars(mysqli_connect_error()) . "</p>";
    echo "</body></html>";
    exit;
}

echo "<h2>🔍 Verifying Wikidot Updates</h2>";

// Check a few sample items that should have been updated
$sample_items = [
    'merits' => ['Absent-Minded', 'Acute Sense', 'Adaptable Nature'],
    'flaws' => ['Absent-Minded', 'Addiction', 'Adolescent']
];

echo "<h3>Sample Merits:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Name</th><th>Cost</th><th>Has Description</th><th>Description Preview</th></tr>";

foreach ($sample_items['merits'] as $name) {
    $result = mysqli_query($conn, "SELECT name, cost, description FROM merits WHERE name = '" . mysqli_real_escape_string($conn, $name) . "'");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $has_desc = !empty($row['description']) ? '✅ Yes' : '❌ No';
        $desc_preview = !empty($row['description']) ? substr($row['description'], 0, 60) . '...' : 'None';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost'] ?? 'NULL') . "</td>";
        echo "<td>{$has_desc}</td>";
        echo "<td>" . htmlspecialchars($desc_preview) . "</td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='4'>❌ Not found: {$name}</td></tr>";
    }
}
echo "</table>";

echo "<h3>Sample Flaws:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Name</th><th>Cost</th><th>Has Description</th><th>Description Preview</th></tr>";

foreach ($sample_items['flaws'] as $name) {
    $result = mysqli_query($conn, "SELECT name, cost, description FROM flaws WHERE name = '" . mysqli_real_escape_string($conn, $name) . "'");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $has_desc = !empty($row['description']) ? '✅ Yes' : '❌ No';
        $desc_preview = !empty($row['description']) ? substr($row['description'], 0, 60) . '...' : 'None';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost'] ?? 'NULL') . "</td>";
        echo "<td>{$has_desc}</td>";
        echo "<td>" . htmlspecialchars($desc_preview) . "</td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='4'>❌ Not found: {$name}</td></tr>";
    }
}
echo "</table>";

// Count statistics
$merits_with_cost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM merits WHERE cost IS NOT NULL"))['count'];
$merits_with_desc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM merits WHERE description IS NOT NULL AND description != ''"))['count'];
$merits_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM merits"))['count'];

$flaws_with_cost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM flaws WHERE cost IS NOT NULL"))['count'];
$flaws_with_desc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM flaws WHERE description IS NOT NULL AND description != ''"))['count'];
$flaws_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM flaws"))['count'];

echo "<h3>📊 Statistics:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Category</th><th>Total</th><th>With Cost</th><th>With Description</th></tr>";
echo "<tr><td>Merits</td><td>{$merits_total}</td><td>{$merits_with_cost}</td><td>{$merits_with_desc}</td></tr>";
echo "<tr><td>Flaws</td><td>{$flaws_total}</td><td>{$flaws_with_cost}</td><td>{$flaws_with_desc}</td></tr>";
echo "</table>";

mysqli_close($conn);
echo "</body></html>";
?>
