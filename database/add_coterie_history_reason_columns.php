<?php
/**
 * Database Migration: Add History and Reason columns to coteries table
 * 
 * This script adds 'history' and 'reason' TEXT columns to the coteries table.
 * 
 * Run via browser: database/add_coterie_history_reason_columns.php
 * Or via CLI: php database/add_coterie_history_reason_columns.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<h2>Adding History and Reason columns to coteries table</h2>";

// Check if history column exists
$checkHistory = mysqli_query($conn, "SHOW COLUMNS FROM coteries LIKE 'history'");
$hasHistory = ($checkHistory && mysqli_num_rows($checkHistory) > 0);

if (!$hasHistory) {
    $addHistorySql = "ALTER TABLE coteries ADD COLUMN history TEXT DEFAULT NULL";
    if (mysqli_query($conn, $addHistorySql)) {
        echo "<p>✅ Success: Added 'history' column to coteries table</p>";
    } else {
        echo "<p>❌ Error: Failed to add 'history' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Info: 'history' column already exists</p>";
}

// Check if reason column exists
$checkReason = mysqli_query($conn, "SHOW COLUMNS FROM coteries LIKE 'reason'");
$hasReason = ($checkReason && mysqli_num_rows($checkReason) > 0);

if (!$hasReason) {
    $addReasonSql = "ALTER TABLE coteries ADD COLUMN reason TEXT DEFAULT NULL";
    if (mysqli_query($conn, $addReasonSql)) {
        echo "<p>✅ Success: Added 'reason' column to coteries table</p>";
    } else {
        echo "<p>❌ Error: Failed to add 'reason' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Info: 'reason' column already exists</p>";
}

if ($hasHistory && $hasReason) {
    echo "<h3>✅ Migration complete: Both columns already exist</h3>";
} else {
    echo "<h3>✅ Migration complete</h3>";
}

mysqli_close($conn);
?>
