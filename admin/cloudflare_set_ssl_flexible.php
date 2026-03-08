<?php
declare(strict_types=1);
/**
 * Set Cloudflare zone SSL/TLS mode to Flexible (origin on HTTP, so no 522 when origin has no HTTPS).
 * Uses .env: CLOUDFLARE_EMAIL + CLOUDFLARE_API_KEY or CLOUDFLARE_API_TOKEN.
 */
header('Content-Type: text/html; charset=utf-8');

$projectRoot = dirname(__DIR__);
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) { continue; }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim(trim($value), '"\''));
        }
    }
}

$token = getenv('CLOUDFLARE_API_TOKEN');
$email = getenv('CLOUDFLARE_EMAIL');
$apiKey = getenv('CLOUDFLARE_API_KEY');
$auth = null;
if ($token !== false && $token !== '') {
    $auth = ['type' => 'token', 'token' => $token];
} elseif ($email !== false && $email !== '' && $apiKey !== false && $apiKey !== '') {
    $auth = ['type' => 'key', 'email' => $email, 'key' => $apiKey];
}
if ($auth === null) {
    echo '<!DOCTYPE html><html><body><p>Error: Set CLOUDFLARE_API_TOKEN or CLOUDFLARE_EMAIL + CLOUDFLARE_API_KEY in .env</p></body></html>';
    exit(1);
}

function cf_headers(array $auth): array {
    if ($auth['type'] === 'token') {
        return ['Authorization: Bearer ' . $auth['token'], 'Content-Type: application/json'];
    }
    return ['X-Auth-Email: ' . $auth['email'], 'X-Auth-Key: ' . $auth['key'], 'Content-Type: application/json'];
}

function cf_get(array $auth, string $path): array {
    $url = 'https://api.cloudflare.com/client/v4' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => cf_headers($auth),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $err !== '') {
        throw new RuntimeException('Request failed: ' . $err);
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success'])) {
        $errors = $data['errors'] ?? [];
        throw new RuntimeException('API error: ' . json_encode($errors));
    }
    return $data;
}

function cf_patch(array $auth, string $path, array $body): array {
    $url = 'https://api.cloudflare.com/client/v4' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => cf_headers($auth),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $err !== '') {
        throw new RuntimeException('Request failed: ' . $err);
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success'])) {
        $errors = $data['errors'] ?? [];
        throw new RuntimeException('API error: ' . json_encode($errors));
    }
    return $data;
}

$zoneName = 'vbn-game.com';
try {
    $zonesData = cf_get($auth, '/zones?name=' . urlencode($zoneName));
    $zones = $zonesData['result'] ?? [];
    if (empty($zones)) {
        throw new RuntimeException('Zone not found: ' . $zoneName);
    }
    $zoneId = $zones[0]['id'];

    $current = cf_get($auth, '/zones/' . $zoneId . '/settings/ssl');
    $currentValue = $current['result']['value'] ?? '';
    if (strtolower($currentValue) === 'flexible') {
        echo '<!DOCTYPE html><html><body><h1>Cloudflare SSL</h1><p>SSL mode is already <strong>Flexible</strong>.</p><p><a href="cloudflare_dns_proxy_status.php">DNS status</a></p></body></html>';
        exit(0);
    }

    cf_patch($auth, '/zones/' . $zoneId . '/settings/ssl', ['value' => 'flexible']);

    echo '<!DOCTYPE html><html><body><h1>Cloudflare SSL</h1><p><strong>Success.</strong> SSL/TLS mode set to <strong>Flexible</strong>. Cloudflare will use HTTP (port 80) to your origin.</p><p><a href="cloudflare_dns_proxy_status.php">DNS status</a></p></body></html>';
} catch (Throwable $e) {
    echo '<!DOCTYPE html><html><body><h1>Cloudflare SSL</h1><p>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p><p><a href="cloudflare_dns_proxy_status.php">DNS status</a></p></body></html>';
    exit(1);
}
