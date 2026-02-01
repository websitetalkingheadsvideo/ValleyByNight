<?php
/**
 * RAG API Endpoint
 * Handles user queries with hybrid search and AI responses
 */

declare(strict_types=1);
session_start();

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
        
        // Step 3: Perform hybrid search
        $search_results = hybrid_search($conn, $question, $query_embedding, $book_filters, 5);
        
        if (empty($search_results)) {
            echo json_encode([
                'success' => true,
                'answer' => 'I couldn\'t find any relevant information in the rulebooks for that question. Could you try rephrasing or asking something else?',
                'sources' => [],
                'model' => 'none'
            ]);
            exit;
        }
        
        // Step 4: Build context from search results
        $context = "";
        $sources = [];
        
        foreach ($search_results as $i => $result) {
            $context .= "\n\n--- Source " . ($i + 1) . " ---\n";
            $context .= "Book: " . $result['book_name'] . "\n";
            $context .= "Page: " . $result['page'] . "\n";
            $context .= "Content:\n" . $result['content'] . "\n";
            
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
        
        // Step 5: Get conversation history
        $conversation_history = get_conversation_history($conn, $session_id, 3);
        $conversation_history = array_reverse($conversation_history); // Oldest first
        
        // Step 6: Query AI (try LM Studio first, fallback to Claude)
        $ai_response = query_lm_studio($question, $context, $conversation_history);
        
        if (!$ai_response['success']) {
            // Fallback to Claude
            $anthropic_api_key = getenv('ANTHROPIC_API_KEY');
            
            if (!$anthropic_api_key) {
                echo json_encode([
                    'success' => false,
                    'error' => 'LM Studio unavailable and no Claude API key configured'
                ]);
                exit;
            }
            
            $ai_response = query_claude($question, $context, $conversation_history, $anthropic_api_key);
            
            if (!$ai_response['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Both LM Studio and Claude failed: ' . $ai_response['error']
                ]);
                exit;
            }
        }
        
        $answer = $ai_response['answer'];
        $model = $ai_response['model'];
        
        // Step 7: Save conversation
        $response_time = (int)((microtime(true) - $start_time) * 1000);
        save_conversation($conn, $user_id, $session_id, $question, $answer, $sources, $model, $response_time);
        
        // Step 8: Return response
        echo json_encode([
            'success' => true,
            'answer' => $answer,
            'sources' => $sources,
            'model' => $model,
            'response_time_ms' => $response_time
        ]);
        
    } catch (Exception $e) {
        error_log("RAG API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Internal error: ' . $e->getMessage()
        ]);
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
?>
