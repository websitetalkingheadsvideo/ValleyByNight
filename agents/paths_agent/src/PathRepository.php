<?php
declare(strict_types=1);

/**
 * PathRepository
 * 
 * Handles database queries for the paths_master table.
 * Provides methods to fetch paths by ID or filtered by type.
 */

class PathRepository
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
     * Fetch a path by ID
     * 
     * @param int $pathId
     * @return array|null Path data or null if not found
     */
    public function getById(int $pathId): ?array
    {
        $query = "SELECT id, name, type, description, source, created_at 
                  FROM paths_master 
                  WHERE id = ?";
        
        $result = db_fetch_one($this->db, $query, 'i', [$pathId]);
        
        if ($result === null) {
            return null;
        }
        
        return $result;
    }
    
    /**
     * List paths with optional type filter
     * 
     * @param string|null $type Filter by path type (e.g., "Necromancy", "Thaumaturgy")
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of path data
     */
    public function listByType(?string $type = null, int $limit = 100, int $offset = 0): array
    {
        $query = "SELECT id, name, type, description, source, created_at 
                  FROM paths_master";
        
        $params = [];
        $types = '';
        $conditions = [];
        
        if ($type !== null) {
            $conditions[] = "type = ?";
            $params[] = $type;
            $types .= 's';
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY type, name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $result = db_select($this->db, $query, $types, $params);
        
        if ($result === false) {
            return [];
        }
        
        $paths = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $paths[] = $row;
        }
        
        return $paths;
    }
}

