<?php
/**
 * TM-07: Ritual Data Audit - Inventory Script
 * 
 * Discovers and inventories all Necromancy, Thaumaturgy, and Assamite ritual entries
 * in the rituals_master database table.
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_inventory.php
 *   Web: https://vbn.talkingheads.video/database/audit_rituals_inventory.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals Inventory (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Inventory (TM-07)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$rituals = [];

try {
    // Verify table exists
    $tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'rituals_master'");
    if (mysqli_num_rows($tables_check) === 0) {
        die("Error: rituals_master table does not exist.");
    }
    mysqli_free_result($tables_check);
    
    // Query rituals for Necromancy, Thaumaturgy, and Assamite (case-insensitive)
    $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source, created_at
              FROM rituals_master
              WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite')
              ORDER BY type, level, name";
    
    $result = mysqli_query($conn, $query);
    
    if ($result === false) {
        $errors[] = "Query failed: " . mysqli_error($conn);
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            // Normalize type to title case for consistency
            $row['type_normalized'] = ucfirst(strtolower(trim($row['type'])));
            $rituals[] = $row;
        }
        mysqli_free_result($result);
        
        $success[] = "Found " . count($rituals) . " rituals";
    }
    
    // Export to JSON
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-inventory.json';
    $inventory_data = [
        'file_path' => 'database/rituals_master',
        'audit_date' => date('Y-m-d H:i:s'),
        'scope' => ['Necromancy', 'Thaumaturgy', 'Assamite'],
        'total_count' => count($rituals),
        'rituals' => $rituals
    ];
    
    $json_output = json_encode($inventory_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write inventory file: " . $output_file;
    } else {
        $success[] = "Inventory exported to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Inventory (TM-07) ===\n\n";
    
    if (!empty($success)) {
        foreach ($success as $msg) {
            echo "✓ " . $msg . "\n";
        }
    }
    
    if (!empty($errors)) {
        foreach ($errors as $msg) {
            echo "✗ " . $msg . "\n";
        }
    }
    
    if (!empty($rituals)) {
        echo "\n=== Summary by Type ===\n";
        $by_type = [];
        foreach ($rituals as $ritual) {
            $type = $ritual['type_normalized'];
            if (!isset($by_type[$type])) {
                $by_type[$type] = 0;
            }
            $by_type[$type]++;
        }
        foreach ($by_type as $type => $count) {
            echo "  {$type}: {$count} rituals\n";
        }
        
        echo "\n=== Sample Rituals (first 5) ===\n";
        foreach (array_slice($rituals, 0, 5) as $ritual) {
            echo "  ID {$ritual['id']}: {$ritual['name']} ({$ritual['type_normalized']} Level {$ritual['level']})\n";
        }
    }
    
    echo "\n";
} else {
    if (!empty($success)) {
        echo "<div class='success'><ul>";
        foreach ($success as $msg) {
            echo "<li>" . htmlspecialchars($msg) . "</li>";
        }
        echo "</ul></div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='error'><ul>";
        foreach ($errors as $msg) {
            echo "<li>" . htmlspecialchars($msg) . "</li>";
        }
        echo "</ul></div>";
    }
    
    if (!empty($rituals)) {
        echo "<h2>Summary</h2>";
        echo "<p>Total rituals found: <strong>" . count($rituals) . "</strong></p>";
        
        $by_type = [];
        foreach ($rituals as $ritual) {
            $type = $ritual['type_normalized'];
            if (!isset($by_type[$type])) {
                $by_type[$type] = 0;
            }
            $by_type[$type]++;
        }
        
        echo "<h3>By Type</h3><ul>";
        foreach ($by_type as $type => $count) {
            echo "<li><strong>{$type}</strong>: {$count} rituals</li>";
        }
        echo "</ul>";
        
        echo "<h3>Inventory File</h3>";
        echo "<p>Exported to: <code>" . htmlspecialchars($output_file) . "</code></p>";
    }
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

