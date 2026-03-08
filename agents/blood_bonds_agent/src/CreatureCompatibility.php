<?php
declare(strict_types=1);

/**
 * CreatureCompatibility
 *
 * Validates valid blood-drink pairings. Invalid pairs are flagged, not enforced.
 */

class CreatureCompatibility
{
    /** @var array<string, array<string>> */
    private const ALLOWED = [
        'Kindred' => ['Mortal', 'Ghoul', 'Kindred'],
        'Ghoul'   => ['Kindred'],
    ];

    /**
     * Check if drinker/source creature types form a valid blood bond pairing.
     *
     * @param string $drinkerType creature_type of drinker (Kindred, Ghoul, Mortal, Wraith, etc.)
     * @param string $sourceType  creature_type of source
     * @return array{valid: bool, flag?: string}
     */
    public static function validatePair(string $drinkerType, string $sourceType): array
    {
        $drinker = self::normalizeType($drinkerType);
        $source  = self::normalizeType($sourceType);

        if ($source === 'Wraith' || $drinker === 'Wraith') {
            return ['valid' => false, 'flag' => 'Wraith vitae incompatible with standard blood bond'];
        }

        if (!isset(self::ALLOWED[$drinker])) {
            return ['valid' => false, 'flag' => "Drinker type '{$drinker}' cannot form blood bonds"];
        }

        if (!in_array($source, self::ALLOWED[$drinker], true)) {
            return ['valid' => false, 'flag' => "Source type '{$source}' incompatible with drinker '{$drinker}'"];
        }

        return ['valid' => true];
    }

    private static function normalizeType(string $type): string
    {
        $t = trim($type);
        return $t === '' ? 'Unknown' : $t;
    }
}
