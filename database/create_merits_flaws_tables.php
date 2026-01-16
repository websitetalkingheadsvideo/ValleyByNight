<?php
/**
 * Database Migration: Create Merits and Flaws tables
 * 
 * This script creates the Merits and Flaws tables and seeds them with all
 * merits and flaws from the Grapevine Menus XML.gvm file, including
 * clan-specific ones (but excluding non-vampire categories like Garou, Mage, Mummy).
 * 
 * It also searches LotNR.md for descriptions and creates lists of missing ones.
 * 
 * Run via browser: database/create_merits_flaws_tables.php
 * Or via CLI: php database/create_merits_flaws_tables.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Categories to include (category 2 = vampire clans, main menus have no category)
// Exclude: Changeling (5), Mage (7), Fera/Werewolf (8), Mummy (10), Kuei-Jin (11), Hunter (12), Werewolf (3), Wraith (6)
$included_categories = [2, null]; // null means no category attribute (main menus)

// Check if tables already exist
$check_merits = "SHOW TABLES LIKE 'merits'";
$check_flaws = "SHOW TABLES LIKE 'flaws'";
$merits_exists = mysqli_num_rows(mysqli_query($conn, $check_merits)) > 0;
$flaws_exists = mysqli_num_rows(mysqli_query($conn, $check_flaws)) > 0;

if ($merits_exists && $flaws_exists) {
    echo "<h2>Tables 'merits' and 'flaws' already exist.</h2>";
    echo "<p>This script will update existing entries and add missing ones.</p>";
} else {
    // Create Merits table
    if (!$merits_exists) {
        $create_merits_sql = "
        CREATE TABLE IF NOT EXISTS merits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            cost VARCHAR(20) DEFAULT NULL,
            description TEXT,
            clan VARCHAR(50) DEFAULT NULL,
            display_order INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_display_order (display_order),
            INDEX idx_clan (clan),
            INDEX idx_cost (cost)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!mysqli_query($conn, $create_merits_sql)) {
            echo "<h2>❌ Error: Failed to create merits table</h2>";
            echo "<p>Error: " . mysqli_error($conn) . "</p>";
            mysqli_close($conn);
            exit;
        }
        echo "<h2>✅ Success: Table 'merits' created successfully!</h2>";
    }
    
    // Create Flaws table
    if (!$flaws_exists) {
        $create_flaws_sql = "
        CREATE TABLE IF NOT EXISTS flaws (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            cost VARCHAR(20) DEFAULT NULL,
            description TEXT,
            clan VARCHAR(50) DEFAULT NULL,
            display_order INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_display_order (display_order),
            INDEX idx_clan (clan),
            INDEX idx_cost (cost)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!mysqli_query($conn, $create_flaws_sql)) {
            echo "<h2>❌ Error: Failed to create flaws table</h2>";
            echo "<p>Error: " . mysqli_error($conn) . "</p>";
            mysqli_close($conn);
            exit;
        }
        echo "<h2>✅ Success: Table 'flaws' created successfully!</h2>";
    }
}

// Load LotNR.md for description extraction
$lotnr_file = __DIR__ . '/../reference/Books_md_ready_fixed_cleaned_v2/LotNR.md';
if (!file_exists($lotnr_file)) {
    die("Error: LotNR.md file not found at: $lotnr_file\n");
}

$lotnr_content = file_get_contents($lotnr_file);
if ($lotnr_content === false) {
    die("Error: Could not read LotNR.md file\n");
}

// Function to extract description from LotNR.md
function extractDescription(string $name, string $content, string $type): ?string {
    // Normalize name for searching - remove special chars, handle case
    $normalized = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $name));
    $name_words = array_filter(explode(' ', $normalized));
    
    // Build pattern that handles OCR issues (split words like "am Bidextrous")
    // Match the key words from the name, allowing spaces between letters
    $word_patterns = [];
    foreach ($name_words as $word) {
        if (strlen($word) > 1) {
            // Allow space after first letter (OCR: "a m" = "am")
            $word_patterns[] = substr($word, 0, 1) . '[\s]*' . substr($word, 1);
        } else {
            $word_patterns[] = $word;
        }
    }
    $flexible_pattern = implode('[\s\-]*', $word_patterns);
    
    // Also try exact match (case insensitive, normalized)
    $exact_pattern = preg_quote($normalized, '/');
    
    // Pattern 1: Name followed by "(cost trait merit/flaw)" then description
    // Example: "am Bidextrous (1 trait merit) You have..."
    $pattern1 = "/(?:{$flexible_pattern}|{$exact_pattern})[\s\-]*\([^)]*trait\s+{$type}\)[^a-z]*([A-Z][^.]*(?:\.[^.]*)*?)(?=\s+[a-z]+\s*\(|\s*\[Page|\s*<div|$)/is";
    
    // Pattern 2: Name followed by description (more flexible, no cost requirement)
    $pattern2 = "/(?:{$flexible_pattern}|{$exact_pattern})[\s\-]*[^a-z]*([A-Z][^.]*(?:\.[^.]*)*?)(?=\s+[a-z]+\s*\(|\s*\[Page|\s*<div|$)/is";
    
    $patterns = [$pattern1, $pattern2];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $desc = trim($matches[1]);
            // Clean up the description
            $desc = preg_replace('/\s+/', ' ', $desc);
            $desc = preg_replace('/\[Page \d+\]/', '', $desc);
            $desc = preg_replace('/<div[^>]*>.*?<\/div>/s', '', $desc);
            $desc = preg_replace('/<div style="page-break-after: always;"><\/div>/', '', $desc);
            $desc = trim($desc);
            
            // Only return if it's substantial and looks like a real description
            if (strlen($desc) > 30 && preg_match('/^[A-Z]/', $desc)) {
                return $desc;
            }
        }
    }
    
    return null;
}

// Parse XML file
$xml_file = __DIR__ . '/../Grapevine/Grapevine Menus XML.gvm';
if (!file_exists($xml_file)) {
    die("Error: XML file not found at: $xml_file\n");
}

$xml_content = file_get_contents($xml_file);
if ($xml_content === false) {
    die("Error: Could not read XML file\n");
}

// Extract merits and flaws from XML
$merits_data = [];
$flaws_data = [];

// Function to extract items from a menu
function extractMenuItems(string $menu_content, string $menu_name): array {
    $items = [];
    $lines = explode("\n", $menu_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Match: <item name="Name" cost="value"/>
        if (preg_match('/<item\s+name="([^"]+)"(?:\s+cost="([^"]*)")?/', $line, $m)) {
            $name = trim($m[1]);
            $cost = isset($m[2]) && $m[2] !== '' ? trim($m[2]) : null;
            $items[] = ['name' => $name, 'cost' => $cost];
        }
    }
    
    return $items;
}

// Extract main Merits menu
if (preg_match('/<menu\s+name="Merits"[^>]*>(.*?)<\/menu>/s', $xml_content, $matches)) {
    $items = extractMenuItems($matches[1], 'Merits');
    foreach ($items as $item) {
        $merits_data[] = [
            'name' => $item['name'],
            'cost' => $item['cost'],
            'clan' => null
        ];
    }
}

// Extract main Flaws menu
if (preg_match('/<menu\s+name="Flaws"[^>]*>(.*?)<\/menu>/s', $xml_content, $matches)) {
    $items = extractMenuItems($matches[1], 'Flaws');
    foreach ($items as $item) {
        $flaws_data[] = [
            'name' => $item['name'],
            'cost' => $item['cost'],
            'clan' => null
        ];
    }
}

// Extract clan-specific menus (category 2 only, and "Merits, Vampire" / "Flaws, Vampire")
preg_match_all('/<menu\s+name="(Merits|Flaws),\s*([^"]+)"[^>]*category="(\d+)"[^>]*>(.*?)<\/menu>/s', $xml_content, $clan_matches, PREG_SET_ORDER);

foreach ($clan_matches as $match) {
    $type = $match[1]; // "Merits" or "Flaws"
    $clan_name = trim($match[2]);
    $category = (int)$match[3];
    $menu_content = $match[4];
    
    // Only include category 2 (vampire clans) or "Vampire" menus
    if ($category === 2 || $clan_name === 'Vampire') {
        $items = extractMenuItems($menu_content, $type . ', ' . $clan_name);
        foreach ($items as $item) {
            if ($type === 'Merits') {
                $merits_data[] = [
                    'name' => $item['name'],
                    'cost' => $item['cost'],
                    'clan' => $clan_name
                ];
            } else {
                $flaws_data[] = [
                    'name' => $item['name'],
                    'cost' => $item['cost'],
                    'clan' => $clan_name
                ];
            }
        }
    }
}

// Remove duplicates (keep first occurrence)
$merits_unique = [];
$merits_seen = [];
foreach ($merits_data as $merit) {
    $key = strtolower($merit['name']);
    if (!isset($merits_seen[$key])) {
        $merits_seen[$key] = true;
        $merits_unique[] = $merit;
    }
}

$flaws_unique = [];
$flaws_seen = [];
foreach ($flaws_data as $flaw) {
    $key = strtolower($flaw['name']);
    if (!isset($flaws_seen[$key])) {
        $flaws_seen[$key] = true;
        $flaws_unique[] = $flaw;
    }
}

$merits_data = $merits_unique;
$flaws_data = $flaws_unique;

// Sort by name for display order
usort($merits_data, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

usort($flaws_data, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Extract descriptions and prepare for database
$merits_with_descriptions = [];
$flaws_with_descriptions = [];
$merits_missing_desc = [];
$flaws_missing_desc = [];
$merits_null_cost = [];
$flaws_null_cost = [];

foreach ($merits_data as $idx => $merit) {
    $description = extractDescription($merit['name'], $lotnr_content, 'merit');
    if (!$description) {
        $merits_missing_desc[] = $merit['name'];
    }
    if ($merit['cost'] === null) {
        $merits_null_cost[] = $merit['name'];
    }
    
    $merits_with_descriptions[] = [
        'name' => $merit['name'],
        'cost' => $merit['cost'],
        'description' => $description,
        'clan' => $merit['clan'],
        'display_order' => $idx + 1
    ];
}

foreach ($flaws_data as $idx => $flaw) {
    $description = extractDescription($flaw['name'], $lotnr_content, 'flaw');
    if (!$description) {
        $flaws_missing_desc[] = $flaw['name'];
    }
    if ($flaw['cost'] === null) {
        $flaws_null_cost[] = $flaw['name'];
    }
    
    $flaws_with_descriptions[] = [
        'name' => $flaw['name'],
        'cost' => $flaw['cost'],
        'description' => $description,
        'clan' => $flaw['clan'],
        'display_order' => $idx + 1
    ];
}

// Insert/Update merits
$merits_insert_sql = "INSERT INTO merits (name, cost, description, clan, display_order) 
                      VALUES (?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      cost = VALUES(cost),
                      description = VALUES(description),
                      clan = VALUES(clan),
                      display_order = VALUES(display_order),
                      updated_at = CURRENT_TIMESTAMP";
$merits_stmt = mysqli_prepare($conn, $merits_insert_sql);

if (!$merits_stmt) {
    echo "<h2>❌ Error: Failed to prepare merits insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$merits_inserted = 0;
$merits_errors = [];

foreach ($merits_with_descriptions as $merit) {
    mysqli_stmt_bind_param($merits_stmt, 'ssssi', 
        $merit['name'], 
        $merit['cost'],
        $merit['description'],
        $merit['clan'],
        $merit['display_order']
    );
    
    if (!mysqli_stmt_execute($merits_stmt)) {
        $merits_errors[] = "Failed to insert/update {$merit['name']}: " . mysqli_stmt_error($merits_stmt);
    } else {
        $merits_inserted++;
    }
}

mysqli_stmt_close($merits_stmt);

// Insert/Update flaws
$flaws_insert_sql = "INSERT INTO flaws (name, cost, description, clan, display_order) 
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     cost = VALUES(cost),
                     description = VALUES(description),
                     clan = VALUES(clan),
                     display_order = VALUES(display_order),
                     updated_at = CURRENT_TIMESTAMP";
$flaws_stmt = mysqli_prepare($conn, $flaws_insert_sql);

if (!$flaws_stmt) {
    echo "<h2>❌ Error: Failed to prepare flaws insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$flaws_inserted = 0;
$flaws_errors = [];

foreach ($flaws_with_descriptions as $flaw) {
    mysqli_stmt_bind_param($flaws_stmt, 'ssssi', 
        $flaw['name'], 
        $flaw['cost'],
        $flaw['description'],
        $flaw['clan'],
        $flaw['display_order']
    );
    
    if (!mysqli_stmt_execute($flaws_stmt)) {
        $flaws_errors[] = "Failed to insert/update {$flaw['name']}: " . mysqli_stmt_error($flaws_stmt);
    } else {
        $flaws_inserted++;
    }
}

mysqli_stmt_close($flaws_stmt);

// Display results
echo "<h2>✅ Database Update Complete</h2>";
echo "<p>✅ Successfully inserted/updated {$merits_inserted} merits.</p>";
echo "<p>✅ Successfully inserted/updated {$flaws_inserted} flaws.</p>";

if (count($merits_errors) > 0 || count($flaws_errors) > 0) {
    echo "<h3>⚠️ Warnings:</h3>";
    echo "<ul>";
    foreach (array_merge($merits_errors, $flaws_errors) as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Display missing descriptions
echo "<h3>📋 Merits Missing Descriptions (" . count($merits_missing_desc) . "):</h3>";
if (count($merits_missing_desc) > 0) {
    echo "<ul>";
    foreach ($merits_missing_desc as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All merits have descriptions!</p>";
}

echo "<h3>📋 Flaws Missing Descriptions (" . count($flaws_missing_desc) . "):</h3>";
if (count($flaws_missing_desc) > 0) {
    echo "<ul>";
    foreach ($flaws_missing_desc as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All flaws have descriptions!</p>";
}

// Display NULL costs
echo "<h3>💰 Merits with NULL Cost (" . count($merits_null_cost) . "):</h3>";
if (count($merits_null_cost) > 0) {
    echo "<ul>";
    foreach ($merits_null_cost as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All merits have costs!</p>";
}

echo "<h3>💰 Flaws with NULL Cost (" . count($flaws_null_cost) . "):</h3>";
if (count($flaws_null_cost) > 0) {
    echo "<ul>";
    foreach ($flaws_null_cost as $name) {
        echo "<li>" . htmlspecialchars($name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>All flaws have costs!</p>";
}

// Save lists to files
$output_dir = __DIR__ . '/../tmp';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

file_put_contents($output_dir . '/merits_missing_descriptions.txt', implode("\n", $merits_missing_desc));
file_put_contents($output_dir . '/flaws_missing_descriptions.txt', implode("\n", $flaws_missing_desc));
file_put_contents($output_dir . '/merits_null_cost.txt', implode("\n", $merits_null_cost));
file_put_contents($output_dir . '/flaws_null_cost.txt', implode("\n", $flaws_null_cost));

echo "<h3>💾 Lists saved to:</h3>";
echo "<ul>";
echo "<li>tmp/merits_missing_descriptions.txt</li>";
echo "<li>tmp/flaws_missing_descriptions.txt</li>";
echo "<li>tmp/merits_null_cost.txt</li>";
echo "<li>tmp/flaws_null_cost.txt</li>";
echo "</ul>";

// Show sample data
echo "<h3>Sample Merits (first 10):</h3>";
$sample_sql = "SELECT name, cost, clan, LEFT(description, 100) as desc_preview FROM merits ORDER BY display_order LIMIT 10";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Name</th><th>Cost</th><th>Clan</th><th>Description Preview</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['clan'] ?? 'General') . "</td>";
        echo "<td>" . htmlspecialchars($row['desc_preview'] ?? 'No description') . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

echo "<h3>Sample Flaws (first 10):</h3>";
$sample_sql = "SELECT name, cost, clan, LEFT(description, 100) as desc_preview FROM flaws ORDER BY display_order LIMIT 10";
$sample_result = mysqli_query($conn, $sample_sql);

if ($sample_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Name</th><th>Cost</th><th>Clan</th><th>Description Preview</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['clan'] ?? 'General') . "</td>";
        echo "<td>" . htmlspecialchars($row['desc_preview'] ?? 'No description') . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($sample_result);
}

mysqli_close($conn);
?>
