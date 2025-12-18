<?php
declare(strict_types=1);

/**
 * CharacterPathsRepository
 * 
 * Handles database queries for the character_paths table.
 * Provides methods to fetch character path ratings.
 */

class CharacterPathsRepository
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
     * Fetch all paths known by a character with their ratings
     * Joins with paths_master to include path details
     * 
     * @param int $characterId
     * @return array Array of path data with ratings, each containing:
     *               - path_id
     *               - path_name
     *               - path_type
     *               - rating
     *               - notes
     *               - is_primary
     */
    public function getByCharacterId(int $characterId): array
    {
        $query = "SELECT cp.character_id, cp.path_id, cp.rating, cp.notes, cp.is_primary,
                         pm.id, pm.name as path_name, pm.type as path_type, pm.description, pm.source
                  FROM character_paths cp
                  INNER JOIN paths_master pm ON cp.path_id = pm.id
                  WHERE cp.character_id = ?
                  ORDER BY cp.is_primary DESC, pm.type, pm.name";
        
        $result = db_select($this->db, $query, 'i', [$characterId]);
        
        if ($result === false) {
            return [];
        }
        
        $characterPaths = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $characterPaths[] = [
                'character_id' => (int)$row['character_id'],
                'path_id' => (int)$row['path_id'],
                'path_name' => $row['path_name'],
                'path_type' => $row['path_type'],
                'rating' => (int)$row['rating'],
                'notes' => $row['notes'],
                'is_primary' => (int)$row['is_primary'],
                'description' => $row['description'],
                'source' => $row['source']
            ];
        }
        
        return $characterPaths;
    }
    
    /**
     * Get a character's rating for a specific path
     * 
     * @param int $characterId
     * @param int $pathId
     * @return int|null Rating or null if character doesn't know the path
     */
    public function getRatingForPath(int $characterId, int $pathId): ?int
    {
        $query = "SELECT rating 
                  FROM character_paths 
                  WHERE character_id = ? AND path_id = ?";
        
        $result = db_fetch_one($this->db, $query, 'ii', [$characterId, $pathId]);
        
        if ($result === null) {
            return null;
        }
        
        return (int)$result['rating'];
    }
}

