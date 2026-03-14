<?php
/**
 * Assign character image: copy file to uploads/characters and set characters.portrait_name (Supabase).
 * CLI: php assign_character_image.php --id=70 --image="Alessandro Vescari.png" [--source=path/to/source.png]
 * If --source is omitted, only the DB is updated (file must already exist in uploads/characters).
 */
declare(strict_types=1);

$project_root = dirname(__DIR__, 4);
$env_file = $project_root . DIRECTORY_SEPARATOR . '.env';
if (is_file($env_file)) {
    $lines = @file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if ($k !== '') {
            putenv($k . '=' . $v);
        }
    }
}
require_once $project_root . '/includes/supabase_client.php';

$options = getopt('', ['id:', 'image:', 'source:', 'dry-run']);
$character_id = isset($options['id']) ? (int) $options['id'] : 0;
$image_filename = isset($options['image']) ? trim((string) $options['image']) : '';
$source_path = isset($options['source']) ? trim((string) $options['source']) : '';
$dry_run = isset($options['dry-run']);

if ($character_id <= 0 || $image_filename === '') {
    fwrite(STDERR, "Usage: php assign_character_image.php --id=CHARACTER_ID --image=FILENAME [--source=path/to/file] [--dry-run]\n");
    exit(1);
}

$upload_dir = $project_root . '/uploads/characters/';
$dest_path = $upload_dir . $image_filename;

if ($source_path !== '') {
    if (!is_file($source_path)) {
        fwrite(STDERR, "Error: Source file not found: {$source_path}\n");
        exit(1);
    }
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!$dry_run) {
        if (!copy($source_path, $dest_path)) {
            fwrite(STDERR, "Error: Failed to copy to {$dest_path}\n");
            exit(1);
        }
        echo "Copied to uploads/characters/" . $image_filename . "\n";
    } else {
        echo "[dry-run] Would copy " . $source_path . " to " . $dest_path . "\n";
    }
} else {
    if (!$dry_run && !is_file($dest_path)) {
        fwrite(STDERR, "Error: File not found in uploads/characters: {$image_filename}\n");
        exit(1);
    }
}

if ($dry_run) {
    echo "[dry-run] Would set portrait_name = " . $image_filename . " for character id " . $character_id . "\n";
    exit(0);
}

$result = supabase_rest_request(
    'PATCH',
    '/rest/v1/characters',
    ['id' => 'eq.' . $character_id],
    ['portrait_name' => $image_filename],
    ['Prefer: return=minimal']
);

if ($result['error'] !== null) {
    fwrite(STDERR, "Error: Database update failed: " . ($result['error']) . "\n");
    exit(1);
}

echo "Updated character id {$character_id} portrait_name to \"{$image_filename}\".\n";
