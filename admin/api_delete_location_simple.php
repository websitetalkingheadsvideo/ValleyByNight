<?php
/**
 * Delete Location API (Simple) - Supabase
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$location_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($location_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid location ID']);
    exit;
}

try {
    supabase_rest_request('DELETE', '/rest/v1/location_ownership', ['location_id' => 'eq.' . $location_id], null, ['Prefer: return=minimal']);
    supabase_rest_request('DELETE', '/rest/v1/location_items', ['location_id' => 'eq.' . $location_id], null, ['Prefer: return=minimal']);
    $res = supabase_rest_request('DELETE', '/rest/v1/locations', ['id' => 'eq.' . $location_id], null, ['Prefer: return=representation']);
    if (is_array($res['data']) && count($res['data']) > 0) {
        echo json_encode(['success' => true, 'message' => 'Location deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Location not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

