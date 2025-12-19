<?php
declare(strict_types=1);

/**
 * DisciplinePowersRepository
 * 
 * Handles database queries for the character_discipline_powers table.
 * CRITICAL: Only queries character_discipline_powers table.
 * Does NOT handle paths or rituals - those are separate systems.
 */

class DisciplinePowersRepository
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
     * Get all discipline powers for a character
     * Optionally filtered by discipline name
     * 
     * @param int $characterId
     * @param string|null $disciplineName Optional filter by discipline
     * @return array Array of power data {discipline_name, power_name, level}
     */
    public function getCharacterDisciplinePowers(int $characterId, ?string $disciplineName = null): array
    {
        if ($disciplineName !== null) {
            $query = "SELECT discipline_name, power_name, level 
                      FROM character_discipline_powers 
                      WHERE character_id = ? AND discipline_name = ?
                      ORDER BY level, power_name";
            $results = db_fetch_all($this->db, $query, 'is', [$characterId, $disciplineName]);
        } else {
            $query = "SELECT discipline_name, power_name, level 
                      FROM character_discipline_powers 
                      WHERE character_id = ?
                      ORDER BY discipline_name, level, power_name";
            $results = db_fetch_all($this->db, $query, 'i', [$characterId]);
        }
        
        return $results;
    }
    
    /**
     * Get powers for a specific discipline at a specific level
     * Used to determine which powers are eligible given discipline dots
     * 
     * @param string $disciplineName
     * @param int $level Discipline level (1-5)
     * @return array Array of power data {power_name, level}
     */
    public function getPowersByDisciplineAndLevel(string $disciplineName, int $level): array
    {
        $query = "SELECT DISTINCT power_name, level 
                  FROM character_discipline_powers 
                  WHERE discipline_name = ? AND level <= ?
                  ORDER BY level, power_name";
        
        return db_fetch_all($this->db, $query, 'si', [$disciplineName, $level]);
    }
    
    /**
     * Check if a power exists for a discipline
     * 
     * @param string $disciplineName
     * @param string $powerName
     * @return bool
     */
    public function powerExists(string $disciplineName, string $powerName): bool
    {
        $query = "SELECT 1 
                  FROM character_discipline_powers 
                  WHERE discipline_name = ? AND power_name = ?
                  LIMIT 1";
        
        $result = db_fetch_one($this->db, $query, 'ss', [$disciplineName, $powerName]);
        
        return $result !== null;
    }
    
    /**
     * Get the level requirement for a specific power
     * 
     * @param string $disciplineName
     * @param string $powerName
     * @return int|null Power level requirement (1-5) or null if not found
     */
    public function getPowerLevel(string $disciplineName, string $powerName): ?int
    {
        $query = "SELECT level 
                  FROM character_discipline_powers 
                  WHERE discipline_name = ? AND power_name = ?
                  LIMIT 1";
        
        $result = db_fetch_one($this->db, $query, 'ss', [$disciplineName, $powerName]);
        
        if ($result === null) {
            return null;
        }
        
        return (int)$result['level'];
    }
}

