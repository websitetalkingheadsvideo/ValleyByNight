<?php
declare(strict_types=1);

/**
 * PathRepository – Supabase
 * Handles reads for paths_master table.
 */
require_once __DIR__ . '/../../../includes/supabase_client.php';

class PathRepository
{
    /** @var mixed Legacy; ignored */
    protected $db;
    /** @var array */
    protected $config;

    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function getById(int $pathId): ?array
    {
        $rows = supabase_table_get('paths_master', [
            'select' => 'id,name,type,description,source,created_at',
            'id' => 'eq.' . $pathId,
            'limit' => '1'
        ]);
        return $rows[0] ?? null;
    }

    public function listByType(?string $type = null, int $limit = 100, int $offset = 0): array
    {
        $query = [
            'select' => 'id,name,type,description,source,created_at',
            'order' => 'type.asc,name.asc',
            'limit' => (string) $limit,
            'offset' => (string) $offset
        ];
        if ($type !== null) {
            $query['type'] = 'eq.' . $type;
        }
        return supabase_table_get('paths_master', $query);
    }
}
