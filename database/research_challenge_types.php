<?php
/**
 * Research Challenge Types from Rulebooks
 * 
 * Searches rulebooks database for challenge resolution mechanics to identify
 * valid challenge_type values and resolution patterns.
 * 
 * Usage:
 *   CLI: php database/research_challenge_types.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Search queries for challenge mechanics
$search_terms = [
    'contested challenge',
    'static challenge',
    'narrative challenge',
    'contest resolution',
    'difficulty number',
    'resisted challenge',
    'challenge type'
];

$results = [];

foreach ($search_terms as $term) {
    // Use LIKE for broader search if FULLTEXT doesn't work
    $query = "SELECT 
                r.id AS rulebook_id,
                r.title AS book_title,
                rp.page_number,
                SUBSTRING(rp.page_text, 
                    GREATEST(1, LOCATE(?, rp.page_text) - 200),
                    LEAST(800, LENGTH(rp.page_text) - GREATEST(1, LOCATE(?, rp.page_text) - 200))
                ) AS excerpt
              FROM rulebook_pages rp
              JOIN rulebooks r ON rp.rulebook_id = r.id
              WHERE rp.page_text LIKE ?
              LIMIT 10";
    
    $search_pattern = '%' . $term . '%';
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $term, $term, $search_pattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $term_results = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $term_results[] = $row;
        }
        
        if (!empty($term_results)) {
            $results[$term] = $term_results;
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Save results
$output_file = __DIR__ . '/../tmp/challenge_type_reference.md';
$tmp_dir = dirname($output_file);
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}

$markdown = "# Challenge Type Research from Rulebooks\n\n";
$markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$markdown .= "## Search Results\n\n";

if (empty($results)) {
    $markdown .= "No results found. This may indicate:\n";
    $markdown .= "- Challenge mechanics are described differently in the rulebooks\n";
    $markdown .= "- Terms need to be adjusted\n";
    $markdown .= "- Full-text search needs to be used instead\n\n";
} else {
    foreach ($results as $term => $term_results) {
        $markdown .= "### Search Term: \"$term\"\n\n";
        foreach ($term_results as $idx => $result) {
            $markdown .= "**Book:** {$result['book_title']} (Page {$result['page_number']})\n\n";
            $markdown .= "```\n" . trim($result['excerpt']) . "\n```\n\n";
            $markdown .= "---\n\n";
        }
    }
}

// Add standard challenge type definitions based on MET/LotN conventions
$markdown .= "## Standard Challenge Types (Laws of the Night Revised)\n\n";
$markdown .= "Based on MET system conventions:\n\n";
$markdown .= "### Contested Challenge\n";
$markdown .= "- Both parties make a challenge/roll\n";
$markdown .= "- Results are compared (higher wins, ties may have special rules)\n";
$markdown .= "- Example: Attack vs. Defense, Mental power vs. Willpower resistance\n\n";
$markdown .= "### Static Challenge\n";
$markdown .= "- One party rolls against a fixed difficulty number\n";
$markdown .= "- No opposed roll from target\n";
$markdown .= "- Example: Breaking down a door (difficulty 6), climbing a wall (difficulty 5)\n\n";
$markdown .= "### Narrative Challenge\n";
$markdown .= "- No dice rolls required\n";
$markdown .= "- Storyteller adjudicates based on circumstances, traits, and story needs\n";
$markdown .= "- Example: Information gathering through investigation, social maneuvering\n\n";

file_put_contents($output_file, $markdown);

if ($is_cli) {
    echo "Challenge Type Research Complete\n";
    echo "Results saved to: $output_file\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Challenge Research</title></head><body>";
    echo "<h1>Challenge Type Research</h1>";
    echo "<p>Results saved to: <code>" . htmlspecialchars($output_file) . "</code></p>";
    echo "<pre>" . htmlspecialchars($markdown) . "</pre>";
    echo "</body></html>";
}

mysqli_close($conn);
?>

