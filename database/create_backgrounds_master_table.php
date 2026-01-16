<?php
/**
 * Database Migration: Create backgrounds_master lookup table
 * 
 * This script creates the backgrounds_master table and seeds it with all canonical
 * Background types from the VtM character creation system.
 * 
 * Run via browser: database/create_backgrounds_master_table.php
 * Or via CLI: php database/create_backgrounds_master_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'backgrounds_master'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'backgrounds_master' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS backgrounds_master;</pre>";
    mysqli_free_result($result);
    exit;
}

// Create backgrounds_master table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS backgrounds_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_order INT NOT NULL,
    description TEXT,
    max_level INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_table_sql)) {
    echo "<h2>❌ Error: Failed to create table</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<pre>" . htmlspecialchars($create_table_sql) . "</pre>";
    mysqli_close($conn);
    exit;
}

echo "<h2>✅ Success: Table 'backgrounds_master' created successfully!</h2>";

// Seed Background data (standard VtM backgrounds)
$backgrounds_data = [
    ['Allies', 1, 'You have friends who can help you.', 5],
    ['Contacts', 2, 'You know people in various places.', 5],
    ['Influence', 3, 'You have political or social influence.', 5],
    ['Mentor', 4, 'You have a wise teacher.', 5],
    ['Resources', 5, 'You have money and material wealth.', 5],
    ['Retainers', 6, 'You have loyal servants.', 5],
    ['Status', 7, 'You have social standing.', 5],
];

$insert_sql = "INSERT INTO backgrounds_master (name, display_order, description, max_level) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);

if (!$stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$inserted_count = 0;
$errors = [];

foreach ($backgrounds_data as $background) {
    mysqli_stmt_bind_param($stmt, 'sisi', $background[0], $background[1], $background[2], $background[3]);
    
    if (!mysqli_stmt_execute($stmt)) {
        $errors[] = "Failed to insert {$background[0]}: " . mysqli_stmt_error($stmt);
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

echo "<p>✅ Successfully inserted {$inserted_count} backgrounds into the table.</p>";

// Show table structure
echo "<h3>Table Structure:</h3>";
$describe_sql = "DESCRIBE backgrounds_master";
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
echo "<h3>All Backgrounds:</h3>";
$sample_sql = "SELECT id, name, display_order, description, max_level FROM backgrounds_master ORDER BY display_order";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Order</th><th>Description</th><th>Max Level</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['display_order']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['max_level']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>DROP TABLE IF EXISTS backgrounds_master;</pre>";

mysqli_close($conn);
?>
