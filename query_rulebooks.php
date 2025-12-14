<?php
/**
 * Direct Database Query for Rulebooks
 * Run this via: php query_rulebooks.php
 */

require_once __DIR__ . '/includes/connect.php';

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error() . "\n");
}

echo "✅ Connected to database successfully!\n\n";

// Check if rulebooks table exists
$tables_query = "SHOW TABLES LIKE 'rulebooks'";
$result = mysqli_query($conn, $tables_query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "❌ Rulebooks table does not exist in the database.\n";
    mysqli_close($conn);
    exit;
}

echo "✅ Rulebooks table exists!\n\n";

// Get count of books
$count_query = "SELECT COUNT(*) as total FROM rulebooks";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
echo "📚 Total books in database: " . $count_row['total'] . "\n\n";

// Get list of all books with their page counts
$books_query = "SELECT id, title, category, system, total_pages, 
                (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) as extracted_pages
                FROM rulebooks 
                ORDER BY title";
$books_result = mysqli_query($conn, $books_query);

if (!$books_result) {
    echo "❌ Error querying books: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit;
}

echo "=== Books in Database ===\n\n";

$books_with_pages = 0;
$books_without_pages = 0;
$total_extracted_pages = 0;
$book_list = [];

while ($book = mysqli_fetch_assoc($books_result)) {
    $has_content = ($book['extracted_pages'] > 0);
    $status = $has_content ? "✅" : "⚠️";
    
    $book_list[] = [
        'id' => $book['id'],
        'title' => $book['title'],
        'category' => $book['category'] ?? 'N/A',
        'system' => $book['system'] ?? 'N/A',
        'pdf_pages' => $book['total_pages'] ?? 0,
        'extracted_pages' => $book['extracted_pages'],
        'has_content' => $has_content
    ];
    
    echo $status . " [" . $book['id'] . "] " . $book['title'] . "\n";
    echo "   Category: " . ($book['category'] ?? 'N/A') . "\n";
    echo "   System: " . ($book['system'] ?? 'N/A') . "\n";
    echo "   PDF Pages: " . ($book['total_pages'] ?? 0) . "\n";
    echo "   Extracted Pages: " . $book['extracted_pages'] . "\n";
    echo "\n";
    
    if ($has_content) {
        $books_with_pages++;
        $total_extracted_pages += $book['extracted_pages'];
    } else {
        $books_without_pages++;
    }
}

echo "\n=== Summary ===\n";
echo "✅ Books with extracted content: " . $books_with_pages . "\n";
echo "⚠️ Books without extracted content: " . $books_without_pages . "\n";
echo "📄 Total extracted pages: " . number_format($total_extracted_pages) . "\n";

// Check rulebook_pages table
$pages_query = "SELECT COUNT(*) as total FROM rulebook_pages";
$pages_result = mysqli_query($conn, $pages_query);
if ($pages_result) {
    $pages_row = mysqli_fetch_assoc($pages_result);
    echo "📑 Total pages in rulebook_pages table: " . number_format($pages_row['total']) . "\n";
}

// Show sample of page content if available
if ($books_with_pages > 0) {
    echo "\n=== Sample Content Check ===\n";
    $sample_query = "SELECT rp.rulebook_id, r.title, COUNT(rp.id) as page_count, 
                     LENGTH(rp.content) as content_length
                     FROM rulebook_pages rp
                     JOIN rulebooks r ON rp.rulebook_id = r.id
                     GROUP BY rp.rulebook_id, r.title
                     ORDER BY page_count DESC
                     LIMIT 5";
    $sample_result = mysqli_query($conn, $sample_query);
    if ($sample_result && mysqli_num_rows($sample_result) > 0) {
        echo "Top 5 books by page count:\n";
        while ($sample = mysqli_fetch_assoc($sample_result)) {
            echo "  - " . $sample['title'] . ": " . $sample['page_count'] . " pages, " . 
                 number_format($sample['content_length']) . " characters\n";
        }
    }
}

mysqli_close($conn);
echo "\n✅ Query complete!\n";
?>
