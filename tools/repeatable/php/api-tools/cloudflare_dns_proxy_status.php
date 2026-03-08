<?php
declare(strict_types=1);
/**
 * Cloudflare DNS proxy status – list zones and DNS records with proxied (orange cloud) flag.
 * Use to confirm the site is behind Cloudflare proxy so you can host with a dynamic IP.
 *
 * Requires CLOUDFLARE_API_TOKEN in .env or environment (API Token with Zone:Read, DNS:Read).
 */

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

// Project root = four levels up from tools/repeatable/php/api-tools/
$projectRoot = dirname(__DIR__, 4);
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            putenv($key . '=' . $value);
        }
    }
}

$token = getenv('CLOUDFLARE_API_TOKEN');
if ($token === false || $token === '') {
    if ($is_cli) {
        echo "Error: CLOUDFLARE_API_TOKEN not set. Add it to .env or export it.\n";
    } else {
        echo '<!DOCTYPE html><html><body><p>Error: CLOUDFLARE_API_TOKEN not set. Add it to .env or server environment.</p></body></html>';
    }
    exit(1);
}

$zoneFilter = null;
if ($is_cli && isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--zone=') === 0) {
            $zoneFilter = trim(substr($arg, 7));
            break;
        }
    }
    if (in_array('--help', $argv, true)) {
        echo "Usage: php cloudflare_dns_proxy_status.php [--zone=example.com]\n";
        echo "  --zone=NAME  Optional: only show this zone (e.g. vbn-game.com).\n";
        echo "  --help       This message.\n";
        echo "\nRequires CLOUDFLARE_API_TOKEN in .env or environment.\n";
        echo "Proxied = orange cloud (traffic via Cloudflare; safe for dynamic IP).\n";
        exit(0);
    }
} else {
    $zoneFilter = isset($_GET['zone']) ? trim((string) $_GET['zone']) : null;
    if ($zoneFilter === '') {
        $zoneFilter = null;
    }
}

function cf_get(string $token, string $path): array {
    $url = 'https://api.cloudflare.com/client/v4' . $path;
    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $err !== '') {
            throw new RuntimeException('Cloudflare API request failed: ' . $url . ($err !== '' ? ' — ' . $err : ''));
        }
        if ($code >= 400) {
            $preview = is_string($raw) ? substr($raw, 0, 500) : '';
            throw new RuntimeException('Cloudflare API HTTP ' . $code . ': ' . $url . ' — ' . $preview);
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
                'timeout' => 15,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $err = error_get_last();
            $hint = (ini_get('allow_url_fopen') !== '1' && ini_get('allow_url_fopen') !== 'On')
                ? ' (allow_url_fopen is off; enable it or install curl)' : '';
            throw new RuntimeException('Cloudflare API request failed: ' . $url . $hint . ($err ? ' — ' . ($err['message'] ?? '') : ''));
        }
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success'])) {
        $errors = isset($data['errors']) && is_array($data['errors'])
            ? json_encode($data['errors']) : (is_string($raw) ? substr($raw, 0, 300) : 'Unknown error');
        throw new RuntimeException('Cloudflare API error: ' . $errors);
    }
    return $data;
}

try {
    $zonesData = cf_get($token, '/zones?per_page=50');
    $zones = $zonesData['result'] ?? [];
    if (!is_array($zones)) {
        $zones = [];
    }
    if ($zoneFilter !== null) {
        $zones = array_values(array_filter($zones, static function (array $z) use ($zoneFilter): bool {
            return isset($z['name']) && (strcasecmp($z['name'], $zoneFilter) === 0 || strcasecmp($z['name'], $zoneFilter . '.') === 0);
        }));
    }
} catch (Throwable $e) {
    if ($is_cli) {
        echo 'Error: ' . $e->getMessage() . "\n";
    } else {
        echo '<!DOCTYPE html><html><body><p>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    }
    exit(1);
}

if ($zones === []) {
    $msg = $zoneFilter !== null
        ? 'No zone found for: ' . $zoneFilter
        : 'No zones returned. Check token permissions (Zone:Read).';
    if ($is_cli) {
        echo $msg . "\n";
    } else {
        echo '<!DOCTYPE html><html><body><p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    }
    exit(0);
}

$out = [];
foreach ($zones as $zone) {
    $zoneId = $zone['id'] ?? '';
    $zoneName = $zone['name'] ?? '';
    if ($zoneId === '') {
        continue;
    }
    $recordsData = cf_get($token, '/zones/' . $zoneId . '/dns_records?per_page=100');
    $records = $recordsData['result'] ?? [];
    if (!is_array($records)) {
        $records = [];
    }
    $out[] = ['zone' => $zoneName, 'records' => $records];
}

if ($is_cli) {
    foreach ($out as $item) {
        echo "Zone: " . $item['zone'] . "\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($item['records'] as $r) {
            $type = $r['type'] ?? '';
            $name = $r['name'] ?? '';
            $content = $r['content'] ?? '';
            $proxied = !empty($r['proxied']);
            echo sprintf("%-6s %-40s %s  [%s]\n", $type, $name, $content, $proxied ? 'proxied' : 'DNS only');
        }
        echo "\n";
    }
    echo "Proxied = orange cloud: traffic goes through Cloudflare; you can change origin IP (dynamic IP) and update the A record.\n";
} else {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cloudflare DNS proxy status</title></head><body>';
    echo '<h1>Cloudflare DNS proxy status</h1>';
    foreach ($out as $item) {
        echo '<h2>Zone: ' . htmlspecialchars($item['zone'], ENT_QUOTES, 'UTF-8') . '</h2>';
        echo '<table border="1" cellpadding="4"><thead><tr><th>Type</th><th>Name</th><th>Content</th><th>Proxied</th></tr></thead><tbody>';
        foreach ($item['records'] as $r) {
            $type = $r['type'] ?? '';
            $name = $r['name'] ?? '';
            $content = $r['content'] ?? '';
            $proxied = !empty($r['proxied']);
            echo '<tr><td>' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . ($proxied ? 'Yes (orange cloud)' : 'No') . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '<p>Proxied = orange cloud: traffic goes through Cloudflare; you can host with a dynamic IP and update the A record when it changes.</p>';
    echo '</body></html>';
}
