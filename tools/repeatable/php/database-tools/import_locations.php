<?php
/**
 * Location JSON Import Script
 * 
 * Imports location JSON files from reference/Locations/ into the database.
 * Supports upsert operations (insert if new, update if exists) based on name.
 * 
 * Usage:
 *   CLI: php database/import_locations.php [filename.json]
 *   Web: database/import_locations.php?file=filename.json
 *        database/import_locations.php?all=1
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Set output format
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Location Import</title><style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;} .success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;}</style></head><body><h1>Location JSON Import</h1>";
}

// Database connection
require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Statistics
$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => []
];

/**
 * Clean string value
 */
function cleanString($value): string {
    if (is_null($value) || $value === '') {
        return '';
    }
    if (is_array($value)) {
        return json_encode($value);
    }
    return trim((string)$value);
}

/**
 * Clean integer value
 */
function cleanInt($value, ?int $default = null): ?int {
    if (is_null($value) || $value === '') {
        return $default;
    }
    return (int)$value;
}

/**
 * Clean decimal value
 */
function cleanDecimal($value): ?string {
    if (is_null($value) || $value === '') {
        return null;
    }
    return (string)$value;
}

/**
 * Clean boolean value
 */
function cleanBool($value): int {
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    if (is_int($value)) {
        return $value ? 1 : 0;
    }
    if (is_string($value)) {
        $lower = strtolower(trim($value));
        return in_array($lower, ['1', 'true', 'yes', 'on']) ? 1 : 0;
    }
    return 0;
}

/**
 * Find existing location by name
 */
function findLocationByName(mysqli $conn, string $name): ?int {
    $stmt = $conn->prepare("SELECT id FROM locations WHERE name = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id'] : null;
}

/**
 * Insert or update location record
 */
function upsertLocation(mysqli $conn, array $data, bool $isPCHaven = false): int {
    $name = cleanString($data['name'] ?? '');
    if (empty($name)) {
        throw new Exception("Location name is required");
    }
    
    // Find existing location
    $location_id = findLocationByName($conn, $name);
    
    // Determine if this is a PC haven
    // If file is in PC Havens folder, or type is Haven and explicitly marked, or JSON has pc_haven field
    $pc_haven = $isPCHaven || 
                (cleanString($data['type'] ?? '') === 'Haven' && (cleanBool($data['pc_haven'] ?? 0) || $isPCHaven));
    
    // Prepare clean data - map JSON fields to database columns
    $cleanData = [
        'name' => $name,
        'type' => cleanString($data['type'] ?? 'Haven'),
        'summary' => cleanString($data['summary'] ?? ''),
        'description' => cleanString($data['description'] ?? ''),
        'notes' => cleanString($data['notes'] ?? ''),
        'status' => cleanString($data['status'] ?? 'Active'),
        'pc_haven' => $pc_haven ? 1 : 0,
        'status_notes' => cleanString($data['status_notes'] ?? ''),
        'district' => cleanString($data['district'] ?? ''),
        'address' => cleanString($data['address'] ?? ''),
        'latitude' => cleanDecimal($data['latitude'] ?? null),
        'longitude' => cleanDecimal($data['longitude'] ?? null),
        'owner_type' => cleanString($data['owner_type'] ?? 'Individual'),
        'owner_notes' => cleanString($data['owner_notes'] ?? ''),
        'faction' => cleanString($data['faction'] ?? ''),
        'access_control' => cleanString($data['access_control'] ?? 'Open'),
        'access_notes' => cleanString($data['access_notes'] ?? ''),
        'security_level' => cleanInt($data['security_level'] ?? 3, 3),
        'security_locks' => cleanBool($data['security_locks'] ?? 0),
        'security_alarms' => cleanBool($data['security_alarms'] ?? 0),
        'security_guards' => cleanBool($data['security_guards'] ?? 0),
        'security_hidden_entrance' => cleanBool($data['security_hidden_entrance'] ?? 0),
        'security_sunlight_protected' => cleanBool($data['security_sunlight_protected'] ?? 0),
        'security_warding_rituals' => cleanBool($data['security_warding_rituals'] ?? 0),
        'security_cameras' => cleanBool($data['security_cameras'] ?? 0),
        'security_reinforced' => cleanBool($data['security_reinforced'] ?? 0),
        'security_notes' => cleanString($data['security_notes'] ?? ''),
        'utility_blood_storage' => cleanBool($data['utility_blood_storage'] ?? 0),
        'utility_computers' => cleanBool($data['utility_computers'] ?? 0),
        'utility_library' => cleanBool($data['utility_library'] ?? 0),
        'utility_medical' => cleanBool($data['utility_medical'] ?? 0),
        'utility_workshop' => cleanBool($data['utility_workshop'] ?? 0),
        'utility_hidden_caches' => cleanBool($data['utility_hidden_caches'] ?? 0),
        'utility_armory' => cleanBool($data['utility_armory'] ?? 0),
        'utility_communications' => cleanBool($data['utility_communications'] ?? 0),
        'utility_notes' => cleanString($data['utility_notes'] ?? ''),
        'social_features' => cleanString($data['social_features'] ?? ''),
        'capacity' => cleanInt($data['capacity'] ?? null),
        'prestige_level' => cleanInt($data['prestige_level'] ?? null),
        'has_supernatural' => cleanBool($data['has_supernatural'] ?? 0),
        'node_points' => cleanInt($data['node_points'] ?? null),
        'node_type' => cleanString($data['node_type'] ?? ''),
        'ritual_space' => cleanString($data['ritual_space'] ?? ''),
        'magical_protection' => cleanString($data['magical_protection'] ?? ''),
        'cursed_blessed' => cleanString($data['cursed_blessed'] ?? ''),
        'parent_location_id' => cleanInt($data['parent_location_id'] ?? null),
        'relationship_type' => cleanString($data['relationship_type'] ?? ''),
        'relationship_notes' => cleanString($data['relationship_notes'] ?? ''),
        'image' => cleanString($data['image'] ?? '')
    ];
    
    if ($location_id) {
        // Update existing - using only fields that exist in the database
        $sql = "UPDATE locations SET 
            type = ?, summary = ?, description = ?, notes = ?, status = ?,
            district = ?, owner_type = ?, faction = ?, access_control = ?, security_level = ?,
            pc_haven = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $bind_result = $stmt->bind_param('ssssssssssii',
            $cleanData['type'], $cleanData['summary'], $cleanData['description'], $cleanData['notes'],
            $cleanData['status'], $cleanData['district'], $cleanData['owner_type'],
            $cleanData['faction'], $cleanData['access_control'], $cleanData['security_level'],
            $cleanData['pc_haven'], $location_id
        );
        
        if (!$bind_result) {
            throw new Exception("Bind failed: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        $stmt->close();
        return $location_id;
    } else {
        // Insert new - using only fields that exist in the database
        $sql = "INSERT INTO locations (
            name, type, summary, description, notes, status, district,
            owner_type, faction, access_control, security_level, pc_haven
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $bind_result = $stmt->bind_param('ssssssssssii',
            $cleanData['name'], $cleanData['type'], $cleanData['summary'], $cleanData['description'],
            $cleanData['notes'], $cleanData['status'], $cleanData['district'],
            $cleanData['owner_type'], $cleanData['faction'], $cleanData['access_control'],
            $cleanData['security_level'], $cleanData['pc_haven']
        );
        
        if (!$bind_result) {
            throw new Exception("Bind failed: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Insert failed: " . $stmt->error);
        }
        $location_id = $conn->insert_id;
        $stmt->close();
        return $location_id;
    }
}

/**
 * Process a single JSON file
 */
function processLocationFile(string $filepath): bool {
    global $conn, $stats;
    
    if (!file_exists($filepath)) {
        $stats['errors'][] = "File not found: $filepath";
        return false;
    }
    
    $json_content = file_get_contents($filepath);
    if ($json_content === false) {
        $stats['errors'][] = "Failed to read file: $filepath";
        return false;
    }
    
    $data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $stats['errors'][] = "Invalid JSON in $filepath: " . json_last_error_msg();
        return false;
    }
    
    if (empty($data['name'])) {
        // Skip template/reference files that don't have a name field
        $stats['skipped']++;
        return true; // Return true to indicate it was processed (just skipped)
    }
    
    // Check if file is in PC Havens folder
    $isPCHaven = (strpos($filepath, 'PC Havens') !== false);
    
    try {
        $was_update = findLocationByName($conn, $data['name']) !== null;
        $location_id = upsertLocation($conn, $data, $isPCHaven);
        
        $stats['processed']++;
        if ($was_update) {
            $stats['updated']++;
        } else {
            $stats['inserted']++;
        }
        return true;
    } catch (Exception $e) {
        $stats['errors'][] = "Error processing $filepath: " . $e->getMessage();
        return false;
    }
}

// Main execution
$reference_dir = __DIR__ . '/../reference/Locations/';
$pc_havens_dir = $reference_dir . 'PC Havens/';

if ($is_cli) {
    // CLI mode
    if (isset($argv[1])) {
        $filename = $argv[1];
        // Try main directory first, then PC Havens
        $filepath = $reference_dir . $filename;
        if (!file_exists($filepath)) {
            $filepath = $pc_havens_dir . $filename;
        }
        processLocationFile($filepath);
    } else {
        // Import all JSON files from both directories
        $files = array_merge(
            glob($reference_dir . '*.json'),
            glob($pc_havens_dir . '*.json')
        );
        foreach ($files as $file) {
            processLocationFile($file);
        }
    }
} else {
    // Web mode
    if (isset($_GET['all']) && $_GET['all'] == '1') {
        // Import all JSON files from both directories
        $files = array_merge(
            glob($reference_dir . '*.json'),
            glob($pc_havens_dir . '*.json')
        );
        echo "<p class='info'>Found " . count($files) . " JSON files to process...</p>";
        foreach ($files as $file) {
            $filename = basename($file);
            $file_data = json_decode(file_get_contents($file), true);
            $was_update = findLocationByName($conn, $file_data['name'] ?? '') !== null;
            echo "<p>Processing: <strong>$filename</strong>... ";
            if (processLocationFile($file)) {
                echo "<span class='success'>" . ($was_update ? "UPDATED" : "INSERTED") . "</span></p>";
            } else {
                echo "<span class='error'>FAILED</span></p>";
            }
        }
    } elseif (isset($_GET['file'])) {
        $filename = $_GET['file'];
        // Try main directory first, then PC Havens
        $filepath = $reference_dir . $filename;
        if (!file_exists($filepath)) {
            $filepath = $pc_havens_dir . $filename;
        }
        $file_data = json_decode(file_get_contents($filepath), true);
        $was_update = findLocationByName($conn, $file_data['name'] ?? '') !== null;
        echo "<p>Processing: <strong>$filename</strong>... ";
        if (processLocationFile($filepath)) {
            echo "<span class='success'>" . ($was_update ? "UPDATED" : "INSERTED") . "</span></p>";
        } else {
            echo "<span class='error'>FAILED</span></p>";
        }
    } else {
        echo "<p class='error'>Usage: ?file=filename.json or ?all=1</p>";
    }
}

// Print statistics
if ($is_cli) {
    echo "\n=== Import Statistics ===\n";
    echo "Processed: {$stats['processed']}\n";
    echo "Inserted: {$stats['inserted']}\n";
    echo "Updated: {$stats['updated']}\n";
    echo "Skipped: {$stats['skipped']}\n";
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "<h2>Import Statistics</h2>";
    echo "<ul>";
    echo "<li>Processed: <strong>{$stats['processed']}</strong></li>";
    echo "<li>Inserted: <strong class='success'>{$stats['inserted']}</strong></li>";
    echo "<li>Updated: <strong class='info'>{$stats['updated']}</strong></li>";
    echo "<li>Skipped: <strong>{$stats['skipped']}</strong></li>";
    echo "</ul>";
    if (!empty($stats['errors'])) {
        echo "<h3 class='error'>Errors:</h3><ul>";
        foreach ($stats['errors'] as $error) {
            echo "<li class='error'>$error</li>";
        }
        echo "</ul>";
    }
    echo "</body></html>";
}

mysqli_close($conn);

