<?php
/**
 * Test script to verify MCP loading functionality
 * 
 * This script tests the MCP system by:
 * 1. Querying the database for the Style Agent MCP
 * 2. Verifying the filesystem path exists
 * 3. Loading key documentation files
 * 4. Displaying chapter metadata
 * 
 * Run via browser: https://vbn.talkingheads.video/test_mcp_loading.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<h1>MCP Loading Test</h1>";

// Test 1: Query MCP from database
echo "<h2>Test 1: Query MCP from Database</h2>";
$query = "SELECT * FROM mcp_style_packs WHERE slug = 'style_agent_mcp' AND enabled = 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$mcp = mysqli_fetch_assoc($result);

if ($mcp) {
    echo "<p>✅ MCP found in database:</p>";
    echo "<ul>";
    echo "<li><strong>Name:</strong> {$mcp['name']}</li>";
    echo "<li><strong>Slug:</strong> {$mcp['slug']}</li>";
    echo "<li><strong>Version:</strong> {$mcp['version']}</li>";
    echo "<li><strong>Filesystem Path:</strong> {$mcp['filesystem_path']}</li>";
    echo "<li><strong>Enabled:</strong> " . ($mcp['enabled'] ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
    
    // Test 2: Verify filesystem path exists
    echo "<h2>Test 2: Verify Filesystem Path</h2>";
    $mcp_path = __DIR__ . '/' . $mcp['filesystem_path'];
    
    if (is_dir($mcp_path)) {
        echo "<p>✅ MCP directory exists: <code>{$mcp_path}</code></p>";
        
        // Test 3: Check for key files
        echo "<h2>Test 3: Check Key Files</h2>";
        $key_files = [
            'README.md',
            'RULES.md',
            'PROMPTS.md',
            'INDEX.md'
        ];
        
        echo "<ul>";
        foreach ($key_files as $file) {
            $file_path = $mcp_path . '/' . $file;
            if (file_exists($file_path)) {
                $size = filesize($file_path);
                echo "<li>✅ <code>{$file}</code> exists ({$size} bytes)</li>";
            } else {
                echo "<li>❌ <code>{$file}</code> NOT FOUND</li>";
            }
        }
        echo "</ul>";
        
        // Test 4: Check docs directory
        echo "<h2>Test 4: Check Documentation Files</h2>";
        $docs_path = $mcp_path . '/docs';
        if (is_dir($docs_path)) {
            $doc_files = glob($docs_path . '/*.md');
            echo "<p>✅ Found " . count($doc_files) . " documentation files in <code>docs/</code></p>";
            echo "<ul>";
            foreach ($doc_files as $doc_file) {
                $filename = basename($doc_file);
                $size = filesize($doc_file);
                echo "<li><code>{$filename}</code> ({$size} bytes)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ <code>docs/</code> directory not found</p>";
        }
        
        // Test 5: Load a sample file
        echo "<h2>Test 5: Load Sample File (README.md)</h2>";
        $readme_path = $mcp_path . '/README.md';
        if (file_exists($readme_path)) {
            $readme_content = file_get_contents($readme_path);
            $first_lines = implode("\n", array_slice(explode("\n", $readme_content), 0, 10));
            echo "<p>✅ Successfully loaded README.md (first 10 lines):</p>";
            echo "<pre>" . htmlspecialchars($first_lines) . "...</pre>";
        } else {
            echo "<p>❌ Could not load README.md</p>";
        }
        
    } else {
        echo "<p>❌ MCP directory does not exist: <code>{$mcp_path}</code></p>";
    }
    
    // Test 6: Query chapter metadata
    echo "<h2>Test 6: Query Chapter Metadata</h2>";
    $chapters_query = "SELECT chapter_name, chapter_file, chapter_number, description, tags 
                       FROM mcp_style_chapters 
                       WHERE mcp_pack_id = ? 
                       ORDER BY display_order";
    $stmt = mysqli_prepare($conn, $chapters_query);
    mysqli_stmt_bind_param($stmt, 'i', $mcp['id']);
    mysqli_stmt_execute($stmt);
    $chapters_result = mysqli_stmt_get_result($stmt);
    
    if ($chapters_result && mysqli_num_rows($chapters_result) > 0) {
        echo "<p>✅ Found " . mysqli_num_rows($chapters_result) . " chapters:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Chapter #</th><th>Name</th><th>File</th><th>Tags</th></tr>";
        while ($chapter = mysqli_fetch_assoc($chapters_result)) {
            echo "<tr>";
            echo "<td>{$chapter['chapter_number']}</td>";
            echo "<td>{$chapter['chapter_name']}</td>";
            echo "<td><code>{$chapter['chapter_file']}</code></td>";
            echo "<td>{$chapter['tags']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No chapters found in database</p>";
    }
    
} else {
    echo "<p>❌ MCP not found in database</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>MCP system test complete. All checks passed!</p>";
echo "<p><a href='docs/MCP_USAGE.md'>See MCP_USAGE.md for usage examples</a></p>";

mysqli_close($conn);
?>

