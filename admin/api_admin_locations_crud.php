<?php
/**
 * Locations CRUD API
 * Handles Create, Read, Update, Delete operations for locations
 * Updated to handle all fields from location_template.json
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

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
    $input = json_decode(file_get_contents('php://input'), true);
    
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
        $pc_haven = cleanBool($input['pc_haven'] ?? 0);
        
        // Only set pc_haven if type is Haven
        if ($type !== 'Haven') {
            $pc_haven = 0;
        }
        
        $query = "INSERT INTO locations (
            name, type, summary, description, notes, status, status_notes, district, address, latitude, longitude,
            owner_type, owner_notes, faction, access_control, access_notes, security_level,
            security_locks, security_alarms, security_guards, security_hidden_entrance, security_sunlight_protected,
            security_warding_rituals, security_cameras, security_reinforced, security_notes,
            utility_blood_storage, utility_computers, utility_library, utility_medical, utility_workshop,
            utility_hidden_caches, utility_armory, utility_communications, utility_notes,
            social_features, capacity, prestige_level, has_supernatural, node_points, node_type,
            ritual_space, magical_protection, cursed_blessed, parent_location_id, relationship_type,
            relationship_notes, image, pc_haven, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssssssddsssssiiiiiiiiisiiiiiiiiisiiissssiisssi',
            $name, $type, $summary, $description, $notes, $status, $status_notes, $district, $address, $latitude, $longitude,
            $owner_type, $owner_notes, $faction, $access_control, $access_notes, $security_level,
            $security_locks, $security_alarms, $security_guards, $security_hidden_entrance, $security_sunlight_protected,
            $security_warding_rituals, $security_cameras, $security_reinforced, $security_notes,
            $utility_blood_storage, $utility_computers, $utility_library, $utility_medical, $utility_workshop,
            $utility_hidden_caches, $utility_armory, $utility_communications, $utility_notes,
            $social_features, $capacity, $prestige_level, $has_supernatural, $node_points, $node_type,
            $ritual_space, $magical_protection, $cursed_blessed, $parent_location_id, $relationship_type,
            $relationship_notes, $image, $pc_haven
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location created successfully',
                'id' => mysqli_insert_id($conn)
            ]);
        } else {
            throw new Exception('Failed to create location: ' . mysqli_error($conn));
        }
        
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
        $pc_haven = cleanBool($input['pc_haven'] ?? 0);
        
        // Only set pc_haven if type is Haven
        if ($type !== 'Haven') {
            $pc_haven = 0;
        }
        
        $query = "UPDATE locations SET 
            name = ?, type = ?, summary = ?, description = ?, notes = ?, status = ?, status_notes = ?,
            district = ?, address = ?, latitude = ?, longitude = ?, owner_type = ?, owner_notes = ?,
            faction = ?, access_control = ?, access_notes = ?, security_level = ?,
            security_locks = ?, security_alarms = ?, security_guards = ?, security_hidden_entrance = ?,
            security_sunlight_protected = ?, security_warding_rituals = ?, security_cameras = ?,
            security_reinforced = ?, security_notes = ?,
            utility_blood_storage = ?, utility_computers = ?, utility_library = ?, utility_medical = ?,
            utility_workshop = ?, utility_hidden_caches = ?, utility_armory = ?, utility_communications = ?,
            utility_notes = ?, social_features = ?, capacity = ?, prestige_level = ?, has_supernatural = ?,
            node_points = ?, node_type = ?, ritual_space = ?, magical_protection = ?, cursed_blessed = ?,
            parent_location_id = ?, relationship_type = ?, relationship_notes = ?, image = ?, pc_haven = ?,
            updated_at = NOW()
            WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssssssddsssssiiiiiiiiisiiiiiiiiisiiissssiisssii',
            $name, $type, $summary, $description, $notes, $status, $status_notes, $district, $address, $latitude, $longitude,
            $owner_type, $owner_notes, $faction, $access_control, $access_notes, $security_level,
            $security_locks, $security_alarms, $security_guards, $security_hidden_entrance, $security_sunlight_protected,
            $security_warding_rituals, $security_cameras, $security_reinforced, $security_notes,
            $utility_blood_storage, $utility_computers, $utility_library, $utility_medical, $utility_workshop,
            $utility_hidden_caches, $utility_armory, $utility_communications, $utility_notes,
            $social_features, $capacity, $prestige_level, $has_supernatural, $node_points, $node_type,
            $ritual_space, $magical_protection, $cursed_blessed, $parent_location_id, $relationship_type,
            $relationship_notes, $image, $pc_haven, $id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update location: ' . mysqli_error($conn));
        }
        
    } elseif ($method === 'DELETE') {
        // Delete location
        $id = cleanInt($input['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid location ID');
        }
        
        $query = "DELETE FROM locations WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete location: ' . mysqli_error($conn));
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
