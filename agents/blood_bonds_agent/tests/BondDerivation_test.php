<?php
declare(strict_types=1);

/**
 * BondDerivation unit tests
 */

require_once __DIR__ . '/../src/BondDerivation.php';

$passed = 0;
$failed = 0;

function ok(bool $cond, string $msg): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "OK: {$msg}\n";
    } else {
        $failed++;
        echo "FAIL: {$msg}\n";
    }
}

// deriveStage
ok(BondDerivation::deriveStage([]) === 0, '0 drinks → stage 0');
ok(BondDerivation::deriveStage([['drink_date' => '1994-10-15', 'notes' => '']]) === 1, '1 drink → stage 1');
ok(BondDerivation::deriveStage([
    ['drink_date' => '1994-10-15', 'notes' => ''],
    ['drink_date' => '1994-10-22', 'notes' => ''],
]) === 2, '2 drinks → stage 2');
ok(BondDerivation::deriveStage([
    ['drink_date' => '1994-10-15', 'notes' => ''],
    ['drink_date' => '1994-10-22', 'notes' => ''],
    ['drink_date' => '1994-10-29', 'notes' => ''],
]) === 3, '3 drinks → stage 3');
ok(BondDerivation::deriveStage(array_fill(0, 5, ['drink_date' => '1994-10-15', 'notes' => ''])) === 3, '5 drinks → stage 3');

// labels
ok(BondDerivation::getStageLabel(0) === 'No bond', 'stage 0 label');
ok(BondDerivation::getStageLabel(3) === 'Full bond', 'stage 3 label');
ok(BondDerivation::getEmotionalPressure(1) !== '', 'stage 1 has emotional pressure');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
