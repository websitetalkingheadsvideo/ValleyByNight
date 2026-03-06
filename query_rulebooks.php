<?php
/**
 * Direct Database Query for Rulebooks
 * Run this via: php query_rulebooks.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/supabase_client.php';

echo "✅ Connected to Supabase successfully!\n\n";

try {
    $rulebooks = supabase_table_get('rulebooks', [
        'select' => 'id,title,category,system,total_pages',
        'order' => 'title.asc'
    ]);
} catch (Throwable $e) {
    echo "❌ Error querying books: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $rulebookPages = supabase_table_get('rulebook_pages', [
        'select' => 'id,rulebook_id,content'
    ]);
} catch (Throwable $e) {
    echo "❌ Error querying pages: " . $e->getMessage() . "\n";
    exit(1);
}

echo "📚 Total books in database: " . count($rulebooks) . "\n\n";

$pageCountsByBook = [];
foreach ($rulebookPages as $page) {
    $rulebookId = (int) ($page['rulebook_id'] ?? 0);
    if ($rulebookId <= 0) {
        continue;
    }
    $pageCountsByBook[$rulebookId] = ($pageCountsByBook[$rulebookId] ?? 0) + 1;
}

echo "=== Books in Database ===\n\n";

$books_with_pages = 0;
$books_without_pages = 0;
$total_extracted_pages = 0;
foreach ($rulebooks as $book) {
    $bookId = (int) ($book['id'] ?? 0);
    $extractedPages = (int) ($pageCountsByBook[$bookId] ?? 0);
    $has_content = ($extractedPages > 0);
    $status = $has_content ? "✅" : "⚠️";

    echo $status . " [" . $bookId . "] " . ($book['title'] ?? 'Untitled') . "\n";
    echo "   Category: " . ($book['category'] ?? 'N/A') . "\n";
    echo "   System: " . ($book['system'] ?? 'N/A') . "\n";
    echo "   PDF Pages: " . ($book['total_pages'] ?? 0) . "\n";
    echo "   Extracted Pages: " . $extractedPages . "\n";
    echo "\n";
    
    if ($has_content) {
        $books_with_pages++;
        $total_extracted_pages += $extractedPages;
    } else {
        $books_without_pages++;
    }
}

echo "\n=== Summary ===\n";
echo "✅ Books with extracted content: " . $books_with_pages . "\n";
echo "⚠️ Books without extracted content: " . $books_without_pages . "\n";
echo "📄 Total extracted pages: " . number_format($total_extracted_pages) . "\n";

echo "📑 Total pages in rulebook_pages table: " . number_format(count($rulebookPages)) . "\n";

// Show sample of page content if available
if ($books_with_pages > 0) {
    echo "\n=== Sample Content Check ===\n";
    $rulebookTitles = [];
    foreach ($rulebooks as $book) {
        $rulebookTitles[(int) ($book['id'] ?? 0)] = (string) ($book['title'] ?? 'Untitled');
    }

    $sampleRows = [];
    foreach ($pageCountsByBook as $bookId => $pageCount) {
        $contentLength = 0;
        foreach ($rulebookPages as $page) {
            if ((int) ($page['rulebook_id'] ?? 0) === (int) $bookId) {
                $contentLength += strlen((string) ($page['content'] ?? ''));
            }
        }
        $sampleRows[] = [
            'title' => $rulebookTitles[(int) $bookId] ?? ('Rulebook ' . $bookId),
            'page_count' => (int) $pageCount,
            'content_length' => $contentLength
        ];
    }

    usort($sampleRows, static function (array $a, array $b): int {
        return $b['page_count'] <=> $a['page_count'];
    });

    $sampleRows = array_slice($sampleRows, 0, 5);
    if (!empty($sampleRows)) {
        echo "Top 5 books by page count:\n";
        foreach ($sampleRows as $sample) {
            echo "  - " . $sample['title'] . ": " . $sample['page_count'] . " pages, " .
                number_format((int) $sample['content_length']) . " characters\n";
        }
    }
}
echo "\n✅ Query complete!\n";
?>
