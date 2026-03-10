<?php
declare(strict_types=1);

/**
 * Laws Agent v3 - API Query Endpoint
 *
 * Accepts POST question and optional follow-up context.
 * Uses Cloudflare AI Search (AutoRAG) ai-search endpoint only: same as MCP, returns generated answer + sources. No Workers AI, no extra token.
 */

ob_start();

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
                }
            }
        }
    }
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Config: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug'])) {
    ob_end_clean();
    $cfAccount = getenv('CF_ACCOUNT_ID');
    $cfRag = getenv('CF_AUTORAG_NAME');
    $cfToken = getenv('CLOUDFLARE_API_TOKEN');
    echo json_encode([
        'debug'                 => true,
        'CF_ACCOUNT_ID'         => $cfAccount ? 'set' : 'missing',
        'CF_AUTORAG_NAME'       => $cfRag ? 'set' : 'missing',
        'CLOUDFLARE_API_TOKEN'  => $cfToken ? 'set' : 'missing',
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
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
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Question is required']);
    exit;
}

if ($previousQuestion !== '' && $previousAnswer !== '') {
    $question = "Previous question: {$previousQuestion}\n\nPrevious answer: {$previousAnswer}\n\nFollow-up: {$question}";
}

$accountId = getenv('CF_ACCOUNT_ID');
$ragName = getenv('CF_AUTORAG_NAME');
$cfToken = getenv('CLOUDFLARE_API_TOKEN');

if ($accountId !== false && $accountId !== '') {
    $accountId = trim((string) $accountId);
} else {
    $accountId = '';
}
if ($ragName !== false && $ragName !== '') {
    $ragName = trim((string) $ragName);
} else {
    $ragName = '';
}
if ($cfToken === false || $cfToken === '') {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'CLOUDFLARE_API_TOKEN is required in .env']);
    exit;
}
$cfToken = trim((string) $cfToken);
$resolveError = '';
if ($accountId === '') {
    $resolved = laws_agent_resolve_account_id($cfToken, $resolveError);
    if ($resolved === '') {
        ob_end_clean();
        http_response_code(500);
        $msg = 'Set CF_ACCOUNT_ID in .env. Get it: https://dash.cloudflare.com → right sidebar "Account ID" (or copy the hex from any dashboard URL after /).';
        if ($resolveError !== '') {
            $msg .= ' Token lookup failed: ' . $resolveError;
        }
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
    $accountId = $resolved;
}

if ($ragName === '') {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'CF_AUTORAG_NAME is required in .env (e.g. your AI Search / laws-agent RAG name).']);
    exit;
}

/**
 * Resolve account ID from token (GET /accounts). Returns [id, ''] or ['', error].
 */
function laws_agent_resolve_account_id(string $token, ?string &$outError): string {
    $outError = '';
    $ch = curl_init('https://api.cloudflare.com/client/v4/accounts?per_page=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
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
 * Cloudflare AI Search (AutoRAG) ai-search endpoint: search + generated answer in one call (same as MCP).
 * Returns ['answer' => string, 'sources' => array of {book, page, category, system, excerpt, relevance}].
 */
function laws_agent_ai_search(string $question, string $accountId, string $ragName, string $token, int $limit = 10): array {
    $url = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/autorag/rags/' . rawurlencode($ragName) . '/ai-search';
    $body = [
        'query'             => $question,
        'rewrite_query'      => true,
        'max_num_results'    => min($limit, 50),
        'ranking_options'    => ['score_threshold' => 0.1],
    ];
    $payload = json_encode($body);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err !== '') {
        throw new RuntimeException('Cloudflare AI Search failed: ' . $err);
    }
    if ($response === false) {
        throw new RuntimeException('Cloudflare AI Search failed: no response');
    }
    $data = json_decode($response, true);
    if (!$data || empty($data['success']) || !isset($data['result'])) {
        $msg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : ('HTTP ' . $httpCode);
        throw new RuntimeException('Cloudflare AI Search failed: ' . $msg);
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

/**
 * Build context string from search results for the LLM.
 */
function laws_agent_build_context(array $results): string {
    if (count($results) === 0) {
        return 'No relevant rulebook content found.';
    }
    $out = "Context from VTM/MET rulebooks:\n\n";
    foreach ($results as $i => $r) {
        $excerpt = mb_strlen($r['page_text']) > 800 ? mb_substr($r['page_text'], 0, 800) . '...' : $r['page_text'];
        $out .= sprintf(
            "[Source %d] %s (Page %d, Category: %s, System: %s):\n%s\n\n",
            $i + 1,
            $r['book_title'],
            $r['page_number'],
            $r['category'],
            $r['system_type'],
            $excerpt
        );
    }
    return $out;
}

/**
 * Cloudflare Workers AI - run LLM (messages: system + user).
 */
function laws_agent_cloudflare_llm(string $question, string $context, string $accountId, string $token, string $model): array {
    $systemPrompt = "You are a helpful assistant answering questions about Vampire: The Masquerade and Mind's Eye Theatre rules. Baseline edition is Laws of the Night Revised. Do not reference V5 or the Second Inquisition. Answer based on the provided context from official rulebooks. Always cite your sources by including [Book Name, Page X] citations in your response.

IMPORTANT: When asked about \"Camarilla traditions\" or \"the Traditions,\" you should always mention the Six Traditions that govern vampire society:
1. The Masquerade - Conceal vampiric nature from mortals at all times
2. Domain - A Prince (or rightful lord) holds the city; respect granted rights
3. Progeny - Do not Embrace without the Prince's explicit leave
4. Accounting - A sire is responsible for a childe until formal Release
5. Hospitality - Present yourself to the Prince upon entering a city
6. Destruction - Only the Prince (or empowered elder) may grant Final Death

These are fundamental laws of the Camarilla (LotN Revised), even if specific details are not found in the search results.";
    $body = [
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question . "\n\n" . $context],
        ],
        'max_tokens' => 2000,
    ];
    $url = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/ai/run/' . rawurlencode($model);
    $payload = json_encode($body);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err !== '') {
        throw new RuntimeException('Cloudflare Workers AI failed: ' . $err);
    }
    if ($response === false) {
        throw new RuntimeException('Cloudflare Workers AI failed: no response');
    }
    $data = json_decode($response, true);
    if (!$data || empty($data['success']) || !isset($data['result']['response'])) {
        $msg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : ('HTTP ' . $httpCode);
        throw new RuntimeException('Cloudflare Workers AI error: ' . $msg);
    }
    $text = trim((string) $data['result']['response']);
    if ($text === '') {
        throw new RuntimeException('Cloudflare Workers AI returned empty response');
    }
    return ['answer' => $text, 'model' => $model];
}

$searchResults = [];
if ($useCloudflareRag) {
    try {
        $searchResults = laws_agent_cloudflare_search($question, $accountId, $ragName, $cfToken, 5);
    } catch (Throwable $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$isTraditionQuestion = (bool) preg_match('/\b(traditions?|masquerade|domain|progeny|accounting|hospitality|destruction)\b/i', $question);
$rawQuestion = isset($input['question']) ? trim((string) $input['question']) : $question;

if (count($searchResults) === 0 && !$isTraditionQuestion) {
    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'question' => $rawQuestion,
        'answer'    => "I couldn't find any relevant information in the rulebooks to answer that question. Please try rephrasing or being more specific.",
        'sources'  => [],
        'ai_model' => $cfModel,
        'searched' => true,
        'results_found' => 0,
    ]);
    exit;
}

$context = count($searchResults) > 0
    ? laws_agent_build_context($searchResults)
    : "No specific rulebook excerpts found, but answer based on fundamental knowledge of the Six Traditions.";

try {
    $aiResponse = laws_agent_cloudflare_llm($question, $context, $accountId, $cfToken, $cfModel);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$sources = [];
foreach ($searchResults as $r) {
    $excerpt = mb_strlen($r['page_text']) > 300 ? mb_substr($r['page_text'], 0, 300) . '...' : $r['page_text'];
    $sources[] = [
        'book'      => $r['book_title'],
        'page'      => $r['page_number'],
        'category'  => $r['category'],
        'system'    => $r['system_type'],
        'excerpt'   => $excerpt,
        'relevance' => $r['relevance'],
    ];
}

ob_end_clean();
echo json_encode([
    'success'       => true,
    'question'      => $rawQuestion,
    'answer'        => $aiResponse['answer'],
    'sources'       => $sources,
    'ai_model'      => $aiResponse['model'],
    'searched'      => true,
    'results_found' => count($searchResults),
]);
