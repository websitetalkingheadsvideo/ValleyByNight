<?php
/**
 * TM-07: Ritual Data Audit - Duplicate Detection & Resolution
 * 
 * Detects duplicate ritual entries by:
 * - ID collision (multiple rows with same id)
 * - Name collision (same type, level, name with case/punctuation variants)
 * - Content similarity (near-identical system_text with shared sources)
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_duplicates.php [--dry-run]
 *   Web: database/audit_rituals_duplicates.php?dry_run=1
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';
$dry_run = false;

if ($is_cli) {
    $dry_run = in_array('--dry-run', $argv);
} else {
    $dry_run = isset($_GET['dry_run']) && $_GET['dry_run'] == '1';
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals Duplicate Detection (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Duplicate Detection (TM-07)</h1>";
}

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$warnings = [];
$duplicate_results = [];

/**
 * Normalize name for comparison (lowercase, trim, remove extra spaces)
 */
function normalizeName(string $name): string {
    return preg_replace('/\s+/', ' ', strtolower(trim($name)));
}

/**
 * Calculate similarity between two strings (simple Levenshtein-based)
 */
function calculateSimilarity(string $str1, string $str2): float {
    $str1 = normalizeName($str1);
    $str2 = normalizeName($str2);
    
    if ($str1 === $str2) {
        return 1.0;
    }
    
    $max_len = max(strlen($str1), strlen($str2));
    if ($max_len === 0) {
        return 1.0;
    }
    
    $distance = levenshtein($str1, $str2);
    return 1.0 - ($distance / $max_len);
}

/**
 * Check if ritual is more complete (has more non-empty fields)
 */
function isMoreComplete(array $ritual1, array $ritual2): bool {
    $fields = ['description', 'system_text', 'requirements', 'ingredients', 'source'];
    $count1 = 0;
    $count2 = 0;
    
    foreach ($fields as $field) {
        $val1 = $ritual1[$field] ?? null;
        $val2 = $ritual2[$field] ?? null;
        
        if ($val1 !== null && trim($val1) !== '') {
            $count1++;
        }
        if ($val2 !== null && trim($val2) !== '') {
            $count2++;
        }
    }
    
    return $count1 > $count2;
}

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
        $query = "SELECT id, name, type, level, description, system_text, requirements, ingredients, source
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
    
    // 1. Check for ID collisions
    $id_groups = [];
    foreach ($rituals as $ritual) {
        $id = $ritual['id'];
        if (!isset($id_groups[$id])) {
            $id_groups[$id] = [];
        }
        $id_groups[$id][] = $ritual;
    }
    
    $id_collisions = [];
    foreach ($id_groups as $id => $group) {
        if (count($group) > 1) {
            $id_collisions[] = [
                'type' => 'id_collision',
                'id' => $id,
                'count' => count($group),
                'rituals' => $group
            ];
        }
    }
    
    // 2. Check for name collisions (same type, level, name with variations)
    $name_groups = [];
    foreach ($rituals as $ritual) {
        $key = strtolower(trim($ritual['type'])) . '|' . $ritual['level'] . '|' . normalizeName($ritual['name']);
        if (!isset($name_groups[$key])) {
            $name_groups[$key] = [];
        }
        $name_groups[$key][] = $ritual;
    }
    
    $name_collisions = [];
    foreach ($name_groups as $key => $group) {
        if (count($group) > 1) {
            list($type, $level, $name) = explode('|', $key);
            $name_collisions[] = [
                'type' => 'name_collision',
                'type_normalized' => $type,
                'level' => $level,
                'name_normalized' => $name,
                'count' => count($group),
                'rituals' => $group
            ];
        }
    }
    
    // 3. Check for content similarity (near-identical system_text)
    $content_similar = [];
    $similarity_threshold = 0.85; // 85% similarity
    
    for ($i = 0; $i < count($rituals); $i++) {
        for ($j = $i + 1; $j < count($rituals); $j++) {
            $ritual1 = $rituals[$i];
            $ritual2 = $rituals[$j];
            
            // Only compare if same type
            if (strtolower(trim($ritual1['type'])) !== strtolower(trim($ritual2['type']))) {
                continue;
            }
            
            $text1 = $ritual1['system_text'] ?? '';
            $text2 = $ritual2['system_text'] ?? '';
            
            if (empty($text1) || empty($text2)) {
                continue;
            }
            
            $similarity = calculateSimilarity($text1, $text2);
            
            if ($similarity >= $similarity_threshold) {
                $content_similar[] = [
                    'type' => 'content_similarity',
                    'ritual1' => $ritual1,
                    'ritual2' => $ritual2,
                    'similarity' => $similarity
                ];
            }
        }
    }
    
    // Combine all duplicate findings
    $all_duplicates = array_merge($id_collisions, $name_collisions, $content_similar);
    
    // Resolve duplicates (select canonical entry)
    $resolved = [];
    $to_remove = [];
    
    foreach ($all_duplicates as $duplicate) {
        if ($duplicate['type'] === 'id_collision') {
            // For ID collisions, keep the first one, mark others for removal
            $canonical = $duplicate['rituals'][0];
            for ($i = 1; $i < count($duplicate['rituals']); $i++) {
                $to_remove[] = $duplicate['rituals'][$i]['id'];
            }
            $resolved[] = [
                'duplicate' => $duplicate,
                'canonical_id' => $canonical['id'],
                'removed_ids' => array_slice(array_column($duplicate['rituals'], 'id'), 1)
            ];
        } elseif ($duplicate['type'] === 'name_collision') {
            // Select most complete version
            $rituals_list = $duplicate['rituals'];
            usort($rituals_list, function($a, $b) {
                return isMoreComplete($b, $a) ? 1 : -1;
            });
            
            $canonical = $rituals_list[0];
            $removed = [];
            for ($i = 1; $i < count($rituals_list); $i++) {
                $removed[] = $rituals_list[$i]['id'];
                $to_remove[] = $rituals_list[$i]['id'];
            }
            
            $resolved[] = [
                'duplicate' => $duplicate,
                'canonical_id' => $canonical['id'],
                'removed_ids' => $removed
            ];
        } elseif ($duplicate['type'] === 'content_similarity') {
            // Select more complete version
            $ritual1 = $duplicate['ritual1'];
            $ritual2 = $duplicate['ritual2'];
            
            if (isMoreComplete($ritual1, $ritual2)) {
                $canonical_id = $ritual1['id'];
                $removed_id = $ritual2['id'];
            } else {
                $canonical_id = $ritual2['id'];
                $removed_id = $ritual1['id'];
            }
            
            $to_remove[] = $removed_id;
            $resolved[] = [
                'duplicate' => $duplicate,
                'canonical_id' => $canonical_id,
                'removed_ids' => [$removed_id]
            ];
        }
    }
    
    // Remove duplicates if not dry run
    if (!$dry_run && !empty($to_remove)) {
        $to_remove = array_unique($to_remove);
        $placeholders = implode(',', array_fill(0, count($to_remove), '?'));
        $delete_query = "DELETE FROM rituals_master WHERE id IN ($placeholders)";
        
        $stmt = mysqli_prepare($conn, $delete_query);
        if ($stmt) {
            $types = str_repeat('i', count($to_remove));
            mysqli_stmt_bind_param($stmt, $types, ...$to_remove);
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Failed to delete duplicates: " . mysqli_stmt_error($stmt);
            } else {
                $success[] = "Removed " . count($to_remove) . " duplicate ritual(s)";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Failed to prepare delete query: " . mysqli_error($conn);
        }
    }
    
    $success[] = "Found " . count($all_duplicates) . " duplicate group(s)";
    $success[] = "Resolved: " . count($resolved);
    
    if ($dry_run) {
        $warnings[] = "DRY RUN MODE - No database changes made";
    }
    
    $duplicate_results = [
        'id_collisions' => $id_collisions,
        'name_collisions' => $name_collisions,
        'content_similar' => $content_similar,
        'resolved' => $resolved,
        'to_remove' => array_unique($to_remove)
    ];
    
    // Save results
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-duplicates.json';
    $results_data = [
        'audit_date' => date('Y-m-d H:i:s'),
        'dry_run' => $dry_run,
        'total_rituals' => count($rituals),
        'duplicate_groups' => count($all_duplicates),
        'resolved_count' => count($resolved),
        'removed_count' => count(array_unique($to_remove)),
        'results' => $duplicate_results
    ];
    
    $json_output = json_encode($results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write duplicate results file: " . $output_file;
    } else {
        $success[] = "Results saved to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Duplicate Detection (TM-07) ===\n\n";
    
    if ($dry_run) {
        echo "⚠ DRY RUN MODE - No database changes will be made\n\n";
    }
    
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
    
    if (!empty($duplicate_results)) {
        if (!empty($duplicate_results['id_collisions'])) {
            echo "\n=== ID Collisions ===\n";
            foreach ($duplicate_results['id_collisions'] as $collision) {
                echo "  ID {$collision['id']}: {$collision['count']} entries\n";
            }
        }
        
        if (!empty($duplicate_results['name_collisions'])) {
            echo "\n=== Name Collisions ===\n";
            foreach (array_slice($duplicate_results['name_collisions'], 0, 10) as $collision) {
                echo "  {$collision['name_normalized']} ({$collision['type_normalized']} Level {$collision['level']}): {$collision['count']} entries\n";
            }
        }
    }
    
    echo "\n";
} else {
    if ($dry_run) {
        echo "<div class='warning'><strong>DRY RUN MODE</strong> - No database changes will be made</div>";
    }
    
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

