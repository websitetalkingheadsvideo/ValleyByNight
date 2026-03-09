<?php
declare(strict_types=1);

/**
 * ClanAccessRepository
 * Handles clan discipline access rules (Supabase).
 */

class ClanAccessRepository
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;

    /** @var array */
    protected $config;

    /** @var array|null */
    protected $clanDisciplinesCache = null;

    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
        $this->loadClanDisciplines();
    }

    protected function loadClanDisciplines(): void
    {
        if ($this->clanDisciplinesCache !== null) {
            return;
        }
        $this->clanDisciplinesCache = $this->config['clan_disciplines'] ?? [];
    }

    public function getClanDisciplines(string $clanName): array
    {
        return $this->clanDisciplinesCache[$clanName] ?? [];
    }

    public function isInClanDiscipline(string $clanName, string $disciplineName): bool
    {
        $clanDisciplines = $this->getClanDisciplines($clanName);
        if ($clanName === 'Caitiff') {
            return false;
        }
        return in_array($disciplineName, $clanDisciplines, true);
    }

    public function getClanName(int $characterId): ?string
    {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
        $rows = supabase_table_get('characters', [
            'select' => 'clan',
            'id' => 'eq.' . $characterId,
            'limit' => '1'
        ]);
        $row = $rows[0] ?? null;
        return $row !== null ? ($row['clan'] ?? null) : null;
    }

    public function getAllClans(): array
    {
        return array_keys($this->clanDisciplinesCache);
    }
}
