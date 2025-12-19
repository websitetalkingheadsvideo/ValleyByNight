<?php
/**
 * Music Registry API
 * Returns the complete music registry JSON file
 * 
 * GET /admin/api_music_registry.php - Returns full registry
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $registry_path = __DIR__ . '/../assets/music/music_registry.json';
    
    if (!file_exists($registry_path)) {
        throw new Exception('Music registry file not found');
    }
    
    $registry_content = file_get_contents($registry_path);
    
    if ($registry_content === false) {
        throw new Exception('Failed to read music registry file');
    }
    
    $registry = json_decode($registry_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in music registry: ' . json_last_error_msg());
    }
    
    echo json_encode([
        'success' => true,
        'registry' => $registry
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

