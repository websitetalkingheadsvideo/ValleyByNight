<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$ids = [89, 90, 92, 102, 107, 112, 113, 125, 131, 151];
$result = mysqli_query($conn, "SELECT c.id, c.character_name, COUNT(cd.id) as discipline_count 
    FROM characters c 
    LEFT JOIN character_disciplines cd ON c.id = cd.character_id 
    WHERE c.id IN (" . implode(',', $ids) . ")
    GROUP BY c.id, c.character_name
    ORDER BY c.id");

echo "Discipline Status:\n";
while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['discipline_count'] > 0 ? 'HAS' : 'MISSING';
    echo "ID {$row['id']} - {$row['character_name']}: {$status} ({$row['discipline_count']} disciplines)\n";
}

