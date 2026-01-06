<?php
/**
 * Database Migration: Add attributes column to wraith_characters table
 * 
 * This script adds an `attributes` JSON column to store character attributes
 * (Physical, Social, Mental) in the same structure as the Vampire character system.
 * 
 * Usage: Run via web browser or CLI
 * URL: database/add_wraith_attributes_column.php
 * CLI: php database/add_wraith_attributes_column.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

echo "<h1>Wraith Characters Table Migration: Add attributes Column</h1>\n";
echo "<pre>\n";

try {
    // Check if table exists first
    $check_table = "SHOW TABLES LIKE 'wraith_characters'";
    $table_result = mysqli_query($conn, $check_table);
    
    if (!$table_result || mysqli_num_rows($table_result) === 0) {
        echo "❌ Error: Table 'wraith_characters' does not exist.\n";
        echo "Please run create_wraith_characters_table.php first.\n";
        if ($table_result) {
            mysqli_free_result($table_result);
        }
        exit;
    }
    mysqli_free_result($table_result);
    
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM wraith_characters LIKE 'attributes'";
    $result = mysqli_query($conn, $checkSql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "✓ Column 'attributes' already exists. Skipping migration.\n";
        mysqli_free_result($result);
    } else {
        // Add the attributes column
        echo "Adding 'attributes' JSON column to wraith_characters table...\n";
        
        $alterSql = "ALTER TABLE wraith_characters 
                     ADD COLUMN attributes JSON NULL 
                     COMMENT 'Character attributes organized by Physical, Social, and Mental categories' 
                     AFTER negativeTraits";
        
        if (mysqli_query($conn, $alterSql)) {
            echo "✓ Successfully added 'attributes' column.\n";
        } else {
            throw new Exception("Failed to add column: " . mysqli_error($conn));
        }
    }
    
    // Verify the column structure
    echo "\nVerifying column structure...\n";
    $descSql = "DESCRIBE wraith_characters";
    $result = mysqli_query($conn, $descSql);
    
    if ($result) {
        echo "\nCurrent wraith_characters table structure (relevant columns):\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-25s %-20s %-10s %-10s %-15s\n", "Field", "Type", "Null", "Key", "Default");
        echo str_repeat("-", 80) . "\n";
        
        $foundAttributes = false;
        while ($row = mysqli_fetch_assoc($result)) {
            // Show attributes column and surrounding columns for context
            if (in_array($row['Field'], ['negativeTraits', 'attributes', 'abilities'])) {
                printf("%-25s %-20s %-10s %-10s %-15s\n",
                    $row['Field'],
                    $row['Type'],
                    $row['Null'],
                    $row['Key'],
                    $row['Default'] ?? 'NULL'
                );
                if ($row['Field'] === 'attributes') {
                    $foundAttributes = true;
                }
            }
        }
        mysqli_free_result($result);
        
        if ($foundAttributes) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "✓ Migration completed successfully!\n";
            echo "\nThe 'attributes' column is now available to store:\n";
            echo "  - Physical attributes: Strength, Dexterity, Stamina\n";
            echo "  - Social attributes: Charisma, Manipulation, Appearance\n";
            echo "  - Mental attributes: Perception, Intelligence, Wits\n";
            echo "\nData format: {\"Physical\": {\"Strength\": 1, ...}, \"Social\": {...}, \"Mental\": {...}}\n";
        } else {
            echo "\n⚠ Warning: Column was added but not found in verification. Please check manually.\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nRolling back is not necessary - column was not added.\n";
}

echo "</pre>\n";
mysqli_close($conn);
?>

