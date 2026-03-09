<?php
declare(strict_types=1);

/**
 * DisciplinePowersRepository
 * Handles disciplies_powers table (Supabase).
 */

class DisciplinePowersRepository
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

    public function getCharacterDisciplinePowers(int $characterId, ?string $disciplineName = null): array
    {
        $this->supabase();
        if ($disciplineName !== null) {
            $rows = supabase_table_get('disciplies_powers', [
                'select' => 'discipline_name,power_name,level',
                'discipline_name' => 'eq.' . $disciplineName,
                'order' => 'level.asc,power_name.asc'
            ]);
        } else {
            $rows = supabase_table_get('disciplies_powers', [
                'select' => 'discipline_name,power_name,level',
                'order' => 'discipline_name.asc,level.asc,power_name.asc'
            ]);
        }
        return is_array($rows) ? $rows : [];
    }

    public function getPowersByDisciplineAndLevel(string $disciplineName, int $level): array
    {
        $this->supabase();
        $rows = supabase_table_get('disciplies_powers', [
            'select' => 'power_name,level',
            'discipline_name' => 'eq.' . $disciplineName,
            'level' => 'lte.' . $level,
            'order' => 'level.asc,power_name.asc'
        ]);
        $seen = [];
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $r) {
            $key = ($r['power_name'] ?? '') . '-' . ($r['level'] ?? 0);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = ['power_name' => $r['power_name'] ?? '', 'level' => (int)($r['level'] ?? 0)];
        }
        return $out;
    }

    public function powerExists(string $disciplineName, string $powerName): bool
    {
        $this->supabase();
        $rows = supabase_table_get('disciplies_powers', [
            'select' => 'id',
            'discipline_name' => 'eq.' . $disciplineName,
            'power_name' => 'eq.' . $powerName,
            'limit' => '1'
        ]);
        return !empty($rows);
    }

    public function getPowerLevel(string $disciplineName, string $powerName): ?int
    {
        $this->supabase();
        $rows = supabase_table_get('disciplies_powers', [
            'select' => 'level',
            'discipline_name' => 'eq.' . $disciplineName,
            'power_name' => 'eq.' . $powerName,
            'limit' => '1'
        ]);
        $row = $rows[0] ?? null;
        return $row !== null ? (int)$row['level'] : null;
    }
}
