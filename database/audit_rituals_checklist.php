<?php
/**
 * TM-07: Ritual Data Audit - Checklist Generator
 * 
 * Generates the audit checklist artifact by combining results from:
 * - Inventory
 * - Completeness audit
 * - Source normalization
 * - Duplicate detection
 * 
 * Usage: 
 *   CLI: php database/audit_rituals_checklist.php
 *   Web: https://vbn.talkingheads.video/database/audit_rituals_checklist.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rituals Audit Checklist (TM-07)</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} pre{background:#2a2a2a;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Rituals Audit Checklist Generator (TM-07)</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];

try {
    // Load all audit results
    $tmp_dir = __DIR__ . '/../tmp';
    
    $inventory_file = $tmp_dir . '/TM-07-rituals-inventory.json';
    $completeness_file = $tmp_dir . '/TM-07-rituals-completeness.json';
    $sources_file = $tmp_dir . '/TM-07-rituals-sources-normalized.json';
    $duplicates_file = $tmp_dir . '/TM-07-rituals-duplicates.json';
    
    $inventory_data = null;
    $completeness_data = null;
    $sources_data = null;
    $duplicates_data = null;
    
    if (file_exists($inventory_file)) {
        $inventory_data = json_decode(file_get_contents($inventory_file), true);
    }
    
    if (file_exists($completeness_file)) {
        $completeness_data = json_decode(file_get_contents($completeness_file), true);
    }
    
    if (file_exists($sources_file)) {
        $sources_data = json_decode(file_get_contents($sources_file), true);
    }
    
    if (file_exists($duplicates_file)) {
        $duplicates_data = json_decode(file_get_contents($duplicates_file), true);
    }
    
    // Get git info if available
    $git_branch = 'unknown';
    $git_commit = 'unknown';
    
    if (function_exists('shell_exec')) {
        $git_branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null') ?: 'unknown');
        $git_commit = trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?: 'unknown');
    }
    
    // Generate markdown checklist
    $checklist = "# TM-07 Ritual Data Audit Checklist\n\n";
    $checklist .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
    $checklist .= "**Branch:** `{$git_branch}`\n";
    $checklist .= "**Commit:** `{$git_commit}`\n\n";
    $checklist .= "## Scope Definition\n\n";
    $checklist .= "This audit covers all ritual entries in the `rituals_master` database table for:\n";
    $checklist .= "- **Necromancy** rituals\n";
    $checklist .= "- **Thaumaturgy** rituals\n";
    $checklist .= "- **Assamite** rituals\n\n";
    
    // Audit Rules
    $checklist .= "## Audit Rules\n\n";
    $checklist .= "Extracted from `agents/rituals_agent/rituals_agent.json`:\n\n";
    $checklist .= "### Required Fields\n";
    $checklist .= "- `ingredients` - Required per agent definition\n";
    $checklist .= "- `requirements` - Required per agent definition\n";
    $checklist .= "- `system_text` - Required per agent definition\n\n";
    $checklist .= "### Field Names\n";
    $checklist .= "Use exact names from agent definition (no aliases):\n";
    $checklist .= "- `id`, `name`, `type`, `level`, `description`, `system_text`, `requirements`, `ingredients`, `source`\n\n";
    $checklist .= "### Unique Key\n";
    $checklist .= "Unique key is `(type, level, name)`\n\n";
    
    // Inventory
    if ($inventory_data) {
        $checklist .= "## Inventory\n\n";
        $checklist .= "**File Path:** `database/rituals_master`\n";
        $checklist .= "**Total Rituals:** " . ($inventory_data['total_count'] ?? 0) . "\n";
        $checklist .= "**Audit Date:** " . ($inventory_data['audit_date'] ?? 'N/A') . "\n\n";
        
        // Count by type
        if (isset($inventory_data['rituals'])) {
            $by_type = [];
            foreach ($inventory_data['rituals'] as $ritual) {
                $type = ucfirst(strtolower(trim($ritual['type'] ?? 'Unknown')));
                if (!isset($by_type[$type])) {
                    $by_type[$type] = 0;
                }
                $by_type[$type]++;
            }
            
            $checklist .= "### Count by Type\n\n";
            foreach ($by_type as $type => $count) {
                $checklist .= "- **{$type}:** {$count} rituals\n";
            }
            $checklist .= "\n";
        }
    }
    
    // Completeness Results
    if ($completeness_data) {
        $checklist .= "## Completeness Audit Results\n\n";
        $checklist .= "**Total Audited:** " . ($completeness_data['total_rituals'] ?? 0) . "\n";
        $checklist .= "**Complete:** " . ($completeness_data['complete_count'] ?? 0) . "\n";
        $checklist .= "**Incomplete:** " . ($completeness_data['incomplete_count'] ?? 0) . "\n\n";
        
        if (isset($completeness_data['results'])) {
            $incomplete = array_filter($completeness_data['results'], function($r) { return !$r['is_complete']; });
            
            if (!empty($incomplete)) {
                $checklist .= "### Incomplete Rituals\n\n";
                $checklist .= "| ID | Name | Type | Level | Missing Fields |\n";
                $checklist .= "|----|------|------|-------|---------------|\n";
                
                foreach ($incomplete as $result) {
                    $missing = implode(', ', $result['missing_fields']);
                    $checklist .= "| {$result['id']} | {$result['name']} | {$result['type']} | {$result['level']} | {$missing} |\n";
                }
                $checklist .= "\n";
            }
        }
    }
    
    // Source Normalization
    if ($sources_data) {
        $checklist .= "## Source Normalization Results\n\n";
        $checklist .= "**Total Analyzed:** " . ($sources_data['total_rituals'] ?? 0) . "\n";
        $checklist .= "**Normalized:** " . ($sources_data['normalized_count'] ?? 0) . "\n";
        $checklist .= "**Unchanged:** " . ($sources_data['unchanged_count'] ?? 0) . "\n";
        $checklist .= "**Empty:** " . ($sources_data['empty_count'] ?? 0) . "\n";
        $checklist .= "**Dry Run:** " . ($sources_data['dry_run'] ? 'Yes' : 'No') . "\n\n";
    }
    
    // Duplicate Detection
    if ($duplicates_data) {
        $checklist .= "## Duplicate Detection Results\n\n";
        $checklist .= "**Total Rituals:** " . ($duplicates_data['total_rituals'] ?? 0) . "\n";
        $checklist .= "**Duplicate Groups Found:** " . ($duplicates_data['duplicate_groups'] ?? 0) . "\n";
        $checklist .= "**Resolved:** " . ($duplicates_data['resolved_count'] ?? 0) . "\n";
        $checklist .= "**Removed:** " . ($duplicates_data['removed_count'] ?? 0) . "\n";
        $checklist .= "**Dry Run:** " . ($duplicates_data['dry_run'] ? 'Yes' : 'No') . "\n\n";
        
        if (isset($duplicates_data['results'])) {
            $results = $duplicates_data['results'];
            
            if (!empty($results['id_collisions'])) {
                $checklist .= "### ID Collisions\n\n";
                foreach ($results['id_collisions'] as $collision) {
                    $checklist .= "- **ID {$collision['id']}:** {$collision['count']} entries\n";
                }
                $checklist .= "\n";
            }
            
            if (!empty($results['name_collisions'])) {
                $checklist .= "### Name Collisions\n\n";
                $checklist .= "| Type | Level | Name | Count |\n";
                $checklist .= "|------|-------|------|-------|\n";
                foreach (array_slice($results['name_collisions'], 0, 20) as $collision) {
                    $checklist .= "| {$collision['type_normalized']} | {$collision['level']} | {$collision['name_normalized']} | {$collision['count']} |\n";
                }
                $checklist .= "\n";
            }
        }
    }
    
    // Per-Ritual Status Table
    if ($inventory_data && $completeness_data && isset($inventory_data['rituals']) && isset($completeness_data['results'])) {
        $checklist .= "## Per-Ritual Status\n\n";
        $checklist .= "| ID | Name | Type | Level | Ingredients | Requirements | System Text | Sources | Duplicates |\n";
        $checklist .= "|----|------|------|-------|-------------|--------------|-------------|---------|------------|\n";
        
        // Create lookup for completeness and sources
        $completeness_lookup = [];
        foreach ($completeness_data['results'] as $result) {
            $completeness_lookup[$result['id']] = $result;
        }
        
        $sources_lookup = [];
        if ($sources_data && isset($sources_data['results'])) {
            foreach ($sources_data['results'] as $result) {
                $sources_lookup[$result['id']] = $result;
            }
        }
        
        $duplicates_lookup = [];
        if ($duplicates_data && isset($duplicates_data['results']['resolved'])) {
            foreach ($duplicates_data['results']['resolved'] as $resolved) {
                foreach ($resolved['removed_ids'] as $removed_id) {
                    $duplicates_lookup[$removed_id] = 'Removed';
                }
                $duplicates_lookup[$resolved['canonical_id']] = 'Canonical';
            }
        }
        
        foreach (array_slice($inventory_data['rituals'], 0, 50) as $ritual) {
            $id = $ritual['id'];
            $name = $ritual['name'];
            $type = $ritual['type'];
            $level = $ritual['level'];
            
            $comp = $completeness_lookup[$id] ?? null;
            $ingredients = $comp && isset($comp['field_status']['ingredients']) ? $comp['field_status']['ingredients'] : '?';
            $requirements = $comp && isset($comp['field_status']['requirements']) ? $comp['field_status']['requirements'] : '?';
            $system_text = $comp && isset($comp['field_status']['system_text']) ? $comp['field_status']['system_text'] : '?';
            
            $source_status = '?';
            if (isset($sources_lookup[$id])) {
                $source_status = $sources_lookup[$id]['changed'] ? '✅ Normalized' : '✅';
            }
            
            $duplicate_status = $duplicates_lookup[$id] ?? '✅';
            
            $checklist .= "| {$id} | {$name} | {$type} | {$level} | {$ingredients} | {$requirements} | {$system_text} | {$source_status} | {$duplicate_status} |\n";
        }
        
        if (count($inventory_data['rituals']) > 50) {
            $checklist .= "\n*Showing first 50 rituals. Total: " . count($inventory_data['rituals']) . "*\n\n";
        }
    }
    
    // Summary Metrics
    $checklist .= "## Summary Metrics\n\n";
    
    $total = $inventory_data['total_count'] ?? 0;
    $complete = $completeness_data['complete_count'] ?? 0;
    $incomplete = $completeness_data['incomplete_count'] ?? 0;
    $normalized = $sources_data['normalized_count'] ?? 0;
    $duplicates_found = $duplicates_data['duplicate_groups'] ?? 0;
    $duplicates_removed = $duplicates_data['removed_count'] ?? 0;
    
    $checklist .= "- **Total Rituals Audited:** {$total}\n";
    $checklist .= "- **Complete:** {$complete}\n";
    $checklist .= "- **Incomplete:** {$incomplete}\n";
    $checklist .= "- **Sources Normalized:** {$normalized}\n";
    $checklist .= "- **Duplicates Found:** {$duplicates_found}\n";
    $checklist .= "- **Duplicates Removed:** {$duplicates_removed}\n\n";
    
    // Save checklist
    $docs_dir = __DIR__ . '/../docs/qa';
    if (!is_dir($docs_dir)) {
        mkdir($docs_dir, 0755, true);
    }
    
    $checklist_file = $docs_dir . '/TM-07-ritual-data-audit.md';
    
    if (file_put_contents($checklist_file, $checklist) === false) {
        $errors[] = "Failed to write checklist file: " . $checklist_file;
    } else {
        $success[] = "Checklist saved to: " . $checklist_file;
    }
    
} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Output results
if ($is_cli) {
    echo "\n=== Rituals Audit Checklist Generator (TM-07) ===\n\n";
    
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
    
    echo "</body></html>";
}

mysqli_close($conn);
?>

