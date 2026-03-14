<?php
/**
 * NPC Briefing API (Supabase)
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$character_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$character_id) {
    echo json_encode(['success' => false, 'error' => 'Character ID required']);
    exit;
}

try {
    $charRows = supabase_table_get('characters', [
        'select' => 'id,character_name,clan,generation,sire,nature,demeanor,concept,biography,agent_notes,acting_notes',
        'id' => 'eq.' . $character_id,
        'limit' => '1'
    ]);
    if (empty($charRows)) {
        echo json_encode(['success' => false, 'error' => 'Character not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $character = $charRows[0];
    $character['agentNotes'] = $character['agent_notes'] ?? '';
    $character['actingNotes'] = $character['acting_notes'] ?? '';

    $traitRows = supabase_table_get('character_traits', [
        'select' => 'trait_name,trait_category',
        'character_id' => 'eq.' . $character_id,
        'order' => 'trait_category.asc,trait_name.asc'
    ]);
    $traits = ['physical' => [], 'social' => [], 'mental' => []];
    foreach ($traitRows as $t) {
        $cat = strtolower((string) ($t['trait_category'] ?? ''));
        if (isset($traits[$cat])) {
            $traits[$cat][] = $t['trait_name'] ?? '';
        }
    }

    $abilities = supabase_table_get('character_abilities', [
        'select' => 'ability_name,ability_category,level,specialization',
        'character_id' => 'eq.' . $character_id,
        'order' => 'ability_category.asc,ability_name.asc'
    ]);

    $disciplines = supabase_table_get('character_disciplines', [
        'select' => 'discipline_name,level',
        'character_id' => 'eq.' . $character_id,
        'order' => 'level.desc,discipline_name.asc'
    ]);

    $backgrounds = supabase_table_get('character_backgrounds', [
        'select' => 'background_name,level',
        'character_id' => 'eq.' . $character_id,
        'order' => 'background_name.asc'
    ]);

    echo json_encode([
        'success' => true,
        'character' => $character,
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
