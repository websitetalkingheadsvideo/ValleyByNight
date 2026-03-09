<?php
declare(strict_types=1);

/**
 * RitualsAgent for Valley by Night
 * 
 * Provides access to ritual definitions, character-known rituals, and ritual rules.
 * Combines data from rituals_master, character_rituals, and the Rules database.
 * 
 * Integration notes:
 * - Uses Supabase via `includes/supabase_client.php`. The legacy DB handle is ignored.
 * - Uses the `rituals_master` table for ritual definitions.
 * - Uses the `character_rituals` table for character-known rituals (read-only).
 * - Uses the `rulebooks`/`rulebook_pages` tables for ritual rules.
 * - Designed to be called from an Agent page in the admin panel or via API.
 */

require_once __DIR__ . '/RitualRepository.php';
require_once __DIR__ . '/CharacterRitualsRepository.php';
require_once __DIR__ . '/RulesRepository.php';
require_once __DIR__ . '/RitualRulesAttacher.php';

class RitualsAgent
{
    /**
     * @var mixed
     */
    protected $db;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @var RitualRepository
     */
    protected $ritualRepository;
    
    /**
     * @var CharacterRitualsRepository
     */
    protected $characterRitualsRepository;
    
    /**
     * @var RulesRepository
     */
    protected $rulesRepository;
    
    /**
     * @var RitualRulesAttacher
     */
    protected $rulesAttacher;
    
    /**
     * RitualsAgent constructor.
     * 
     * Loads the Supabase client when no legacy DB handle is passed in.
     * 
     * @param mixed|null $db
     * @param array|null $config
     * @throws Exception
     */
    public function __construct($db = null, array $config = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $this->db = null;
        }
        
        // Load configuration
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->loadConfig();
        }
        
        // Initialize components
        $this->ritualRepository = new RitualRepository($this->db, $this->config);
        $this->characterRitualsRepository = new CharacterRitualsRepository($this->db, $this->config);
        $this->rulesRepository = new RulesRepository($this->db, $this->config);
        $this->rulesAttacher = new RitualRulesAttacher($this->rulesRepository, $this->config);
    }
    
    /**
     * Load configuration from settings.json
     */
    protected function loadConfig(): void
    {
        $configPath = __DIR__ . '/../config/settings.json';
        if (file_exists($configPath)) {
            $configContent = file_get_contents($configPath);
            $this->config = json_decode($configContent, true) ?: [];
        } else {
            $this->config = [];
        }
    }
    
    /**
     * Fetch a ritual by ID
     * 
     * @param int $ritualId
     * @param bool $includeRules
     * @return array|null Ritual data with optional rules attached
     */
    public function getRitualById(int $ritualId, bool $includeRules = true): ?array
    {
        $ritual = $this->ritualRepository->getById($ritualId);
        
        if ($ritual === null) {
            return null;
        }
        
        if ($includeRules) {
            $ritual = $this->rulesAttacher->attachRules($ritual, true, true);
        }
        
        return $ritual;
    }
    
    /**
     * Fetch a ritual by type, level, and name
     * 
     * @param string $type
     * @param int $level
     * @param string $name
     * @param bool $includeRules
     * @return array|null Ritual data with optional rules attached
     */
    public function getRitual(string $type, int $level, string $name, bool $includeRules = true): ?array
    {
        $ritual = $this->ritualRepository->getByTypeLevelName($type, $level, $name);
        
        if ($ritual === null) {
            return null;
        }
        
        if ($includeRules) {
            $ritual = $this->rulesAttacher->attachRules($ritual, true, true);
        }
        
        return $ritual;
    }
    
    /**
     * List rituals with optional filters
     * 
     * @param string|null $type Filter by ritual type (e.g., "Thaumaturgy", "Necromancy")
     * @param int|null $level Filter by ritual level
     * @param bool $includeRules Whether to attach rules (default false for performance)
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of ritual data
     */
    public function listRituals(?string $type = null, ?int $level = null, bool $includeRules = false, int $limit = 100, int $offset = 0): array
    {
        $rituals = $this->ritualRepository->listRituals($type, $level, $limit, $offset);
        
        if ($includeRules) {
            foreach ($rituals as &$ritual) {
                $ritual = $this->rulesAttacher->attachRules($ritual, true, true);
            }
            unset($ritual);
        }
        
        return $rituals;
    }
    
    /**
     * Get rituals known by a character
     * 
     * @param int $characterId
     * @param bool $includeRules
     * @return array Array of ritual data with character-specific notes
     */
    public function getKnownRitualsForCharacter(int $characterId, bool $includeRules = true): array
    {
        $rituals = $this->characterRitualsRepository->getKnownRitualsForCharacter($characterId);
        
        if ($includeRules) {
            foreach ($rituals as &$ritual) {
                $ritual = $this->rulesAttacher->attachRules($ritual, true, true);
            }
            unset($ritual);
        }
        
        return $rituals;
    }
    
    /**
     * Get ritual rules (global and/or tradition-specific)
     * 
     * @param string|null $tradition Tradition name (e.g., "Thaumaturgy", "Necromancy")
     * @return array Rules bundle with 'global' and 'tradition' keys
     */
    public function getRitualRules(?string $tradition = null): array
    {
        $globalRules = $this->rulesRepository->getGlobalRitualRules(
            $this->config['rules']['default_limit'] ?? 10
        );
        
        $traditionRules = [];
        if ($tradition !== null) {
            $traditionRules = $this->rulesRepository->getTraditionRules(
                $tradition,
                $this->config['rules']['default_limit'] ?? 10
            );
        }
        
        return [
            'global' => $globalRules,
            'tradition' => $traditionRules
        ];
    }
}

