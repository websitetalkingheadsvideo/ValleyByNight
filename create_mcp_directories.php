<?php
/**
 * Create Missing MCP Directories on Remote Server
 * 
 * This script creates the missing directory structure for the Style Agent MCP
 * on the remote server. Run this once to set up the directory structure.
 * 
 * Run via browser: https://vbn.talkingheads.video/create_mcp_directories.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Use lowercase 'agents' path (standardized across local and remote)
$base_path = __DIR__ . '/agents/style_agent';
$created = [];
$errors = [];

echo "<h1>Create MCP Directories</h1>";
echo "<p>Creating directories at: <code>{$base_path}</code></p>";

// Check if base directory exists
if (!is_dir($base_path)) {
    die("<p style='color: red;'>❌ Base directory does not exist: {$base_path}</p>");
}

// Directories to create
$directories = [
    'docs',
    'rules',
    'prompts'
];

echo "<h2>Creating Directories</h2>";
echo "<ul>";

foreach ($directories as $dir) {
    $dir_path = $base_path . '/' . $dir;
    
    if (is_dir($dir_path)) {
        echo "<li>✅ <code>{$dir}/</code> already exists</li>";
    } else {
        if (mkdir($dir_path, 0755, true)) {
            echo "<li style='color: green;'>✅ Created <code>{$dir}/</code></li>";
            $created[] = $dir;
        } else {
            echo "<li style='color: red;'>❌ Failed to create <code>{$dir}/</code></li>";
            $errors[] = "Failed to create: {$dir}/";
        }
    }
}

echo "</ul>";

// Verify structure
echo "<h2>Verification</h2>";
$all_dirs = ['docs', 'indexes', 'rules', 'prompts'];
$all_exist = true;

echo "<ul>";
foreach ($all_dirs as $dir) {
    $dir_path = $base_path . '/' . $dir;
    if (is_dir($dir_path)) {
        $file_count = count(glob($dir_path . '/*'));
        echo "<li>✅ <code>{$dir}/</code> exists ({$file_count} items)</li>";
    } else {
        echo "<li style='color: red;'>❌ <code>{$dir}/</code> MISSING</li>";
        $all_exist = false;
    }
}
echo "</ul>";

// Summary
echo "<hr>";
echo "<h2>Summary</h2>";

if (count($errors) === 0 && $all_exist) {
    echo "<p style='color: green; font-weight: bold;'>✅ All directories created successfully!</p>";
    
    if (count($created) > 0) {
        echo "<p>Created " . count($created) . " new directory(ies):</p>";
        echo "<ul>";
        foreach ($created as $dir) {
            echo "<li><code>{$dir}/</code></li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Upload Art Bible chapter files to <code>docs/</code></li>";
    echo "<li>Verify all files uploaded correctly</li>";
    echo "<li>Run <a href='verify_mcp_structure.php'>verify_mcp_structure.php</a> to confirm</li>";
    echo "</ol>";
} else {
    if (count($errors) > 0) {
        echo "<p style='color: red; font-weight: bold;'>❌ Errors occurred:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }
    
    if (!$all_exist) {
        echo "<p style='color: orange;'>⚠️ Some directories are still missing. Check file permissions.</p>";
        echo "<p>Required permissions: Directory must be writable (0755 recommended)</p>";
    }
}

// Show current permissions
echo "<h3>Current Directory Permissions</h3>";
echo "<ul>";
foreach ($all_dirs as $dir) {
    $dir_path = $base_path . '/' . $dir;
    if (is_dir($dir_path)) {
        $perms = substr(sprintf('%o', fileperms($dir_path)), -4);
        echo "<li><code>{$dir}/</code>: {$perms}</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='verify_mcp_structure.php'>← Back to Structure Verification</a></p>";
?>

