<?php
declare(strict_types=1);

/**
 * CharacterRitualsRepository
 * Handles read-only database queries for character-known rituals (Supabase).
 */

class CharacterRitualsRepository
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

    public function getKnownRitualsForCharacter(int $characterId): array
    {
        $this->supabase();
        $crs = supabase_table_get('character_rituals', [
            'select' => 'ritual_id,ritual_name,ritual_type,level,is_custom,description',
            'character_id' => 'eq.' . $characterId
        ]);
        if (empty($crs)) {
            return [];
        }
        $ritualIds = [];
        $byNameTypeLevel = [];
        foreach ($crs as $cr) {
            $rid = $cr['ritual_id'] ?? null;
            if ($rid !== null && $rid !== '') {
                $ritualIds[(int)$rid] = true;
            } else {
                $key = ($cr['ritual_name'] ?? '') . '|' . ($cr['ritual_type'] ?? '') . '|' . ($cr['level'] ?? '');
                $byNameTypeLevel[$key] = $cr;
            }
        }
        $ritualIds = array_keys($ritualIds);
        $ritualMap = [];
        if (!empty($ritualIds)) {
            $rms = supabase_table_get('rituals_master', [
                'select' => 'id,name,type,level,description,system_text,requirements,ingredients,source,created_at',
                'id' => 'in.(' . implode(',', $ritualIds) . ')'
            ]);
            foreach ($rms as $rm) {
                $ritualMap[(int)$rm['id']] = $rm;
            }
        }
        $allRituals = supabase_table_get('rituals_master', [
            'select' => 'id,name,type,level,description,system_text,requirements,ingredients,source,created_at'
        ]);
        $byNTL = [];
        foreach ($allRituals as $rm) {
            $key = ($rm['name'] ?? '') . '|' . ($rm['type'] ?? '') . '|' . ($rm['level'] ?? '');
            $byNTL[$key] = $rm;
        }
        $rituals = [];
        foreach ($crs as $cr) {
            $ritual = null;
            $rid = $cr['ritual_id'] ?? null;
            if ($rid !== null && $rid !== '') {
                $ritual = $ritualMap[(int)$rid] ?? null;
            }
            if ($ritual === null) {
                $key = ($cr['ritual_name'] ?? '') . '|' . ($cr['ritual_type'] ?? '') . '|' . ($cr['level'] ?? '');
                $ritual = $byNTL[$key] ?? null;
            }
            if ($ritual === null) {
                $rituals[] = [
                    'id' => null,
                    'name' => $cr['ritual_name'] ?? '',
                    'type' => $cr['ritual_type'] ?? '',
                    'level' => $cr['level'] ?? null,
                    'description' => $cr['description'] ?? '',
                    'system_text' => null,
                    'requirements' => null,
                    'ingredients' => null,
                    'source' => null,
                    'created_at' => null,
                    'is_custom' => (bool)($cr['is_custom'] ?? false)
                ];
            } else {
                $ritual['is_custom'] = (bool)($cr['is_custom'] ?? false);
                $rituals[] = $ritual;
            }
        }
        usort($rituals, static function ($a, $b) {
            $t = strcmp($a['type'] ?? '', $b['type'] ?? '');
            if ($t !== 0) return $t;
            $l = ((int)($a['level'] ?? 0)) <=> ((int)($b['level'] ?? 0));
            return $l !== 0 ? $l : strcmp($a['name'] ?? '', $b['name'] ?? '');
        });
        return $rituals;
    }
}

