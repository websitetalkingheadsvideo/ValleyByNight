<?php
/**
 * API for Paths Agent
 * 
 * Provides endpoints for path definitions, path powers, and character path ratings.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../agents/paths_agent/src/PathsAgent.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $agent = new PathsAgent(null);
    
    switch ($action) {
        case 'list_paths':
            handleListPaths($agent);
            break;
            
        case 'get_powers':
            handleGetPowers($agent);
            break;
            
        case 'get_character_paths':
            handleGetCharacterPaths($agent);
            break;
            
        case 'can_use_power':
            handleCanUsePower($agent);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Valid actions: list_paths, get_powers, get_character_paths, can_use_power'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function handleListPaths(PathsAgent $agent) {
    $type = $_GET['type'] ?? $_POST['type'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_POST['limit']) ? (int)$_POST['limit'] : 100);
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : (isset($_POST['offset']) ? (int)$_POST['offset'] : 0);
    
    if ($type === '') {
        $type = null;
    }
    
    $result = $agent->listPathsByType($type, $limit, $offset);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);
}

function handleGetPowers(PathsAgent $agent) {
    $pathId = isset($_GET['path_id']) ? (int)$_GET['path_id'] : (isset($_POST['path_id']) ? (int)$_POST['path_id'] : 0);
    
    if ($pathId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'path_id parameter is required and must be a positive integer'
        ]);
        return;
    }
    
    $result = $agent->getPathPowers($pathId);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);
}

function handleGetCharacterPaths(PathsAgent $agent) {
    $characterId = isset($_GET['character_id']) ? (int)$_GET['character_id'] : (isset($_POST['character_id']) ? (int)$_POST['character_id'] : 0);
    
    if ($characterId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'character_id parameter is required and must be a positive integer'
        ]);
        return;
    }
    
    $result = $agent->getCharacterPaths($characterId);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);
}

function handleCanUsePower(PathsAgent $agent) {
    $characterId = isset($_GET['character_id']) ? (int)$_GET['character_id'] : (isset($_POST['character_id']) ? (int)$_POST['character_id'] : 0);
    $powerId = isset($_GET['power_id']) ? (int)$_GET['power_id'] : (isset($_POST['power_id']) ? (int)$_POST['power_id'] : 0);
    
    if ($characterId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'character_id parameter is required and must be a positive integer'
        ]);
        return;
    }
    
    if ($powerId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'power_id parameter is required and must be a positive integer'
        ]);
        return;
    }
    
    $result = $agent->canUsePathPower($characterId, $powerId);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);
}

