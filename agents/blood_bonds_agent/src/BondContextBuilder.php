<?php
declare(strict_types=1);

/**
 * BondContextBuilder
 *
 * Assembles bond context JSON for Dialogue Agent and diagnostics.
 */

require_once __DIR__ . '/BloodDrinkRepository.php';
require_once __DIR__ . '/BondDerivation.php';
require_once __DIR__ . '/CreatureCompatibility.php';

class BondContextBuilder
{
    /** @var mysqli */
    private $conn;

    /** @var BloodDrinkRepository */
    private $repo;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->repo = new BloodDrinkRepository($conn);
    }

    /**
     * Build bond context for a single drinker-source pair.
     *
     * @return array<string, mixed>|null Null if either character missing
     */
    public function buildPairContext(int $drinkerId, int $sourceId): ?array
    {
        $drinker = $this->repo->getCharacter($drinkerId);
        $source  = $this->repo->getCharacter($sourceId);

        $diagnostics = [];

        if (!$drinker) {
            $diagnostics[] = ['type' => 'orphaned_drinker', 'id' => $drinkerId];
        }
        if (!$source) {
            $diagnostics[] = ['type' => 'orphaned_source', 'id' => $sourceId];
        }
        if (!$drinker || !$source) {
            return [
                'drinker_id'   => $drinkerId,
                'drinker_name' => $drinker['name'] ?? 'Unknown',
                'source_id'    => $sourceId,
                'source_name'  => $source['name'] ?? 'Unknown',
                'bond_stage'   => 0,
                'bond_stage_label' => 'No bond',
                'drink_count'  => 0,
                'drinks'       => [],
                'emotional_pressure' => '',
                'compatibility' => ['valid' => false, 'flag' => 'Character not found'],
                'diagnostics'   => $diagnostics,
            ];
        }

        $drinks = $this->repo->getDrinksBetween($drinkerId, $sourceId);
        $stage  = BondDerivation::deriveStage($drinks);
        $compat = CreatureCompatibility::validatePair(
            $drinker['creature_type'],
            $source['creature_type']
        );

        if (!$compat['valid']) {
            $diagnostics[] = ['type' => 'invalid_creature_pair', 'flag' => $compat['flag'] ?? ''];
        }
        if (count($drinks) > 3) {
            $diagnostics[] = ['type' => 'unusual_pattern', 'description' => 'More than 3 drinks recorded'];
        }

        $drinksForOutput = [];
        foreach ($drinks as $d) {
            $drinksForOutput[] = [
                'drink_date' => $d['drink_date'],
                'notes'      => $d['notes'],
            ];
        }

        return [
            'drinker_id'        => $drinkerId,
            'drinker_name'      => $drinker['name'],
            'source_id'         => $sourceId,
            'source_name'       => $source['name'],
            'bond_stage'        => $stage,
            'bond_stage_label'  => BondDerivation::getStageLabel($stage),
            'drink_count'       => count($drinks),
            'drinks'            => $drinksForOutput,
            'emotional_pressure'=> BondDerivation::getEmotionalPressure($stage),
            'compatibility'     => $compat,
            'diagnostics'       => $diagnostics,
        ];
    }

    /**
     * Build all bonds for a character (as drinker).
     *
     * @return array<string, mixed>
     */
    public function buildCharacterBondsAsDrinker(int $characterId): array
    {
        $character = $this->repo->getCharacter($characterId);
        $diagnostics = [];

        $bonds = [];
        $pairs = $this->repo->getAllDrinkPairs();
        foreach ($pairs as $p) {
            if ($p['drinker_id'] !== $characterId) {
                continue;
            }
            $ctx = $this->buildPairContext($p['drinker_id'], $p['source_id']);
            if ($ctx) {
                $bonds[] = [
                    'source_id'         => $ctx['source_id'],
                    'source_name'       => $ctx['source_name'],
                    'bond_stage'        => $ctx['bond_stage'],
                    'bond_stage_label'  => $ctx['bond_stage_label'],
                    'emotional_pressure'=> $ctx['emotional_pressure'],
                    'drink_count'       => $ctx['drink_count'],
                ];
                $diagnostics = array_merge($diagnostics, $ctx['diagnostics']);
            }
        }

        return [
            'character_id'   => $characterId,
            'character_name' => $character['name'] ?? 'Unknown',
            'bonds_as_drinker' => $bonds,
            'diagnostics'    => $diagnostics,
        ];
    }

    /**
     * Build system-wide diagnostics.
     *
     * @return array<string, mixed>
     */
    public function buildDiagnostics(): array
    {
        $orphanedDrinker = [];
        $orphanedSource  = [];
        $invalidPairs    = [];
        $unusualPatterns = [];

        $pairs = $this->repo->getAllDrinkPairs();
        foreach ($pairs as $p) {
            if (!$this->repo->characterExists($p['drinker_id'])) {
                $orphanedDrinker[] = $p['drinker_id'];
            }
            if (!$this->repo->characterExists($p['source_id'])) {
                $orphanedSource[] = $p['source_id'];
            }

            $ctx = $this->buildPairContext($p['drinker_id'], $p['source_id']);
            if ($ctx && !$ctx['compatibility']['valid']) {
                $invalidPairs[] = [
                    'drinker_id' => $p['drinker_id'],
                    'source_id'  => $p['source_id'],
                    'flag'       => $ctx['compatibility']['flag'] ?? '',
                ];
            }
            if ($p['drink_count'] > 3) {
                $unusualPatterns[] = [
                    'drinker_id'  => $p['drinker_id'],
                    'source_id'   => $p['source_id'],
                    'drink_count' => $p['drink_count'],
                ];
            }
        }

        return [
            'orphaned_drinker_ids' => array_unique($orphanedDrinker),
            'orphaned_source_ids'  => array_unique($orphanedSource),
            'invalid_creature_pairs' => $invalidPairs,
            'unusual_patterns'     => $unusualPatterns,
        ];
    }
}
