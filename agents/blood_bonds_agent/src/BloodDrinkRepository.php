<?php
declare(strict_types=1);

/**
 * BloodDrinkRepository
 *
 * Read-only access to character_blood_drinks and related character data.
 */

class BloodDrinkRepository
{
    /** @var mysqli */
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get all drinks from source to drinker, ordered by date.
     *
     * @param int $drinkerId
     * @param int $sourceId
     * @return array<int, array{id: int, drink_date: string, notes: string}>
     */
    public function getDrinksBetween(int $drinkerId, int $sourceId): array
    {
        $rows = db_fetch_all(
            $this->conn,
            'SELECT id, drink_date, notes FROM character_blood_drinks
             WHERE drinker_character_id = ? AND source_character_id = ?
             ORDER BY drink_date ASC',
            'ii',
            [$drinkerId, $sourceId]
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'         => (int) $r['id'],
                'drink_date' => (string) $r['drink_date'],
                'notes'      => (string) ($r['notes'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Get character id, name, creature_type. Returns null if not found.
     *
     * @param int $characterId
     * @return array{id: int, name: string, creature_type: string}|null
     */
    public function getCharacter(int $characterId): ?array
    {
        $columns = $this->getCharacterColumns();
        $select  = implode(', ', array_merge(['id', 'character_name'], $columns));

        $row = db_fetch_one(
            $this->conn,
            "SELECT {$select} FROM characters WHERE id = ? LIMIT 1",
            'i',
            [$characterId]
        );

        if (!$row) {
            return null;
        }

        $creatureType = 'Kindred';
        if (in_array('creature_type', $columns, true) && isset($row['creature_type']) && $row['creature_type'] !== '') {
            $creatureType = (string) $row['creature_type'];
        } elseif (empty($row['clan'] ?? '') && empty($row['generation'] ?? '')) {
            $creatureType = 'Unknown';
        }

        return [
            'id'            => (int) $row['id'],
            'name'          => (string) ($row['character_name'] ?? ''),
            'creature_type' => $creatureType,
        ];
    }

    /**
     * Get all drinker-source pairs with drink counts (for diagnostics).
     *
     * @return array<int, array{drinker_id: int, source_id: int, drink_count: int}>
     */
    public function getAllDrinkPairs(): array
    {
        $rows = db_fetch_all(
            $this->conn,
            'SELECT drinker_character_id, source_character_id, COUNT(*) AS drink_count
             FROM character_blood_drinks
             GROUP BY drinker_character_id, source_character_id'
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'drinker_id'   => (int) $r['drinker_character_id'],
                'source_id'    => (int) $r['source_character_id'],
                'drink_count'  => (int) $r['drink_count'],
            ];
        }
        return $out;
    }

    /**
     * Check if character exists.
     */
    public function characterExists(int $characterId): bool
    {
        $row = db_fetch_one($this->conn, 'SELECT 1 FROM characters WHERE id = ? LIMIT 1', 'i', [$characterId]);
        return $row !== null;
    }

    private function getCharacterColumns(): array
    {
        $result = mysqli_query($this->conn, 'DESCRIBE characters');
        $cols   = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cols[] = $row['Field'];
        }
        mysqli_free_result($result);

        $wanted = ['creature_type', 'clan', 'generation'];
        return array_values(array_intersect($cols, $wanted));
    }
}
