<?php
/**
 * RAG Database Setup Script
 * This script creates the optimized database schema for the Laws Agent RAG system
 * Run this once to set up the database structure
 */

require_once __DIR__ . '/../../includes/connect.php';

echo "=== RAG Database Setup ===\n\n";

// Step 1: Drop old tables if they exist
echo "Step 1: Removing old laws_agent tables...\n";

$old_tables = [
    'laws_agent_documents',
    'laws_agent_embeddings', 
    'laws_agent_conversations',
    'laws_agent_sources'
];

foreach ($old_tables as $table) {
    $result = mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
    if ($result) {
        echo "  ✓ Dropped table: $table\n";
    } else {
        echo "  ! Table $table didn't exist or couldn't be dropped\n";
    }
}

echo "\n";

// Step 2: Create new optimized tables
echo "Step 2: Creating new RAG tables...\n";

// Books table - stores metadata about each book
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
    echo "  ✓ Created table: rag_books\n";
} else {
    die("  ✗ Error creating rag_books: " . mysqli_error($conn) . "\n");
}

// Documents table - stores the actual document chunks
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
    echo "  ✓ Created table: rag_documents\n";
} else {
    die("  ✗ Error creating rag_documents: " . mysqli_error($conn) . "\n");
}

// Embeddings table - stores vector embeddings for semantic search
$create_embeddings = "
CREATE TABLE IF NOT EXISTS `rag_embeddings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT NOT NULL,
    `embedding` BLOB NOT NULL,
    `embedding_model` VARCHAR(50) DEFAULT 'claude-3-5-sonnet',
    `dimension` INT DEFAULT 1024,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `rag_documents`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_embedding` (`document_id`, `embedding_model`),
    INDEX `idx_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($conn, $create_embeddings)) {
    echo "  ✓ Created table: rag_embeddings\n";
} else {
    die("  ✗ Error creating rag_embeddings: " . mysqli_error($conn) . "\n");
}

// User conversations table - stores chat history
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
    echo "  ✓ Created table: rag_conversations\n";
} else {
    die("  ✗ Error creating rag_conversations: " . mysqli_error($conn) . "\n");
}

// User preferences table - stores user settings
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
    echo "  ✓ Created table: rag_user_preferences\n";
} else {
    die("  ✗ Error creating rag_user_preferences: " . mysqli_error($conn) . "\n");
}

echo "\n";
echo "=== Database setup complete! ===\n";
echo "\nNext steps:\n";
echo "1. Run import_rag_data.php to load your JSON data\n";
echo "2. The system will automatically generate embeddings during import\n";
echo "3. Test the Laws Agent interface\n\n";

$conn->close();
?>
