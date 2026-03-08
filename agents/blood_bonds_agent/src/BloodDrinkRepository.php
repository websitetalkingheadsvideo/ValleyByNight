<?php
declare(strict_types=1);

/**
 * BloodDrinkRepository – Supabase
 */
require_once __DIR__ . '/../../../includes/supabase_client.php';

class BloodDrinkRepository
{
    /** @var mixed Legacy; ignored */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getDrinksBetween(int $drinkerId, int $sourceId): array
    {
        $rows = supabase_table_get('character_blood_drinks', [
            'select' => 'id,drink_date,notes',
            'drinker_character_id' => 'eq.' . $drinkerId,
            'source_character_id' => 'eq.' . $sourceId,
            'order' => 'drink_date.asc'
        ]);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'         => (int) $r['id'],
                'drink_date' => (string) ($r['drink_date'] ?? ''),
                'notes'      => (string) ($r['notes'] ?? ''),
            ];
        }
        return $out;
    }

    public function getCharacter(int $characterId): ?array
    {
        $rows = supabase_table_get('characters', [
            'select' => 'id,character_name,clan,generation,creature_type',
            'id' => 'eq.' . $characterId,
            'limit' => '1'
        ]);
        $row = $rows[0] ?? null;
        if (!$row) {
            return null;
        }
        $creatureType = 'Kindred';
        if (!empty($row['creature_type'])) {
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

    public function getAllDrinkPairs(): array
    {
        $rows = supabase_table_get('character_blood_drinks', ['select' => 'drinker_character_id,source_character_id']);
        $counts = [];
        foreach ($rows as $r) {
            $key = (int) $r['drinker_character_id'] . '-' . (int) $r['source_character_id'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        $out = [];
        foreach ($counts as $key => $count) {
            [$drinkerId, $sourceId] = explode('-', $key, 2);
            $out[] = [
                'drinker_id'  => (int) $drinkerId,
                'source_id'   => (int) $sourceId,
                'drink_count' => $count,
            ];
        }
        return $out;
    }

    public function characterExists(int $characterId): bool
    {
        $rows = supabase_table_get('characters', ['select' => 'id', 'id' => 'eq.' . $characterId, 'limit' => '1']);
        return !empty($rows);
    }
}
