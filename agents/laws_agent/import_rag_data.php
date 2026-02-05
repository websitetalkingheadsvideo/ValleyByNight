<?php
/**
 * RAG Data Import Script
 * Imports JSON data into the database and generates embeddings
 * Usage: php import_rag_data.php path/to/rag_documents.json
 */

require_once __DIR__ . '/../../includes/connect.php';

// Load environment variables for API keys
$anthropic_api_key = getenv('ANTHROPIC_API_KEY');
if (!$anthropic_api_key) {
    die("Error: ANTHROPIC_API_KEY not found in environment variables.\n");
}

// Configuration
$BATCH_SIZE = 10; // Process embeddings in batches
$EMBEDDING_MODEL = 'claude-3-5-sonnet-20241022'; // Claude model for embeddings

/**
 * Generate embedding using Anthropic API
 */
function generate_embedding($text, $api_key, $model) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => $model,
        'max_tokens' => 1,
        'messages' => [
            [
                'role' => 'user',
                'content' => "Generate a semantic embedding for this text (respond with just 'OK'): " . substr($text, 0, 8000)
            ]
        ]
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
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("Embedding API error: HTTP $http_code - $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    // Extract embedding from response headers or use text-based embedding
    // Note: Claude doesn't have direct embedding endpoint, so we'll use a workaround
    // For production, consider using a dedicated embedding model or service
    
    // Fallback: Create a simple hash-based embedding for now
    // TODO: Replace with proper embedding service (OpenAI, Cohere, or local model)
    return create_simple_embedding($text);
}

/**
 * Create a simple TF-IDF style embedding as fallback
 * This is temporary until we integrate a proper embedding service
 */
function create_simple_embedding($text) {
    // Normalize and tokenize
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    // Create a fixed-size embedding (1024 dimensions)
    $embedding = array_fill(0, 1024, 0.0);
    
    // Use word hashing to populate embedding
    foreach ($words as $word) {
        $hash = crc32($word);
        $index = abs($hash) % 1024;
        $embedding[$index] += 1.0;
    }
    
    // Normalize the embedding
    $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $embedding)));
    if ($magnitude > 0) {
        $embedding = array_map(function($x) use ($magnitude) { 
            return $x / $magnitude; 
        }, $embedding);
    }
    
    return $embedding;
}

/**
 * Encode embedding as binary for storage
 */
function encode_embedding($embedding) {
    // Pack as array of floats
    return pack('f*', ...$embedding);
}

/**
 * Derive book metadata from documents array and filename.
 * Uses first document's metadata.source and metadata.book_code when present.
 */
function derive_book_info(array $documents, string $json_file) {
    $base = $documents[0]['metadata'] ?? [];
    $name = $base['source'] ?? basename($json_file, '.json');
    $code = $base['book_code'] ?? null;
    if ($code === null || $code === '') {
        $stem = basename($json_file, '.json');
        $stem = preg_replace('/_rag(_final|_v2)?$/i', '', $stem);
        $code = strtoupper(preg_replace('/[^a-z0-9]+/i', '_', trim($stem, '_')));
    }
    $max_page = 0;
    foreach ($documents as $doc) {
        if (isset($doc['page']) && $doc['page'] > $max_page) {
            $max_page = (int) $doc['page'];
        }
    }
    return [
        'book_name' => $name,
        'book_code' => $code,
        'source' => $name,
        'category' => 'Core',
        'system' => 'MET-VTM',
        'total_pages' => $max_page,
        'total_chunks' => count($documents)
    ];
}

/**
 * Main import function
 */
function import_json_data($json_file, $conn, $api_key) {
    global $BATCH_SIZE, $EMBEDDING_MODEL;
    
    echo "=== RAG Data Import ===\n\n";
    
    // Step 1: Load JSON file
    echo "Step 1: Loading JSON file...\n";
    if (!file_exists($json_file)) {
        die("Error: File not found: $json_file\n");
    }
    
    $json_content = file_get_contents($json_file);
    $documents = json_decode($json_content, true);
    
    if (!$documents) {
        die("Error: Invalid JSON file\n");
    }
    
    echo "  ✓ Loaded " . count($documents) . " documents\n\n";
    
    // Step 2: Extract book information from JSON metadata (and filename)
    echo "Step 2: Processing book metadata...\n";
    
    $book_info = derive_book_info($documents, $json_file);
    
    echo "  Book: {$book_info['book_name']}\n";
    echo "  Code: {$book_info['book_code']}\n";
    echo "  Pages: {$book_info['total_pages']}\n";
    echo "  Chunks: {$book_info['total_chunks']}\n\n";
    
    // Step 3: Insert book record
    echo "Step 3: Creating book record...\n";
    
    $book_id = db_execute($conn,
        "INSERT INTO rag_books (book_name, book_code, source, category, system, total_pages, total_chunks) 
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         book_name = VALUES(book_name),
         source = VALUES(source),
         category = VALUES(category),
         system = VALUES(system),
         total_pages = VALUES(total_pages),
         total_chunks = VALUES(total_chunks)",
        "ssssiii",
        [
            $book_info['book_name'],
            $book_info['book_code'],
            $book_info['source'],
            $book_info['category'],
            $book_info['system'],
            $book_info['total_pages'],
            $book_info['total_chunks']
        ]
    );
    
    if (!$book_id) {
        // If update, fetch existing book_id
        $existing = db_fetch_one($conn,
            "SELECT id FROM rag_books WHERE book_code = ?",
            "s",
            [$book_info['book_code']]
        );
        $book_id = $existing['id'];
    }
    
    echo "  ✓ Book ID: $book_id\n\n";
    
    // Step 4: Import documents with embeddings
    echo "Step 4: Importing documents and generating embeddings...\n";
    echo "  Note: Using simple embedding fallback (consider upgrading to proper embedding service)\n\n";
    
    $imported = 0;
    $failed = 0;
    $start_time = microtime(true);
    
    foreach ($documents as $i => $doc) {
        $progress = $i + 1;
        
        try {
            // Begin transaction for each document
            db_begin_transaction($conn);
            
            // Insert document
            $metadata_json = json_encode($doc['metadata']);
            
            $doc_id = db_execute($conn,
                "INSERT INTO rag_documents (book_id, doc_id, page, chunk_index, total_chunks, content, content_type, metadata)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 content = VALUES(content),
                 content_type = VALUES(content_type),
                 metadata = VALUES(metadata)",
                "isiissss",
                [
                    $book_id,
                    $doc['id'],
                    $doc['page'],
                    $doc['chunk_index'],
                    $doc['total_chunks'],
                    $doc['content'],
                    $doc['content_type'],
                    $metadata_json
                ]
            );
            
            if (!$doc_id) {
                // Fetch existing doc_id
                $existing_doc = db_fetch_one($conn,
                    "SELECT id FROM rag_documents WHERE book_id = ? AND doc_id = ?",
                    "is",
                    [$book_id, $doc['id']]
                );
                $doc_id = $existing_doc['id'];
            }
            
            // Generate and store embedding
            $embedding = create_simple_embedding($doc['content']);
            $embedding_binary = encode_embedding($embedding);
            
            db_execute($conn,
                "INSERT INTO rag_embeddings (document_id, embedding, embedding_model, dimension)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 embedding = VALUES(embedding)",
                "issi",
                [
                    $doc_id,
                    $embedding_binary,
                    'simple_tfidf',
                    1024
                ]
            );
            
            db_commit($conn);
            $imported++;
            
            // Progress indicator
            if ($progress % 10 === 0 || $progress === count($documents)) {
                $elapsed = microtime(true) - $start_time;
                $rate = $progress / $elapsed;
                $eta = (count($documents) - $progress) / $rate;
                
                echo sprintf(
                    "  Progress: %d/%d (%.1f%%) - %.1f docs/sec - ETA: %.0f sec\r",
                    $progress,
                    count($documents),
                    ($progress / count($documents)) * 100,
                    $rate,
                    $eta
                );
            }
            
        } catch (Exception $e) {
            db_rollback($conn);
            error_log("Failed to import document {$doc['id']}: " . $e->getMessage());
            $failed++;
        }
    }
    
    echo "\n\n";
    
    // Summary
    $total_time = microtime(true) - $start_time;
    
    echo "=== Import Complete ===\n";
    echo "  ✓ Successfully imported: $imported documents\n";
    if ($failed > 0) {
        echo "  ✗ Failed: $failed documents\n";
    }
    echo "  ⏱ Total time: " . number_format($total_time, 2) . " seconds\n";
    echo "  📊 Average: " . number_format($imported / $total_time, 2) . " docs/sec\n\n";
    
    echo "Next step: Test the Laws Agent interface!\n\n";
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$input = $argv[1] ?? null;

if (!$input) {
    echo "Usage: php import_rag_data.php <path_to_json_file|path_to_Books_directory>\n";
    echo "  Single file: php import_rag_data.php ./rag_documents.json\n";
    echo "  All books:   php import_rag_data.php " . __DIR__ . "/Books\n";
    exit(1);
}

if (is_dir($input)) {
    $files = glob(rtrim($input, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json');
    $files = array_filter($files, static function ($path) {
        return strpos($path, 'backups' . DIRECTORY_SEPARATOR) === false;
    });
    sort($files);
    $total = count($files);
    echo "Batch import: found $total JSON file(s) in $input\n\n";
    foreach ($files as $i => $json_file) {
        echo "\n[" . ($i + 1) . "/$total] " . basename($json_file) . "\n";
        import_json_data($json_file, $conn, $anthropic_api_key);
    }
    echo "\nBatch import complete. " . $total . " book(s) imported.\n";
} else {
    import_json_data($input, $conn, $anthropic_api_key);
}

$conn->close();
?>
