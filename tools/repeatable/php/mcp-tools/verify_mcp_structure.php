<?php
/**
 * Verify MCP Structure
 * 
 * This script verifies the local MCP structure is complete.
 * It checks that all required directories and files exist.
 * 
 * Run via browser: verify_mcp_structure.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Use lowercase 'agents' path (standardized across local and remote)
$mcp_path = __DIR__ . '/../../agents/style_agent';
$errors = [];
$warnings = [];

echo "<h1>MCP Structure Verification</h1>";
echo "<p>Verifying structure at: <code>{$mcp_path}</code></p>";

// Check if MCP directory exists
if (!is_dir($mcp_path)) {
    die("<p style='color: red;'>❌ MCP directory does not exist: {$mcp_path}</p>");
}

echo "<h2>Directory Structure</h2>";
echo "<ul>";

// Required directories
$required_dirs = [
    'docs',
    'indexes',
    'rules',
    'prompts'
];

foreach ($required_dirs as $dir) {
    $dir_path = $mcp_path . '/' . $dir;
    if (is_dir($dir_path)) {
        $file_count = count(glob($dir_path . '/*'));
        echo "<li>✅ <code>{$dir}/</code> exists ({$file_count} items)</li>";
    } else {
        echo "<li style='color: red;'>❌ <code>{$dir}/</code> MISSING</li>";
        $errors[] = "Directory missing: {$dir}/";
    }
}

echo "</ul>";

// Required root files
echo "<h2>Required Root Files</h2>";
echo "<ul>";

$required_files = [
    'README.md',
    'RULES.md',
    'PROMPTS.md',
    'INDEX.md'
];

foreach ($required_files as $file) {
    $file_path = $mcp_path . '/' . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "<li>✅ <code>{$file}</code> exists ({$size} bytes)</li>";
    } else {
        echo "<li style='color: red;'>❌ <code>{$file}</code> MISSING</li>";
        $errors[] = "File missing: {$file}";
    }
}

echo "</ul>";

// Check docs directory contents
echo "<h2>Documentation Files (docs/)</h2>";
$docs_path = $mcp_path . '/docs';
if (is_dir($docs_path)) {
    $doc_files = glob($docs_path . '/*.md');
    echo "<p>Found " . count($doc_files) . " markdown files:</p>";
    echo "<ul>";
    foreach ($doc_files as $doc_file) {
        $filename = basename($doc_file);
        $size = filesize($doc_file);
        echo "<li><code>{$filename}</code> ({$size} bytes)</li>";
    }
    echo "</ul>";
    
    // Check for expected files
    $expected_docs = [
        'Valley_by_Night_Art_Bible_Master_Edition.md',
        'Art_Bible_I_PORTRAIT_SYSTEM__.md',
        'Art_Bible_II_CINEMATIC_SYSTEM__.md',
        'Art_Bible_III_LOCATION_&_ARCHITECTURE_SYSTEM__.md',
        'Art_Bible_IV_3D_ASSET_SYSTEM__.md',
        'Art_Bible_V_UI_&_WEB_ART_SYSTEM__.md',
        'Art_Bible_VI_STORYBOARDS_&_ANIMATICS__.md',
        'Art_Bible_VII_MARKETING_MATERIALS_SYSTEM__.md',
        'Art_Bible_VIII_FLOORPLAN_&_BLUEPRINT_SYSTEM__.md',
        'Art_Bible_IX_NAMING_CONVENTIONS_&_FOLDER_STRUCTURE__.md',
        'Art_Bible_X_MASTER_INDEX_&_INTEGRATION_LAYER__.md'
    ];
    
    $missing_docs = [];
    foreach ($expected_docs as $expected) {
        $expected_path = $docs_path . '/' . $expected;
        if (!file_exists($expected_path)) {
            $missing_docs[] = $expected;
        }
    }
    
    if (count($missing_docs) > 0) {
        echo "<p style='color: orange;'>⚠️ Missing expected documentation files:</p>";
        echo "<ul>";
        foreach ($missing_docs as $missing) {
            echo "<li><code>{$missing}</code></li>";
            $warnings[] = "Expected file missing: {$missing}";
        }
        echo "</ul>";
    }
}

// Check indexes directory
echo "<h2>Index Files (indexes/)</h2>";
$indexes_path = $mcp_path . '/indexes';
if (is_dir($indexes_path)) {
    $index_files = glob($indexes_path . '/*.md');
    echo "<p>Found " . count($index_files) . " index files:</p>";
    echo "<ul>";
    foreach ($index_files as $index_file) {
        $filename = basename($index_file);
        $size = filesize($index_file);
        echo "<li><code>{$filename}</code> ({$size} bytes)</li>";
    }
    echo "</ul>";
}

// Summary
echo "<hr>";
echo "<h2>Summary</h2>";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ All checks passed! Structure is complete.</p>";
} else {
    if (count($errors) > 0) {
        echo "<p style='color: red; font-weight: bold;'>❌ Found " . count($errors) . " error(s):</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }
    
    if (count($warnings) > 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Found " . count($warnings) . " warning(s):</p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li>{$warning}</li>";
        }
        echo "</ul>";
    }
}

// Directory structure reminder
echo "<h3>Required Local Directory Structure:</h3>";
echo "<pre>";
echo "/agents/\n";
echo "/agents/style_agent/\n";
echo "/agents/style_agent/docs/\n";
echo "/agents/style_agent/indexes/\n";
echo "/agents/style_agent/rules/\n";
echo "/agents/style_agent/prompts/\n";
echo "</pre>";
?>

