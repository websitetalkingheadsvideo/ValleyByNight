<?php
/**
 * Test database connection using same logic as includes/connect.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test (using connect.php logic)</h1>\n";
echo "<pre>\n";

// Load .env file if it exists (same logic as connect.php)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✓ Found .env file\n";
    
    // Clear system DB environment variables first
    $dbVars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'];
    foreach ($dbVars as $var) {
        if (getenv($var)) {
            putenv("$var"); // Unset system variable
        }
    }
    
    // Now load from .env file
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            // Set from .env file
            putenv("$key=$value");
        }
    }
} else {
    echo "⚠ No .env file found - using system environment variables\n";
}

// Get database credentials (same defaults as connect.php)
$servername = getenv('DB_HOST') ?: "vdb5.pit.pair.com";
$username = getenv('DB_USER') ?: "working_64";
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME') ?: "working_vbn";

echo "\nDatabase Configuration (from connect.php logic):\n";
echo "  Host: $servername\n";
echo "  User: $username\n";
echo "  Password: " . ($password ? str_repeat('*', min(strlen($password), 20)) . " (length: " . strlen($password) . ")" : "NOT SET") . "\n";
echo "  Database: $dbname\n\n";

// Require password to be set
if ($password === false || $password === '') {
    echo "❌ ERROR: DB_PASSWORD is not set!\n";
    echo "\nPlease set it in one of these ways:\n";
    echo "1. Create/update .env file with: DB_PASSWORD=your_password\n";
    echo "2. Set system environment variable: DB_PASSWORD\n";
    exit(1);
}

// Disable mysqli exceptions (same as connect.php)
if (PHP_VERSION_ID >= 80000) {
    mysqli_report(MYSQLI_REPORT_OFF);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Test connection (same logic as connect.php)
echo "Attempting connection...\n";
try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $conn = false;
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $conn = false;
}

if (!$conn) {
    $error = mysqli_connect_error();
    $errno = mysqli_connect_errno();
    echo "❌ Connection failed\n";
    echo "Error: $error\n";
    echo "Error number: $errno\n\n";
    
    // Try connecting without database (same fallback as connect.php)
    echo "Attempting connection without database...\n";
    try {
        $conn = mysqli_connect($servername, $username, $password);
        if ($conn) {
            echo "✓ Connection to server successful (without database)\n";
            mysqli_close($conn);
        } else {
            echo "❌ Connection to server also failed\n";
            echo "Error: " . mysqli_connect_error() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
} else {
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
}

echo "\n</pre>\n";
?>

