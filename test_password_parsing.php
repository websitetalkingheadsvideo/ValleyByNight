<?php
// Quick test to see exactly what password is being parsed
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'DB_PASSWORD=') === 0) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value);
            echo "Password value length: " . strlen($value) . "\n";
            echo "Password value (raw): [" . $value . "]\n";
            echo "Password value (hex): " . bin2hex($value) . "\n";
            echo "Password with quotes removed: [" . trim($value, '"\'') . "]\n";
            
            // Test what the parser would actually use
            $parsed = trim($value, '"\'');
            echo "Parsed password length: " . strlen($parsed) . "\n";
            echo "Parsed password: [" . $parsed . "]\n";
            break;
        }
    }
} else {
    echo ".env file not found\n";
}
?>

