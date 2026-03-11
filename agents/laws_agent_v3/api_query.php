<?php
declare(strict_types=1);

/**
 * Laws Agent v3 - API Query Endpoint
 *
 * Same process as MCP_AI_SEARCH_USAGE.md: Cloudflare AI Search (AutoRAG) with rag_id "laws-agent"
 * and query = user question. Accepts POST question and optional follow-up context.
 * Uses ai-search endpoint only (no Workers AI). Returns generated answer + sources.
 */

ob_start();

try {
    require_once __DIR__ . '/../../includes/load_env.php';
    load_project_env();
    $projectEnv = __DIR__ . '/../../.env';
    if (is_file($projectEnv) && is_readable($projectEnv)) {
        $lines = file($projectEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
                    continue;
                }
                [$key, $value] = explode('=', $trimmed, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if ($key !== '') {
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                }
            }
        }
    }
} catch (Throwable $e) {
    ob_end_clean();
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Config: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
}

$isCli = (php_sapi_name() === 'cli');
if ($isCli) {
    $question = isset($argv[1]) ? trim((string) $argv[1]) : '';
    if ($question === '') {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Usage: php api_query.php "Your question"']);
        exit(1);
    }
    $input = ['question' => $question];
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

if (!$isCli && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    $accountId = getenv('CF_ACCOUNT_ID') ?: ($_ENV['CF_ACCOUNT_ID'] ?? '');
    $token = trim((string) (getenv('CF_FUCKING_7th_API') ?: getenv('CLOUDFLARE_API_TOKEN') ?: ($_ENV['CF_FUCKING_7th_API'] ?? $_ENV['CLOUDFLARE_API_TOKEN'] ?? '')));
    echo json_encode([
        'debug'         => true,
        'token_set'     => $token !== '',
        'CF_ACCOUNT_ID' => $accountId ? 'set' : 'missing',
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = $rawInput ? json_decode($rawInput, true) : [];
if (!is_array($input)) {
    $input = [];
}

$question = isset($input['question']) ? trim((string) $input['question']) : '';
$previousQuestion = isset($input['previous_question']) ? trim((string) $input['previous_question']) : '';
$previousAnswer = isset($input['previous_answer']) ? trim((string) $input['previous_answer']) : '';

if ($question === '') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Question is required']);
    exit;
}

if ($previousQuestion !== '' && $previousAnswer !== '') {
    $question = "Previous question: {$previousQuestion}\n\nPrevious answer: {$previousAnswer}\n\nFollow-up: {$question}";
}

$apiToken = trim((string) (getenv('CF_FUCKING_7th_API') ?: getenv('CLOUDFLARE_API_TOKEN') ?: ($_ENV['CF_FUCKING_7th_API'] ?? $_ENV['CLOUDFLARE_API_TOKEN'] ?? '')));
if ($apiToken === '') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'CLOUDFLARE_API_TOKEN or CF_FUCKING_7th_API not set in .env']);
    exit;
}
$authForSearch = ['type' => 'token', 'token' => $apiToken];

// Verify token exactly like: curl "https://api.cloudflare.com/client/v4/user/tokens/verify" -H "Authorization: Bearer <token>"
$chVerify = curl_init('https://api.cloudflare.com/client/v4/user/tokens/verify');
curl_setopt_array($chVerify, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiToken],
    CURLOPT_TIMEOUT        => 10,
]);
$verifyResponse = curl_exec($chVerify);
$verifyHttpCode = (int) curl_getinfo($chVerify, CURLINFO_HTTP_CODE);
curl_close($chVerify);
$verifyData = is_string($verifyResponse) ? json_decode($verifyResponse, true) : null;
if ($verifyHttpCode !== 200 || !is_array($verifyData) || empty($verifyData['success'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Cloudflare token verify failed',
        'http_code' => $verifyHttpCode,
        'response'  => is_string($verifyResponse) ? $verifyResponse : '',
    ]);
    exit;
}

$accountId = trim((string) (getenv('CF_ACCOUNT_ID') ?: ($_ENV['CF_ACCOUNT_ID'] ?? '')));
if ($accountId === '') {
    $resolveError = '';
    $accountId = laws_agent_resolve_account_id($authForSearch, $resolveError);
    if ($accountId === '') {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        $msg = 'Set CF_ACCOUNT_ID in .env (dashboard URL or sidebar). Token has no Account read.';
        if ($resolveError !== '') {
            $msg .= ' ' . $resolveError;
        }
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}
$ragName = getenv('LAWS_AGENT_RAG_NAME');
if ($ragName === false || trim((string) $ragName) === '') {
    $ragName = getenv('CF_AUTORAG_NAME');
}
$ragName = ($ragName !== false && trim((string) $ragName) !== '') ? trim((string) $ragName) : 'laws-agent';

/**
 * Cloudflare API auth headers (same as admin/cloudflare_set_ssl_flexible.php).
 */
function laws_agent_cf_headers(array $auth): array {
    if ($auth['type'] === 'token') {
        return ['Authorization: Bearer ' . $auth['token'], 'Content-Type: application/json'];
    }
    return ['X-Auth-Email: ' . $auth['email'], 'X-Auth-Key: ' . $auth['key'], 'Content-Type: application/json'];
}

/**
 * Resolve account ID (GET /accounts). Returns [id, ''] or ['', error].
 */
function laws_agent_resolve_account_id(array $auth, ?string &$outError): string {
    $outError = '';
    $ch = curl_init('https://api.cloudflare.com/client/v4/accounts?per_page=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => laws_agent_cf_headers($auth),
        CURLOPT_TIMEOUT       => 10,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $err !== '') {
        $outError = $err ?: 'no response';
        return '';
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        $outError = 'invalid JSON';
        return '';
    }
    if (empty($data['success'])) {
        $errors = $data['errors'] ?? [];
        $outError = $httpCode . ' ' . (isset($errors[0]['message']) ? $errors[0]['message'] : json_encode($errors));
        return '';
    }
    $result = $data['result'] ?? null;
    if (!is_array($result) || count($result) === 0) {
        $outError = 'token has no accounts or wrong response shape';
        return '';
    }
    $first = $result[0];
    $id = isset($first['id']) ? trim((string) $first['id']) : '';
    if ($id === '') {
        $outError = 'result[0].id missing';
        return '';
    }
    return $id;
}

/**
 * Cloudflare AI Search (ai-search endpoint). PHP cURL only; no shell.
 * Returns ['answer' => string, 'sources' => array of {book, page, category, system, excerpt, relevance}].
 */
function laws_agent_ai_search(string $question, string $accountId, string $ragName, array $auth, int $limit = 10): array {
    $token = $auth['type'] === 'token' ? $auth['token'] : '';
    if ($token === '') {
        throw new RuntimeException('Cloudflare AI Search failed: token required');
    }
    // Official path: autorag/rags/{name}/ai-search (docs say endpoints still use autorag)
    $url = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/autorag/rags/' . rawurlencode($ragName) . '/ai-search';
    $body = [
        'query'             => $question,
        'rewrite_query'      => false,
        'max_num_results'    => min($limit, 50),
        'ranking_options'    => ['score_threshold' => 0.1],
    ];
    $payload = json_encode($body);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err !== '') {
        throw new RuntimeException('Cloudflare AI Search failed: ' . $err);
    }
    if ($response === false || $response === '') {
        throw new RuntimeException('Cloudflare AI Search failed: no response');
    }
    $data = json_decode($response, true);
    if (!$data || empty($data['success']) || !isset($data['result'])) {
        $parts = ['Cloudflare AI Search error'];
        $parts[] = 'http_code=' . $httpCode;
        $parts[] = 'url=' . $url;
        if (is_array($data) && !empty($data['errors'])) {
            foreach ($data['errors'] as $e) {
                $code = isset($e['code']) ? (int) $e['code'] : 0;
                $m = isset($e['message']) ? $e['message'] : json_encode($e);
                $parts[] = ($code ? "[{$code}] " : '') . $m;
            }
        }
        $parts[] = 'raw_response=' . mb_substr((string) $response, 0, 1000);
        throw new RuntimeException(implode(' ', $parts));
    }
    $result = $data['result'];
    $answer = isset($result['response']) ? trim((string) $result['response']) : '';
    $rows = [];
    $rawData = $result['data'] ?? [];
    foreach (is_array($rawData) ? $rawData : [] as $item) {
        $attrs = $item['attributes'] ?? $item['metadata'] ?? [];
        $content = $item['content'] ?? [];
        $text = '';
        if (is_array($content)) {
            foreach ($content as $c) {
                if (isset($c['type']) && $c['type'] === 'text' && isset($c['text'])) {
                    $text .= $c['text'] . "\n";
                }
            }
        }
        $text = trim($text) ?: (string) ($item['text'] ?? $item['page_text'] ?? $item['content'] ?? '');
        $pageNum = (int) ($attrs['page_number'] ?? $attrs['page'] ?? $item['page_number'] ?? $item['page'] ?? 0);
        $bookTitle = $attrs['book_title'] ?? $attrs['title'] ?? $item['filename'] ?? $item['title'] ?? $item['file_id'] ?? 'Unknown';
        $rows[] = [
            'book'      => $bookTitle,
            'page'      => $pageNum,
            'category'  => $attrs['category'] ?? $item['category'] ?? 'Other',
            'system'    => $attrs['system_type'] ?? $item['system_type'] ?? 'Unknown',
            'excerpt'   => mb_strlen($text) > 300 ? mb_substr($text, 0, 300) . '...' : $text,
            'relevance' => isset($item['score']) && is_numeric($item['score']) ? (float) $item['score'] : 0.0,
        ];
    }
    return ['answer' => $answer, 'sources' => array_slice($rows, 0, $limit)];
}

$rawQuestion = isset($input['question']) ? trim((string) $input['question']) : $question;

try {
    $aiSearchResult = laws_agent_ai_search($question, $accountId, $ragName, $authForSearch, 10);
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$answer = $aiSearchResult['answer'];
$sources = $aiSearchResult['sources'];
if ($answer === '') {
    $answer = "I couldn't find any relevant information in the rulebooks to answer that question. Please try rephrasing or being more specific.";
}

ob_end_clean();
echo json_encode([
    'success'       => true,
    'question'      => $rawQuestion,
    'answer'        => $answer,
    'sources'       => $sources,
    'ai_model'      => 'Cloudflare AI Search',
    'searched'      => true,
    'results_found' => count($sources),
]);
