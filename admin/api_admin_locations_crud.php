<?php
/**
 * Locations CRUD API
 * Handles Create, Read, Update, Delete operations for locations
 * Updated to handle all fields from location_template.json
 */

// Disable error display to prevent output from corrupting JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to prevent any output from corrupting JSON
ob_start();

// Function to output JSON and exit cleanly
function outputJson($data) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Register error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
});

session_start();
header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    outputJson(['success' => false, 'error' => 'Unauthorized']);
}

require_once __DIR__ . '/../includes/supabase_client.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to clean and validate input
function cleanString($value) {
    return trim($value ?? '');
}

function cleanInt($value, $default = null) {
    $val = $value ?? $default;
    return $val !== null && $val !== '' ? intval($val) : $default;
}

function cleanFloat($value, $default = null) {
    $val = $value ?? $default;
    return $val !== null && $val !== '' ? floatval($val) : $default;
}

function cleanBool($value) {
    return ($value == 1 || $value === true || $value === '1' || $value === 'true') ? 1 : 0;
}

try {
    // Handle request body
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    if ($input === null && $rawInput !== '') {
        throw new Exception('Failed to parse JSON input');
    }
    
    if ($method === 'POST') {
        // Create new location - handle all fields
        $name = cleanString($input['name'] ?? '');
        $type = cleanString($input['type'] ?? '');
        $summary = cleanString($input['summary'] ?? '');
        $description = cleanString($input['description'] ?? '');
        $notes = cleanString($input['notes'] ?? '');
        $status = cleanString($input['status'] ?? 'Active');
        $status_notes = cleanString($input['status_notes'] ?? '');
        $district = cleanString($input['district'] ?? '');
        $address = cleanString($input['address'] ?? '');
        $latitude = cleanFloat($input['latitude'] ?? null);
        $longitude = cleanFloat($input['longitude'] ?? null);
        $owner_type = cleanString($input['owner_type'] ?? '');
        $owner_notes = cleanString($input['owner_notes'] ?? '');
        $faction = cleanString($input['faction'] ?? '');
        $access_control = cleanString($input['access_control'] ?? '');
        $access_notes = cleanString($input['access_notes'] ?? '');
        $security_level = cleanInt($input['security_level'] ?? 3);
        $security_locks = cleanBool($input['security_locks'] ?? 0);
        $security_alarms = cleanBool($input['security_alarms'] ?? 0);
        $security_guards = cleanBool($input['security_guards'] ?? 0);
        $security_hidden_entrance = cleanBool($input['security_hidden_entrance'] ?? 0);
        $security_sunlight_protected = cleanBool($input['security_sunlight_protected'] ?? 0);
        $security_warding_rituals = cleanBool($input['security_warding_rituals'] ?? 0);
        $security_cameras = cleanBool($input['security_cameras'] ?? 0);
        $security_reinforced = cleanBool($input['security_reinforced'] ?? 0);
        $security_notes = cleanString($input['security_notes'] ?? '');
        $utility_blood_storage = cleanBool($input['utility_blood_storage'] ?? 0);
        $utility_computers = cleanBool($input['utility_computers'] ?? 0);
        $utility_library = cleanBool($input['utility_library'] ?? 0);
        $utility_medical = cleanBool($input['utility_medical'] ?? 0);
        $utility_workshop = cleanBool($input['utility_workshop'] ?? 0);
        $utility_hidden_caches = cleanBool($input['utility_hidden_caches'] ?? 0);
        $utility_armory = cleanBool($input['utility_armory'] ?? 0);
        $utility_communications = cleanBool($input['utility_communications'] ?? 0);
        $utility_notes = cleanString($input['utility_notes'] ?? '');
        $social_features = cleanString($input['social_features'] ?? '');
        $capacity = cleanInt($input['capacity'] ?? null);
        $prestige_level = cleanInt($input['prestige_level'] ?? null);
        $has_supernatural = cleanBool($input['has_supernatural'] ?? 0);
        $node_points = cleanInt($input['node_points'] ?? null);
        $node_type = cleanString($input['node_type'] ?? '');
        $ritual_space = cleanString($input['ritual_space'] ?? '');
        $magical_protection = cleanString($input['magical_protection'] ?? '');
        $cursed_blessed = cleanString($input['cursed_blessed'] ?? '');
        $parent_location_id = cleanInt($input['parent_location_id'] ?? null);
        $relationship_type = cleanString($input['relationship_type'] ?? '');
        $relationship_notes = cleanString($input['relationship_notes'] ?? '');
        $image = cleanString($input['image'] ?? '');
        $blueprint = cleanString($input['blueprint'] ?? '');
        $moodboard = cleanString($input['moodboard'] ?? '');
        $pc_haven = $type === 'Haven' ? cleanBool($input['pc_haven'] ?? 0) : 0;
        
        $payload = [
            'name' => $name, 'type' => $type, 'summary' => $summary, 'description' => $description, 'notes' => $notes,
            'status' => $status, 'status_notes' => $status_notes, 'district' => $district, 'address' => $address,
            'latitude' => $latitude, 'longitude' => $longitude, 'owner_type' => $owner_type, 'owner_notes' => $owner_notes,
            'faction' => $faction, 'access_control' => $access_control, 'access_notes' => $access_notes, 'security_level' => $security_level,
            'security_locks' => $security_locks, 'security_alarms' => $security_alarms, 'security_guards' => $security_guards,
            'security_hidden_entrance' => $security_hidden_entrance, 'security_sunlight_protected' => $security_sunlight_protected,
            'security_warding_rituals' => $security_warding_rituals, 'security_cameras' => $security_cameras,
            'security_reinforced' => $security_reinforced, 'security_notes' => $security_notes,
            'utility_blood_storage' => $utility_blood_storage, 'utility_computers' => $utility_computers,
            'utility_library' => $utility_library, 'utility_medical' => $utility_medical, 'utility_workshop' => $utility_workshop,
            'utility_hidden_caches' => $utility_hidden_caches, 'utility_armory' => $utility_armory,
            'utility_communications' => $utility_communications, 'utility_notes' => $utility_notes,
            'social_features' => $social_features, 'capacity' => $capacity, 'prestige_level' => $prestige_level,
            'has_supernatural' => $has_supernatural, 'node_points' => $node_points, 'node_type' => $node_type,
            'ritual_space' => $ritual_space, 'magical_protection' => $magical_protection, 'cursed_blessed' => $cursed_blessed,
            'parent_location_id' => $parent_location_id, 'relationship_type' => $relationship_type,
            'relationship_notes' => $relationship_notes, 'image' => $image, 'blueprint' => $blueprint, 'moodboard' => $moodboard, 'pc_haven' => $pc_haven,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')
        ];
        $res = supabase_rest_request('POST', '/rest/v1/locations', [], $payload, ['Prefer: return=representation']);
        if ($res['error'] !== null) {
            error_log('Location CRUD API - POST failed: ' . $res['error']);
            throw new Exception('Failed to create location: ' . $res['error']);
        }
        $data = is_array($res['data']) && isset($res['data'][0]) ? $res['data'][0] : (is_array($res['data']) ? $res['data'] : []);
        $insertId = $data['id'] ?? null;
        outputJson(['success' => true, 'message' => 'Location created successfully', 'id' => $insertId]);
        
    } elseif ($method === 'PUT') {
        // Update existing location - handle all fields
        $id = cleanInt($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid location ID');
        }
        
        $name = cleanString($input['name'] ?? '');
        $type = cleanString($input['type'] ?? '');
        $summary = cleanString($input['summary'] ?? '');
        $description = cleanString($input['description'] ?? '');
        $notes = cleanString($input['notes'] ?? '');
        $status = cleanString($input['status'] ?? 'Active');
        $status_notes = cleanString($input['status_notes'] ?? '');
        $district = cleanString($input['district'] ?? '');
        $address = cleanString($input['address'] ?? '');
        $latitude = cleanFloat($input['latitude'] ?? null);
        $longitude = cleanFloat($input['longitude'] ?? null);
        $owner_type = cleanString($input['owner_type'] ?? '');
        $owner_notes = cleanString($input['owner_notes'] ?? '');
        $faction = cleanString($input['faction'] ?? '');
        $access_control = cleanString($input['access_control'] ?? '');
        $access_notes = cleanString($input['access_notes'] ?? '');
        $security_level = cleanInt($input['security_level'] ?? 3);
        $security_locks = cleanBool($input['security_locks'] ?? 0);
        $security_alarms = cleanBool($input['security_alarms'] ?? 0);
        $security_guards = cleanBool($input['security_guards'] ?? 0);
        $security_hidden_entrance = cleanBool($input['security_hidden_entrance'] ?? 0);
        $security_sunlight_protected = cleanBool($input['security_sunlight_protected'] ?? 0);
        $security_warding_rituals = cleanBool($input['security_warding_rituals'] ?? 0);
        $security_cameras = cleanBool($input['security_cameras'] ?? 0);
        $security_reinforced = cleanBool($input['security_reinforced'] ?? 0);
        $security_notes = cleanString($input['security_notes'] ?? '');
        $utility_blood_storage = cleanBool($input['utility_blood_storage'] ?? 0);
        $utility_computers = cleanBool($input['utility_computers'] ?? 0);
        $utility_library = cleanBool($input['utility_library'] ?? 0);
        $utility_medical = cleanBool($input['utility_medical'] ?? 0);
        $utility_workshop = cleanBool($input['utility_workshop'] ?? 0);
        $utility_hidden_caches = cleanBool($input['utility_hidden_caches'] ?? 0);
        $utility_armory = cleanBool($input['utility_armory'] ?? 0);
        $utility_communications = cleanBool($input['utility_communications'] ?? 0);
        $utility_notes = cleanString($input['utility_notes'] ?? '');
        $social_features = cleanString($input['social_features'] ?? '');
        $capacity = cleanInt($input['capacity'] ?? null);
        $prestige_level = cleanInt($input['prestige_level'] ?? null);
        $has_supernatural = cleanBool($input['has_supernatural'] ?? 0);
        $node_points = cleanInt($input['node_points'] ?? null);
        $node_type = cleanString($input['node_type'] ?? '');
        $ritual_space = cleanString($input['ritual_space'] ?? '');
        $magical_protection = cleanString($input['magical_protection'] ?? '');
        $cursed_blessed = cleanString($input['cursed_blessed'] ?? '');
        $parent_location_id = cleanInt($input['parent_location_id'] ?? null);
        $relationship_type = cleanString($input['relationship_type'] ?? '');
        $relationship_notes = cleanString($input['relationship_notes'] ?? '');
        $image = cleanString($input['image'] ?? '');
        $blueprint = cleanString($input['blueprint'] ?? '');
        $moodboard = cleanString($input['moodboard'] ?? '');
        $pc_haven = cleanBool($input['pc_haven'] ?? 0);
        
        // Only set pc_haven if type is Haven
        if ($type !== 'Haven') {
            $pc_haven = 0;
        }
        
        $payload = [
            'name' => $name, 'type' => $type, 'summary' => $summary, 'description' => $description, 'notes' => $notes,
            'status' => $status, 'status_notes' => $status_notes, 'district' => $district, 'address' => $address,
            'latitude' => $latitude, 'longitude' => $longitude, 'owner_type' => $owner_type, 'owner_notes' => $owner_notes,
            'faction' => $faction, 'access_control' => $access_control, 'access_notes' => $access_notes, 'security_level' => $security_level,
            'security_locks' => $security_locks, 'security_alarms' => $security_alarms, 'security_guards' => $security_guards,
            'security_hidden_entrance' => $security_hidden_entrance, 'security_sunlight_protected' => $security_sunlight_protected,
            'security_warding_rituals' => $security_warding_rituals, 'security_cameras' => $security_cameras,
            'security_reinforced' => $security_reinforced, 'security_notes' => $security_notes,
            'utility_blood_storage' => $utility_blood_storage, 'utility_computers' => $utility_computers,
            'utility_library' => $utility_library, 'utility_medical' => $utility_medical, 'utility_workshop' => $utility_workshop,
            'utility_hidden_caches' => $utility_hidden_caches, 'utility_armory' => $utility_armory,
            'utility_communications' => $utility_communications, 'utility_notes' => $utility_notes,
            'social_features' => $social_features, 'capacity' => $capacity, 'prestige_level' => $prestige_level,
            'has_supernatural' => $has_supernatural, 'node_points' => $node_points, 'node_type' => $node_type,
            'ritual_space' => $ritual_space, 'magical_protection' => $magical_protection, 'cursed_blessed' => $cursed_blessed,
            'parent_location_id' => $parent_location_id, 'relationship_type' => $relationship_type,
            'relationship_notes' => $relationship_notes, 'image' => $image, 'blueprint' => $blueprint, 'moodboard' => $moodboard, 'pc_haven' => $pc_haven,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $res = supabase_rest_request('PATCH', '/rest/v1/locations', ['id' => 'eq.' . $id], $payload, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            error_log('Location CRUD API - PATCH failed: ' . $res['error']);
            throw new Exception('Failed to update location: ' . $res['error']);
        }
        outputJson(['success' => true, 'message' => 'Location updated successfully']);
        
    } elseif ($method === 'DELETE') {
        $id = cleanInt($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid location ID');
        }
        $res = supabase_rest_request('DELETE', '/rest/v1/locations', ['id' => 'eq.' . $id], null, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            throw new Exception('Failed to delete location: ' . $res['error']);
        }
        outputJson(['success' => true, 'message' => 'Location deleted successfully']);
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Throwable $e) {
    error_log('Location CRUD API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    outputJson([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
