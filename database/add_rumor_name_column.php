<?php
/**
 * Database Migration: Add rumor_name column to rumors table
 * 
 * This script adds a `rumor_name` VARCHAR column to store the string identifier
 * for rumors (e.g., "rumor_lilith_selective_visions") while keeping `id` as
 * the auto-increment integer primary key.
 * 
 * Usage: Run via web browser or CLI
 * URL: database/add_rumor_name_column.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

echo "<h1>Rumor Table Migration: Add rumor_name Column</h1>\n";
echo "<pre>\n";

try {
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM rumors LIKE 'rumor_name'";
    $result = mysqli_query($conn, $checkSql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "✓ Column 'rumor_name' already exists. Skipping migration.\n";
        mysqli_free_result($result);
    } else {
        // Add the rumor_name column
        echo "Adding 'rumor_name' column to rumors table...\n";
        
        $alterSql = "ALTER TABLE rumors 
                     ADD COLUMN rumor_name VARCHAR(255) NULL 
                     COMMENT 'String identifier for the rumor (e.g., rumor_lilith_selective_visions)' 
                     AFTER id";
        
        if (mysqli_query($conn, $alterSql)) {
            echo "✓ Successfully added 'rumor_name' column.\n";
            
            // Add index for faster lookups by rumor_name
            echo "Adding index on 'rumor_name' column...\n";
            $indexSql = "CREATE INDEX idx_rumor_name ON rumors(rumor_name)";
            
            if (mysqli_query($conn, $indexSql)) {
                echo "✓ Successfully added index on 'rumor_name'.\n";
            } else {
                echo "⚠ Warning: Could not create index (may already exist): " . mysqli_error($conn) . "\n";
            }
        } else {
            throw new Exception("Failed to add column: " . mysqli_error($conn));
        }
    }
    
    // Verify the column structure
    echo "\nVerifying column structure...\n";
    $descSql = "DESCRIBE rumors";
    $result = mysqli_query($conn, $descSql);
    
    if ($result) {
        echo "\nCurrent rumors table structure:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-20s %-10s %-10s %-10s\n", "Field", "Type", "Null", "Key", "Default");
        echo str_repeat("-", 80) . "\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            printf("%-20s %-20s %-10s %-10s %-10s\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key'],
                $row['Default'] ?? 'NULL'
            );
        }
        mysqli_free_result($result);
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✓ Migration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Import rumors using the updated JSON format (with rumor_name field)\n";
    echo "2. The 'id' field will auto-increment for new rumors\n";
    echo "3. Use 'rumor_name' to reference rumors by their string identifier\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nRolling back is not necessary - column was not added.\n";
}

echo "</pre>\n";
mysqli_close($conn);
?>

