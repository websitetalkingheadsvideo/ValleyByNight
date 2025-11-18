<?php
/**
 * NPC Character Teaser Tracking System
 * 
 * Scans NPCs from the database and compares them against existing Character Teaser files
 * to identify which NPCs are missing Character Teasers.
 * 
 * Output: missing-character-teasers.json (in tracking folder)
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set content type for web browser output
header('Content-Type: text/html; charset=utf-8');

// Get project root (script is in reference/Scenes/Character Teasers/tracking/)
// __DIR__ = project_root/reference/Scenes/Character Teasers/tracking
// Go up 4 levels to get project root
$project_root = dirname(dirname(dirname(dirname(__DIR__))));
 
// Verify project root by checking for includes/connect.php
$connect_file = $project_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'connect.php';
if (!file_exists($connect_file)) {
    die("Error: Could not find project root. Expected connect.php at: {$connect_file}\n");
}

require_once $connect_file;

// Configuration
// CHARACTER_TEASERS_DIR is the parent directory of tracking (where the .md files are)
define('CHARACTER_TEASERS_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('OUTPUT_DIR', __DIR__);
define('OUTPUT_FILE', OUTPUT_DIR . DIRECTORY_SEPARATOR . 'missing-character-teasers.json');

/**
 * Get all NPCs from the database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of NPC records with all required fields
 */
function get_all_npcs($conn): array {
    $query = "SELECT c.*
              FROM characters c
              WHERE c.player_name = 'NPC'
              ORDER BY c.id";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Failed to query NPCs: " . mysqli_error($conn));
    }
    
    $npcs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $npcs[] = $row;
    }
    
    return $npcs;
}

/**
 * Get negative traits for a character
 * 
 * @param mysqli $conn Database connection
 * @param int $character_id Character ID
 * @return array Array of negative trait names
 */
function get_negative_traits($conn, int $character_id): array {
    $query = "SELECT trait_name 
              FROM character_negative_traits 
              WHERE character_id = ?
              ORDER BY trait_category, trait_name";
    
    $result = db_fetch_all($conn, $query, 'i', [$character_id]);
    
    $traits = [];
    foreach ($result as $row) {
        $traits[] = $row['trait_name'];
    }
    
    return $traits;
}

/**
 * Get equipment traits (assigned equipment items) for a character
 * 
 * @param mysqli $conn Database connection
 * @param int $character_id Character ID
 * @return array Array of equipment item names
 */
function get_equipment_traits($conn, int $character_id): array {
    $query = "SELECT i.name
              FROM character_equipment ce
              INNER JOIN items i ON ce.item_id = i.id
              WHERE ce.character_id = ?
              ORDER BY i.name";
    
    $result = db_fetch_all($conn, $query, 'i', [$character_id]);
    
    $equipment = [];
    foreach ($result as $row) {
        $equipment[] = $row['name'];
    }
    
    return $equipment;
}

/**
 * Get morality path name for a character
 * 
 * @param mysqli $conn Database connection
 * @param int $character_id Character ID
 * @return array|null Morality data with path_name or null
 */
function get_morality($conn, int $character_id): ?array {
    $query = "SELECT path_name 
              FROM character_morality 
              WHERE character_id = ?";
    
    $result = db_fetch_one($conn, $query, 'i', [$character_id]);
    
    if ($result && !empty($result['path_name'])) {
        return ['path_name' => $result['path_name']];
    }
    
    return null;
}

/**
 * Scan existing Character Teaser files
 * 
 * @param string $teasers_dir Directory containing Character Teaser files
 * @return array Associative array mapping character names to boolean (true = has teaser)
 */
function scan_character_teasers(string $teasers_dir): array {
    $teasers = [];
    
    if (!is_dir($teasers_dir)) {
        error_log("Warning: Character Teasers directory not found: {$teasers_dir}");
        return $teasers;
    }
    
    $files = scandir($teasers_dir);
    
    foreach ($files as $file) {
        // Skip hidden files and directories
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Only process .md files
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'md') {
            continue;
        }
        
        // Extract character name from filename (remove .md extension)
        $character_name = pathinfo($file, PATHINFO_FILENAME);
        
        // Store the character name (normalized for comparison)
        $teasers[strtolower(trim($character_name))] = true;
    }
    
    return $teasers;
}

/**
 * Normalize character name for comparison
 * 
 * @param string $name Character name
 * @return string Normalized name (lowercase, trimmed)
 */
function normalize_name(string $name): string {
    return strtolower(trim($name));
}

/**
 * Build complete NPC data with all required fields
 * 
 * @param mysqli $conn Database connection
 * @param array $npc NPC record from database
 * @return array Complete NPC data with all required fields
 */
function build_npc_data($conn, array $npc): array {
    $character_id = (int)$npc['id'];
    
    // Get related data
    $negative_traits = get_negative_traits($conn, $character_id);
    $equipment_traits = get_equipment_traits($conn, $character_id);
    $morality = get_morality($conn, $character_id);
    
    // Build the NPC data structure
    $npc_data = [
        'id' => $character_id,
        'name' => $npc['character_name'] ?? null,
        'notes' => $npc['notes'] ?? null,
        'biography' => $npc['biography'] ?? null,
        'appearance' => $npc['appearance'] ?? null,
        'clan' => $npc['clan'] ?? null,
        'nature' => $npc['nature'] ?? null,
        'demeanor' => $npc['demeanor'] ?? null,
        'concept' => $npc['concept'] ?? null,
        'equipmentTraits' => $equipment_traits,
        'negativeTraits' => $negative_traits,
        'morality' => $morality
    ];
    
    return $npc_data;
}

/**
 * Main execution
 */
try {
    echo "<!DOCTYPE html>\n";
    echo "<html><head><title>NPC Character Teaser Tracking</title>\n";
    echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#f0f0f0;}h1{color:#d32f2f;}.success{color:#4caf50;}.error{color:#f44336;}.info{color:#2196f3;}</style></head><body>\n";
    echo "<h1>NPC Character Teaser Tracking System</h1>\n";
    echo "<hr>\n";
    
    // Step 1: Get all NPCs from database
    echo "<p class='info'>Step 1: Querying NPCs from database...</p>\n";
    $npcs = get_all_npcs($conn);
    echo "<p class='success'>Found " . count($npcs) . " NPCs in database.</p>\n";
    
    // Step 2: Scan existing Character Teaser files
    echo "<p class='info'>Step 2: Scanning Character Teaser files...</p>\n";
    $existing_teasers = scan_character_teasers(CHARACTER_TEASERS_DIR);
    echo "<p class='success'>Found " . count($existing_teasers) . " existing Character Teaser files.</p>\n";
    
    // Step 3: Identify NPCs missing Character Teasers
    echo "<p class='info'>Step 3: Identifying NPCs missing Character Teasers...</p>\n";
    $missing_teasers = [];
    
    foreach ($npcs as $npc) {
        $character_name = $npc['character_name'] ?? '';
        $normalized_name = normalize_name($character_name);
        
        // Check if this NPC has a Character Teaser
        if (!isset($existing_teasers[$normalized_name])) {
            // Build complete NPC data
            $npc_data = build_npc_data($conn, $npc);
            $missing_teasers[] = $npc_data;
        }
    }
    
    echo "<p class='success'>Found " . count($missing_teasers) . " NPCs missing Character Teasers.</p>\n";
    
    // Step 4: Sort by ID for deterministic output
    usort($missing_teasers, function($a, $b) {
        return $a['id'] <=> $b['id'];
    });
    
    // Step 5: Write JSON output
    echo "<p class='info'>Step 4: Writing JSON output...</p>\n";
    
    // Ensure output directory exists
    if (!is_dir(OUTPUT_DIR)) {
        mkdir(OUTPUT_DIR, 0755, true);
    }
    
    $json_output = json_encode($missing_teasers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json_output === false) {
        throw new Exception("Failed to encode JSON: " . json_last_error_msg());
    }
    
    $bytes_written = file_put_contents(OUTPUT_FILE, $json_output);
    
    if ($bytes_written === false) {
        throw new Exception("Failed to write output file: " . OUTPUT_FILE);
    }
    
    echo "<p class='success'><strong>Success!</strong> Output written to: <code>" . htmlspecialchars(OUTPUT_FILE) . "</code></p>\n";
    echo "<p class='info'>Total bytes written: " . number_format($bytes_written) . "</p>\n";
    echo "<hr>\n";
    echo "<h2>Summary</h2>\n";
    echo "<ul>\n";
    echo "  <li>Total NPCs: <strong>" . count($npcs) . "</strong></li>\n";
    echo "  <li>NPCs with teasers: <strong>" . (count($npcs) - count($missing_teasers)) . "</strong></li>\n";
    echo "  <li>NPCs missing teasers: <strong>" . count($missing_teasers) . "</strong></li>\n";
    echo "</ul>\n";
    
    if (count($missing_teasers) > 0) {
        echo "<p class='info'><a href='missing-character-teasers.json' target='_blank' style='color:#2196f3;text-decoration:underline;'>View missing-character-teasers.json</a></p>\n";
    }
    
    echo "</body></html>\n";
    
} catch (Exception $e) {
    echo "<p class='error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("NPC Teaser Tracking Error: " . $e->getMessage());
    echo "</body></html>\n";
    exit(1);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>

