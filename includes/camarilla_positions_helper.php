<?php
/**
 * Camarilla Positions Helper Functions
 * Provides database query functions for Camarilla position data
 */
declare(strict_types=1);

require_once __DIR__ . '/supabase_client.php';

// Default "tonight" date for the game
define('CAMARILLA_DEFAULT_NIGHT', '1994-10-21 00:00:00');

/**
 * Normalize character names into assignment key formats.
 *
 * @return array<int,string>
 */
function camarilla_character_assignment_keys(string $characterName): array {
    $base = strtoupper($characterName);
    $underscored = strtoupper(str_replace(' ', '_', $characterName));
    $underscoredNoHyphen = strtoupper(str_replace('-', '_', str_replace(' ', '_', $characterName)));
    return array_values(array_unique([$underscored, $underscoredNoHyphen, $base]));
}

/**
 * @param array<int,array<string,mixed>> $characters
 * @return array<string,array<string,mixed>>
 */
function camarilla_build_character_lookup(array $characters): array {
    $lookup = [];
    foreach ($characters as $character) {
        $name = isset($character['character_name']) ? (string) $character['character_name'] : '';
        if ($name === '') {
            continue;
        }
        $keys = camarilla_character_assignment_keys($name);
        foreach ($keys as $key) {
            if (!isset($lookup[$key])) {
                $lookup[$key] = $character;
            }
        }
    }
    return $lookup;
}

/**
 * @param array<int,array<string,mixed>> $assignments
 * @param array<string,array<string,mixed>> $characterLookup
 * @return array<int,array<string,mixed>>
 */
function camarilla_attach_character_details(array $assignments, array $characterLookup): array {
    $rows = [];
    foreach ($assignments as $assignment) {
        $assignmentCharacterId = isset($assignment['character_id']) ? (string) $assignment['character_id'] : '';
        $character = $characterLookup[strtoupper($assignmentCharacterId)] ?? null;

        $rows[] = [
            'position_id' => $assignment['position_id'] ?? null,
            'assignment_character_id' => $assignmentCharacterId,
            'start_night' => $assignment['start_night'] ?? null,
            'end_night' => $assignment['end_night'] ?? null,
            'is_acting' => $assignment['is_acting'] ?? null,
            'character_name' => $character['character_name'] ?? null,
            'clan' => $character['clan'] ?? null,
            'character_id' => $character['id'] ?? null,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $aActing = (int) ($a['is_acting'] ?? 1);
        $bActing = (int) ($b['is_acting'] ?? 1);
        if ($aActing !== $bActing) {
            return $aActing <=> $bActing;
        }
        return strcmp((string) ($b['start_night'] ?? ''), (string) ($a['start_night'] ?? ''));
    });

    return $rows;
}

/**
 * Get all current holders for a specific position on a given night
 * Supports positions that can have multiple holders (like Talon)
 * 
 * @param string $positionId Position ID
 * @param string|null $night In-game night (DATETIME format). If null, uses default.
 * @return array Array of assignment records with character details (empty if vacant)
 */
function get_all_current_holders_for_position(string $positionId, ?string $night = null): array {
    if ($night === null) {
        $night = CAMARILLA_DEFAULT_NIGHT;
    }

    $assignments = supabase_table_get('camarilla_position_assignments', [
        'select' => 'position_id,character_id,start_night,end_night,is_acting',
        'position_id' => 'eq.' . $positionId,
        'start_night' => 'lte.' . $night,
        'or' => '(end_night.is.null,end_night.gte.' . $night . ')',
        'order' => 'is_acting.asc,start_night.desc'
    ]);

    if (empty($assignments)) {
        return [];
    }

    $characters = supabase_table_get('characters', [
        'select' => 'id,character_name,clan'
    ]);
    $characterLookup = camarilla_build_character_lookup($characters);

    return camarilla_attach_character_details($assignments, $characterLookup);
}

/**
 * Get the current holder for a specific position on a given night
 * Returns single holder (first one) for backward compatibility
 * For positions with multiple holders, use get_all_current_holders_for_position()
 * 
 * @param string $positionId Position ID
 * @param string|null $night In-game night (DATETIME format). If null, uses default.
 * @return array|null Assignment record with character details, or null if vacant
 */
function get_current_holder_for_position(string $positionId, ?string $night = null): ?array {
    $holders = get_all_current_holders_for_position($positionId, $night);
    return !empty($holders) ? $holders[0] : null;
}

/**
 * Get all positions with their current holders for a given night
 * Supports positions with multiple holders (like Talon)
 * 
 * @param string|null $night In-game night (DATETIME format). If null, uses default.
 * @return array Array of positions with nested current_holders array (empty if vacant)
 */
function get_all_positions_with_current_holders(?string $night = null): array {
    if ($night === null) {
        $night = CAMARILLA_DEFAULT_NIGHT;
    }

    $positions = supabase_table_get('camarilla_positions', [
        'select' => '*',
        'order' => 'importance_rank.asc,category.asc,name.asc'
    ]);
    
    $result = [];
    foreach ($positions as $position) {
        // Get all holders (supports multiple holders for positions like Talon)
        $holders = get_all_current_holders_for_position($position['position_id'], $night);
        
        $result[] = [
            'position' => $position,
            'current_holders' => $holders, // Array of holders (can be empty)
            'current_holder' => !empty($holders) ? $holders[0] : null // First holder for backward compatibility
        ];
    }
    
    return $result;
}

/**
 * Get complete history of assignments for a specific position
 * 
 * @param string $positionId Position ID
 * @return array All assignments ordered by start_night DESC
 */
function get_position_history(string $positionId): array {
    $assignments = supabase_table_get('camarilla_position_assignments', [
        'select' => '*',
        'position_id' => 'eq.' . $positionId,
        'order' => 'start_night.desc'
    ]);
    if (empty($assignments)) {
        return [];
    }

    $characters = supabase_table_get('characters', [
        'select' => 'id,character_name,clan'
    ]);
    $characterLookup = camarilla_build_character_lookup($characters);

    $rows = [];
    foreach ($assignments as $assignment) {
        $assignmentCharacterId = isset($assignment['character_id']) ? (string) $assignment['character_id'] : '';
        $character = $characterLookup[strtoupper($assignmentCharacterId)] ?? null;
        $rows[] = array_merge($assignment, [
            'character_name' => $character['character_name'] ?? null,
            'clan' => $character['clan'] ?? null,
            'character_id' => $character['id'] ?? null
        ]);
    }

    return $rows;
}

/**
 * Get all positions a character has held (past and present)
 * 
 * @param string $characterId Character ID
 * @return array All position assignments for the character ordered by start_night DESC
 */
function get_character_position_history(string $characterId): array {
    $assignments = supabase_table_get('camarilla_position_assignments', [
        'select' => '*',
        'character_id' => 'eq.' . $characterId,
        'order' => 'start_night.desc'
    ]);
    if (empty($assignments)) {
        return [];
    }

    $positions = supabase_table_get('camarilla_positions', [
        'select' => 'position_id,name,category'
    ]);
    $positionLookup = [];
    foreach ($positions as $position) {
        $positionKey = isset($position['position_id']) ? (string) $position['position_id'] : '';
        if ($positionKey !== '') {
            $positionLookup[$positionKey] = $position;
        }
    }

    $rows = [];
    foreach ($assignments as $assignment) {
        $positionId = isset($assignment['position_id']) ? (string) $assignment['position_id'] : '';
        $position = $positionLookup[$positionId] ?? null;
        $rows[] = array_merge($assignment, [
            'position_name' => $position['name'] ?? null,
            'category' => $position['category'] ?? null,
            'position_id' => $position['position_id'] ?? $positionId
        ]);
    }

    return $rows;
}
