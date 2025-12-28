<?php
/**
 * Export All Havens to JSON
 * Extracts all locations with type = 'Haven' from the database and exports to JSON file
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/connect.php';

try {
    // Query all Havens from the database
    $havens = db_fetch_all($conn, 
        "SELECT * 
         FROM locations 
         WHERE type = 'Haven' 
         ORDER BY id ASC"
    );
    
    if (!$havens) {
        $havens = [];
    }
    
    // Prepare output data
    $output = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_havens' => count($havens),
        'havens' => $havens
    ];
    
    // Export to JSON file
    $outputFile = __DIR__ . '/data/havens.json';
    $jsonOutput = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Ensure the data directory exists
    $dataDir = dirname($outputFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    // Write to file
    file_put_contents($outputFile, $jsonOutput);
    
    // Output success message
    echo "✅ Successfully exported " . count($havens) . " Haven(s) to: " . $outputFile . "\n";
    echo "📄 File size: " . number_format(filesize($outputFile)) . " bytes\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

mysqli_close($conn);
?>

