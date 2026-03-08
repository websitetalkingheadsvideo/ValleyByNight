<?php
/**
 * Upload Character Image Endpoint
 * Handles secure upload and storage of character portrait images
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/supabase_client.php';

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$character_id = 0;
if (isset($_POST['character_id'])) {
    $character_id = (int) $_POST['character_id'];
} elseif (isset($_POST['characterId'])) {
    $character_id = (int) $_POST['characterId'];
}

try {
    if ($character_id > 0) {
        $user_id = (string) $_SESSION['user_id'];
        $charRows = supabase_table_get('characters', [
            'select' => 'id',
            'id' => 'eq.' . $character_id,
            'user_id' => 'eq.' . $user_id,
            'limit' => '1'
        ]);
        if (empty($charRows)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Character not found or access denied']);
            exit;
        }
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = $_FILES['image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['image']['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $sanitized_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_FILES['image']['name']);
    $unique_filename = $character_id . '_' . time() . '_' . substr(md5($sanitized_name), 0, 8) . '.' . $file_extension;
    
    // Full path for storage
    $upload_dir = dirname(__DIR__) . '/uploads/characters/';
    $file_path = $upload_dir . $unique_filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    $db_filename = $unique_filename;
    if ($character_id > 0) {
        $updateResult = supabase_rest_request(
            'PATCH',
            '/rest/v1/characters',
            ['id' => 'eq.' . $character_id],
            ['character_image' => $db_filename],
            ['Prefer: return=minimal']
        );
        if ($updateResult['error'] !== null) {
            @unlink($file_path);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update database']);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'filePath' => $db_filename,
        'image_path' => $db_filename
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $e->getMessage()]);
}

