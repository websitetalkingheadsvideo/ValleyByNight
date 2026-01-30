<?php
/**
 * Compare abilities in reference/Books/abilities.json to abilities in the database.
 * Outputs abilities that exist in the JSON file but are not in the abilities table.
 *
 * Run: php tools/compare_abilities_json_to_db.php
 */

declare(strict_types=1);

$json_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'reference' . DIRECTORY_SEPARATOR . 'Books' . DIRECTORY_SEPARATOR . 'abilities.json';
if (!file_exists($json_path) || !is_readable($json_path)) {
    fwrite(STDERR, "Cannot read: {$json_path}\n");
    exit(1);
}

$raw = file_get_contents($json_path);
$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON: {$json_path}\n");
    exit(1);
}

$json_abilities = [];
foreach (['Talents', 'Skills', 'Knowledges'] as $category) {
    if (!isset($data[$category]) || !is_array($data[$category])) {
        continue;
    }
    foreach ($data[$category] as $entry) {
        if (isset($entry['ability']) && is_string($entry['ability'])) {
            $name = trim($entry['ability']);
            if ($name !== '') {
                $json_abilities[$name] = true;
            }
        }
    }
}
$json_abilities = array_keys($json_abilities);
sort($json_abilities);

require_once __DIR__ . '/../includes/connect.php';
if (!$conn) {
    fwrite(STDERR, "Database connection failed: " . mysqli_connect_error() . "\n");
    exit(1);
}

$result = mysqli_query($conn, "SELECT name FROM abilities");
if (!$result) {
    fwrite(STDERR, "Query failed: " . mysqli_error($conn) . "\n");
    exit(1);
}
$db_names = [];
while ($row = mysqli_fetch_assoc($result)) {
    $db_names[$row['name']] = true;
}
mysqli_free_result($result);
mysqli_close($conn);

$missing = [];
foreach ($json_abilities as $name) {
    if (!isset($db_names[$name])) {
        $missing[] = $name;
    }
}

$is_cli = (php_sapi_name() === 'cli');
if ($is_cli) {
    echo "Abilities in reference/Books/abilities.json that are NOT in the database (" . count($missing) . "):\n\n";
    foreach ($missing as $name) {
        echo "  " . $name . "\n";
    }
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Abilities in reference/Books/abilities.json that are NOT in the database (" . count($missing) . "):\n\n";
    foreach ($missing as $name) {
        echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "\n";
    }
}
