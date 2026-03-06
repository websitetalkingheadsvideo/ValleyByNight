<?php
/**
 * Generic Upload Endpoint
 * Handles file uploads for DataManager and other components
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Determine file field name (supports both 'file' and 'image')
$file_field = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file_field = 'file';
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_field = 'image';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

// Get character ID if provided
$character_id = 0;
if (isset($_POST['character_id'])) {
    $character_id = intval($_POST['character_id']);
} elseif (isset($_POST['characterId'])) {
    $character_id = intval($_POST['characterId']);
}

// Determine upload type from file field or parameter
$upload_type = isset($_POST['type']) ? $_POST['type'] : 'character_image';

try {
    require_once __DIR__ . '/includes/supabase_client.php';
    
    // If a character id is provided, verify ownership
    if ($character_id > 0) {
        $verifyRows = supabase_table_get('characters', [
            'select' => 'id',
            'id' => 'eq.' . (string) $character_id,
            'user_id' => 'eq.' . (string) $_SESSION['user_id'],
            'limit' => '1'
        ]);
        if (empty($verifyRows)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Character not found or access denied']);
            exit;
        }
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = $_FILES[$file_field]['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES[$file_field]['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION);
    $sanitized_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_FILES[$file_field]['name']);
    $unique_filename = ($character_id > 0 ? $character_id . '_' : '') . time() . '_' . substr(md5($sanitized_name), 0, 8) . '.' . $file_extension;
    
    // Determine upload directory based on type
    if ($upload_type === 'character_image') {
        $upload_dir = __DIR__ . '/uploads/characters/';
    } else {
        $upload_dir = __DIR__ . '/uploads/';
    }
    
    $file_path = $upload_dir . $unique_filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES[$file_field]['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Update database with filename if character_id provided
    $db_filename = $unique_filename;
    if ($character_id > 0 && $upload_type === 'character_image') {
        $updateResult = supabase_rest_request(
            'PATCH',
            '/rest/v1/characters',
            ['id' => 'eq.' . (string) $character_id],
            ['character_image' => $db_filename],
            ['Prefer: return=minimal']
        );
        if ($updateResult['error'] !== null) {
            // Delete uploaded file if database update fails
            @unlink($file_path);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update database']);
            exit;
        }
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'filePath' => $db_filename,
        'image_path' => $db_filename,
        'filename' => $db_filename
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $e->getMessage()]);
}
?>

