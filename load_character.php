<?php
/**
 * Load Character API (Player-accessible)
 * Wrapper that allows players to load their own characters
 */
// Suppress error output to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/supabase_client.php';

$character_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
    // Verify character belongs to user (unless admin)
    $charRows = supabase_table_get('characters', [
        'select' => '*',
        'id' => 'eq.' . (string) $character_id,
        'limit' => '1',
    ]);
    $char = !empty($charRows) ? $charRows[0] : null;
    
    if (!$char) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    // Check ownership or admin role
    $is_owner = ($char['user_id'] == $_SESSION['user_id']);
    require_once __DIR__ . '/includes/verify_role.php';
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = verifyUserRole(null, $user_id);
    $is_admin = isAdminUser($user_role);
    
    if (!$is_owner && !$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Get traits
    $traits = supabase_table_get('character_traits', [
        'select' => 'trait_name,trait_category,trait_level',
        'character_id' => 'eq.' . (string) $character_id,
    ]);
    
    // Get abilities
    $characterAbilities = supabase_table_get('character_abilities', [
        'select' => 'ability_name,ability_category,level,specialization',
        'character_id' => 'eq.' . (string) $character_id,
    ]);
    $abilityDefs = supabase_table_get('abilities', [
        'select' => 'name,category',
    ]);
    $abilityCategoryMap = [];
    foreach ($abilityDefs as $abilityDef) {
        $name = isset($abilityDef['name']) ? (string) $abilityDef['name'] : '';
        if ($name === '') {
            continue;
        }
        $abilityCategoryMap[$name] = $abilityDef['category'] ?? null;
    }
    $abilities = [];
    foreach ($characterAbilities as $abilityRow) {
        $abilityName = isset($abilityRow['ability_name']) ? (string) $abilityRow['ability_name'] : '';
        $resolvedCategory = $abilityRow['ability_category'] ?? null;
        if (($resolvedCategory === null || $resolvedCategory === '') && isset($abilityCategoryMap[$abilityName])) {
            $resolvedCategory = $abilityCategoryMap[$abilityName];
        }
        $abilities[] = [
            'ability_name' => $abilityName,
            'ability_category' => $resolvedCategory,
            'level' => $abilityRow['level'] ?? 0,
            'specialization' => $abilityRow['specialization'] ?? null,
        ];
    }
    usort($abilities, static function ($a, $b) {
        $aCategory = strtolower((string)($a['ability_category'] ?? ''));
        $bCategory = strtolower((string)($b['ability_category'] ?? ''));
        $aName = strtolower((string)($a['ability_name'] ?? ''));
        $bName = strtolower((string)($b['ability_name'] ?? ''));
        if ($aCategory === $bCategory) {
            return $aName <=> $bName;
        }
        return $aCategory <=> $bCategory;
    });
    
    // Get disciplines with powers
    $disciplines = supabase_table_get('character_disciplines', [
        'select' => 'discipline_name,level,is_custom',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'discipline_name.asc',
    ]);
    
    // Get powers for each discipline
    foreach ($disciplines as &$disc) {
        $powers = supabase_table_get('character_discipline_powers', [
            'select' => 'power_name,level',
            'character_id' => 'eq.' . (string) $character_id,
            'discipline_name' => 'eq.' . (string)($disc['discipline_name'] ?? ''),
            'order' => 'level.asc,power_name.asc',
        ]);
        $disc['powers'] = $powers;
        $disc['power_count'] = count($powers);
    }
    unset($disc);
    
    // Get backgrounds
    $backgrounds = supabase_table_get('character_backgrounds', [
        'select' => 'background_name,level',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'background_name.asc',
    ]);
    
    // Get morality
    $moralityRows = supabase_table_get('character_morality', [
        'select' => '*',
        'character_id' => 'eq.' . (string) $character_id,
        'limit' => '1',
    ]);
    $morality = !empty($moralityRows) ? $moralityRows[0] : null;
    
    // Get merits & flaws
    $merits_flaws = supabase_table_get('character_merits_flaws', [
        'select' => 'name,type,category,point_value,xp_bonus,description',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'type.asc,name.asc',
    ]);
    
    // Get status
    $statusRows = supabase_table_get('character_status', [
        'select' => '*',
        'character_id' => 'eq.' . (string) $character_id,
        'limit' => '1',
    ]);
    $status = !empty($statusRows) ? $statusRows[0] : null;
    
    // Check if character image file actually exists
    $character_image = null;
    $upload_dir = __DIR__ . '/uploads/characters/';
    $name_candidates = [];
    if (!empty($char['portrait_name'])) {
        $name_candidates[] = trim((string)$char['portrait_name']);
    }
    if (!empty($char['character_image'])) {
        $name_candidates[] = trim((string)$char['character_image']);
    }
    foreach ($name_candidates as $candidate) {
        if ($candidate !== '' && file_exists($upload_dir . basename($candidate))) {
            $character_image = basename($candidate);
            break;
        }
    }
    
    // Map database fields to expected format
    $response = [
        'success' => true,
        'character' => [
            'id' => $char['id'],
            'character_name' => $char['character_name'],
            'player_name' => $char['player_name'],
            'chronicle' => $char['chronicle'],
            'clan' => $char['clan'],
            'generation' => $char['generation'],
            'nature' => $char['nature'],
            'demeanor' => $char['demeanor'],
            'sire' => $char['sire'],
            'concept' => $char['concept'],
            'biography' => $char['biography'],
            'appearance' => $char['appearance'],
            'notes' => $char['notes'],
            'equipment' => $char['equipment'],
            'character_image' => $character_image,
            'clan_logo_url' => $char['clan_logo_url'],
            'current_state' => $char['current_state'],
            'camarilla_status' => $char['camarilla_status'],
            'total_xp' => $char['experience_total'] ?? 0,
            'spent_xp' => $char['experience_spent'] ?? 0,
            'custom_data' => $char['custom_data'],
            'created_at' => $char['created_at'],
            'updated_at' => $char['updated_at']
        ],
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds,
        'morality' => $morality,
        'merits_flaws' => $merits_flaws,
        'status' => $status
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error but don't expose details in response
    error_log('load_character.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading character data'
    ]);
    exit();
}
?>

