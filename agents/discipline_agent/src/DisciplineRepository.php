<?php
declare(strict_types=1);

/**
 * DisciplineRepository
 * Handles character_disciplines table (Supabase).
 */

class DisciplineRepository
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

    public function getCharacterDisciplines(int $characterId): array
    {
        $this->supabase();
        $rows = supabase_table_get('character_disciplines', [
            'select' => 'discipline_name,level',
            'character_id' => 'eq.' . $characterId
        ]);
        return $this->filterOutPathsAndRituals(is_array($rows) ? $rows : []);
    }

    public function getDisciplineLevel(int $characterId, string $disciplineName): ?int
    {
        if (!$this->isInnateDiscipline($disciplineName)) {
            return null;
        }
        $this->supabase();
        $rows = supabase_table_get('character_disciplines', [
            'select' => 'level',
            'character_id' => 'eq.' . $characterId,
            'discipline_name' => 'eq.' . $disciplineName,
            'limit' => '1'
        ]);
        $row = $rows[0] ?? null;
        return $row !== null ? (int)$row['level'] : null;
    }

    public function isInnateDiscipline(string $disciplineName): bool
    {
        $exclusionPatterns = $this->config['exclusion_patterns'] ?? [];
        $pathPrefixes = $exclusionPatterns['path_prefixes'] ?? ['Path of'];
        foreach ($pathPrefixes as $prefix) {
            if (stripos($disciplineName, $prefix) === 0) {
                return false;
            }
        }
        $this->supabase();
        $pathNames = supabase_table_get('paths_master', ['select' => 'name']);
        foreach (is_array($pathNames) ? $pathNames : [] as $p) {
            if (strcasecmp($p['name'] ?? '', $disciplineName) === 0) {
                return false;
            }
        }
        $ritualRows = supabase_table_get('character_rituals', ['select' => 'ritual_type']);
        $ritualTypes = array_unique(array_filter(array_column(is_array($ritualRows) ? $ritualRows : [], 'ritual_type')));
        if (in_array($disciplineName, $ritualTypes, true)) {
            return false;
        }
        return true;
    }

    protected function filterOutPathsAndRituals(array $disciplines): array
    {
        $filtered = [];
        foreach ($disciplines as $d) {
            $name = $d['discipline_name'] ?? '';
            if ($this->isInnateDiscipline($name)) {
                $filtered[] = $d;
            }
        }
        return $filtered;
    }

    public function getAllDisciplineNames(): array
    {
        $this->supabase();
        $rows = supabase_table_get('character_disciplines', ['select' => 'discipline_name']);
        $names = [];
        foreach (is_array($rows) ? $rows : [] as $r) {
            $name = $r['discipline_name'] ?? '';
            if ($name !== '' && $this->isInnateDiscipline($name)) {
                $names[] = $name;
            }
        }
        return array_unique($names);
    }
}
