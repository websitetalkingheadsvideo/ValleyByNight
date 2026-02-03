<?php
/**
 * Database Migration: Create werewolf_characters table
 *
 * Creates a separate table for Werewolf: The Apocalypse (LARP / Mind's Eye Theatre)
 * characters without affecting the existing VtM characters or wraith_characters tables.
 *
 * Run via browser: database/create_werewolf_characters_table.php
 * Or via CLI: php database/create_werewolf_characters_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$check_table = "SHOW TABLES LIKE 'werewolf_characters'";
$result = mysqli_query($conn, $check_table);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'werewolf_characters' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS werewolf_characters;</pre>";
    mysqli_free_result($result);
    exit;
}

$create_table_sql = "
CREATE TABLE IF NOT EXISTS werewolf_characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    character_name VARCHAR(255) NOT NULL DEFAULT '',
    player_name VARCHAR(255) DEFAULT NULL,
    chronicle VARCHAR(255) DEFAULT 'Valley by Night',
    nature VARCHAR(100) DEFAULT NULL,
    demeanor VARCHAR(100) DEFAULT NULL,
    concept TEXT DEFAULT NULL,
    breed VARCHAR(50) DEFAULT NULL,
    auspice VARCHAR(50) DEFAULT NULL,
    tribe VARCHAR(100) DEFAULT NULL,
    pack_name VARCHAR(255) DEFAULT NULL,
    pack_totem VARCHAR(255) DEFAULT NULL,
    rank VARCHAR(50) DEFAULT NULL,
    pc TINYINT(1) DEFAULT 1,
    appearance TEXT DEFAULT NULL,
    biography TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    equipment TEXT DEFAULT NULL,
    character_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    rage_permanent INT DEFAULT 0,
    rage_current INT DEFAULT 0,
    gnosis_permanent INT DEFAULT 0,
    gnosis_current INT DEFAULT 0,
    willpower_permanent INT DEFAULT 5,
    willpower_current INT DEFAULT 5,
    attributes JSON DEFAULT NULL,
    abilities JSON DEFAULT NULL,
    forms JSON DEFAULT NULL,
    gifts JSON DEFAULT NULL,
    rites JSON DEFAULT NULL,
    renown JSON DEFAULT NULL,
    backgrounds JSON DEFAULT NULL,
    backgroundDetails JSON DEFAULT NULL,
    merits_flaws JSON DEFAULT NULL,
    touchstones JSON DEFAULT NULL,
    harano_hauglosk JSON DEFAULT NULL,
    health_levels JSON DEFAULT NULL,
    relationships JSON DEFAULT NULL,
    custom_data JSON DEFAULT NULL,
    actingNotes TEXT DEFAULT NULL,
    agentNotes TEXT DEFAULT NULL,
    health_status VARCHAR(255) DEFAULT NULL,
    experience_total INT DEFAULT 0,
    spent_xp INT DEFAULT 0,
    experience_unspent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_character_name (character_name),
    INDEX idx_breed (breed),
    INDEX idx_auspice (auspice),
    INDEX idx_tribe (tribe),
    INDEX idx_pack_name (pack_name),
    INDEX idx_status (status),
    INDEX idx_pc (pc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($conn, $create_table_sql)) {
    echo "<h2>Success: Table 'werewolf_characters' created successfully.</h2>";
    echo "<p>The table includes columns for Werewolf: The Apocalypse LARP characters (breed, auspice, tribe, pack, renown, rage, gnosis, gifts, rites, forms, etc.).</p>";

    echo "<h3>Table Structure:</h3>";
    $describe_sql = "DESCRIBE werewolf_characters";
    $describe_result = mysqli_query($conn, $describe_sql);

    if ($describe_result) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = mysqli_fetch_assoc($describe_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        mysqli_free_result($describe_result);
    }
} else {
    echo "<h2>Error: Failed to create table</h2>";
    echo "<p>Error: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
}

echo "<hr><h3>Rollback (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS werewolf_characters;</pre>";

mysqli_close($conn);
