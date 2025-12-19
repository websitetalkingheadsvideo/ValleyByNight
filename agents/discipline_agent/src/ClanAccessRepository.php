<?php
declare(strict_types=1);

/**
 * ClanAccessRepository
 * 
 * Handles clan discipline access rules.
 * Determines which disciplines are in-clan vs out-of-clan for each clan.
 */

class ClanAccessRepository
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
     * Cache for clan discipline mappings
     * @var array|null
     */
    protected $clanDisciplinesCache = null;
    
    /**
     * @param mysqli $db
     * @param array $config
     */
    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
        $this->loadClanDisciplines();
    }
    
    /**
     * Load clan discipline mappings from config
     * 
     * @return void
     */
    protected function loadClanDisciplines(): void
    {
        if ($this->clanDisciplinesCache !== null) {
            return;
        }
        
        $this->clanDisciplinesCache = $this->config['clan_disciplines'] ?? [];
    }
    
    /**
     * Get in-clan disciplines for a specific clan
     * 
     * @param string $clanName
     * @return array Array of discipline names
     */
    public function getClanDisciplines(string $clanName): array
    {
        return $this->clanDisciplinesCache[$clanName] ?? [];
    }
    
    /**
     * Check if a discipline is in-clan for a specific clan
     * 
     * @param string $clanName
     * @param string $disciplineName
     * @return bool True if in-clan, false if out-of-clan
     */
    public function isInClanDiscipline(string $clanName, string $disciplineName): bool
    {
        $clanDisciplines = $this->getClanDisciplines($clanName);
        
        // Caitiff has no in-clan disciplines (empty array means all are out-of-clan)
        if ($clanName === 'Caitiff') {
            return false;
        }
        
        return in_array($disciplineName, $clanDisciplines, true);
    }
    
    /**
     * Get a character's clan from the database
     * 
     * @param int $characterId
     * @return string|null Clan name or null if not found
     */
    public function getClanName(int $characterId): ?string
    {
        $query = "SELECT clan FROM characters WHERE id = ?";
        $result = db_fetch_one($this->db, $query, 'i', [$characterId]);
        
        if ($result === null) {
            return null;
        }
        
        return $result['clan'] ?? null;
    }
    
    /**
     * Get all available clans
     * 
     * @return array Array of clan names
     */
    public function getAllClans(): array
    {
        return array_keys($this->clanDisciplinesCache);
    }
}

