<?php
/**
 * Update NPC Notes API
 * Updates agent_notes and acting_notes for characters
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$character_id = intval($input['character_id'] ?? 0);
$agent_notes = trim($input['agentNotes'] ?? '');
$acting_notes = trim($input['actingNotes'] ?? '');

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid character ID']);
    exit();
}

try {
    // Update character notes
    $query = "UPDATE characters SET 
              agent_notes = ?,
              acting_notes = ?,
              updated_at = NOW()
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssi', $agent_notes, $acting_notes, $character_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Notes updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update notes: ' . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

