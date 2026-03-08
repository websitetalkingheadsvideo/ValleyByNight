<?php
/**
 * Database Migration: Create character_blood_drinks table
 *
 * Event-based tracking of blood drinking events. Bond stage is derived at read time,
 * never stored. Used by blood_bonds_agent for narrative context.
 *
 * Run via browser: database/create_character_blood_drinks_table.php
 * Or via CLI: php database/create_character_blood_drinks_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

$table = 'character_blood_drinks';
$check = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
if ($check && mysqli_num_rows($check) > 0) {
    $is_cli = (php_sapi_name() === 'cli');
    if ($is_cli) {
        echo "Table '{$table}' already exists.\n";
    } else {
        echo "<h2>Table '{$table}' already exists.</h2>";
        echo "<p>If you want to recreate it, drop it first:</p>";
        echo "<pre>DROP TABLE IF EXISTS {$table};</pre>";
    }
    mysqli_free_result($check);
    exit;
}

$sql = "
CREATE TABLE character_blood_drinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drinker_character_id INT NOT NULL,
    source_character_id INT NOT NULL,
    drink_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_drinker_source (drinker_character_id, source_character_id),
    INDEX idx_drink_date (drink_date),
    FOREIGN KEY (drinker_character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (source_character_id) REFERENCES characters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $sql)) {
    $is_cli = (php_sapi_name() === 'cli');
    if ($is_cli) {
        echo "Error: " . mysqli_error($conn) . "\n";
    } else {
        echo "<h2>Error creating table</h2><p>" . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8') . "</p>";
    }
    exit;
}

$is_cli = (php_sapi_name() === 'cli');
if ($is_cli) {
    echo "Table '{$table}' created successfully.\n";
} else {
    echo "<h2>Table '{$table}' created successfully.</h2>";
}
