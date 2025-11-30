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

// Database connection
try {
    require_once __DIR__ . '/connect.php';
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit();
}

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
    $user_id = $_SESSION['user_id'];
    
    // Log the received data for debugging
    error_log('Wraith save - Character data: ' . json_encode($data));
    
    // Start transaction
    db_begin_transaction($conn);
    
    try {
        if ($character_id > 0) {
            // Update existing character
            $update_sql = "UPDATE wraith_characters SET 
                character_name = ?, shadow_name = ?, player_name = ?, chronicle = ?, 
                nature = ?, demeanor = ?, concept = ?, circle = ?, guild = ?, 
                legion_at_death = ?, date_of_death = ?, cause_of_death = ?, 
                pc = ?, appearance = ?, ghostly_appearance = ?, biography = ?, 
                notes = ?, equipment = ?, custom_data = ?, status = ?, 
                timeline = ?, personality = ?, traits = ?, negativeTraits = ?, 
                attributes = ?, abilities = ?, specializations = ?, fetters = ?, passions = ?, 
                arcanoi = ?, backgrounds = ?, backgroundDetails = ?, 
                willpower_permanent = ?, willpower_current = ?, 
                pathos_corpus = ?, shadow = ?, harrowing = ?, 
                merits_flaws = ?, status_details = ?, relationships = ?, 
                artifacts = ?, actingNotes = ?, agentNotes = ?, health_status = ?,
                experience_total = ?, spent_xp = ?, experience_unspent = ?,
                shadow_xp_total = ?, shadow_xp_spent = ?, shadow_xp_available = ?" .
                ($cleanData['character_image'] !== '' ? ", character_image = ?" : "") .
                " WHERE id = ?";

            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
            }

            $params = [
                $cleanData['character_name'],
                $cleanData['shadow_name'],
                $cleanData['player_name'],
                $cleanData['chronicle'],
                $cleanData['nature'],
                $cleanData['demeanor'],
                $cleanData['concept'],
                $cleanData['circle'],
                $cleanData['guild'],
                $cleanData['legion_at_death'],
                $cleanData['date_of_death'],
                $cleanData['cause_of_death'],
                $cleanData['pc'],
                $cleanData['appearance'],
                $cleanData['ghostly_appearance'],
                $cleanData['biography'],
                $cleanData['notes'],
                $cleanData['equipment'],
                $cleanData['custom_data'],
                $cleanData['status'],
                $cleanData['timeline'],
                $cleanData['personality'],
                $cleanData['traits'],
                $cleanData['negativeTraits'],
                $cleanData['attributes'],
                $cleanData['abilities'],
                $cleanData['specializations'],
                $cleanData['fetters'],
                $cleanData['passions'],
                $cleanData['arcanoi'],
                $cleanData['backgrounds'],
                $cleanData['backgroundDetails'],
                $cleanData['willpower_permanent'],
                $cleanData['willpower_current'],
                $cleanData['pathos_corpus'],
                $cleanData['shadow'],
                $cleanData['harrowing'],
                $cleanData['merits_flaws'],
                $cleanData['status_details'],
                $cleanData['relationships'],
                $cleanData['artifacts'],
                $cleanData['actingNotes'],
                $cleanData['agentNotes'],
                $cleanData['health_status'],
                $cleanData['experience_total'],
                $cleanData['spent_xp'],
                $cleanData['experience_unspent'],
                $cleanData['shadow_xp_total'],
                $cleanData['shadow_xp_spent'],
                $cleanData['shadow_xp_available']
            ];

            $types = 'ssssssssssssisssssssssssssssssssssssssssssssssssssiiiiiii';

            if ($cleanData['character_image'] !== '') {
                $params[] = $cleanData['character_image'];
                $types .= 's';
            }

            $params[] = $character_id;
            $types .= 'i';

            mysqli_stmt_bind_param($stmt, $types, ...$params);

            if (!mysqli_stmt_execute($stmt)) {
                error_log('Wraith character update error: ' . mysqli_stmt_error($stmt));
                throw new Exception('Failed to update character: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            // Create new character
            $character_sql = "INSERT INTO wraith_characters 
                (user_id, character_name, shadow_name, player_name, chronicle, character_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $character_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'issssss',
                $user_id,
                $cleanData['character_name'],
                $cleanData['shadow_name'],
                $cleanData['player_name'],
                $cleanData['chronicle'],
                $cleanData['character_image'],
                $cleanData['status']
            );

            if (!mysqli_stmt_execute($stmt)) {
                error_log('Wraith character insert error: ' . mysqli_stmt_error($stmt));
                throw new Exception('Failed to create character: ' . mysqli_stmt_error($stmt));
            }

            $character_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Now update with all the other fields
            $update_sql = "UPDATE wraith_characters SET 
                nature = ?, demeanor = ?, concept = ?, circle = ?, guild = ?, 
                legion_at_death = ?, date_of_death = ?, cause_of_death = ?, 
                pc = ?, appearance = ?, ghostly_appearance = ?, biography = ?, 
                notes = ?, equipment = ?, custom_data = ?, 
                timeline = ?, personality = ?, traits = ?, negativeTraits = ?, 
                attributes = ?, abilities = ?, specializations = ?, fetters = ?, passions = ?, 
                arcanoi = ?, backgrounds = ?, backgroundDetails = ?, 
                willpower_permanent = ?, willpower_current = ?, 
                pathos_corpus = ?, shadow = ?, harrowing = ?, 
                merits_flaws = ?, status_details = ?, relationships = ?, 
                artifacts = ?, actingNotes = ?, agentNotes = ?, health_status = ?,
                experience_total = ?, spent_xp = ?, experience_unspent = ?,
                shadow_xp_total = ?, shadow_xp_spent = ?, shadow_xp_available = ?
                WHERE id = ?";

            $stmt = mysqli_prepare($conn, $update_sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare update: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'ssssssssissssssssssssssssssssssssssssssssssssssiiiiiii',
                $cleanData['nature'],
                $cleanData['demeanor'],
                $cleanData['concept'],
                $cleanData['circle'],
                $cleanData['guild'],
                $cleanData['legion_at_death'],
                $cleanData['date_of_death'],
                $cleanData['cause_of_death'],
                $cleanData['pc'],
                $cleanData['appearance'],
                $cleanData['ghostly_appearance'],
                $cleanData['biography'],
                $cleanData['notes'],
                $cleanData['equipment'],
                $cleanData['custom_data'],
                $cleanData['timeline'],
                $cleanData['personality'],
                $cleanData['traits'],
                $cleanData['negativeTraits'],
                $cleanData['attributes'],
                $cleanData['abilities'],
                $cleanData['specializations'],
                $cleanData['fetters'],
                $cleanData['passions'],
                $cleanData['arcanoi'],
                $cleanData['backgrounds'],
                $cleanData['backgroundDetails'],
                $cleanData['willpower_permanent'],
                $cleanData['willpower_current'],
                $cleanData['pathos_corpus'],
                $cleanData['shadow'],
                $cleanData['harrowing'],
                $cleanData['merits_flaws'],
                $cleanData['status_details'],
                $cleanData['relationships'],
                $cleanData['artifacts'],
                $cleanData['actingNotes'],
                $cleanData['agentNotes'],
                $cleanData['health_status'],
                $cleanData['experience_total'],
                $cleanData['spent_xp'],
                $cleanData['experience_unspent'],
                $cleanData['shadow_xp_total'],
                $cleanData['shadow_xp_spent'],
                $cleanData['shadow_xp_available'],
                $character_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                error_log('Wraith character update error: ' . mysqli_stmt_error($stmt));
                throw new Exception('Failed to update character: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
        
        // Commit transaction
        db_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => ($character_id > 0 && isset($data['id'])) ? 'Character updated successfully!' : 'Character created successfully!',
            'character_id' => $character_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        db_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving character: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

