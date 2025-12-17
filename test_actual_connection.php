<?php
// Test using the actual includes/connect.php file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing with actual includes/connect.php...\n\n";

try {
    require_once __DIR__ . '/includes/connect.php';
    
    if ($conn) {
        echo "✅ SUCCESS: Database connection established!\n\n";
        
        // Test a simple query
        $result = mysqli_query($conn, "SELECT VERSION() as version, DATABASE() as db");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "MySQL Version: " . $row['version'] . "\n";
            echo "Connected to database: " . $row['db'] . "\n";
        }
        
        // Test if we can query a table
        $tables_result = mysqli_query($conn, "SHOW TABLES");
        if ($tables_result) {
            $table_count = mysqli_num_rows($tables_result);
            echo "Tables found: $table_count\n";
        }
        
        mysqli_close($conn);
        echo "\n✅ All tests passed! Database connection is working correctly.\n";
    } else {
        echo "❌ ERROR: Connection failed\n";
        echo "Error: " . mysqli_connect_error() . "\n";
        echo "Error number: " . mysqli_connect_errno() . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
?>

