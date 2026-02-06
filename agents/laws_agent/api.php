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
    set_time_limit(220);  // LM Studio curl 210s + DB + JSON; server may still have its own timeout
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
        // Step 1: Generate query embedding
        $query_embedding = create_simple_embedding($question);
        
        // Step 2: Prepare book filters
        $book_filters = [];
        if (!empty($book_filter) && $book_filter !== 'all') {
            $book_filters = [$book_filter];
        }
        
        // Step 3: Perform hybrid search (more chunks for better recall)
        $rag_chunk_limit = 6;
        $rag_chunk_max_chars = 750;
        $keyword_query = $question;
        if (preg_match('/disciplines?/', strtolower($question))) {
            $keyword_query .= ' discipline disciplines';
        }
        $search_results = hybrid_search($conn, $keyword_query, $query_embedding, $book_filters, $rag_chunk_limit);

        // When question is about disciplines, prefer chunks that actually mention discipline(s)
        if (preg_match('/disciplines?/', strtolower($question))) {
            $discipline_chunks = array_filter($search_results, function ($r) {
                $c = strtolower($r['content']);
                return (strpos($c, 'discipline') !== false || strpos($c, 'disciplines') !== false);
            });
            if (!empty($discipline_chunks)) {
                $search_results = array_values(array_slice($discipline_chunks, 0, $rag_chunk_limit));
            }
        }

        if (empty($search_results)) {
            echo json_encode([
                'success' => true,
                'answer' => 'I couldn\'t find any relevant information in the rulebooks for that question. Could you try rephrasing or asking something else?',
                'sources' => [],
                'model' => 'none'
            ]);
            exit;
        }
        error_log("Pages retrieved: " . implode(", ", array_map(function($r) { 
            return $r['page']; 
        }, $search_results)));
        // Step 4: Build context from search results
        $context = "";
        $sources = [];
        
        foreach ($search_results as $i => $result) {
            $content = strlen($result['content']) > $rag_chunk_max_chars
                ? substr($result['content'], 0, $rag_chunk_max_chars) . '...'
                : $result['content'];
            $context .= "\n\n--- Source " . ($i + 1) . " ---\n";
            $context .= "Book: " . $result['book_name'] . "\n";
            $context .= "Page: " . $result['page'] . "\n";
            $context .= "Content:\n" . $content . "\n";
            
            $metadata = json_decode($result['metadata'], true);
            
            $sources[] = [
                'book' => $result['book_name'],
                'book_code' => $result['book_code'],
                'page' => $result['page'],
                'content_type' => $result['content_type'],
                'category' => $result['category'],
                'system' => $result['system'],
                'excerpt' => substr($result['content'], 0, 200) . '...',
                'score' => round($result['search_score'], 3)
            ];
        }

        $context .= load_knowledge_base(__DIR__ . '/knowledge-base', 2500);

        // Step 5: Get conversation history
        $conversation_history = get_conversation_history($conn, $session_id, 3);
        $conversation_history = array_reverse($conversation_history); // Oldest first
        
        // Step 6: Query AI (try LM Studio first, fallback to Claude)
        $ai_response = query_lm_studio($question, $context, $conversation_history);
        $lm_studio_error = (!$ai_response['success']) ? ($ai_response['error'] ?? 'unknown') : null;

        if (!$ai_response['success']) {
            // Fallback to Claude
            $anthropic_api_key = getenv('ANTHROPIC_API_KEY');

            if (!$anthropic_api_key) {
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio unavailable and no Claude API key configured. LM Studio: ' . ($lm_studio_error ?? 'unknown')
                ]);
                exit;
            }

            $ai_response = query_claude($question, $context, $conversation_history, $anthropic_api_key);

            if (!$ai_response['success']) {
                $claude_err = $ai_response['error'] ?? 'unknown';
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio: ' . ($lm_studio_error ?? 'unknown') . '; Claude: ' . $claude_err
                ]);
                exit;
            }
        }
        
        $answer = $ai_response['answer'];
        $model = $ai_response['model'];
        $response_time = (int)((microtime(true) - $start_time) * 1000);

        // Step 7: Save conversation (non-fatal; log and continue on failure)
        if (!empty($answer)) {
            $saved = save_conversation($conn, $user_id, $session_id, $question, $answer, $sources, $model, $response_time);
            if ($saved === false) {
                error_log('RAG API: save_conversation failed for user_id=' . $user_id . ' session_id=' . $session_id);
            }
        }

        // Step 8: Return response (ensure UTF-8 so json_encode does not fail)
        $answer_utf8 = mb_convert_encoding($answer, 'UTF-8', 'UTF-8');
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

