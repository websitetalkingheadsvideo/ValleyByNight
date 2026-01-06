<?php
/**
 * Database Migration: Create abilities table
 * 
 * This script creates the abilities table and seeds it with all 32 canonical abilities
 * from the VtM character creation system.
 * 
 * Run via browser: database/create_abilities_table.php
 * Or via CLI: php database/create_abilities_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'abilities'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'abilities' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS abilities;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create abilities table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS abilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('Physical', 'Social', 'Mental', 'Optional') NOT NULL,
    display_order INT NOT NULL,
    description TEXT,
    min_level TINYINT DEFAULT 0,
    max_level TINYINT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name_category (name, category),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_table_sql)) {
    echo "<h2>❌ Error: Failed to create table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'abilities' created successfully!</h2>";

// Seed abilities data
$abilities_data = [
    // Physical Abilities (8 total)
    ['Athletics', 'Physical', 1, 'Physical fitness and sports'],
    ['Brawl', 'Physical', 2, 'Unarmed combat'],
    ['Dodge', 'Physical', 3, 'Evading attacks'],
    ['Firearms', 'Physical', 4, 'Ranged weapons'],
    ['Melee', 'Physical', 5, 'Close combat weapons'],
    ['Security', 'Physical', 6, 'Locks, alarms, traps'],
    ['Stealth', 'Physical', 7, 'Hiding and sneaking'],
    ['Survival', 'Physical', 8, 'Wilderness survival'],
    
    // Social Abilities (9 total)
    ['Animal Ken', 'Social', 1, 'Understanding animals'],
    ['Empathy', 'Social', 2, 'Reading emotions'],
    ['Expression', 'Social', 3, 'Artistic expression'],
    ['Intimidation', 'Social', 4, 'Frightening others'],
    ['Leadership', 'Social', 5, 'Commanding others'],
    ['Subterfuge', 'Social', 6, 'Deception and lies'],
    ['Streetwise', 'Social', 7, 'Urban knowledge'],
    ['Etiquette', 'Social', 8, 'Social graces'],
    ['Performance', 'Social', 9, 'Acting, singing, etc.'],
    
    // Mental Abilities (10 total)
    ['Academics', 'Mental', 1, 'Scholarly knowledge'],
    ['Computer', 'Mental', 2, 'Technology and programming'],
    ['Finance', 'Mental', 3, 'Money and economics'],
    ['Investigation', 'Mental', 4, 'Research and deduction'],
    ['Law', 'Mental', 5, 'Legal knowledge'],
    ['Linguistics', 'Mental', 6, 'Languages'],
    ['Medicine', 'Mental', 7, 'Medical knowledge'],
    ['Occult', 'Mental', 8, 'Supernatural knowledge'],
    ['Politics', 'Mental', 9, 'Political systems'],
    ['Science', 'Mental', 10, 'Scientific knowledge'],
    
    // Optional Abilities (5 total)
    ['Alertness', 'Optional', 1, 'General awareness'],
    ['Awareness', 'Optional', 2, 'Supernatural awareness'],
    ['Drive', 'Optional', 3, 'Vehicle operation'],
    ['Crafts', 'Optional', 4, 'Handicrafts and making things'],
    ['Firecraft', 'Optional', 5, 'Fire-related skills'],
];

$insert_sql = "INSERT INTO abilities (name, category, display_order, description, min_level, max_level) VALUES (?, ?, ?, ?, 0, 5)";
$stmt = mysqli_prepare($conn, $insert_sql);

if (!$stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_count = 0;
$errors = [];

foreach ($abilities_data as $ability) {
    mysqli_stmt_bind_param($stmt, 'ssis', $ability[0], $ability[1], $ability[2], $ability[3]);
    
    if (!mysqli_stmt_execute($stmt)) {
        $errors[] = "Failed to insert {$ability[0]}: " . mysqli_stmt_error($stmt);
    } else {
        $inserted_count++;
    }
}

mysqli_stmt_close($stmt);

if (count($errors) > 0) {
    echo "<h3>⚠️ Warnings:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<p>✅ Successfully inserted {$inserted_count} abilities into the table.</p>";

// Show table structure
echo "<h3>Table Structure:</h3>";
$describe_sql = "DESCRIBE abilities";
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

// Show sample data
echo "<h3>Sample Data (first 10 abilities):</h3>";
$sample_sql = "SELECT id, name, category, display_order FROM abilities ORDER BY category, display_order LIMIT 10";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Display Order</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['display_order']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

// Show count by category
echo "<h3>Abilities Count by Category:</h3>";
$count_sql = "SELECT category, COUNT(*) as count FROM abilities GROUP BY category ORDER BY category";
$count_result = mysqli_query($conn, $count_sql);

if ($count_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Category</th><th>Count</th></tr>";
    while ($row = mysqli_fetch_assoc($count_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($count_result);
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS abilities;</pre>";

mysqli_close($conn);
?>

