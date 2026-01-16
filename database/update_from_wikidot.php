<?php
declare(strict_types=1);

/**
 * Update Merits and Flaws from Wikidot Pages
 * 
 * This script fetches and parses the Wikidot pages for merits and flaws,
 * extracts costs and descriptions, and updates the database.
 * 
 * Sources:
 * - http://theanarchstate.wikidot.com/merits
 * - http://theanarchstate.wikidot.com/flaws
 * 
 * Run via browser: database/update_from_wikidot.php
 * Or via CLI: php database/update_from_wikidot.php
 */

// Set error reporting BEFORE anything else
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Disable output buffering immediately
while (ob_get_level()) {
    ob_end_clean();
}

// Output HTML header immediately so title shows even if errors occur
echo "<!DOCTYPE html>";
echo "<html><head><title>Update Merits & Flaws from Wikidot</title></head><body>";
echo "<p>Script started at " . date('Y-m-d H:i:s') . "</p>";
flush();

// Increase execution time for processing many items
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', 300);

echo "<p>Loading database connection...</p>";
flush();

try {
    require_once __DIR__ . '/../includes/connect.php';
    echo "<p>Database connection file loaded</p>";
    flush();
} catch (Throwable $e) {
    echo "<h2>ERROR: Error loading connect.php</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</body></html>";
    flush();
    exit;
}

if (!isset($conn) || !$conn) {
    echo "<h2>ERROR: Database Connection Failed</h2>";
    if (function_exists('mysqli_connect_error')) {
        echo "<p>Error: " . htmlspecialchars(mysqli_connect_error()) . "</p>";
    } else {
        echo "<p>Connection variable not set. Check includes/connect.php</p>";
    }
    echo "</body></html>";
    flush();
    exit;
}

echo "<p>Database connected successfully</p>";
flush();

// Function to fetch and parse Wikidot page
function fetchWikidotPage(string $url): string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $content === false) {
        throw new Exception("Failed to fetch {$url}: HTTP {$httpCode}");
    }
    
    return $content;
}

// Function to parse Wikidot content for merits/flaws
function parseWikidotContent(string $content, string $type): array {
    $items = [];
    
    // Pattern 1: HTML format - <p><strong>Name (Xpt Merit/Flaw)</strong><br>Description...
    // Format: <p><strong>Absent-Minded (3pt Flaw)</strong><br>Description text...</p>
    // Also handles: <p><strong>Name (Xpt Merit/Flaw)</strong>Description...</p>
    $html_pattern = '/<p><strong>([^<]+?)\s*\(([^)]+?)(?:pt|point|points)?\s*(Merit|Flaw)\)<\/strong>(?:<br\s*\/?>)?\s*(.*?)<\/p>/is';
    
    // Pattern 2: Markdown format (fallback) - **Name (Xpt Merit/Flaw)** followed by description
    // Format: **Absent-Minded (3pt Flaw)**\nDescription text...
    $markdown_pattern = '/\*\*([^*]+?)\s*\(([^)]+?)(?:pt|point|points)?\s*(Merit|Flaw)\)\*\*/i';
    
    // Try HTML format first
    preg_match_all($html_pattern, $content, $html_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    
    if (count($html_matches) > 0) {
        foreach ($html_matches as $match) {
            $name = trim($match[1][0]);
            $cost_raw = trim($match[2][0]);
            $item_type = trim($match[3][0]);
            $description_text = isset($match[4]) ? $match[4][0] : '';
            
            // Only process if it matches the type we're looking for
            if (strtolower($item_type) === strtolower($type)) {
                // Clean up description
                $description = cleanDescription($description_text);
                
                // Skip if description is too short
                if (strlen($description) > 20) {
                    $cost = normalizeCost($cost_raw);
                    
                    // Use name as key, but allow overwriting if we find a better match
                    if (!isset($items[$name]) || strlen($description) > strlen($items[$name]['description'])) {
                        $items[$name] = [
                            'name' => $name,
                            'cost' => $cost,
                            'description' => $description
                        ];
                    }
                }
            }
        }
    } else {
        // Fallback to markdown format
        preg_match_all($markdown_pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        
        foreach ($matches as $idx => $match) {
            $name = trim($match[1][0]);
            $cost_raw = trim($match[2][0]);
            $item_type = trim($match[3][0]);
            
            // Only process if it matches the type we're looking for
            if (strtolower($item_type) === strtolower($type)) {
                // Find where this match ends
                $match_end = $match[0][1] + strlen($match[0][0]);
                
                // Find where the next entry starts (next **Name (Xpt...) or section header ##)
                $next_entry_pos = strlen($content);
                if (isset($matches[$idx + 1])) {
                    $next_entry_pos = $matches[$idx + 1][0][1];
                }
                
                // Also check for section headers
                $next_section = strpos($content, '##', $match_end);
                if ($next_section !== false && $next_section < $next_entry_pos) {
                    $next_entry_pos = $next_section;
                }
                
                // Extract description text between this entry and the next
                $description_text = substr($content, $match_end, $next_entry_pos - $match_end);
                
                // Clean up description
                $description = cleanDescription($description_text);
                
                // Skip if description is too short
                if (strlen($description) > 20) {
                    $cost = normalizeCost($cost_raw);
                    
                    // Use name as key, but allow overwriting if we find a better match
                    if (!isset($items[$name]) || strlen($description) > strlen($items[$name]['description'])) {
                        $items[$name] = [
                            'name' => $name,
                            'cost' => $cost,
                            'description' => $description
                        ];
                    }
                }
            }
        }
    }
    
    return $items;
}

// Function to normalize cost format
function normalizeCost(string $cost): string {
    $cost = trim($cost);
    // Remove "pt", "point", "points" if present
    $cost = preg_replace('/\s*(?:pt|point|points)\s*/i', '', $cost);
    // Normalize "or" to " or "
    $cost = preg_replace('/\s+or\s+/i', ' or ', $cost);
    // Normalize ranges
    $cost = preg_replace('/\s*-\s*/', '-', $cost);
    // Normalize commas
    $cost = preg_replace('/\s*,\s*/', ', ', $cost);
    return trim($cost);
}

// Function to clean description
function cleanDescription(string $desc): string {
    // Convert <br> and <br/> tags to spaces before stripping tags
    $desc = preg_replace('/<br\s*\/?>/i', ' ', $desc);
    // Remove HTML tags
    $desc = strip_tags($desc);
    // Remove Wikidot-specific formatting
    $desc = preg_replace('/\[\[.*?\]\]/', '', $desc); // Remove wiki links
    $desc = preg_replace('/\[.*?\]/', '', $desc); // Remove other brackets
    // Remove HTML entities
    $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Normalize whitespace - convert all whitespace to single spaces, but preserve sentence structure
    $desc = preg_replace('/\s+/', ' ', $desc);
    // Remove page break markers
    $desc = preg_replace('/\[Page \d+\]/', '', $desc);
    // Remove div tags (in case any remain)
    $desc = preg_replace('/<div[^>]*>.*?<\/div>/s', '', $desc);
    // Remove leading/trailing punctuation/whitespace that might be artifacts
    $desc = preg_replace('/^[\s:\-\.]+/', '', $desc);
    $desc = preg_replace('/[\s:\-\.]+$/', '', $desc);
    // Trim
    $desc = trim($desc);
    return $desc;
}

// Function to find name in database (handles variations)
function findNameInDb($conn, string $name, string $table): ?string {
    // Try exact match first
    $escaped_name = mysqli_real_escape_string($conn, $name);
    $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE name = '{$escaped_name}'");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['name'];
    }
    
    // Try case-insensitive match
    $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE LOWER(name) = LOWER('{$escaped_name}')");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['name'];
    }
    
    // Try with variations (hyphens, spaces, apostrophes)
    $variations = [
        str_replace('-', ' ', $name),
        str_replace(' ', '-', $name),
        str_replace("'", "'", $name),
        str_replace("'", "'", $name),
        str_replace('"', "'", $name),
    ];
    
    foreach ($variations as $variation) {
        $escaped = mysqli_real_escape_string($conn, $variation);
        $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE LOWER(name) = LOWER('{$escaped}')");
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['name'];
        }
    }
    
    // Try partial match (for cases like "Acute Sense" matching "Acute Sense of Smell")
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        $first_words = implode(' ', array_slice($words, 0, 2));
        $escaped = mysqli_real_escape_string($conn, $first_words);
        $result = mysqli_query($conn, "SELECT name FROM {$table} WHERE LOWER(name) LIKE LOWER('{$escaped}%') LIMIT 1");
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['name'];
        }
    }
    
    return null;
}

echo "<h2>Updating Database from Wikidot Pages</h2>";

// Fetch and parse merits page
echo "<p>Fetching merits page...</p>";
try {
    $merits_url = 'http://theanarchstate.wikidot.com/merits';
    $merits_content = fetchWikidotPage($merits_url);
    
    // Debug: save raw content
    file_put_contents(__DIR__ . '/../tmp/wikidot_merits_raw.html', $merits_content);
    
    $merits_data = parseWikidotContent($merits_content, 'Merit');
    echo "<p>Found " . count($merits_data) . " merits on Wikidot page.</p>";
    
    // Show first few for debugging
    if (count($merits_data) > 0) {
        echo "<p><strong>Sample merits found:</strong> ";
        $sample = array_slice(array_keys($merits_data), 0, 5);
        echo implode(', ', $sample) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>ERROR: Error fetching merits: " . htmlspecialchars($e->getMessage()) . "</p>";
    $merits_data = [];
}

// Fetch and parse flaws page
echo "<p>Fetching flaws page...</p>";
try {
    $flaws_url = 'http://theanarchstate.wikidot.com/flaws';
    $flaws_content = fetchWikidotPage($flaws_url);
    
    // Debug: save raw content
    file_put_contents(__DIR__ . '/../tmp/wikidot_flaws_raw.html', $flaws_content);
    
    $flaws_data = parseWikidotContent($flaws_content, 'Flaw');
    echo "<p>Found " . count($flaws_data) . " flaws on Wikidot page.</p>";
    
    // Show first few for debugging
    if (count($flaws_data) > 0) {
        echo "<p><strong>Sample flaws found:</strong> ";
        $sample = array_slice(array_keys($flaws_data), 0, 5);
        echo implode(', ', $sample) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>ERROR: Error fetching flaws: " . htmlspecialchars($e->getMessage()) . "</p>";
    $flaws_data = [];
}

// Update merits
echo "<p>Starting database updates...</p>";
echo "<p>Processing " . count($merits_data) . " merits and " . count($flaws_data) . " flaws...</p>";
flush();

// Pre-load all existing names from database for fast lookup
echo "<p>Loading existing database names for fast lookup...</p>";
flush();

$existing_merits = [];
$merits_result = mysqli_query($conn, "SELECT name FROM merits");
if ($merits_result) {
    while ($row = mysqli_fetch_assoc($merits_result)) {
        $existing_merits[strtolower($row['name'])] = $row['name'];
    }
    mysqli_free_result($merits_result);
}

$existing_flaws = [];
$flaws_result = mysqli_query($conn, "SELECT name FROM flaws");
if ($flaws_result) {
    while ($row = mysqli_fetch_assoc($flaws_result)) {
        $existing_flaws[strtolower($row['name'])] = $row['name'];
    }
    mysqli_free_result($flaws_result);
}

echo "<p>Loaded " . count($existing_merits) . " existing merits and " . count($existing_flaws) . " existing flaws from database.</p>";
flush();

// Fast lookup function using pre-loaded data
function fastFindName(array $existing_names, string $name): ?string {
    $normalized = strtolower($name);
    
    // Try exact match first
    if (isset($existing_names[$normalized])) {
        return $existing_names[$normalized];
    }
    
    // Try variations (hyphens, spaces, apostrophes)
    $variations = [
        str_replace('-', ' ', $normalized),
        str_replace(' ', '-', $normalized),
        str_replace("'", "'", $normalized),
        str_replace("'", "'", $normalized),
    ];
    
    foreach ($variations as $variation) {
        if (isset($existing_names[$variation])) {
            return $existing_names[$variation];
        }
    }
    
    // Try partial match for multi-word names
    $words = explode(' ', $normalized);
    if (count($words) >= 2) {
        $first_words = implode(' ', array_slice($words, 0, 2));
        foreach ($existing_names as $db_normalized => $db_name) {
            if (strpos($db_normalized, $first_words) === 0) {
                return $db_name;
            }
        }
    }
    
    return null;
}

// Use INSERT ... ON DUPLICATE KEY UPDATE to insert new or update existing
$update_merits_sql = "INSERT INTO merits (name, cost, description, clan, display_order) 
                      VALUES (?, ?, ?, NULL, 0)
                      ON DUPLICATE KEY UPDATE 
                      cost = VALUES(cost),
                      description = VALUES(description),
                      updated_at = CURRENT_TIMESTAMP";
echo "<p>Preparing merits insert/update statement...</p>";
flush();

$merits_stmt = mysqli_prepare($conn, $update_merits_sql);

if (!$merits_stmt) {
    echo "<p>ERROR: Error preparing merits update statement: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
    echo "</body></html>";
    flush();
    mysqli_close($conn);
    exit;
}

echo "<p>Merits statement prepared successfully</p>";
flush();

$merits_updated = 0;
$merits_inserted = 0;
$merits_not_found = [];
$merits_errors = [];
$merits_updated_list = [];
$merits_inserted_list = [];

$merits_total = count($merits_data);
$merits_processed = 0;

echo "<p>Starting merits loop (0/{$merits_total})...</p>";
flush();

if ($merits_total == 0) {
    echo "<p>WARNING: No merits data to process!</p>";
    flush();
} else {
    echo "<p>Merits data array has {$merits_total} items</p>";
    flush();
}

echo "<p>Starting foreach loop for merits...</p>";
flush();

// Check if array is actually iterable
if (!is_array($merits_data)) {
    echo "<p>ERROR: \$merits_data is not an array! Type: " . gettype($merits_data) . "</p>";
    echo "</body></html>";
    flush();
    exit;
}

try {
    foreach ($merits_data as $wikidot_name => $data) {
        $merits_processed++;
        
        // Show progress every 50 items or first item
        if ($merits_processed == 1 || $merits_processed % 50 == 0 || $merits_processed == $merits_total) {
            echo "<p>Processing merits ({$merits_processed}/{$merits_total}): " . htmlspecialchars($wikidot_name) . "...</p>";
            flush();
        }
        
        // Validate data structure
        if (!isset($data['cost']) || !isset($data['description'])) {
            echo "<p>WARNING: Skipping " . htmlspecialchars($wikidot_name) . " - missing cost or description</p>";
            flush();
            continue;
        }
        
        $db_name = findNameInDb($conn, $wikidot_name, 'merits');
        
        // Try to find in database using fast lookup
        $db_name = fastFindName($existing_merits, $wikidot_name);
        
        // Use the database name if found, otherwise use the Wikidot name (will insert new)
        $name_to_use = $db_name ? $db_name : $wikidot_name;
        
        mysqli_stmt_bind_param($merits_stmt, 'sss', $name_to_use, $data['cost'], $data['description']);
        if (mysqli_stmt_execute($merits_stmt)) {
            if ($db_name) {
                $merits_updated++;
                $merits_updated_list[] = "{$db_name} => Cost: {$data['cost']}, Description: " . substr($data['description'], 0, 50) . "...";
            } else {
                $merits_inserted++;
                $merits_inserted_list[] = "{$wikidot_name} => Cost: {$data['cost']}, Description: " . substr($data['description'], 0, 50) . "...";
            }
        } else {
            if ($db_name) {
                $merits_errors[] = "Failed to update {$db_name}: " . mysqli_stmt_error($merits_stmt);
            } else {
                $merits_errors[] = "Failed to insert {$wikidot_name}: " . mysqli_stmt_error($merits_stmt);
            }
        }
    }
} catch (Throwable $e) {
    echo "<p>FATAL ERROR in merits loop: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</body></html>";
    flush();
    exit;
}

mysqli_stmt_close($merits_stmt);

echo "<p><strong>Merits processing complete!</strong> {$merits_updated} updated, {$merits_inserted} inserted, " . count($merits_errors) . " errors.</p>";
flush();

// Update flaws - Use INSERT ... ON DUPLICATE KEY UPDATE to insert new or update existing
$update_flaws_sql = "INSERT INTO flaws (name, cost, description, clan, display_order) 
                     VALUES (?, ?, ?, NULL, 0)
                     ON DUPLICATE KEY UPDATE 
                     cost = VALUES(cost),
                     description = VALUES(description),
                     updated_at = CURRENT_TIMESTAMP";
$flaws_stmt = mysqli_prepare($conn, $update_flaws_sql);

if (!$flaws_stmt) {
    echo "<p>ERROR: Error preparing flaws update statement: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$flaws_updated = 0;
$flaws_inserted = 0;
$flaws_not_found = [];
$flaws_errors = [];
$flaws_updated_list = [];
$flaws_inserted_list = [];

$flaws_total = count($flaws_data);
$flaws_processed = 0;

echo "<p>Processing flaws (0/{$flaws_total})...</p>";
flush();
ob_flush();

foreach ($flaws_data as $wikidot_name => $data) {
    $flaws_processed++;
    
    // Show progress every 50 items
    if ($flaws_processed % 50 == 0 || $flaws_processed == $flaws_total) {
        echo "<p>Processing flaws ({$flaws_processed}/{$flaws_total})...</p>";
        flush();
        ob_flush();
    }
    
    // Try to find in database using fast lookup
    $db_name = fastFindName($existing_flaws, $wikidot_name);
    
    // Use the database name if found, otherwise use the Wikidot name (will insert new)
    $name_to_use = $db_name ? $db_name : $wikidot_name;
    
    mysqli_stmt_bind_param($flaws_stmt, 'sss', $name_to_use, $data['cost'], $data['description']);
    if (mysqli_stmt_execute($flaws_stmt)) {
        if ($db_name) {
            $flaws_updated++;
            $flaws_updated_list[] = "{$db_name} => Cost: {$data['cost']}, Description: " . substr($data['description'], 0, 50) . "...";
        } else {
            $flaws_inserted++;
            $flaws_inserted_list[] = "{$wikidot_name} => Cost: {$data['cost']}, Description: " . substr($data['description'], 0, 50) . "...";
        }
    } else {
        if ($db_name) {
            $flaws_errors[] = "Failed to update {$db_name}: " . mysqli_stmt_error($flaws_stmt);
        } else {
            $flaws_errors[] = "Failed to insert {$wikidot_name}: " . mysqli_stmt_error($flaws_stmt);
        }
    }
}

mysqli_stmt_close($flaws_stmt);

echo "<p><strong>Flaws processing complete!</strong> {$flaws_updated} updated, {$flaws_inserted} inserted, " . count($flaws_errors) . " errors.</p>";
flush();

// Get remaining NULL costs and descriptions
$merits_still_null_cost = mysqli_fetch_all(
    mysqli_query($conn, "SELECT name FROM merits WHERE cost IS NULL ORDER BY name"),
    MYSQLI_ASSOC
);
$merits_still_null_desc = mysqli_fetch_all(
    mysqli_query($conn, "SELECT name FROM merits WHERE description IS NULL OR description = '' ORDER BY name"),
    MYSQLI_ASSOC
);
$flaws_still_null_cost = mysqli_fetch_all(
    mysqli_query($conn, "SELECT name FROM flaws WHERE cost IS NULL ORDER BY name"),
    MYSQLI_ASSOC
);
$flaws_still_null_desc = mysqli_fetch_all(
    mysqli_query($conn, "SELECT name FROM flaws WHERE description IS NULL OR description = '' ORDER BY name"),
    MYSQLI_ASSOC
);

// Display results
echo "<hr style='margin: 20px 0; border: 2px solid green;'>";
echo "<h2 style='color: green; font-size: 24px;'>=== DATABASE UPDATE COMPLETE ===</h2>";
echo "<p><strong>Finished at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Successfully updated {$merits_updated} merits and inserted {$merits_inserted} new merits with costs and descriptions.</p>";
echo "<p>Successfully updated {$flaws_updated} flaws and inserted {$flaws_inserted} new flaws with costs and descriptions.</p>";
flush();

// Quick verification - check a sample updated item
if ($merits_updated > 0 && count($merits_updated_list) > 0) {
    $sample_name = array_keys($merits_data)[0];
    $db_name = findNameInDb($conn, $sample_name, 'merits');
    if ($db_name) {
        $verify = mysqli_query($conn, "SELECT name, cost, LEFT(description, 50) as desc_preview FROM merits WHERE name = '" . mysqli_real_escape_string($conn, $db_name) . "'");
        if ($verify && $row = mysqli_fetch_assoc($verify)) {
            echo "<p><strong>Sample verification:</strong> {$row['name']} - Cost: {$row['cost']}, Description: {$row['desc_preview']}...</p>";
        }
    }
}

if ($merits_inserted > 0) {
    echo "<h3>New Merits Inserted (" . $merits_inserted . "):</h3>";
    echo "<ul>";
    foreach (array_slice($merits_inserted_list, 0, 20) as $item) {
        echo "<li>" . htmlspecialchars($item) . "</li>";
    }
    if (count($merits_inserted_list) > 20) {
        echo "<li><em>... and " . (count($merits_inserted_list) - 20) . " more</em></li>";
    }
    echo "</ul>";
}

if ($flaws_inserted > 0) {
    echo "<h3>New Flaws Inserted (" . $flaws_inserted . "):</h3>";
    echo "<ul>";
    foreach (array_slice($flaws_inserted_list, 0, 20) as $item) {
        echo "<li>" . htmlspecialchars($item) . "</li>";
    }
    if (count($flaws_inserted_list) > 20) {
        echo "<li><em>... and " . (count($flaws_inserted_list) - 20) . " more</em></li>";
    }
    echo "</ul>";
}

if (count($merits_errors) > 0 || count($flaws_errors) > 0) {
    echo "<h3>ERRORS:</h3>";
    echo "<ul>";
    foreach (array_merge($merits_errors, $flaws_errors) as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Display remaining NULL costs
echo "<h3>Merits Still Missing Costs (" . count($merits_still_null_cost) . "):</h3>";
if (count($merits_still_null_cost) > 0) {
    echo "<ul>";
    foreach (array_slice($merits_still_null_cost, 0, 20) as $row) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    if (count($merits_still_null_cost) > 20) {
        echo "<li><em>... and " . (count($merits_still_null_cost) - 20) . " more</em></li>";
    }
    echo "</ul>";
} else {
    echo "<p>All merits now have costs!</p>";
}

echo "<h3>Flaws Still Missing Costs (" . count($flaws_still_null_cost) . "):</h3>";
if (count($flaws_still_null_cost) > 0) {
    echo "<ul>";
    foreach (array_slice($flaws_still_null_cost, 0, 20) as $row) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    if (count($flaws_still_null_cost) > 20) {
        echo "<li><em>... and " . (count($flaws_still_null_cost) - 20) . " more</em></li>";
    }
    echo "</ul>";
} else {
    echo "<p>All flaws now have costs!</p>";
}

// Display remaining NULL descriptions
echo "<h3>Merits Still Missing Descriptions (" . count($merits_still_null_desc) . "):</h3>";
if (count($merits_still_null_desc) > 0) {
    echo "<ul>";
    foreach (array_slice($merits_still_null_desc, 0, 20) as $row) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    if (count($merits_still_null_desc) > 20) {
        echo "<li><em>... and " . (count($merits_still_null_desc) - 20) . " more</em></li>";
    }
    echo "</ul>";
} else {
    echo "<p>All merits now have descriptions!</p>";
}

echo "<h3>Flaws Still Missing Descriptions (" . count($flaws_still_null_desc) . "):</h3>";
if (count($flaws_still_null_desc) > 0) {
    echo "<ul>";
    foreach (array_slice($flaws_still_null_desc, 0, 20) as $row) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    if (count($flaws_still_null_desc) > 20) {
        echo "<li><em>... and " . (count($flaws_still_null_desc) - 20) . " more</em></li>";
    }
    echo "</ul>";
} else {
    echo "<p>All flaws now have descriptions!</p>";
}

// Save detailed logs
$output_dir = __DIR__ . '/../tmp';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$merits_null_cost_list = array_column($merits_still_null_cost, 'name');
$merits_null_desc_list = array_column($merits_still_null_desc, 'name');
$flaws_null_cost_list = array_column($flaws_still_null_cost, 'name');
$flaws_null_desc_list = array_column($flaws_still_null_desc, 'name');

file_put_contents($output_dir . '/merits_still_missing_costs.txt', implode("\n", $merits_null_cost_list));
file_put_contents($output_dir . '/merits_still_missing_descriptions.txt', implode("\n", $merits_null_desc_list));
file_put_contents($output_dir . '/flaws_still_missing_costs.txt', implode("\n", $flaws_null_cost_list));
file_put_contents($output_dir . '/flaws_still_missing_descriptions.txt', implode("\n", $flaws_null_desc_list));

// Create detailed update log
$update_log = "WIKIDOT UPDATE LOG\n";
$update_log .= "==================\n\n";
$update_log .= "MERITS UPDATED (" . count($merits_updated_list) . "):\n";
$update_log .= implode("\n", $merits_updated_list) . "\n\n";
$update_log .= "NEW MERITS INSERTED (" . $merits_inserted . "):\n";
if (count($merits_inserted_list) > 0) {
    $update_log .= implode("\n", $merits_inserted_list) . "\n\n";
} else {
    $update_log .= "None\n\n";
}
$update_log .= "FLAWS UPDATED (" . count($flaws_updated_list) . "):\n";
$update_log .= implode("\n", $flaws_updated_list) . "\n\n";
$update_log .= "NEW FLAWS INSERTED (" . $flaws_inserted . "):\n";
if (count($flaws_inserted_list) > 0) {
    $update_log .= implode("\n", $flaws_inserted_list) . "\n\n";
} else {
    $update_log .= "None\n\n";
}
$update_log .= "MERITS STILL MISSING COSTS:\n";
$update_log .= implode("\n", $merits_null_cost_list) . "\n\n";
$update_log .= "MERITS STILL MISSING DESCRIPTIONS:\n";
$update_log .= implode("\n", $merits_null_desc_list) . "\n\n";
$update_log .= "FLAWS STILL MISSING COSTS:\n";
$update_log .= implode("\n", $flaws_null_cost_list) . "\n\n";
$update_log .= "FLAWS STILL MISSING DESCRIPTIONS:\n";
$update_log .= implode("\n", $flaws_null_desc_list) . "\n";

file_put_contents($output_dir . '/wikidot_update_log.txt', $update_log);

echo "<h3>Detailed logs saved to:</h3>";
echo "<ul>";
echo "<li>tmp/merits_still_missing_costs.txt</li>";
echo "<li>tmp/merits_still_missing_descriptions.txt</li>";
echo "<li>tmp/flaws_still_missing_costs.txt</li>";
echo "<li>tmp/flaws_still_missing_descriptions.txt</li>";
echo "<li>tmp/wikidot_update_log.txt (complete log)</li>";
echo "</ul>";

// Show summary
echo "<h3>Summary:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Category</th><th>Updated</th><th>Missing Costs</th><th>Missing Descriptions</th></tr>";
echo "<tr><td>Merits</td><td>{$merits_updated}</td><td>" . count($merits_null_cost_list) . "</td><td>" . count($merits_null_desc_list) . "</td></tr>";
echo "<tr><td>Flaws</td><td>{$flaws_updated}</td><td>" . count($flaws_null_cost_list) . "</td><td>" . count($flaws_null_desc_list) . "</td></tr>";
echo "</table>";

mysqli_close($conn);
echo "</body></html>";
?>
