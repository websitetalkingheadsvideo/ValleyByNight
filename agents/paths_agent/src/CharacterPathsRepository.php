<?php
declare(strict_types=1);

/**
 * CharacterPathsRepository – Supabase
 * Handles reads for character_paths with path details.
 */
require_once __DIR__ . '/../../../includes/supabase_client.php';

class CharacterPathsRepository
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

    public function getByCharacterId(int $characterId): array
    {
        $cps = supabase_table_get('character_paths', [
            'select' => 'character_id,path_id,rating,notes,is_primary',
            'character_id' => 'eq.' . $characterId
        ]);
        if (empty($cps)) {
            return [];
        }
        $pathIds = array_unique(array_column($cps, 'path_id'));
        $paths = supabase_table_get('paths_master', [
            'select' => 'id,name,type,description,source',
            'id' => 'in.(' . implode(',', array_map('intval', $pathIds)) . ')'
        ]);
        $pathMap = [];
        foreach ($paths as $p) {
            $pathMap[(int) $p['id']] = $p;
        }
        $out = [];
        foreach ($cps as $cp) {
            $pathId = (int) $cp['path_id'];
            $pm = $pathMap[$pathId] ?? null;
            $out[] = [
                'character_id' => (int) $cp['character_id'],
                'path_id' => $pathId,
                'path_name' => $pm['name'] ?? '',
                'path_type' => $pm['type'] ?? '',
                'rating' => (int) ($cp['rating'] ?? 0),
                'notes' => $cp['notes'] ?? null,
                'is_primary' => (int) ($cp['is_primary'] ?? 0),
                'description' => $pm['description'] ?? null,
                'source' => $pm['source'] ?? null
            ];
        }
        usort($out, static function ($a, $b) {
            if ($a['is_primary'] !== $b['is_primary']) {
                return $b['is_primary'] <=> $a['is_primary'];
            }
            $t = strcmp($a['path_type'] ?? '', $b['path_type'] ?? '');
            return $t !== 0 ? $t : strcmp($a['path_name'] ?? '', $b['path_name'] ?? '');
        });
        return $out;
    }

    public function getRatingForPath(int $characterId, int $pathId): ?int
    {
        $rows = supabase_table_get('character_paths', [
            'select' => 'rating',
            'character_id' => 'eq.' . $characterId,
            'path_id' => 'eq.' . $pathId,
            'limit' => '1'
        ]);
        $row = $rows[0] ?? null;
        return $row !== null ? (int) $row['rating'] : null;
    }
}
