<?php
/**
 * Verify Character Image Files
 * Checks if image files exist and are accessible
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Image File Verification</title></head><body>";
echo "<h1>Character Image File Verification</h1>";
echo "<pre>";

$characters_to_check = [
    'Helena Crowly',
    'Charles "C.W." Whitford',
    'Naomi Blackbird',
    'Lilith Nightshade',
    'Butch Reed',
    'Alistaire'
];

$upload_dir = dirname(__DIR__) . '/uploads/characters/';
$web_path = 'uploads/characters/';

foreach ($characters_to_check as $name) {
    $sql = "SELECT id, character_name, character_image FROM characters WHERE character_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $image_filename = $row['character_image'];
            $file_path = $upload_dir . $image_filename;
            $file_exists = file_exists($file_path);
            $file_size = $file_exists ? filesize($file_path) : 0;
            
            echo sprintf("%-30s ID: %-5s Image: %-30s ", $row['character_name'], $row['id'], $image_filename ?: '(empty)');
            
            if ($image_filename) {
                if ($file_exists) {
                    echo sprintf("✅ EXISTS (%s)\n", formatBytes($file_size));
                } else {
                    echo "❌ FILE NOT FOUND\n";
                }
            } else {
                echo "⚠️  NO IMAGE SET\n";
            }
        } else {
            echo sprintf("%-30s NOT FOUND IN DATABASE\n", $name);
        }
        
        mysqli_stmt_close($stmt);
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

echo "\n";
echo "Upload directory: $upload_dir\n";
echo "Web path: $web_path\n";
echo "Directory exists: " . (is_dir($upload_dir) ? "YES" : "NO") . "\n";
echo "Directory writable: " . (is_writable($upload_dir) ? "YES" : "NO") . "\n";

echo "</pre></body></html>";
mysqli_close($conn);
?>

