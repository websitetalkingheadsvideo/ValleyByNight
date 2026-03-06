<?php
/**
 * Connect to Style Agent MCP
 * 
 * Demonstrates how to connect to and load the Style Agent MCP from the database.
 * 
 * Run via browser: connect_to_style_agent_mcp.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/supabase_client.php';

echo "<h1>Connect to Style Agent MCP</h1>";

// Step 1: Query MCP from database
echo "<h2>Step 1: Query MCP from Database</h2>";
$mcpRows = supabase_table_get('mcp_style_packs', [
    'select' => '*',
    'slug' => 'eq.style_agent_mcp',
    'enabled' => 'eq.1',
    'limit' => '1'
]);
$mcp = !empty($mcpRows) ? $mcpRows[0] : null;

if (!$mcp) {
    die("<p style='color: red;'>❌ Style Agent MCP not found or disabled in database</p>");
}

echo "<p style='color: green;'>✅ MCP found in database</p>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
foreach ($mcp as $key => $value) {
    echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
}
echo "</table>";

// Step 2: Verify filesystem path
echo "<h2>Step 2: Verify Filesystem Path</h2>";
$mcp_path = __DIR__ . '/' . $mcp['filesystem_path'];

if (is_dir($mcp_path)) {
    echo "<p style='color: green;'>✅ MCP directory exists: <code>{$mcp_path}</code></p>";
} else {
    echo "<p style='color: red;'>❌ MCP directory not found: <code>{$mcp_path}</code></p>";
    echo "<p>Please verify the path is correct or run <a href='create_mcp_directories.php'>create_mcp_directories.php</a></p>";
    exit;
}

// Step 3: Load MCP Documentation
echo "<h2>Step 3: Load MCP Documentation Files</h2>";

$doc_files = [
    'README.md' => 'MCP Overview',
    'RULES.md' => 'Distilled Rules',
    'PROMPTS.md' => 'Prompt Templates',
    'INDEX.md' => 'Chapter Index'
];

echo "<ul>";
foreach ($doc_files as $filename => $description) {
    $file_path = $mcp_path . '/' . $filename;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        $first_line = '';
        $content = file_get_contents($file_path);
        $lines = explode("\n", $content);
        if (count($lines) > 0) {
            $first_line = trim($lines[0]);
        }
        echo "<li style='color: green;'>✅ <strong>{$description}</strong> (<code>{$filename}</code>) - {$size} bytes";
        if ($first_line) {
            echo "<br><em>" . htmlspecialchars(substr($first_line, 0, 100)) . "...</em>";
        }
        echo "</li>";
    } else {
        echo "<li style='color: red;'>❌ <strong>{$description}</strong> (<code>{$filename}</code>) - NOT FOUND</li>";
    }
}
echo "</ul>";

// Step 4: List Documentation Chapters
echo "<h2>Step 4: List Documentation Chapters</h2>";
$docs_path = $mcp_path . '/docs';
if (is_dir($docs_path)) {
    $doc_files = glob($docs_path . '/*.md');
    echo "<p>Found " . count($doc_files) . " chapter files:</p>";
    echo "<ul>";
    foreach ($doc_files as $doc_file) {
        $filename = basename($doc_file);
        $size = filesize($doc_file);
        echo "<li><code>{$filename}</code> ({$size} bytes)</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ <code>docs/</code> directory not found</p>";
}

// Step 5: Query Chapter Metadata
echo "<h2>Step 5: Query Chapter Metadata from Database</h2>";
$chapters_result = supabase_table_get('mcp_style_chapters', [
    'select' => 'chapter_name,chapter_file,chapter_number,description,tags',
    'mcp_pack_id' => 'eq.' . (string) $mcp['id'],
    'order' => 'display_order.asc'
]);

if (!empty($chapters_result)) {
    echo "<p>Found " . count($chapters_result) . " registered chapters:</p>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>#</th><th>Chapter Name</th><th>File</th><th>Tags</th></tr>";
    foreach ($chapters_result as $chapter) {
        echo "<tr>";
        echo "<td>{$chapter['chapter_number']}</td>";
        echo "<td><strong>{$chapter['chapter_name']}</strong></td>";
        echo "<td><code>{$chapter['chapter_file']}</code></td>";
        echo "<td>{$chapter['tags']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No chapter metadata found in database</p>";
}

// Step 6: Example Usage - Load a Specific Chapter
echo "<h2>Step 6: Example - Load Specific Chapter</h2>";
$example_chapter = 'Art_Bible_I_PORTRAIT_SYSTEM__.md';
$example_path = $docs_path . '/' . $example_chapter;

if (file_exists($example_path)) {
    $content = file_get_contents($example_path);
    $lines = explode("\n", $content);
    $preview = array_slice($lines, 0, 15);
    
    echo "<p>✅ Successfully loaded <code>{$example_chapter}</code></p>";
    echo "<p><strong>Preview (first 15 lines):</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars(implode("\n", $preview));
    echo "\n...</pre>";
    echo "<p><em>Full file: " . count($lines) . " lines, " . strlen($content) . " bytes</em></p>";
} else {
    echo "<p style='color: red;'>❌ Example chapter not found: <code>{$example_chapter}</code></p>";
}

// Step 7: MCP Connection Summary
echo "<hr>";
echo "<h2>Connection Summary</h2>";
echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50;'>";
echo "<h3 style='margin-top: 0;'>✅ Successfully Connected to Style Agent MCP</h3>";
echo "<ul>";
echo "<li><strong>MCP Name:</strong> {$mcp['name']}</li>";
echo "<li><strong>Version:</strong> {$mcp['version']}</li>";
echo "<li><strong>Path:</strong> <code>{$mcp['filesystem_path']}</code></li>";
echo "<li><strong>Status:</strong> " . ($mcp['enabled'] ? 'Enabled' : 'Disabled') . "</li>";
echo "<li><strong>Last Updated:</strong> {$mcp['last_updated']}</li>";
echo "</ul>";
echo "</div>";

// Usage Example Code
echo "<h2>Usage Example Code</h2>";
echo "<p>Here's how to connect to the MCP in your own code:</p>";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;'>";
echo htmlspecialchars('<?php
require_once __DIR__ . \'/includes/supabase_client.php\';

// Query MCP from database
$rows = supabase_table_get(\'mcp_style_packs\', [
    \'select\' => \'*\',
    \'slug\' => \'eq.style_agent_mcp\',
    \'enabled\' => \'eq.1\',
    \'limit\' => \'1\'
]);
$mcp = !empty($rows) ? $rows[0] : null;

if ($mcp) {
    $mcp_path = __DIR__ . \'/\' . $mcp[\'filesystem_path\'];
    
    // Load README
    $readme = file_get_contents($mcp_path . \'/README.md\');
    
    // Load RULES
    $rules = file_get_contents($mcp_path . \'/RULES.md\');
    
    // Load specific chapter
    $portrait_rules = file_get_contents($mcp_path . \'/docs/Art_Bible_I_PORTRAIT_SYSTEM__.md\');
    
    // Use the content...
}
?>');
echo "</pre>";

echo "<hr>";
echo "<p><a href='test_mcp_loading.php'>← Back to MCP Loading Test</a> | ";
echo "<a href='docs/MCP_USAGE.md'>View Full Usage Guide</a></p>";
?>

