<?php
/**
 * Delete Character API
 * Handles DELETE method requests for DataManager compatibility
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database
require_once __DIR__ . '/includes/connect.php';

// Get character ID from query string (DELETE method)
$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

// Start transaction
db_begin_transaction($conn);

try {
    // Delete from all related tables
    $tables = [
        'character_traits',
        'character_negative_traits',
        'character_abilities',
        'character_ability_specializations',
        'character_disciplines',
        'character_backgrounds',
        'character_merits_flaws',
        'character_morality',
        'character_derangements',
        'character_equipment',
        'character_influences',
        'character_rituals',
        'character_status',
        'character_coteries',
        'character_relationships'
    ];
    
    foreach ($tables as $table) {
        $result = db_execute($conn, "DELETE FROM $table WHERE character_id = ?", 'i', [$character_id]);
        if ($result === false) {
            throw new Exception("Failed to delete from $table");
        }
    }
    
    // Finally delete the character itself
    $affected = db_execute($conn, "DELETE FROM characters WHERE id = ?", 'i', [$character_id]);
    
    if ($affected === false) {
        throw new Exception("Failed to delete character");
    }
    
    if ($affected > 0) {
        db_commit($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Character deleted successfully',
            'character_id' => $character_id
        ]);
    } else {
        db_rollback($conn);
        echo json_encode([
            'success' => false, 
            'message' => 'Character not found'
        ]);
    }
    
} catch (Exception $e) {
    db_rollback($conn);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>

