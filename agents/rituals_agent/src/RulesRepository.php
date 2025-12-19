<?php
declare(strict_types=1);

/**
 * RulesRepository
 * 
 * Handles queries to the Rules database (rulebooks/rulebook_pages tables).
 * Provides methods to fetch global and tradition-specific ritual rules.
 */

class RulesRepository
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
     * Get global ritual rules
     * 
     * Searches for general ritual rules that apply to all ritual types.
     * 
     * @param int $limit Maximum number of results
     * @return array Array of rule excerpts with metadata
     */
    public function getGlobalRitualRules(int $limit = 10): array
    {
        // Search for general ritual rules (not tradition-specific)
        $query = "ritual casting learning components ingredients";
        
        $sql = <<<SQL
            SELECT
                r.id AS rulebook_id,
                r.title AS book_title,
                r.category,
                r.system_type,
                rp.page_number,
                rp.page_text,
                MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
            FROM rulebook_pages rp
            JOIN rulebooks r ON rp.rulebook_id = r.id
            WHERE MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE)
              AND (
                  rp.page_text LIKE '%ritual%' 
                  OR rp.page_text LIKE '%rituals%'
                  OR rp.page_text LIKE '%casting%'
                  OR rp.page_text LIKE '%learning ritual%'
                  OR rp.page_text LIKE '%ritual component%'
                  OR rp.page_text LIKE '%ritual ingredient%'
              )
              AND (
                  rp.page_text NOT LIKE '%thaumaturgy%'
                  AND rp.page_text NOT LIKE '%necromancy%'
                  AND rp.page_text NOT LIKE '%assamite%'
              )
            ORDER BY relevance DESC
            LIMIT ?
        SQL;
        
        $result = db_select($this->db, $sql, 'ssi', [$query, $query, $limit]);
        
        if ($result === false) {
            return [];
        }
        
        $rules = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rules[] = [
                'rulebook_id' => (int)$row['rulebook_id'],
                'book_title' => $row['book_title'],
                'category' => $row['category'],
                'system_type' => $row['system_type'],
                'page_number' => (int)$row['page_number'],
                'page_text' => $row['page_text'],
                'excerpt' => $this->extractExcerpt($row['page_text'], 300),
                'relevance' => (float)($row['relevance'] ?? 0)
            ];
        }
        
        return $rules;
    }
    
    /**
     * Get tradition-specific ritual rules
     * 
     * Searches for rules specific to a ritual tradition (e.g., Thaumaturgy, Necromancy).
     * 
     * @param string $tradition Tradition name (e.g., "Thaumaturgy", "Necromancy", "Assamite")
     * @param int $limit Maximum number of results
     * @return array Array of rule excerpts with metadata
     */
    public function getTraditionRules(string $tradition, int $limit = 10): array
    {
        // Build search query for tradition-specific rules
        $traditionLower = strtolower($tradition);
        $query = "{$traditionLower} ritual rituals casting learning";
        
        $sql = <<<SQL
            SELECT
                r.id AS rulebook_id,
                r.title AS book_title,
                r.category,
                r.system_type,
                rp.page_number,
                rp.page_text,
                MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
            FROM rulebook_pages rp
            JOIN rulebooks r ON rp.rulebook_id = r.id
            WHERE MATCH(rp.page_text) AGAINST(? IN NATURAL LANGUAGE MODE)
              AND (
                  rp.page_text LIKE ? 
                  OR rp.page_text LIKE ?
              )
              AND (
                  rp.page_text LIKE '%ritual%' 
                  OR rp.page_text LIKE '%rituals%'
                  OR rp.page_text LIKE '%casting%'
              )
            ORDER BY relevance DESC
            LIMIT ?
        SQL;
        
        $traditionPattern1 = "%{$traditionLower}%";
        $traditionPattern2 = "%{$tradition}%";
        
        $result = db_select($this->db, $sql, 'ssssi', [
            $query, 
            $query, 
            $traditionPattern1, 
            $traditionPattern2, 
            $limit
        ]);
        
        if ($result === false) {
            return [];
        }
        
        $rules = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rules[] = [
                'rulebook_id' => (int)$row['rulebook_id'],
                'book_title' => $row['book_title'],
                'category' => $row['category'],
                'system_type' => $row['system_type'],
                'page_number' => (int)$row['page_number'],
                'page_text' => $row['page_text'],
                'excerpt' => $this->extractExcerpt($row['page_text'], 300),
                'relevance' => (float)($row['relevance'] ?? 0),
                'tradition' => $tradition
            ];
        }
        
        return $rules;
    }
    
    /**
     * Extract an excerpt from text
     * 
     * @param string $text
     * @param int $maxChars
     * @return string
     */
    protected function extractExcerpt(string $text, int $maxChars = 300): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) <= $maxChars) {
            return $text;
        }
        
        $excerpt = substr($text, 0, $maxChars);
        $lastPeriod = strrpos($excerpt, '.');
        
        if ($lastPeriod !== false && $lastPeriod > $maxChars * 0.7) {
            return substr($text, 0, $lastPeriod + 1);
        }
        
        return $excerpt . '...';
    }
}

