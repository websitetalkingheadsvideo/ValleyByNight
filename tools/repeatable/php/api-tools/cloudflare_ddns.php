<?php
declare(strict_types=1);
/**
 * Cloudflare Dynamic DNS: compare public IP to A record, update only if changed.
 * Run daily via cron. Logs each run with timestamp, detected IP, and action.
 *
 * Requires (in .env): CLOUDFLARE_EMAIL + CLOUDFLARE_API_KEY, or CLOUDFLARE_API_TOKEN.
 * Optional: CLOUDFLARE_DDNS_ZONE, CLOUDFLARE_DDNS_NAME (default: vbn-game.com).
 */

$is_cli = php_sapi_name() === 'cli';

function web_result(bool $success, string $message): void {
    header('Content-Type: text/html; charset=utf-8');
    $title = $success ? 'Success' : 'Failure';
    $h = $success ? 'h2' : 'h2';
    $color = $success ? 'green' : 'red';
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cloudflare DDNS – ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body>';
    echo '<h1>Cloudflare DDNS</h1><' . $h . ' style="color:' . $color . ';">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</' . $h . '>';
    echo '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
    echo '<p><a href="cloudflare_dns_proxy_status.php">DNS status</a></p></body></html>';
}

function web_exit(bool $success, string $message, int $code): void {
    web_result($success, $message);
    exit($code);
}

$projectRoot = dirname(__DIR__, 4);
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

$zoneName = getenv('CLOUDFLARE_DDNS_ZONE') ?: 'vbn-game.com';
$recordName = getenv('CLOUDFLARE_DDNS_NAME') ?: 'vbn-game.com';

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
    $msg = 'No Cloudflare auth. Set CLOUDFLARE_API_TOKEN or CLOUDFLARE_EMAIL + CLOUDFLARE_API_KEY in .env';
    log_ddns($projectRoot, 'ERROR: ' . $msg);
    if (!$is_cli) {
        web_exit(false, $msg, 1);
    }
    exit(1);
}

function log_ddns(string $projectRoot, string $message): void {
    $logDir = $projectRoot . '/tools/repeatable';
    $logFile = $logDir . '/cloudflare_ddns.log';
    if (!is_dir($logDir)) {
        $logDir = $projectRoot . '/admin';
        $logFile = $logDir . '/cloudflare_ddns.log';
    }
    $line = date('Y-m-d H:i:s') . ' ' . $message . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    if (php_sapi_name() === 'cli') {
        echo trim($message) . "\n";
    }
}

function get_public_ip(): string {
    $services = ['https://icanhazip.com', 'https://api.ipify.org', 'https://ifconfig.me/ip'];
    foreach ($services as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if ($raw !== false) {
            $ip = trim((string) $raw);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    throw new RuntimeException('Could not determine public IP');
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
        throw new RuntimeException('Cloudflare request failed: ' . $err);
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success'])) {
        $errors = $data['errors'] ?? [];
        throw new RuntimeException('Cloudflare API: ' . json_encode($errors));
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
        throw new RuntimeException('Cloudflare PATCH failed: ' . $err);
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success'])) {
        $errors = $data['errors'] ?? [];
        throw new RuntimeException('Cloudflare API: ' . json_encode($errors));
    }
    return $data;
}

try {
    $publicIp = get_public_ip();
} catch (Throwable $e) {
    log_ddns($projectRoot, 'ERROR: ' . $e->getMessage());
    if (!$is_cli) {
        web_exit(false, 'Could not get public IP: ' . $e->getMessage(), 1);
    }
    exit(1);
}

try {
    $zonesData = cf_get($auth, '/zones?name=' . urlencode($zoneName));
    $zones = $zonesData['result'] ?? [];
    if (empty($zones)) {
        throw new RuntimeException('Zone not found: ' . $zoneName);
    }
    $zoneId = $zones[0]['id'];

    $recordsData = cf_get($auth, '/zones/' . $zoneId . '/dns_records?type=A&per_page=100');
    $records = $recordsData['result'] ?? [];
    $aRecord = null;
    foreach ($records as $r) {
        $name = $r['name'] ?? '';
        if ($name === $recordName || $name === $recordName . '.') {
            $aRecord = $r;
            break;
        }
    }
    if ($aRecord === null) {
        throw new RuntimeException('A record not found for ' . $recordName);
    }

    $dnsIp = trim((string) ($aRecord['content'] ?? ''));
    if ($dnsIp === '' || !filter_var($dnsIp, FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Invalid IP in DNS record: ' . ($aRecord['content'] ?? ''));
    }

    if ($publicIp === $dnsIp) {
        log_ddns($projectRoot, "OK public_ip={$publicIp} dns_ip={$dnsIp} action=no_change");
        if (!$is_cli) {
            web_exit(true, "No change. Public IP: {$publicIp} (DNS already matches.)", 0);
        }
        exit(0);
    }

    $recordId = $aRecord['id'];
    $proxied = !empty($aRecord['proxied']);
    cf_patch($auth, '/zones/' . $zoneId . '/dns_records/' . $recordId, [
        'content' => $publicIp,
        'proxied' => $proxied,
    ]);
    log_ddns($projectRoot, "OK public_ip={$publicIp} previous_dns_ip={$dnsIp} action=updated");
    if (!$is_cli) {
        web_exit(true, "Updated. DNS A record set to {$publicIp} (was {$dnsIp}).", 0);
    }
    exit(0);

} catch (Throwable $e) {
    log_ddns($projectRoot, 'ERROR: ' . $e->getMessage());
    if (!$is_cli) {
        web_exit(false, $e->getMessage(), 1);
    }
    exit(1);
}
