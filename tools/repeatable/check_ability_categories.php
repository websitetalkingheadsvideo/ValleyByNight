<?php
/**
 * Check ability categories for a character
 * 
 * Usage: php tools/repeatable/check_ability_categories.php --character="Dorikhan Caine"
 *        php tools/repeatable/check_ability_categories.php --character-id=188
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Parse command line arguments
$character_name = null;
$character_id = null;

if (php_sapi_name() === 'cli') {
    // CLI mode
    foreach ($argv as $arg) {
        if (strpos($arg, '--character=') === 0) {
            $character_name = substr($arg, 12);
        } elseif (strpos($arg, '--character-id=') === 0) {
            $character_id = (int)substr($arg, 15);
        }
    }
} else {
    // Web mode
    $character_name = $_GET['character'] ?? null;
    $character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : null;
}

if (!$character_name && !$character_id) {
    die("Error: Must provide either --character=\"Name\" or --character-id=123\n");
}

// Find character ID if name provided
if ($character_name && !$character_id) {
    $find_sql = "SELECT id FROM characters WHERE character_name = ? LIMIT 1";
    $find_stmt = mysqli_prepare($conn, $find_sql);
    if ($find_stmt) {
        mysqli_stmt_bind_param($find_stmt, 's', $character_name);
        mysqli_stmt_execute($find_stmt);
        $result = mysqli_stmt_get_result($find_stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $character_id = (int)$row['id'];
        } else {
            die("Error: Character '{$character_name}' not found in database\n");
        }
        mysqli_stmt_close($find_stmt);
    } else {
        die("Error: Failed to prepare query: " . mysqli_error($conn) . "\n");
    }
}

// Check if ability_category column exists
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$col_check = db_fetch_all($conn, $check_column_sql);
$has_category_column = !empty($col_check);

if (!$has_category_column) {
    die("Error: ability_category column does not exist in character_abilities table\n");
}

// Get all abilities for this character
$query_sql = "SELECT ability_name, ability_category, level, specialization FROM character_abilities WHERE character_id = ? ORDER BY ability_category, ability_name";
$query_stmt = mysqli_prepare($conn, $query_sql);

if (!$query_stmt) {
    die("Error: Failed to prepare query: " . mysqli_error($conn) . "\n");
}

mysqli_stmt_bind_param($query_stmt, 'i', $character_id);
mysqli_stmt_execute($query_stmt);
$result = mysqli_stmt_get_result($query_stmt);

$abilities_by_category = [];
$total_count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $category = $row['ability_category'] ?? '(NULL/empty)';
    if (!isset($abilities_by_category[$category])) {
        $abilities_by_category[$category] = [];
    }
    $abilities_by_category[$category][] = $row;
    $total_count++;
}

mysqli_stmt_close($query_stmt);

echo "Character ID {$character_id} has {$total_count} total abilities:\n\n";

foreach ($abilities_by_category as $category => $abilities) {
    echo "Category: {$category} (" . count($abilities) . " abilities)\n";
    foreach ($abilities as $ability) {
        $spec = !empty($ability['specialization']) ? " ({$ability['specialization']})" : '';
        echo "  - {$ability['ability_name']} {$ability['level']}{$spec}\n";
    }
    echo "\n";
}

mysqli_close($conn);
?>
