<?php
// Quick test with the old password from db.php
$host = 'vdb5.pit.pair.com';
$user = 'working_64';
$pass = 'KevinHenry09!';
$db   = 'working_vbn';

echo "Testing with old password from db.php...\n";
$conn = mysqli_connect($host, $user, $pass, $db);

if ($conn) {
    echo "✅ SUCCESS with old password!\n";
    mysqli_close($conn);
} else {
    echo "❌ FAILED: " . mysqli_connect_error() . "\n";
}

echo "\nTesting with new password from .env...\n";
$envFile = __DIR__ . '/.env';
$newPass = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'DB_PASSWORD=') === 0) {
            list($key, $value) = explode('=', $line, 2);
            $newPass = trim(trim($value), '"\'');
            break;
        }
    }
}

$conn2 = mysqli_connect($host, $user, $newPass, $db);
if ($conn2) {
    echo "✅ SUCCESS with new password!\n";
    mysqli_close($conn2);
} else {
    echo "❌ FAILED: " . mysqli_connect_error() . "\n";
}
?>

