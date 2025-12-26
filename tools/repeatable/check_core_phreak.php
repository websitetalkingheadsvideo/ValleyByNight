<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$ids = [95, 97];
$result = mysqli_query($conn, "SELECT c.id, c.character_name, c.clan, COUNT(cd.id) as discipline_count 
    FROM characters c 
    LEFT JOIN character_disciplines cd ON c.id = cd.character_id 
    WHERE c.id IN (" . implode(',', $ids) . ")
    GROUP BY c.id, c.character_name, c.clan
    ORDER BY c.id");

while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['discipline_count'] > 0 ? 'HAS' : 'MISSING';
    echo "ID {$row['id']} - {$row['character_name']} ({$row['clan']}): {$status} ({$row['discipline_count']} disciplines)\n";
}

