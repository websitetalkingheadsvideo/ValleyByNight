<?php
/**
 * TM-07: Ritual Data Audit - Source Normalization
 * 
 * Analyzes and normalizes source field values for all Necromancy, Thaumaturgy, and Assamite rituals.
 * Standardizes book names, edition markers, page formatting, and array/singleton consistency.
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_sources.php [--dry-run]
 *   Web: database/audit_rituals_sources.php?dry_run=1
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
    echo "<!DOCTYPE html><html><head><title>Rituals Source Normalization (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Source Normalization (TM-07)</h1>";
}

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];
$warnings = [];
$normalization_results = [];
$source_patterns = [];

/**
 * Normalize source value to canonical format
 */
function normalizeSource(string $source): string {
    // Trim whitespace
    $normalized = trim($source);
    
    // Standardize common abbreviations
    $normalized = preg_replace('/\bVTM\b/i', 'Vampire: The Masquerade', $normalized);
    $normalized = preg_replace('/\bMET\b/i', 'Mind\'s Eye Theatre', $normalized);
    
    // Standardize page formatting: "p. 123", "page 123", "p123" -> "p. 123"
    $normalized = preg_replace('/\bpage\s+(\d+)\b/i', 'p. $1', $normalized);
    $normalized = preg_replace('/\bp(\d+)\b/i', 'p. $1', $normalized);
    
    // Standardize edition markers
    $normalized = preg_replace('/\b20th\s+anniversary\b/i', '20th Anniversary', $normalized);
    $normalized = preg_replace('/\b20th\s+anniv\b/i', '20th Anniversary', $normalized);
    $normalized = preg_replace('/\brevised\b/i', 'Revised', $normalized);
    
    return $normalized;
}

/**
 * Extract source patterns from a source string
 */
function extractSourcePatterns(string $source): array {
    $patterns = [
        'has_page' => preg_match('/\bp\.?\s*\d+/i', $source),
        'has_edition' => preg_match('/\b(revised|20th|anniversary|edition)\b/i', $source),
        'has_abbreviation' => preg_match('/\b(VTM|MET)\b/i', $source),
        'format_type' => 'string' // Will be updated if array detected
    ];
    
    return $patterns;
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
        $query = "SELECT id, name, type, level, source
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
    
    // Analyze and normalize sources
    $normalized_count = 0;
    $unchanged_count = 0;
    $empty_count = 0;
    
    foreach ($rituals as $ritual) {
        $ritual_id = $ritual['id'];
        $ritual_name = $ritual['name'];
        $ritual_type = $ritual['type'];
        $ritual_level = $ritual['level'];
        $original_source = $ritual['source'] ?? null;
        
        if ($original_source === null || trim($original_source) === '') {
            $empty_count++;
            $normalization_results[] = [
                'id' => $ritual_id,
                'name' => $ritual_name,
                'type' => $ritual_type,
                'level' => $ritual_level,
                'original_source' => $original_source,
                'normalized_source' => null,
                'changed' => false,
                'reason' => 'Empty source'
            ];
            continue;
        }
        
        // Check if source is JSON array (some might be stored as JSON)
        $source_value = $original_source;
        $is_json = false;
        
        if (is_string($original_source) && (substr(trim($original_source), 0, 1) === '[' || substr(trim($original_source), 0, 1) === '{')) {
            $decoded = json_decode($original_source, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $source_value = implode(', ', $decoded);
                $is_json = true;
            }
        }
        
        // Normalize source
        $normalized_source = normalizeSource($source_value);
        $changed = ($normalized_source !== $source_value);
        
        if ($changed) {
            $normalized_count++;
            
            // Update database if not dry run
            if (!$dry_run) {
                $update_query = "UPDATE rituals_master SET source = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'si', $normalized_source, $ritual_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        $errors[] = "Failed to update ritual ID {$ritual_id}: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "Failed to prepare update for ritual ID {$ritual_id}: " . mysqli_error($conn);
                }
            }
        } else {
            $unchanged_count++;
        }
        
        // Extract patterns
        $patterns = extractSourcePatterns($normalized_source);
        $source_patterns[] = [
            'source' => $normalized_source,
            'patterns' => $patterns
        ];
        
        $normalization_results[] = [
            'id' => $ritual_id,
            'name' => $ritual_name,
            'type' => $ritual_type,
            'level' => $ritual_level,
            'original_source' => $original_source,
            'normalized_source' => $normalized_source,
            'changed' => $changed,
            'was_json' => $is_json,
            'patterns' => $patterns
        ];
    }
    
    $success[] = "Analyzed " . count($rituals) . " rituals";
    $success[] = "Normalized: {$normalized_count}";
    $success[] = "Unchanged: {$unchanged_count}";
    $warnings[] = "Empty: {$empty_count}";
    
    if ($dry_run) {
        $warnings[] = "DRY RUN MODE - No database changes made";
    } else {
        $success[] = "Database updated with normalized sources";
    }
    
    // Save results
    $output_dir = __DIR__ . '/../tmp';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $output_file = $output_dir . '/TM-07-rituals-sources-normalized.json';
    $results_data = [
        'audit_date' => date('Y-m-d H:i:s'),
        'dry_run' => $dry_run,
        'total_rituals' => count($rituals),
        'normalized_count' => $normalized_count,
        'unchanged_count' => $unchanged_count,
        'empty_count' => $empty_count,
        'results' => $normalization_results,
        'source_patterns' => $source_patterns
    ];
    
    $json_output = json_encode($results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($output_file, $json_output) === false) {
        $errors[] = "Failed to write normalization results file: " . $output_file;
    } else {
        $success[] = "Results saved to: " . $output_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Source Normalization (TM-07) ===\n\n";
    
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
    
    if (!empty($normalization_results)) {
        $changed = array_filter($normalization_results, function($r) { return $r['changed']; });
        
        if (!empty($changed)) {
            echo "\n=== Changed Sources (first 10) ===\n";
            foreach (array_slice($changed, 0, 10) as $result) {
                echo "  ID {$result['id']}: {$result['name']}\n";
                echo "    Original: " . substr($result['original_source'], 0, 60) . "...\n";
                echo "    Normalized: " . substr($result['normalized_source'], 0, 60) . "...\n";
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

