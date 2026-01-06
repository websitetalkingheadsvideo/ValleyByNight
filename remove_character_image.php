<?php
/**
 * Remove Character Image API
 * Removes character image file and clears database reference
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Include database connection
require_once __DIR__ . '/includes/connect.php';

// Get character ID from POST
$character_id = isset($_POST['character_id']) ? intval($_POST['character_id']) : 0;

if ($character_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid character ID']);
    exit;
}

try {
    // Verify character ownership (unless admin)
    $verify_query = "SELECT id, character_image, user_id FROM characters WHERE id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $character_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Character not found']);
        exit;
    }
    
    $character = $result->fetch_assoc();
    $is_owner = ($character['user_id'] == $_SESSION['user_id']);
    require_once __DIR__ . '/includes/verify_role.php';
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = verifyUserRole($conn, $user_id);
    $is_admin = isAdminUser($user_role);
    
    if (!$is_owner && !$is_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Get image filename
    $image_filename = $character['character_image'];
    
    // Delete image file if it exists
    if (!empty($image_filename)) {
        $image_path = __DIR__ . '/uploads/characters/' . $image_filename;
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }
    
    // Clear database reference
    $update_query = "UPDATE characters SET character_image = NULL WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $character_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Character image removed successfully'
        ]);
    } else {
        throw new Exception('Failed to update database');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>

