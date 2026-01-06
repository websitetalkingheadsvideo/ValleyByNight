<?php
/**
 * Database Migration: Create Nature_Demeanor lookup table
 * 
 * This script creates the Nature_Demeanor table and seeds it with all 28 canonical
 * Nature/Demeanor archetypes from the VtM character creation system.
 * 
 * Run via browser: database/create_nature_demeanor_table.php
 * Or via CLI: php database/create_nature_demeanor_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'Nature_Demeanor'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'Nature_Demeanor' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS Nature_Demeanor;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create Nature_Demeanor table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS Nature_Demeanor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_order INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_table_sql)) {
    echo "<h2>❌ Error: Failed to create table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'Nature_Demeanor' created successfully!</h2>";

// Seed Nature/Demeanor data (alphabetically ordered)
$nature_demeanor_data = [
    ['Architect', 1, 'One who builds structures, systems, or organizations'],
    ['Autist', 2, 'One who focuses intensely on specific interests or patterns'],
    ['Bon Vivant', 3, 'One who enjoys life and its pleasures'],
    ['Bravo', 4, 'One who uses intimidation and violence'],
    ['Caregiver', 5, 'One who nurtures and protects others'],
    ['Capitalist', 6, 'One who values wealth and commerce'],
    ['Competitor', 7, 'One who seeks to win and excel'],
    ['Conformist', 8, 'One who follows established rules and norms'],
    ['Conniver', 9, 'One who manipulates through schemes'],
    ['Curmudgeon', 10, 'One who is grumpy and pessimistic'],
    ['Deviant', 11, 'One who rejects social norms'],
    ['Director', 12, 'One who leads and coordinates'],
    ['Fanatic', 13, 'One who is obsessively devoted to a cause'],
    ['Gallant', 14, 'One who is chivalrous and honorable'],
    ['Judge', 15, 'One who evaluates and passes judgment'],
    ['Loner', 16, 'One who prefers solitude'],
    ['Martyr', 17, 'One who sacrifices for others'],
    ['Masochist', 18, 'One who endures pain or suffering'],
    ['Monster', 19, 'One who embraces their dark nature'],
    ['Pedagogue', 20, 'One who teaches and educates'],
    ['Penitent', 21, 'One who seeks redemption'],
    ['Perfectionist', 22, 'One who demands flawlessness'],
    ['Rebel', 23, 'One who resists authority'],
    ['Rogue', 24, 'One who operates outside the law'],
    ['Survivor', 25, 'One who endures and adapts'],
    ['Thrill-Seeker', 26, 'One who pursues excitement and danger'],
    ['Traditionalist', 27, 'One who values established customs'],
    ['Visionary', 28, 'One who sees future possibilities'],
];

$insert_sql = "INSERT INTO Nature_Demeanor (name, display_order, description) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);

if (!$stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_count = 0;
$errors = [];

foreach ($nature_demeanor_data as $archetype) {
    mysqli_stmt_bind_param($stmt, 'sis', $archetype[0], $archetype[1], $archetype[2]);
    
    if (!mysqli_stmt_execute($stmt)) {
        $errors[] = "Failed to insert {$archetype[0]}: " . mysqli_stmt_error($stmt);
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

echo "<p>✅ Successfully inserted {$inserted_count} Nature/Demeanor archetypes into the table.</p>";

// Show table structure
echo "<h3>Table Structure:</h3>";
$describe_sql = "DESCRIBE Nature_Demeanor";
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

// Show all data
echo "<h3>All Nature/Demeanor Archetypes:</h3>";
$sample_sql = "SELECT id, name, display_order, description FROM Nature_Demeanor ORDER BY display_order";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Order</th><th>Description</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['display_order']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS Nature_Demeanor;</pre>";

mysqli_close($conn);
?>

