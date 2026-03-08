<?php
declare(strict_types=1);

/**
 * PathPowersRepository – Supabase
 * Handles reads for path_powers table.
 */
require_once __DIR__ . '/../../../includes/supabase_client.php';

class PathPowersRepository
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

    public function getByPathId(int $pathId): array
    {
        return supabase_table_get('path_powers', [
            'select' => 'id,path_id,level,power_name,system_text,challenge_type,challenge_notes',
            'path_id' => 'eq.' . $pathId,
            'order' => 'level.asc,power_name.asc'
        ]);
    }

    public function getById(int $powerId): ?array
    {
        $rows = supabase_table_get('path_powers', [
            'select' => 'id,path_id,level,power_name,system_text,challenge_type,challenge_notes',
            'id' => 'eq.' . $powerId,
            'limit' => '1'
        ]);
        return $rows[0] ?? null;
    }
}
