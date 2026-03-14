<?php
declare(strict_types=1);

/**
 * Character Images Audit API
 * Returns all characters with image status (Present / Missing / Broken) for admin reporting.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../includes/character_portrait_resolver.php';

$base_dir = dirname(__DIR__);
$upload_dir = $base_dir . '/uploads/characters';
$reference_dir = $base_dir . '/reference/Characters/Images';

try {
    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name,portrait_name',
        'order' => 'character_name.asc',
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load characters: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$characters = [];
$summary = [
    'total' => 0,
    'present' => 0,
    'missing' => 0,
    'broken' => 0,
];

foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    $character_name = trim((string) ($row['character_name'] ?? ''));
    $portrait_name = isset($row['portrait_name']) && $row['portrait_name'] !== '' ? trim((string) $row['portrait_name']) : null;
    $character_image = null;
    if (isset($row['character_image']) && $row['character_image'] !== '') {
        $character_image = trim((string) $row['character_image']);
    }

    $resolved = resolve_character_portrait(
        $character_name,
        $portrait_name,
        $character_image,
        $upload_dir,
        $reference_dir
    );

    $resolved_filename = $resolved['resolved_filename'];
    $attempted_reference = $resolved['attempted_reference'];

    if ($resolved_filename !== null) {
        $image_status = 'Present';
        $image_reference = $resolved_filename;
        $summary['present']++;
    } elseif ($attempted_reference !== null && $attempted_reference !== '') {
        $image_status = 'Broken';
        $image_reference = $attempted_reference;
        $summary['broken']++;
    } else {
        $image_status = 'Missing';
        $image_reference = '';
        $summary['missing']++;
    }

    $summary['total']++;

    $characters[] = [
        'id' => $id,
        'character_name' => $character_name,
        'image_status' => $image_status,
        'image_reference' => $image_reference,
    ];
}

$payload = [
    'success' => true,
    'generated' => date('Y-m-d H:i:s'),
    'summary' => $summary,
    'characters' => $characters,
];
$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (!empty($_GET['save'])) {
    $reports_dir = __DIR__ . '/reports';
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }
    $ts = date('Y-m-d_H-i-s');
    $latest = $reports_dir . '/character_images_audit_latest.json';
    $stamped = $reports_dir . '/character_images_audit_' . $ts . '.json';
    file_put_contents($latest, $json);
    file_put_contents($stamped, $json);
    $payload['saved_to'] = ['reports/character_images_audit_latest.json', 'reports/character_images_audit_' . $ts . '.json'];
}

echo $json;
