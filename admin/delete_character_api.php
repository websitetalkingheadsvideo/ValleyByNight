<?php
/**
 * Delete Character API
 * Handles character deletion with CASCADE to all related tables
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$input = json_decode(file_get_contents('php://input'), true);
$character_id = isset($input['character_id']) ? (int)($input['character_id'] ?? 0) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
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
        'character_relationships',
        'ghouls'
    ];

    foreach ($tables as $table) {
        $result = supabase_rest_request(
            'DELETE',
            '/rest/v1/' . $table,
            ['character_id' => 'eq.' . (string) $character_id],
            null,
            ['Prefer: return=minimal']
        );
        if ($result['error'] !== null) {
            throw new Exception("Failed to delete from $table");
        }
    }

    $characterDeleteResult = supabase_rest_request(
        'DELETE',
        '/rest/v1/characters',
        ['id' => 'eq.' . (string) $character_id],
        null,
        ['Prefer: return=representation']
    );

    if ($characterDeleteResult['error'] !== null) {
        throw new Exception("Failed to delete character");
    }

    $deletedRows = is_array($characterDeleteResult['data']) ? $characterDeleteResult['data'] : [];
    $affected = count($deletedRows);

    if ($affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Character deleted successfully',
            'character_id' => $character_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Character not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
