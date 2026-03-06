<?php
/**
 * Simple Rulebooks Database Query
 * This script outputs results to a text file
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/supabase_client.php';

$output_file = __DIR__ . '/rulebooks_database_report.txt';
$output = fopen($output_file, 'w');

fwrite($output, "✅ Connected to database successfully!\n\n");

try {
    $rulebooks = supabase_table_get('rulebooks', [
        'select' => 'id,title,category,system,total_pages',
        'order' => 'title.asc'
    ]);
} catch (Throwable $e) {
    $error = "❌ Error querying books: " . $e->getMessage() . "\n";
    fwrite($output, $error);
    fclose($output);
    die($error);
}

try {
    $rulebookPages = supabase_table_get('rulebook_pages', [
        'select' => 'id,rulebook_id'
    ]);
} catch (Throwable $e) {
    $error = "❌ Error querying pages: " . $e->getMessage() . "\n";
    fwrite($output, $error);
    fclose($output);
    die($error);
}

fwrite($output, "📚 Total books in database: " . count($rulebooks) . "\n\n");

$pageCountsByBook = [];
foreach ($rulebookPages as $page) {
    $rulebookId = (int) ($page['rulebook_id'] ?? 0);
    if ($rulebookId <= 0) {
        continue;
    }
    $pageCountsByBook[$rulebookId] = ($pageCountsByBook[$rulebookId] ?? 0) + 1;
}

fwrite($output, "=== Books in Database ===\n\n");

$books_with_pages = 0;
$books_without_pages = 0;
$total_extracted_pages = 0;

foreach ($rulebooks as $book) {
    $bookId = (int) ($book['id'] ?? 0);
    $extractedPages = (int) ($pageCountsByBook[$bookId] ?? 0);
    $has_content = ($extractedPages > 0);
    $status = $has_content ? "✅" : "⚠️";
    
    $line = sprintf("%s [%d] %s\n   Category: %s\n   System: %s\n   PDF Pages: %d\n   Extracted Pages: %d\n\n",
        $status,
        $bookId,
        (string) ($book['title'] ?? 'Untitled'),
        $book['category'] ?? 'N/A',
        $book['system'] ?? 'N/A',
        $book['total_pages'] ?? 0,
        $extractedPages
    );
    
    fwrite($output, $line);
    
    if ($has_content) {
        $books_with_pages++;
        $total_extracted_pages += $extractedPages;
    } else {
        $books_without_pages++;
    }
}

fwrite($output, "\n=== Summary ===\n");
fwrite($output, "✅ Books with extracted content: " . $books_with_pages . "\n");
fwrite($output, "⚠️ Books without extracted content: " . $books_without_pages . "\n");
fwrite($output, "📄 Total extracted pages: " . number_format($total_extracted_pages) . "\n");

fwrite($output, "📑 Total pages in rulebook_pages table: " . number_format(count($rulebookPages)) . "\n");

fwrite($output, "\n✅ Report complete! Saved to: " . $output_file . "\n");

fclose($output);

echo "✅ Report generated! Check: " . $output_file . "\n";
?>
