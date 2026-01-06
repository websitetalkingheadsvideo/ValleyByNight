<?php
/**
 * Fix MCP filesystem_path to use lowercase 'agents' instead of 'Agent'
 * 
 * This ensures consistency after uploading agents folder to remote server.
 * 
 * Run via browser: database/fix_mcp_path_to_agents.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<h1>Fix MCP Path to 'agents' (lowercase)</h1>";

// Check current path
$check_sql = "SELECT id, name, slug, filesystem_path FROM mcp_style_packs WHERE slug = 'style_agent_mcp'";
$result = mysqli_query($conn, $check_sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$current = mysqli_fetch_assoc($result);

if (!$current) {
    die("<p style='color: red;'>❌ MCP not found in database</p>");
}

echo "<h2>Current Path:</h2>";
echo "<p><code>{$current['filesystem_path']}</code></p>";

// Update to lowercase if needed
if ($current['filesystem_path'] !== 'agents/style_agent') {
    $update_sql = "UPDATE mcp_style_packs 
                   SET filesystem_path = 'agents/style_agent' 
                   WHERE slug = 'style_agent_mcp'";
    
    if (mysqli_query($conn, $update_sql)) {
        $affected = mysqli_affected_rows($conn);
        echo "<p style='color: green;'>✅ Successfully updated {$affected} row(s) to use <code>agents/style_agent</code></p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Path is already correct: <code>agents/style_agent</code></p>";
}

// Show updated record
$result = mysqli_query($conn, $check_sql);
$updated = mysqli_fetch_assoc($result);

echo "<h2>Updated Record:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
foreach ($updated as $key => $value) {
    echo "<tr><td><strong>{$key}</strong></td><td><code>{$value}</code></td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Verification</h2>";
echo "<p>Character Agent paths are already using lowercase <code>agents/</code>:</p>";
echo "<ul>";
echo "<li><code>/agents/character_agent/data/Characters/</code></li>";
echo "<li><code>/agents/character_agent/data/History/</code></li>";
echo "<li><code>/agents/character_agent/data/Plots/</code></li>";
echo "</ul>";
echo "<p style='color: green;'>✅ All paths are consistent with lowercase <code>agents/</code></p>";

mysqli_close($conn);
?>

