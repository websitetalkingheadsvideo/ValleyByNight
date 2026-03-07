<?php
declare(strict_types=1);

/**
 * Minimal Supabase REST client for runtime PHP endpoints.
 */

/**
 * Load KEY=VALUE lines from a .env file into getenv() via putenv().
 * Skips empty lines, comments, and lines without '='.
 */
function supabase_load_env_file(string $envFile): void {
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

function supabase_load_env_once(): void {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envFile = dirname(__DIR__) . '/.env';
    supabase_load_env_file($envFile);

    if (getenv('SUPABASE_URL') !== false && trim((string) getenv('SUPABASE_URL')) !== '') {
        return;
    }
    $entryScript = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
    if ($entryScript !== '' && is_file($entryScript)) {
        $entryDir = dirname($entryScript);
        $candidates = [$entryDir . '/../.env', $entryDir . '/.env'];
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                supabase_load_env_file($resolved);
                if (getenv('SUPABASE_URL') !== false && trim((string) getenv('SUPABASE_URL')) !== '') {
                    return;
                }
            }
        }
    }
}

function supabase_base_url(): string {
    supabase_load_env_once();
    $url = getenv('SUPABASE_URL');
    if (!is_string($url) || trim($url) === '') {
        throw new RuntimeException('SUPABASE_URL is missing.');
    }
    return rtrim($url, '/');
}

function supabase_api_key(): string {
    supabase_load_env_once();

    $serviceRoleKey = getenv('SUPABASE_SERVICE_ROLE_KEY');
    if (is_string($serviceRoleKey) && trim($serviceRoleKey) !== '') {
        return $serviceRoleKey;
    }

    $key = getenv('SUPABASE_KEY');
    if (!is_string($key) || trim($key) === '') {
        throw new RuntimeException('SUPABASE_KEY is missing.');
    }
    return $key;
}

/**
 * @param array<string,string> $query
 * @param array<mixed>|null $payload
 * @param array<int,string> $extraHeaders
 * @return array{status:int,data:mixed,error:?string}
 */
function supabase_rest_request(string $method, string $path, array $query = [], ?array $payload = null, array $extraHeaders = []): array {
    $base = supabase_base_url();
    $key = supabase_api_key();

    $url = $base . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
    ];
    if (!empty($extraHeaders)) {
        $headers = array_merge($headers, $extraHeaders);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize cURL.');
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($payload !== null) {
        $json = json_encode($payload);
        if ($json === false) {
            throw new RuntimeException('Failed to encode Supabase payload JSON.');
        }
        $options[CURLOPT_POSTFIELDS] = $json;
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'status' => $status,
            'data' => null,
            'error' => $curlError !== '' ? $curlError : 'Supabase request failed.',
        ];
    }

    $decoded = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        $errorMessage = is_array($decoded) && isset($decoded['message']) ? (string) $decoded['message'] : $raw;
        return [
            'status' => $status,
            'data' => $decoded,
            'error' => $errorMessage,
        ];
    }

    return [
        'status' => $status,
        'data' => $decoded,
        'error' => null,
    ];
}

/**
 * @param array<string,string> $query
 * @return array<int,array<string,mixed>>
 */
function supabase_table_get(string $table, array $query): array {
    $result = supabase_rest_request('GET', '/rest/v1/' . $table, $query);
    if ($result['error'] !== null) {
        throw new RuntimeException('Supabase GET failed for table ' . $table . ': ' . $result['error']);
    }

    if (!is_array($result['data'])) {
        throw new RuntimeException('Supabase returned invalid response for table ' . $table . '.');
    }

    return $result['data'];
}

