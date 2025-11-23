<?php
/**
 * Camarilla Positions Helper Functions
 * Provides database query functions for Camarilla position data
 */

// Default "tonight" date for the game
define('CAMARILLA_DEFAULT_NIGHT', '1994-10-21 00:00:00');

/**
 * Get the current holder for a specific position on a given night
 * 
 * @param string $positionId Position ID
 * @param string|null $night In-game night (DATETIME format). If null, uses default.
 * @return array|null Assignment record with character details, or null if vacant
 */
function get_current_holder_for_position(string $positionId, ?string $night = null): ?array {
    global $conn;
    
    if ($night === null) {
        $night = CAMARILLA_DEFAULT_NIGHT;
    }
    
    // Query to find current holder, preferring non-acting over acting
    // JOIN on character_name - assignment table stores character_name as identifier
    // Try multiple transformations to handle different name formats
    $query = "SELECT 
                cpa.position_id,
                cpa.character_id as assignment_character_id,
                cpa.start_night,
                cpa.end_night,
                cpa.is_acting,
                c.character_name,
                c.clan,
                c.id as character_id
              FROM camarilla_position_assignments cpa
              LEFT JOIN characters c ON (
                UPPER(REPLACE(c.character_name, ' ', '_')) = cpa.character_id
                OR UPPER(REPLACE(REPLACE(c.character_name, ' ', '_'), '-', '_')) = cpa.character_id
                OR UPPER(c.character_name) = cpa.character_id
              )
              WHERE cpa.position_id = ?
                AND cpa.start_night <= ?
                AND (cpa.end_night IS NULL OR cpa.end_night >= ?)
              ORDER BY cpa.is_acting ASC, cpa.start_night DESC
              LIMIT 1";
    
    $result = db_fetch_one($conn, $query, "sss", [$positionId, $night, $night]);
    
    // DEBUG: Log query result
    if ($result) {
        error_log("DEBUG get_current_holder_for_position - position_id: $positionId, assignment_character_id: " . ($result['assignment_character_id'] ?? 'NULL') . ", character_id (from join): " . ($result['character_id'] ?? 'NULL') . ", character_name: " . ($result['character_name'] ?? 'NULL'));
    } else {
        error_log("DEBUG get_current_holder_for_position - position_id: $positionId, NO RESULT");
    }
    
    return $result ?: null;
}

/**
 * Get all positions with their current holders for a given night
 * 
 * @param string|null $night In-game night (DATETIME format). If null, uses default.
 * @return array Array of positions with nested current_holder object (or null if vacant)
 */
function get_all_positions_with_current_holders(?string $night = null): array {
    global $conn;
    
    if ($night === null) {
        $night = CAMARILLA_DEFAULT_NIGHT;
    }
    
    // Get all positions
    $positions_query = "SELECT * FROM camarilla_positions ORDER BY importance_rank ASC, category ASC, name ASC";
    $positions = db_fetch_all($conn, $positions_query);
    
    $result = [];
    foreach ($positions as $position) {
        $holder = get_current_holder_for_position($position['position_id'], $night);
        
        $result[] = [
            'position' => $position,
            'current_holder' => $holder
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
    global $conn;
    
    $query = "SELECT 
                cpa.*,
                c.character_name,
                c.clan,
                c.id as character_id
              FROM camarilla_position_assignments cpa
              LEFT JOIN characters c ON UPPER(REPLACE(c.character_name, ' ', '_')) = cpa.character_id
              WHERE cpa.position_id = ?
              ORDER BY cpa.start_night DESC";
    
    return db_fetch_all($conn, $query, "s", [$positionId]);
}

/**
 * Get all positions a character has held (past and present)
 * 
 * @param string $characterId Character ID
 * @return array All position assignments for the character ordered by start_night DESC
 */
function get_character_position_history(string $characterId): array {
    global $conn;
    
    $query = "SELECT 
                cpa.*,
                cp.name as position_name,
                cp.category,
                cp.position_id
              FROM camarilla_position_assignments cpa
              LEFT JOIN camarilla_positions cp ON cpa.position_id = cp.position_id
              WHERE cpa.character_id = ?
              ORDER BY cpa.start_night DESC";
    
    // character_id is likely an integer, but using "s" for flexibility
    return db_fetch_all($conn, $query, "s", [$characterId]);
}
