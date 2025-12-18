<?php
declare(strict_types=1);

/**
 * RitualRepository
 * 
 * Handles database queries for the rituals_master table.
 * Provides methods to fetch rituals by ID, composite key, or filtered listing.
 */

class RitualRepository
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
     * Fetch a ritual by ID
     * 
     * @param int $ritualId
     * @return array|null Ritual data or null if not found
     */
    public function getById(int $ritualId): ?array
    {
        $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source, created_at 
                  FROM rituals_master 
                  WHERE id = ?";
        
        $result = db_fetch_one($this->db, $query, 'i', [$ritualId]);
        
        if ($result === null) {
            return null;
        }
        
        return $result;
    }
    
    /**
     * Fetch a ritual by type, level, and name
     * 
     * @param string $type
     * @param int $level
     * @param string $name
     * @return array|null Ritual data or null if not found
     */
    public function getByTypeLevelName(string $type, int $level, string $name): ?array
    {
        $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source, created_at 
                  FROM rituals_master 
                  WHERE type = ? AND level = ? AND name = ?";
        
        $result = db_fetch_one($this->db, $query, 'sis', [$type, $level, $name]);
        
        if ($result === null) {
            return null;
        }
        
        return $result;
    }
    
    /**
     * List rituals with optional filters
     * 
     * @param string|null $type Filter by ritual type
     * @param int|null $level Filter by ritual level
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of ritual data
     */
    public function listRituals(?string $type = null, ?int $level = null, int $limit = 100, int $offset = 0): array
    {
        $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source, created_at 
                  FROM rituals_master";
        
        $params = [];
        $types = '';
        $conditions = [];
        
        if ($type !== null) {
            $conditions[] = "type = ?";
            $params[] = $type;
            $types .= 's';
        }
        
        if ($level !== null) {
            $conditions[] = "level = ?";
            $params[] = $level;
            $types .= 'i';
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY type, level, name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $result = db_select($this->db, $query, $types, $params);
        
        if ($result === false) {
            return [];
        }
        
        $rituals = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rituals[] = $row;
        }
        
        return $rituals;
    }
}

