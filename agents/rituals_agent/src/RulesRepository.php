<?php
declare(strict_types=1);

/**
 * RulesRepository
 * Handles queries to rulebooks/rulebook_pages (Supabase).
 * Returns [] if tables unavailable - no MySQL full-text search equivalent.
 */

class RulesRepository
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;

    /** @var array */
    protected $config;

    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    protected function supabase(): void
    {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
    }

    public function getGlobalRitualRules(int $limit = 10): array
    {
        try {
            $this->supabase();
            $rows = supabase_table_get('rulebook_pages', [
                'select' => 'rulebook_id,page_number,page_text',
                'page_text' => 'ilike.%ritual%',
                'limit' => (string)$limit
            ]);
        } catch (Throwable $e) {
            return [];
        }
        if (empty($rows)) return [];
        $rbIds = array_unique(array_filter(array_column($rows, 'rulebook_id')));
        $rulebooks = [];
        if (!empty($rbIds)) {
            $rbs = supabase_table_get('rulebooks', ['select' => 'id,title,category,system_type', 'id' => 'in.(' . implode(',', array_map('intval', $rbIds)) . ')']);
            foreach ($rbs as $r) {
                $rulebooks[(int)$r['id']] = $r;
            }
        }
        $rules = [];
        foreach ($rows as $r) {
            $rb = $rulebooks[(int)($r['rulebook_id'] ?? 0)] ?? null;
            $rules[] = [
                'rulebook_id' => (int)($r['rulebook_id'] ?? 0),
                'book_title' => $rb['title'] ?? '',
                'category' => $rb['category'] ?? null,
                'system_type' => $rb['system_type'] ?? null,
                'page_number' => (int)($r['page_number'] ?? 0),
                'page_text' => $r['page_text'] ?? '',
                'excerpt' => $this->extractExcerpt($r['page_text'] ?? '', 300),
                'relevance' => 0.0
            ];
        }
        return $rules;
    }

    public function getTraditionRules(string $tradition, int $limit = 10): array
    {
        try {
            $this->supabase();
            $pat = '%' . addcslashes($tradition, '%_\\') . '%';
            $rows = supabase_table_get('rulebook_pages', [
                'select' => 'rulebook_id,page_number,page_text',
                'page_text' => 'ilike.' . $pat,
                'limit' => (string)$limit
            ]);
        } catch (Throwable $e) {
            return [];
        }
        if (empty($rows)) return [];
        $rbIds = array_unique(array_filter(array_column($rows, 'rulebook_id')));
        $rulebooks = [];
        if (!empty($rbIds)) {
            $rbs = supabase_table_get('rulebooks', ['select' => 'id,title,category,system_type', 'id' => 'in.(' . implode(',', array_map('intval', $rbIds)) . ')']);
            foreach ($rbs as $r) {
                $rulebooks[(int)$r['id']] = $r;
            }
        }
        $rules = [];
        foreach ($rows as $r) {
            $rb = $rulebooks[(int)($r['rulebook_id'] ?? 0)] ?? null;
            $rules[] = [
                'rulebook_id' => (int)($r['rulebook_id'] ?? 0),
                'book_title' => $rb['title'] ?? '',
                'category' => $rb['category'] ?? null,
                'system_type' => $rb['system_type'] ?? null,
                'page_number' => (int)($r['page_number'] ?? 0),
                'page_text' => $r['page_text'] ?? '',
                'excerpt' => $this->extractExcerpt($r['page_text'] ?? '', 300),
                'relevance' => 0.0,
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

