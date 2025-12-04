<?php
/**
 * Add pc_haven field to locations table
 * 
 * Adds a TINYINT(1) field to identify havens that are possible PC havens.
 * Marks existing havens in the database as PC havens.
 * 
 * Usage: https://vbn.talkingheads.video/database/add_pc_haven_field.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Add PC Haven Field</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;}</style></head><body><h1>Add PC Haven Field</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];

try {
    // Check if column already exists
    $check_query = "SHOW COLUMNS FROM locations LIKE 'pc_haven'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        // Add the column
        $alter_query = "ALTER TABLE locations ADD COLUMN pc_haven TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is this a possible PC haven?'";
        
        if (mysqli_query($conn, $alter_query)) {
            $success[] = "Added pc_haven column to locations table";
        } else {
            $errors[] = "Failed to add column: " . mysqli_error($conn);
        }
    } else {
        $success[] = "Column pc_haven already exists";
    }
    
    // Update existing havens to pc_haven = 1
    // Only update locations where type = 'Haven'
    $update_query = "UPDATE locations SET pc_haven = 1 WHERE type = 'Haven'";
    
    if (mysqli_query($conn, $update_query)) {
        $affected = mysqli_affected_rows($conn);
        $success[] = "Updated $affected havens to pc_haven = 1";
    } else {
        $errors[] = "Failed to update havens: " . mysqli_error($conn);
    }
    
} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}

// Display results
if ($is_cli) {
    echo "\n=== Migration Results ===\n";
    foreach ($success as $msg) {
        echo "✓ $msg\n";
    }
    foreach ($errors as $msg) {
        echo "✗ $msg\n";
    }
} else {
    echo "<h2>Migration Results</h2>";
    if (!empty($success)) {
        echo "<ul>";
        foreach ($success as $msg) {
            echo "<li class='success'>✓ $msg</li>";
        }
        echo "</ul>";
    }
    if (!empty($errors)) {
        echo "<ul>";
        foreach ($errors as $msg) {
            echo "<li class='error'>✗ $msg</li>";
        }
        echo "</ul>";
    }
    echo "</body></html>";
}

mysqli_close($conn);

