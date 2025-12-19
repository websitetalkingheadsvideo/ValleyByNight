<?php
declare(strict_types=1);

/**
 * AbilityValidator
 * 
 * Validates ability names and categories against canonical definitions.
 * Handles normalization, exact matching, and fuzzy matching.
 */

require_once __DIR__ . '/AbilityRepository.php';

class AbilityValidator
{
    /**
     * @var AbilityRepository
     */
    protected $repository;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @param AbilityRepository $repository
     * @param array $config
     */
    public function __construct(AbilityRepository $repository, array $config = [])
    {
        $this->repository = $repository;
        $this->config = $config;
    }
    
    /**
     * Validate a source ability
     * 
     * @param array $sourceAbility {name: string, category?: string, level?: int, specialization?: string}
     * @return array {isValid: bool, normalizedAbility: array, issues: array}
     */
    public function validate(array $sourceAbility): array
    {
        $issues = [];
        $normalized = [
            'name' => '',
            'category' => null,
            'level' => (int)($sourceAbility['level'] ?? 1),
            'specialization' => $sourceAbility['specialization'] ?? null
        ];
        
        // Extract and normalize name
        $sourceName = $sourceAbility['name'] ?? '';
        if (empty($sourceName)) {
            $issues[] = $this->createIssue('MISSING_NAME', 'error', 'Ability name is required');
            return [
                'isValid' => false,
                'normalizedAbility' => $normalized,
                'issues' => $issues
            ];
        }
        
        $normalizedName = $this->normalizeName($sourceName);
        $sourceCategory = isset($sourceAbility['category']) ? $this->normalizeCategory($sourceAbility['category']) : null;
        
        // Try exact match first
        $canonical = $this->repository->getCanonicalAbility($normalizedName, $sourceCategory);
        
        if ($canonical !== null) {
            // Exact match found
            $normalized['name'] = $canonical['name'];
            $normalized['category'] = $canonical['category'];
            
            // Validate category match if provided
            if ($sourceCategory !== null && $canonical['category'] !== $sourceCategory) {
                $issues[] = $this->createIssue(
                    'CATEGORY_MISMATCH',
                    'warning',
                    "Category mismatch: ability '{$sourceName}' is in category '{$canonical['category']}', not '{$sourceCategory}'",
                    [
                        'source_name' => $sourceName,
                        'source_category' => $sourceCategory,
                        'canonical_name' => $canonical['name'],
                        'canonical_category' => $canonical['category'],
                        'confidence' => 1.0
                    ]
                );
            }
            
            // Validate level bounds
            $level = $normalized['level'];
            if ($level < ($canonical['min_level'] ?? 0) || $level > ($canonical['max_level'] ?? 5)) {
                $issues[] = $this->createIssue(
                    'LEVEL_OUT_OF_BOUNDS',
                    'warning',
                    "Level {$level} is outside valid range [{$canonical['min_level']}-{$canonical['max_level']}] for ability '{$canonical['name']}'",
                    [
                        'source_name' => $sourceName,
                        'canonical_name' => $canonical['name'],
                        'level' => $level,
                        'min_level' => $canonical['min_level'],
                        'max_level' => $canonical['max_level'],
                        'confidence' => 1.0
                    ]
                );
            }
            
            return [
                'isValid' => true,
                'normalizedAbility' => $normalized,
                'issues' => $issues
            ];
        }
        
        // Try without category constraint
        if ($sourceCategory !== null) {
            $canonical = $this->repository->getCanonicalAbility($normalizedName);
            if ($canonical !== null) {
                $normalized['name'] = $canonical['name'];
                $normalized['category'] = $canonical['category'];
                $issues[] = $this->createIssue(
                    'CATEGORY_MISMATCH',
                    'warning',
                    "Category mismatch: ability '{$sourceName}' exists but in category '{$canonical['category']}', not '{$sourceCategory}'",
                    [
                        'source_name' => $sourceName,
                        'source_category' => $sourceCategory,
                        'canonical_name' => $canonical['name'],
                        'canonical_category' => $canonical['category'],
                        'confidence' => 1.0
                    ]
                );
                return [
                    'isValid' => true,
                    'normalizedAbility' => $normalized,
                    'issues' => $issues
                ];
            }
        }
        
        // Try fuzzy matching if enabled
        $fuzzyEnabled = $this->config['normalization']['fuzzy_matching'] ?? true;
        if ($fuzzyEnabled) {
            $threshold = $this->config['validation']['fuzzy_threshold'] ?? 0.8;
            $matches = $this->repository->searchAbilities($normalizedName, $sourceCategory, $threshold);
            
            if (!empty($matches)) {
                $bestMatch = $matches[0];
                if (count($matches) > 1) {
                    // Multiple matches - ambiguous
                    $matchNames = array_map(function($m) { return $m['ability']['name']; }, $matches);
                    $issues[] = $this->createIssue(
                        'AMBIGUOUS_MAPPING',
                        'warning',
                        "Multiple potential matches for '{$sourceName}': " . implode(', ', $matchNames),
                        [
                            'source_name' => $sourceName,
                            'source_category' => $sourceCategory,
                            'matches' => $matchNames,
                            'confidence' => $bestMatch['similarity']
                        ]
                    );
                }
                
                $normalized['name'] = $bestMatch['ability']['name'];
                $normalized['category'] = $bestMatch['ability']['category'];
                $issues[] = $this->createIssue(
                    'FUZZY_MATCH',
                    'info',
                    "Fuzzy match: '{$sourceName}' matched to '{$bestMatch['ability']['name']}' (similarity: " . round($bestMatch['similarity'], 2) . ")",
                    [
                        'source_name' => $sourceName,
                        'canonical_name' => $bestMatch['ability']['name'],
                        'canonical_category' => $bestMatch['ability']['category'],
                        'confidence' => $bestMatch['similarity']
                    ]
                );
                return [
                    'isValid' => true,
                    'normalizedAbility' => $normalized,
                    'issues' => $issues
                ];
            }
        }
        
        // No match found - unknown ability
        $allowUnknown = $this->config['validation']['allow_unknown'] ?? true;
        $issues[] = $this->createIssue(
            'UNKNOWN_ABILITY',
            $allowUnknown ? 'warning' : 'error',
            "Unknown ability: '{$sourceName}' not found in canonical abilities",
            [
                'source_name' => $sourceName,
                'source_category' => $sourceCategory,
                'confidence' => 0.0
            ]
        );
        
        // If unknown not allowed, don't normalize
        if (!$allowUnknown) {
            return [
                'isValid' => false,
                'normalizedAbility' => $normalized,
                'issues' => $issues
            ];
        }
        
        // Use source name as-is if unknown allowed
        $normalized['name'] = $sourceName;
        if ($sourceCategory !== null) {
            $normalized['category'] = $sourceCategory;
        } else {
            // Try to derive category from abilities table
            $derivedCategory = $this->repository->getCategoryForAbility($sourceName);
            if ($derivedCategory !== null) {
                $normalized['category'] = $derivedCategory;
            }
        }
        
        return [
            'isValid' => $allowUnknown,
            'normalizedAbility' => $normalized,
            'issues' => $issues
        ];
    }
    
    /**
     * Normalize ability name (trim, optionally lowercase)
     * 
     * @param string $name
     * @return string
     */
    protected function normalizeName(string $name): string
    {
        $trimWhitespace = $this->config['normalization']['trim_whitespace'] ?? true;
        $caseSensitive = $this->config['normalization']['case_sensitive'] ?? false;
        
        if ($trimWhitespace) {
            $name = trim($name);
        }
        
        if (!$caseSensitive) {
            $name = strtolower($name);
        }
        
        return $name;
    }
    
    /**
     * Normalize category name
     * 
     * @param string $category
     * @return string
     */
    protected function normalizeCategory(string $category): string
    {
        $normalized = ucfirst(strtolower(trim($category)));
        $validCategories = ['Physical', 'Social', 'Mental', 'Optional'];
        return in_array($normalized, $validCategories, true) ? $normalized : $category;
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

