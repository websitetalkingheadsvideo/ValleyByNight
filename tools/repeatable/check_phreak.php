<?php
require_once dirname(__DIR__, 2) . '/includes/connect.php';

$result = mysqli_query($conn, "SELECT c.id, c.character_name, c.clan, COUNT(cd.id) as discipline_count 
    FROM characters c 
    LEFT JOIN character_disciplines cd ON c.id = cd.character_id 
    WHERE c.id = 97
    GROUP BY c.id, c.character_name, c.clan");

$row = mysqli_fetch_assoc($result);
if ($row) {
    echo "ID {$row['id']} - {$row['character_name']} ({$row['clan']}): {$row['discipline_count']} disciplines\n";
    if ($row['discipline_count'] > 0) {
        $disc_result = mysqli_query($conn, "SELECT discipline_name, level FROM character_disciplines WHERE character_id = 97");
        while ($disc = mysqli_fetch_assoc($disc_result)) {
            echo "  - {$disc['discipline_name']} x{$disc['level']}\n";
        }
    }
} else {
    echo "Character ID 97 not found\n";
}

