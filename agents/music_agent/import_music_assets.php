<?php
/**
 * Music Assets Import Script
 * 
 * Idempotent import script that reads music_registry.json and inserts/updates
 * asset records in the music_assets table.
 * 
 * Only processes assets (not cues, bindings, or mix_profiles).
 * Uses safe upsert logic to prevent duplicates and only update when data changes.
 * 
 * Usage: Access via web browser - returns JSON response with import results
 */

declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include database connection
if (!file_exists(__DIR__ . '/../../includes/connect.php')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection file not found'], JSON_PRETTY_PRINT);
    exit;
}

require_once __DIR__ . '/../../includes/connect.php';

// Check if database connection exists
if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . (isset($conn) ? mysqli_connect_error() : 'Connection not established')], JSON_PRETTY_PRINT);
    exit;
}

// Include registry I/O functions for path constant
if (!file_exists(__DIR__ . '/music_registry_io.php')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Registry I/O file not found'], JSON_PRETTY_PRINT);
    exit;
}

require_once __DIR__ . '/music_registry_io.php';

// Registry path (from music_registry_io.php constant)
if (!defined('REGISTRY_PATH')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'REGISTRY_PATH constant not defined'], JSON_PRETTY_PRINT);
    exit;
}

$registry_path = REGISTRY_PATH;

/**
 * Determine asset status based on asset data
 * Defaults to "placeholder" unless JSON explicitly has status field
 * 
 * @param array $asset Asset data from JSON
 * @return string Status value
 */
function determine_asset_status(array $asset): string {
    // Check if JSON explicitly has a status field
    if (isset($asset['status']) && !empty($asset['status'])) {
        return sanitize_string($asset['status']);
    }
    
    // Fallback: Check for explicit status indicators in notes or other fields
    $notes = strtolower($asset['notes'] ?? '');
    $title = strtolower($asset['title'] ?? '');
    
    // Look for production/final indicators
    if (preg_match('/\b(final|production|complete|ready)\b/i', $notes . ' ' . $title)) {
        return 'production';
    }
    
    // Default to placeholder for safety
    return 'placeholder';
}

/**
 * Extract source type string from source object or string
 * BUG FIX: Previously stored full JSON object in source column.
 * Now correctly extracts just the type: envato_elements, envato_generative, local_file, etc.
 * Full source object is preserved in metadata field.
 * 
 * @param array|string $source Source object from asset or string
 * @return string Source type string (sanitized)
 */
function format_source_string($source): string {
    // If source is already a string, sanitize and return
    if (is_string($source)) {
        return sanitize_string($source);
    }
    
    // If source is an object/array, extract the type field
    if (is_array($source) && isset($source['type'])) {
        return sanitize_string($source['type']);
    }
    
    // Fallback
    return 'unknown';
}

/**
 * Sanitize string values for database storage
 * Removes whitespace, newlines, tabs, carriage returns from identifier-like strings
 * 
 * @param string $str String to sanitize
 * @return string Sanitized string
 */
function sanitize_string(string $str): string {
    // Trim whitespace
    $str = trim($str);
    
    // Remove control characters (\r, \n, \t) from identifier-like strings
    $str = str_replace(["\r", "\n", "\t"], '', $str);
    
    return $str;
}

/**
 * Convert tags array to comma-separated string
 * Sanitizes each tag before joining
 * 
 * @param array $tags Tags array from asset
 * @return string Comma-separated tags (sanitized)
 */
function format_tags_string(array $tags): string {
    if (empty($tags)) {
        return '';
    }
    
    // Sanitize each tag and filter out empty ones
    $sanitized_tags = array_filter(array_map('sanitize_string', $tags));
    
    return implode(',', $sanitized_tags);
}

/**
 * Repair existing bad rows in database (one-time migration)
 * Fixes rows where source column contains JSON instead of type string.
 * Guarded to only run once per script execution.
 * 
 * @param mysqli $conn Database connection
 * @return array Statistics: repaired, errors
 */
function repair_bad_source_rows(mysqli $conn): array {
    $stats = [
        'repaired' => 0,
        'errors' => []
    ];
    
    // Find rows where source begins with '{' (indicating JSON was stored)
    $find_bad_query = "SELECT id, source, metadata FROM music_assets WHERE source LIKE '{%'";
    $result = mysqli_query($conn, $find_bad_query);
    
    if ($result === false) {
        $stats['errors'][] = "Error finding bad rows: " . mysqli_error($conn);
        return $stats;
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $asset_id = $row['id'];
        $bad_source = $row['source'];
        $existing_metadata = $row['metadata'];
        
        // Try to parse the JSON source string
        $source_decoded = json_decode($bad_source, true);
        
        if ($source_decoded !== null && isset($source_decoded['type'])) {
            // Successfully parsed - extract type and merge into metadata
            $correct_source = sanitize_string($source_decoded['type']);
            
            // Merge source object into metadata
            $metadata_decoded = null;
            if (!empty($existing_metadata)) {
                $metadata_decoded = json_decode($existing_metadata, true);
            }
            
            if ($metadata_decoded === null) {
                // No existing metadata, create new with source
                $metadata_decoded = ['source' => $source_decoded];
            } else {
                // Merge source into existing metadata
                $metadata_decoded['source'] = $source_decoded;
            }
            
            $new_metadata_json = json_encode($metadata_decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Update the row
            $update_query = "UPDATE music_assets 
                            SET source = ?, 
                                metadata = ?,
                                updated_at = NOW() 
                            WHERE id = ?";
            
            $update_result = db_execute($conn, $update_query, 'sss', [
                $correct_source,
                $new_metadata_json,
                $asset_id
            ]);
            
            if ($update_result === false) {
                $stats['errors'][] = "Failed to repair asset {$asset_id}: " . mysqli_error($conn);
            } else {
                $stats['repaired']++;
            }
        } else {
            // JSON parsing failed (likely truncated) - will be fixed during normal import
            // This is expected and not a fatal error - the import phase will fix it
            // Don't add to errors array since it's not a failure, just a note
        }
    }
    
    mysqli_free_result($result);
    
    return $stats;
}

/**
 * Main import function
 * 
 * @param mysqli $conn Database connection
 * @param string $registry_path Path to music_registry.json
 * @return array Statistics: inserted, updated, skipped, errors
 */
function import_music_assets(mysqli $conn, string $registry_path): array {
    $stats = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    // Load registry JSON
    if (!file_exists($registry_path)) {
        $stats['errors'][] = "Registry file not found: {$registry_path}";
        return $stats;
    }
    
    $json_content = file_get_contents($registry_path);
    if ($json_content === false) {
        $stats['errors'][] = "Failed to read registry file";
        return $stats;
    }
    
    $registry = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $stats['errors'][] = "Invalid JSON: " . json_last_error_msg();
        return $stats;
    }
    
    // Validate assets array exists
    if (!isset($registry['assets']) || !is_array($registry['assets'])) {
        $stats['errors'][] = "No assets array found in registry";
        return $stats;
    }
    
    // Process each asset
    foreach ($registry['assets'] as $asset) {
        // Validate required fields
        if (!isset($asset['asset_id'])) {
            $stats['errors'][] = "Asset missing asset_id, skipping";
            $stats['skipped']++;
            continue;
        }
        
        $asset_id = $asset['asset_id'];
        
        // Map fields according to requirements
        // BUG FIX: source should be just the type string, not the full JSON object
        $id = sanitize_string($asset_id);
        $title = sanitize_string($asset['title'] ?? '');
        $source_obj = $asset['source'] ?? [];
        $source = format_source_string($source_obj); // Extract type string only
        $tags_array = $asset['tags'] ?? [];
        $tags = format_tags_string($tags_array);
        $file_count = isset($asset['files']) && is_array($asset['files']) ? count($asset['files']) : 0;
        $status = determine_asset_status($asset);
        // Full asset JSON (including complete source object) goes into metadata
        $metadata_json = json_encode($asset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Check if asset already exists
        $check_query = "SELECT id, title, source, status, file_count, tags, metadata, updated_at 
                        FROM music_assets 
                        WHERE id = ?";
        $existing = db_select($conn, $check_query, 's', [$id]);
        
        if ($existing === false) {
            $stats['errors'][] = "Error checking for existing asset {$asset_id}: " . mysqli_error($conn);
            $stats['skipped']++;
            continue;
        }
        
        $existing_row = mysqli_fetch_assoc($existing);
        
        if ($existing_row === null) {
            // Insert new asset
            $insert_query = "INSERT INTO music_assets 
                            (id, title, source, status, file_count, tags, metadata, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = db_execute($conn, $insert_query, 'sssiiss', [
                $id,
                $title,
                $source,
                $status,
                $file_count,
                $tags,
                $metadata_json
            ]);
            
            if ($result === false) {
                $stats['errors'][] = "Failed to insert asset {$asset_id}: " . mysqli_error($conn);
                $stats['skipped']++;
            } else {
                $stats['inserted']++;
            }
        } else {
            // Check if data has changed (upsert logic: only update if changed)
            $existing_metadata = $existing_row['metadata'] ?? '';
            $existing_metadata_decoded = json_decode($existing_metadata, true);
            
            // Compare key fields to determine if update is needed
            $has_changes = false;
            
            if ($existing_row['title'] !== $title ||
                $existing_row['source'] !== $source ||
                $existing_row['status'] !== $status ||
                $existing_row['file_count'] != $file_count ||
                $existing_row['tags'] !== $tags) {
                $has_changes = true;
            }
            
            // Compare metadata JSON (normalize for comparison)
            // Use json_encode with same flags for both to ensure consistent comparison
            $existing_metadata_normalized = json_encode($existing_metadata_decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $new_metadata_normalized = json_encode($asset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if ($existing_metadata_normalized !== $new_metadata_normalized) {
                $has_changes = true;
            }
            
            if ($has_changes) {
                // Update existing asset
                $update_query = "UPDATE music_assets 
                                SET title = ?, 
                                    source = ?, 
                                    status = ?, 
                                    file_count = ?, 
                                    tags = ?, 
                                    metadata = ?, 
                                    updated_at = NOW() 
                                WHERE id = ?";
                
                $result = db_execute($conn, $update_query, 'sssiiss', [
                    $title,
                    $source,
                    $status,
                    $file_count,
                    $tags,
                    $metadata_json,
                    $id
                ]);
                
                if ($result === false) {
                    $stats['errors'][] = "Failed to update asset {$asset_id}: " . mysqli_error($conn);
                    $stats['skipped']++;
                } else {
                    $stats['updated']++;
                }
            } else {
                // No changes, skip update
                $stats['skipped']++;
            }
        }
    }
    
    return $stats;
}

// Main execution - Web mode only
header('Content-Type: application/json');

try {
    // Step 1: Repair existing bad rows (one-time migration)
    // This fixes rows where source contains JSON instead of type string
    $repair_stats = repair_bad_source_rows($conn);
    
    // Step 2: Import/update assets from JSON
    $import_stats = import_music_assets($conn, $registry_path);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
    exit;
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
    exit;
}

// Combine statistics
// Success is based on import stats only - repair warnings are non-fatal
$response = [
    'success' => empty($import_stats['errors']),
    'repair' => [
        'repaired' => $repair_stats['repaired'],
        'warnings' => $repair_stats['errors'] ?? []
    ],
    'stats' => [
        'inserted' => $import_stats['inserted'],
        'updated' => $import_stats['updated'],
        'skipped' => $import_stats['skipped']
    ]
];

// Only show import errors as actual errors
if (!empty($import_stats['errors'])) {
    $response['errors'] = $import_stats['errors'];
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;

/**
 * VERIFICATION SQL QUERIES
 * 
 * After running the import, verify the data is correct:
 * 
 * -- Check that source column contains only type strings (not JSON):
 * SELECT id, title, source, LENGTH(source) as source_length 
 * FROM music_assets 
 * WHERE source LIKE '{%' OR LENGTH(source) > 50;
 * -- Should return 0 rows (no JSON in source column)
 * 
 * -- Verify source values are one of the expected types:
 * SELECT DISTINCT source 
 * FROM music_assets 
 * ORDER BY source;
 * -- Should show: envato_elements, envato_generative, local_file, other, unknown
 * 
 * -- Check that metadata contains full asset JSON with source object:
 * SELECT id, 
 *        JSON_EXTRACT(metadata, '$.source.type') as metadata_source_type,
 *        source as db_source_column,
 *        JSON_EXTRACT(metadata, '$.title') as metadata_title
 * FROM music_assets 
 * LIMIT 5;
 * -- metadata_source_type should match db_source_column
 * 
 * -- Verify all assets have metadata:
 * SELECT COUNT(*) as total_assets,
 *        COUNT(metadata) as assets_with_metadata
 * FROM music_assets;
 * -- Both counts should match
 */

