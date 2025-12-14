<?php
/**
 * Map Agent API
 * Returns map data for specified map and layer
 * SECURITY: Validates map ID and layer against whitelist
 */
header('Content-Type: application/json; charset=utf-8');

$configs = include __DIR__ . '/map_config.php';

// SECURITY: Validate and sanitize input
$mapId = isset($_GET['map']) ? trim($_GET['map']) : 'phoenix_1994';
$layer = isset($_GET['layer']) ? trim($_GET['layer']) : 'cities';

// Validate map ID against whitelist (config keys)
if (!isset($configs[$mapId])) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown map id']);
    exit;
}

$mapConfig = $configs[$mapId];

// Validate layer against whitelist (config data_files keys)
if (!isset($mapConfig['data_files'][$layer])) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown layer']);
    exit;
}

// SECURITY: Prevent path traversal - ensure file path is within expected directory
$filePath = $mapConfig['data_files'][$layer];
$baseDir = dirname(__DIR__);
$realPath = realpath($filePath);

// Verify file exists and is within project directory
if (!$realPath || strpos($realPath, $baseDir) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid file path']);
    exit;
}

if (!file_exists($realPath)) {
    echo json_encode(['map' => $mapId, 'layer' => $layer, 'items' => []]);
    exit;
}

echo file_get_contents($realPath);
