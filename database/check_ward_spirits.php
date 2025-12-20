<?php
require_once __DIR__ . '/../includes/connect.php';

// Check for "Ward vs Spirits" matches
$q = "SELECT id, name, type, level FROM rituals_master WHERE LOWER(name) LIKE '%ward%' AND LOWER(name) LIKE '%spirit%' AND LOWER(TRIM(type)) = 'thaumaturgy' ORDER BY id";
$r = mysqli_query($conn, $q);
echo "Rituals with 'ward' and 'spirit':\n";
while($row = mysqli_fetch_assoc($r)) {
    echo "  {$row['id']}: {$row['name']} (Level {$row['level']})\n";
}

// Check normalized
$q2 = "SELECT id, name, type, level, LOWER(REPLACE(REPLACE(REPLACE(TRIM(name), ' vs ', ' versus '), ' vs. ', ' versus '), ' v ', ' versus ')) as normalized FROM rituals_master WHERE LOWER(TRIM(type)) = 'thaumaturgy' AND (LOWER(name) LIKE '%ward%' OR LOWER(name) LIKE '%spirit%') ORDER BY id";
$r2 = mysqli_query($conn, $q2);
echo "\nNormalized names:\n";
while($row = mysqli_fetch_assoc($r2)) {
    echo "  {$row['id']}: '{$row['name']}' -> normalized: '{$row['normalized']}'\n";
}

// Check what "Ward vs Spirits" normalizes to
$test = "Ward vs Spirits";
$normalized = strtolower(str_replace([' vs ', ' vs. ', ' v '], ' versus ', trim($test)));
echo "\n'{$test}' normalizes to: '{$normalized}'\n";

mysqli_close($conn);
?>



