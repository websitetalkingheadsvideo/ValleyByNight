<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');
session_start();

/**
 * RAG API Endpoint
 * Handles user queries with hybrid search and AI responses
 */
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/rag_functions.php';
require_once __DIR__ . '/supabase_rag.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get action
$action = $_GET['action'] ?? '';

/**
 * Handle Ask Question
 */
if ($action === 'ask') {
    ignore_user_abort(true);
    if (function_exists('set_time_limit') && !ini_get('safe_mode')) {
        @set_time_limit(305);
    }
    $start_time = microtime(true);

    // Get parameters
    $question = trim($_GET['question'] ?? '');
    $book_filter = $_GET['book'] ?? '';
    $session_id = $_SESSION['rag_session_id'] ?? null;
    
    // Validate question
    if (empty($question)) {
        echo json_encode(['success' => false, 'error' => 'Question is required']);
        exit;
    }
    
    // Create session ID if needed
    if (!$session_id) {
        $session_id = uniqid('rag_', true);
        $_SESSION['rag_session_id'] = $session_id;
    }
    
    try {
        // Step 2: Prepare book filters (source patterns for Supabase lore_embeddings)
        $book_filters = [];
        $mentioned_clans = get_mentioned_clans($question);

        if (!empty($book_filter) && $book_filter !== 'all') {
            $book_filters = [$book_filter];
        } elseif (!empty($mentioned_clans)) {
            $book_filters = get_clan_book_filters_supabase($question);
        } elseif (is_traditions_question($question)) {
            $book_filters = get_core_book_codes_supabase();
        }

        // Step 3: Perform Supabase hybrid search (vector + keyword). Keyword uses best term from question, not first word.
        $rag_chunk_limit = 6;
        $rag_chunk_max_chars = 750;
        $rag_sources_display_limit = 12;
        $search_response = supabase_hybrid_search($question, $question, $book_filters, $rag_chunk_limit * 4);
        if (isset($search_response['error']) && $search_response['error'] !== null) {
            echo json_encode([
                'success' => false,
                'error' => $search_response['error'],
            ]);
            exit;
        }
        $search_results = $search_response['results'];

        // Put chunks that match question terms first so the model sees the relevant one (e.g. Celerity p.142) before noise
        $question_terms = get_question_terms($question);
        if (!empty($question_terms)) {
            usort($search_results, function ($a, $b) use ($question_terms) {
                $ca = strtolower((string) ($a['content'] ?? ''));
                $cb = strtolower((string) ($b['content'] ?? ''));
                $a_hits = 0;
                $b_hits = 0;
                foreach ($question_terms as $term) {
                    if (strpos($ca, $term) !== false) {
                        $a_hits++;
                    }
                    if (strpos($cb, $term) !== false) {
                        $b_hits++;
                    }
                }
                if ($b_hits !== $a_hits) {
                    return $b_hits <=> $a_hits;
                }
                $a_first = PHP_INT_MAX;
                $b_first = PHP_INT_MAX;
                foreach ($question_terms as $term) {
                    $pa = strpos($ca, $term);
                    $pb = strpos($cb, $term);
                    if ($pa !== false && $pa < $a_first) {
                        $a_first = $pa;
                    }
                    if ($pb !== false && $pb < $b_first) {
                        $b_first = $pb;
                    }
                }
                return $a_first <=> $b_first;
            });
        }

        $results_for_context = array_slice($search_results, 0, $rag_chunk_limit);
        $results_for_sources = array_slice($search_results, 0, $rag_sources_display_limit);

        if (empty($search_results)) {
            echo json_encode([
                'success' => true,
                'answer' => 'No matching rulebook passages were found for that question. Try rephrasing or asking something else.',
                'sources' => [],
                'model' => 'none'
            ]);
            exit;
        }
        error_log("Pages retrieved: " . implode(", ", array_map(function($r) { 
            return $r['page']; 
        }, $results_for_context)));
        // Step 4: Build context from top N results; attach more sources for popups
        $context = "";
        $traditions_source = null;
        if (is_traditions_question($question)) {
            $traditions_doc = get_traditions_document_supabase();
            if ($traditions_doc['content'] !== '') {
                $context = $traditions_doc['content'];
                $traditions_source = $traditions_doc['source'];
            } else {
                $context = load_traditions_knowledge_base(__DIR__ . '/knowledge-base', 4000);
            }
        }
        $sources = [];
        if ($traditions_source !== null) {
            $sources[] = $traditions_source;
        }
        
        foreach ($results_for_context as $i => $result) {
            $content = strlen($result['content']) > $rag_chunk_max_chars
                ? substr($result['content'], 0, $rag_chunk_max_chars) . '...'
                : $result['content'];
            $context .= "\n\n--- Source " . ($i + 1) . " ---\n";
            $context .= "Book: " . $result['book_name'] . "\n";
            $context .= "Page: " . $result['page'] . "\n";
            $context .= "Content:\n" . $content . "\n";
        }

        $seen_source_key = [];
        $excerpt_len = 550;
        foreach ($results_for_sources as $result) {
            $doc_key = $result['id'] ?? $result['document_id'] ?? '';
            $key = $doc_key . '|' . ($result['book_code'] ?? '') . '|' . ($result['page'] ?? '');
            if ($key !== '' && isset($seen_source_key[$key])) {
                continue;
            }
            if ($key !== '') {
                $seen_source_key[$key] = true;
            }
            $raw_score = (float) ($result['search_score'] ?? 0);
            $score_display = round($raw_score, 3);
            $content = $result['content'];
            if (strlen($content) <= $excerpt_len) {
                $excerpt = $content;
            } else {
                $excerpt = substr($content, 0, $excerpt_len);
                $last_period = strrpos($excerpt, '.');
                if ($last_period !== false && $last_period > (int)($excerpt_len / 2)) {
                    $excerpt = substr($content, 0, $last_period + 1);
                } else {
                    $excerpt .= '...';
                }
            }
            $sources[] = [
                'book' => $result['book_name'],
                'book_code' => $result['book_code'],
                'page' => $result['page'],
                'content_type' => $result['content_type'],
                'category' => $result['category'],
                'system' => $result['system'],
                'excerpt' => $excerpt,
                'score' => $score_display
            ];
        }

        $context .= load_knowledge_base(__DIR__ . '/knowledge-base', 2500);
        $context .= load_book_summaries(__DIR__ . '/../../reference/Books_summaries', 2000);

        // Step 5: Get conversation history
        $conversation_history = get_conversation_history($conn, $session_id, 3);
        $conversation_history = array_reverse($conversation_history); // Oldest first
        
        // Step 6: Query AI (try LM Studio first, fallback to Claude)
        $ai_response = query_lm_studio($question, $context, $conversation_history);
        $lm_studio_error = (!$ai_response['success']) ? ($ai_response['error'] ?? 'unknown') : null;

        if (!$ai_response['success']) {
            $err = $lm_studio_error ?? 'unknown';
            $is_timeout = (stripos($err, 'timed out') !== false || stripos($err, 'timeout') !== false);
            if ($is_timeout) {
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio took too long to respond (5 min limit). Try again.'
                ]);
                exit;
            }
            $anthropic_api_key = getenv('ANTHROPIC_API_KEY');
            if (!$anthropic_api_key) {
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio unavailable and no Claude API key. LM Studio: ' . $err
                ]);
                exit;
            }
            $ai_response = query_claude($question, $context, $conversation_history, $anthropic_api_key);
            if (!$ai_response['success']) {
                $claude_err = $ai_response['error'] ?? 'unknown';
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio: ' . $err . '; Claude: ' . $claude_err
                ]);
                exit;
            }
        }
        
        $answer = $ai_response['answer'];
        $model = $ai_response['model'];
        $response_time = (int)((microtime(true) - $start_time) * 1000);

        // Step 7: Return response immediately so the page updates; then save (so slow DB doesn't block the user)
        $answer_utf8 = (function_exists('mb_convert_encoding')) ? mb_convert_encoding($answer, 'UTF-8', 'UTF-8') : $answer;
        if ($answer_utf8 === false || $answer_utf8 === null) {
            $answer_utf8 = $answer;
        }
        $payload = [
            'success' => true,
            'answer' => $answer_utf8,
            'sources' => $sources,
            'model' => $model,
            'response_time_ms' => $response_time
        ];
        $out = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($out === false) {
            error_log('RAG API: json_encode failed: ' . json_last_error_msg());
            echo json_encode(['success' => false, 'error' => 'Response encoding error']);
            exit;
        }
        echo $out;
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }

        // Step 8: Save conversation after client has the response (non-fatal)
        if (!empty($answer)) {
            $saved = save_conversation($conn, $user_id, $session_id, $question, $answer, $sources, $model, $response_time);
            if ($saved === false) {
                error_log('RAG API: save_conversation failed for user_id=' . $user_id . ' session_id=' . $session_id);
            }
        }
        exit;
    } catch (Exception $e) {
        error_log("RAG API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Internal error: ' . $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Handle Get Books
 */
else if ($action === 'get_books') {
    try {
        $books = get_all_books($conn);
        
        echo json_encode([
            'success' => true,
            'books' => $books
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Handle Reset Session
 */
else if ($action === 'reset_session') {
    unset($_SESSION['rag_session_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversation history cleared'
    ]);
}

/**
 * Handle Get History
 */
else if ($action === 'get_history') {
    $session_id = $_SESSION['rag_session_id'] ?? null;
    
    if (!$session_id) {
        echo json_encode([
            'success' => true,
            'history' => []
        ]);
        exit;
    }
    
    try {
        $history = get_conversation_history($conn, $session_id, 20);
        
        echo json_encode([
            'success' => true,
            'history' => array_reverse($history)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Invalid action
 */
else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action'
    ]);
}

$conn->close();

