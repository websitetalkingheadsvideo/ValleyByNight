<?php
/**
 * Update Position API
 * Handles updating position data
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$position_id = isset($input['position_id']) ? trim($input['position_id']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$category = isset($input['category']) ? trim($input['category']) : '';
$description = isset($input['description']) ? trim($input['description']) : null;
$importance_rank = isset($input['importance_rank']) && $input['importance_rank'] !== '' ? intval($input['importance_rank']) : null;
$current_holder_id = isset($input['current_holder']) ? intval($input['current_holder']) : null;
$is_acting = isset($input['is_acting']) ? (intval($input['is_acting']) ? 1 : 0) : 0;

if (empty($position_id) || empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Position ID, name, and category are required']);
    exit();
}

try {
    // Update position
    $positionUpdate = supabase_rest_request(
        'PATCH',
        '/rest/v1/camarilla_positions',
        ['position_id' => 'eq.' . $position_id],
        [
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'importance_rank' => $importance_rank
        ],
        ['Prefer: return=minimal']
    );
    if ($positionUpdate['error'] !== null) {
        throw new Exception('Failed to update position: ' . $positionUpdate['error']);
    }

    // Handle current holder assignment if provided
    if ($current_holder_id !== null) {
        $default_night = CAMARILLA_DEFAULT_NIGHT;
        
        // If current_holder_id is 0, it means the user selected "Vacant" in the dropdown.
        // For single-holder positions, this clears the position.
        // For multi-holder positions (Talon/Whip), we'll assume it means clearing ALL for now 
        // since the UI only supports a single selection.
        if ($current_holder_id === 0) {
            $endAssignmentsResult = supabase_rest_request(
                'PATCH',
                '/rest/v1/camarilla_position_assignments',
                [
                    'position_id' => 'eq.' . $position_id,
                    'or' => '(end_night.is.null,end_night.gte.' . $default_night . ')'
                ],
                ['end_night' => $default_night],
                ['Prefer: return=minimal']
            );
            if ($endAssignmentsResult['error'] !== null) {
                throw new Exception('Failed to clear assignments: ' . $endAssignmentsResult['error']);
            }
        } else {
            // Get character name for assignment
            $characterRows = supabase_table_get('characters', [
                'select' => 'id,character_name',
                'id' => 'eq.' . (string) $current_holder_id,
                'limit' => '1'
            ]);
            $character = !empty($characterRows) ? $characterRows[0] : null;
            
            if ($character) {
                // Check if this position allows multiple holders (Talon, Whip)
                $allows_multiple = in_array(strtolower($position_id), ['talon', 'whip']);

                // Only end ALL existing active assignments if NOT a multi-holder position
                if (!$allows_multiple) {
                    $endAssignmentsResult = supabase_rest_request(
                        'PATCH',
                        '/rest/v1/camarilla_position_assignments',
                        [
                            'position_id' => 'eq.' . $position_id,
                            'or' => '(end_night.is.null,end_night.gte.' . $default_night . ')'
                        ],
                        ['end_night' => $default_night],
                        ['Prefer: return=minimal']
                    );
                    if ($endAssignmentsResult['error'] !== null) {
                        throw new Exception('Failed to end active assignments: ' . $endAssignmentsResult['error']);
                    }
                }
                
                // Create character_id string for assignment (e.g., "SABINE_TOREADOR")
                $assignment_character_id = strtoupper(str_replace([' ', '-'], '_', $character['character_name']));
                
                // Always end any existing assignment for THIS specific character in THIS position
                // to avoid duplicates or to update their status (like switching from acting to permanent)
                $endSpecificResult = supabase_rest_request(
                    'PATCH',
                    '/rest/v1/camarilla_position_assignments',
                    [
                        'position_id' => 'eq.' . $position_id,
                        'character_id' => 'eq.' . $assignment_character_id,
                        'or' => '(end_night.is.null,end_night.gte.' . $default_night . ')'
                    ],
                    ['end_night' => $default_night],
                    ['Prefer: return=minimal']
                );
                if ($endSpecificResult['error'] !== null) {
                    throw new Exception('Failed to end previous character assignment: ' . $endSpecificResult['error']);
                }
                
                // Create new assignment
                $insertAssignmentResult = supabase_rest_request(
                    'POST',
                    '/rest/v1/camarilla_position_assignments',
                    [],
                    [[
                        'position_id' => $position_id,
                        'character_id' => $assignment_character_id,
                        'start_night' => $default_night,
                        'end_night' => null,
                        'is_acting' => $is_acting
                    ]],
                    ['Prefer: return=minimal']
                );
                if ($insertAssignmentResult['error'] !== null) {
                    throw new Exception('Failed to create assignment: ' . $insertAssignmentResult['error']);
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Position updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

