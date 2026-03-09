<?php
/**
 * View Ritual API
 * Returns complete ritual data for modal display
 */

// Suppress any output that might interfere with JSON
ob_start();

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once __DIR__ . '/../../includes/supabase_client.php';
    require_once __DIR__ . '/src/RitualsAgent.php';
    
    // Clear any output buffer before JSON
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dependencies: ' . $e->getMessage()
    ]);
    exit();
} catch (Error $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error loading dependencies: ' . $e->getMessage()
    ]);
    exit();
}

$ritual_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ritual_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ritual ID']);
    exit();
}

try {
    // Ensure output buffer is clean
    if (ob_get_level() > 0) {
        ob_clean();
    }
    $agent = new RitualsAgent(null);
    
    // Get ritual without rules first (more reliable)
    $ritual = $agent->getRitualById($ritual_id, false);
    
    if (!$ritual) {
        echo json_encode(['success' => false, 'message' => 'Ritual not found']);
        exit();
    }
    
    // Try to attach rules separately (non-critical, can fail silently)
    $rules_array = [];
    try {
        $ritualWithRules = $agent->getRitualById($ritual_id, true);
        if ($ritualWithRules && isset($ritualWithRules['rules'])) {
            // Flatten rules array if it's in the nested format
            if (isset($ritualWithRules['rules']['global']) && is_array($ritualWithRules['rules']['global'])) {
                $rules_array = array_merge($rules_array, $ritualWithRules['rules']['global']);
            }
            if (isset($ritualWithRules['rules']['tradition']) && is_array($ritualWithRules['rules']['tradition'])) {
                $rules_array = array_merge($rules_array, $ritualWithRules['rules']['tradition']);
            }
            // If it's already a flat array, use it as-is
            if (empty($rules_array) && is_array($ritualWithRules['rules']) && !isset($ritualWithRules['rules']['global'])) {
                $rules_array = $ritualWithRules['rules'];
            }
        }
    } catch (Exception $rulesError) {
        // Rules attachment failed - not critical, continue without rules
        error_log('Rules attachment failed (non-critical): ' . $rulesError->getMessage());
        $rules_array = [];
    }
    
    // Ensure all fields are present
    $ritual_data = [
        'id' => $ritual['id'] ?? $ritual_id,
        'name' => $ritual['name'] ?? '',
        'type' => $ritual['type'] ?? '',
        'level' => $ritual['level'] ?? 0,
        'description' => $ritual['description'] ?? '',
        'system_text' => $ritual['system_text'] ?? '',
        'requirements' => $ritual['requirements'] ?? '',
        'ingredients' => $ritual['ingredients'] ?? '',
        'source' => $ritual['source'] ?? '',
        'created_at' => $ritual['created_at'] ?? null,
        'rules' => $rules_array
    ];
    
    echo json_encode([
        'success' => true,
        'ritual' => $ritual_data
    ]);
    
} catch (Exception $e) {
    error_log('Ritual API Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error loading ritual: ' . $e->getMessage()
    ]);
    exit();
} catch (Error $e) {
    error_log('Ritual API Fatal Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error loading ritual: ' . $e->getMessage()
    ]);
    exit();
}

