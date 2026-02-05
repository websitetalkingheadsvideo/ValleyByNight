<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/rag_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$result = db_fetch_one(
    $conn,
    'SELECT email_verified, username FROM users WHERE id = ?',
    'i',
    [$_SESSION['user_id']]
);

if (!$result || !$result['email_verified']) {
    $conn->close();
    die('Email verification required.');
}

set_time_limit(0);
ini_set('memory_limit', '512M');
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/html; charset=utf-8');

function derive_book_info_web(array $documents, string $json_file): array {
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

function import_one_book(string $json_file, mysqli $conn, ?callable $on_progress = null): array {
    $out = ['ok' => false, 'message' => '', 'imported' => 0];
    if (!file_exists($json_file)) {
        $out['message'] = 'File not found: ' . $json_file;
        return $out;
    }
    $json_content = file_get_contents($json_file);
    $documents = json_decode($json_content, true);
    if (!$documents) {
        $out['message'] = 'Invalid JSON';
        return $out;
    }
    $book_info = derive_book_info_web($documents, $json_file);

    $existing = db_fetch_one($conn, 'SELECT id FROM rag_books WHERE book_code = ?', 's', [$book_info['book_code']]);
    if ($existing) {
        $book_id = $existing['id'];
        db_execute($conn,
            'UPDATE rag_books SET book_name = ?, source = ?, category = ?, system = ?, total_pages = ?, total_chunks = ? WHERE id = ?',
            'sssssii',
            [
                $book_info['book_name'],
                $book_info['source'],
                $book_info['category'],
                $book_info['system'],
                $book_info['total_pages'],
                $book_info['total_chunks'],
                $book_id
            ]
        );
    } else {
        $book_id = db_execute($conn,
            "INSERT INTO rag_books (book_name, book_code, source, category, `system`, total_pages, total_chunks) VALUES (?, ?, ?, ?, ?, ?, ?)",
            'ssssiii',
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
    }
    if (!$book_id) {
        $existing = db_fetch_one($conn, 'SELECT id FROM rag_books WHERE book_code = ?', 's', [$book_info['book_code']]);
        $book_id = $existing ? $existing['id'] : null;
    }
    if (!$book_id) {
        $out['message'] = 'Failed to create book record';
        return $out;
    }

    $batch_size = 50;
    db_begin_transaction($conn);
    $imported = 0;
    try {
        $row_placeholders = '(?, ?, ?, ?, ?, ?, ?, ?)';
        $embed_row = '(?, ?, ?, ?)';
        for ($offset = 0; $offset < count($documents); $offset += $batch_size) {
            $batch = array_slice($documents, $offset, $batch_size);
            $doc_ids_batch = [];
            $params = [];
            $types = '';
            foreach ($batch as $doc) {
                $params[] = $book_id;
                $params[] = $doc['id'];
                $params[] = $doc['page'];
                $params[] = $doc['chunk_index'];
                $params[] = $doc['total_chunks'];
                $params[] = $doc['content'];
                $params[] = $doc['content_type'] ?? '';
                $params[] = json_encode($doc['metadata'] ?? []);
                $types .= 'isiissss';
                $doc_ids_batch[] = $doc['id'];
            }
            $rows = implode(', ', array_fill(0, count($batch), $row_placeholders));
            $sql = "INSERT INTO rag_documents (book_id, doc_id, page, chunk_index, total_chunks, content, content_type, metadata) VALUES $rows ON DUPLICATE KEY UPDATE content = VALUES(content)";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new RuntimeException('Prepare documents: ' . mysqli_error($conn));
            }
            $refs = [];
            foreach ($params as $k => $v) {
                $refs[$k] = &$params[$k];
            }
            array_unshift($refs, $types);
            if (!call_user_func_array([$stmt, 'bind_param'], $refs) || !$stmt->execute()) {
                throw new RuntimeException('Insert documents: ' . $stmt->error);
            }
            $stmt->close();

            $in_placeholders = implode(', ', array_fill(0, count($doc_ids_batch), '?'));
            $id_rows = db_fetch_all($conn, "SELECT id, doc_id FROM rag_documents WHERE book_id = ? AND doc_id IN ($in_placeholders)", 'i' . str_repeat('s', count($doc_ids_batch)), array_merge([$book_id], $doc_ids_batch));
            $doc_id_by_business = [];
            foreach ($id_rows as $r) {
                $doc_id_by_business[$r['doc_id']] = $r['id'];
            }

            $emb_params = [];
            $emb_types = '';
            foreach ($batch as $doc) {
                $rid = $doc_id_by_business[$doc['id']] ?? null;
                if ($rid === null) {
                    continue;
                }
                $emb = create_simple_embedding($doc['content']);
                $emb_bin = pack('f*', ...$emb);
                $emb_params[] = $rid;
                $emb_params[] = $emb_bin;
                $emb_params[] = 'simple_tfidf';
                $emb_params[] = 1024;
                $emb_types .= 'ibsi';
                $imported++;
            }
            if ($emb_params !== []) {
                $n = count($emb_params) / 4;
                $rows_emb = implode(', ', array_fill(0, $n, $embed_row));
                $sql_emb = "INSERT INTO rag_embeddings (document_id, embedding, embedding_model, dimension) VALUES $rows_emb ON DUPLICATE KEY UPDATE embedding = VALUES(embedding)";
                $stmt_emb = mysqli_prepare($conn, $sql_emb);
                if (!$stmt_emb) {
                    throw new RuntimeException('Prepare embeddings: ' . mysqli_error($conn));
                }
                $refs_emb = [];
                foreach ($emb_params as $k => $v) {
                    $refs_emb[$k] = &$emb_params[$k];
                }
                array_unshift($refs_emb, $emb_types);
                if (!call_user_func_array([$stmt_emb, 'bind_param'], $refs_emb) || !$stmt_emb->execute()) {
                    throw new RuntimeException('Insert embeddings: ' . $stmt_emb->error);
                }
                $stmt_emb->close();
            }
            if ($on_progress !== null) {
                $on_progress($imported, count($documents));
            }
        }
        db_commit($conn);
    } catch (Throwable $e) {
        db_rollback($conn);
        $out['message'] = $e->getMessage();
        return $out;
    }
    $out['ok'] = true;
    $out['imported'] = $imported;
    $out['message'] = $book_info['book_name'] . ' (' . $book_info['book_code'] . '): ' . $imported . ' chunks';
    return $out;
}

$books_dir = __DIR__ . '/Books';
$files = glob($books_dir . '/*.json');
$files = array_filter($files, static function ($path) {
    return strpos($path, DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR) === false;
});
sort($files);
$total = count($files);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Books – Laws Agent</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; background: #1a0f0f; color: #ccc; }
        h1 { color: #8b0000; }
        .log { background: #111; border: 1px solid #333; border-radius: 6px; padding: 1rem; margin: 1rem 0; font-family: monospace; font-size: 14px; white-space: pre-wrap; }
        .ok { color: #6a6; }
        .err { color: #c66; }
        a { color: #8b0000; }
        a:hover { color: #a00; }
    </style>
</head>
<body>
<h1>Import Books</h1>
<p>Importing <?php echo $total; ?> book(s) from <code>Books/</code>. This may take several minutes.</p>
<div class="log">
<?php
foreach ($files as $i => $json_file) {
    $num = $i + 1;
    echo htmlspecialchars("[$num/$total] " . basename($json_file) . " … ", ENT_QUOTES, 'UTF-8');
    flush();
    $progress_fn = static function () {
        echo '.';
        flush();
    };
    try {
        $result = import_one_book($json_file, $conn, $progress_fn);
        if ($result['ok']) {
            echo '<span class="ok">' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') . "</span>\n";
        } else {
            echo '<span class="err">' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') . "</span>\n";
        }
    } catch (Throwable $e) {
        echo '<span class="err">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</span>\n";
    }
    flush();
}
?>
</div>
<p><strong>Done.</strong> <a href="index.php">Back to Laws Agent</a></p>
</body>
</html>
<?php
$conn->close();
