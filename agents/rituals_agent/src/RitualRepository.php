<?php
declare(strict_types=1);

/**
 * RitualRepository
 * Handles database queries for the rituals_master table (Supabase).
 */

class RitualRepository
{
    /** @var mixed Legacy MySQL connection - unused, kept for constructor compatibility */
    protected $db;

    /** @var array */
    protected $config;

    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Ensure Supabase client is loaded (used when $this->db is null).
     */
    protected function supabase(): void
    {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
    }

    public function getById(int $ritualId): ?array
    {
        $this->supabase();
        $rows = supabase_table_get('rituals_master', [
            'select' => 'id,name,type,level,description,system_text,requirements,ingredients,source,created_at',
            'id' => 'eq.' . $ritualId,
            'limit' => 1
        ]);
        if (empty($rows) || !is_array($rows)) {
            return null;
        }
        return $rows[0];
    }

    public function getByTypeLevelName(string $type, int $level, string $name): ?array
    {
        $this->supabase();
        $rows = supabase_table_get('rituals_master', [
            'select' => 'id,name,type,level,description,system_text,requirements,ingredients,source,created_at',
            'type' => 'eq.' . $type,
            'level' => 'eq.' . $level,
            'name' => 'eq.' . $name,
            'limit' => 1
        ]);
        if (empty($rows) || !is_array($rows)) {
            return null;
        }
        return $rows[0];
    }

    public function listRituals(?string $type = null, ?int $level = null, int $limit = 100, int $offset = 0): array
    {
        $this->supabase();
        $query = [
            'select' => 'id,name,type,level,description,system_text,requirements,ingredients,source,created_at',
            'order' => 'type.asc,level.asc,name.asc',
            'limit' => $limit,
            'offset' => $offset
        ];
        if ($type !== null) {
            $query['type'] = 'eq.' . $type;
        }
        if ($level !== null) {
            $query['level'] = 'eq.' . $level;
        }
        $rows = supabase_table_get('rituals_master', $query);
        return is_array($rows) ? $rows : [];
    }
}
