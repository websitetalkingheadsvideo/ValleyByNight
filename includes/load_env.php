<?php
declare(strict_types=1);

/**
 * Load project environment variables from .env into getenv().
 */
function load_project_env(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $envFile = dirname(__DIR__) . '/.env';

    if (!is_file($envFile) || !is_readable($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $envKey = trim($key);
        $envValue = trim($value, " \t\n\r\0\x0B\"'");

        if ($envKey !== '') {
            putenv($envKey . '=' . $envValue);
        }
    }
}
