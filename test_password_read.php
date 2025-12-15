<?php
// Test if password is being read correctly from .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
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
}

$pwd = getenv('DB_PASSWORD');
echo "Password read: [" . $pwd . "]\n";
echo "Length: " . strlen($pwd) . "\n";
echo "Hex: " . bin2hex($pwd) . "\n";

// Test connection with exact password
$conn = mysqli_connect('vdb5.pit.pair.com', 'working_64', $pwd, 'working_vbn');
if ($conn) {
    echo "SUCCESS: Connection works!\n";
    mysqli_close($conn);
} else {
    echo "FAILED: " . mysqli_connect_error() . "\n";
    echo "Error code: " . mysqli_connect_errno() . "\n";
}
?>

