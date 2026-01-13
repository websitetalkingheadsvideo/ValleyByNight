<?php
/**
 * Add blueprint and moodboard fields to locations table
 * 
 * Adds VARCHAR(255) fields to store paths/URLs to blueprint and moodboard images
 * for locations.
 * 
 * Usage: database/add_location_blueprint_moodboard_fields.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Add Location Blueprint & Moodboard Fields</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;}</style></head><body><h1>Add Location Blueprint & Moodboard Fields</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];

try {
    // Check if blueprint column already exists
    $check_query = "SHOW COLUMNS FROM locations LIKE 'blueprint'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        // Add the blueprint column
        $alter_query = "ALTER TABLE locations ADD COLUMN blueprint VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path/URL to blueprint image'";
        
        if (mysqli_query($conn, $alter_query)) {
            $success[] = "Added blueprint column to locations table";
        } else {
            $errors[] = "Failed to add blueprint column: " . mysqli_error($conn);
        }
    } else {
        $success[] = "Column blueprint already exists";
    }
    
    // Check if moodboard column already exists
    $check_query = "SHOW COLUMNS FROM locations LIKE 'moodboard'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        // Add the moodboard column
        $alter_query = "ALTER TABLE locations ADD COLUMN moodboard VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path/URL to moodboard image'";
        
        if (mysqli_query($conn, $alter_query)) {
            $success[] = "Added moodboard column to locations table";
        } else {
            $errors[] = "Failed to add moodboard column: " . mysqli_error($conn);
        }
    } else {
        $success[] = "Column moodboard already exists";
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
