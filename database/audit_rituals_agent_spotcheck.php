<?php
/**
 * TM-07: Ritual Data Audit - Rituals Agent Spot-Check
 * 
 * Executes spot-checks using the Rituals Agent to verify:
 * - Complete field retrieval (all required fields present)
 * - Correct source serialization
 * - No duplicate results for same ritual
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_agent_spotcheck.php
 *   Web: database/audit_rituals_agent_spotcheck.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals Agent Spot-Check (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Agent Spot-Check (TM-07)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Load Rituals Agent
require_once __DIR__ . '/../agents/rituals_agent/src/RitualsAgent.php';

$errors = [];
$success = [];
$warnings = [];
$spotcheck_results = [];

// Required fields from agent definition
$required_fields = ['id', 'name', 'type', 'level', 'description', 'system_text', 'requirements', 'ingredients', 'source'];

try {
    // Initialize agent
    $agent = new RitualsAgent($conn);
    $success[] = "Rituals Agent initialized";
    
    // Load inventory to get sample rituals
    $inventory_file = __DIR__ . '/../tmp/TM-07-rituals-inventory.json';
    $rituals = [];
    
    if (file_exists($inventory_file)) {
        $inventory_data = json_decode(file_get_contents($inventory_file), true);
        if (isset($inventory_data['rituals'])) {
            $rituals = $inventory_data['rituals'];
            $success[] = "Loaded " . count($rituals) . " rituals from inventory";
        }
    }
    
    // If no inventory, query directly for samples
    if (empty($rituals)) {
        $query = "SELECT id, name, type, level
                  FROM rituals_master
                  WHERE LOWER(TRIM(type)) IN ('necromancy', 'thaumaturgy', 'assamite')
                  ORDER BY type, level, name
                  LIMIT 10";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rituals[] = $row;
            }
            mysqli_free_result($result);
        }
    }
    
    // Select 5-10 sample rituals across different types and levels
    $samples = [];
    $by_type = [];
    foreach ($rituals as $ritual) {
        $type = strtolower(trim($ritual['type']));
        if (!isset($by_type[$type])) {
            $by_type[$type] = [];
        }
        $by_type[$type][] = $ritual;
    }
    
    // Get 2-3 samples from each type
    foreach ($by_type as $type => $type_rituals) {
        $samples = array_merge($samples, array_slice($type_rituals, 0, min(3, count($type_rituals))));
    }
    
    $samples = array_slice($samples, 0, 10);
    $success[] = "Selected " . count($samples) . " sample rituals for spot-check";
    
    // Perform spot-checks
    $checks_passed = 0;
    $checks_failed = 0;
    
    foreach ($samples as $sample) {
        $ritual_id = $sample['id'];
        $ritual_name = $sample['name'];
        $ritual_type = $sample['type'];
        $ritual_level = $sample['level'];
        
        $check_result = [
            'ritual_id' => $ritual_id,
            'ritual_name' => $ritual_name,
            'ritual_type' => $ritual_type,
            'ritual_level' => $ritual_level,
            'checks' => []
        ];
        
        // Check 1: getRitualById with rules
        try {
            $ritual = $agent->getRitualById($ritual_id, true);
            
            if ($ritual === null) {
                $check_result['checks'][] = [
                    'test' => 'getRitualById (with rules)',
                    'status' => 'FAIL',
                    'message' => 'Ritual not found'
                ];
                $checks_failed++;
            } else {
                // Verify all required fields present
                $missing_fields = [];
                foreach ($required_fields as $field) {
                    if (!isset($ritual[$field])) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (empty($missing_fields)) {
                    $check_result['checks'][] = [
                        'test' => 'getRitualById (with rules)',
                        'status' => 'PASS',
                        'message' => 'All required fields present'
                    ];
                    $checks_passed++;
                } else {
                    $check_result['checks'][] = [
                        'test' => 'getRitualById (with rules)',
                        'status' => 'FAIL',
                        'message' => 'Missing fields: ' . implode(', ', $missing_fields)
                    ];
                    $checks_failed++;
                }
                
                // Verify rules attached
                if (isset($ritual['rules'])) {
                    $check_result['checks'][] = [
                        'test' => 'Rules attachment',
                        'status' => 'PASS',
                        'message' => 'Rules attached successfully'
                    ];
                } else {
                    $check_result['checks'][] = [
                        'test' => 'Rules attachment',
                        'status' => 'WARN',
                        'message' => 'Rules not attached (may be normal if no rules found)'
                    ];
                }
            }
        } catch (Exception $e) {
            $check_result['checks'][] = [
                'test' => 'getRitualById (with rules)',
                'status' => 'FAIL',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            $checks_failed++;
        }
        
        // Check 2: getRitual by type, level, name
        try {
            $ritual2 = $agent->getRitual($ritual_type, $ritual_level, $ritual_name, false);
            
            if ($ritual2 === null) {
                $check_result['checks'][] = [
                    'test' => 'getRitual (by type/level/name)',
                    'status' => 'FAIL',
                    'message' => 'Ritual not found'
                ];
                $checks_failed++;
            } else {
                // Verify it's the same ritual
                if ($ritual2['id'] == $ritual_id) {
                    $check_result['checks'][] = [
                        'test' => 'getRitual (by type/level/name)',
                        'status' => 'PASS',
                        'message' => 'Correct ritual retrieved'
                    ];
                    $checks_passed++;
                } else {
                    $check_result['checks'][] = [
                        'test' => 'getRitual (by type/level/name)',
                        'status' => 'FAIL',
                        'message' => 'Different ritual retrieved (ID mismatch)'
                    ];
                    $checks_failed++;
                }
            }
        } catch (Exception $e) {
            $check_result['checks'][] = [
                'test' => 'getRitual (by type/level/name)',
                'status' => 'FAIL',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            $checks_failed++;
        }
        
        // Check 3: Verify source serialization
        if (isset($ritual) && $ritual !== null) {
            $source = $ritual['source'] ?? null;
            if ($source !== null) {
                // Check if source is properly serialized (not corrupted)
                if (is_string($source) || is_array($source)) {
                    $check_result['checks'][] = [
                        'test' => 'Source serialization',
                        'status' => 'PASS',
                        'message' => 'Source is valid string/array'
                    ];
                    $checks_passed++;
                } else {
                    $check_result['checks'][] = [
                        'test' => 'Source serialization',
                        'status' => 'FAIL',
                        'message' => 'Source has invalid type: ' . gettype($source)
                    ];
                    $checks_failed++;
                }
            }
        }
        
        $spotcheck_results[] = $check_result;
    }
    
    // Check 4: listRituals - verify no duplicates
    try {
        $all_rituals = $agent->listRituals(null, null, false, 1000, 0);
        
        $id_counts = [];
        foreach ($all_rituals as $rit) {
            $id = $rit['id'];
            if (!isset($id_counts[$id])) {
                $id_counts[$id] = 0;
            }
            $id_counts[$id]++;
        }
        
        $duplicate_ids = array_filter($id_counts, function($count) { return $count > 1; });
        
        if (empty($duplicate_ids)) {
            $check_result = [
                'test' => 'listRituals - no duplicates',
                'status' => 'PASS',
                'message' => 'No duplicate IDs found in listRituals results'
            ];
            $checks_passed++;
        } else {
            $check_result = [
                'test' => 'listRituals - no duplicates',
                'status' => 'FAIL',
                'message' => 'Found duplicate IDs: ' . implode(', ', array_keys($duplicate_ids))
            ];
            $checks_failed++;
        }
        
        $spotcheck_results[] = [
            'ritual_id' => null,
            'ritual_name' => 'listRituals test',
            'checks' => [$check_result]
        ];
        
        $success[] = "listRituals returned " . count($all_rituals) . " rituals";
    } catch (Exception $e) {
        $errors[] = "listRituals failed: " . $e->getMessage();
    }
    
    $success[] = "Spot-checks completed: {$checks_passed} passed, {$checks_failed} failed";
    
    // Save results
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-agent-spotcheck.json';
    $results_data = [
        'audit_date' => date('Y-m-d H:i:s'),
        'total_samples' => count($samples),
        'checks_passed' => $checks_passed,
        'checks_failed' => $checks_failed,
        'results' => $spotcheck_results
    ];
    
    $json_output = json_encode($results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write spot-check results file: " . $output_file;
    } else {
        $success[] = "Results saved to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Agent Spot-Check (TM-07) ===\n\n";
    
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
    
    if (!empty($spotcheck_results)) {
        echo "\n=== Spot-Check Results ===\n";
        foreach ($spotcheck_results as $result) {
            if ($result['ritual_id']) {
                echo "\nRitual ID {$result['ritual_id']}: {$result['ritual_name']}\n";
            } else {
                echo "\n{$result['ritual_name']}\n";
            }
            foreach ($result['checks'] as $check) {
                $status = $check['status'] === 'PASS' ? '✓' : ($check['status'] === 'WARN' ? '⚠' : '✗');
                echo "  {$status} {$check['test']}: {$check['message']}\n";
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
    
    if (!empty($spotcheck_results)) {
        echo "<h2>Spot-Check Results</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>Ritual</th><th>Test</th><th>Status</th><th>Message</th></tr>";
        foreach ($spotcheck_results as $result) {
            $ritual_name = $result['ritual_id'] ? "ID {$result['ritual_id']}: {$result['ritual_name']}" : $result['ritual_name'];
            foreach ($result['checks'] as $check) {
                $status_class = $check['status'] === 'PASS' ? 'success' : ($check['status'] === 'WARN' ? 'warning' : 'error');
                echo "<tr>";
                echo "<td>" . htmlspecialchars($ritual_name) . "</td>";
                echo "<td>" . htmlspecialchars($check['test']) . "</td>";
                echo "<td class='{$status_class}'>" . htmlspecialchars($check['status']) . "</td>";
                echo "<td>" . htmlspecialchars($check['message']) . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

