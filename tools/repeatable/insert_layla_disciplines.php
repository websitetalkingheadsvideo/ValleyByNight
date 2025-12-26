<?php
/**
 * Insert Layla al-Sahr's disciplines based on character teaser
 * Quietus x3, Obfuscate x2, Celerity x2
 */

require_once dirname(__DIR__, 2) . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

$character_id = 107;
$disciplines = [
    ['Quietus', 3],
    ['Obfuscate', 2],
    ['Celerity', 2]
];

// Delete existing disciplines for idempotency
$delete_stmt = mysqli_prepare($conn, "DELETE FROM character_disciplines WHERE character_id = ?");
mysqli_stmt_bind_param($delete_stmt, 'i', $character_id);
mysqli_stmt_execute($delete_stmt);
mysqli_stmt_close($delete_stmt);

// Insert disciplines
$insert_stmt = mysqli_prepare($conn, "INSERT INTO character_disciplines (character_id, discipline_name, level) VALUES (?, ?, ?)");

foreach ($disciplines as $disc) {
    $name = $disc[0];
    $level = $disc[1];
    mysqli_stmt_bind_param($insert_stmt, 'isi', $character_id, $name, $level);
    mysqli_stmt_execute($insert_stmt);
}

mysqli_stmt_close($insert_stmt);

echo "Inserted disciplines for Layla al-Sahr (ID: $character_id):\n";
foreach ($disciplines as $disc) {
    echo "  - {$disc[0]} x{$disc[1]}\n";
}
echo "Done.\n";

