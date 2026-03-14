<?php
/**
 * API Endpoint to serve report JSON files
 * Prevents direct file access and ensures proper authentication
 */
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get report path from query parameter
$reportPath = isset($_GET['path']) ? $_GET['path'] : '';

if (empty($reportPath)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Missing path parameter']);
    exit();
}

// Get report type from query parameter
$reportType = isset($_GET['type']) ? $_GET['type'] : 'daily';

// Sanitize filename to prevent directory traversal
$filename = basename($reportPath);

// Validate report type
if (!in_array($reportType, ['daily', 'continuity'])) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid report type']);
    exit();
}

// Construct full file path
$reports_dir = __DIR__ . '/reports';
$file_path = $reports_dir . '/' . $reportType . '/' . $filename;

// Security check: ensure file is within reports directory
$real_file_path = realpath($file_path);
$real_reports_dir = realpath($reports_dir);

if (!$real_file_path || strpos($real_file_path, $real_reports_dir) !== 0) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden: Invalid file path']);
    exit();
}

// Check if file exists
if (!file_exists($real_file_path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Report not found']);
    exit();
}

// Read and output file
$content = file_get_contents($real_file_path);
if ($content === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Failed to read report']);
    exit();
}

// Validate JSON
$json_data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit();
}

// Output JSON with proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
echo $content;

