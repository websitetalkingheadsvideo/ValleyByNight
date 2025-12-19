<?php
declare(strict_types=1);

/**
 * DisciplineRepository
 * 
 * Handles database queries for the character_disciplines table.
 * CRITICAL: This repository ONLY handles innate disciplines.
 * Paths and rituals are EXCLUDED - they are handled by PathsAgent and RitualsAgent.
 * 
 * Exclusion strategy:
 * - Only queries character_disciplines table
 * - Filters out any discipline names matching path patterns
 * - Cross-checks against character_paths and character_rituals to ensure exclusion
 */

class DisciplineRepository
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
     * Get all innate disciplines for a character
     * EXCLUDES paths and rituals
     * 
     * @param int $characterId
     * @return array Array of discipline data {discipline_name, level}
     */
    public function getCharacterDisciplines(int $characterId): array
    {
        // Get all disciplines from character_disciplines table
        $query = "SELECT discipline_name, level 
                  FROM character_disciplines 
                  WHERE character_id = ?";
        
        $disciplines = db_fetch_all($this->db, $query, 'i', [$characterId]);
        
        // Filter out paths and rituals
        $filtered = $this->filterOutPathsAndRituals($disciplines);
        
        return $filtered;
    }
    
    /**
     * Get a specific discipline level for a character
     * 
     * @param int $characterId
     * @param string $disciplineName
     * @return int|null Discipline level (0-5) or null if not found
     */
    public function getDisciplineLevel(int $characterId, string $disciplineName): ?int
    {
        // First check if this is a path/ritual - if so, return null
        if (!$this->isInnateDiscipline($disciplineName)) {
            return null;
        }
        
        $query = "SELECT level 
                  FROM character_disciplines 
                  WHERE character_id = ? AND discipline_name = ?";
        
        $result = db_fetch_one($this->db, $query, 'is', [$characterId, $disciplineName]);
        
        if ($result === null) {
            return null;
        }
        
        return (int)$result['level'];
    }
    
    /**
     * Check if a discipline name represents an innate discipline (not a path/ritual)
     * 
     * @param string $disciplineName
     * @return bool True if innate discipline, false if path/ritual
     */
    public function isInnateDiscipline(string $disciplineName): bool
    {
        // Check exclusion patterns from config
        $exclusionPatterns = $this->config['exclusion_patterns'] ?? [];
        $pathPrefixes = $exclusionPatterns['path_prefixes'] ?? ['Path of'];
        
        // Check if name starts with any path prefix
        foreach ($pathPrefixes as $prefix) {
            if (stripos($disciplineName, $prefix) === 0) {
                return false;
            }
        }
        
        // Check if it exists in character_paths table
        $pathCheck = db_fetch_one(
            $this->db,
            "SELECT 1 FROM character_paths WHERE path_name = ? LIMIT 1",
            's',
            [$disciplineName]
        );
        if ($pathCheck !== null) {
            return false;
        }
        
        // Check if it exists in character_rituals table (as ritual type)
        $ritualCheck = db_fetch_one(
            $this->db,
            "SELECT 1 FROM character_rituals WHERE ritual_type = ? LIMIT 1",
            's',
            [$disciplineName]
        );
        if ($ritualCheck !== null) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Filter out paths and rituals from discipline list
     * 
     * @param array $disciplines
     * @return array Filtered disciplines (only innate)
     */
    protected function filterOutPathsAndRituals(array $disciplines): array
    {
        $filtered = [];
        
        foreach ($disciplines as $discipline) {
            $name = $discipline['discipline_name'] ?? '';
            
            if ($this->isInnateDiscipline($name)) {
                $filtered[] = $discipline;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get all unique discipline names from character_disciplines (for validation)
     * EXCLUDES paths and rituals
     * 
     * @return array Array of unique discipline names
     */
    public function getAllDisciplineNames(): array
    {
        $query = "SELECT DISTINCT discipline_name FROM character_disciplines";
        $results = db_fetch_all($this->db, $query);
        
        $names = [];
        foreach ($results as $row) {
            $name = $row['discipline_name'] ?? '';
            if ($this->isInnateDiscipline($name)) {
                $names[] = $name;
            }
        }
        
        return array_unique($names);
    }
}

