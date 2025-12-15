<?php
// Test with exact password string (bypass .env parsing)
$exactPassword = '6>52S{23Jwex';

echo "Testing with exact password string...\n";
echo "Password: [$exactPassword]\n";
echo "Length: " . strlen($exactPassword) . "\n\n";

$conn = mysqli_connect('vdb5.pit.pair.com', 'working_64', $exactPassword, 'working_vbn');

if ($conn) {
    echo "✅ SUCCESS with exact string!\n";
    mysqli_close($conn);
} else {
    echo "❌ FAILED: " . mysqli_connect_error() . "\n";
    echo "Error code: " . mysqli_connect_errno() . "\n";
}

// Now test with .env file
echo "\n--- Testing with .env file ---\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $dbVars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'];
    foreach ($dbVars as $var) {
        if (getenv($var)) {
            putenv("$var");
        }
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            putenv("$key=$value");
        }
    }
    
    $pwd = getenv('DB_PASSWORD');
    echo "Password from .env: [$pwd]\n";
    echo "Length: " . strlen($pwd) . "\n";
    echo "Match exact? " . ($pwd === $exactPassword ? 'YES' : 'NO') . "\n";
    
    if ($pwd !== $exactPassword) {
        echo "DIFFERENCE DETECTED!\n";
        echo "Exact bytes: ";
        for($i=0; $i<strlen($exactPassword); $i++) echo ord($exactPassword[$i]) . ' ';
        echo "\n.env bytes: ";
        for($i=0; $i<strlen($pwd); $i++) echo ord($pwd[$i]) . ' ';
        echo "\n";
    }
    
    $conn2 = mysqli_connect('vdb5.pit.pair.com', 'working_64', $pwd, 'working_vbn');
    if ($conn2) {
        echo "✅ SUCCESS with .env password!\n";
        mysqli_close($conn2);
    } else {
        echo "❌ FAILED with .env: " . mysqli_connect_error() . "\n";
    }
}
?>

