<?php
require_once __DIR__ . '/../includes/connect.php';

function connect_db() {
    global $conn;
    return $conn;
}

$conn = connect_db();
if (!$conn) {
    die("Database connection failed\n");
}

// Count distinct clans with active characters
$result = db_fetch_one($conn, 
    "SELECT COUNT(DISTINCT clan) as active_clan_count 
     FROM characters 
     WHERE status = 'active' 
     AND clan IS NOT NULL 
     AND clan != '' 
     AND clan != 'N/A'"
);

// Get the list of active clans
$clans = db_fetch_all($conn,
    "SELECT DISTINCT clan
     FROM characters 
     WHERE status = 'active' 
     AND clan IS NOT NULL 
     AND clan != '' 
     AND clan != 'N/A'
     ORDER BY clan ASC"
);

$clan_names = array_column($clans, 'clan');
echo implode(', ', $clan_names);
?>
