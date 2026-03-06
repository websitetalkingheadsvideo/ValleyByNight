<?php
/**
 * Wraith Character Save Handler
 * Handles saving Wraith: The Oblivion characters to the wraith_characters table
 * 
 * Version: 1.0.0
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Global error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => "PHP Error: $message in $file on line $line"
        ]);
        exit();
    }
});

// Start session first
session_start();

// Set headers
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once __DIR__ . '/supabase_client.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON data
$input = file_get_contents('php://input');
error_log('Wraith save - Raw input: ' . $input);

$data = json_decode($input, true);
error_log('Wraith save - Decoded data: ' . json_encode($data));

if (!$data) {
    error_log('Wraith save - JSON decode failed');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Clean the data
function cleanString($value) {
    if (is_string($value)) {
        return trim($value);
    }
    return $value;
}

function cleanInt($value) {
    return (int)$value;
}

function cleanJsonData($value) {
    // If empty, return NULL for JSON column
    if (empty($value) || trim($value) === '') {
        return null;
    }
    
    // If it's already an array or object, encode it
    if (is_array($value) || is_object($value)) {
        return json_encode($value);
    }
    
    // If it's a string, try to validate it's valid JSON
    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }
    
    // Try to decode and re-encode to validate JSON
    $decoded = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Valid JSON, return the original trimmed string
        return $trimmed;
    }
    
    // Not valid JSON, treat as plain text and wrap in JSON object
    return json_encode(['text' => $trimmed]);
}

// Clean basic fields
$cleanData = [
    'character_name' => cleanString($data['character_name'] ?? ''),
    'shadow_name' => cleanString($data['shadow_name'] ?? ''),
    'player_name' => cleanString($data['player_name'] ?? ''),
    'chronicle' => cleanString($data['chronicle'] ?? 'Valley by Night'),
    'nature' => cleanString($data['nature'] ?? ''),
    'demeanor' => cleanString($data['demeanor'] ?? ''),
    'concept' => cleanString($data['concept'] ?? ''),
    'circle' => cleanString($data['circle'] ?? ''),
    'guild' => cleanString($data['guild'] ?? ''),
    'legion_at_death' => cleanString($data['legion_at_death'] ?? ''),
    'date_of_death' => !empty($data['date_of_death']) ? cleanString($data['date_of_death']) : null,
    'cause_of_death' => cleanString($data['cause_of_death'] ?? ''),
    'pc' => cleanInt($data['pc'] ?? $data['is_pc'] ?? 1),
    'appearance' => cleanString($data['appearance'] ?? ''),
    'ghostly_appearance' => cleanString($data['ghostly_appearance'] ?? ''),
    'biography' => cleanString($data['biography'] ?? ''),
    'notes' => cleanString($data['notes'] ?? ''),
    'equipment' => cleanString($data['equipment'] ?? ''),
    'custom_data' => cleanJsonData($data['custom_data'] ?? ''),
    'character_image' => cleanString($data['imagePath'] ?? $data['character_image'] ?? ''),
    'status' => cleanString($data['status'] ?? $data['current_state'] ?? 'active'),
    'actingNotes' => cleanString($data['actingNotes'] ?? ''),
    'agentNotes' => cleanString($data['agentNotes'] ?? ''),
    'health_status' => cleanString($data['health_status'] ?? ''),
    'willpower_permanent' => cleanInt($data['willpower_permanent'] ?? 5),
    'willpower_current' => cleanInt($data['willpower_current'] ?? 5)
];

// Validate status
$validStates = ['active', 'inactive', 'archived'];
$cleanData['status'] = strtolower($cleanData['status'] ?? 'active');
if (!in_array($cleanData['status'], $validStates, true)) {
    $cleanData['status'] = 'active';
}

// Clean JSON fields
$jsonFields = [
    'timeline' => $data['timeline'] ?? null,
    'personality' => $data['personality'] ?? null,
    'traits' => $data['traits'] ?? null,
    'negativeTraits' => $data['negativeTraits'] ?? null,
    'attributes' => $data['attributes'] ?? null,
    'abilities' => $data['abilities'] ?? null,
    'specializations' => $data['specializations'] ?? null,
    'fetters' => $data['fetters'] ?? null,
    'passions' => $data['passions'] ?? null,
    'arcanoi' => $data['arcanoi'] ?? null,
    'backgrounds' => $data['backgrounds'] ?? null,
    'backgroundDetails' => $data['backgroundDetails'] ?? null,
    'pathos_corpus' => $data['pathos_corpus'] ?? null,
    'shadow' => $data['shadow'] ?? null,
    'harrowing' => $data['harrowing'] ?? null,
    'merits_flaws' => $data['merits_flaws'] ?? null,
    'relationships' => $data['relationships'] ?? null,
    'artifacts' => $data['artifacts'] ?? null,
    'status_details' => $data['status'] ?? null
];

foreach ($jsonFields as $key => $value) {
    $cleanData[$key] = cleanJsonData($value);
}

// Handle XP fields
$cleanData['experience_total'] = cleanInt($data['status']['xp_total'] ?? $data['xp_total'] ?? 0);
$cleanData['spent_xp'] = cleanInt($data['status']['xp_spent'] ?? $data['spent_xp'] ?? 0);
$cleanData['experience_unspent'] = $cleanData['experience_total'] - $cleanData['spent_xp'];
$cleanData['shadow_xp_total'] = cleanInt($data['status']['shadow_xp_total'] ?? 0);
$cleanData['shadow_xp_spent'] = cleanInt($data['status']['shadow_xp_spent'] ?? 0);
$cleanData['shadow_xp_available'] = $cleanData['shadow_xp_total'] - $cleanData['shadow_xp_spent'];

// Identify if this is an update or create
$character_id = 0;
if (isset($data['character_id'])) {
    $character_id = (int)$data['character_id'];
} elseif (isset($data['id'])) {
    $character_id = (int)$data['id'];
}

try {
    $payload = [
        'character_name' => $cleanData['character_name'],
        'shadow_name' => $cleanData['shadow_name'],
        'player_name' => $cleanData['player_name'],
        'chronicle' => $cleanData['chronicle'],
        'nature' => $cleanData['nature'],
        'demeanor' => $cleanData['demeanor'],
        'concept' => $cleanData['concept'],
        'circle' => $cleanData['circle'],
        'guild' => $cleanData['guild'],
        'legion_at_death' => $cleanData['legion_at_death'],
        'date_of_death' => $cleanData['date_of_death'],
        'cause_of_death' => $cleanData['cause_of_death'],
        'pc' => $cleanData['pc'],
        'appearance' => $cleanData['appearance'],
        'ghostly_appearance' => $cleanData['ghostly_appearance'],
        'biography' => $cleanData['biography'],
        'notes' => $cleanData['notes'],
        'equipment' => $cleanData['equipment'],
        'custom_data' => $cleanData['custom_data'],
        'status' => $cleanData['status'],
        'timeline' => $cleanData['timeline'],
        'personality' => $cleanData['personality'],
        'traits' => $cleanData['traits'],
        'negativeTraits' => $cleanData['negativeTraits'],
        'attributes' => $cleanData['attributes'],
        'abilities' => $cleanData['abilities'],
        'specializations' => $cleanData['specializations'],
        'fetters' => $cleanData['fetters'],
        'passions' => $cleanData['passions'],
        'arcanoi' => $cleanData['arcanoi'],
        'backgrounds' => $cleanData['backgrounds'],
        'backgroundDetails' => $cleanData['backgroundDetails'],
        'willpower_permanent' => $cleanData['willpower_permanent'],
        'willpower_current' => $cleanData['willpower_current'],
        'pathos_corpus' => $cleanData['pathos_corpus'],
        'shadow' => $cleanData['shadow'],
        'harrowing' => $cleanData['harrowing'],
        'merits_flaws' => $cleanData['merits_flaws'],
        'status_details' => $cleanData['status_details'],
        'relationships' => $cleanData['relationships'],
        'artifacts' => $cleanData['artifacts'],
        'actingNotes' => $cleanData['actingNotes'],
        'agentNotes' => $cleanData['agentNotes'],
        'health_status' => $cleanData['health_status'],
        'experience_total' => $cleanData['experience_total'],
        'spent_xp' => $cleanData['spent_xp'],
        'experience_unspent' => $cleanData['experience_unspent'],
        'shadow_xp_total' => $cleanData['shadow_xp_total'],
        'shadow_xp_spent' => $cleanData['shadow_xp_spent'],
        'shadow_xp_available' => $cleanData['shadow_xp_available'],
    ];
    if ($cleanData['character_image'] !== '') {
        $payload['character_image'] = $cleanData['character_image'];
    }

    if ($character_id > 0) {
        $updateResult = supabase_rest_request(
            'PATCH',
            '/rest/v1/wraith_characters',
            ['id' => 'eq.' . (string) $character_id],
            $payload,
            ['Prefer: return=minimal']
        );
        if ($updateResult['error'] !== null) {
            throw new RuntimeException('Failed to update character: ' . $updateResult['error']);
        }
    } else {
        $payload['user_id'] = (int) $_SESSION['user_id'];
        $insertResult = supabase_rest_request(
            'POST',
            '/rest/v1/wraith_characters',
            ['select' => 'id'],
            [$payload],
            ['Prefer: return=representation']
        );
        if ($insertResult['error'] !== null) {
            throw new RuntimeException('Failed to create character: ' . $insertResult['error']);
        }
        $insertedRows = $insertResult['data'];
        if (!is_array($insertedRows) || empty($insertedRows) || !isset($insertedRows[0]['id'])) {
            throw new RuntimeException('Create character response missing inserted ID.');
        }
        $character_id = (int) $insertedRows[0]['id'];
    }

    echo json_encode([
        'success' => true,
        'message' => ($character_id > 0 && isset($data['id'])) ? 'Character updated successfully!' : 'Character created successfully!',
        'character_id' => $character_id
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('save_wraith_character.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving character: ' . $e->getMessage()
    ]);
}
?>

