<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$result = mysqli_query($conn, "SELECT id, character_name, clan, generation FROM characters WHERE id = 151");
$row = mysqli_fetch_assoc($result);

echo "Julien Roche (ID: 151):\n";
echo "Clan: " . ($row['clan'] ?? 'Unknown') . "\n";
echo "Generation: " . ($row['generation'] ?? 'Unknown') . "\n";

