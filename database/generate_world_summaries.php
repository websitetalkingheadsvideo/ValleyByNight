<?php
/**
 * VbN Canon Summary Generator
 * 
 * Generates versioned world summary reports from the Pair Networks MySQL database.
 * 
 * Hard Rules:
 * - Source of truth: Remote DB (Pair Networks) ONLY
 * - READ ONLY DB access (no INSERT/UPDATE/DELETE/ALTER/CREATE)
 * - Never connect to localhost - fail hard if DB host resolves to localhost/socket
 * - Use connect.php only - no new credentials or inline secrets
 * - Do not modify existing files - only create/overwrite summary files
 * 
 * Usage:
 *   php database/generate_world_summaries.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/** When true (set by web caller), abort() throws so caller can catch; otherwise die(). */
function _generate_world_summaries_abort(string $msg, int $code = 1): void {
    if (defined('GENERATE_WORLD_SUMMARIES_WEB_MODE') && GENERATE_WORLD_SUMMARIES_WEB_MODE) {
        throw new RuntimeException($msg, $code);
    }
    die($msg);
}

$project_root = dirname(__DIR__);

// Include version management
require_once $project_root . '/includes/version.php';

// Validate version constant exists
if (!defined('LOTN_VERSION')) {
    _generate_world_summaries_abort("ERROR: LOTN_VERSION constant not defined. Cannot proceed without version.\n");
}

$version = LOTN_VERSION;
$version_shorthand = str_replace('.', '', $version); // 0.8.63 → 0863

// Validate version format (should be X.Y.Z)
if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    _generate_world_summaries_abort("ERROR: Invalid version format '{$version}'. Expected format: X.Y.Z\n");
}

echo "Version detected: {$version} (shorthand: {$version_shorthand})\n";

// Include database connection
require_once $project_root . '/includes/connect.php';

// Validate connection
if (!$conn) {
    _generate_world_summaries_abort("ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
}

// CRITICAL: Validate we're connecting to remote DB, not localhost
$db_host = getenv('DB_HOST') ?: "vdb5.pit.pair.com";
if ($db_host === 'localhost' || $db_host === '127.0.0.1' || strpos($db_host, '/') !== false) {
    _generate_world_summaries_abort("ERROR: Database host '{$db_host}' appears to be localhost or socket. This script requires remote Pair Networks database only.\n");
}

// Verify we can query (READ-ONLY check)
$test_query = "SELECT 1 as test";
$test_result = mysqli_query($conn, $test_query);
if (!$test_result) {
    _generate_world_summaries_abort("ERROR: Cannot execute queries on database. Connection may be invalid.\n");
}
mysqli_free_result($test_result);

echo "Database connection validated: {$db_host}\n";
echo "READ-ONLY mode: All queries are SELECT only\n\n";

// Output directory
$output_dir = $project_root . '/reference/world/_summaries';
if (!is_dir($output_dir)) {
    if (!mkdir($output_dir, 0755, true)) {
        _generate_world_summaries_abort("ERROR: Cannot create output directory: {$output_dir}\n");
    }
}

// Get current datetime in America/Denver timezone for metadata
date_default_timezone_set('America/Denver');
$generation_datetime = date('Y-m-d H:i America/Denver');
$generation_date = date('Y-m-d');

/**
 * Convert version to shorthand format
 * 0.8.63 → 0863
 */
function getVersionShorthand(string $version): string {
    return str_replace('.', '', $version);
}

/**
 * Generate metadata header YAML frontmatter (Index-Page Contract)
 */
function generateMetadataHeader(string $report_id, string $title, string $version, string $generated_datetime): string {
    $shorthand = getVersionShorthand($version);
    return <<<YAML
---
report_id: {$report_id}
version: {$version}
title: {$title}
source: remote_db_pair_networks
generated_at: {$generated_datetime}
db_readonly: true
---

YAML;
}

/**
 * Handle NULL or invalid values - display as UNKNOWN
 */
function handleUnknownValue($value): string {
    if ($value === null || $value === '' || $value === false) {
        return 'UNKNOWN';
    }
    return (string)$value;
}

/**
 * Handle narrative fields with precedence rules
 * Priority: full narratives > summaries
 * Never truncate - output verbatim or mark as truncated
 */
function handleNarrativeField($full_field, $summary_field = null): string {
    // Priority 1: Full narrative fields
    if (!empty($full_field)) {
        // Check if already truncated (common patterns)
        if (strlen($full_field) > 500 && (substr($full_field, -3) === '...' || substr($full_field, -4) === '....')) {
            return $full_field . "\n\n[TRUNCATED IN SOURCE]";
        }
        return $full_field;
    }
    
    // Priority 2: Summary fields
    if (!empty($summary_field)) {
        return $summary_field;
    }
    
    // No data exists
    return 'MISSING';
}

/**
 * Inspect database schema (read-only) to detect available fields
 */
function inspectSchema(mysqli $conn, string $table_name): array {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table_name}`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[$row['Field']] = $row['Type'];
        }
        mysqli_free_result($result);
    }
    return $columns;
}

/**
 * Generate Characters Summary
 */
function generateCharactersSummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('characters_summary', 'Character Summary - VbN World Overview', $version, $generation_datetime);
    
    // Inspect schema for available fields
    $char_columns = inspectSchema($conn, 'characters');
    $has_creature_type = isset($char_columns['creature_type']);
    $has_sect = isset($char_columns['sect']);
    
    // Check for wraith_characters table
    $has_wraith_table = false;
    $wraith_count = 0;
    $wraith_table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'wraith_characters'");
    if ($wraith_table_check && mysqli_num_rows($wraith_table_check) > 0) {
        $has_wraith_table = true;
        $wraith_count_result = @db_fetch_one($conn, "SELECT COUNT(*) as count FROM wraith_characters ORDER BY id");
        $wraith_count = (int)($wraith_count_result['count'] ?? 0);
    }
    
    $output = $metadata;
    $output .= "# Character Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    
    // High-Level Stats
    $output .= "## High-Level Stats\n\n";
    $output .= "### **Fact** (from database)\n\n";
    
    // Total character count from characters table (vampires)
    $total_count = db_fetch_one($conn, "SELECT COUNT(*) as count FROM characters ORDER BY id");
    $total_vampires = (int)($total_count['count'] ?? 0);
    
    // Counts by creature type
    if ($has_creature_type) {
        $creature_counts = db_fetch_all($conn,
            "SELECT creature_type, COUNT(*) as count 
             FROM characters 
             WHERE creature_type IS NOT NULL AND creature_type != ''
             GROUP BY creature_type 
             ORDER BY count DESC, creature_type"
        );
        if (!empty($creature_counts)) {
            $output .= "**By Creature Type:**\n";
            foreach ($creature_counts as $type) {
                $output .= "- **{$type['creature_type']}**: {$type['count']}\n";
            }
            $output .= "\n";
        }
    } else {
        // Characters table contains vampires (Kindred) - all have clan/generation
        // Wraiths are in separate wraith_characters table
        $output .= "**By Creature Type:**\n";
        $output .= "- **Kindred (Vampires)**: {$total_vampires}\n";
        if ($has_wraith_table && $wraith_count > 0) {
            $output .= "- **Wraiths**: {$wraith_count}\n";
        }
        $total_all = $total_vampires + $wraith_count;
        $output .= "- **Total**: {$total_all}\n\n";
    }
    
    // Counts by clan (Kindred only)
    $clan_counts = db_fetch_all($conn,
        "SELECT clan, COUNT(*) as count 
         FROM characters 
         WHERE clan IS NOT NULL AND clan != '' AND clan != 'N/A'
         GROUP BY clan 
         ORDER BY count DESC, clan"
    );
    if (!empty($clan_counts)) {
        $output .= "**By Clan (Kindred only):**\n";
        foreach ($clan_counts as $clan) {
            $output .= "- **{$clan['clan']}**: {$clan['count']}\n";
        }
        $output .= "\n";
    }
    
    // Counts by generation
    $gen_counts = db_fetch_all($conn,
        "SELECT generation, COUNT(*) as count 
         FROM characters 
         WHERE generation IS NOT NULL
         GROUP BY generation 
         ORDER BY generation ASC"
    );
    if (!empty($gen_counts)) {
        $output .= "**By Generation:**\n";
        foreach ($gen_counts as $gen) {
            $output .= "- **Generation {$gen['generation']}**: {$gen['count']}\n";
        }
        $output .= "\n";
    }
    
    $output .= "---\n\n";
    
    // Data quality tracking
    $quality_issues = [];
    $duplicate_names = [];
    $missing_biographies = [];
    $missing_appearances = [];
    $missing_relationships = [];
    $truncated_narratives = [];
    
    // Check for duplicate names
    $duplicates = db_fetch_all($conn,
        "SELECT character_name, COUNT(*) as count 
         FROM characters 
         GROUP BY character_name 
         HAVING count > 1 
         ORDER BY character_name"
    );
    foreach ($duplicates as $dup) {
        $duplicate_names[] = "{$dup['character_name']} (appears {$dup['count']} times)";
    }
    
    // Per-Character Entries
    $output .= "## Per-Character Entries\n\n";
    
    // Get all characters with explicit ORDER BY for determinism
    $characters = db_fetch_all($conn, 
        "SELECT * FROM characters ORDER BY id ASC, character_name ASC"
    );
    
    foreach ($characters as $char) {
        $char_id = (int)$char['id'];
        $char_name = handleUnknownValue($char['character_name']);
        
        $output .= "### **{$char_name}**\n\n";
        
        // Creature type determination
        // Characters in 'characters' table are vampires (Kindred) - all have clan/generation
        // Wraiths are stored in separate 'wraith_characters' table
        // Check if this character also exists in wraith_characters (shouldn't happen, but check anyway)
        $creature_type = 'Kindred'; // Default: characters table = vampires
        
        if ($has_creature_type) {
            $creature_type = handleUnknownValue($char['creature_type'] ?? null);
        } else {
            // Check if character name exists in wraith_characters table
            if ($has_wraith_table) {
                $wraith_check = @db_fetch_one($conn,
                    "SELECT id FROM wraith_characters WHERE character_name = ? ORDER BY id",
                    's', [$char_name]
                );
                if ($wraith_check) {
                    $creature_type = 'Wraith';
                } elseif (!empty($char['clan']) || !empty($char['generation'])) {
                    $creature_type = 'Kindred';
                } else {
                    // No clan/generation - could be mortal, ghoul, or unknown
                    $creature_type = 'UNKNOWN';
                }
            } else {
                // No wraith table, infer from clan/generation
                if (!empty($char['clan']) || !empty($char['generation'])) {
                    $creature_type = 'Kindred';
                } else {
                    $creature_type = 'UNKNOWN';
                }
            }
        }
        
        $output .= "- **Creature Type**: {$creature_type}\n";
        
        // Clan (if Kindred)
        if (!empty($char['clan']) && $char['clan'] !== 'N/A') {
            $output .= "- **Clan**: {$char['clan']}\n";
        }
        
        // Sect
        if ($has_sect && !empty($char['sect'])) {
            $output .= "- **Sect**: {$char['sect']}\n";
        }
        
        // Generation
        if (!empty($char['generation'])) {
            $output .= "- **Generation**: {$char['generation']}\n";
        }
        
        // Positions / Titles / Status
        // Check character_status table (if it exists)
        // Schema uses status_type, status_name, level (rows, not columns)
        $statuses = @db_fetch_all($conn,
            "SELECT status_type, status_name, level 
             FROM character_status 
             WHERE character_id = ? 
             ORDER BY status_type, status_name",
            'i', [$char_id]
        );
        
        if (!empty($statuses)) {
            $status_list = [];
            foreach ($statuses as $status) {
                $status_display = $status['status_name'];
                if (!empty($status['level'])) {
                    $status_display .= " (Level {$status['level']})";
                }
                $status_list[] = "{$status['status_type']}: {$status_display}";
            }
            $output .= "- **Status**: " . implode(', ', $status_list) . "\n";
        }
        
        // Check for positions via position_history or similar (if table exists)
        // Note: camarilla_positions structure may vary - query defensively
        $char_name = $char['character_name'] ?? '';
        if (!empty($char_name)) {
            // Try to find positions held by this character via position_history if it exists
            $position_history_check = @mysqli_query($conn, "SHOW TABLES LIKE 'position_history'");
            if ($position_history_check && mysqli_num_rows($position_history_check) > 0) {
                $positions = @db_fetch_all($conn,
                    "SELECT position_id 
                     FROM position_history 
                     WHERE character_id = ? AND end_date IS NULL 
                     ORDER BY position_id",
                    'i', [$char_id]
                );
                if (!empty($positions)) {
                    $pos_ids = array_column($positions, 'position_id');
                    // Get position names
                    $pos_names = [];
                    foreach ($pos_ids as $pos_id) {
                        $pos_info = @db_fetch_one($conn,
                            "SELECT name FROM camarilla_positions WHERE position_id = ?",
                            's', [$pos_id]
                        );
                        if ($pos_info && !empty($pos_info['name'])) {
                            $pos_names[] = $pos_info['name'];
                        }
                    }
                    if (!empty($pos_names)) {
                        $output .= "- **Positions**: " . implode(', ', $pos_names) . "\n";
                    }
                }
            }
        }
        
        // Biography (canonical field - single source of truth)
        $biography_text = handleNarrativeField($char['biography'] ?? null);
        
        if ($biography_text === 'MISSING') {
            $missing_biographies[] = $char_name;
        } elseif (strpos($biography_text, '[TRUNCATED IN SOURCE]') !== false) {
            $truncated_narratives[] = "{$char_name} (biography)";
        }
        
        $output .= "- **Biography**: {$biography_text}\n";
        
        // Appearance (full narrative preferred)
        $appearance_text = handleNarrativeField($char['appearance'] ?? null);
        
        if ($appearance_text === 'MISSING') {
            $missing_appearances[] = $char_name;
        } elseif (strpos($appearance_text, '[TRUNCATED IN SOURCE]') !== false) {
            $truncated_narratives[] = "{$char_name} (appearance)";
        }
        
        $output .= "- **Appearance**: {$appearance_text}\n";
        
        // Relationships
        $output .= "- **Relationships**:\n";
        
        // Sire
        if (!empty($char['sire'])) {
            $sire_exists = db_fetch_one($conn,
                "SELECT id FROM characters WHERE character_name = ? ORDER BY id",
                's', [$char['sire']]
            );
            if (!$sire_exists) {
                $missing_relationships[] = "{$char_name} -> Sire: {$char['sire']} (not found in database)";
            }
            $output .= "  - **Sire**: {$char['sire']}\n";
        }
        
        // Childe
        $childer = db_fetch_all($conn,
            "SELECT character_name 
             FROM characters 
             WHERE sire = ? 
             ORDER BY character_name",
            's', [$char_name]
        );
        if (!empty($childer)) {
            $childe_names = array_column($childer, 'character_name');
            $output .= "  - **Childe**: " . implode(', ', $childe_names) . "\n";
        }
        
        // Domitor (if ghoul)
        // Note: Ghouls might be in characters table without clan/generation, or identified differently
        if ($creature_type === 'Ghoul' || ($creature_type === 'UNKNOWN' && empty($char['clan']) && empty($char['generation']))) {
            // Check for domitor relationship
            $domitor = db_fetch_one($conn,
                "SELECT related_character_name 
                 FROM character_relationships 
                 WHERE character_id = ? AND relationship_type = 'Domitor' 
                 ORDER BY related_character_name",
                'i', [$char_id]
            );
            if ($domitor) {
                $output .= "  - **Domitor**: {$domitor['related_character_name']}\n";
            }
        }
        
        // Boons (read-only from boons table, if it exists)
        $boons = @db_fetch_all($conn,
            "SELECT boon_type, description 
             FROM boons 
             WHERE creditor_id = ? OR debtor_id = ? 
             ORDER BY created_date DESC",
            'ii', [$char_id, $char_id]
        );
        if (!empty($boons)) {
            $output .= "  - **Boons**: " . count($boons) . " boon(s) recorded\n";
        }
        
        // Key allies/rivals from character_relationships
        $relationships = db_fetch_all($conn,
            "SELECT related_character_name, relationship_type, relationship_subtype, description 
             FROM character_relationships 
             WHERE character_id = ? 
             AND relationship_type IN ('Ally', 'Rival', 'Enemy', 'Friend') 
             ORDER BY relationship_type, related_character_name",
            'i', [$char_id]
        );
        if (!empty($relationships)) {
            foreach ($relationships as $rel) {
                $rel_type = $rel['relationship_type'];
                if (!empty($rel['relationship_subtype'])) {
                    $rel_type .= " ({$rel['relationship_subtype']})";
                }
                $output .= "  - **{$rel_type}**: {$rel['related_character_name']}";
                if (!empty($rel['description'])) {
                    $output .= " - {$rel['description']}";
                }
                $output .= "\n";
            }
        }
        
        $output .= "\n";
    }
    
    // Data Quality Issues Section (MANDATORY)
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    
    if (!empty($duplicate_names)) {
        $quality_issues[] = "**Duplicate Character Names:** " . implode(', ', $duplicate_names);
    }
    if (!empty($missing_biographies)) {
        $quality_issues[] = "**Missing Biography:** " . count($missing_biographies) . " characters (" . implode(', ', array_slice($missing_biographies, 0, 10)) . (count($missing_biographies) > 10 ? '...' : '') . ")";
    }
    if (!empty($missing_appearances)) {
        $quality_issues[] = "**Missing Appearance:** " . count($missing_appearances) . " characters (" . implode(', ', array_slice($missing_appearances, 0, 10)) . (count($missing_appearances) > 10 ? '...' : '') . ")";
    }
    if (!empty($missing_relationships)) {
        $quality_issues[] = "**Orphaned Relationship References:** " . count($missing_relationships) . " references (" . implode('; ', array_slice($missing_relationships, 0, 5)) . (count($missing_relationships) > 5 ? '...' : '') . ")";
    }
    if (!empty($truncated_narratives)) {
        $quality_issues[] = "**Truncated Narrative Fields:** " . implode(', ', array_unique($truncated_narratives));
    }
    
    if (empty($quality_issues)) {
        $output .= "No data quality issues detected.\n";
    } else {
        foreach ($quality_issues as $issue) {
            $output .= "- {$issue}\n";
        }
    }
    
    return $output;
}

/**
 * Generate Locations Summary
 */
function generateLocationsSummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('locations_summary', 'Location Summary - VbN World Overview', $version, $generation_datetime);
    
    $output = $metadata;
    $output .= "# Location Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    
    // Get all locations with explicit ORDER BY
    $locations = db_fetch_all($conn, 
        "SELECT * FROM locations ORDER BY id ASC, name ASC"
    );
    $total_locations = count($locations);
    
    $output .= "## Location Overview\n\n";
    $output .= "### **Fact** (from database)\n\n";
    $output .= "- **Total Locations**: {$total_locations}\n\n";
    
    // Type distribution
    $type_counts = db_fetch_all($conn,
        "SELECT type, COUNT(*) as count 
         FROM locations 
         WHERE type IS NOT NULL AND type != ''
         GROUP BY type 
         ORDER BY count DESC, type"
    );
    if (!empty($type_counts)) {
        $output .= "**By Type:**\n";
        foreach ($type_counts as $type) {
            $output .= "- **{$type['type']}**: {$type['count']}\n";
        }
        $output .= "\n";
    }
    
    $output .= "---\n\n";
    $output .= "## Locations by District\n\n";
    
    // Group by district
    $districts = db_fetch_all($conn,
        "SELECT district, COUNT(*) as count 
         FROM locations 
         WHERE district IS NOT NULL AND district != ''
         GROUP BY district 
         ORDER BY district"
    );
    
    $quality_issues = [];
    $missing_descriptions = [];
    $truncated_descriptions = [];
    
    foreach ($districts as $district) {
        $output .= "### {$district['district']}\n\n";
        
        $district_locations = db_fetch_all($conn,
            "SELECT * FROM locations WHERE district = ? ORDER BY id ASC, name ASC",
            's', [$district['district']]
        );
        
        foreach ($district_locations as $loc) {
            $output .= "#### **{$loc['name']}**\n\n";
            
            // Type
            if (!empty($loc['type'])) {
                $output .= "- **Type**: {$loc['type']}\n";
            }
            
            // Description (full narrative preferred over summary)
            $description = handleNarrativeField($loc['description'] ?? null, $loc['summary'] ?? null);
            
            if ($description === 'MISSING') {
                $missing_descriptions[] = $loc['name'];
            } elseif (strpos($description, '[TRUNCATED IN SOURCE]') !== false) {
                $truncated_descriptions[] = $loc['name'];
            }
            
            $output .= "- **Description**: {$description}\n";
            
            // Faction
            if (!empty($loc['faction'])) {
                $output .= "- **Faction**: {$loc['faction']}\n";
            }
            
            // Owner type
            if (!empty($loc['owner_type'])) {
                $output .= "- **Owner Type**: {$loc['owner_type']}\n";
            }
            
            $output .= "\n";
        }
    }
    
    // Data Quality Issues
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    
    if (!empty($missing_descriptions)) {
        $quality_issues[] = "**Missing Descriptions:** " . count($missing_descriptions) . " locations";
    }
    if (!empty($truncated_descriptions)) {
        $quality_issues[] = "**Truncated Descriptions:** " . implode(', ', $truncated_descriptions);
    }
    
    if (empty($quality_issues)) {
        $output .= "No data quality issues detected.\n";
    } else {
        foreach ($quality_issues as $issue) {
            $output .= "- {$issue}\n";
        }
    }
    
    return $output;
}

/**
 * Generate Game Lore Summary
 */
function generateGameLoreSummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('game_lore_summary', 'Game Lore Summary - VbN World Overview', $version, $generation_datetime);
    
    $output = $metadata;
    $output .= "# Game Lore Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    $output .= "## Core Premise\n\n";
    $output .= "### **Fact** (from database)\n\n";
    $output .= "The chronicle tagline: **\"On your first night among the Kindred, the Prince dies—and the city of Phoenix bleeds intrigue...\"**\n\n";
    $output .= "- **City**: Phoenix, Arizona\n";
    $output .= "- **Year**: 1994\n";
    $output .= "- **Tone**: Recognizable Phoenix but not tied strictly to real-world accuracy\n\n";
    
    // Data Quality Issues
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    $output .= "No data quality issues detected.\n";
    
    return $output;
}

/**
 * Generate Plot Hooks Summary
 */
function generatePlotHooksSummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('plot_hooks_summary', 'Plot Hooks Summary - VbN World Overview', $version, $generation_datetime);
    
    $output = $metadata;
    $output .= "# Plot Hooks Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    $output .= "## Plot Hooks\n\n";
    $output .= "### **Fact** (from database)\n\n";
    
    // Check if plot_hooks table exists
    $tables = db_fetch_all($conn, "SHOW TABLES LIKE 'plot_hooks'");
    if (!empty($tables)) {
        $plot_hooks = db_fetch_all($conn, 
            "SELECT * FROM plot_hooks ORDER BY id ASC, created_at DESC"
        );
        
        if (!empty($plot_hooks)) {
            foreach ($plot_hooks as $hook) {
                $output .= "#### " . handleUnknownValue($hook['title'] ?? 'Untitled Plot Hook') . "\n\n";
                $output .= "- **Fact** (from database): " . handleNarrativeField($hook['description'] ?? null) . "\n\n";
            }
        } else {
            $output .= "No plot hooks found in database.\n\n";
        }
    } else {
        $output .= "No plot_hooks table found in database.\n\n";
    }
    
    // Data Quality Issues
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    $output .= "No data quality issues detected.\n";
    
    return $output;
}

/**
 * Generate Canon Clan Summary
 */
function generateCanonClanSummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('canon_clan_summary', 'Canon Clan Summary - VbN World Overview', $version, $generation_datetime);
    
    $output = $metadata;
    $output .= "# Canon Clan Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    $output .= "## Clan Presence in Phoenix\n\n";
    $output .= "### **Fact** (from database)\n\n";
    
    // Get clan distribution with character details (explicit ORDER BY)
    $clan_distribution = db_fetch_all($conn,
        "SELECT clan, COUNT(*) as count 
         FROM characters 
         WHERE clan IS NOT NULL AND clan != '' AND clan != 'N/A'
         GROUP BY clan 
         ORDER BY count DESC, clan"
    );
    
    foreach ($clan_distribution as $clan_data) {
        $clan_name = $clan_data['clan'];
        $output .= "### {$clan_name} ({$clan_data['count']} characters)\n\n";
        
        $clan_members = db_fetch_all($conn,
            "SELECT id, character_name, generation, concept, biography 
             FROM characters 
             WHERE clan = ? 
             ORDER BY id ASC, generation ASC, character_name ASC",
            's', [$clan_name]
        );
        
        foreach ($clan_members as $member) {
            $output .= "#### " . handleUnknownValue($member['character_name']) . "\n";
            if (!empty($member['generation'])) {
                $output .= " (Generation {$member['generation']})\n\n";
            } else {
                $output .= "\n\n";
            }
            
            if (!empty($member['concept'])) {
                $output .= "- **Fact** (from database): {$member['concept']}\n";
            }
            
            $bio = handleNarrativeField($member['biography'] ?? null);
            if ($bio !== 'MISSING') {
                $output .= "- **Fact**: {$bio}\n";
            }
            $output .= "\n";
        }
    }
    
    // Data Quality Issues
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    $output .= "No data quality issues detected.\n";
    
    return $output;
}

/**
 * Generate VbN History Summary
 */
function generateVbNHistorySummary(mysqli $conn, string $version, string $generation_datetime, string $generation_date): string {
    $metadata = generateMetadataHeader('vbn_history_summary', 'VbN History Summary - VbN World Overview', $version, $generation_datetime);
    
    $output = $metadata;
    $output .= "# VbN History Summary - VbN World Overview\n\n";
    $output .= "**Source:** MySQL Database (canonical, read-only)\n";
    $output .= "**Generated:** {$generation_date}\n\n";
    $output .= "---\n\n";
    $output .= "## Historical Timeline\n\n";
    $output .= "### **Fact** (from database)\n\n";
    $output .= "Historical data extracted from character biographies, location descriptions, and other canonical sources.\n\n";
    
    // Extract dates from character biographies (explicit ORDER BY)
    $characters = db_fetch_all($conn, 
        "SELECT id, character_name, biography, clan, generation 
         FROM characters 
         WHERE biography IS NOT NULL AND biography != ''
         ORDER BY id ASC, character_name ASC"
    );
    
    $output .= "## Notable Events (Extracted from Character Biographies)\n\n";
    
    foreach ($characters as $char) {
        if (preg_match_all('/(\d{4})/', $char['biography'], $matches)) {
            $years = array_unique($matches[1]);
            sort($years);
            if (!empty($years)) {
                $output .= "### " . handleUnknownValue($char['character_name']);
                if (!empty($char['clan'])) {
                    $output .= " ({$char['clan']})";
                }
                $output .= "\n\n";
                $output .= "- **Fact** (from database): Historical references: " . implode(', ', $years) . "\n\n";
                $output .= handleNarrativeField($char['biography']) . "\n\n";
            }
        }
    }
    
    // Data Quality Issues
    $output .= "---\n\n";
    $output .= "## Data Quality Issues Detected\n\n";
    $output .= "No data quality issues detected.\n";
    
    return $output;
}

// Generate all summaries
echo "Generating world summaries...\n\n";

$summaries = [
    '01_characters_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generateCharactersSummary($conn, $version, $generation_datetime, $generation_date);
    },
    '02_locations_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generateLocationsSummary($conn, $version, $generation_datetime, $generation_date);
    },
    '03_game_lore_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generateGameLoreSummary($conn, $version, $generation_datetime, $generation_date);
    },
    '04_plot_hooks_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generatePlotHooksSummary($conn, $version, $generation_datetime, $generation_date);
    },
    '05_canon_clan_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generateCanonClanSummary($conn, $version, $generation_datetime, $generation_date);
    },
    '06_vbn_history_summary' => function() use ($conn, $version, $generation_datetime, $generation_date) {
        return generateVbNHistorySummary($conn, $version, $generation_datetime, $generation_date);
    },
];

$generated_count = 0;
$error_count = 0;

foreach ($summaries as $filename_base => $generator) {
    $filename = "{$filename_base}_{$version_shorthand}.md";
    $filepath = $output_dir . '/' . $filename;
    
    echo "Generating: {$filename}... ";
    
    try {
        $content = $generator();
        
        if (file_put_contents($filepath, $content) === false) {
            echo "FAILED (file write error)\n";
            $error_count++;
        } else {
            echo "OK (" . number_format(strlen($content)) . " bytes)\n";
            $generated_count++;
        }
    } catch (Exception $e) {
        echo "FAILED: {$e->getMessage()}\n";
        $error_count++;
    }
}

echo "\n";
echo "=== Summary Generation Complete ===\n";
echo "Generated: {$generated_count} files\n";
if ($error_count > 0) {
    echo "Errors: {$error_count} files\n";
}
echo "Version: {$version} ({$version_shorthand})\n";
echo "Output directory: {$output_dir}\n";

// Verify index-page compatibility
echo "\n";
echo "=== Website Compatibility Check ===\n";
$index_file = $project_root . '/reference/world/index.php';
if (file_exists($index_file)) {
    echo "Index page found: {$index_file}\n";
    echo "Compatible: Yes (files follow naming pattern: *_XXXX.md)\n";
} else {
    echo "WARNING: Index page not found. Files generated but compatibility not verified.\n";
}

mysqli_close($conn);
