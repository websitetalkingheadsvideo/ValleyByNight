<?php
/**
 * API Endpoint to serve boon report JSON files
 * Prevents direct file access and ensures proper authentication
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get report type and filename from query parameters
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
$filename = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($reportType) || empty($filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing type or file parameter']);
    exit();
}

// Validate report type
$allowedTypes = ['daily', 'validation', 'character'];
if (!in_array($reportType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report type']);
    exit();
}

// Sanitize filename to prevent directory traversal
$filename = basename($filename);

// Construct full file path
$reports_dir = __DIR__ . '/reports';
$file_path = $reports_dir . '/' . $reportType . '/' . $filename;

// Security check: ensure file is within reports directory
$real_file_path = realpath($file_path);
$real_reports_dir = realpath($reports_dir);

if (!$real_file_path || strpos($real_file_path, $real_reports_dir) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Invalid file path']);
    exit();
}

// Check if file exists
if (!file_exists($real_file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit();
}

// Read and output file
$content = file_get_contents($real_file_path);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read report file']);
    exit();
}

// Validate JSON
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON in report file']);
    exit();
}

// Output the JSON data
echo $content;
?>

