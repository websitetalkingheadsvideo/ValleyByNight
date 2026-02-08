<?php
declare(strict_types=1);

/**
 * Supabase RAG search for laws_agent.
 * Uses lore_embeddings + match_lore_embeddings RPC (pgvector).
 */
const SUPABASE_RAG_URL = 'https://zddvmwebmhigsxlcumzc.supabase.co';
const SUPABASE_RAG_KEY = 'sb_publishable_1u1_NSIVxMk7WJQCEYJBpQ_C64XQzfW';

/**
 * Get query embedding from OpenAI (text-embedding-3-small).
 * @return array<float>|null 1536-dim embedding or null on failure
 */
function supabase_get_query_embedding(string $text): ?array {
    $api_key = getenv('OPENAI_API_KEY');
    if (!$api_key || $api_key === '') {
        error_log('supabase_rag: OPENAI_API_KEY not set');
        return null;
    }
    $url = 'https://api.openai.com/v1/embeddings';
    $body = json_encode([
        'model' => 'text-embedding-3-small',
        'input' => $text,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200 || $response === false) {
        error_log('supabase_rag: OpenAI embedding failed HTTP ' . $http . ' ' . substr((string) $response, 0, 200));
        return null;
    }
    $data = json_decode($response, true);
    $emb = $data['data'][0]['embedding'] ?? null;
    return is_array($emb) ? $emb : null;
}

/**
 * Call Supabase match_lore_embeddings RPC.
 * @param array<float> $query_embedding
 * @param array<string> $source_filters e.g. ['lotnr','clanbook brujah'] – filter by source ILIKE
 * @return array<array<string,mixed>> rows on success, [] on failure (sets _supabase_rpc_failed)
 */
function supabase_match_lore(array $query_embedding, float $match_threshold, int $match_count, array $source_filters = []): array {
    $GLOBALS['_supabase_rpc_failed'] = false;
    $rpc_url = rtrim(SUPABASE_RAG_URL, '/') . '/rest/v1/rpc/match_lore_embeddings';
    $body = json_encode([
        'query_embedding' => $query_embedding,
        'match_threshold' => $match_threshold,
        'match_count' => $match_count,
    ]);
    $ch = curl_init($rpc_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_RAG_KEY,
            'Authorization: Bearer ' . SUPABASE_RAG_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200 || $response === false) {
        $GLOBALS['_supabase_rpc_failed'] = true;
        error_log('supabase_rag: match_lore_embeddings failed HTTP ' . $http . ' ' . substr((string) $response, 0, 300));
        return [];
    }
    $data = json_decode($response, true);
    $rows = is_array($data) ? $data : [];
    if (!empty($source_filters)) {
        $rows = array_filter($rows, function ($r) use ($source_filters) {
            $src = strtolower((string) ($r['source'] ?? ''));
            $title = strtolower((string) ($r['title'] ?? ''));
            foreach ($source_filters as $f) {
                $f = strtolower($f);
                if (strpos($src, $f) !== false || strpos($title, $f) !== false) {
                    return true;
                }
            }
            return false;
        });
    }
    return array_values($rows);
}

/**
 * Text search on lore_embeddings (keyword fallback / hybrid).
 * @return array<array<string,mixed>>
 */
function supabase_text_search(string $query, int $limit, array $source_filters = []): array {
    $base = rtrim(SUPABASE_RAG_URL, '/') . '/rest/v1/lore_embeddings';
    $pattern = '%' . $query . '%';
    $params = [
        'select' => 'id,title,content_text,source,metadata,category',
        'content_text' => 'ilike.' . $pattern,
        'order' => 'created_at.desc',
        'limit' => (string) $limit,
    ];
    $q = http_build_query($params);
    $full_url = $base . '?' . $q;
    $ch = curl_init($full_url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_RAG_KEY,
            'Authorization: Bearer ' . SUPABASE_RAG_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200 || $response === false) {
        return [];
    }
    $data = json_decode($response, true);
    $rows = is_array($data) ? $data : [];
    if (!empty($source_filters)) {
        $rows = array_filter($rows, function ($r) use ($source_filters) {
            $src = strtolower((string) ($r['source'] ?? ''));
            $title = strtolower((string) ($r['title'] ?? ''));
            foreach ($source_filters as $f) {
                if (strpos($src, strtolower($f)) !== false || strpos($title, strtolower($f)) !== false) {
                    return true;
                }
            }
            return false;
        });
    }
    return array_values($rows);
}

/**
 * Map lore_embeddings row to api.php expected format.
 */
function supabase_row_to_result(array $row, float $score = 1.0): array {
    $meta = $row['metadata'] ?? [];
    $page = is_array($meta) ? ($meta['page_number'] ?? $meta['page_start'] ?? $meta['page'] ?? 0) : 0;
    $title = $row['title'] ?? '';
    $src = $row['source'] ?? '';
    $book_name = $src !== '' ? $src : (preg_match('/^(.+?)\s+-/', $title, $m) ? trim($m[1]) : $title);
    if (stripos($book_name, 'Laws of the Night') !== false && stripos($book_name, 'Vampire the Masquerade') !== false) {
        $book_name = 'Laws of the Night';
    }
    $book_code = strtoupper(preg_replace('/[^a-z0-9]+/i', '_', trim($src ?: $book_name, '_')));
    return [
        'id' => $row['id'] ?? '',
        'document_id' => $row['id'] ?? '',
        'content' => $row['content_text'] ?? '',
        'content_type' => $row['category'] ?? 'general',
        'book_name' => $book_name,
        'book_code' => $book_code,
        'page' => (string) $page,
        'metadata' => $meta,
        'search_score' => $score,
    ];
}

/**
 * Map book_code filters to source substring filters for Supabase.
 */
function supabase_book_filters_to_source_patterns(array $book_codes): array {
    $patterns = [];
    foreach ($book_codes as $code) {
        $c = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', trim($code, '_')));
        $patterns[] = $c;
    }
    return array_unique($patterns);
}

/** Minimum semantic similarity (0.5 = permissive, 0.6 = strict). */
const SUPABASE_MATCH_THRESHOLD = 0.52;

/** Stopwords: never use these as the sole keyword for text search (they match everything). */
const SUPABASE_STOPWORDS = [
    'how', 'what', 'when', 'where', 'why', 'who', 'which', 'does', 'do', 'is', 'are', 'was', 'were',
    'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'and', 'or', 'but',
    'it', 'its', 'this', 'that', 'these', 'those', 'can', 'could', 'would', 'should', 'will', 'work',
];

/**
 * Extract the best single keyword from a query: longest non-stopword with length >= 2.
 * Using the first word (e.g. "how") makes keyword search useless; this picks the actual topic.
 */
function supabase_best_keyword(string $query): ?string {
    $words = preg_split('/\s+/', strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY);
    $words = array_map(static function ($w) {
        return preg_replace('/[^a-z0-9]/', '', $w);
    }, $words);
    $best = null;
    $best_len = 1;
    foreach ($words as $w) {
        if (strlen($w) >= 2 && !in_array($w, SUPABASE_STOPWORDS, true) && strlen($w) > $best_len) {
            $best = $w;
            $best_len = strlen($w);
        }
    }
    return $best;
}

/**
 * Supabase hybrid search: semantic (match_lore_embeddings) + keyword (ilike).
 * Returns ['results' => array, 'error' => null] or ['results' => [], 'error' => 'message'] on connection failure.
 * @param array<string> $book_filters book_code values for source filtering
 */
function supabase_hybrid_search(string $question, string $keyword_query, array $book_filters, int $limit): array {
    $source_patterns = supabase_book_filters_to_source_patterns($book_filters);
    $query_embedding = supabase_get_query_embedding($question);
    $semantic = [];
    $rpc_failed = false;
    if ($query_embedding !== null) {
        $semantic = supabase_match_lore($query_embedding, SUPABASE_MATCH_THRESHOLD, $limit * 2, $source_patterns);
        $rpc_failed = supabase_last_rpc_failed();
    }
    $keyword = [];
    $kw = supabase_best_keyword($keyword_query);
    if ($kw !== null && strlen($kw) >= 2) {
        $keyword = supabase_text_search($kw, $limit * 2, $source_patterns);
    }
    if ($rpc_failed && empty($semantic) && empty($keyword)) {
        return [
            'results' => [],
            'error' => 'Failed to connect to Supabase RAG. The match_lore_embeddings RPC may be missing or the embedding dimension may not match.',
        ];
    }
    $seen = [];
    $combined = [];
    foreach ($semantic as $i => $r) {
        $id = $r['id'] ?? uniqid('', true);
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $sim = (float) ($r['similarity'] ?? 1.0 - $i * 0.01);
        $combined[] = supabase_row_to_result($r, $sim * 0.5);
    }
    foreach ($keyword as $i => $r) {
        $id = $r['id'] ?? uniqid('', true);
        if (isset($seen[$id])) {
            foreach ($combined as &$item) {
                if (($item['id'] ?? $item['document_id'] ?? '') === $id) {
                    $item['search_score'] += 0.3;
                    break;
                }
            }
            continue;
        }
        $seen[$id] = true;
        $combined[] = supabase_row_to_result($r, 0.3);
    }
    usort($combined, function ($a, $b) {
        return $b['search_score'] <=> $a['search_score'];
    });
    return ['results' => array_slice($combined, 0, $limit), 'error' => null];
}

function supabase_last_rpc_failed(): bool {
    return (bool) ($GLOBALS['_supabase_rpc_failed'] ?? false);
}
