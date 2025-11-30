<?php
/**
 * Database Migration: Create mcp_style_packs and mcp_style_chapters tables
 * 
 * This script creates the MCP (Modular Content Pack) registry tables for the VbN project.
 * These tables allow the system to discover, reference, and manage MCP packs through the database.
 * 
 * Run via browser: https://vbn.talkingheads.video/database/create_mcp_style_packs_table.php
 * Or via CLI: php database/create_mcp_style_packs_table.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if mcp_style_packs table already exists
$check_table = "SHOW TABLES LIKE 'mcp_style_packs'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Table 'mcp_style_packs' already exists.</h2>";
    echo "<p>If you want to recreate it, drop it first:</p>";
    echo "<pre>DROP TABLE IF EXISTS mcp_style_chapters;</pre>";
    echo "<pre>DROP TABLE IF EXISTS mcp_style_packs;</pre>";
    mysqli_free_result($result);
    exit;
}

// Begin transaction
if (!db_begin_transaction($conn)) {
    die("Failed to begin transaction: " . mysqli_error($conn));
}

$errors = [];

// Create mcp_style_packs table
$create_packs_table_sql = "
CREATE TABLE IF NOT EXISTS mcp_style_packs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    description TEXT,
    filesystem_path VARCHAR(500) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_enabled (enabled),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_packs_table_sql)) {
    $errors[] = "Failed to create mcp_style_packs table: " . mysqli_error($conn);
} else {
    echo "<h2>✅ Success: Table 'mcp_style_packs' created successfully!</h2>";
}

// Create mcp_style_chapters table (optional, for chapter-level metadata)
$create_chapters_table_sql = "
CREATE TABLE IF NOT EXISTS mcp_style_chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mcp_pack_id INT NOT NULL,
    chapter_name VARCHAR(255) NOT NULL,
    chapter_file VARCHAR(500) NOT NULL,
    chapter_number INT,
    description TEXT,
    tags VARCHAR(500),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mcp_pack_id) REFERENCES mcp_style_packs(id) ON DELETE CASCADE,
    INDEX idx_mcp_pack_id (mcp_pack_id),
    INDEX idx_chapter_number (chapter_number),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($conn, $create_chapters_table_sql)) {
    $errors[] = "Failed to create mcp_style_chapters table: " . mysqli_error($conn);
} else {
    echo "<h2>✅ Success: Table 'mcp_style_chapters' created successfully!</h2>";
}

// If there were errors, rollback
if (count($errors) > 0) {
    db_rollback($conn);
    echo "<h2>❌ Error: Transaction rolled back</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    mysqli_close($conn);
    exit;
}

// Commit transaction
if (!db_commit($conn)) {
    die("Failed to commit transaction: " . mysqli_error($conn));
}

// Insert initial Style_Agent_MCP row
$insert_mcp_sql = "INSERT INTO mcp_style_packs (name, slug, version, description, filesystem_path, enabled) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_mcp_sql);

if (!$stmt) {
    echo "<h2>❌ Error: Failed to prepare insert statement</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    mysqli_close($conn);
    exit;
}

$name = "Style Agent MCP";
$slug = "style_agent_mcp";
$version = "1.0.0";
$description = "Valley by Night Art Bible - Complete visual style guide for portraits, cinematics, locations, 3D assets, UI/web art, storyboards, marketing materials, floorplans, and naming conventions. Provides all rules, constraints, aesthetic guidelines, and prompt templates needed for generating and validating art assets that align with the gothic-noir, Phoenix 1994 aesthetic.";

// Use lowercase 'agents' path (matches both local and remote after upload)
$filesystem_path = "agents/style_agent";

$enabled = 1;

mysqli_stmt_bind_param($stmt, 'sssssi', $name, $slug, $version, $description, $filesystem_path, $enabled);

if (!mysqli_stmt_execute($stmt)) {
    echo "<h2>❌ Error: Failed to insert Style_Agent_MCP row</h2>";
    echo "<p>Error: " . mysqli_stmt_error($stmt) . "</p>";
} else {
    $mcp_id = mysqli_stmt_insert_id($stmt);
    echo "<p>✅ Successfully inserted Style_Agent_MCP row (ID: {$mcp_id})</p>";
}

mysqli_stmt_close($stmt);

// Optionally insert chapter metadata
// Note: Art_Bible_00_Introduction.md doesn't exist as a separate file - introduction is in the master file
$chapters_data = [
    ['Portrait System', 'Art_Bible_I_PORTRAIT_SYSTEM__.md', 1, 'Complete rules for 1024×1024 character portraits', 'portrait,character,clan', 1],
    ['Cinematic System', 'Art_Bible_II_CINEMATIC_SYSTEM__.md', 2, 'Rules for 30-60 second cinematic sequences', 'cinematic,cutscene,animation', 2],
    ['Location & Architecture', 'Art_Bible_III_LOCATION_&_ARCHITECTURE_SYSTEM__.md', 3, 'Visual identity for all environments', 'location,architecture,environment', 3],
    ['3D Asset System', 'Art_Bible_IV_3D_ASSET_SYSTEM__.md', 4, '3D production pipeline rules', '3d,model,texture,asset', 4],
    ['UI & Web Art', 'Art_Bible_V_UI_&_WEB_ART_SYSTEM__.md', 5, 'UI and web art design rules', 'ui,web,interface,design', 5],
    ['Storyboards & Animatics', 'Art_Bible_VI_STORYBOARDS_&_ANIMATICS__.md', 6, 'Storyboard and animatic creation guidelines', 'storyboard,animatic,planning', 6],
    ['Marketing Materials', 'Art_Bible_VII_MARKETING_MATERIALS_SYSTEM__.md', 7, 'Marketing material design rules', 'marketing,poster,banner', 7],
    ['Floorplan & Blueprint', 'Art_Bible_VIII_FLOORPLAN_&_BLUEPRINT_SYSTEM__.md', 8, 'Floorplan and blueprint creation rules', 'floorplan,blueprint,architecture', 8],
    ['Naming & Folder Structure', 'Art_Bible_IX_NAMING_CONVENTIONS_&_FOLDER_STRUCTURE__.md', 9, 'File organization and naming standards', 'naming,folder,organization', 9],
    ['Master Index & Integration', 'Art_Bible_X_MASTER_INDEX_&_INTEGRATION_LAYER__.md', 10, 'System integration and cross-references', 'index,integration,system', 10],
];

$insert_chapter_sql = "INSERT INTO mcp_style_chapters (mcp_pack_id, chapter_name, chapter_file, chapter_number, description, tags, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
$chapter_stmt = mysqli_prepare($conn, $insert_chapter_sql);

if (!$chapter_stmt) {
    echo "<h3>⚠️ Warning: Failed to prepare chapter insert statement</h3>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
} else {
    $inserted_chapters = 0;
    foreach ($chapters_data as $chapter) {
        mysqli_stmt_bind_param($chapter_stmt, 'ississs', $mcp_id, $chapter[0], $chapter[1], $chapter[2], $chapter[3], $chapter[4], $chapter[5]);
        
        if (!mysqli_stmt_execute($chapter_stmt)) {
            echo "<p>⚠️ Warning: Failed to insert chapter '{$chapter[0]}': " . mysqli_stmt_error($chapter_stmt) . "</p>";
        } else {
            $inserted_chapters++;
        }
    }
    
    if ($inserted_chapters > 0) {
        echo "<p>✅ Successfully inserted {$inserted_chapters} chapter metadata rows</p>";
    }
    
    mysqli_stmt_close($chapter_stmt);
}

// Show table structure
echo "<hr>";
echo "<h3>Table Structure: mcp_style_packs</h3>";
$describe_sql = "DESCRIBE mcp_style_packs";
$describe_result = mysqli_query($conn, $describe_sql);

if ($describe_result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($describe_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($describe_result);
}

// Show inserted MCP data
echo "<h3>Inserted MCP Pack:</h3>";
$show_mcp_sql = "SELECT * FROM mcp_style_packs WHERE slug = 'style_agent_mcp'";
$show_mcp_result = mysqli_query($conn, $show_mcp_sql);

if ($show_mcp_result && mysqli_num_rows($show_mcp_result) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    $mcp_row = mysqli_fetch_assoc($show_mcp_result);
    foreach ($mcp_row as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($show_mcp_result);
}

// Show chapter count
echo "<h3>Chapter Metadata:</h3>";
$chapter_count_sql = "SELECT COUNT(*) as count FROM mcp_style_chapters WHERE mcp_pack_id = ?";
$count_stmt = mysqli_prepare($conn, $chapter_count_sql);
mysqli_stmt_bind_param($count_stmt, 'i', $mcp_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
echo "<p>Total chapters registered: " . htmlspecialchars($count_row['count']) . "</p>";
mysqli_stmt_close($count_stmt);

// Rollback script (for reference)
echo "<hr>";
echo "<h3>Rollback Script (if needed):</h3>";
echo "<pre>";
echo "DROP TABLE IF EXISTS mcp_style_chapters;\n";
echo "DROP TABLE IF EXISTS mcp_style_packs;\n";
echo "</pre>";

mysqli_close($conn);
?>

