<?php
/**
 * Database Migration: Create wraith_characters table
 * 
 * This script creates a separate table for Wraith: The Oblivion characters
 * without affecting the existing VtM characters table.
 * 
 * Run via browser: https://vbn.talkingheads.video/database/create_wraith_characters_table.php
 * Or via CLI: php database/create_wraith_characters_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'wraith_characters'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'wraith_characters' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS wraith_characters;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create wraith_characters table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS wraith_characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    character_name VARCHAR(255) NOT NULL DEFAULT '',
    shadow_name VARCHAR(255) DEFAULT NULL,
    player_name VARCHAR(255) DEFAULT NULL,
    chronicle VARCHAR(255) DEFAULT 'Valley by Night',
    nature VARCHAR(100) DEFAULT NULL,
    demeanor VARCHAR(100) DEFAULT NULL,
    concept TEXT DEFAULT NULL,
    circle VARCHAR(100) DEFAULT NULL,
    guild VARCHAR(100) DEFAULT NULL,
    legion_at_death VARCHAR(100) DEFAULT NULL,
    date_of_death DATE DEFAULT NULL,
    cause_of_death TEXT DEFAULT NULL,
    pc TINYINT(1) DEFAULT 1,
    appearance TEXT DEFAULT NULL,
    ghostly_appearance TEXT DEFAULT NULL,
    biography TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    equipment TEXT DEFAULT NULL,
    character_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    timeline JSON DEFAULT NULL,
    personality JSON DEFAULT NULL,
    traits JSON DEFAULT NULL,
    negativeTraits JSON DEFAULT NULL,
    abilities JSON DEFAULT NULL,
    specializations JSON DEFAULT NULL,
    fetters JSON DEFAULT NULL,
    passions JSON DEFAULT NULL,
    arcanoi JSON DEFAULT NULL,
    backgrounds JSON DEFAULT NULL,
    backgroundDetails JSON DEFAULT NULL,
    willpower_permanent INT DEFAULT 5,
    willpower_current INT DEFAULT 5,
    pathos_corpus JSON DEFAULT NULL,
    shadow JSON DEFAULT NULL,
    harrowing JSON DEFAULT NULL,
    merits_flaws JSON DEFAULT NULL,
    status_details JSON DEFAULT NULL,
    relationships JSON DEFAULT NULL,
    artifacts JSON DEFAULT NULL,
    custom_data JSON DEFAULT NULL,
    actingNotes TEXT DEFAULT NULL,
    agentNotes TEXT DEFAULT NULL,
    health_status VARCHAR(255) DEFAULT NULL,
    experience_total INT DEFAULT 0,
    spent_xp INT DEFAULT 0,
    experience_unspent INT DEFAULT 0,
    shadow_xp_total INT DEFAULT 0,
    shadow_xp_spent INT DEFAULT 0,
    shadow_xp_available INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_character_name (character_name),
    INDEX idx_shadow_name (shadow_name),
    INDEX idx_guild (guild),
    INDEX idx_circle (circle),
    INDEX idx_status (status),
    INDEX idx_pc (pc),
    INDEX idx_date_of_death (date_of_death)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($conn, $create_table_sql)) {
    echo "<h2>✅ Success: Table 'wraith_characters' created successfully!</h2>";
    echo "<p>The table includes all required columns for Wraith: The Oblivion characters.</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $describe_sql = "DESCRIBE wraith_characters";
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
    echo "<h2>❌ Error: Failed to create table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS wraith_characters;</pre>";

mysqli_close($conn);
?>

