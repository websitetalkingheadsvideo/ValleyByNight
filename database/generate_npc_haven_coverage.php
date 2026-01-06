<?php
/**
 * NPC Haven Coverage Generator
 * 
 * Creates reference/Locations/plan/character list.json containing a complete list
 * of all NPCs currently in the game with their haven status and recommendations.
 * 
 * Usage:
 *   CLI: php database/generate_npc_haven_coverage.php
 *   Web: database/generate_npc_haven_coverage.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$project_root = dirname(__DIR__);
$output_file = $project_root . '/reference/Locations/plan/character list.json';
$output_dir = dirname($output_file);

// Create output directory if it doesn't exist
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Allowed district/town values from plan
$allowed_districts = [
    "Downtown Phoenix",
    "Southern Scottsdale",
    "Mesa - Industrial District (East Mesa, near Superstition Springs)",
    "Central Phoenix / 24th Street Area (between Indian School and Camelback roads)",
    "Northern Scottsdale",
    "Mesa",
    "Outer Phoenix / Desert Park Area",
    "Scottsdale / Camelback Mountain Area",
    "North Phoenix / Dunlap and 32nd Avenue Area",
    "South Phoenix / South Mountain Area",
    "Downtown Phoenix / Roosevelt Row (Roosevelt Street and Grand Avenue Area)",
    "West Phoenix / Camelback Mountain Area (Horse Zoning District)",
    "Downtown Phoenix / Central Avenue and Indian School Road Area",
    "Downtown Phoenix / Industrial District"
];

/**
 * Get all NPCs from database
 */
function getNPCsFromDatabase(mysqli $conn): array {
    $query = "SELECT id, character_name, clan, biography, status, updated_at 
              FROM characters 
              WHERE (pc = 0 OR pc IS NULL) 
              ORDER BY character_name";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Database query failed: " . mysqli_error($conn));
    }
    
    $npcs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $npcs[] = [
            'id' => (int)$row['id'],
            'character_name' => $row['character_name'],
            'clan' => $row['clan'] ?? null,
            'biography' => $row['biography'] ?? null,
            'status' => $row['status'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'source' => 'database'
        ];
    }
    
    return $npcs;
}

/**
 * Scan JSON files for NPCs
 */
function getNPCsFromJSON(string $project_root): array {
    $npcs = [];
    $directories = [
        $project_root . '/reference/Characters',
        $project_root . '/reference/Characters/Added to Database',
        $project_root . '/reference/Characters/Ghouls',
        $project_root . '/reference/Characters/Wraiths'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $content = file_get_contents($file->getPathname());
                $data = json_decode($content, true);
                
                if ($data && isset($data['character_name'])) {
                    // Check if NPC (pc = 0 or missing)
                    $is_pc = isset($data['pc']) && $data['pc'] == 1;
                    if (!$is_pc) {
                        $npcs[] = [
                            'character_name' => $data['character_name'],
                            'clan' => $data['clan'] ?? null,
                            'biography' => $data['biography'] ?? null,
                            'status' => $data['status'] ?? null,
                            'source' => 'json',
                            'file_path' => $file->getPathname()
                        ];
                    }
                }
            }
        }
    }
    
    return $npcs;
}

/**
 * Find haven for NPC by checking locations table
 */
function findHavenForNPC(mysqli $conn, string $character_name): ?array {
    // Escape character name for LIKE query
    $escaped_name = mysqli_real_escape_string($conn, $character_name);
    
    // Try multiple matching strategies
    $queries = [
        // Check owner_notes for character name
        "SELECT id, name, district, owner_notes, owner_type 
         FROM locations 
         WHERE type = 'Haven' 
         AND (owner_notes LIKE '%{$escaped_name}%' OR name LIKE '%{$escaped_name}%')
         ORDER BY id ASC
         LIMIT 1",
        
        // Check if owner_type is Individual and owner_notes matches
        "SELECT id, name, district, owner_notes, owner_type 
         FROM locations 
         WHERE type = 'Haven' 
         AND owner_type = 'Individual' 
         AND owner_notes LIKE '%{$escaped_name}%'
         ORDER BY id ASC
         LIMIT 1"
    ];
    
    foreach ($queries as $query) {
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'district' => $row['district'] ?? null
            ];
        }
    }
    
    return null;
}

/**
 * Check if haven JSON file exists
 */
function checkHavenJSONExists(string $project_root, string $haven_name): string {
    // Normalize filename: spaces to underscores, remove special chars
    $normalized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $haven_name);
    $normalized = preg_replace('/_+/', '_', $normalized);
    $normalized = trim($normalized, '_');
    
    $file_path = $project_root . '/reference/Locations/' . $normalized . '.json';
    
    if (file_exists($file_path)) {
        return 'yes';
    }
    
    return 'no';
}

/**
 * Generate haven recommendation based on clan and biography
 */
function generateHavenRecommendation(?string $clan, ?string $biography): string {
    $biography_lower = strtolower($biography ?? '');
    $clan_lower = strtolower($clan ?? '');
    
    // Extract special needs from biography
    $needs = [];
    if (stripos($biography_lower, 'security') !== false || stripos($biography_lower, 'secure') !== false) {
        $needs[] = 'high security';
    }
    if (stripos($biography_lower, 'ritual') !== false || stripos($biography_lower, 'thaumaturgy') !== false) {
        $needs[] = 'ritual space';
    }
    if (stripos($biography_lower, 'library') !== false || stripos($biography_lower, 'research') !== false) {
        $needs[] = 'library';
    }
    if (stripos($biography_lower, 'medical') !== false || stripos($biography_lower, 'hospital') !== false) {
        $needs[] = 'medical facilities';
    }
    if (stripos($biography_lower, 'workshop') !== false || stripos($biography_lower, 'craft') !== false) {
        $needs[] = 'workshop';
    }
    if (stripos($biography_lower, 'armory') !== false || stripos($biography_lower, 'weapon') !== false) {
        $needs[] = 'armory';
    }
    if (stripos($biography_lower, 'hidden') !== false || stripos($biography_lower, 'concealed') !== false) {
        $needs[] = 'concealed entrance';
    }
    if (stripos($biography_lower, 'accessibility') !== false || stripos($biography_lower, 'accessible') !== false) {
        $needs[] = 'accessibility accommodations';
    }
    
    // Clan-specific recommendations
    $recommendation = '';
    
    if ($clan_lower === 'tremere') {
        $recommendation = "A secure, warded haven with dedicated ritual space for Thaumaturgical practices";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 3));
        } else {
            $recommendation .= "; includes reinforced entry points, warding rituals, and a library for occult research";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'nosferatu') {
        $recommendation = "A hidden, highly secure haven with multiple concealed entrances";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 3));
        } else {
            $recommendation .= "; includes blackout measures, reinforced security, and feeding accommodations that minimize exposure";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'toreador') {
        $recommendation = "An aesthetically pleasing haven that blends with mortal society";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes security measures appropriate for the district and feeding opportunities in cultural/artistic venues";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'ventrue') {
        $recommendation = "A prestigious, well-secured haven in an upscale district";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes comprehensive security systems, professional monitoring, and access to business/social networks";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'brujah') {
        $recommendation = "A defensible haven in an industrial or reclaimed urban area";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes reinforced structure, multiple exits, and space for clan gatherings";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'gangrel') {
        $recommendation = "A secluded haven on the fringes of urban development";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes natural camouflage, animal patrols, and minimal technological footprint";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'malkavian') {
        $recommendation = "A haven that accommodates unique sensory and psychological needs";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes soundproofing, controlled lighting, and security that doesn't trigger paranoia";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'setite' || $clan_lower === 'followers of set') {
        $recommendation = "A haven that serves dual purposes as both sanctuary and operational base";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes hidden areas, security for sensitive operations, and feeding opportunities";
        }
        $recommendation .= ".";
    } elseif ($clan_lower === 'giovanni') {
        $recommendation = "A secure haven with space for necromantic practices and family business";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 2));
        } else {
            $recommendation .= "; includes ritual space for necromancy, secure storage, and family meeting areas";
        }
        $recommendation .= ".";
    } else {
        // Generic recommendation
        $recommendation = "A secure haven appropriate for the character's needs";
        if (!empty($needs)) {
            $recommendation .= "; includes " . implode(', ', array_slice($needs, 0, 3));
        } else {
            $recommendation .= "; includes basic security measures and feeding accommodations";
        }
        $recommendation .= ".";
    }
    
    return $recommendation;
}

/**
 * Normalize character name for matching (case-insensitive)
 */
function normalizeName(string $name): string {
    return strtolower(trim($name));
}

// Main execution
try {
    if ($is_cli) {
        echo "NPC Haven Coverage Generator\n";
        echo "============================\n\n";
    } else {
        echo "<html><head><title>NPC Haven Coverage Generator</title></head><body><pre>";
    }
    
    // Step 1: Get NPCs from database
    if ($is_cli) {
        echo "Step 1: Querying database for NPCs...\n";
    }
    $db_npcs = getNPCsFromDatabase($conn);
    if ($is_cli) {
        echo "   Found " . count($db_npcs) . " NPCs in database\n\n";
    }
    
    // Step 2: Get NPCs from JSON files
    if ($is_cli) {
        echo "Step 2: Scanning JSON files for NPCs...\n";
    }
    $json_npcs = getNPCsFromJSON($project_root);
    if ($is_cli) {
        echo "   Found " . count($json_npcs) . " NPCs in JSON files\n\n";
    }
    
    // Step 3: Merge and deduplicate NPCs
    if ($is_cli) {
        echo "Step 3: Merging and deduplicating NPCs...\n";
    }
    $merged_npcs = [];
    $name_map = []; // Track by normalized name
    
    // Add database NPCs first (preferred source)
    foreach ($db_npcs as $npc) {
        $normalized = normalizeName($npc['character_name']);
        if (!isset($name_map[$normalized])) {
            $merged_npcs[] = $npc;
            $name_map[$normalized] = count($merged_npcs) - 1;
        } else {
            // Duplicate - prefer database entry
            $existing_idx = $name_map[$normalized];
            if ($merged_npcs[$existing_idx]['source'] === 'json') {
                // Replace JSON with DB entry
                $merged_npcs[$existing_idx] = $npc;
            }
        }
    }
    
    // Add JSON NPCs that aren't in database
    foreach ($json_npcs as $npc) {
        $normalized = normalizeName($npc['character_name']);
        if (!isset($name_map[$normalized])) {
            $merged_npcs[] = $npc;
            $name_map[$normalized] = count($merged_npcs) - 1;
        }
    }
    
    if ($is_cli) {
        echo "   Total unique NPCs: " . count($merged_npcs) . "\n\n";
    }
    
    // Step 4: Process each NPC
    if ($is_cli) {
        echo "Step 4: Processing NPCs (finding havens, generating recommendations)...\n";
    }
    
    $characters = [];
    foreach ($merged_npcs as $npc) {
        $character_name = $npc['character_name'];
        
        // Determine inDatabase status
        $in_database = 'no';
        if ($npc['source'] === 'database') {
            $in_database = 'yes';
        } elseif (isset($npc['id'])) {
            $in_database = 'yes';
        }
        
        // Find haven
        $haven = findHavenForNPC($conn, $character_name);
        $has_haven = $haven ? 'yes' : 'no';
        $haven_id = $haven ? $haven['id'] : null;
        $district = $haven ? $haven['district'] : null;
        
        // Check if haven JSON exists
        $haven_json_exists = 'unknown';
        if ($has_haven === 'yes' && $haven) {
            $haven_json_exists = checkHavenJSONExists($project_root, $haven['name']);
        }
        
        // Generate recommendation
        $recommendation = generateHavenRecommendation($npc['clan'] ?? null, $npc['biography'] ?? null);
        
        // Build source references
        $source_refs = [];
        if ($npc['source'] === 'database' && isset($npc['id'])) {
            $source_refs[] = "DB:characters.id=" . $npc['id'];
        }
        if (isset($npc['file_path'])) {
            $source_refs[] = str_replace($project_root . '/', '', $npc['file_path']);
        }
        
        $characters[] = [
            'characterName' => $character_name,
            'hasHaven' => $has_haven,
            'havenId' => $haven_id,
            'districtOrTown' => $district,
            'inDatabase' => $in_database,
            'havenJsonExists' => $haven_json_exists,
            'recommendedHavenDescription' => $recommendation,
            'sourceRefs' => $source_refs
        ];
    }
    
    // Sort by character name
    usort($characters, function($a, $b) {
        return strcasecmp($a['characterName'], $b['characterName']);
    });
    
    // Step 5: Build final JSON structure
    if ($is_cli) {
        echo "Step 5: Building final JSON structure...\n";
    }
    
    $output = [
        'generatedAt' => date('Y-m-d'),
        'characters' => $characters
    ];
    
    $json_output = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    // Step 6: Write to file
    if ($is_cli) {
        echo "Step 6: Writing to file: {$output_file}\n";
    }
    file_put_contents($output_file, $json_output);
    
    if ($is_cli) {
        echo "\n✓ Success! Generated character list with " . count($characters) . " NPCs\n";
        echo "  Output file: {$output_file}\n";
    } else {
        echo "\n✓ Success! Generated character list with " . count($characters) . " NPCs\n";
        echo "  Output file: {$output_file}\n";
        echo "</pre></body></html>";
    }
    
} catch (Exception $e) {
    $error_msg = "Error: " . $e->getMessage();
    if ($is_cli) {
        echo "\n❌ {$error_msg}\n";
    } else {
        echo "<p style='color: red;'>{$error_msg}</p></body></html>";
    }
    exit(1);
}
