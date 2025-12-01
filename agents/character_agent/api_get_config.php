<?php
/**
 * Character Agent Configuration API
 * Returns configuration data as JSON for modal display
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$config_file = __DIR__ . '/config/settings.json';
$config_exists = file_exists($config_file);
$config_data = null;
$config_error = null;

if ($config_exists) {
    $config_content = file_get_contents($config_file);
    if ($config_content !== false) {
        $config_data = json_decode($config_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $config_error = 'Invalid JSON: ' . json_last_error_msg();
        }
    } else {
        $config_error = 'Unable to read config file';
    }
}

$response = [
    'success' => true,
    'config_exists' => $config_exists,
    'config_file' => $config_file,
    'config_data' => $config_data,
    'config_error' => $config_error
];

if ($config_exists) {
    $response['file_info'] = [
        'last_modified' => date('Y-m-d H:i:s', filemtime($config_file)),
        'file_size' => filesize($config_file),
        'file_size_formatted' => number_format(filesize($config_file)) . ' bytes'
    ];
}

echo json_encode($response);
?>

