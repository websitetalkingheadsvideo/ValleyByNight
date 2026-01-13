<?php
/**
 * List files in reference/Locations directory
 * Returns file tree structure for file browser
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$basePath = __DIR__ . '/../reference/Locations';
$requestedPath = $_GET['path'] ?? '';

// Security: Prevent directory traversal
$requestedPath = str_replace('..', '', $requestedPath);
$fullPath = realpath($basePath . '/' . $requestedPath);

// Ensure we're still within the base directory
if ($fullPath === false || strpos($fullPath, realpath($basePath)) !== 0) {
    $fullPath = realpath($basePath);
    $requestedPath = '';
}

if (!$fullPath || !is_dir($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Invalid path']);
    exit();
}

$files = [];
$directories = [];

try {
    $items = scandir($fullPath);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '_notes') {
            continue;
        }
        
        $itemPath = $fullPath . '/' . $item;
        $relativePath = ($requestedPath ? $requestedPath . '/' : '') . $item;
        
        if (is_dir($itemPath)) {
            $directories[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => 'directory'
            ];
        } else {
            // Only show image files
            $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
            
            if (in_array($extension, $imageExtensions)) {
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'type' => 'file',
                    'extension' => $extension
                ];
            }
        }
    }
    
    // Sort: directories first, then files, both alphabetically
    usort($directories, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    usort($files, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'path' => $requestedPath,
        'directories' => $directories,
        'files' => $files
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
