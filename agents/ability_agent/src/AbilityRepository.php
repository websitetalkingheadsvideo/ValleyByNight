<?php
declare(strict_types=1);

/**
 * AbilityRepository
 * 
 * Handles database queries for the abilities table (canonical ability definitions).
 * Provides methods to fetch abilities by name, category, or search.
 */

class AbilityRepository
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
     * Cache for all abilities (loaded once per instance)
     * @var array|null
     */
    protected $allAbilitiesCache = null;
    
    /**
     * Cache for abilities by category
     * @var array
     */
    protected $categoryCache = [];
    
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
     * Fetch a canonical ability by name and optional category
     * 
     * @param string $name
     * @param string|null $category
     * @return array|null Ability data or null if not found
     */
    public function getCanonicalAbility(string $name, ?string $category = null): ?array
    {
        $normalizedName = $this->normalizeName($name);
        $all = $this->getAllCanonicalAbilities($category);
        foreach ($all as $row) {
            if ($this->normalizeName($row['name'] ?? '') === $normalizedName) {
                if ($category === null || $this->normalizeCategory($row['category'] ?? '') === $this->normalizeCategory($category)) {
                    return $row;
                }
            }
        }
        return null;
    }
    
    /**
     * Get all canonical abilities, optionally filtered by category
     * 
     * @param string|null $category
     * @return array Array of ability data
     */
    public function getAllCanonicalAbilities(?string $category = null): array
    {
        $this->supabase();
        if ($category !== null) {
            if (isset($this->categoryCache[$category])) {
                return $this->categoryCache[$category];
            }
            $normalizedCategory = $this->normalizeCategory($category);
            $rows = supabase_table_get('abilities', [
                'select' => 'id,name,category,display_order,description,min_level,max_level',
                'category' => 'eq.' . $normalizedCategory,
                'order' => 'display_order.asc'
            ]);
            $this->categoryCache[$category] = is_array($rows) ? $rows : [];
            return $this->categoryCache[$category];
        }
        if ($this->allAbilitiesCache === null) {
            $rows = supabase_table_get('abilities', [
                'select' => 'id,name,category,display_order,description,min_level,max_level',
                'order' => 'category.asc,display_order.asc'
            ]);
            $this->allAbilitiesCache = is_array($rows) ? $rows : [];
        }
        return $this->allAbilitiesCache;
    }

    protected function supabase(): void {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
    }
    
    /**
     * Get abilities by category
     * 
     * @param string $category
     * @return array Array of ability data
     */
    public function getAbilitiesByCategory(string $category): array
    {
        return $this->getAllCanonicalAbilities($category);
    }
    
    /**
     * Search abilities using fuzzy matching (name similarity)
     * 
     * @param string $query
     * @param string|null $category
     * @param float $threshold Minimum similarity threshold (0.0-1.0)
     * @return array Array of {ability, similarity} pairs
     */
    public function searchAbilities(string $query, ?string $category = null, float $threshold = 0.7): array
    {
        $allAbilities = $this->getAllCanonicalAbilities($category);
        $normalizedQuery = $this->normalizeName($query);
        $matches = [];
        
        foreach ($allAbilities as $ability) {
            $similarity = $this->calculateSimilarity($normalizedQuery, $this->normalizeName($ability['name']));
            if ($similarity >= $threshold) {
                $matches[] = [
                    'ability' => $ability,
                    'similarity' => $similarity
                ];
            }
        }
        
        // Sort by similarity descending
        usort($matches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return $matches;
    }
    
    /**
     * Check if an ability name and category combination is valid
     * 
     * @param string $name
     * @param string $category
     * @return bool
     */
    public function isValidAbility(string $name, string $category): bool
    {
        $ability = $this->getCanonicalAbility($name, $category);
        return $ability !== null;
    }
    
    /**
     * Get category for an ability by name
     * 
     * @param string $name
     * @return string|null Category or null if not found
     */
    public function getCategoryForAbility(string $name): ?string
    {
        $ability = $this->getCanonicalAbility($name);
        return $ability ? $ability['category'] : null;
    }
    
    /**
     * Normalize ability name for comparison (lowercase, trim)
     * 
     * @param string $name
     * @return string
     */
    protected function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
    
    /**
     * Normalize category name (title case: Physical, Social, Mental, Optional)
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
     * Calculate similarity between two strings using Levenshtein distance
     * Returns a value between 0.0 and 1.0 where 1.0 is an exact match
     * 
     * @param string $str1
     * @param string $str2
     * @return float
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }
        
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1.0 - ($distance / $maxLen);
    }
}

