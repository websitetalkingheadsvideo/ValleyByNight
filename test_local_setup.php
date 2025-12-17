<?php
/**
 * Quick test script to verify local setup
 * Delete this file after verification
 */

echo "<h1>VbN Local Setup Test</h1>";

// Test 1: .env file
echo "<h2>1. Environment File</h2>";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✓ .env file found<br>";
} else {
    echo "✗ .env file NOT found<br>";
}

// Test 2: Database connection
echo "<h2>2. Database Connection</h2>";
require_once __DIR__ . '/includes/connect.php';

if (isset($conn) && $conn) {
    echo "✓ Database connection: SUCCESS<br>";
    
    // Test a simple query
    $result = mysqli_query($conn, "SELECT 1 as test");
    if ($result) {
        echo "✓ Database query test: SUCCESS<br>";
    } else {
        echo "✗ Database query test: FAILED<br>";
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✗ Database connection: FAILED<br>";
    if (isset($conn)) {
        echo "Error: " . mysqli_connect_error() . "<br>";
    }
}

// Test 3: PHP Info
echo "<h2>3. PHP Configuration</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Loaded extensions: mysqli=" . (extension_loaded('mysqli') ? 'YES' : 'NO') . ", session=" . (extension_loaded('session') ? 'YES' : 'NO') . "<br>";

// Test 4: File paths
echo "<h2>4. File Paths</h2>";
echo "Current directory: " . __DIR__ . "<br>";
echo "index.php exists: " . (file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO') . "<br>";

echo "<hr>";
echo "<p><strong>If all tests show ✓, your setup is working!</strong></p>";
echo "<p>Now try accessing <a href='index.php'>index.php</a> to see the actual application.</p>";
?>

