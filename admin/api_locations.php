<?php
/**
 * Locations API
 * Returns all locations from the locations table
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

try {
    $locations = supabase_table_get('locations', [
        'select' => 'id,name,type,summary,description,notes,status,status_notes,district,address,latitude,longitude,owner_type,owner_notes,faction,access_control,access_notes,security_level,security_locks,security_alarms,security_guards,security_hidden_entrance,security_sunlight_protected,security_warding_rituals,security_cameras,security_reinforced,security_notes,utility_blood_storage,utility_computers,utility_library,utility_medical,utility_workshop,utility_hidden_caches,utility_armory,utility_communications,utility_notes,social_features,capacity,prestige_level,has_supernatural,node_points,node_type,ritual_space,magical_protection,cursed_blessed,parent_location_id,relationship_type,relationship_notes,image,blueprint,moodboard,pc_haven,created_at,updated_at',
        'order' => 'id.desc'
    ]);
    echo json_encode([
        'success' => true,
        'locations' => $locations
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

