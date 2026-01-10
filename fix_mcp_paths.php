<?php
/**
 * Fix MCP paths in .cursor/mcp.json
 * Updates all G:/VbN/ paths to \\amber\htdocs\
 */

$mcp_file = __DIR__ . '/.cursor/mcp.json';

if (!file_exists($mcp_file)) {
    die("Error: .cursor/mcp.json not found\n");
}

$content = file_get_contents($mcp_file);
if ($content === false) {
    die("Error: Could not read .cursor/mcp.json\n");
}

// Replace all variations of the old path
$replacements = [
    'G:/VbN/' => '\\\\amber\\htdocs\\',
    'G:\\VbN\\' => '\\\\amber\\htdocs\\',
    '"G:/VbN/' => '"\\\\amber\\htdocs\\',
    '"G:\\\\VbN\\\\' => '"\\\\amber\\htdocs\\',
];

$updated = $content;
foreach ($replacements as $old => $new) {
    $updated = str_replace($old, $new, $updated);
}

if ($updated === $content) {
    echo "No changes needed - paths already correct or pattern not found\n";
    echo "Current content preview:\n";
    echo substr($content, 0, 500) . "...\n";
} else {
    // Backup original
    $backup = $mcp_file . '.backup.' . date('Y-m-d_His');
    copy($mcp_file, $backup);
    echo "Backup created: $backup\n";
    
    // Write updated content
    file_put_contents($mcp_file, $updated);
    echo "✅ Updated .cursor/mcp.json\n";
    echo "All paths changed from G:/VbN/ to \\\\amber\\htdocs\\\n";
}
