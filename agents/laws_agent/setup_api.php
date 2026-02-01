<?php

/**
 * Setup API Backend
 * Handles web-based setup requests
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Check authentication
if (!isset($_SESSION['setup_authenticated']) || !$_SESSION['setup_authenticated']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/rag_functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/**
 * Check current status
 */
if ($action === 'check_status') {
    try {
        // Check if tables exist
        $tables_exist = true;
        $required_tables = ['rag_books', 'rag_documents', 'rag_embeddings', 'rag_conversations', 'rag_user_preferences'];
        
        foreach ($required_tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (!$result || mysqli_num_rows($result) === 0) {
                $tables_exist = false;
                break;
            }
        }
        
        // Check if data is imported
        $document_count = 0;
        if ($tables_exist) {
            $count_result = db_fetch_one($conn, "SELECT COUNT(*) as count FROM rag_documents");
            $document_count = $count_result['count'] ?? 0;
        }
        
        echo json_encode([
            'success' => true,
            'database_ready' => $tables_exist,
            'data_imported' => $document_count > 0,
            'document_count' => $document_count
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Setup database tables
 */
else if ($action === 'setup_database') {
    try {
        ob_start();
        
        // Drop old tables
        $old_tables = [
            'laws_agent_documents',
            'laws_agent_embeddings', 
            'laws_agent_conversations',
            'laws_agent_sources'
        ];
        
        $output = "Removing old tables...\n";
        foreach ($old_tables as $table) {
            mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
        }
        
        // Create new tables
        $output .= "\nCreating new RAG tables...\n\n";
        
        // Books table
        $create_books = "
        CREATE TABLE IF NOT EXISTS `rag_books` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `book_name` VARCHAR(255) NOT NULL,
            `book_code` VARCHAR(50) NOT NULL UNIQUE,
            `source` VARCHAR(255) NOT NULL,
            `category` VARCHAR(100),
            `system` VARCHAR(50),
            `total_pages` INT DEFAULT 0,
            `total_chunks` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_book_code` (`book_code`),
            INDEX `idx_category` (`category`),
            INDEX `idx_system` (`system`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (mysqli_query($conn, $create_books)) {
            $output .= "✓ Created table: rag_books\n";
        } else {
            throw new Exception("Error creating rag_books: " . mysqli_error($conn));
        }
        
        // Documents table
        $create_documents = "
        CREATE TABLE IF NOT EXISTS `rag_documents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `book_id` INT NOT NULL,
            `doc_id` VARCHAR(50) NOT NULL,
            `page` INT,
            `chunk_index` INT DEFAULT 0,
            `total_chunks` INT DEFAULT 1,
            `content` TEXT NOT NULL,
            `content_type` VARCHAR(100),
            `metadata` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`book_id`) REFERENCES `rag_books`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_doc` (`book_id`, `doc_id`),
            INDEX `idx_book_page` (`book_id`, `page`),
            INDEX `idx_content_type` (`content_type`),
            FULLTEXT INDEX `idx_content_search` (`content`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (mysqli_query($conn, $create_documents)) {
            $output .= "✓ Created table: rag_documents\n";
        } else {
            throw new Exception("Error creating rag_documents: " . mysqli_error($conn));
        }
        
        // Embeddings table
        $create_embeddings = "
        CREATE TABLE IF NOT EXISTS `rag_embeddings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `document_id` INT NOT NULL,
            `embedding` BLOB NOT NULL,
            `embedding_model` VARCHAR(50) DEFAULT 'simple_tfidf',
            `dimension` INT DEFAULT 1024,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`document_id`) REFERENCES `rag_documents`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_embedding` (`document_id`, `embedding_model`),
            INDEX `idx_document` (`document_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (mysqli_query($conn, $create_embeddings)) {
            $output .= "✓ Created table: rag_embeddings\n";
        } else {
            throw new Exception("Error creating rag_embeddings: " . mysqli_error($conn));
        }
        
        // Conversations table
        $create_conversations = "
        CREATE TABLE IF NOT EXISTS `rag_conversations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `session_id` VARCHAR(100),
            `question` TEXT NOT NULL,
            `answer` TEXT,
            `sources_used` JSON,
            `model_used` VARCHAR(50),
            `response_time_ms` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_session` (`session_id`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (mysqli_query($conn, $create_conversations)) {
            $output .= "✓ Created table: rag_conversations\n";
        } else {
            throw new Exception("Error creating rag_conversations: " . mysqli_error($conn));
        }
        
        // User preferences table
        $create_preferences = "
        CREATE TABLE IF NOT EXISTS `rag_user_preferences` (
            `user_id` INT PRIMARY KEY,
            `preferred_books` JSON,
            `preferred_model` VARCHAR(50) DEFAULT 'lm_studio',
            `max_sources` INT DEFAULT 5,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (mysqli_query($conn, $create_preferences)) {
            $output .= "✓ Created table: rag_user_preferences\n";
        } else {
            throw new Exception("Error creating rag_user_preferences: " . mysqli_error($conn));
        }
        
        $output .= "\n✓ Database setup complete!\n";
        
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'message' => $output
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Import data from uploaded JSON
 */
else if ($action === 'import_data') {
    try {
        if (!isset($_FILES['jsonFile']) || $_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $json_content = file_get_contents($_FILES['jsonFile']['tmp_name']);
        $documents = json_decode($json_content, true);
        
        if (!$documents) {
            throw new Exception('Invalid JSON file');
        }
        
        ob_start();
        $output = "Loading JSON file...\n";
        $output .= "✓ Loaded " . count($documents) . " documents\n\n";
        
        // Extract book info
        $book_info = [
            'book_name' => 'Laws of the Night - Vampire the Masquerade',
            'book_code' => 'LOTN-VTM',
            'source' => $documents[0]['metadata']['source'] ?? 'Unknown',
            'category' => 'Core',
            'system' => 'MET-VTM',
            'total_pages' => 0,
            'total_chunks' => count($documents)
        ];
        
        foreach ($documents as $doc) {
            if ($doc['page'] > $book_info['total_pages']) {
                $book_info['total_pages'] = $doc['page'];
            }
        }
        
        $output .= "Processing book metadata...\n";
        $output .= "Book: {$book_info['book_name']}\n";
        $output .= "Pages: {$book_info['total_pages']}, Chunks: {$book_info['total_chunks']}\n\n";
        
        // Insert book
        $book_id = db_execute($conn,
            "INSERT INTO rag_books (book_name, book_code, source, category, system, total_pages, total_chunks) 
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
             book_name = VALUES(book_name),
             source = VALUES(source),
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
            $existing = db_fetch_one($conn, "SELECT id FROM rag_books WHERE book_code = ?", "s", [$book_info['book_code']]);
            $book_id = $existing['id'];
        }
        
        $output .= "Creating book record... Book ID: $book_id\n\n";
        $output .= "Importing documents and generating embeddings...\n";
        $output .= "(This may take 1-2 minutes)\n\n";
        
        $imported = 0;
        
        foreach ($documents as $i => $doc) {
            db_begin_transaction($conn);
            
            try {
                $metadata_json = json_encode($doc['metadata']);
                
                $doc_id = db_execute($conn,
                    "INSERT INTO rag_documents (book_id, doc_id, page, chunk_index, total_chunks, content, content_type, metadata)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE content = VALUES(content)",
                    "isiissss",
                    [$book_id, $doc['id'], $doc['page'], $doc['chunk_index'], $doc['total_chunks'], $doc['content'], $doc['content_type'], $metadata_json]
                );
                
                if (!$doc_id) {
                    $existing_doc = db_fetch_one($conn, "SELECT id FROM rag_documents WHERE book_id = ? AND doc_id = ?", "is", [$book_id, $doc['id']]);
                    $doc_id = $existing_doc['id'];
                }
                
                $embedding = create_simple_embedding($doc['content']);
                $embedding_binary = pack('f*', ...$embedding);
                
                db_execute($conn,
                    "INSERT INTO rag_embeddings (document_id, embedding, embedding_model, dimension)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE embedding = VALUES(embedding)",
                    "issi",
                    [$doc_id, $embedding_binary, 'simple_tfidf', 1024]
                );
                
                db_commit($conn);
                $imported++;
                
                if (($i + 1) % 25 === 0) {
                    $output .= "Progress: " . ($i + 1) . "/" . count($documents) . " documents...\n";
                }
                
            } catch (Exception $e) {
                db_rollback($conn);
                throw $e;
            }
        }
        
        $output .= "\n✓ Successfully imported: $imported documents\n";
        $output .= "✓ Database ready!\n";
        
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'message' => $output,
            'imported' => $imported
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Test system
 */
else if ($action === 'test_system') {
    try {
        ob_start();
        $output = "Running system tests...\n\n";
        
        // Test 1: Books
        $books = get_all_books($conn);
        $output .= "✓ Books: " . count($books) . " found\n";
        
        // Test 2: Documents
        $doc_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM rag_documents");
        $output .= "✓ Documents: {$doc_count['count']} found\n";
        
        // Test 3: Embeddings
        $emb_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM rag_embeddings");
        $output .= "✓ Embeddings: {$emb_count['count']} found\n\n";
        
        // Test 4: Search
        if ($doc_count['count'] > 0) {
            $query_embedding = create_simple_embedding('test query');
            $results = hybrid_search($conn, 'test query', $query_embedding, [], 3);
            $output .= "✓ Search: Working (" . count($results) . " results)\n";
        }
        
        // Test 5: LM Studio
        $lm_test = query_lm_studio("Test", "Test context", []);
        if ($lm_test['success']) {
            $output .= "✓ LM Studio: Connected\n";
        } else {
            $output .= "⚠ LM Studio: Not available (will use Claude fallback)\n";
        }
        
        // Test 6: Claude
        $anthropic_key = getenv('ANTHROPIC_API_KEY');
        if ($anthropic_key) {
            $output .= "✓ Claude API: Key configured\n";
        } else {
            $output .= "⚠ Claude API: No key found\n";
        }
        
        $output .= "\n✓ All tests completed!\n";
        $output .= "\nSystem is ready to use.\n";
        
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'message' => $output
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action'
    ]);
}

$conn->close();
?>
