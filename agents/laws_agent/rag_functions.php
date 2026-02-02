<?php
error_reporting(0);
ini_set('display_errors', '0');

/**
 * RAG Helper Functions

 * Create a simple TF-IDF style embedding
 */
function create_simple_embedding($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    $embedding = array_fill(0, 1024, 0.0);
    
    foreach ($words as $word) {
        $hash = crc32($word);
        $index = abs($hash) % 1024;
        $embedding[$index] += 1.0;
    }
    
    $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $embedding)));
    if ($magnitude > 0) {
        $embedding = array_map(function($x) use ($magnitude) { 
            return $x / $magnitude; 
        }, $embedding);
    }
    
    return $embedding;
}

/**
 * RAG Helper Functions
 * Provides search, retrieval, and AI integration functions
 */

/**
 * Decode binary embedding from database
 */
function decode_embedding($binary) {
    return unpack('f*', $binary);
}

/**
 * Calculate cosine similarity between two embeddings
 */
function cosine_similarity($embedding1, $embedding2) {
    $dot_product = 0.0;
    $magnitude1 = 0.0;
    $magnitude2 = 0.0;
    
    $count = min(count($embedding1), count($embedding2));
    
    for ($i = 0; $i < $count; $i++) {
        $dot_product += $embedding1[$i] * $embedding2[$i];
        $magnitude1 += $embedding1[$i] * $embedding1[$i];
        $magnitude2 += $embedding2[$i] * $embedding2[$i];
    }
    
    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);
    
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0.0;
    }
    
    return $dot_product / ($magnitude1 * $magnitude2);
}

/**
 * Perform keyword search using MySQL FULLTEXT
 */
function keyword_search($conn, $query, $book_filters = [], $limit = 10) {
    $sql = "
        SELECT 
            d.id,
            d.doc_id,
            d.page,
            d.content,
            d.content_type,
            d.metadata,
            b.book_name,
            b.book_code,
            b.category,
            b.system,
            MATCH(d.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM rag_documents d
        JOIN rag_books b ON d.book_id = b.id
        WHERE MATCH(d.content) AGAINST(? IN NATURAL LANGUAGE MODE)
    ";
    
    $params = [$query, $query];
    $types = "ss";
    
    // Add book filters
    if (!empty($book_filters)) {
        $placeholders = str_repeat('?,', count($book_filters) - 1) . '?';
        $sql .= " AND b.book_code IN ($placeholders)";
        $params = array_merge($params, $book_filters);
        $types .= str_repeat('s', count($book_filters));
    }
    
    $sql .= " ORDER BY relevance DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";
    
    return db_fetch_all($conn, $sql, $types, $params);
}

/**
 * Perform semantic search using embeddings
 */
function semantic_search($conn, $query_embedding, $book_filters = [], $limit = 10) {
    // Fetch all embeddings (with book filter if provided)
    $sql = "
        SELECT 
            e.id,
            e.document_id,
            e.embedding,
            d.doc_id,
            d.page,
            d.content,
            d.content_type,
            d.metadata,
            b.book_name,
            b.book_code,
            b.category,
            b.system
        FROM rag_embeddings e
        JOIN rag_documents d ON e.document_id = d.id
        JOIN rag_books b ON d.book_id = b.id
    ";
    
    $params = [];
    $types = "";
    
    if (!empty($book_filters)) {
        $placeholders = str_repeat('?,', count($book_filters) - 1) . '?';
        $sql .= " WHERE b.book_code IN ($placeholders)";
        $params = $book_filters;
        $types = str_repeat('s', count($book_filters));
    }
    
    $embeddings = db_fetch_all($conn, $sql, $types, $params);
    
    // Calculate similarities
    $similarities = [];
    foreach ($embeddings as $row) {
        $doc_embedding = decode_embedding($row['embedding']);
        $similarity = cosine_similarity($query_embedding, $doc_embedding);
        
        $similarities[] = [
            'document_id' => $row['document_id'],
            'doc_id' => $row['doc_id'],
            'page' => $row['page'],
            'content' => $row['content'],
            'content_type' => $row['content_type'],
            'metadata' => $row['metadata'],
            'book_name' => $row['book_name'],
            'book_code' => $row['book_code'],
            'category' => $row['category'],
            'system' => $row['system'],
            'similarity' => $similarity
        ];
    }
    
    // Sort by similarity
    usort($similarities, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });
    
    // Return top results
    return array_slice($similarities, 0, $limit);
}

/**
 * Hybrid search: combines keyword and semantic search
 */
function hybrid_search($conn, $query, $query_embedding, $book_filters = [], $limit = 5) {
    // Get both types of results
    $keyword_results = keyword_search($conn, $query, $book_filters, $limit * 2);
    $semantic_results = semantic_search($conn, $query_embedding, $book_filters, $limit * 2);
    
    // Combine and deduplicate results
    $combined = [];
    $seen_ids = [];
    
    // Weight: 60% semantic, 40% keyword
    foreach ($semantic_results as $i => $result) {
        $doc_id = $result['document_id'];
        if (!isset($seen_ids[$doc_id])) {
            $combined[] = [
                'data' => $result,
                'score' => $result['similarity'] * 0.6,
                'source' => 'semantic'
            ];
            $seen_ids[$doc_id] = true;
        }
    }
    
    foreach ($keyword_results as $i => $result) {
        $doc_id = $result['id'];
        if (!isset($seen_ids[$doc_id])) {
            $combined[] = [
                'data' => $result,
                'score' => ($result['relevance'] / 100) * 0.4,
                'source' => 'keyword'
            ];
            $seen_ids[$doc_id] = true;
        } else {
            // Boost existing semantic result if also in keyword results
            foreach ($combined as &$item) {
                if ($item['data']['document_id'] == $doc_id || $item['data']['id'] == $doc_id) {
                    $item['score'] += ($result['relevance'] / 100) * 0.4;
                }
            }
        }
    }
    
    // Sort by combined score
    usort($combined, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Return top results
    $top_results = array_slice($combined, 0, $limit);
    
    // Format results
    $formatted = [];
    foreach ($top_results as $item) {
        $formatted[] = array_merge($item['data'], ['search_score' => $item['score']]);
    }
    
    return $formatted;
}

/**
 * Query LM Studio
 */
function query_lm_studio($question, $context, $conversation_history = []) {
    $lm_studio_url = 'http://192.168.0.217:1234/v1/chat/completions';
    
    // Build messages array
    $messages = [];
    
    // System message
    $messages[] = [
        'role' => 'system',
        'content' => 'You are a helpful assistant for the Vampire: The Masquerade / Mind\'s Eye Theatre tabletop roleplaying game. 
You answer questions about rules, disciplines, clans, mechanics, and lore based ONLY on the provided context from official rulebooks.

When answering:
1. Be accurate and cite specific rules when relevant
2. If the context doesn\'t contain the answer, say so
3. Use game terminology correctly
4. Be concise but complete
5. Format your response clearly with proper paragraphs

Context from rulebooks:
' . $context
    ];
    
    // Add conversation history (last 3 exchanges)
    $history_limit = min(6, count($conversation_history));
    for ($i = max(0, count($conversation_history) - $history_limit); $i < count($conversation_history); $i++) {
        $exchange = $conversation_history[$i];
        $messages[] = ['role' => 'user', 'content' => $exchange['question']];
        $messages[] = ['role' => 'assistant', 'content' => $exchange['answer']];
    }
    
    // Current question
    $messages[] = [
        'role' => 'user',
        'content' => $question
    ];
    
    $data = [
        'model' => 'meta-llama-3.1-8b-instruct',  // Add this line
        'messages' => $messages,
        'temperature' => 0.1,
        'max_tokens' => 1000,
        'stream' => false
    ];
    
    $ch = curl_init($lm_studio_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);  // 2 minutes
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $http_code !== 200) {
        return [
            'success' => false,
            'error' => $error ?: "HTTP $http_code",
            'response' => $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => false,
            'error' => 'Invalid response format',
            'response' => $response
        ];
    }
    
    return [
        'success' => true,
        'answer' => $result['choices'][0]['message']['content'],
        'model' => $result['model'] ?? 'lm_studio'
    ];
}

/**
 * Query Claude (fallback)
 */
function query_claude($question, $context, $conversation_history = [], $api_key) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    // Build system prompt
    $system_prompt = 'You are a helpful assistant for the Vampire: The Masquerade / Mind\'s Eye Theatre tabletop roleplaying game.
You answer questions about rules, disciplines, clans, mechanics, and lore based ONLY on the provided context from official rulebooks.

When answering:
1. Be accurate and cite specific rules when relevant
2. If the context doesn\'t contain the answer, say so
3. Use game terminology correctly
4. Be concise but complete
5. Format your response clearly with proper paragraphs

Context from rulebooks:
' . $context;
    
    // Build messages
    $messages = [];
    
    // Add conversation history (last 3 exchanges)
    $history_limit = min(6, count($conversation_history));
    for ($i = max(0, count($conversation_history) - $history_limit); $i < count($conversation_history); $i++) {
        $exchange = $conversation_history[$i];
        $messages[] = ['role' => 'user', 'content' => $exchange['question']];
        $messages[] = ['role' => 'assistant', 'content' => $exchange['answer']];
    }
    
    $messages[] = ['role' => 'user', 'content' => $question];
    
    $data = [
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 2000,
        'system' => $system_prompt,
        'messages' => $messages
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $http_code !== 200) {
        return [
            'success' => false,
            'error' => $error ?: "HTTP $http_code: $response"
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['content'][0]['text'])) {
        return [
            'success' => false,
            'error' => 'Invalid response format'
        ];
    }
    
    return [
        'success' => true,
        'answer' => $result['content'][0]['text'],
        'model' => 'claude-3.5-sonnet'
    ];
}

/**
 * Get all books for filtering
 */
function get_all_books($conn) {
    return db_fetch_all($conn,
        "SELECT id, book_name, book_code, category, `system`, total_pages, total_chunks 
         FROM rag_books 
         ORDER BY book_name"
    );
}

/**
 * Save conversation to database
 */
function save_conversation($conn, $user_id, $session_id, $question, $answer, $sources, $model, $response_time) {
    $sources_json = json_encode($sources);
    
    return db_execute($conn,
        "INSERT INTO rag_conversations (user_id, session_id, question, answer, sources_used, model_used, response_time_ms)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        "isssssi",
        [$user_id, $session_id, $question, $answer, $sources_json, $model, $response_time]
    );
}

/**
 * Get conversation history for a session
 */
function get_conversation_history($conn, $session_id, $limit = 10) {
    return db_fetch_all($conn,
        "SELECT question, answer, sources_used, model_used, created_at
         FROM rag_conversations
         WHERE session_id = ?
         ORDER BY created_at DESC
         LIMIT ?",
        "si",
        [$session_id, $limit]
    );
}

?>
