<?php
/**
 * Fix MCP paths in .cursor/mcp.json to use mapped drive V:\
 * Run: php fix_mcp_json_paths.php
 */

$mcp_file = __DIR__ . '/.cursor/mcp.json';

if (!file_exists($mcp_file)) {
    die("Error: .cursor/mcp.json not found at {$mcp_file}\n");
}

$content = file_get_contents($mcp_file);
if ($content === false) {
    die("Error: Could not read .cursor/mcp.json\n");
}

echo "Current content (first 500 chars):\n";
echo substr($content, 0, 500) . "...\n\n";

// Replace all variations of network path with V:\
$replacements = [
    '\\\\amber\\htdocs\\' => 'V:\\',
    '//amber/htdocs/' => 'V:\\',
    '"\\\\amber\\htdocs\\' => '"V:\\',
    '"//amber/htdocs/' => '"V:\\',
    'G:/VbN/' => 'V:\\',
    'G:\\VbN\\' => 'V:\\',
];

$updated = $content;
$changed = false;

foreach ($replacements as $old => $new) {
    if (strpos($updated, $old) !== false) {
        $updated = str_replace($old, $new, $updated);
        $changed = true;
        echo "Replaced: {$old} → {$new}\n";
    }
}

if (!$changed) {
    echo "No network paths found to replace.\n";
    echo "Content already uses V:\\ or different format.\n";
    exit(0);
}

// Validate JSON before writing
$json_test = json_decode($updated, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Updated JSON is invalid: " . json_last_error_msg() . "\n");
}

// Backup original
$backup = $mcp_file . '.backup.' . date('Y-m-d_His');
copy($mcp_file, $backup);
echo "\nBackup created: {$backup}\n";

// Write updated content
file_put_contents($mcp_file, $updated);
echo "✅ Updated .cursor/mcp.json\n";
echo "All network paths changed to V:\\\n";
