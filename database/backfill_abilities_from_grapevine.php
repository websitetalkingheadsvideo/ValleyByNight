<?php
/**
 * Backfill abilities table with new abilities (Grapevine menu alignment).
 * Inserts only rows that do not already exist (name + category).
 * Run once after create_abilities_table.php was already run in the past.
 *
 * Run via browser: database/backfill_abilities_from_grapevine.php
 * Or via CLI: php database/backfill_abilities_from_grapevine.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$new_abilities = [
    ['Archery', 'Physical', 9, 'Bow and crossbow'],
    ['Blindfighting', 'Physical', 10, 'Fighting without sight'],
    ['Demolitions', 'Physical', 11, 'Explosives'],
    ['Flight', 'Physical', 12, 'Aerial movement'],
    ['Throwing', 'Physical', 13, 'Thrown weapons'],
    ['Disguise', 'Social', 10, 'Assuming false identity'],
    ['Bureaucracy', 'Mental', 11, 'Bureaucratic systems'],
    ['Enigmas', 'Mental', 12, 'Puzzles and codes'],
    ['Meditation', 'Mental', 13, 'Mental discipline and focus'],
    ['Repair', 'Mental', 14, 'Fixing and maintaining equipment'],
    ['Torture', 'Mental', 15, 'Extracting information through pain'],
];

$stmt = mysqli_prepare(
    $conn,
    "INSERT IGNORE INTO abilities (name, category, display_order, description, min_level, max_level) VALUES (?, ?, ?, ?, 0, 5)"
);
if (!$stmt) {
    die("Failed to prepare statement: " . mysqli_error($conn));
}

$inserted = 0;
foreach ($new_abilities as $row) {
    mysqli_stmt_bind_param($stmt, 'ssis', $row[0], $row[1], $row[2], $row[3]);
    if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
        $inserted++;
    }
}
mysqli_stmt_close($stmt);

$is_cli = (php_sapi_name() === 'cli');
if ($is_cli) {
    echo "Inserted {$inserted} new abilities (skipped " . (count($new_abilities) - $inserted) . " already present).\n";
} else {
    echo "<p>Inserted <strong>{$inserted}</strong> new abilities (skipped " . (count($new_abilities) - $inserted) . " already present).</p>\n";
}

mysqli_close($conn);
