<?php
declare(strict_types=1);

// Start output buffering to catch any errors
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set error handler to return JSON on fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ob_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        $errorMsg = 'Fatal error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line'];
        error_log('Laws Agent Fatal Error: ' . $errorMsg);
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
        ]);
        exit;
    }
});

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://192.168.0.155', 'http://localhost', 'http://127.0.0.1'];

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
    }
    http_response_code(200);
    ob_end_clean();
    exit;
}

// Set CORS headers for actual request (only if cross-origin)
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/connect.php';
    // Load anthropic helper if it exists (optional - AI is optional)
    $anthropicHelperPath = __DIR__ . '/../../includes/anthropic_helper.php';
    if (file_exists($anthropicHelperPath)) {
        require_once $anthropicHelperPath;
    }
    require_once __DIR__ . '/markdown_loader.php';
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load required files: ' . $e->getMessage(),
    ]);
    ob_end_flush();
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * @return array{authenticated:bool,verified:bool,user_id?:int,error?:string,http_code?:int}
 */
function check_authentication(mysqli $conn): array
{
    if (!isset($_SESSION['user_id'])) {
        return [
            'authenticated' => false,
            'verified' => false,
            'error' => 'Not logged in',
            'http_code' => 401,
        ];
    }

    $userId = $_SESSION['user_id'];
    $result = db_fetch_one(
        $conn,
        'SELECT email_verified FROM users WHERE id = ?',
        'i',
        [$userId]
    );

    if (!$result) {
        return [
            'authenticated' => false,
            'verified' => false,
            'error' => 'User not found',
            'http_code' => 401,
        ];
    }

    if (!$result['email_verified']) {
        return [
            'authenticated' => true,
            'verified' => false,
            'error' => 'Email verification required',
            'http_code' => 403,
        ];
    }

    return [
        'authenticated' => true,
        'verified' => true,
        'user_id' => (int) $userId,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
/**
 * Filter out false positives for discipline searches
 * Checks if page actually discusses the discipline, not just mentions it
 */
function is_relevant_discipline_page(string $pageText, string $disciplineName): bool
{
    $text = strtolower($pageText);
    $discipline = strtolower($disciplineName);
    
    // Must contain the discipline name as a whole word (not part of another word)
    // Use word boundaries to ensure exact match
    if (!preg_match('/\b' . preg_quote($discipline, '/') . '\b/i', $pageText)) {
        return false;
    }
    
    // Check for discipline-specific patterns that indicate actual discussion
    $disciplineIndicators = [
        // Level indicators (most reliable)
        '/\blevel\s+[1-5]\b/i',
        '/\blevel\s+[ivx]+/i',
        // Discipline power structure
        '/\b(power|ability|discipline)\s+.*' . preg_quote($discipline, '/') . '/i',
        '/' . preg_quote($discipline, '/') . '\s+.*\b(power|ability|discipline)\b/i',
        // System/challenge text
        '/\b(system|challenge|difficulty|trait|roll)\b/i',
        // Section headers
        '/^#+\s*' . preg_quote($discipline, '/') . '/im',
        '/' . preg_quote($discipline, '/') . '\s*discipline/im',
    ];
    
    $indicatorCount = 0;
    foreach ($disciplineIndicators as $pattern) {
        if (preg_match($pattern, $pageText)) {
            $indicatorCount++;
        }
    }
    
    // Need at least 2 indicators to be considered relevant
    if ($indicatorCount >= 2) {
        return true;
    }
    
    // Check for false positive patterns (words that contain the discipline name)
    $falsePositivePatterns = [
        '/\bdifficulty\b/i',  // Contains "celerity" but not about Celerity
        '/\bpiscine\b/i',      // Nosferatu merit, not a discipline
        '/\b(blind|fighting)\b/i',  // Blind fighting, not discipline
        '/\b(errata|apolog|mistake)\b/i',  // Errata pages
        '/\b(animism|spirit|form)\b/i',  // Other concepts
    ];
    
    $falsePositiveCount = 0;
    foreach ($falsePositivePatterns as $pattern) {
        if (preg_match($pattern, $pageText)) {
            $falsePositiveCount++;
        }
    }
    
    // If we have false positive patterns and no strong discipline indicators, reject
    if ($falsePositiveCount > 0 && $indicatorCount < 2) {
        return false;
    }
    
    // Check if discipline appears in context of other disciplines (reference table, etc.)
    // This is less reliable but can help filter out index/reference pages
    $otherDisciplines = ['potence', 'fortitude', 'auspex', 'dominate', 'presence', 'obfuscate', 'protean', 'animalism'];
    $otherDisciplineCount = 0;
    foreach ($otherDisciplines as $other) {
        if (stripos($pageText, $other) !== false && $other !== $discipline) {
            $otherDisciplineCount++;
        }
    }
    
    // If page mentions 3+ other disciplines but few discipline indicators, likely a reference/index page
    if ($otherDisciplineCount >= 3 && $indicatorCount < 2) {
        return false;
    }
    
    // Default: if it has the discipline name and at least one indicator, allow it
    return $indicatorCount >= 1;
}

function search_rulebooks(mysqli $conn, string $query, ?string $category = null, ?string $system = null, int $limit = 5): array
{
    // Detect if this is a discipline query
    $isDisciplineQuery = preg_match('/\b(celerity|potence|fortitude|auspex|dominate|presence|obfuscate|protean|animalism|thaumaturgy|necromancy|serpentis|vicissitude|chimestry|dementation|quietus|obtenebration)\b/i', $query, $disciplineMatches);
    $disciplineName = $isDisciplineQuery ? $disciplineMatches[1] : null;
    
    // Use BOOLEAN MODE for discipline queries to require exact word match
    // Safe: searchMode is validated to be one of two fixed strings
    $searchMode = $isDisciplineQuery ? 'BOOLEAN' : 'NATURAL LANGUAGE';
    $searchQuery = $isDisciplineQuery ? ('+"' . $disciplineName . '"') : $query;
    
    // Build SQL with validated search mode
    if ($isDisciplineQuery) {
        $sql = <<<SQL
            SELECT
                r.id AS rulebook_id,
                r.title AS book_title,
                r.category,
                r.system_type,
                rp.page_number,
                rp.page_text,
                MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE) AS relevance
            FROM rulebook_pages rp
            JOIN rulebooks r ON rp.rulebook_id = r.id
            WHERE MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE)
        SQL;
    } else {
        $sql = <<<SQL
            SELECT
                r.id AS rulebook_id,
                r.title AS book_title,
                r.category,
                r.system_type,
                rp.page_number,
                rp.page_text,
                MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
            FROM rulebook_pages rp
            JOIN rulebooks r ON rp.rulebook_id = r.id
            WHERE MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE)
        SQL;
    }

    $params = [$searchQuery, $searchQuery];
    $types = 'ss';

    if ($category) {
        $sql .= ' AND r.category = ?';
        $params[] = $category;
        $types .= 's';
    }

    if ($system) {
        $sql .= ' AND r.system_type = ?';
        $params[] = $system;
        $types .= 's';
    }

    // Get more results initially so we can filter
    $initialLimit = $isDisciplineQuery ? ($limit * 3) : $limit;
    $sql .= ' ORDER BY relevance DESC LIMIT ?';
    $params[] = $initialLimit;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        // Filter discipline results for relevance
        if ($isDisciplineQuery && $disciplineName) {
            if (!is_relevant_discipline_page((string) $row['page_text'], $disciplineName)) {
                continue; // Skip false positives
            }
        }
        $results[] = $row;
        
        // Stop once we have enough filtered results
        if (count($results) >= $limit) {
            break;
        }
    }

    return $results;
}

function question_indicates_traditions(string $question): bool
{
    $q = strtolower($question);
    if (strpos($q, 'tradition') !== false) {
        return true;
    }

    $keywords = ['masquerade', 'domain', 'progeny', 'accounting', 'hospitality', 'destruction'];
    $hits = 0;

    foreach ($keywords as $keyword) {
        if (strpos($q, $keyword) !== false) {
            $hits++;
        }
    }

    return $hits >= 2;
}

/**
 * @return array<int, array<string, mixed>>
 */
function search_rulebooks_traditions(mysqli $conn, ?string $category = null, ?string $system = null, int $limit = 20): array
{
    $boolean = '+Tradition* Masquerade Domain Progeny Accounting Hospitality Destruction "Tradition of the" "the Six Traditions"';

    $sql = <<<SQL
        SELECT
            r.id AS rulebook_id,
            r.title AS book_title,
            r.category,
            r.system_type,
            rp.page_number,
            rp.page_text,
            MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE) AS relevance,
            (
                (rp.page_text LIKE '%Tradition%') +
                2 * (rp.page_text REGEXP 'Tradition of the (Masquerade|Domain|Progeny|Accounting|Hospitality|Destruction)')
            ) AS tboost
        FROM rulebook_pages rp
        JOIN rulebooks r ON rp.rulebook_id = r.id
        WHERE MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE)
    SQL;

    $params = [$boolean, $boolean];
    $types = 'ss';

    if ($category) {
        $sql .= ' AND r.category = ?';
        $params[] = $category;
        $types .= 's';
    }

    if ($system) {
        $sql .= ' AND r.system_type = ?';
        $params[] = $system;
        $types .= 's';
    }

    $sql .= ' ORDER BY (relevance + tboost) DESC LIMIT ?';
    $params[] = $limit;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    return $results;
}

function clean_utf8_string(string $text): string
{
    // Remove invalid UTF-8 bytes using regex (more reliable than iconv/mb_convert_encoding)
    // This regex matches valid UTF-8 sequences and removes everything else
    $text = preg_replace('/(?>[\x00-\x1F\x7F]+|[\xC0-\xC1]|[\xF5-\xFF]|[\x80-\xBF](?![\x80-\xBF])|(?<![\xC2-\xDF]|[\xE0-\xEF]|[\xF0-\xF4])[\x80-\xBF]|[\xC2-\xDF](?![\x80-\xBF])|[\xE0-\xEF](?![\x80-\xBF]{2})|[\xF0-\xF4](?![\x80-\xBF]{3}))/', '', $text);
    
    // Fallback: use mb_convert_encoding if available
    if (function_exists('mb_convert_encoding')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
    
    // Remove control characters (keep \n, \r, \t)
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    return $text;
}

/**
 * Clean OCR artifacts from text
 * Removes repeated words, patterns, and random character sequences
 * Ignores page headers, footers, running titles, and repeated section labels
 */
function clean_ocr_artifacts(string $text): string
{
    // Ignore page headers, footers, running titles, and repeated section labels
    // Remove repeated concatenated words (e.g., "RendezvousRendezvous" → "Rendezvous")
    // Matches a word repeated 2+ times when concatenated (no spaces)
    // Use word boundaries and case-insensitive matching
    $text = preg_replace('/([A-Za-z]{3,})\1+/i', '$1', $text);
    
    // Remove repeated uppercase patterns like "SABBATABBATABBAT" → "SABBAT"
    // Handles cases where uppercase words are repeated 3+ times
    $text = preg_replace('/([A-Z]{4,})\1{2,}/', '$1', $text);
    
    // Remove repeated number patterns concatenated with text (e.g., "1404140Laws" → "Laws")
    // Matches 2+ digit numbers repeated 2+ times concatenated with letters
    $text = preg_replace('/(\d{2,})\1+([A-Za-z])/', '$2', $text);
    
    // Add space between numbers and letters when concatenated (e.g., "80Mind" → "80 Mind")
    // But only if it looks like a page number (1-3 digits) followed by capital letter
    $text = preg_replace('/(\d{1,3})([A-Z][a-z])/', '$1 $2', $text);
    
    // Remove standalone repeated numbers (page number artifacts like "1404140")
    // Matches 2+ digit numbers repeated 2+ times
    $text = preg_replace('/\b(\d{2,})\1+\b/', '', $text);
    
    // Remove patterns like "TH EH EH EH EH" (pattern with spaces)
    // Matches 1-4 uppercase letters, optional space + 1-4 letters, repeated 2+ times
    $text = preg_replace('/\b([A-Z]{1,4}(?:\s+[A-Z]{1,4})?)(?:\s+\1){2,}\b/', '$1', $text);
    
    // Remove excessive repeated single characters with spaces (e.g., "E S S S S" → "")
    // Matches single letter repeated 3+ times with spaces between
    $text = preg_replace('/\b([A-Za-z])(?:\s+\1){3,}\b/', '', $text);
    
    // Remove patterns like "TH EH EH EH EH" where first part might be missing spaces
    // Handle "THEH EH EH EH EH" type patterns
    $text = preg_replace('/([A-Z]{2,4})([A-Z]{1,3})\s+(\2\s+){3,}/', '$1', $text);
    
    // Clean up multiple spaces left after removals
    $text = preg_replace('/\s{2,}/', ' ', $text);
    
    return trim($text);
}

function clean_utf8_array(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $data[$key] = clean_utf8_string($value);
        } elseif (is_array($value)) {
            $data[$key] = clean_utf8_array($value);
        }
    }
    return $data;
}

/**
 * Format AI response for better readability
 * - Fixes nested source citations
 * - Adds paragraph breaks before/after source citations
 * - Adds paragraph breaks for better readability
 * - Converts to HTML paragraphs
 */
function format_ai_response(string $text): string
{
    // Don't escape HTML yet - we need to work with the text first
    
    // Fix nested source citations like "(Source (Source 5: ...))" → "(Source 5: ...)"
    // Handle multiple levels of nesting
    $text = preg_replace('/\(Source\s*\(Source\s*\(Source\s*(\d+[^)]+)\)\)\)/', '(Source $1)', $text);
    $text = preg_replace('/\(Source\s*\(Source\s*(\d+[^)]+)\)\)/', '(Source $1)', $text);
    
    // Add paragraph break before each new source citation (start new paragraph at each source)
    $text = preg_replace('/\s*(\(Source\s*\d+[^)]+\))/', '</p><p>$1', $text);
    
    // Add paragraph break after source citations (before next sentence starts)
    $text = preg_replace('/(\(Source\s*\d+[^)]+\))\s+/', '$1 </p><p>', $text);
    
    // Split long paragraphs: Add paragraph breaks after sentences (every 2-3 sentences)
    // Pattern: Period/question/exclamation followed by space and capital letter (new sentence)
    // But only if we're more than 200 chars into the current paragraph
    // Split into sentences first
    $sentences = preg_split('/([.!?]\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $paragraphs = [];
    $currentParagraph = '';
    $charCount = 0;
    
    foreach ($sentences as $sentence) {
        $currentParagraph .= $sentence;
        $charCount += strlen($sentence);
        
        // If we've accumulated ~300+ chars and this sentence ends with punctuation, start new paragraph
        if ($charCount > 300 && preg_match('/[.!?]\s*$/', $sentence)) {
            $paragraphs[] = trim($currentParagraph);
            $currentParagraph = '';
            $charCount = 0;
        }
    }
    if (!empty($currentParagraph)) {
        $paragraphs[] = trim($currentParagraph);
    }
    
    // Rejoin with paragraph tags
    $text = '<p>' . implode('</p><p>', array_filter($paragraphs)) . '</p>';
    
    // Clean up empty paragraphs
    $text = preg_replace('/<p>\s*<\/p>/', '', $text);
    
    // Now escape HTML in the content (but preserve our paragraph tags)
    // This is tricky - we need to escape content but not tags
    $text = preg_replace_callback('/<p>(.*?)<\/p>/s', function($matches) {
        $content = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        return '<p>' . $content . '</p>';
    }, $text);
    
    // Style source citations with color (after escaping, simple regex replacement)
    // Pattern: (Source X: Book Name, Page Y)
    $text = preg_replace(
        '/\(Source\s*(\d+[^)]+)\)/',
        '<span style="color: #6c757d; font-weight: 500;">(Source $1)</span>',
        $text
    );
    
    return $text;
}

function extract_excerpt(string $text, int $maxChars = 800): string
{
    // Clean UTF-8 first
    $text = clean_utf8_string($text);
    
    // Clean OCR artifacts
    $text = clean_ocr_artifacts($text);
    
    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (strlen($text) <= $maxChars) {
        return $text;
    }

    $excerpt = substr($text, 0, $maxChars);
    $lastPeriod = strrpos($excerpt, '.');

    if ($lastPeriod !== false && $lastPeriod > $maxChars * 0.7) {
        return substr($text, 0, $lastPeriod + 1);
    }

    return $excerpt . '...';
}

function build_context_from_results(array $results, string $question = ''): string
{
    if ($results === []) {
        return 'No relevant rulebook content found.';
    }

    $context = "Context from VTM/MET rulebooks:\n\n";
    
    // Detect if this is a discipline question
    $isDisciplineQuestion = preg_match('/\b(discipline|celerity|potence|fortitude|auspex|dominate|presence|obfuscate|protean|animalism|thaumaturgy|necromancy|serpentis|vicissitude|chimestry|dementation|quietus|obtenebration)\b/i', $question);

    foreach ($results as $index => $result) {
        $sourceNum = $index + 1;
        
        // For canonical source discipline questions, use much longer excerpts to capture all levels
        $isCanonical = stripos($result['book_title'] ?? '', 'Laws of the Night') !== false && 
                      (stripos($result['book_title'] ?? '', 'Revised') !== false || 
                       stripos($result['book_title'] ?? '', 'Text searchable') !== false);
        
        // Use very long excerpts for canonical source discipline questions to get all 5 levels
        $excerptLength = ($isCanonical && $isDisciplineQuestion) ? 5000 : 800;
        $excerpt = extract_excerpt((string) $result['page_text'], $excerptLength);

        $context .= sprintf(
            "[Source %d] %s (Page %d, Category: %s, System: %s):\n%s\n\n",
            $sourceNum,
            $result['book_title'],
            $result['page_number'],
            $result['category'],
            $result['system_type'],
            $excerpt
        );
    }

    return $context;
}

/**
 * @return array<int, array<string, mixed>>
 */
function prioritize_tradition_pages(array $results): array
{
    if ($results === []) {
        return $results;
    }

    $score = static function (array $row): int {
        $text = (string) ($row['page_text'] ?? '');
        $points = 0;
        foreach ([
            'Tradition of the Masquerade',
            'Tradition of the Domain',
            'Tradition of the Progeny',
            'Tradition of the Accounting',
            'Tradition of the Hospitality',
            'Tradition of the Destruction',
        ] as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $points += 10;
            }
        }

        foreach (['Masquerade', 'Domain', 'Progeny', 'Accounting', 'Hospitality', 'Destruction'] as $name) {
            $pattern = sprintf('/%s.{0,80}Tradition/i', preg_quote($name, '/'));
            $patternReverse = sprintf('/Tradition.{0,80}%s/i', preg_quote($name, '/'));
            if (preg_match($pattern, $text) || preg_match($patternReverse, $text)) {
                $points += 3;
            }
        }

        if (preg_match('/\bSix\s+Traditions\b/i', $text)) {
            $points += 6;
        }
        if (preg_match('/\bThe\s+Traditions\b/i', $text)) {
            $points += 4;
        }

        return $points;
    };

    usort(
        $results,
        static function ($a, $b) use ($score) {
            $scoreA = $score($a);
            $scoreB = $score($b);
            if ($scoreA === $scoreB) {
                $relevanceA = (float) ($a['relevance'] ?? 0);
                $relevanceB = (float) ($b['relevance'] ?? 0);
                return $relevanceB <=> $relevanceA;
            }

            return $scoreB <=> $scoreA;
        }
    );

    return $results;
}

function seed_traditions(mysqli $conn, string $title = 'VTM - Traditions (Reference)', string $category = 'Core', string $system = 'MET-VTM'): array
{
    $existingRulebook = db_fetch_one(
        $conn,
        'SELECT id FROM rulebooks WHERE title = ? AND system_type = ?',
        'ss',
        [$title, $system]
    );

    if ($existingRulebook) {
        $rulebookId = (int) $existingRulebook['id'];
    } else {
        $insertId = db_execute(
            $conn,
            'INSERT INTO rulebooks (title, category, system_type) VALUES (?,?,?)',
            'sss',
            [$title, $category, $system]
        );

        if (!$insertId) {
            return ['success' => false, 'error' => 'Failed to create rulebook'];
        }

        $rulebookId = (int) $insertId;
    }

    $pages = [
        1 => "The Six Traditions (Camarilla)\n\nThe Camarilla recognizes six foundational Traditions that govern Kindred society. Each Tradition is often titled as 'Tradition of the …'. The six are: Masquerade, Domain, Progeny, Accounting, Hospitality, and Destruction.",
        2 => "Tradition of the Masquerade\n\nKeep the existence of Kindred hidden from mortals; avoid breaches that expose vampiric society.",
        3 => "Tradition of the Domain\n\nRespect the authority of a domain's ruler (typically the Prince). A guest must observe the local ruler’s laws and customs.",
        4 => "Tradition of the Progeny\n\nDo not create childer without the permission of the domain’s ruler; illicit Embraces are punishable.",
        5 => "Tradition of the Accounting\n\nThe sire is responsible for the actions and education of her childe until released; debts and obligations must be honored.",
        6 => "Tradition of the Hospitality\n\nAnnounce yourself when entering a new domain and request leave to remain; unannounced Kindred risk sanction.",
        7 => "Tradition of the Destruction\n\nDo not destroy another Kindred without proper authority (usually vested in the domain’s ruler).",
    ];

    db_execute($conn, 'DELETE FROM rulebook_pages WHERE rulebook_id = ?', 'i', [$rulebookId]);

    $inserted = 0;
    foreach ($pages as $pageNumber => $text) {
        $ok = db_execute(
            $conn,
            'INSERT INTO rulebook_pages (rulebook_id, page_number, page_text) VALUES (?,?,?)',
            'iis',
            [$rulebookId, $pageNumber, $text]
        );

        if ($ok) {
            $inserted++;
        }
    }

    return [
        'success' => true,
        'rulebook_id' => $rulebookId,
        'inserted_pages' => $inserted,
        'title' => $title,
        'category' => $category,
        'system' => $system,
    ];
}

function ask_laws_agent(mysqli $conn, string $question, ?string $category = null, ?string $system = null): array
{
    // Try file-based Laws of the Night search first
    $fileResults = [];
    try {
        $loader = new LawsOfTheNightLoader();
        $loadResult = $loader->loadIndex();
        
        if ($loadResult['success'] && $loadResult['files_loaded'] > 0) {
            // Map category filter if provided
            $fileCategory = null;
            if ($category === 'Core') {
                // For Laws of the Night, we can search by content type
                // Let the search handle it without category filter initially
            }
            
            // Search file-based index
            $fileResults = $loader->search($question, $fileCategory, 10);
            
            // Convert file results to unified format
            $fileResults = array_map(static function ($entry) use ($loader) {
                return [
                    'source_type' => 'file',
                    'book_title' => 'Laws of the Night Revised',
                    'page_number' => $entry['chapter'] ?? 0,
                    'category' => ucfirst($entry['category']),
                    'system_type' => 'MET-VTM',
                    'page_text' => $entry['content'],
                    'relevance' => $entry['relevance'] ?? 0,
                    'file_path' => $entry['relative_path'],
                    'title' => $entry['title'],
                    'section' => $entry['section'] ?? null,
                ];
            }, $fileResults);
        }
    } catch (Throwable $e) {
        // Silently fall back to database if file system fails
        error_log('Laws of the Night file loader error: ' . $e->getMessage());
    }

    // Database search (existing functionality)
    if (question_indicates_traditions($question)) {
        $searchResults = search_rulebooks_traditions($conn, $category, $system, 20);
        if ($searchResults === []) {
            $searchResults = search_rulebooks($conn, $question, $category, $system, 20);
        }

        if ($searchResults !== []) {
            $found = [];
            $names = ['Masquerade', 'Domain', 'Progeny', 'Accounting', 'Hospitality', 'Destruction'];
            foreach ($searchResults as $result) {
                $text = strtolower((string) $result['page_text']);
                foreach ($names as $name) {
                    $lower = strtolower($name);
                    if (strpos($text, $lower) !== false || strpos($text, 'tradition of the ' . $lower) !== false) {
                        $found[$name] = true;
                    }
                }
            }

            if (count($found) < 4) {
                $byKey = [];
                foreach ($searchResults as $result) {
                    $key = $result['rulebook_id'] . ':' . $result['page_number'];
                    $byKey[$key] = $result;
                }

                foreach ($names as $name) {
                    if (isset($found[$name])) {
                        continue;
                    }

                    $phrase = '"Tradition of the ' . $name . '" ' . $name . ' Tradition*';
                    $boolean = '+(' . $phrase . ')';

                    $sql = <<<SQL
                        SELECT
                            r.id AS rulebook_id,
                            r.title AS book_title,
                            r.category,
                            r.system_type,
                            rp.page_number,
                            rp.page_text,
                            MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE) AS relevance
                        FROM rulebook_pages rp
                        JOIN rulebooks r ON rp.rulebook_id = r.id
                        WHERE MATCH(rp.page_text) AGAINST(? IN BOOLEAN MODE)
                    SQL;

                    $params = [$boolean, $boolean];
                    $types = 'ss';
                    if ($category) {
                        $sql .= ' AND r.category = ?';
                        $params[] = $category;
                        $types .= 's';
                    }
                    if ($system) {
                        $sql .= ' AND r.system_type = ?';
                        $params[] = $system;
                        $types .= 's';
                    }
                    $sql .= ' ORDER BY relevance DESC LIMIT 3';

                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_assoc()) {
                            $key = $row['rulebook_id'] . ':' . $row['page_number'];
                            if (!isset($byKey[$key])) {
                                $byKey[$key] = $row;
                            }
                        }
                    }
                }

                $searchResults = array_slice(array_values($byKey), 0, 30);
            }

            $searchResults = prioritize_tradition_pages($searchResults);

            if (count($found) < 4) {
                $broader = search_rulebooks_traditions($conn, $category, null, 20);
                if ($broader !== []) {
                    $byKey = [];
                    foreach ($searchResults as $result) {
                        $key = $result['rulebook_id'] . ':' . $result['page_number'];
                        $byKey[$key] = $result;
                    }
                    foreach ($broader as $result) {
                        $key = $result['rulebook_id'] . ':' . $result['page_number'];
                        if (!isset($byKey[$key])) {
                            $byKey[$key] = $result;
                        }
                    }
                    $searchResults = array_slice(array_values($byKey), 0, 30);
                    $searchResults = prioritize_tradition_pages($searchResults);
                }
            }
        }
    } else {
        // Use improved search_rulebooks function which includes discipline filtering
        $searchResults = search_rulebooks($conn, $question, $category, $system, 10);
    }

    // Add source_type to database results
    $searchResults = array_map(static function ($row) {
        $row['source_type'] = 'database';
        return $row;
    }, $searchResults);

    // Combine file and database results, prioritizing file results
    $allResults = array_merge($fileResults, $searchResults);
    
    // Sort by relevance if available
    if (!empty($allResults)) {
        usort($allResults, static function ($a, $b) {
            $relevanceA = (float) ($a['relevance'] ?? 0);
            $relevanceB = (float) ($b['relevance'] ?? 0);
            
            // Prioritize "Laws of the Night Revised" as canonical source
            $isCanonicalA = stripos($a['book_title'] ?? '', 'Laws of the Night') !== false && 
                           (stripos($a['book_title'] ?? '', 'Revised') !== false || 
                            stripos($a['book_title'] ?? '', 'Text searchable') !== false);
            $isCanonicalB = stripos($b['book_title'] ?? '', 'Laws of the Night') !== false && 
                           (stripos($b['book_title'] ?? '', 'Revised') !== false || 
                            stripos($b['book_title'] ?? '', 'Text searchable') !== false);
            
            if ($isCanonicalA && !$isCanonicalB) {
                $relevanceA += 200; // Strong priority for canonical source
            }
            if ($isCanonicalB && !$isCanonicalA) {
                $relevanceB += 200;
            }
            
            // Prioritize file results slightly
            if (($a['source_type'] ?? '') === 'file' && ($b['source_type'] ?? '') !== 'file') {
                $relevanceA += 50;
            }
            if (($b['source_type'] ?? '') === 'file' && ($a['source_type'] ?? '') !== 'file') {
                $relevanceB += 50;
            }
            return $relevanceB <=> $relevanceA;
        });
        
        // Limit total results
        $allResults = array_slice($allResults, 0, 15);
    }

    $searchResults = $allResults;

    if ($searchResults === []) {
        return [
            'success' => true,
            'question' => $question,
            'answer' => "I couldn't find specific information about that in the VTM/MET rulebooks. Try rephrasing your question or using different keywords. You can also specify a category (Core, Faction, Supplement, Blood Magic, Journal) or system (MET-VTM, VTM, MTA, etc.) to narrow the search.",
            'sources' => [],
            'ai_model' => null,
            'searched' => true,
            'results_found' => 0,
        ];
    }

    $context = build_context_from_results($searchResults, $question);

    $prompt = $context . "\nQuestion: " . $question;

    $systemPrompt = <<<PROMPT
You are an expert on Vampire: The Masquerade and Mind's Eye Theatre rules and lore. Your role is to answer questions based ONLY on the provided rulebook excerpts above.

CANONICAL SOURCE PRIORITY:
- "VTM - Laws of the Night Revised" or "Laws of the Night (Text searchable)" is the PRIMARY CANONICAL SOURCE
- When this source appears in the context, you MUST thoroughly review ALL excerpts from it, not just the first one
- This source contains the complete rules (Levels 1-5 for Disciplines, standard abilities, etc.)
- For Disciplines: The canonical source lists all 5 levels with section headers. Look for headers like "## Alacrity" (Level 1), "## Swiftness" (Level 2), "## Rapidity" (Level 3), "## Legerity" (Level 4), "## Fleetness" (Level 5)
- You MUST list all 5 levels separately: "Level 1: [name] - [description]. Level 2: [name] - [description]. Level 3: [name] - [description]. Level 4: [name] - [description]. Level 5: [name] - [description]."
- Do NOT skip levels or combine them. If you see Level 1 and Level 6, you missed Levels 2-5 - look through ALL excerpts from the canonical source
- Prioritize information from this canonical source over other sources when both cover the same topic

IMPORTANT RULES:
1. Always cite your sources using the format: (Source [number]: [Book Title], Page [page])
2. CRITICAL: Only cite sources that contain information RELEVANT to answering the question. Do NOT mention sources that don't contain relevant information, even if they appear in the context. If a source doesn't help answer the question, ignore it.
3. Cite MULTIPLE sources throughout your response when they are relevant. If 10+ sources are provided, reference 6-8 different sources that actually contain relevant information. Don't just cite 2-3 sources - spread citations across many relevant sources.
4. Each major point or piece of information should cite its source. If multiple sources cover the same topic, cite multiple sources.
5. Do not mention sources just because they exist - only cite sources that provide actual information relevant to the question.
6. If the excerpts don't contain enough information to fully answer the question, say so clearly
7. Do not make up or assume information not present in the excerpts
8. Be concise but thorough in your explanations
9. Use the exact terminology from the rulebooks
10. PRIORITIZE player-accessible content (Levels 1-5 for Disciplines, standard character abilities) over NPC-only or advanced content (Level 6+, elder powers, Storyteller-only content), but still cite sources for both types when relevant
11. Format your response with proper paragraph breaks - use double line breaks between paragraphs to make the answer readable
12. Organize information clearly: cover basic/standard content first, then mention advanced/NPC-only content separately if relevant

Answer the user's question now:
PROMPT;

    // Check if AI functions are available
    if (!function_exists('call_anthropic')) {
        // AI not available - return search results with excerpts
        $sources = array_map(
            static function ($row): array {
                $source = [
                    'book' => $row['book_title'],
                    'page' => (int) $row['page_number'],
                    'category' => $row['category'],
                    'system' => $row['system_type'],
                    'excerpt' => extract_excerpt((string) $row['page_text'], 300),
                    'relevance' => (float) ($row['relevance'] ?? 0),
                ];
                
                if (($row['source_type'] ?? '') === 'file') {
                    $source['source_type'] = 'file';
                    $source['file_path'] = $row['file_path'] ?? null;
                    $source['title'] = $row['title'] ?? null;
                    $source['section'] = $row['section'] ?? null;
                } else {
                    $source['source_type'] = 'database';
                }
                
                return $source;
            },
            $searchResults
        );

        // Build a simple answer from the excerpts
        $answer = "Found " . count($searchResults) . " relevant source(s) in the rulebooks:\n\n";
        foreach ($sources as $idx => $source) {
            $answer .= "**" . ($idx + 1) . ". " . $source['book'];
            if ($source['page'] > 0) {
                $answer .= ", Page " . $source['page'];
            }
            $answer .= "**\n";
            $answer .= $source['excerpt'] . "\n\n";
        }
        $answer .= "\n*Note: AI summarization is not available. Showing raw search results.*";

        return [
            'success' => true,
            'question' => $question,
            'answer' => $answer,
            'sources' => $sources,
            'ai_model' => null,
            'searched' => true,
            'results_found' => count($searchResults),
        ];
    }

    $aiResponse = call_anthropic($prompt, $systemPrompt, 1500);

    if (!$aiResponse['success']) {
        // If AI fails but we have results, return them anyway
        $sources = array_map(
            static function ($row): array {
                $source = [
                    'book' => $row['book_title'],
                    'page' => (int) $row['page_number'],
                    'category' => $row['category'],
                    'system' => $row['system_type'],
                    'excerpt' => extract_excerpt((string) $row['page_text'], 300),
                    'relevance' => (float) ($row['relevance'] ?? 0),
                ];
                
                if (($row['source_type'] ?? '') === 'file') {
                    $source['source_type'] = 'file';
                    $source['file_path'] = $row['file_path'] ?? null;
                    $source['title'] = $row['title'] ?? null;
                    $source['section'] = $row['section'] ?? null;
                } else {
                    $source['source_type'] = 'database';
                }
                
                return $source;
            },
            $searchResults
        );

        // Build answer from excerpts as HTML
        $answer = '<p>Found ' . count($searchResults) . ' relevant source(s). AI service unavailable: ' . htmlspecialchars($aiResponse['error'], ENT_QUOTES, 'UTF-8') . '</p>';
        $answer .= '<ol>';
        foreach ($sources as $idx => $source) {
            $bookTitle = htmlspecialchars($source['book'], ENT_QUOTES, 'UTF-8');
            $pageInfo = $source['page'] > 0 ? ', Page ' . $source['page'] : '';
            $excerpt = htmlspecialchars($source['excerpt'], ENT_QUOTES, 'UTF-8');
            
            $answer .= '<li>';
            $answer .= '<strong>' . $bookTitle . $pageInfo . '</strong><br>';
            $answer .= $excerpt;
            $answer .= '</li>';
        }
        $answer .= '</ol>';

        return [
            'success' => true,
            'question' => $question,
            'answer' => $answer,
            'sources' => $sources,
            'ai_model' => null,
            'searched' => true,
            'results_found' => count($searchResults),
        ];
    }

    $sources = array_map(
        static function ($row): array {
            $source = [
                'book' => $row['book_title'],
                'page' => (int) $row['page_number'],
                'category' => $row['category'],
                'system' => $row['system_type'],
                'excerpt' => extract_excerpt((string) $row['page_text'], 300),
                'relevance' => (float) ($row['relevance'] ?? 0),
            ];
            
            // Add file-specific metadata if available
            if (($row['source_type'] ?? '') === 'file') {
                $source['source_type'] = 'file';
                $source['file_path'] = $row['file_path'] ?? null;
                $source['title'] = $row['title'] ?? null;
                $source['section'] = $row['section'] ?? null;
            } else {
                $source['source_type'] = 'database';
            }
            
            return $source;
        },
        $searchResults
    );

    // Format the AI response for better readability
    $formattedAnswer = format_ai_response($aiResponse['content']);
    
    return [
        'success' => true,
        'question' => $question,
        'answer' => $formattedAnswer,
        'sources' => $sources,
        'ai_model' => $aiResponse['model'] ?? 'claude-3-5-sonnet',
        'searched' => true,
        'results_found' => count($searchResults),
    ];
}

try {
    // SECURITY: Removed hardcoded bypass key - use proper authentication only
    $action = $_GET['action'] ?? $_POST['action'] ?? 'ask';

    // Only health check and public traditions are accessible without auth
    if ($action !== 'health' && $action !== 'public_traditions') {
        $auth = check_authentication($conn);
        if (!$auth['authenticated'] || !$auth['verified']) {
            http_response_code($auth['http_code'] ?? 401);
            echo json_encode([
                'success' => false,
                'error' => $auth['error'] ?? 'Unauthorized',
            ]);
            exit;
        }
    }

    switch ($action) {
        case 'ask':
            $question = $_GET['question'] ?? $_POST['question'] ?? '';
            $category = $_GET['category'] ?? $_POST['category'] ?? null;
            $system = $_GET['system'] ?? $_POST['system'] ?? null;

            $question = trim($question);
            if ($question === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Question parameter is required',
                ]);
                exit;
            }

            try {
                $response = ask_laws_agent($conn, $question, $category, $system);
                if (!is_array($response)) {
                    throw new Exception('ask_laws_agent returned invalid response type: ' . gettype($response));
                }
                // Clean UTF-8 encoding in the response
                $response = clean_utf8_array($response);
                // Use JSON encoding flags that handle invalid UTF-8 better
                $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
                if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
                }
                $json = json_encode($response, $jsonFlags);
                if ($json === false) {
                    throw new Exception('JSON encode failed: ' . json_last_error_msg());
                }
                echo $json;
            } catch (Throwable $e) {
                error_log('Laws Agent ask error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error processing question: ' . $e->getMessage(),
                    'question' => $question,
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'public_traditions':
            $rulebook = db_fetch_one(
                $conn,
                'SELECT id FROM rulebooks WHERE title = ? AND system_type = ? LIMIT 1',
                'ss',
                ['VTM - Traditions (Reference)', 'MET-VTM']
            );
            if (!$rulebook) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Traditions reference not found. Ask an admin to seed it.',
                ]);
                break;
            }

            $rulebookId = (int) $rulebook['id'];
            $pagesResult = db_select(
                $conn,
                'SELECT page_number, page_text FROM rulebook_pages WHERE rulebook_id = ? ORDER BY page_number ASC',
                'i',
                [$rulebookId]
            );

            $pages = [];
            if ($pagesResult) {
                while ($row = mysqli_fetch_assoc($pagesResult)) {
                    $pages[] = $row;
                }
            }

            echo json_encode([
                'success' => true,
                'title' => 'VTM - Traditions (Reference)',
                'rulebook_id' => $rulebookId,
                'pages' => $pages,
            ]);
            break;

        case 'seed_traditions':
            if (!$mcpBypass) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Forbidden',
                ]);
                exit;
            }

            $seed = seed_traditions($conn);
            echo json_encode($seed);
            break;

        case 'health':
            $apiKeyConfigured = (function_exists('load_openai_api_key') && load_openai_api_key() !== null) 
                || (function_exists('load_anthropic_api_key') && load_anthropic_api_key() !== null);

            echo json_encode([
                'success' => true,
                'status' => 'online',
                'api_configured' => $apiKeyConfigured,
                'database' => 'connected',
                'authenticated' => true,
            ]);
            break;

        case 'debug_stats':
            try {
                $loader = new LawsOfTheNightLoader();
                $loadResult = $loader->loadIndex();
                $stats = $loader->getStats();
                
                echo json_encode([
                    'success' => true,
                    'index_status' => $loadResult,
                    'stats' => $stats,
                    'base_path' => $loader->getBasePath(),
                ]);
            } catch (Throwable $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
            break;

        case 'test_search':
            $query = $_GET['query'] ?? $_POST['query'] ?? '';
            if (empty($query)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Query parameter is required',
                ]);
                exit;
            }

            try {
                $loader = new LawsOfTheNightLoader();
                $loadResult = $loader->loadIndex();
                
                if (!$loadResult['success']) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to load index: ' . implode(', ', $loadResult['errors']),
                    ]);
                    break;
                }

                $category = $_GET['category'] ?? $_POST['category'] ?? null;
                $results = $loader->search($query, $category, 10);
                
                // Format results for display
                $formatted = array_map(static function ($entry) use ($loader) {
                    return [
                        'title' => $entry['title'],
                        'category' => $entry['category'],
                        'section' => $entry['section'],
                        'file_path' => $entry['relative_path'],
                        'relevance' => $entry['relevance'],
                        'excerpt' => $loader->getExcerpt($entry['content'], 200),
                    ];
                }, $results);

                echo json_encode([
                    'success' => true,
                    'query' => $query,
                    'results' => $formatted,
                    'count' => count($results),
                ]);
            } catch (Throwable $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action. Supported actions: ask, health',
            ]);
    }
} catch (Throwable $exception) {
    ob_clean();
    http_response_code(500);
    $errorMsg = 'Internal server error: ' . $exception->getMessage();
    $errorMsg .= ' in ' . basename($exception->getFile()) . ' on line ' . $exception->getLine();
    error_log('Laws Agent Exception: ' . $errorMsg);
    error_log('Stack trace: ' . $exception->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $errorMsg,
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}


