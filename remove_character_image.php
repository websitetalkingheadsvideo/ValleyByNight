<?php
/**
 * Remove Character Image API
 * Removes character image file and clears database reference
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/includes/supabase_client.php';
require_once __DIR__ . '/includes/verify_role.php';

$character_id = isset($_POST['character_id']) ? (int) $_POST['character_id'] : 0;
if ($character_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid character ID']);
    exit;
}

try {
    $charRows = supabase_table_get('characters', [
        'select' => 'id,character_image,user_id',
        'id' => 'eq.' . $character_id,
        'limit' => '1'
    ]);
    if (empty($charRows)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Character not found']);
        exit;
    }
    $character = $charRows[0];
    $user_id = (int) $_SESSION['user_id'];
    $is_owner = ((int) ($character['user_id'] ?? 0)) === $user_id;
    $user_role = verifyUserRole(null, $user_id);
    $is_admin = isAdminUser($user_role);
    if (!$is_owner && !$is_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    $image_filename = $character['character_image'] ?? null;
    if (!empty($image_filename)) {
        $image_path = __DIR__ . '/uploads/characters/' . $image_filename;
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }

    $updateResult = supabase_rest_request(
        'PATCH',
        '/rest/v1/characters',
        ['id' => 'eq.' . $character_id],
        ['character_image' => null],
        ['Prefer: return=minimal']
    );
    if ($updateResult['error'] !== null) {
        throw new Exception('Failed to update database');
    }
    echo json_encode(['success' => true, 'message' => 'Character image removed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

