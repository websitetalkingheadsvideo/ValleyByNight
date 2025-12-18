<?php
/**
 * Find similar ritual names in rituals_master for unmatched character_rituals
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get unmatched rows
$unmatched = mysqli_query($conn, "
    SELECT id, character_id, ritual_name, ritual_type, level, is_custom
    FROM character_rituals
    WHERE ritual_id IS NULL
    ORDER BY ritual_name
");

$unmatched_rows = [];
while ($row = mysqli_fetch_assoc($unmatched)) {
    $unmatched_rows[] = $row;
}

echo "=== Searching for Similar Names in rituals_master ===\n\n";

foreach ($unmatched_rows as $cr) {
    $name = trim($cr['ritual_name']);
    $type = trim($cr['ritual_type']);
    
    echo "Character Ritual: '{$name}' (Type: {$type}, Level: {$cr['level']})\n";
    
    // Try various search patterns
    $patterns = [
        "%{$name}%",
        "%" . str_replace(' ', '%', $name) . "%",
        "%" . preg_replace('/\s+/', '%', $name) . "%"
    ];
    
    // Extract key words
    $words = preg_split('/\s+/', $name);
    $key_words = array_filter($words, function($w) {
        return strlen($w) > 3 && !in_array(strtolower($w), ['the', 'of', 'vs', 'and', 'with']);
    });
    
    if (!empty($key_words)) {
        $key_word_pattern = "%" . implode("%", $key_words) . "%";
        $patterns[] = $key_word_pattern;
    }
    
    $all_matches = [];
    foreach ($patterns as $pattern) {
        $query = "
            SELECT id, name, type, level
            FROM rituals_master
            WHERE LOWER(name) LIKE LOWER(?)
            AND LOWER(TRIM(type)) = LOWER(?)
            ORDER BY 
                CASE 
                    WHEN LOWER(name) = LOWER(?) THEN 1
                    WHEN LOWER(name) LIKE LOWER(?) THEN 2
                    ELSE 3
                END,
                id
            LIMIT 5
        ";
        $stmt = mysqli_prepare($conn, $query);
        $exact_lower = strtolower($name);
        $pattern_lower = strtolower($pattern);
        mysqli_stmt_bind_param($stmt, 'ssss', $pattern, $type, $exact_lower, $pattern_lower);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $all_matches[$row['id']] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($all_matches)) {
        echo "  Potential matches found:\n";
        foreach ($all_matches as $match) {
            $similarity = similar_text(strtolower($name), strtolower($match['name']), $percent);
            echo "    - ID: {$match['id']}, Name: '{$match['name']}', Level: {$match['level']}, Similarity: " . round($percent, 1) . "%\n";
        }
    } else {
        echo "  ✗ No similar matches found\n";
    }
    
    echo "\n";
}

mysqli_close($conn);
?>

