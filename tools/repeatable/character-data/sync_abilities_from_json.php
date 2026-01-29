<?php
/**
 * Sync missing abilities from character JSON files into the database.
 *
 * Scans JSON files in reference/Characters/Added to Database for characters
 * that have 0 abilities in the DB but have abilities in JSON. Inserts those
 * abilities. Supports multiple JSON ability formats (string "Name 3", object
 * with name/ability_name, category/ability_category, level, specialization;
 * optional specializations map).
 *
 * Usage:
 *   CLI: php sync_abilities_from_json.php [--dry-run|--execute]
 *   Web: sync_abilities_from_json.php?dry_run=1 (preview)
 *        POST to sync_abilities_from_json.php with execute=1 (run)
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_cli = php_sapi_name() === 'cli';
$argv = $argv ?? [];
$dry_run_explicit = ($is_cli && in_array('--dry-run', $argv, true))
    || (isset($_GET['dry_run']) && $_GET['dry_run'] === '1')
    || (isset($_POST['dry_run']) && $_POST['dry_run'] === '1');
$execute = ($is_cli && in_array('--execute', $argv, true))
    || (isset($_POST['execute']) && $_POST['execute'] === '1')
    || (isset($_GET['execute']) && $_GET['execute'] === '1');
$dry_run = !$execute || $dry_run_explicit;

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        echo '<!DOCTYPE html><html><head><title>Sync Abilities</title></head><body>';
        echo '<p>Access denied. Admin required.</p>';
        echo '<a href="index.php">Back to Character Data</a></body></html>';
        exit;
    }
}

require_once __DIR__ . '/../../../includes/connect.php';
require_once __DIR__ . '/parse_abilities_helper.php';

if (!$conn) {
    $err = "Database connection failed.";
    if ($is_cli) {
        die($err . "\n");
    }
    echo '<!DOCTYPE html><html><head><title>Sync Abilities</title></head><body><p>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="index.php">Back to Character Data</a></body></html>';
    exit;
}

$do_run = $execute && !$dry_run;

$json_dir = __DIR__ . '/../../../reference/Characters/Added to Database';
if (!is_dir($json_dir)) {
    $err = "JSON directory not found: " . $json_dir;
    if ($is_cli) {
        die($err . "\n");
    }
    echo '<!DOCTYPE html><html><head><title>Sync Abilities</title></head><body><p>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="index.php">Back to Character Data</a></body></html>';
    exit;
}

$col_check = db_fetch_all($conn, "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'");
$has_category_column = !empty($col_check);

$chars_no_abilities = db_fetch_all($conn,
    "SELECT c.id, c.character_name FROM characters c " .
    "WHERE (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) = 0 ORDER BY c.id"
);
$by_id = [];
$by_name = [];
foreach ($chars_no_abilities as $r) {
    $id = (int)$r['id'];
    $by_id[$id] = $r;
    $nameKey = strtolower(trim(preg_replace('/\s+/', ' ', $r['character_name'] ?? '')));
    if ($nameKey !== '') {
        $by_name[$nameKey] = $r;
    }
}

$json_files = glob($json_dir . '/*.json');
usort($json_files, function (string $a, string $b): int {
    $anpc = preg_match('/npc__.+\d+\.json$/i', basename($a)) ? 0 : 1;
    $bnpc = preg_match('/npc__.+\d+\.json$/i', basename($b)) ? 0 : 1;
    if ($anpc !== $bnpc) {
        return $anpc <=> $bnpc;
    }
    return strcmp(basename($a), basename($b));
});
$stats = ['characters_processed' => 0, 'abilities_inserted' => 0, 'errors' => [], 'skipped_no_match' => 0, 'skipped_has_abilities' => 0, 'skipped_matched_no_abilities' => 0];
$log_lines = [];
$log_matched_no_abilities = [];
$logged_matched_no_abilities_cids = [];

function lookup_category(mysqli $conn, string $ability_name, bool $has_col): ?string {
    if (!$has_col) {
        return '';
    }
    $row = db_fetch_one($conn, "SELECT category FROM abilities WHERE name COLLATE utf8mb4_unicode_ci = ? LIMIT 1", "s", [$ability_name]);
    return $row ? (trim($row['category'] ?? '') ?: null) : null;
}

foreach ($json_files as $path) {
    $raw = @file_get_contents($path);
    if ($raw === false) {
        $stats['errors'][] = "Read failed: " . basename($path);
        continue;
    }
    $json_data = json_decode($raw, true);
    if (!is_array($json_data)) {
        continue;
    }

    $filename = basename($path);
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $char_id = null;
    if (preg_match('/npc__.+__(\d+)\.json$/', $filename, $m)) {
        $char_id = (int)$m[1];
    } elseif (isset($json_data['id']) && (int)$json_data['id'] > 0) {
        $char_id = (int)$json_data['id'];
    }

    $db_char = null;
    $match_via = null;
    if ($char_id !== null && isset($by_id[$char_id])) {
        $db_char = $by_id[$char_id];
        $match_via = 'id';
    } elseif (isset($json_data['character_name'])) {
        $n = strtolower(trim(preg_replace('/\s+/', ' ', $json_data['character_name'])));
        if ($n !== '' && isset($by_name[$n])) {
            $db_char = $by_name[$n];
            $match_via = 'character_name';
        }
    }
    if ($db_char === null && !preg_match('/^npc__.+\d+$/', $base)) {
        $candidates = [strtolower(trim(str_replace('_', ' ', $base)))];
        $noTrail = preg_replace('/[\d_]+$/', '', trim($base));
        if ($noTrail !== '') {
            $candidates[] = strtolower(trim(str_replace('_', ' ', $noTrail)));
        }
        foreach ($candidates as $c) {
            if ($c !== '' && isset($by_name[$c])) {
                $db_char = $by_name[$c];
                $match_via = 'filename';
                break;
            }
        }
    }

    if ($db_char === null) {
        $stats['skipped_no_match']++;
        continue;
    }

    $cid = (int)$db_char['id'];

    $count_row = db_fetch_one($conn, "SELECT COUNT(*) AS n FROM character_abilities WHERE character_id = ?", "i", [$cid]);
    $existing = (int)($count_row['n'] ?? 0);
    if ($existing > 0) {
        $stats['skipped_has_abilities']++;
        continue;
    }

    $parsed = parse_abilities_from_character_json($json_data);
    if (count($parsed) === 0) {
        if (!in_array($cid, $logged_matched_no_abilities_cids, true)) {
            $logged_matched_no_abilities_cids[] = $cid;
            $stats['skipped_matched_no_abilities']++;
            $via = $match_via ? " [match:{$match_via}]" : '';
            $log_matched_no_abilities[] = sprintf("  id=%d %s <- %s%s", $cid, trim($db_char['character_name'] ?? '?'), $filename, $via);
        }
        continue;
    }

    $inserted = 0;
    foreach ($parsed as $a) {
        $cat = lookup_category($conn, $a['name'], $has_category_column);
        if ($cat === null) {
            $cat = $a['category'];
        }

        if ($do_run) {
            if ($has_category_column) {
                $res = db_execute($conn,
                    "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?)",
                    "issis",
                    [$cid, $a['name'], $cat, $a['level'], $a['specialization']]
                );
            } else {
                $res = db_execute($conn,
                    "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)",
                    "isis",
                    [$cid, $a['name'], $a['level'], $a['specialization']]
                );
            }
            if ($res !== false) {
                $inserted++;
            } else {
                $stats['errors'][] = "Insert ability '{$a['name']}' char_id={$cid}: " . mysqli_error($conn);
            }
        } else {
            $inserted++;
        }
    }

    if ($inserted > 0) {
        $stats['characters_processed']++;
        $stats['abilities_inserted'] += $inserted;
        $via = $match_via ? " [match:{$match_via}]" : '';
        $log_lines[] = sprintf("char_id=%d %s: %d abilities from %s%s", $cid, $db_char['character_name'] ?? '?', $inserted, $filename, $via);
    }
}

$log_dir = __DIR__ . '/../../../tools/repeatable/';
$log_file = $log_dir . 'abilities-sync-' . date('Ymd-His') . '.log';
$log_content = "Sync abilities " . ($do_run ? "EXECUTE" : "DRY-RUN") . " " . date('c') . "\n";
$log_content .= "Characters with 0 abilities (candidates): " . count($chars_no_abilities) . "\n";
foreach ($chars_no_abilities as $r) {
    $log_content .= "  id=" . $r['id'] . " " . trim($r['character_name'] ?? '') . "\n";
}
$log_content .= "Characters processed: " . $stats['characters_processed'] . "\n";
$log_content .= "Abilities inserted: " . $stats['abilities_inserted'] . "\n";
$log_content .= "Skipped (no JSON match): " . $stats['skipped_no_match'] . "\n";
$log_content .= "Skipped (already has abilities): " . $stats['skipped_has_abilities'] . "\n";
$log_content .= "Skipped (matched, but JSON has no abilities): " . $stats['skipped_matched_no_abilities'] . "\n";
if (!empty($log_matched_no_abilities)) {
    $log_content .= "Matched JSONs with no abilities:\n";
    foreach ($log_matched_no_abilities as $l) {
        $log_content .= $l . "\n";
    }
}
foreach ($log_lines as $l) {
    $log_content .= $l . "\n";
}
foreach ($stats['errors'] as $e) {
    $log_content .= "ERROR: " . $e . "\n";
}
@file_put_contents($log_file, $log_content);

if ($is_cli) {
    echo $log_content;
    exit(0);
}

if ($do_run) {
    if (empty($stats['errors'])) {
        $_SESSION['abilities_sync_message'] = [
            'type' => $stats['characters_processed'] > 0 ? 'success' : 'info',
            'text' => $stats['characters_processed'] > 0
                ? 'Abilities sync complete. ' . $stats['characters_processed'] . ' character(s) updated, ' . $stats['abilities_inserted'] . ' abilities inserted. Log: ' . basename($log_file)
                : 'Abilities sync complete. No characters needed updating. Log: ' . basename($log_file)
        ];
    } else {
        $_SESSION['abilities_sync_message'] = [
            'type' => 'warning',
            'text' => 'Abilities sync finished with errors. ' . $stats['characters_processed'] . ' updated, ' . $stats['abilities_inserted'] . ' abilities. ' . count($stats['errors']) . ' error(s). Log: ' . basename($log_file)
        ];
    }
    header('Location: index.php');
    exit;
}

$title = $dry_run ? 'Sync Abilities (dry run)' : ($do_run ? 'Sync Abilities (execute)' : 'Sync Abilities');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container py-4">
        <h1 class="h3 mb-4"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <pre class="bg-black text-white p-3 rounded overflow-auto" style="max-height: 60vh;"><?php echo htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8'); ?></pre>
        <p class="text-white">Log written to <code><?php echo htmlspecialchars(basename($log_file), ENT_QUOTES, 'UTF-8'); ?></code></p>
        <a href="index.php" class="btn btn-primary">Back to Character Data</a>
        <?php if ($dry_run && $stats['characters_processed'] > 0): ?>
            <form method="POST" action="" class="d-inline ms-2" onsubmit="return confirm('Add missing abilities to the database for these characters?');">
                <input type="hidden" name="execute" value="1">
                <button type="submit" class="btn btn-success">Run sync (execute)</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
