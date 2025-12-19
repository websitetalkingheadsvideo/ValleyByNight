<?php
/**
 * Music Bindings API
 * Returns filtered music bindings based on type and target
 * 
 * GET /admin/api_music_bindings.php?type=npc&id=123 - Returns NPC bindings
 * GET /admin/api_music_bindings.php?type=location&id=456 - Returns location bindings
 * GET /admin/api_music_bindings.php?type=event&event_key=cinematic_intro_start - Returns event bindings
 * GET /admin/api_music_bindings.php - Returns all bindings
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

try {
    $registry_path = __DIR__ . '/../assets/music/music_registry.json';
    
    if (!file_exists($registry_path)) {
        throw new Exception('Music registry file not found');
    }
    
    $registry_content = file_get_contents($registry_path);
    
    if ($registry_content === false) {
        throw new Exception('Failed to read music registry file');
    }
    
    $registry = json_decode($registry_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in music registry: ' . json_last_error_msg());
    }
    
    if (!isset($registry['bindings']) || !is_array($registry['bindings'])) {
        throw new Exception('Invalid registry structure: bindings array not found');
    }
    
    $bindings = $registry['bindings'];
    $filtered_bindings = [];
    
    // Get filter parameters
    $type = $_GET['type'] ?? '';
    $target_id = $_GET['id'] ?? '';
    $event_key = $_GET['event_key'] ?? '';
    $binding_type = $_GET['binding_type'] ?? '';
    
    // Filter bindings
    foreach ($bindings as $binding) {
        $include = true;
        
        // Filter by binding type (on_location_enter, on_focus_acquired, etc.)
        if (!empty($binding_type) && isset($binding['binding_type'])) {
            if ($binding['binding_type'] !== $binding_type) {
                $include = false;
            }
        }
        
        // Filter by target type and ID (NPC, location, etc.)
        if (!empty($type) && !empty($target_id)) {
            if (!isset($binding['target_ref']) || !is_array($binding['target_ref'])) {
                $include = false;
            } else {
                $target_ref = $binding['target_ref'];
                if (!isset($target_ref['type']) || $target_ref['type'] !== $type) {
                    $include = false;
                } elseif (!isset($target_ref['id']) || $target_ref['id'] !== $target_id) {
                    $include = false;
                }
            }
        }
        
        // Filter by event key
        if (!empty($event_key)) {
            if (!isset($binding['event']) || !is_array($binding['event'])) {
                $include = false;
            } else {
                if (!isset($binding['event']['event_key']) || $binding['event']['event_key'] !== $event_key) {
                    $include = false;
                }
            }
        }
        
        if ($include) {
            $filtered_bindings[] = $binding;
        }
    }
    
    // Also include related cues and assets for the filtered bindings
    $related_cues = [];
    $related_assets = [];
    
    foreach ($filtered_bindings as $binding) {
        if (isset($binding['play_cue_ref'])) {
            $cue_id = $binding['play_cue_ref'];
            
            // Find the cue
            if (isset($registry['cues']) && is_array($registry['cues'])) {
                foreach ($registry['cues'] as $cue) {
                    if (isset($cue['cue_id']) && $cue['cue_id'] === $cue_id) {
                        $related_cues[$cue_id] = $cue;
                        
                        // Find the asset
                        if (isset($cue['asset_ref']) && isset($registry['assets']) && is_array($registry['assets'])) {
                            $asset_id = $cue['asset_ref'];
                            foreach ($registry['assets'] as $asset) {
                                if (isset($asset['asset_id']) && $asset['asset_id'] === $asset_id) {
                                    $related_assets[$asset_id] = $asset;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'bindings' => $filtered_bindings,
        'cues' => array_values($related_cues),
        'assets' => array_values($related_assets),
        'count' => count($filtered_bindings)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

