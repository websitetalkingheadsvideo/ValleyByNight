<?php
/**
 * Update MCP filesystem_path to use 'Agent' instead of 'agents'
 * 
 * This fixes the case sensitivity issue where local uses 'agents' 
 * but remote server uses 'Agent'
 * 
 * Run via browser: database/update_mcp_path_to_agent.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<h1>Update MCP Path to 'Agent'</h1>";

// Update the filesystem_path
$update_sql = "UPDATE mcp_style_packs 
               SET filesystem_path = 'Agent/style_agent' 
               WHERE slug = 'style_agent_mcp'";

if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✅ Successfully updated {$affected} row(s)</p>";
    
    // Show updated record
    $show_sql = "SELECT id, name, slug, filesystem_path FROM mcp_style_packs WHERE slug = 'style_agent_mcp'";
    $result = mysqli_query($conn, $show_sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<h2>Updated Record:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($row as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Error updating: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> The database now points to <code>Agent/style_agent</code></p>";

mysqli_close($conn);
?>

