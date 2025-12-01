<?php
/**
 * NPC Briefing API
 * Returns character data formatted for NPC briefing modal
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$character_id) {
    echo json_encode(['success' => false, 'error' => 'Character ID required']);
    exit();
}

try {
    // Get character basic info
    $char_query = "SELECT id, character_name, clan, generation, sire, nature, demeanor, concept, biography, agent_notes as agentNotes, acting_notes as actingNotes
                   FROM characters 
                   WHERE id = ?";
    $stmt = mysqli_prepare($conn, $char_query);
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $char_result = mysqli_stmt_get_result($stmt);
    
    if (!$char_result || mysqli_num_rows($char_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    $character = mysqli_fetch_assoc($char_result);
    
    // Get traits
    $traits_query = "SELECT trait_name, trait_category 
                     FROM character_traits 
                     WHERE character_id = ? 
                     ORDER BY trait_category, trait_name";
    $stmt = mysqli_prepare($conn, $traits_query);
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $traits_result = mysqli_stmt_get_result($stmt);
    
    $traits = ['physical' => [], 'social' => [], 'mental' => []];
    while ($trait = mysqli_fetch_assoc($traits_result)) {
        $category = strtolower($trait['trait_category']);
        if (isset($traits[$category])) {
            $traits[$category][] = $trait['trait_name'];
        }
    }
    
    // Get abilities
    $abilities_query = "SELECT ability_name, ability_category, level, specialization 
                        FROM character_abilities 
                        WHERE character_id = ? 
                        ORDER BY ability_category, ability_name";
    $stmt = mysqli_prepare($conn, $abilities_query);
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $abilities_result = mysqli_stmt_get_result($stmt);
    
    $abilities = [];
    while ($ability = mysqli_fetch_assoc($abilities_result)) {
        $abilities[] = $ability;
    }
    
    // Get disciplines
    $disciplines_query = "SELECT discipline_name, level 
                          FROM character_disciplines 
                          WHERE character_id = ? 
                          ORDER BY level DESC, discipline_name";
    $stmt = mysqli_prepare($conn, $disciplines_query);
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $disciplines_result = mysqli_stmt_get_result($stmt);
    
    $disciplines = [];
    while ($discipline = mysqli_fetch_assoc($disciplines_result)) {
        $disciplines[] = $discipline;
    }
    
    // Get backgrounds
    $backgrounds_query = "SELECT background_name, level 
                         FROM character_backgrounds 
                         WHERE character_id = ? 
                         ORDER BY background_name";
    $stmt = mysqli_prepare($conn, $backgrounds_query);
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    $backgrounds_result = mysqli_stmt_get_result($stmt);
    
    $backgrounds = [];
    while ($background = mysqli_fetch_assoc($backgrounds_result)) {
        $backgrounds[] = $background;
    }
    
    echo json_encode([
        'success' => true,
        'character' => $character,
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

