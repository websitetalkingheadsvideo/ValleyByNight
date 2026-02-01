<?php
/**
 * RAG System Test Script
 * Tests database connection, search functions, and AI integration
 */

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/rag_functions.php';

echo "=== RAG System Test ===\n\n";

// Test 1: Database Connection
echo "Test 1: Database Connection\n";
if ($conn) {
    echo "  ✓ Database connected successfully\n\n";
} else {
    die("  ✗ Database connection failed\n");
}

// Test 2: Check Books
echo "Test 2: Check Books in Database\n";
$books = get_all_books($conn);
if (empty($books)) {
    echo "  ⚠ No books found. Have you run import_rag_data.php?\n\n";
} else {
    echo "  ✓ Found " . count($books) . " book(s):\n";
    foreach ($books as $book) {
        echo "    - {$book['book_name']} ({$book['book_code']})\n";
        echo "      Pages: {$book['total_pages']}, Chunks: {$book['total_chunks']}\n";
    }
    echo "\n";
}

// Test 3: Check Documents
echo "Test 3: Check Documents\n";
$doc_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM rag_documents");
if ($doc_count['count'] == 0) {
    echo "  ⚠ No documents found. Run import_rag_data.php first.\n\n";
} else {
    echo "  ✓ Found {$doc_count['count']} documents\n\n";
}

// Test 4: Check Embeddings
echo "Test 4: Check Embeddings\n";
$emb_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM rag_embeddings");
if ($emb_count['count'] == 0) {
    echo "  ⚠ No embeddings found. Run import_rag_data.php first.\n\n";
} else {
    echo "  ✓ Found {$emb_count['count']} embeddings\n\n";
}

// Test 5: Test Keyword Search
if ($doc_count['count'] > 0) {
    echo "Test 5: Keyword Search\n";
    echo "  Query: 'Celerity discipline'\n";
    
    $keyword_results = keyword_search($conn, 'Celerity discipline', [], 3);
    
    if (empty($keyword_results)) {
        echo "  ⚠ No results found\n\n";
    } else {
        echo "  ✓ Found " . count($keyword_results) . " results:\n";
        foreach ($keyword_results as $i => $result) {
            echo "    " . ($i + 1) . ". {$result['book_name']}, Page {$result['page']}\n";
            echo "       Relevance: " . round($result['relevance'], 2) . "\n";
            echo "       Excerpt: " . substr($result['content'], 0, 80) . "...\n";
        }
        echo "\n";
    }
}

// Test 6: Test Semantic Search
if ($emb_count['count'] > 0) {
    echo "Test 6: Semantic Search\n";
    echo "  Query: 'What are vampiric powers?'\n";
    
    $query_embedding = create_simple_embedding('What are vampiric powers?');
    $semantic_results = semantic_search($conn, $query_embedding, [], 3);
    
    if (empty($semantic_results)) {
        echo "  ⚠ No results found\n\n";
    } else {
        echo "  ✓ Found " . count($semantic_results) . " results:\n";
        foreach ($semantic_results as $i => $result) {
            echo "    " . ($i + 1) . ". {$result['book_name']}, Page {$result['page']}\n";
            echo "       Similarity: " . round($result['similarity'], 3) . "\n";
            echo "       Excerpt: " . substr($result['content'], 0, 80) . "...\n";
        }
        echo "\n";
    }
}

// Test 7: Test Hybrid Search
if ($doc_count['count'] > 0 && $emb_count['count'] > 0) {
    echo "Test 7: Hybrid Search (Semantic + Keyword)\n";
    echo "  Query: 'How does blood bonding work?'\n";
    
    $query = 'How does blood bonding work?';
    $query_embedding = create_simple_embedding($query);
    $hybrid_results = hybrid_search($conn, $query, $query_embedding, [], 5);
    
    if (empty($hybrid_results)) {
        echo "  ⚠ No results found\n\n";
    } else {
        echo "  ✓ Found " . count($hybrid_results) . " results:\n";
        foreach ($hybrid_results as $i => $result) {
            echo "    " . ($i + 1) . ". {$result['book_name']}, Page {$result['page']}\n";
            echo "       Score: " . round($result['search_score'], 3) . "\n";
            echo "       Type: {$result['content_type']}\n";
        }
        echo "\n";
    }
}

// Test 8: Test LM Studio Connection
echo "Test 8: LM Studio Connection\n";
echo "  Endpoint: http://192.168.0.217:1234/v1/chat/completions\n";

$test_response = query_lm_studio(
    "What is a vampire?",
    "A vampire is an undead creature that drinks blood.",
    []
);

if ($test_response['success']) {
    echo "  ✓ LM Studio is responding\n";
    echo "    Model: {$test_response['model']}\n";
    echo "    Sample response: " . substr($test_response['answer'], 0, 100) . "...\n\n";
} else {
    echo "  ✗ LM Studio error: {$test_response['error']}\n";
    echo "    (This is OK if you're using Claude fallback)\n\n";
}

// Test 9: Test Claude API (if key exists)
echo "Test 9: Claude API Connection\n";
$anthropic_api_key = getenv('ANTHROPIC_API_KEY');

if (!$anthropic_api_key) {
    echo "  ⚠ ANTHROPIC_API_KEY not found in environment\n\n";
} else {
    echo "  API Key found: " . substr($anthropic_api_key, 0, 8) . "...\n";
    
    $test_response = query_claude(
        "What is a vampire?",
        "A vampire is an undead creature that drinks blood.",
        [],
        $anthropic_api_key
    );
    
    if ($test_response['success']) {
        echo "  ✓ Claude API is responding\n";
        echo "    Model: {$test_response['model']}\n";
        echo "    Sample response: " . substr($test_response['answer'], 0, 100) . "...\n\n";
    } else {
        echo "  ✗ Claude API error: {$test_response['error']}\n\n";
    }
}

// Summary
echo "=== Test Summary ===\n\n";

if ($doc_count['count'] > 0 && $emb_count['count'] > 0) {
    echo "✓ System is ready to use!\n";
    echo "  - Database: Connected\n";
    echo "  - Books: " . count($books) . " loaded\n";
    echo "  - Documents: {$doc_count['count']} indexed\n";
    echo "  - Embeddings: {$emb_count['count']} generated\n";
    echo "  - Search: Working\n";
    
    if ($test_response['success']) {
        echo "  - AI: " . ($test_response['model'] === 'claude-3.5-sonnet' ? 'Claude' : 'LM Studio') . " working\n";
    }
    
    echo "\nNext step: Open http://192.168.0.155/agents/ in your browser!\n";
} else {
    echo "⚠ Setup incomplete\n";
    echo "\nNext steps:\n";
    if (count($books) == 0) {
        echo "1. Run: php import_rag_data.php /path/to/rag_documents.json\n";
    }
    echo "2. Run this test again to verify\n";
}

echo "\n";

$conn->close();
?>
