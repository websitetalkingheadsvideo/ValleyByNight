<?php
/**
 * Replace mage_characters with full schema (drops FKs, drops table, recreates).
 * Use when current mage_characters is the thin overlay and you need the full Mage sheet table.
 * Run once via browser or CLI: php database/replace_mage_characters_table.php
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$db = mysqli_real_escape_string($conn, defined('DB_NAME') ? DB_NAME : '');
if ($db === '') {
    $r = mysqli_query($conn, "SELECT DATABASE() AS d");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $db = $row['d'];
    }
    mysqli_free_result($r);
}

$fkResult = mysqli_query($conn,
    "SELECT CONSTRAINT_NAME, TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE 
     WHERE REFERENCED_TABLE_SCHEMA = '" . $db . "' AND REFERENCED_TABLE_NAME = 'mage_characters'"
);

if ($fkResult && mysqli_num_rows($fkResult) > 0) {
    while ($row = mysqli_fetch_assoc($fkResult)) {
        $tbl = $row['TABLE_NAME'];
        $cn = $row['CONSTRAINT_NAME'];
        $sql = "ALTER TABLE `" . mysqli_real_escape_string($conn, $tbl) . "` DROP FOREIGN KEY `" . mysqli_real_escape_string($conn, $cn) . "`";
        if (!mysqli_query($conn, $sql)) {
            die("Failed to drop FK {$cn}: " . mysqli_error($conn));
        }
    }
    mysqli_free_result($fkResult);
}

if (!mysqli_query($conn, "DROP TABLE IF EXISTS mage_characters")) {
    die("Failed to drop mage_characters: " . mysqli_error($conn));
}

$create_table_sql = "
CREATE TABLE mage_characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    character_name VARCHAR(255) NOT NULL DEFAULT '',
    player_name VARCHAR(255) DEFAULT NULL,
    chronicle VARCHAR(255) DEFAULT 'Valley by Night',
    nature VARCHAR(100) DEFAULT NULL,
    demeanor VARCHAR(100) DEFAULT NULL,
    concept TEXT DEFAULT NULL,
    tradition VARCHAR(100) DEFAULT NULL,
    paradigm TEXT DEFAULT NULL,
    practice VARCHAR(255) DEFAULT NULL,
    instruments JSON DEFAULT NULL,
    pc TINYINT(1) DEFAULT 1,
    appearance TEXT DEFAULT NULL,
    biography TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    equipment TEXT DEFAULT NULL,
    character_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    arete INT DEFAULT 1,
    willpower_permanent INT DEFAULT 5,
    willpower_current INT DEFAULT 5,
    quintessence JSON DEFAULT NULL,
    paradox JSON DEFAULT NULL,
    spheres JSON DEFAULT NULL,
    attributes JSON DEFAULT NULL,
    abilities JSON DEFAULT NULL,
    rotes JSON DEFAULT NULL,
    backgrounds JSON DEFAULT NULL,
    backgroundDetails JSON DEFAULT NULL,
    merits_flaws JSON DEFAULT NULL,
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
    INDEX idx_tradition (tradition),
    INDEX idx_status (status),
    INDEX idx_pc (pc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_table_sql)) {
    die("Failed to create mage_characters: " . mysqli_error($conn));
}

echo "Done: mage_characters dropped and recreated with full schema. You can run the Mage import now.";
mysqli_close($conn);
