<?php
/**
 * Ability Agent - API View Ability Endpoint
 * 
 * Returns detailed information about a specific ability by ID.
 * 
 * TM-05: Ability Agent Implementation - API Endpoint
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/src/AbilityAgent.php';

try {
    $abilityId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($abilityId <= 0) {
        throw new Exception('Invalid ability ID');
    }
    
    $agent = new AbilityAgent($conn);
    
    // Get all abilities and find the one with matching ID
    $allAbilities = $agent->getCanonicalAbilities(null);
    $ability = null;
    
    foreach ($allAbilities as $ab) {
        if (isset($ab['id']) && intval($ab['id']) === $abilityId) {
            $ability = $ab;
            break;
        }
    }
    
    if (!$ability) {
        throw new Exception('Ability not found');
    }
    
    echo json_encode([
        'success' => true,
        'ability' => $ability
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}

