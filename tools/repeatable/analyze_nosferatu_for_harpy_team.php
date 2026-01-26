<?php
/**
 * Analyze Nosferatu Characters for Harpy Team
 * 
 * Examines all Nosferatu characters in the database and recommends
 * 2 candidates for the Harpy's team based on:
 * - Information gathering capabilities
 * - Social/political skills
 * - Clan loyalty and reliability
 * - Ability to work as "lesser harpies" (information gatherers)
 */

declare(strict_types=1);

// CLI-only tool
if (php_sapi_name() !== 'cli') {
    die("This tool must be run from the command line.\n");
}

require_once __DIR__ . '/../../includes/connect.php';

echo "Analyzing Nosferatu Characters for Harpy Team Candidates...\n";
echo str_repeat("=", 70) . "\n\n";

// Query all Nosferatu characters
// Use SELECT * to get all available columns, then check what exists
// Query by clan column directly (as used in api_get_characters.php)
$query = "SELECT c.*, c.clan as clan_name
FROM characters c
WHERE LOWER(COALESCE(c.clan, '')) = 'nosferatu'
  AND (c.player_name = 'NPC' OR c.pc = 0 OR c.player_name IS NULL)
ORDER BY c.character_name ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error querying database: " . mysqli_error($conn) . "\n");
}

$nosferatu = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nosferatu[] = $row;
}

echo "Found " . count($nosferatu) . " Nosferatu characters in database.\n\n";

if (count($nosferatu) === 0) {
    echo "No Nosferatu characters found. Exiting.\n";
    exit(0);
}

// Display all Nosferatu with details
echo "ALL NOSFERATU CHARACTERS:\n";
echo str_repeat("-", 70) . "\n";

foreach ($nosferatu as $index => $char) {
    echo "\n" . ($index + 1) . ". " . htmlspecialchars($char['character_name'] ?? 'Unknown') . "\n";
    echo "   ID: " . ($char['id'] ?? 'N/A') . "\n";
    echo "   Concept: " . htmlspecialchars($char['concept'] ?? 'N/A') . "\n";
    if (isset($char['role'])) {
        echo "   Role: " . htmlspecialchars($char['role']) . "\n";
    }
    if (isset($char['title'])) {
        echo "   Title: " . htmlspecialchars($char['title']) . "\n";
    }
    echo "   Generation: " . htmlspecialchars($char['generation'] ?? 'N/A') . "\n";
    echo "   Nature: " . htmlspecialchars($char['nature'] ?? 'N/A') . "\n";
    echo "   Demeanor: " . htmlspecialchars($char['demeanor'] ?? 'N/A') . "\n";
    echo "   Status: " . htmlspecialchars($char['status'] ?? 'active') . "\n";
    
    if (!empty($char['biography'])) {
        $bio_preview = substr(strip_tags($char['biography']), 0, 150);
        echo "   Biography: " . htmlspecialchars($bio_preview) . "...\n";
    }
}

// Analyze for Harpy team suitability
echo "\n\n" . str_repeat("=", 70) . "\n";
echo "HARPY TEAM SUITABILITY ANALYSIS\n";
echo str_repeat("=", 70) . "\n\n";

echo "Criteria for Harpy Team Members (Lesser Harpies):\n";
echo "- Information gathering capabilities (Nosferatu specialty)\n";
echo "- Social/political awareness\n";
echo "- Reliability and clan loyalty\n";
echo "- Ability to work discreetly\n";
echo "- Knowledge of domain politics\n";
echo "- Trustworthiness for sensitive information\n\n";

$candidates = [];

foreach ($nosferatu as $char) {
    $score = 0;
    $reasons = [];
    
    // Check for information-related roles/titles
    $info_keywords = ['information', 'broker', 'spy', 'informant', 'network', 'intelligence', 'gather', 'collect'];
    $role_lower = strtolower($char['role'] ?? '');
    $title_lower = strtolower($char['title'] ?? '');
    $concept_lower = strtolower($char['concept'] ?? '');
    $bio_lower = strtolower($char['biography'] ?? '');
    $name_lower = strtolower($char['character_name'] ?? '');
    
    // Score based on information gathering indicators
    foreach ($info_keywords as $keyword) {
        if (strpos($role_lower, $keyword) !== false || 
            strpos($title_lower, $keyword) !== false ||
            strpos($concept_lower, $keyword) !== false ||
            strpos($bio_lower, $keyword) !== false ||
            strpos($name_lower, $keyword) !== false) {
            $score += 3;
            $reasons[] = "Information-related role/concept";
            break;
        }
    }
    
    // Check for Primogen (high status, political knowledge)
    if (stripos($title_lower, 'primogen') !== false) {
        $score += 5;
        $reasons[] = "Primogen - high political standing";
    }
    
    // Check for social/political roles
    $social_keywords = ['social', 'political', 'diplomat', 'mediator', 'counselor'];
    foreach ($social_keywords as $keyword) {
        if (strpos($role_lower, $keyword) !== false || 
            strpos($title_lower, $keyword) !== false ||
            strpos($concept_lower, $keyword) !== false) {
            $score += 2;
            $reasons[] = "Social/political role";
            break;
        }
    }
    
    // Check generation (lower is better for influence)
    if (!empty($char['generation'])) {
        $gen = (int)$char['generation'];
        if ($gen >= 8 && $gen <= 12) {
            $score += 2;
            $reasons[] = "Appropriate generation for influence";
        } elseif ($gen < 8) {
            $score += 3;
            $reasons[] = "Low generation - significant power";
        }
    }
    
    // Check for active status
    if (($char['status'] ?? 'active') === 'active') {
        $score += 1;
        $reasons[] = "Active character";
    }
    
    // Check biography for relevant traits
    if (!empty($char['biography'])) {
        $bio = strtolower($char['biography']);
        if (strpos($bio, 'trust') !== false || strpos($bio, 'loyal') !== false) {
            $score += 1;
            $reasons[] = "Trustworthy/loyal traits mentioned";
        }
        if (strpos($bio, 'network') !== false || strpos($bio, 'contact') !== false) {
            $score += 2;
            $reasons[] = "Network/contacts mentioned";
        }
    }
    
    // Special mention for Alistaire (known information broker)
    if (stripos($char['character_name'], 'alistaire') !== false) {
        $score += 10;
        $reasons[] = "KNOWN: Information broker (from pre-game primer)";
    }
    
    $candidates[] = [
        'character' => $char,
        'score' => $score,
        'reasons' => $reasons
    ];
}

// Sort by score (highest first)
usort($candidates, function($a, $b) {
    return $b['score'] - $a['score'];
});

echo "CANDIDATE RANKINGS:\n";
echo str_repeat("-", 70) . "\n\n";

foreach ($candidates as $index => $candidate) {
    $char = $candidate['character'];
    echo ($index + 1) . ". " . htmlspecialchars($char['character_name'] ?? 'Unknown') . 
         " (Score: " . $candidate['score'] . ")\n";
    echo "   Reasons: " . implode(", ", $candidate['reasons']) . "\n";
    echo "   Concept: " . htmlspecialchars($char['concept'] ?? 'N/A') . "\n";
    $role_title = [];
    if (!empty($char['role'])) $role_title[] = $char['role'];
    if (!empty($char['title'])) $role_title[] = $char['title'];
    echo "   Role/Title: " . htmlspecialchars(implode(' / ', $role_title) ?: 'N/A') . "\n\n";
}

// Top 2 recommendations
echo "\n" . str_repeat("=", 70) . "\n";
echo "TOP 2 RECOMMENDATIONS FOR HARPY'S TEAM\n";
echo str_repeat("=", 70) . "\n\n";

$top2 = array_slice($candidates, 0, 2);

foreach ($top2 as $index => $candidate) {
    $char = $candidate['character'];
    echo "\nRECOMMENDATION #" . ($index + 1) . ":\n";
    echo str_repeat("-", 70) . "\n";
    echo "Name: " . htmlspecialchars($char['character_name'] ?? 'Unknown') . "\n";
    echo "ID: " . ($char['id'] ?? 'N/A') . "\n";
    echo "Score: " . $candidate['score'] . "\n";
    echo "\nQualification Summary:\n";
    foreach ($candidate['reasons'] as $reason) {
        echo "  • " . $reason . "\n";
    }
    echo "\nDetails:\n";
    echo "  Concept: " . htmlspecialchars($char['concept'] ?? 'N/A') . "\n";
    if (isset($char['role'])) {
        echo "  Role: " . htmlspecialchars($char['role']) . "\n";
    }
    if (isset($char['title'])) {
        echo "  Title: " . htmlspecialchars($char['title']) . "\n";
    }
    echo "  Generation: " . htmlspecialchars($char['generation'] ?? 'N/A') . "\n";
    echo "  Nature: " . htmlspecialchars($char['nature'] ?? 'N/A') . "\n";
    echo "  Demeanor: " . htmlspecialchars($char['demeanor'] ?? 'N/A') . "\n";
    
    if (!empty($char['biography'])) {
        echo "\n  Biography:\n";
        $bio_lines = explode("\n", wordwrap(strip_tags($char['biography']), 65));
        foreach ($bio_lines as $line) {
            echo "    " . htmlspecialchars(trim($line)) . "\n";
        }
    }
}

echo "\n\n" . str_repeat("=", 70) . "\n";
echo "ANALYSIS COMPLETE\n";
echo str_repeat("=", 70) . "\n";

mysqli_close($conn);
?>
