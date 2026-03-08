<?php
declare(strict_types=1);

/**
 * BondDerivation
 *
 * Pure functions to derive blood bond stage from drink history.
 * Stage is never stored; always computed at read time.
 */

class BondDerivation
{
    /** @var array<int, string> */
    private const STAGE_LABELS = [
        0 => 'No bond',
        1 => 'Fascination',
        2 => 'Attachment',
        3 => 'Full bond',
    ];

    /** @var array<int, string> */
    private const EMOTIONAL_PRESSURE = [
        0 => '',
        1 => 'Drawn to the source; fascination and irrational pull. First taste creates longing.',
        2 => 'Strong emotional dependence; difficulty resisting. Denial, shame, or rationalization may persist.',
        3 => 'Total devotion to the source. Obedience feels natural; separation causes distress.',
    ];

    /**
     * Derive bond stage from drink count.
     *
     * @param array<int, array{drink_date: string, notes?: string}> $drinks Ordered by date ascending
     * @return int Stage 0-3
     */
    public static function deriveStage(array $drinks): int
    {
        $count = count($drinks);
        if ($count >= 3) {
            return 3;
        }
        if ($count === 2) {
            return 2;
        }
        if ($count === 1) {
            return 1;
        }
        return 0;
    }

    /**
     * @param int $stage 0-3
     * @return string
     */
    public static function getStageLabel(int $stage): string
    {
        return self::STAGE_LABELS[$stage] ?? 'Unknown';
    }

    /**
     * @param int $stage 0-3
     * @return string
     */
    public static function getEmotionalPressure(int $stage): string
    {
        return self::EMOTIONAL_PRESSURE[$stage] ?? '';
    }
}
