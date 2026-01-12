<?php
/**
 * Check Books Database - Run When Site is Back Up
 * 
 * This script will check your rulebooks database and create a detailed report.
 * 
 * TO USE:
 * 1. Wait until your site is back up and database access is restored
 * 2. Upload this file to your server
 * 3. Access it via: check_books_when_ready.php
 * 4. It will create a file called 'books_report.txt with all the details
 */

require_once __DIR__ . '/../../includes/connect.php';

// Output file for the report
$report_file = __DIR__ . '/../../books_database_report.txt';
$fp = fopen($report_file, 'w');

if (!$conn) {
    $error = "❌ Database connection failed: " . mysqli_connect_error() . "\n";
    fwrite($fp, $error);
    fclose($fp);
    die($error);
}

fwrite($fp, "========================================\n");
fwrite($fp, "RULEBOOKS DATABASE REPORT\n");
fwrite($fp, "Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($fp, "========================================\n\n");

// Check if rulebooks table exists
$tables_query = "SHOW TABLES LIKE 'rulebooks'";
$result = mysqli_query($conn, $tables_query);

if (!$result || mysqli_num_rows($result) == 0) {
    fwrite($fp, "❌ Rulebooks table does not exist.\n");
    fclose($fp);
    die("Rulebooks table not found");
}

fwrite($fp, "✅ Rulebooks table exists!\n\n");

// Get total count
$count_query = "SELECT COUNT(*) as total FROM rulebooks";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
fwrite($fp, "📚 TOTAL BOOKS: " . $count_row['total'] . "\n\n");

// Get all books with details
$books_query = "SELECT id, title, category, system, total_pages, 
                (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) as extracted_pages
                FROM rulebooks 
                ORDER BY title";
$books_result = mysqli_query($conn, $books_query);

if (!$books_result) {
    fwrite($fp, "❌ Error: " . mysqli_error($conn) . "\n");
    fclose($fp);
    die("Query failed");
}

fwrite($fp, "========================================\n");
fwrite($fp, "ALL BOOKS IN DATABASE\n");
fwrite($fp, "========================================\n\n");

$books_with_content = 0;
$books_without_content = 0;
$total_extracted_pages = 0;

while ($book = mysqli_fetch_assoc($books_result)) {
    $has_content = ($book['extracted_pages'] > 0);
    $status = $has_content ? "✅" : "⚠️";
    
    fwrite($fp, sprintf("%s [ID: %d] %s\n", $status, $book['id'], $book['title']));
    fwrite($fp, "   Category: " . ($book['category'] ?? 'N/A') . "\n");
    fwrite($fp, "   System: " . ($book['system'] ?? 'N/A') . "\n");
    fwrite($fp, "   PDF Pages: " . ($book['total_pages'] ?? 0) . "\n");
    fwrite($fp, "   Extracted Pages: " . $book['extracted_pages'] . "\n");
    fwrite($fp, "\n");
    
    if ($has_content) {
        $books_with_content++;
        $total_extracted_pages += $book['extracted_pages'];
    } else {
        $books_without_content++;
    }
}

// Summary
fwrite($fp, "\n========================================\n");
fwrite($fp, "SUMMARY STATISTICS\n");
fwrite($fp, "========================================\n\n");
fwrite($fp, "Total Books: " . $count_row['total'] . "\n");
fwrite($fp, "Books WITH extracted content: " . $books_with_content . "\n");
fwrite($fp, "Books WITHOUT extracted content: " . $books_without_content . "\n");
fwrite($fp, "Total extracted pages: " . number_format($total_extracted_pages) . "\n");

// Total pages in rulebook_pages table
$pages_query = "SELECT COUNT(*) as total FROM rulebook_pages";
$pages_result = mysqli_query($conn, $pages_query);
if ($pages_result) {
    $pages_row = mysqli_fetch_assoc($pages_result);
    fwrite($fp, "Total pages in rulebook_pages table: " . number_format($pages_row['total']) . "\n");
}

fwrite($fp, "\n========================================\n");
fwrite($fp, "Report saved to: books_database_report.txt\n");
fwrite($fp, "========================================\n");

fclose($fp);
mysqli_close($conn);

echo "✅ Report generated successfully!<br>";
echo "📄 Check the file: <strong>books_database_report.txt</strong> in your project root.<br>";
echo "<br>";
echo "The report contains:<br>";
echo "- Complete list of all books<br>";
echo "- Which books have extracted content<br>";
echo "- Summary statistics<br>";
?>
