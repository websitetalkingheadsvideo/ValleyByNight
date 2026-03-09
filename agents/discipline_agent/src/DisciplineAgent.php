<?php
declare(strict_types=1);

/**
 * DisciplineAgent for Valley by Night
 * 
 * Validates and lists character disciplines (innate disciplines only).
 * 
 * CRITICAL: This agent does NOT handle paths or rituals.
 * - Paths are handled by PathsAgent
 * - Rituals are handled by RitualsAgent
 * - This agent ONLY handles innate disciplines from character_disciplines table
 * 
 * Provides:
 * - Discipline listing (innate only, excludes paths/rituals)
 * - Dot range validation (0-5)
 * - Clan access rule validation
 * - Power eligibility validation
 * 
 * Integration notes:
 * - Uses Supabase (includes/supabase_client.php). $db param ignored.
 * - Uses character_disciplines, disciplies_powers, characters.
 * - Designed to be called from character creation/update workflows or via API.
 */

require_once __DIR__ . '/DisciplineRepository.php';
require_once __DIR__ . '/DisciplinePowersRepository.php';
require_once __DIR__ . '/ClanAccessRepository.php';
require_once __DIR__ . '/DisciplineValidator.php';

class DisciplineAgent
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @var DisciplineRepository
     */
    protected $disciplineRepository;
    
    /**
     * @var DisciplinePowersRepository
     */
    protected $powersRepository;
    
    /**
     * @var ClanAccessRepository
     */
    protected $clanRepository;
    
    /**
     * @var DisciplineValidator
     */
    protected $validator;
    
    /**
     * DisciplineAgent constructor.
     * 
     * Loads the Supabase client when no legacy DB handle is passed in.
     * 
     * @param mixed|null $db
     * @param array|null $config
     * @throws Exception
     */
    public function __construct($db = null, array $config = null)
    {
        $this->db = null;
        require_once __DIR__ . '/../../../includes/supabase_client.php';
        
        // Load configuration
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->loadConfig();
        }
        
        // Initialize components
        $this->disciplineRepository = new DisciplineRepository($this->db, $this->config);
        $this->powersRepository = new DisciplinePowersRepository($this->db, $this->config);
        $this->clanRepository = new ClanAccessRepository($this->db, $this->config);
        $this->validator = new DisciplineValidator(
            $this->disciplineRepository,
            $this->powersRepository,
            $this->clanRepository,
            $this->config
        );
    }
    
    /**
     * Load configuration from settings.json
     * 
     * @return void
     */
    protected function loadConfig(): void
    {
        $configPath = __DIR__ . '/../config/settings.json';
        $defaultConfig = [
            'enabled' => true,
            'validation' => [
                'min_dots' => 0,
                'max_dots' => 5,
                'strict_mode' => false,
                'allow_out_of_clan' => true
            ],
            'clan_disciplines' => [],
            'exclusion_patterns' => [
                'path_prefixes' => ['Path of'],
                'exclude_tables' => ['character_paths', 'character_rituals']
            ],
            'logging' => [
                'enabled' => true,
                'level' => 'info'
            ]
        ];
        
        if (!file_exists($configPath)) {
            $this->log('warning', "Config file not found: {$configPath}, using defaults");
            $this->config = $defaultConfig;
            return;
        }
        
        $jsonContent = file_get_contents($configPath);
        if ($jsonContent === false) {
            $this->log('warning', "Failed to read config file: {$configPath}, using defaults");
            $this->config = $defaultConfig;
            return;
        }
        
        $config = json_decode($jsonContent, true);
        if ($config === null) {
            $this->log('warning', "Invalid JSON in config file: {$configPath}, using defaults");
            $this->config = $defaultConfig;
            return;
        }
        
        // Merge with defaults
        $this->config = array_merge($defaultConfig, $config);
    }
    
    /**
     * List a character's innate disciplines (excludes paths/rituals)
     * 
     * @param int $characterId
     * @return array {disciplines: array, summary: array}
     */
    public function listCharacterDisciplines(int $characterId): array
    {
        $disciplines = $this->disciplineRepository->getCharacterDisciplines($characterId);
        
        // Get powers for each discipline
        $disciplinesWithPowers = [];
        foreach ($disciplines as $discipline) {
            $disciplineName = $discipline['discipline_name'];
            $level = (int)$discipline['level'];
            
            $powers = $this->powersRepository->getCharacterDisciplinePowers($characterId, $disciplineName);
            
            $disciplinesWithPowers[] = [
                'discipline_name' => $disciplineName,
                'level' => $level,
                'powers' => $powers,
                'power_count' => count($powers)
            ];
        }
        
        $summary = [
            'total_disciplines' => count($disciplinesWithPowers),
            'total_powers' => array_sum(array_column($disciplinesWithPowers, 'power_count')),
            'character_id' => $characterId
        ];
        
        return [
            'disciplines' => $disciplinesWithPowers,
            'summary' => $summary
        ];
    }
    
    /**
     * Validate discipline dot ranges and constraints
     * 
     * @param int $characterId
     * @param array $updates {discipline_name: level, ...}
     * @return array {isValid: bool, errors: array, warnings: array}
     */
    public function validateDisciplineDots(int $characterId, array $updates): array
    {
        return $this->validator->validateDisciplineDots($updates);
    }
    
    /**
     * Validate clan access rules for a discipline
     * 
     * @param int $characterId
     * @param string $disciplineName
     * @return array {hasAccess: bool, isInClan: bool, restrictions: array}
     */
    public function validateClanDisciplineAccess(int $characterId, string $disciplineName): array
    {
        return $this->validator->validateClanDisciplineAccess($characterId, $disciplineName);
    }
    
    /**
     * Validate discipline power eligibility
     * 
     * @param int $characterId
     * @param array $requestedPowers {discipline_name: [power_names], ...}
     * @return array {isValid: bool, errors: array, eligiblePowers: array}
     */
    public function validateDisciplinePowers(int $characterId, array $requestedPowers): array
    {
        return $this->validator->validateDisciplinePowers($characterId, $requestedPowers);
    }
    
    /**
     * Log a message
     * 
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message): void
    {
        if (!($this->config['logging']['enabled'] ?? true)) {
            return;
        }
        
        $configLevel = $this->config['logging']['level'] ?? 'info';
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        if (($levels[$level] ?? 999) >= ($levels[$configLevel] ?? 1)) {
            error_log("[DisciplineAgent {$level}] {$message}");
        }
    }
}

