<?php
declare(strict_types=1);

/**
 * PathPowersRepository
 * 
 * Handles database queries for the path_powers table.
 * Provides methods to fetch powers by path ID or by power ID.
 */

class PathPowersRepository
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
     * Fetch all powers for a given path ID
     * Results are ordered deterministically by level, then by power_name
     * 
     * @param int $pathId
     * @return array Array of power data, ordered by level, then power_name
     */
    public function getByPathId(int $pathId): array
    {
        $query = "SELECT id, path_id, level, power_name, system_text, challenge_type, challenge_notes 
                  FROM path_powers 
                  WHERE path_id = ? 
                  ORDER BY level ASC, power_name ASC";
        
        $result = db_select($this->db, $query, 'i', [$pathId]);
        
        if ($result === false) {
            return [];
        }
        
        $powers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $powers[] = $row;
        }
        
        return $powers;
    }
    
    /**
     * Fetch a power by ID
     * 
     * @param int $powerId
     * @return array|null Power data or null if not found
     */
    public function getById(int $powerId): ?array
    {
        $query = "SELECT id, path_id, level, power_name, system_text, challenge_type, challenge_notes 
                  FROM path_powers 
                  WHERE id = ?";
        
        $result = db_fetch_one($this->db, $query, 'i', [$powerId]);
        
        if ($result === null) {
            return null;
        }
        
        return $result;
    }
}

