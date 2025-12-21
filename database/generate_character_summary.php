<?php
/**
 * Generate Character Summary Markdown
 * 
 * Analyzes exported NPC character JSON files and generates an updated
 * character summary markdown document.
 * 
 * Usage:
 *   php database/generate_character_summary.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$project_root = dirname(__DIR__);
$characters_dir = $project_root . '/reference/Characters/Added to Database';
$output_file = $project_root . '/reference/world/_summaries/01_characters_summary_0861.md';

// Database connection for getting current data
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/**
 * Get all NPCs from database with full data
 */
function getAllNPCs(mysqli $conn): array {
    $query = "SELECT * FROM characters WHERE (pc = 0 OR player_name = 'NPC') ORDER BY character_name";
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
 * Get character abilities summary
 */
function getAbilitiesSummary(mysqli $conn, int $character_id): string {
    $result = db_fetch_all($conn,
        "SELECT ca.ability_name, ca.level
         FROM character_abilities ca
         WHERE ca.character_id = ?
         ORDER BY ca.level DESC, ca.ability_name
         LIMIT 10",
        'i', [$character_id]
    );
    
    if (empty($result)) {
        return '';
    }
    
    $abilities = [];
    foreach ($result as $ability) {
        $abilities[] = $ability['ability_name'] . ' x' . $ability['level'];
    }
    
    return implode(', ', $abilities);
}

/**
 * Get character disciplines summary
 */
function getDisciplinesSummary(mysqli $conn, int $character_id): string {
    $result = db_fetch_all($conn,
        "SELECT discipline_name, level
         FROM character_disciplines
         WHERE character_id = ?
         ORDER BY level DESC, discipline_name",
        'i', [$character_id]
    );
    
    if (empty($result)) {
        return '';
    }
    
    $disciplines = [];
    foreach ($result as $disc) {
        $disciplines[] = $disc['discipline_name'] . ' ' . $disc['level'];
    }
    
    return implode(', ', $disciplines);
}

/**
 * Analyze characters and generate summary
 */
function generateSummary(mysqli $conn): string {
    $npcs = getAllNPCs($conn);
    
    // Clan distribution
    $clanCounts = [];
    foreach ($npcs as $npc) {
        $clan = $npc['clan'] ?? 'Unknown';
        $clanCounts[$clan] = ($clanCounts[$clan] ?? 0) + 1;
    }
    arsort($clanCounts);
    
    // Generation distribution
    $generationCounts = [];
    foreach ($npcs as $npc) {
        $gen = (int)($npc['generation'] ?? 13);
        $generationCounts[$gen] = ($generationCounts[$gen] ?? 0) + 1;
    }
    ksort($generationCounts);
    
    // Build summary
    $summary = "# Character Summary - VbN World Overview\n\n";
    $summary .= "**Source:** MySQL Database (canonical)\n";
    $summary .= "**Generated:** " . date('Y-m-d') . "\n";
    $summary .= "**Total NPCs:** " . count($npcs) . "\n\n";
    $summary .= "---\n\n";
    
    // Clan Distribution
    $summary .= "## Clan Distribution\n\n";
    $summary .= "### **Fact** (from database)\n\n";
    $summary .= "Based on analysis of " . count($npcs) . " NPC characters in the database:\n\n";
    
    foreach ($clanCounts as $clan => $count) {
        $summary .= "- **{$clan}**: {$count} character" . ($count > 1 ? 's' : '') . "\n";
    }
    
    $summary .= "\n### **Inference**\n\n";
    $topClan = array_key_first($clanCounts);
    $summary .= "The {$topClan} clan appears to have the strongest presence in Phoenix.\n\n";
    $summary .= "---\n\n";
    
    // Notable NPCs
    $summary .= "## Notable NPCs\n\n";
    
    // Group by roles/titles
    $courtOfficers = [];
    $primogen = [];
    $otherNotable = [];
    
    foreach ($npcs as $npc) {
        $notes = strtolower($npc['notes'] ?? '');
        $concept = strtolower($npc['concept'] ?? '');
        $title = '';
        
        // Check for titles in notes or concept
        if (stripos($notes, 'harpy') !== false || stripos($concept, 'harpy') !== false) {
            $title = 'Harpy';
            $courtOfficers[] = $npc;
        } elseif (stripos($notes, 'sheriff') !== false || stripos($concept, 'sheriff') !== false) {
            $title = 'Sheriff';
            $courtOfficers[] = $npc;
        } elseif (stripos($notes, 'primogen') !== false || stripos($concept, 'primogen') !== false) {
            $title = 'Primogen';
            $primogen[] = $npc;
        } else {
            $otherNotable[] = $npc;
        }
    }
    
    // Court Officers
    if (!empty($courtOfficers)) {
        $summary .= "### Court Officers\n\n";
        foreach ($courtOfficers as $npc) {
            $abilities = getAbilitiesSummary($conn, (int)$npc['id']);
            $disciplines = getDisciplinesSummary($conn, (int)$npc['id']);
            
            $summary .= "#### **{$npc['character_name']}** ({$npc['clan']}, Generation {$npc['generation']})\n";
            $summary .= "- **Fact** (from database): " . ($npc['concept'] ?? 'No concept recorded') . "\n";
            if (!empty($npc['biography'])) {
                $bio = substr($npc['biography'], 0, 200);
                $summary .= "- **Fact**: " . $bio . (strlen($npc['biography']) > 200 ? '...' : '') . "\n";
            }
            if (!empty($abilities)) {
                $summary .= "- **Fact**: Abilities: {$abilities}\n";
            }
            if (!empty($disciplines)) {
                $summary .= "- **Fact**: Disciplines: {$disciplines}\n";
            }
            $summary .= "- **Interpretation**: " . ($npc['notes'] ?? 'No interpretation available') . "\n\n";
        }
    }
    
    // Primogen
    if (!empty($primogen)) {
        $summary .= "### Clan Primogen\n\n";
        foreach ($primogen as $npc) {
            $summary .= "#### **{$npc['character_name']}** ({$npc['clan']}, Generation {$npc['generation']})\n";
            $summary .= "- **Fact** (from database): " . ($npc['concept'] ?? 'No concept recorded') . "\n";
            if (!empty($npc['biography'])) {
                $bio = substr($npc['biography'], 0, 200);
                $summary .= "- **Fact**: " . $bio . (strlen($npc['biography']) > 200 ? '...' : '') . "\n";
            }
            $summary .= "\n";
        }
    }
    
    // Other Notable Characters (first 20)
    if (!empty($otherNotable)) {
        $summary .= "### Other Notable Characters\n\n";
        $count = 0;
        foreach ($otherNotable as $npc) {
            if ($count >= 20) break;
            $summary .= "#### **{$npc['character_name']}** ({$npc['clan']}, Generation {$npc['generation']})\n";
            $summary .= "- **Fact** (from database): " . ($npc['concept'] ?? 'No concept recorded') . "\n";
            if (!empty($npc['sire'])) {
                $summary .= "- **Fact**: Sire: {$npc['sire']}\n";
            }
            $summary .= "\n";
            $count++;
        }
    }
    
    // Generation Analysis
    $summary .= "---\n\n";
    $summary .= "## Generation Analysis\n\n";
    $summary .= "### **Fact** (from database)\n\n";
    
    foreach ($generationCounts as $gen => $count) {
        $summary .= "- **Generation {$gen}**: {$count} character" . ($count > 1 ? 's' : '') . "\n";
    }
    
    $summary .= "\n### **Interpretation**\n\n";
    $summary .= "The distribution of generations indicates the power structure and age demographics of Phoenix's Kindred population.\n\n";
    
    // Sources
    $summary .= "---\n\n";
    $summary .= "## Sources Cited\n\n";
    $summary .= "- MySQL Database (canonical source)\n";
    $summary .= "- `database/export_npcs.php` - Export script\n";
    $summary .= "- `reference/Characters/character.json` - Schema reference\n\n";
    
    $summary .= "---\n\n";
    $summary .= "**Note on Data Quality**: All character data is sourced directly from the MySQL database, which is the canonical source of truth. This summary reflects the current state of NPCs in the database.\n";
    
    return $summary;
}

// Generate and write summary
try {
    $summary = generateSummary($conn);
    
    // Write to file
    if (file_put_contents($output_file, $summary) === false) {
        throw new Exception("Failed to write summary file: $output_file");
    }
    
    echo "Character summary generated successfully!\n";
    echo "Output file: $output_file\n";
    echo "Summary length: " . strlen($summary) . " characters\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

mysqli_close($conn);

