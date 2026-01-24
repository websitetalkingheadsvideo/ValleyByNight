<?php
/**
 * Remove Optional abilities from a character
 * 
 * Usage: php tools/repeatable/remove_optional_abilities.php --character="Dorikhan Caine"
 *        php tools/repeatable/remove_optional_abilities.php --character-id=123
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
    $args = [];
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

// Delete Optional abilities and NULL/empty category abilities
$delete_sql = "DELETE FROM character_abilities WHERE character_id = ? AND (ability_category = 'Optional' OR ability_category IS NULL OR ability_category = '')";
$delete_stmt = mysqli_prepare($conn, $delete_sql);

if (!$delete_stmt) {
    die("Error: Failed to prepare delete statement: " . mysqli_error($conn) . "\n");
}

mysqli_stmt_bind_param($delete_stmt, 'i', $character_id);
mysqli_stmt_execute($delete_stmt);
$deleted_count = mysqli_stmt_affected_rows($delete_stmt);
mysqli_stmt_close($delete_stmt);

echo "Successfully removed {$deleted_count} Optional/NULL/empty category ability(ies) for character ID {$character_id}\n";

mysqli_close($conn);
?>
