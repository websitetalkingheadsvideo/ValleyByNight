<?php
/**
 * Check which havens in database are missing JSON files
 * Web accessible via your site's database/check_missing_haven_files.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Missing Haven Files</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .missing{color:#f00;} .found{color:#0f0;}</style></head><body><h1>Missing Haven JSON Files</h1>";
}

require_once __DIR__ . '/../includes/connect.php';

$conn = getDbConnection();
if (!$conn) {
    die("Database connection failed\n");
}

// Get all havens from database
$query = "SELECT id, name, type FROM locations WHERE type = 'Haven' ORDER BY name";
$result = mysqli_query($conn, $query);

$db_havens = [];
while ($row = mysqli_fetch_assoc($result)) {
    $db_havens[] = $row;
}

// Get all JSON files in Locations folder (excluding PC Havens)
$reference_dir = __DIR__ . '/../reference/Locations/';
$json_files = glob($reference_dir . '*.json');

$existing_files = [];
foreach ($json_files as $file) {
    $basename = basename($file);
    // Skip templates and style summaries
    if (stripos($basename, 'template') !== false || 
        stripos($basename, 'style') !== false ||
        stripos($basename, 'layout') !== false) {
        continue;
    }
    
    $data = json_decode(file_get_contents($file), true);
    if ($data && isset($data['name'])) {
        $existing_files[strtolower($data['name'])] = $basename;
    }
}

if ($is_cli) {
    echo "=== MISSING HAVEN JSON FILES ===\n\n";
} else {
    echo "<h2>Database Havens: " . count($db_havens) . "</h2>";
    echo "<h2>JSON Files Found: " . count($existing_files) . "</h2>";
}

$missing = [];
foreach ($db_havens as $haven) {
    $name_lower = strtolower($haven['name']);
    if (!isset($existing_files[$name_lower])) {
        $missing[] = $haven;
        if ($is_cli) {
            echo "MISSING: {$haven['name']} (ID: {$haven['id']})\n";
        } else {
            echo "<p class='missing'>MISSING: <strong>{$haven['name']}</strong> (ID: {$haven['id']})</p>";
        }
    }
}

if ($is_cli) {
    echo "\n=== SUMMARY ===\n";
    echo "Missing files: " . count($missing) . "\n";
    
    if (count($missing) > 0) {
        echo "\nMissing haven names:\n";
        foreach ($missing as $haven) {
            echo "- {$haven['name']}\n";
        }
    }
} else {
    echo "<h2>Summary: " . count($missing) . " missing files</h2>";
    
    if (count($missing) > 0) {
        echo "<ul>";
        foreach ($missing as $haven) {
            echo "<li class='missing'>{$haven['name']} (ID: {$haven['id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='found'>All havens have JSON files!</p>";
    }
    echo "</body></html>";
}

mysqli_close($conn);
