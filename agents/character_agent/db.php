<?php
declare(strict_types=1);

// db.php - Shared database connection for VbN Character MCP server
function vbn_get_connection(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    require __DIR__ . '/../../includes/connect.php';
    if (!$conn instanceof mysqli) {
        throw new RuntimeException('Database connection is not initialized.');
    }

    return $conn;
}
