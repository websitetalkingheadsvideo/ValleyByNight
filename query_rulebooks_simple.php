<?php
/**
 * Simple Rulebooks Database Query
 * This script outputs results to a text file
 */

require_once __DIR__ . '/includes/connect.php';

$output_file = __DIR__ . '/rulebooks_database_report.txt';
$output = fopen($output_file, 'w');

if (!$conn) {
    $error = "❌ Database connection failed: " . mysqli_connect_error() . "\n";
    fwrite($output, $error);
    fclose($output);
    die($error);
}

fwrite($output, "✅ Connected to database successfully!\n\n");

// Check if rulebooks table exists
$tables_query = "SHOW TABLES LIKE 'rulebooks'";
$result = mysqli_query($conn, $tables_query);

if (!$result || mysqli_num_rows($result) == 0) {
    $error = "❌ Rulebooks table does not exist in the database.\n";
    fwrite($output, $error);
    fclose($output);
    die($error);
}

fwrite($output, "✅ Rulebooks table exists!\n\n");

// Get count of books
$count_query = "SELECT COUNT(*) as total FROM rulebooks";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
fwrite($output, "📚 Total books in database: " . $count_row['total'] . "\n\n");

// Get list of all books
$books_query = "SELECT id, title, category, system, total_pages, 
                (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) as extracted_pages
                FROM rulebooks 
                ORDER BY title";
$books_result = mysqli_query($conn, $books_query);

if (!$books_result) {
    $error = "❌ Error querying books: " . mysqli_error($conn) . "\n";
    fwrite($output, $error);
    fclose($output);
    die($error);
}

fwrite($output, "=== Books in Database ===\n\n");

$books_with_pages = 0;
$books_without_pages = 0;
$total_extracted_pages = 0;

while ($book = mysqli_fetch_assoc($books_result)) {
    $has_content = ($book['extracted_pages'] > 0);
    $status = $has_content ? "✅" : "⚠️";
    
    $line = sprintf("%s [%d] %s\n   Category: %s\n   System: %s\n   PDF Pages: %d\n   Extracted Pages: %d\n\n",
        $status,
        $book['id'],
        $book['title'],
        $book['category'] ?? 'N/A',
        $book['system'] ?? 'N/A',
        $book['total_pages'] ?? 0,
        $book['extracted_pages']
    );
    
    fwrite($output, $line);
    
    if ($has_content) {
        $books_with_pages++;
        $total_extracted_pages += $book['extracted_pages'];
    } else {
        $books_without_pages++;
    }
}

fwrite($output, "\n=== Summary ===\n");
fwrite($output, "✅ Books with extracted content: " . $books_with_pages . "\n");
fwrite($output, "⚠️ Books without extracted content: " . $books_without_pages . "\n");
fwrite($output, "📄 Total extracted pages: " . number_format($total_extracted_pages) . "\n");

// Check rulebook_pages table
$pages_query = "SELECT COUNT(*) as total FROM rulebook_pages";
$pages_result = mysqli_query($conn, $pages_query);
if ($pages_result) {
    $pages_row = mysqli_fetch_assoc($pages_result);
    fwrite($output, "📑 Total pages in rulebook_pages table: " . number_format($pages_row['total']) . "\n");
}

fwrite($output, "\n✅ Report complete! Saved to: " . $output_file . "\n");

fclose($output);
mysqli_close($conn);

echo "✅ Report generated! Check: " . $output_file . "\n";
?>
