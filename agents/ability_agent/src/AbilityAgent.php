<?php
declare(strict_types=1);

/**
 * AbilityAgent for Valley by Night
 * 
 * Validates and maps ability data from external/source formats into the project's
 * canonical ability schema. Provides validation, alias resolution, deprecation handling,
 * and integration with the character import workflow.
 * 
 * Integration notes:
 * - Uses Supabase via `includes/supabase_client.php`. The legacy DB handle is ignored.
 * - Uses the `abilities` table for canonical ability definitions.
 * - Designed to be called from character import workflow or via API.
 */

require_once __DIR__ . '/AbilityRepository.php';
require_once __DIR__ . '/AbilityValidator.php';
require_once __DIR__ . '/AbilityMapper.php';

class AbilityAgent
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
     * @var AbilityRepository
     */
    protected $repository;
    
    /**
     * @var AbilityValidator
     */
    protected $validator;
    
    /**
     * @var AbilityMapper
     */
    protected $mapper;
    
    /**
     * AbilityAgent constructor.
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
        $this->repository = new AbilityRepository($this->db, $this->config);
        $this->validator = new AbilityValidator($this->repository, $this->config);
        $this->mapper = new AbilityMapper($this->repository, $this->validator, $this->config);
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
                'strict_mode' => false,
                'allow_unknown' => true,
                'auto_replace_deprecated' => false,
                'fuzzy_threshold' => 0.8
            ],
            'aliases' => [],
            'deprecations' => [],
            'normalization' => [
                'case_sensitive' => false,
                'trim_whitespace' => true,
                'fuzzy_matching' => true
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
     * Validate a source ability
     * 
     * @param array $sourceAbility {name: string, category?: string, level?: int, specialization?: string}
     * @return array {isValid: bool, normalizedAbility: array, issues: array}
     */
    public function validate(array $sourceAbility): array
    {
        return $this->validator->validate($sourceAbility);
    }
    
    /**
     * Map a source ability to canonical format
     * 
     * @param array $sourceAbility {name: string, category?: string, level?: int, specialization?: string}
     * @return array {canonicalAbility: array|null, issues: array}
     */
    public function map(array $sourceAbility): array
    {
        return $this->mapper->map($sourceAbility);
    }
    
    /**
     * Process a list of source abilities
     * 
     * @param array $sourceAbilities Array of source ability objects
     * @return array {mappedAbilities: array, allIssues: array, summary: array}
     */
    public function processAbilities(array $sourceAbilities): array
    {
        $mappedAbilities = [];
        $allIssues = [];
        $summary = [
            'total' => count($sourceAbilities),
            'valid' => 0,
            'invalid' => 0,
            'deprecated' => 0,
            'mapped' => 0,
            'unknown' => 0
        ];
        
        foreach ($sourceAbilities as $index => $sourceAbility) {
            $mappingResult = $this->map($sourceAbility);
            $issues = $mappingResult['issues'];
            
            // Count issue types
            foreach ($issues as $issue) {
                if ($issue['code'] === 'DEPRECATED_ABILITY') {
                    $summary['deprecated']++;
                } elseif ($issue['code'] === 'UNKNOWN_ABILITY') {
                    $summary['unknown']++;
                }
                
                // Add source reference to issue
                $issue['source_index'] = $index;
                $issue['source_ability'] = $sourceAbility;
                $allIssues[] = $issue;
            }
            
            if ($mappingResult['canonicalAbility'] !== null) {
                $mappedAbilities[] = $mappingResult['canonicalAbility'];
                $summary['mapped']++;
                $summary['valid']++;
            } else {
                $summary['invalid']++;
            }
        }
        
        return [
            'mappedAbilities' => $mappedAbilities,
            'allIssues' => $allIssues,
            'summary' => $summary
        ];
    }
    
    /**
     * Get all canonical abilities, optionally filtered by category
     * 
     * @param string|null $category
     * @return array Array of canonical ability data
     */
    public function getCanonicalAbilities(?string $category = null): array
    {
        return $this->repository->getAllCanonicalAbilities($category);
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
            error_log("[AbilityAgent {$level}] {$message}");
        }
    }
}

