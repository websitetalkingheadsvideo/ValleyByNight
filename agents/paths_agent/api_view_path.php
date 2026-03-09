<?php
/**
 * Paths Agent - API Endpoint for Viewing Path Details
 * 
 * Returns path information including all powers for a given path ID.
 * 
 * TM-03: Paths Agent Core Implementation - API Endpoint
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin access required.'
    ]);
    exit();
}

// Set JSON response header
header('Content-Type: application/json');

// Get path ID from query parameter
$pathId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pathId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid path ID. Must be a positive integer.'
    ]);
    exit();
}

require_once __DIR__ . '/../../includes/supabase_client.php';
require_once __DIR__ . '/src/PathsAgent.php';

try {
    $agent = new PathsAgent(null);
    
    // Get path details
    $result = $agent->listPathsByType(null, 10000, 0);
    $paths = $result['paths'] ?? [];
    
    $path = null;
    foreach ($paths as $p) {
        if ((int)$p['id'] === $pathId) {
            $path = $p;
            break;
        }
    }
    
    if ($path === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Path not found with ID: ' . $pathId
        ]);
        exit();
    }
    
    // Get powers for this path
    $powersResult = $agent->getPathPowers($pathId);
    $powers = $powersResult['powers'] ?? [];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'path' => $path,
        'powers' => $powers,
        'power_count' => count($powers)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading path: ' . htmlspecialchars($e->getMessage())
    ]);
}

