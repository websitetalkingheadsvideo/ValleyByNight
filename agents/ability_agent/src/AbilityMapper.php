<?php
declare(strict_types=1);

/**
 * AbilityMapper
 * 
 * Maps source abilities to canonical format using aliases and deprecations.
 * Handles alias resolution, deprecated ability replacement, and category derivation.
 */

require_once __DIR__ . '/AbilityRepository.php';
require_once __DIR__ . '/AbilityValidator.php';

class AbilityMapper
{
    /**
     * @var AbilityRepository
     */
    protected $repository;
    
    /**
     * @var AbilityValidator
     */
    protected $validator;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @param AbilityRepository $repository
     * @param AbilityValidator $validator
     * @param array $config
     */
    public function __construct(AbilityRepository $repository, AbilityValidator $validator, array $config = [])
    {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->config = $config;
    }
    
    /**
     * Map a source ability to canonical format
     * 
     * @param array $sourceAbility {name: string, category?: string, level?: int, specialization?: string}
     * @return array {canonicalAbility: array|null, issues: array}
     */
    public function map(array $sourceAbility): array
    {
        $issues = [];
        $workingAbility = $sourceAbility;
        
        // Step 1: Check for alias
        $aliased = $this->resolveAlias($workingAbility['name'] ?? '');
        if ($aliased !== null && $aliased !== $workingAbility['name']) {
            $issues[] = $this->createIssue(
                'ALIAS_MAPPED',
                'info',
                "Alias '{$workingAbility['name']}' mapped to canonical name '{$aliased}'",
                [
                    'source_name' => $workingAbility['name'],
                    'canonical_name' => $aliased,
                    'confidence' => 1.0
                ]
            );
            $workingAbility['name'] = $aliased;
        }
        
        // Step 2: Check for deprecation
        $deprecation = $this->checkDeprecation($workingAbility['name'] ?? '');
        if ($deprecation !== null) {
            $autoReplace = $this->config['validation']['auto_replace_deprecated'] ?? false;
            $replacement = $deprecation['replacement'];
            
            if ($autoReplace) {
                $issues[] = $this->createIssue(
                    'DEPRECATED_ABILITY',
                    'warning',
                    "Deprecated ability '{$workingAbility['name']}' automatically replaced with '{$replacement}': {$deprecation['reason']}",
                    [
                        'source_name' => $workingAbility['name'],
                        'canonical_name' => $replacement,
                        'replacement' => $replacement,
                        'reason' => $deprecation['reason'],
                        'confidence' => 1.0
                    ]
                );
                $workingAbility['name'] = $replacement;
                
                // Update category if provided in deprecation
                if (isset($deprecation['category'])) {
                    $workingAbility['category'] = $deprecation['category'];
                }
            } else {
                $issues[] = $this->createIssue(
                    'DEPRECATED_ABILITY',
                    'warning',
                    "Deprecated ability '{$workingAbility['name']}' should be replaced with '{$replacement}': {$deprecation['reason']}",
                    [
                        'source_name' => $workingAbility['name'],
                        'canonical_name' => $workingAbility['name'],
                        'replacement' => $replacement,
                        'reason' => $deprecation['reason'],
                        'confidence' => 1.0
                    ]
                );
            }
        }
        
        // Step 3: Derive category if missing
        if (empty($workingAbility['category'])) {
            $derivedCategory = $this->deriveCategory($workingAbility['name'] ?? '');
            if ($derivedCategory !== null) {
                $workingAbility['category'] = $derivedCategory;
                $issues[] = $this->createIssue(
                    'CATEGORY_DERIVED',
                    'info',
                    "Category derived from canonical definition: '{$derivedCategory}'",
                    [
                        'source_name' => $workingAbility['name'],
                        'canonical_category' => $derivedCategory,
                        'confidence' => 1.0
                    ]
                );
            }
        }
        
        // Step 4: Validate the mapped ability
        $validationResult = $this->validator->validate($workingAbility);
        $issues = array_merge($issues, $validationResult['issues']);
        
        // If validation failed and strict mode, return null
        if (!$validationResult['isValid'] && ($this->config['validation']['strict_mode'] ?? false)) {
            return [
                'canonicalAbility' => null,
                'issues' => $issues
            ];
        }
        
        // Return canonical ability (use normalized from validation)
        return [
            'canonicalAbility' => $validationResult['normalizedAbility'],
            'issues' => $issues
        ];
    }
    
    /**
     * Resolve an alias to canonical name
     * 
     * @param string $name
     * @return string|null Canonical name or null if no alias
     */
    protected function resolveAlias(string $name): ?string
    {
        $aliases = $this->config['aliases'] ?? [];
        $normalizedName = strtolower(trim($name));
        
        // Check direct alias mapping
        if (isset($aliases[$name])) {
            return $aliases[$name];
        }
        
        // Check case-insensitive
        foreach ($aliases as $alias => $canonical) {
            if (strtolower(trim($alias)) === $normalizedName) {
                return $canonical;
            }
        }
        
        return null;
    }
    
    /**
     * Check if an ability is deprecated
     * 
     * @param string $name
     * @return array|null Deprecation info or null if not deprecated
     */
    protected function checkDeprecation(string $name): ?array
    {
        $deprecations = $this->config['deprecations'] ?? [];
        $normalizedName = strtolower(trim($name));
        
        // Check direct deprecation entry
        if (isset($deprecations[$name])) {
            return $deprecations[$name];
        }
        
        // Check case-insensitive
        foreach ($deprecations as $deprecated => $info) {
            if (strtolower(trim($deprecated)) === $normalizedName) {
                return $info;
            }
        }
        
        return null;
    }
    
    /**
     * Derive category from canonical definition
     * 
     * @param string $name
     * @return string|null Category or null if not found
     */
    protected function deriveCategory(string $name): ?string
    {
        return $this->repository->getCategoryForAbility($name);
    }
    
    /**
     * Create an issue array
     * 
     * @param string $code
     * @param string $severity
     * @param string $message
     * @param array $metadata
     * @return array
     */
    protected function createIssue(string $code, string $severity, string $message, array $metadata = []): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'metadata' => $metadata
        ];
    }
}

