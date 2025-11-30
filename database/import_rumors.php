<?php
/**
 * Import Rumors from JSON Files to Database
 * 
 * This script imports rumors from JSON files in two locations:
 * 1. data/rumors/*.json (array format - old structure)
 * 2. reference/rumors/*.json (single object format - new structure)
 * 
 * It converts the data to match the database schema and handles:
 * - Converting array fields to comma-separated strings
 * - Mapping old field names to new field names
 * - Handling missing fields with defaults
 * - Skipping duplicates based on rumor_name
 * 
 * Usage: Run via web browser
 * URL: https://vbn.talkingheads.video/database/import_rumors.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

echo "<h1>Import Rumors from JSON Files</h1>\n";
echo "<pre>\n";

/**
 * Convert array field to comma-separated string
 */
function arrayToString($value): string {
    if (is_array($value)) {
        return implode(',', array_filter($value, function($v) {
            return $v !== null && $v !== '';
        }));
    }
    if (is_string($value)) {
        return $value;
    }
    return '';
}

/**
 * Convert old format rumor to new database format
 */
function convertRumorToDbFormat(array $rumor, string $sourceFile = ''): ?array {
    // Get rumor_name from 'id' or 'rumor_name' field
    $rumorName = $rumor['rumor_name'] ?? $rumor['id'] ?? null;
    if (empty($rumorName)) {
        return null; // Skip rumors without identifier
    }
    
    // Map old fields to new fields
    $dbRumor = [
        'rumor_name' => (string) $rumorName,
        'title' => $rumor['title'] ?? 'Untitled Rumor',
        'rumor_text' => $rumor['rumor_text'] ?? $rumor['text'] ?? '',
        'truth_rating' => isset($rumor['truth_rating']) ? (int) $rumor['truth_rating'] : 1,
        'danger_rating' => isset($rumor['danger_rating']) ? (int) $rumor['danger_rating'] : 0,
        'source_type' => $rumor['source_type'] ?? null,
        'clan_tags' => arrayToString($rumor['clan_tags'] ?? []),
        'location_tags' => arrayToString($rumor['location_tags'] ?? []),
        'connects_to_plot_ids' => arrayToString($rumor['connects_to_plot_ids'] ?? []),
        'spread_likelihood' => $rumor['spread_likelihood'] ?? 'medium',
        'visibility' => $rumor['visibility'] ?? 'public',
        'nighttime_trigger_flags' => arrayToString($rumor['nighttime_trigger_flags'] ?? []),
        'storyteller_notes' => $rumor['storyteller_notes'] ?? $rumor['gm_notes'] ?? '',
    ];
    
    // Handle created_at and updated_at
    if (isset($rumor['created_at']) && !empty($rumor['created_at'])) {
        $dbRumor['created_at'] = $rumor['created_at'];
    }
    if (isset($rumor['updated_at']) && !empty($rumor['updated_at'])) {
        $dbRumor['updated_at'] = $rumor['updated_at'];
    }
    
    // Add source file info to storyteller_notes if available
    if (!empty($sourceFile) && !empty($dbRumor['storyteller_notes'])) {
        $dbRumor['storyteller_notes'] .= "\n\n[Imported from: {$sourceFile}]";
    } elseif (!empty($sourceFile)) {
        $dbRumor['storyteller_notes'] = "[Imported from: {$sourceFile}]";
    }
    
    return $dbRumor;
}

/**
 * Check if rumor_name already exists in database
 */
function rumorExists(mysqli $conn, string $rumorName): bool {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rumors WHERE rumor_name = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $rumorName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    
    return ($row[0] > 0);
}

/**
 * Insert rumor into database
 */
function insertRumor(mysqli $conn, array $rumor): bool {
    $sql = "INSERT INTO rumors (
        rumor_name, title, rumor_text, truth_rating, danger_rating,
        source_type, clan_tags, location_tags, connects_to_plot_ids,
        spread_likelihood, visibility, nighttime_trigger_flags, storyteller_notes,
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo "  ❌ Prepare failed: " . mysqli_error($conn) . "\n";
        return false;
    }
    
    // Handle timestamps
    $createdAt = $rumor['created_at'] ?? date('Y-m-d H:i:s');
    $updatedAt = $rumor['updated_at'] ?? date('Y-m-d H:i:s');
    
    mysqli_stmt_bind_param($stmt, "sssiiisssssssss",
        $rumor['rumor_name'],
        $rumor['title'],
        $rumor['rumor_text'],
        $rumor['truth_rating'],
        $rumor['danger_rating'],
        $rumor['source_type'],
        $rumor['clan_tags'],
        $rumor['location_tags'],
        $rumor['connects_to_plot_ids'],
        $rumor['spread_likelihood'],
        $rumor['visibility'],
        $rumor['nighttime_trigger_flags'],
        $rumor['storyteller_notes'],
        $createdAt,
        $updatedAt
    );
    
    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        echo "  ❌ Insert failed: " . mysqli_stmt_error($stmt) . "\n";
    }
    
    mysqli_stmt_close($stmt);
    return $success;
}

/**
 * Process a single JSON file
 */
function processJsonFile(string $filePath, string $sourceDir): array {
    global $conn;
    
    $stats = ['processed' => 0, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
    $filename = basename($filePath);
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Processing: {$filename}\n";
    echo str_repeat("=", 80) . "\n";
    
    $json = @file_get_contents($filePath);
    if ($json === false) {
        echo "❌ Could not read file: {$filePath}\n";
        $stats['errors']++;
        return $stats;
    }
    
    $data = json_decode($json, true);
    if ($data === null) {
        echo "❌ Invalid JSON in file: {$filePath}\n";
        $stats['errors']++;
        return $stats;
    }
    
    // Handle both array format and single object format
    $rumors = [];
    if (isset($data[0]) && is_array($data[0])) {
        // Array format: [ {rumor1}, {rumor2}, ... ]
        $rumors = $data;
    } elseif (is_array($data) && isset($data['rumor_name'])) {
        // Single object format: { rumor_name: "...", ... }
        $rumors = [$data];
    } else {
        echo "⚠ Warning: Unknown JSON structure in {$filename}\n";
        $stats['errors']++;
        return $stats;
    }
    
    echo "Found " . count($rumors) . " rumor(s) in file\n\n";
    
    foreach ($rumors as $index => $rumor) {
        $stats['processed']++;
        
        $dbRumor = convertRumorToDbFormat($rumor, $filename);
        if ($dbRumor === null) {
            echo "  ⚠ Skipping rumor #{$index}: Missing rumor_name/id\n";
            $stats['skipped']++;
            continue;
        }
        
        // Check if already exists
        if (rumorExists($conn, $dbRumor['rumor_name'])) {
            echo "  ⊘ Skipping '{$dbRumor['rumor_name']}': Already exists\n";
            $stats['skipped']++;
            continue;
        }
        
        // Insert into database
        if (insertRumor($conn, $dbRumor)) {
            echo "  ✓ Inserted '{$dbRumor['rumor_name']}': {$dbRumor['title']}\n";
            $stats['inserted']++;
        } else {
            echo "  ❌ Failed to insert '{$dbRumor['rumor_name']}'\n";
            $stats['errors']++;
        }
    }
    
    return $stats;
}

// Main import process
$totalStats = ['processed' => 0, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];

// Process files from data/rumors/
$dataDir = __DIR__ . '/../data/rumors';
if (is_dir($dataDir)) {
    echo "Scanning: data/rumors/\n";
    $files = glob($dataDir . '/*.json');
    if ($files) {
        foreach ($files as $file) {
            $stats = processJsonFile($file, 'data/rumors');
            $totalStats['processed'] += $stats['processed'];
            $totalStats['inserted'] += $stats['inserted'];
            $totalStats['skipped'] += $stats['skipped'];
            $totalStats['errors'] += $stats['errors'];
        }
    } else {
        echo "No JSON files found in data/rumors/\n";
    }
} else {
    echo "Directory not found: data/rumors/\n";
}

// Process files from reference/rumors/ (excluding template)
$refDir = __DIR__ . '/../reference/rumors';
if (is_dir($refDir)) {
    echo "\n\nScanning: reference/rumors/\n";
    $files = glob($refDir . '/*.json');
    if ($files) {
        foreach ($files as $file) {
            $filename = basename($file);
            // Skip template file
            if ($filename === 'rumor_template.json') {
                continue;
            }
            $stats = processJsonFile($file, 'reference/rumors');
            $totalStats['processed'] += $stats['processed'];
            $totalStats['inserted'] += $stats['inserted'];
            $totalStats['skipped'] += $stats['skipped'];
            $totalStats['errors'] += $stats['errors'];
        }
    } else {
        echo "No JSON files found in reference/rumors/\n";
    }
} else {
    echo "Directory not found: reference/rumors/\n";
}

// Summary
echo "\n\n" . str_repeat("=", 80) . "\n";
echo "IMPORT SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "Total Processed: {$totalStats['processed']}\n";
echo "Successfully Inserted: {$totalStats['inserted']}\n";
echo "Skipped (duplicates): {$totalStats['skipped']}\n";
echo "Errors: {$totalStats['errors']}\n";
echo str_repeat("=", 80) . "\n";

// Show current database count
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM rumors");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "\nTotal rumors in database: {$row['total']}\n";
    mysqli_free_result($result);
}

echo "</pre>\n";
mysqli_close($conn);
?>

