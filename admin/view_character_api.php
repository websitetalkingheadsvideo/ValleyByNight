<?php
/**
 * View Character API
 * Returns complete character data for modal display
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$character_id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if ($character_id === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit();
}

try {
    $charRows = supabase_table_get('characters', [
        'select' => '*',
        'id' => 'eq.' . $character_id,
        'limit' => '1',
    ]);
    $char = !empty($charRows) ? $charRows[0] : null;

    if (!$char) {
        echo json_encode(['success' => false, 'message' => 'Character not found']);
        exit();
    }
    
    // Get traits (positive only: is_negative=false or null)
    $traitsRows = supabase_table_get('character_traits', [
        'select' => 'trait_name,trait_category,is_negative',
        'character_id' => 'eq.' . (string) $character_id,
        'or' => '(is_negative.eq.false,is_negative.is.null)',
    ]);
    $traits = [];
    foreach ($traitsRows as $r) {
        $traits[] = [
            'trait_name' => $r['trait_name'] ?? '',
            'trait_category' => $r['trait_category'] ?? '',
            'trait_type' => !empty($r['is_negative']) ? 'negative' : 'positive',
        ];
    }
    
    // Get abilities (join with abilities table to get category if not stored in character_abilities)
    $characterAbilities = supabase_table_get('character_abilities', [
        'select' => 'ability_name,category,level,specialization',
        'character_id' => 'eq.' . (string) $character_id,
    ]);
    try {
        $abilityDefs = supabase_table_get('lookup_abilities', ['select' => 'name,category']);
    } catch (Throwable $e) {
        $abilityDefs = [];
    }
    $abilityCategoryMap = [];
    foreach ($abilityDefs as $abilityDef) {
        $abilityName = (string)($abilityDef['name'] ?? $abilityDef['ability_name'] ?? '');
        if ($abilityName === '') {
            continue;
        }
        $abilityCategoryMap[$abilityName] = $abilityDef['category'] ?? null;
    }
    $abilities = [];
    foreach ($characterAbilities as $abilityRow) {
        $abilityName = (string)($abilityRow['ability_name'] ?? '');
        $abilityCategory = $abilityRow['category'] ?? $abilityRow['ability_category'] ?? null;
        if (($abilityCategory === null || $abilityCategory === '') && isset($abilityCategoryMap[$abilityName])) {
            $abilityCategory = $abilityCategoryMap[$abilityName];
        }
        $abilities[] = [
            'ability_name' => $abilityName,
            'ability_category' => $abilityCategory,
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
        'select' => 'discipline_name,level',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'discipline_name.asc',
    ]);
    
    // Add is_custom flag (default to false if column doesn't exist)
    foreach ($disciplines as &$disc) {
        $disc['is_custom'] = false;
    }
    unset($disc);
    
    // character_discipline_powers does not exist in Supabase; discipline_powers is reference-only (no character_id)
    foreach ($disciplines as &$disc) {
        try {
            $powers = supabase_table_get('discipline_powers', [
                'select' => 'power_name,power_level',
                'discipline_name' => 'eq.' . (string)($disc['discipline_name'] ?? ''),
                'order' => 'power_level.asc,power_name.asc',
            ]);
            $disc['powers'] = array_map(static fn($p) => ['power_name' => $p['power_name'] ?? '', 'level' => $p['power_level'] ?? 0], $powers);
        } catch (Throwable $e) {
            $disc['powers'] = [];
        }
        $disc['power_count'] = count($disc['powers']);
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
    
    // Get merits & flaws (Supabase uses cost, not point_value/point_cost)
    $merits_flaws_rows = supabase_table_get('character_merits_flaws', [
        'select' => 'name,type,category,cost,description',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'type.asc,name.asc',
    ]);
    $merits_flaws = [];
    foreach ($merits_flaws_rows as $r) {
        $merits_flaws[] = [
            'name' => $r['name'] ?? '',
            'type' => $r['type'] ?? '',
            'category' => $r['category'] ?? '',
            'point_value' => $r['cost'] ?? null,
            'point_cost' => $r['cost'] ?? null,
            'description' => $r['description'] ?? '',
            'xp_bonus' => null,
        ];
    }
    
    // character_status table does not exist in Supabase; use character fields
    $status = [
        'health_levels' => $char['health_levels'] ?? null,
        'blood_pool_current' => $char['blood_pool_current'] ?? null,
        'blood_pool_maximum' => $char['blood_pool'] ?? null,
        'sect_status' => null,
        'clan_status' => null,
        'city_status' => null,
    ];
    
    // character_coteries table does not exist in Supabase
    $coteries_from_json = [];
    $coteries_from_agent = [];
    $coterie_members = supabase_table_get('coterie_members', [
        'select' => 'coterie_id,role',
        'character_id' => 'eq.' . (string) $character_id,
    ]);
    foreach ($coterie_members as $member) {
        $coterie_id = isset($member['coterie_id']) ? (int)$member['coterie_id'] : 0;
        if ($coterie_id <= 0) {
            continue;
        }
        $coteries = supabase_table_get('coteries', [
            'select' => 'coterie_name,description',
            'id' => 'eq.' . (string) $coterie_id,
            'limit' => '1',
        ]);
        if (empty($coteries)) {
            continue;
        }
        $coterie = $coteries[0];
        $coteries_from_agent[] = [
            'coterie_name' => $coterie['coterie_name'] ?? '',
            'coterie_type' => $coterie['description'] ?? '',
            'role' => $member['role'] ?? '',
            'description' => '',
        ];
    }
    
    // Merge both sources (coterie_members takes precedence if duplicate names)
    $coterie_map = [];
    foreach ($coteries_from_json as $coterie) {
        $coterie_map[$coterie['coterie_name']] = $coterie;
    }
    foreach ($coteries_from_agent as $member) {
        $coterie_map[$member['coterie_name']] = $member;
    }
    $coteries = array_values($coterie_map);
    
    // Get relationships (Supabase has related_character_name, relationship_type, description; no relationship_subtype, strength)
    $rel_rows = supabase_table_get('character_relationships', [
        'select' => 'related_character_name,relationship_type,description',
        'character_id' => 'eq.' . (string) $character_id,
        'order' => 'relationship_type.asc,related_character_name.asc',
    ]);
    $relationships = [];
    foreach ($rel_rows as $r) {
        $relationships[] = [
            'related_character_name' => $r['related_character_name'] ?? '',
            'relationship_type' => $r['relationship_type'] ?? '',
            'relationship_subtype' => null,
            'strength' => null,
            'description' => $r['description'] ?? '',
        ];
    }
    
    // ghouls table does not exist in Supabase
    $ghoul_overlay = null;
    
    // Resolve portrait from actual files first to avoid stale DB image values.
    $character_image = null;
    $upload_dir = dirname(__DIR__) . '/uploads/characters/';
    $character_name = trim((string)($char['character_name'] ?? ''));

    $normalize_image_name = static function ($value): string {
        $normalized = trim((string)$value);
        if ($normalized === '') {
            return '';
        }
        $normalized = str_replace('\\', '/', $normalized);
        $parsed_path = parse_url($normalized, PHP_URL_PATH);
        if (is_string($parsed_path) && $parsed_path !== '') {
            $normalized = $parsed_path;
        }
        return trim((string)basename($normalized));
    };

    // 1) Explicit portrait_name if the referenced file exists.
    if (!empty($char['portrait_name'])) {
        $portrait_candidate = $normalize_image_name($char['portrait_name']);
        if ($portrait_candidate !== '' && file_exists($upload_dir . $portrait_candidate)) {
            $character_image = $portrait_candidate;
        }
    }

    // 2) Character-name based file discovery (preferred over stale hashed filenames).
    if ($character_image === null && $character_name !== '') {
        $bases = [$character_name, str_replace(' ', '_', $character_name)];
        $extensions = ['png', 'webp', 'jpg', 'jpeg', 'jfif', 'gif'];

        foreach ($bases as $base_name) {
            foreach ($extensions as $extension) {
                $candidate = $base_name . '.' . $extension;
                if (file_exists($upload_dir . $candidate)) {
                    $character_image = $candidate;
                    break 2;
                }
            }
        }
    }

    // 3) character_image field only if that exact file exists.
    if ($character_image === null && !empty($char['character_image'])) {
        $image_candidate = $normalize_image_name($char['character_image']);
        if ($image_candidate !== '' && file_exists($upload_dir . $image_candidate)) {
            $character_image = $image_candidate;
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
            'derangement' => $char['derangement'] ?? null,
            'sire' => $char['sire'],
            'concept' => $char['concept'],
            'biography' => $char['biography'],
            'appearance' => $char['appearance'],
            'notes' => $char['notes'],
            'equipment' => $char['equipment'],
            'character_image' => $character_image,
            'clan_logo_url' => $char['clan_logo_url'] ?? null,
            'current_state' => $char['status'] ?? $char['current_state'] ?? 'active',
            'camarilla_status' => $char['camarilla_status'],
            'total_xp' => $char['experience_total'] ?? 0,
            'spent_xp' => $char['spent_xp'] ?? $char['experience_spent'] ?? 0,
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
        'status' => $status,
        'coteries' => $coteries,
        'relationships' => $relationships,
        'ghoul_overlay' => $ghoul_overlay
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

