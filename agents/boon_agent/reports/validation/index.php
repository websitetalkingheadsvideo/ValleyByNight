<?php
/**
 * Boon Agent Validation Reports Directory Index
 * Returns list of validation report files as JSON for modal display
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$reports_dir = __DIR__;
$reports = [];

try {
    if (is_dir($reports_dir)) {
        $files = scandir($reports_dir);
        
        foreach ($files as $file) {
            // Skip hidden files, directories, and .gitkeep
            if ($file === '.' || $file === '..' || $file === '.gitkeep' || $file === 'index.php') {
                continue;
            }
            
            $file_path = $reports_dir . '/' . $file;
            
            // Only include JSON files
            if (is_file($file_path) && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $file_info = [
                    'filename' => $file,
                    'name' => basename($file, '.json'),
                    'size' => filesize($file_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($file_path)),
                    'url' => '../api_get_boon_report.php?type=validation&file=' . urlencode($file)
                ];
                $reports[] = $file_info;
            }
        }
        
        // Sort by modified date (newest first)
        usort($reports, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
    }
    
    echo json_encode([
        'success' => true,
        'type' => 'validation',
        'reports' => $reports,
        'count' => count($reports)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

