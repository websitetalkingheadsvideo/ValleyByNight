<?php
/**
 * Paths Database Completion Audit Script
 * 
 * Audits paths_master and path_powers tables to identify missing fields:
 * - paths_master.description (NULL values)
 * - path_powers.system_text (NULL values)
 * - path_powers.challenge_type ('unknown' values)
 * - path_powers.challenge_notes (NULL values)
 * 
 * Scope: Only Necromancy and Thaumaturgy paths
 * 
 * Usage:
 *   CLI: php database/audit_paths_completion.php
 *   Web: https://vbn.talkingheads.video/database/audit_paths_completion.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Verify tables exist
$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'paths_master'");
if (mysqli_num_rows($tables_check) === 0) {
    die("Error: paths_master table does not exist. Please create the table first.");
}
mysqli_free_result($tables_check);

$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'path_powers'");
if (mysqli_num_rows($tables_check) === 0) {
    die("Error: path_powers table does not exist. Please create the table first.");
}
mysqli_free_result($tables_check);

// Initialize audit results
$audit_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'paths_master' => [
        'total' => 0,
        'necromancy' => ['total' => 0, 'missing_description' => 0, 'paths' => []],
        'thaumaturgy' => ['total' => 0, 'missing_description' => 0, 'paths' => []]
    ],
    'path_powers' => [
        'total' => 0,
        'missing_system_text' => 0,
        'unknown_challenge_type' => 0,
        'missing_challenge_notes' => 0,
        'powers_by_path' => []
    ],
    'summary' => [
        'paths_needing_description' => [],
        'powers_needing_system_text' => [],
        'powers_needing_challenge_type' => [],
        'powers_needing_challenge_notes' => []
    ]
];

// Query all Necromancy and Thaumaturgy paths
$paths_query = "SELECT id, name, type, description 
                FROM paths_master 
                WHERE type IN ('Necromancy', 'Thaumaturgy')
                ORDER BY type, name";

$result = mysqli_query($conn, $paths_query);
if (!$result) {
    die("Error querying paths_master: " . mysqli_error($conn));
}

$paths_by_id = [];
while ($row = mysqli_fetch_assoc($result)) {
    $path_id = (int)$row['id'];
    $path_type = $row['type'];
    $path_name = $row['name'];
    $description = $row['description'];
    
    // Store path info
    $paths_by_id[$path_id] = [
        'id' => $path_id,
        'name' => $path_name,
        'type' => $path_type,
        'description' => $description
    ];
    
    // Count totals
    $audit_results['paths_master']['total']++;
    $audit_results['paths_master'][strtolower($path_type)]['total']++;
    
    // Check for missing description
    if (is_null($description) || trim($description) === '') {
        $audit_results['paths_master'][strtolower($path_type)]['missing_description']++;
        $audit_results['paths_master'][strtolower($path_type)]['paths'][] = [
            'id' => $path_id,
            'name' => $path_name
        ];
        $audit_results['summary']['paths_needing_description'][] = [
            'id' => $path_id,
            'name' => $path_name,
            'type' => $path_type
        ];
    }
}
mysqli_free_result($result);

// Query all powers for these paths
if (!empty($paths_by_id)) {
    $path_ids = array_keys($paths_by_id);
    $placeholders = implode(',', array_fill(0, count($path_ids), '?'));
    
    $powers_query = "SELECT pp.id, pp.path_id, pp.level, pp.power_name, 
                            pp.system_text, pp.challenge_type, pp.challenge_notes,
                            pm.name as path_name, pm.type as path_type
                     FROM path_powers pp
                     INNER JOIN paths_master pm ON pp.path_id = pm.id
                     WHERE pp.path_id IN ($placeholders)
                     ORDER BY pm.type, pm.name, pp.level";
    
    $stmt = mysqli_prepare($conn, $powers_query);
    if (!$stmt) {
        die("Error preparing powers query: " . mysqli_error($conn));
    }
    
    // Bind parameters
    $types = str_repeat('i', count($path_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$path_ids);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing powers query: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $power_id = (int)$row['id'];
        $path_id = (int)$row['path_id'];
        $level = (int)$row['level'];
        $power_name = $row['power_name'];
        $system_text = $row['system_text'];
        $challenge_type = $row['challenge_type'];
        $challenge_notes = $row['challenge_notes'];
        $path_name = $row['path_name'];
        $path_type = $row['path_type'];
        
        // Initialize path entry if not exists
        if (!isset($audit_results['path_powers']['powers_by_path'][$path_id])) {
            $audit_results['path_powers']['powers_by_path'][$path_id] = [
                'path_id' => $path_id,
                'path_name' => $path_name,
                'path_type' => $path_type,
                'total_powers' => 0,
                'missing_system_text' => 0,
                'unknown_challenge_type' => 0,
                'missing_challenge_notes' => 0,
                'powers' => []
            ];
        }
        
        $audit_results['path_powers']['total']++;
        $audit_results['path_powers']['powers_by_path'][$path_id]['total_powers']++;
        
        $power_info = [
            'id' => $power_id,
            'level' => $level,
            'power_name' => $power_name
        ];
        $needs_update = false;
        
        // Check for missing system_text
        if (is_null($system_text) || trim($system_text) === '') {
            $audit_results['path_powers']['missing_system_text']++;
            $audit_results['path_powers']['powers_by_path'][$path_id]['missing_system_text']++;
            $power_info['needs_system_text'] = true;
            $needs_update = true;
            $audit_results['summary']['powers_needing_system_text'][] = [
                'power_id' => $power_id,
                'path_id' => $path_id,
                'path_name' => $path_name,
                'path_type' => $path_type,
                'level' => $level,
                'power_name' => $power_name
            ];
        }
        
        // Check for unknown challenge_type
        if ($challenge_type === 'unknown' || is_null($challenge_type)) {
            $audit_results['path_powers']['unknown_challenge_type']++;
            $audit_results['path_powers']['powers_by_path'][$path_id]['unknown_challenge_type']++;
            $power_info['needs_challenge_type'] = true;
            $needs_update = true;
            $audit_results['summary']['powers_needing_challenge_type'][] = [
                'power_id' => $power_id,
                'path_id' => $path_id,
                'path_name' => $path_name,
                'path_type' => $path_type,
                'level' => $level,
                'power_name' => $power_name,
                'current_value' => $challenge_type
            ];
        }
        
        // Check for missing challenge_notes
        if (is_null($challenge_notes) || trim($challenge_notes) === '') {
            $audit_results['path_powers']['missing_challenge_notes']++;
            $audit_results['path_powers']['powers_by_path'][$path_id]['missing_challenge_notes']++;
            $power_info['needs_challenge_notes'] = true;
            $needs_update = true;
            $audit_results['summary']['powers_needing_challenge_notes'][] = [
                'power_id' => $power_id,
                'path_id' => $path_id,
                'path_name' => $path_name,
                'path_type' => $path_type,
                'level' => $level,
                'power_name' => $power_name
            ];
        }
        
        if ($needs_update) {
            $audit_results['path_powers']['powers_by_path'][$path_id]['powers'][] = $power_info;
        }
    }
    
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}

// Calculate completion percentages
$audit_results['completion'] = [
    'paths_master' => [
        'necromancy' => $audit_results['paths_master']['necromancy']['total'] > 0 
            ? (($audit_results['paths_master']['necromancy']['total'] - $audit_results['paths_master']['necromancy']['missing_description']) / $audit_results['paths_master']['necromancy']['total'] * 100)
            : 100,
        'thaumaturgy' => $audit_results['paths_master']['thaumaturgy']['total'] > 0
            ? (($audit_results['paths_master']['thaumaturgy']['total'] - $audit_results['paths_master']['thaumaturgy']['missing_description']) / $audit_results['paths_master']['thaumaturgy']['total'] * 100)
            : 100
    ],
    'path_powers' => [
        'system_text' => $audit_results['path_powers']['total'] > 0
            ? (($audit_results['path_powers']['total'] - $audit_results['path_powers']['missing_system_text']) / $audit_results['path_powers']['total'] * 100)
            : 100,
        'challenge_type' => $audit_results['path_powers']['total'] > 0
            ? (($audit_results['path_powers']['total'] - $audit_results['path_powers']['unknown_challenge_type']) / $audit_results['path_powers']['total'] * 100)
            : 100,
        'challenge_notes' => $audit_results['path_powers']['total'] > 0
            ? (($audit_results['path_powers']['total'] - $audit_results['path_powers']['missing_challenge_notes']) / $audit_results['path_powers']['total'] * 100)
            : 100
    ]
];

// Save JSON output
$json_output = json_encode($audit_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$output_file = __DIR__ . '/../tmp/paths_audit_baseline.json';

// Ensure tmp directory exists
$tmp_dir = dirname($output_file);
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}

file_put_contents($output_file, $json_output);

// Display results
if ($is_cli) {
    echo "Paths Database Completion Audit\n";
    echo "================================\n\n";
    echo "Timestamp: " . $audit_results['timestamp'] . "\n\n";
    
    echo "PATHS_MASTER Summary:\n";
    echo "  Total paths: " . $audit_results['paths_master']['total'] . "\n";
    echo "  Necromancy: " . $audit_results['paths_master']['necromancy']['total'] . " total, ";
    echo $audit_results['paths_master']['necromancy']['missing_description'] . " missing descriptions\n";
    echo "  Thaumaturgy: " . $audit_results['paths_master']['thaumaturgy']['total'] . " total, ";
    echo $audit_results['paths_master']['thaumaturgy']['missing_description'] . " missing descriptions\n\n";
    
    echo "PATH_POWERS Summary:\n";
    echo "  Total powers: " . $audit_results['path_powers']['total'] . "\n";
    echo "  Missing system_text: " . $audit_results['path_powers']['missing_system_text'] . "\n";
    echo "  Unknown challenge_type: " . $audit_results['path_powers']['unknown_challenge_type'] . "\n";
    echo "  Missing challenge_notes: " . $audit_results['path_powers']['missing_challenge_notes'] . "\n\n";
    
    echo "Completion Percentages:\n";
    echo "  Paths - Necromancy: " . number_format($audit_results['completion']['paths_master']['necromancy'], 1) . "%\n";
    echo "  Paths - Thaumaturgy: " . number_format($audit_results['completion']['paths_master']['thaumaturgy'], 1) . "%\n";
    echo "  Powers - system_text: " . number_format($audit_results['completion']['path_powers']['system_text'], 1) . "%\n";
    echo "  Powers - challenge_type: " . number_format($audit_results['completion']['path_powers']['challenge_type'], 1) . "%\n";
    echo "  Powers - challenge_notes: " . number_format($audit_results['completion']['path_powers']['challenge_notes'], 1) . "%\n\n";
    
    echo "Detailed results saved to: $output_file\n";
} else {
    echo "<!DOCTYPE html>\n<html><head><title>Paths Audit Results</title></head><body>\n";
    echo "<h1>Paths Database Completion Audit</h1>\n";
    echo "<p><strong>Timestamp:</strong> " . htmlspecialchars($audit_results['timestamp']) . "</p>\n";
    
    echo "<h2>PATHS_MASTER Summary</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Total paths:</strong> " . $audit_results['paths_master']['total'] . "</li>\n";
    echo "<li><strong>Necromancy:</strong> " . $audit_results['paths_master']['necromancy']['total'] . " total, ";
    echo $audit_results['paths_master']['necromancy']['missing_description'] . " missing descriptions</li>\n";
    echo "<li><strong>Thaumaturgy:</strong> " . $audit_results['paths_master']['thaumaturgy']['total'] . " total, ";
    echo $audit_results['paths_master']['thaumaturgy']['missing_description'] . " missing descriptions</li>\n";
    echo "</ul>\n";
    
    echo "<h2>PATH_POWERS Summary</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Total powers:</strong> " . $audit_results['path_powers']['total'] . "</li>\n";
    echo "<li><strong>Missing system_text:</strong> " . $audit_results['path_powers']['missing_system_text'] . "</li>\n";
    echo "<li><strong>Unknown challenge_type:</strong> " . $audit_results['path_powers']['unknown_challenge_type'] . "</li>\n";
    echo "<li><strong>Missing challenge_notes:</strong> " . $audit_results['path_powers']['missing_challenge_notes'] . "</li>\n";
    echo "</ul>\n";
    
    echo "<h2>Completion Percentages</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Paths - Necromancy:</strong> " . number_format($audit_results['completion']['paths_master']['necromancy'], 1) . "%</li>\n";
    echo "<li><strong>Paths - Thaumaturgy:</strong> " . number_format($audit_results['completion']['paths_master']['thaumaturgy'], 1) . "%</li>\n";
    echo "<li><strong>Powers - system_text:</strong> " . number_format($audit_results['completion']['path_powers']['system_text'], 1) . "%</li>\n";
    echo "<li><strong>Powers - challenge_type:</strong> " . number_format($audit_results['completion']['path_powers']['challenge_type'], 1) . "%</li>\n";
    echo "<li><strong>Powers - challenge_notes:</strong> " . number_format($audit_results['completion']['path_powers']['challenge_notes'], 1) . "%</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Detailed results saved to:</strong> <code>" . htmlspecialchars($output_file) . "</code></p>\n";
    echo "</body></html>\n";
}

mysqli_close($conn);
?>

