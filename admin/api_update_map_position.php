<?php
/**
 * API to update location map pixel coordinates (Supabase)
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$input = json_decode((string) file_get_contents('php://input'), true) ?? [];
if (!isset($input['location_id']) || !isset($input['map_pixel_x']) || !isset($input['map_pixel_y'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$location_id = (int) $input['location_id'];
$map_pixel_x = (float) $input['map_pixel_x'];
$map_pixel_y = (float) $input['map_pixel_y'];

try {
    $res = supabase_rest_request('PATCH', '/rest/v1/locations', ['id' => 'eq.' . $location_id], [
        'map_pixel_x' => $map_pixel_x,
        'map_pixel_y' => $map_pixel_y
    ], ['Prefer: return=minimal']);
    if ($res['error'] !== null) {
        throw new Exception($res['error']);
    }
    echo json_encode(['success' => true, 'message' => 'Marker position updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

