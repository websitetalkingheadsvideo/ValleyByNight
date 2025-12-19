<?php
/**
 * TM-07: Ritual Data Audit - QA Gates
 * 
 * Runs validation checks to ensure:
 * - No regressions in existing Rituals Agent queries
 * - Foreign key integrity maintained (character_rituals.ritual_id)
 * - No SQL errors or constraint violations
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_qa_gates.php
 *   Web: https://vbn.talkingheads.video/database/audit_rituals_qa_gates.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals QA Gates (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals QA Gates (TM-07)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$warnings = [];
$qa_results = [];

try {
    // Check 1: Verify rituals_master table structure
    $success[] = "Checking rituals_master table structure...";
    
    $columns_query = "SHOW COLUMNS FROM rituals_master";
    $columns_result = mysqli_query($conn, $columns_query);
    
    if ($columns_result === false) {
        $errors[] = "Failed to query rituals_master columns: " . mysqli_error($conn);
    } else {
        $expected_columns = ['id', 'name', 'type', 'level', 'description', 'system_text', 'requirements', 'ingredients', 'source', 'created_at'];
        $actual_columns = [];
        
        while ($row = mysqli_fetch_assoc($columns_result)) {
            $actual_columns[] = $row['Field'];
        }
        mysqli_free_result($columns_result);
        
        $missing_columns = array_diff($expected_columns, $actual_columns);
        if (empty($missing_columns)) {
            $success[] = "✓ All required columns present in rituals_master";
            $qa_results['table_structure'] = 'PASS';
        } else {
            $errors[] = "✗ Missing columns: " . implode(', ', $missing_columns);
            $qa_results['table_structure'] = 'FAIL';
        }
    }
    
    // Check 2: Verify foreign key integrity (character_rituals.ritual_id)
    $success[] = "Checking foreign key integrity...";
    
    $fk_check_query = "
        SELECT COUNT(*) as invalid_count
        FROM character_rituals cr
        LEFT JOIN rituals_master rm ON cr.ritual_id = rm.id
        WHERE cr.ritual_id IS NOT NULL AND rm.id IS NULL
    ";
    
    $fk_result = mysqli_query($conn, $fk_check_query);
    if ($fk_result === false) {
        $errors[] = "Failed to check foreign key integrity: " . mysqli_error($conn);
        $qa_results['fk_integrity'] = 'ERROR';
    } else {
        $row = mysqli_fetch_assoc($fk_result);
        $invalid_count = (int)$row['invalid_count'];
        mysqli_free_result($fk_result);
        
        if ($invalid_count === 0) {
            $success[] = "✓ Foreign key integrity maintained (all ritual_id references valid)";
            $qa_results['fk_integrity'] = 'PASS';
        } else {
            $warnings[] = "⚠ Found {$invalid_count} character_rituals rows with invalid ritual_id references";
            $qa_results['fk_integrity'] = 'WARN';
        }
    }
    
    // Check 3: Test basic queries (no errors)
    $success[] = "Testing basic queries...";
    
    $test_queries = [
        'count_all' => "SELECT COUNT(*) as count FROM rituals_master WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite')",
        'count_by_type' => "SELECT type, COUNT(*) as count FROM rituals_master WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite') GROUP BY type",
        'sample_ritual' => "SELECT * FROM rituals_master WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite') LIMIT 1"
    ];
    
    $query_results = [];
    foreach ($test_queries as $name => $query) {
        $result = mysqli_query($conn, $query);
        if ($result === false) {
            $errors[] = "Query '{$name}' failed: " . mysqli_error($conn);
            $query_results[$name] = 'FAIL';
        } else {
            mysqli_free_result($result);
            $query_results[$name] = 'PASS';
        }
    }
    
    if (empty(array_filter($query_results, function($r) { return $r === 'FAIL'; }))) {
        $success[] = "✓ All test queries executed successfully";
        $qa_results['basic_queries'] = 'PASS';
    } else {
        $errors[] = "✗ Some test queries failed";
        $qa_results['basic_queries'] = 'FAIL';
    }
    
    // Check 4: Verify unique constraint on (type, level, name)
    $success[] = "Checking unique constraint...";
    
    $unique_check_query = "
        SELECT type, level, name, COUNT(*) as count
        FROM rituals_master
        WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite')
        GROUP BY type, level, name
        HAVING count > 1
    ";
    
    $unique_result = mysqli_query($conn, $unique_check_query);
    if ($unique_result === false) {
        $errors[] = "Failed to check unique constraint: " . mysqli_error($conn);
        $qa_results['unique_constraint'] = 'ERROR';
    } else {
        $duplicate_count = mysqli_num_rows($unique_result);
        mysqli_free_result($unique_result);
        
        if ($duplicate_count === 0) {
            $success[] = "✓ Unique constraint maintained (no duplicate type/level/name combinations)";
            $qa_results['unique_constraint'] = 'PASS';
        } else {
            $warnings[] = "⚠ Found {$duplicate_count} duplicate type/level/name combinations";
            $qa_results['unique_constraint'] = 'WARN';
        }
    }
    
    // Check 5: Test Rituals Agent still works
    $success[] = "Testing Rituals Agent functionality...";
    
    require_once __DIR__ . '/../agents/rituals_agent/src/RitualsAgent.php';
    
    try {
        $agent = new RitualsAgent($conn);
        $test_rituals = $agent->listRituals(null, null, false, 5, 0);
        
        if (is_array($test_rituals) && count($test_rituals) > 0) {
            $success[] = "✓ Rituals Agent listRituals() working correctly";
            $qa_results['agent_functionality'] = 'PASS';
        } else {
            $warnings[] = "⚠ Rituals Agent listRituals() returned empty or invalid result";
            $qa_results['agent_functionality'] = 'WARN';
        }
    } catch (Exception $e) {
        $errors[] = "✗ Rituals Agent test failed: " . $e->getMessage();
        $qa_results['agent_functionality'] = 'FAIL';
    }
    
    // Summary
    $total_checks = count($qa_results);
    $passed_checks = count(array_filter($qa_results, function($r) { return $r === 'PASS'; }));
    $failed_checks = count(array_filter($qa_results, function($r) { return $r === 'FAIL'; }));
    $warned_checks = count(array_filter($qa_results, function($r) { return $r === 'WARN'; }));
    
    $success[] = "QA Gates Summary: {$passed_checks} passed, {$warned_checks} warnings, {$failed_checks} failed out of {$total_checks} checks";
    
    // Save results
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-qa-gates.json';
    $results_data = [
        'audit_date' => date('Y-m-d H:i:s'),
        'total_checks' => $total_checks,
        'passed' => $passed_checks,
        'warnings' => $warned_checks,
        'failed' => $failed_checks,
        'results' => $qa_results
    ];
    
    $json_output = json_encode($results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write QA gates results file: " . $output_file;
    } else {
        $success[] = "Results saved to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals QA Gates (TM-07) ===\n\n";
    
    if (!empty($success)) {
        foreach ($success as $msg) {
            echo $msg . "\n";
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
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

