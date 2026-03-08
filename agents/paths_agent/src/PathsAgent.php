<?php
declare(strict_types=1);

/**
 * PathsAgent for Valley by Night
 * 
 * Provides access to path definitions, path powers, and character path ratings.
 * Reads only from paths_master, path_powers, and character_paths tables.
 * 
 * Integration notes:
 * - Expects a MySQL connection `$conn` created in connect.php (mysqli object).
 * - Uses the `paths_master` table for path definitions.
 * - Uses the `path_powers` table for path powers.
 * - Uses the `character_paths` table for character path ratings (read-only).
 * - Designed to be called from an Agent page in the admin panel or via API.
 */

require_once __DIR__ . '/PathRepository.php';
require_once __DIR__ . '/PathPowersRepository.php';
require_once __DIR__ . '/CharacterPathsRepository.php';

class PathsAgent
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
     * @var PathRepository
     */
    protected $pathRepository;
    
    /**
     * @var PathPowersRepository
     */
    protected $pathPowersRepository;
    
    /**
     * @var CharacterPathsRepository
     */
    protected $characterPathsRepository;
    
    /**
     * PathsAgent constructor.
     * DB is legacy (ignored). Repositories use Supabase.
     *
     * @param mixed|null $db Ignored; kept for API compatibility
     * @param array|null $config
     */
    public function __construct($db = null, array $config = null)
    {
        $this->db = $db;
        
        // Load configuration
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->loadConfig();
        }
        
        // Initialize components
        $this->pathRepository = new PathRepository($this->db, $this->config);
        $this->pathPowersRepository = new PathPowersRepository($this->db, $this->config);
        $this->characterPathsRepository = new CharacterPathsRepository($this->db, $this->config);
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
     * Generate challenge metadata for responses
     * 
     * @param array $sourcesRead Subset of allowed sources that were used
     * @param array $additionalData Additional metadata fields (e.g., requiredRating, characterRating)
     * @return array Challenge metadata object
     */
    protected function generateChallengeMetadata(array $sourcesRead, array $additionalData = []): array
    {
        $metadata = [
            'challenge' => [
                'code' => 'TM-03',
                'name' => 'Paths Agent: Core Implementation',
                'dependsOn' => ['TM-02']
            ],
            'sourcesRead' => $sourcesRead,
            'gating' => [
                'type' => 'rating-only',
                'ritualLogicIncluded' => false
            ]
        ];
        
        // Merge additional data (e.g., for canUsePathPower)
        if (!empty($additionalData)) {
            $metadata = array_merge($metadata, $additionalData);
        }
        
        return $metadata;
    }
    
    /**
     * List paths with optional type filter
     * 
     * @param string|null $type Filter by path type (e.g., "Necromancy", "Thaumaturgy")
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Response with paths and challenge metadata
     */
    public function listPathsByType(?string $type = null, int $limit = 100, int $offset = 0): array
    {
        $paths = $this->pathRepository->listByType($type, $limit, $offset);
        
        $metadata = $this->generateChallengeMetadata(['paths_master']);
        
        return [
            'paths' => $paths,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Get all powers for a given path
     * 
     * @param int $pathId
     * @return array Response with powers and challenge metadata
     */
    public function getPathPowers(int $pathId): array
    {
        $powers = $this->pathPowersRepository->getByPathId($pathId);
        
        $metadata = $this->generateChallengeMetadata(['paths_master', 'path_powers']);
        
        return [
            'powers' => $powers,
            'path_id' => $pathId,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Get all paths known by a character with their ratings
     * 
     * @param int $characterId
     * @return array Response with character paths and challenge metadata
     */
    public function getCharacterPaths(int $characterId): array
    {
        $characterPaths = $this->characterPathsRepository->getByCharacterId($characterId);
        
        $metadata = $this->generateChallengeMetadata(['paths_master', 'character_paths']);
        
        return [
            'character_id' => $characterId,
            'paths' => $characterPaths,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Evaluate if a character may use a given path power (rating gate only)
     * 
     * @param int $characterId
     * @param int $powerId
     * @return array Structured response with decision, reasoning, and challenge metadata
     */
    public function canUsePathPower(int $characterId, int $powerId): array
    {
        // Get power details (includes path_id and level)
        $power = $this->pathPowersRepository->getById($powerId);
        
        if ($power === null) {
            $metadata = $this->generateChallengeMetadata(['path_powers'], [
                'powerId' => $powerId,
                'requiredRating' => null,
                'characterRating' => null,
                'pathId' => null
            ]);
            
            return [
                'canUse' => false,
                'reasoning' => "Power with ID {$powerId} not found",
                'requiredRating' => null,
                'characterRating' => null,
                'powerId' => $powerId,
                'pathId' => null,
                'metadata' => $metadata
            ];
        }
        
        $pathId = (int)$power['path_id'];
        $requiredRating = (int)$power['level'];
        
        // Get character's rating for this path
        $characterRating = $this->characterPathsRepository->getRatingForPath($characterId, $pathId);
        
        // Rating gate: character rating must be >= power level
        $canUse = ($characterRating !== null && $characterRating >= $requiredRating);
        
        if ($canUse) {
            $reasoning = "Character has path rating {$characterRating}, which meets or exceeds required rating {$requiredRating}";
        } elseif ($characterRating === null) {
            $reasoning = "Character does not know this path (no rating found)";
        } else {
            $reasoning = "Character has path rating {$characterRating}, which is below required rating {$requiredRating}";
        }
        
        $metadata = $this->generateChallengeMetadata(
            ['path_powers', 'character_paths'],
            [
                'requiredRating' => $requiredRating,
                'characterRating' => $characterRating,
                'powerId' => $powerId,
                'pathId' => $pathId
            ]
        );
        
        return [
            'canUse' => $canUse,
            'reasoning' => $reasoning,
            'requiredRating' => $requiredRating,
            'characterRating' => $characterRating,
            'powerId' => $powerId,
            'pathId' => $pathId,
            'metadata' => $metadata
        ];
    }
}

