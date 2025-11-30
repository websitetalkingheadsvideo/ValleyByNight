<?php
// db.php - Database connection for VbN Style Agent MCP server
function vbn_get_connection(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = 'vdb5.pit.pair.com';
    $user = 'working_64';
    $pass = 'pcf577#1';
    $db   = 'working_vbn';

    $conn = mysqli_connect($host, $user, $pass, $db);
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    if (!mysqli_set_charset($conn, 'utf8mb4')) {
        throw new Exception('Error setting charset: ' . mysqli_error($conn));
    }

    return $conn;
}

