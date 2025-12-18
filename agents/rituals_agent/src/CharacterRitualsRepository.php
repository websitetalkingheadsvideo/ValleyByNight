<?php
declare(strict_types=1);

/**
 * CharacterRitualsRepository
 * 
 * Handles read-only database queries for character-known rituals.
 * Joins character_rituals with rituals_master to return full ritual definitions.
 */

class CharacterRitualsRepository
{
    /**
     * @var mysqli
     */
    protected $db;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @param mysqli $db
     * @param array $config
     */
    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Get all rituals known by a character
     * 
     * Supports both ritual_id FK lookup (preferred) and fallback to 
     * (ritual_name, ritual_type, level) matching for rows without FK.
     * 
     * @param int $characterId
     * @return array Array of ritual data with character-specific fields (notes, is_custom)
     */
    public function getKnownRitualsForCharacter(int $characterId): array
    {
        // Prefer FK join when ritual_id is available, fallback to legacy matching
        $query = "SELECT rm.id, rm.name, rm.type, rm.level, rm.description, rm.system_text, 
                         rm.requirements, rm.ingredients, rm.source, rm.created_at,
                         cr.ritual_name, cr.ritual_type, cr.level AS character_level,
                         cr.is_custom, cr.description AS character_notes,
                         cr.ritual_id
                  FROM character_rituals cr
                  LEFT JOIN rituals_master rm ON (
                      cr.ritual_id = rm.id 
                      OR (cr.ritual_id IS NULL AND rm.name = cr.ritual_name 
                          AND rm.type = cr.ritual_type 
                          AND rm.level = cr.level)
                  )
                  WHERE cr.character_id = ?
                  ORDER BY rm.type, rm.level, rm.name";
        
        $result = db_select($this->db, $query, 'i', [$characterId]);
        
        if ($result === false) {
            return [];
        }
        
        $rituals = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // If no match found in rituals_master, create a minimal ritual object
            if ($row['id'] === null) {
                $rituals[] = [
                    'id' => null,
                    'name' => $row['ritual_name'] ?? '',
                    'type' => $row['ritual_type'] ?? '',
                    'level' => $row['character_level'] ?? null,
                    'description' => $row['character_notes'] ?? '',
                    'system_text' => null,
                    'requirements' => null,
                    'ingredients' => null,
                    'source' => null,
                    'created_at' => null,
                    'is_custom' => (bool)($row['is_custom'] ?? false)
                ];
            } else {
                // Full ritual match - add character-specific fields
                $ritual = $row;
                $ritual['is_custom'] = (bool)($row['is_custom'] ?? false);
                unset($ritual['character_notes']); // Remove duplicate description field
                $rituals[] = $ritual;
            }
        }
        
        return $rituals;
    }
}

