<?php
/**
 * Check .cursor/mcp.json for errors
 */

$mcp_file = __DIR__ . '/../../.cursor/mcp.json';

if (!file_exists($mcp_file)) {
    die("Error: .cursor/mcp.json not found\n");
}

$content = file_get_contents($mcp_file);
echo "File exists: " . filesize($mcp_file) . " bytes\n\n";

// Check JSON validity
$json = json_decode($content, true);
$error = json_last_error();

if ($error !== JSON_ERROR_NONE) {
    echo "❌ JSON ERROR: " . json_last_error_msg() . "\n";
    echo "Error code: {$error}\n\n";
    
    // Find the problematic line
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        $test_json = json_decode(substr($content, 0, strpos($content, $line) + strlen($line)), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error around line " . ($i + 1) . ":\n";
            echo $line . "\n\n";
            break;
        }
    }
} else {
    echo "✅ JSON is valid\n\n";
    echo "Structure:\n";
    print_r($json);
}
