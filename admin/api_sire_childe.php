<?php
/**
 * API for Sire/Childe Relationship Management
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'update':
    case 'update_sire':
        handleUpdateRelationship();
        break;
    case 'tree':
        handleGetFamilyTree();
        break;
    default:
        handleUpdateRelationship();
        break;
}

function handleUpdateRelationship(): void {
    $input = json_decode((string) file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        return;
    }

    $character_id = (int) ($input['character_id'] ?? 0);
    $sire = trim((string) ($input['sire'] ?? ''));

    if ($character_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Character ID required']);
        return;
    }

    if ($sire !== '') {
        $existing = supabase_table_get('characters', ['select' => 'id', 'character_name' => 'eq.' . $sire, 'limit' => 1]);
        if (empty($existing) || !is_array($existing)) {
            echo json_encode(['success' => false, 'error' => 'Sire not found in database']);
            return;
        }
    }

    $payload = ['sire' => $sire === '' ? null : $sire];
    $result = supabase_rest_request('PATCH', '/rest/v1/characters?id=eq.' . $character_id, [], $payload);

    if ($result['error'] !== null) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $result['error']]);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Relationship updated successfully']);
}

function handleGetFamilyTree(): void {
    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name,clan,generation,sire',
        'order' => 'generation.desc,character_name.asc'
    ]);
    $rows = is_array($rows) ? $rows : [];

    $by_name = [];
    foreach ($rows as $r) {
        $by_name[$r['character_name'] ?? ''] = $r;
    }

    $tree = [];
    foreach ($rows as $row) {
        $name = $row['character_name'] ?? '';
        $childer = [];
        foreach ($rows as $c) {
            if (trim((string) ($c['sire'] ?? '')) === $name) {
                $childer[] = $c['character_name'] ?? '';
            }
        }
        $row['childer'] = $childer;
        $tree[] = $row;
    }

    echo json_encode(['success' => true, 'tree' => $tree]);
}
