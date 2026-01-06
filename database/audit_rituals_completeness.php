<?php
/**
 * TM-07: Ritual Data Audit - Completeness Check
 * 
 * Checks all Necromancy, Thaumaturgy, and Assamite rituals for completeness
 * of required fields: ingredients, requirements, system_text
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_completeness.php
 *   Web: database/audit_rituals_completeness.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals Completeness Audit (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Completeness Audit (TM-07)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$warnings = [];
$completeness_results = [];

// Audit rules from Rituals Agent definition
$required_fields = [
    'ingredients' => 'Required per agent definition',
    'requirements' => 'Required per agent definition',
    'system_text' => 'Required per agent definition'
];

try {
    // Verify table exists
    $tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'rituals_master'");
    if (mysqli_num_rows($tables_check) === 0) {
        die("Error: rituals_master table does not exist.");
    }
    mysqli_free_result($tables_check);
    
    // Load inventory if available
    $inventory_file = __DIR__ . '/../tmp/TM-07-rituals-inventory.json';
    $rituals = [];
    
    if (file_exists($inventory_file)) {
        $inventory_data = json_decode(file_get_contents($inventory_file), true);
        if (isset($inventory_data['rituals'])) {
            $rituals = $inventory_data['rituals'];
            $success[] = "Loaded " . count($rituals) . " rituals from inventory file";
        }
    }
    
    // If inventory not available, query directly
    if (empty($rituals)) {
        $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source, created_at
                  FROM rituals_master
                  WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite')
                  ORDER BY type, level, name";
        
        $result = mysqli_query($conn, $query);
        
        if ($result === false) {
            $errors[] = "Query failed: " . mysqli_error($conn);
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $rituals[] = $row;
            }
            mysqli_free_result($result);
        }
    }
    
    // Check completeness for each ritual
    $complete_count = 0;
    $incomplete_count = 0;
    
    foreach ($rituals as $ritual) {
        $ritual_id = $ritual['id'];
        $ritual_name = $ritual['name'];
        $ritual_type = $ritual['type'];
        $ritual_level = $ritual['level'];
        
        $missing_fields = [];
        $field_status = [];
        
        // Check each required field
        foreach ($required_fields as $field => $reason) {
            $value = $ritual[$field] ?? null;
            $is_empty = false;
            
            if ($value === null) {
                $is_empty = true;
            } elseif (is_string($value)) {
                $is_empty = trim($value) === '';
            } elseif (is_array($value)) {
                $is_empty = empty($value);
            }
            
            if ($is_empty) {
                $missing_fields[] = $field;
                $field_status[$field] = '❌ Missing';
            } else {
                $field_status[$field] = '✅ Complete';
            }
        }
        
        $is_complete = empty($missing_fields);
        
        if ($is_complete) {
            $complete_count++;
        } else {
            $incomplete_count++;
        }
        
        $completeness_results[] = [
            'id' => $ritual_id,
            'name' => $ritual_name,
            'type' => $ritual_type,
            'level' => $ritual_level,
            'is_complete' => $is_complete,
            'missing_fields' => $missing_fields,
            'field_status' => $field_status
        ];
    }
    
    $success[] = "Audited " . count($rituals) . " rituals";
    $success[] = "Complete: {$complete_count}";
    $warnings[] = "Incomplete: {$incomplete_count}";
    
    // Save results
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-completeness.json';
    $results_data = [
        'audit_date' => date('Y-m-d H:i:s'),
        'total_rituals' => count($rituals),
        'complete_count' => $complete_count,
        'incomplete_count' => $incomplete_count,
        'required_fields' => array_keys($required_fields),
        'results' => $completeness_results
    ];
    
    $json_output = json_encode($results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write completeness results file: " . $output_file;
    } else {
        $success[] = "Results saved to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Completeness Audit (TM-07) ===\n\n";
    
    if (!empty($success)) {
        foreach ($success as $msg) {
            echo "✓ " . $msg . "\n";
        }
    }
    
    if (!empty($warnings)) {
        foreach ($warnings as $msg) {
            echo "⚠ " . $msg . "\n";
        }
    }
    
    if (!empty($errors)) {
        foreach ($errors as $msg) {
            echo "✗ " . $msg . "\n";
        }
    }
    
    if (!empty($completeness_results)) {
        echo "\n=== Incomplete Rituals ===\n";
        $incomplete = array_filter($completeness_results, function($r) { return !$r['is_complete']; });
        
        if (empty($incomplete)) {
            echo "  All rituals are complete!\n";
        } else {
            foreach ($incomplete as $result) {
                echo "  ID {$result['id']}: {$result['name']} ({$result['type']} Level {$result['level']})\n";
                echo "    Missing: " . implode(', ', $result['missing_fields']) . "\n";
            }
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
    
    if (!empty($warnings)) {
        echo "<div class='warning'><ul>";
        foreach ($warnings as $msg) {
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
    
    if (!empty($completeness_results)) {
        $incomplete = array_filter($completeness_results, function($r) { return !$r['is_complete']; });
        
        echo "<h2>Incomplete Rituals</h2>";
        if (empty($incomplete)) {
            echo "<p class='success'>All rituals are complete!</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Level</th><th>Missing Fields</th></tr>";
            foreach ($incomplete as $result) {
                echo "<tr>";
                echo "<td>{$result['id']}</td>";
                echo "<td>" . htmlspecialchars($result['name']) . "</td>";
                echo "<td>{$result['type']}</td>";
                echo "<td>{$result['level']}</td>";
                echo "<td>" . htmlspecialchars(implode(', ', $result['missing_fields'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

