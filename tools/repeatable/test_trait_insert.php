<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$project_root = dirname(__DIR__, 2);
require_once $project_root . '/includes/connect.php';

if (!$conn) {
    die("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

mysqli_set_charset($conn, 'utf8mb4');

$character_id = 107;
$trait_name = "Test Trait";
$trait_category = "Physical";
$trait_type = "positive";

$insert_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, ?)";
$insert_stmt = mysqli_prepare($conn, $insert_sql);

if (!$insert_stmt) {
    die("ERROR: Failed to prepare statement: " . mysqli_error($conn) . "\n");
}

mysqli_stmt_bind_param($insert_stmt, 'isss', $character_id, $trait_name, $trait_category, $trait_type);

if (mysqli_stmt_execute($insert_stmt)) {
    echo "SUCCESS: Trait inserted\n";
} else {
    echo "ERROR: " . mysqli_stmt_error($insert_stmt) . "\n";
}

mysqli_stmt_close($insert_stmt);

