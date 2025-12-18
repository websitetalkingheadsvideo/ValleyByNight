<?php
/**
 * Generate Complete Paths Update Data
 * 
 * Creates comprehensive update data structure with path descriptions,
 * power system_text, challenge_type, and challenge_notes based on
 * Laws of the Night Revised mechanics and extracted rulebook content.
 * 
 * Usage:
 *   CLI: php database/generate_paths_update_data.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Load baseline audit
$baseline_file = __DIR__ . '/../tmp/paths_audit_baseline.json';
$baseline = json_decode(file_get_contents($baseline_file), true);

// Thaumaturgy path descriptions (based on path names and known mechanics)
$thaumaturgy_descriptions = [
    'The Path of Blood' => 'The foundational path of Thaumaturgy, focusing on manipulating vitae (blood) and its properties. This path allows Tremere to control, enhance, and weaponize blood itself.',
    'Movement of the Mind' => 'A path of telekinetic manipulation, allowing the thaumaturge to move objects, create force effects, and manipulate the physical world through mental power.',
    'The Lure of Flames' => 'A destructive path specializing in fire manipulation, allowing the practitioner to create, control, and weaponize flames against enemies.',
    'Path of the Focused Mind' => 'Enhances mental clarity, focus, and cognitive abilities. This path allows the thaumaturge to improve their concentration, resist mental attacks, and process information more efficiently.',
    'Weather Control' => 'Manipulates atmospheric conditions, allowing the thaumaturge to create fog, rain, wind, storms, and even lightning. Particularly useful for battlefield control and dramatic effects.',
    'Mastery of the Mortal Shell' => 'Focuses on controlling and manipulating the bodies of mortals (and sometimes vampires). This path allows the thaumaturge to cause physical malfunctions, seizures, and even complete bodily control.',
    'Neptune\'s Might' => 'Manipulates water in all its forms. Practitioners can see through water, control its flow, transform blood to water, and create watery barriers or dehydration effects.',
    'Path of Technomancy' => 'Allows manipulation and control of electronic devices and technology. Practitioners can cause equipment failures, remotely control devices, encrypt/decrypt data, and even telecommute their consciousness into machines.',
    'Spirit Manipulation' => 'Interacts with and controls spirits from the spirit world. Allows the thaumaturge to see spirits, communicate with them, command them, trap them, and even merge with them.',
    'Path of Conjuring' => 'The art of creating objects from nothing or transforming existing materials. Practitioners can summon simple objects, make them permanent, forge magical items, reverse conjurations, and even affect life itself.'
];

// Get all paths and powers from database
$paths_query = "SELECT id, name, type FROM paths_master WHERE type IN ('Necromancy', 'Thaumaturgy') ORDER BY type, name";
$paths_result = mysqli_query($conn, $paths_query);

$paths_data = [];
while ($row = mysqli_fetch_assoc($paths_result)) {
    $paths_data[$row['id']] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'type' => $row['type']
    ];
}
mysqli_free_result($paths_result);

$powers_query = "SELECT pp.id, pp.path_id, pp.level, pp.power_name, pm.name as path_name, pm.type as path_type
                 FROM path_powers pp
                 INNER JOIN paths_master pm ON pp.path_id = pm.id
                 WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
                 ORDER BY pm.type, pm.name, pp.level";
$powers_result = mysqli_query($conn, $powers_query);

$update_data = [
    'paths_master_updates' => [],
    'path_powers_updates' => [],
    'timestamp' => date('Y-m-d H:i:s'),
    'notes' => 'Generated update data. Power system_text, challenge_type, and challenge_notes should be populated from rulebooks or manual research.'
];

// Collect paths needing descriptions (Thaumaturgy only)
while ($row = mysqli_fetch_assoc($paths_result)) {
    $path_id = (int)$row['id'];
    $path_name = $row['name'];
    $path_type = $row['type'];
    
    if ($path_type === 'Thaumaturgy' && isset($thaumaturgy_descriptions[$path_name])) {
        $update_data['paths_master_updates'][] = [
            'id' => $path_id,
            'name' => $path_name,
            'description' => $thaumaturgy_descriptions[$path_name]
        ];
    }
}
mysqli_free_result($paths_result);

// Collect powers needing updates
while ($row = mysqli_fetch_assoc($powers_result)) {
    $update_data['path_powers_updates'][] = [
        'id' => (int)$row['id'],
        'path_id' => (int)$row['path_id'],
        'power_name' => $row['power_name'],
        'path_name' => $row['path_name'],
        'path_type' => $row['path_type'],
        'level' => (int)$row['level'],
        'system_text' => null, // To be filled from rulebooks
        'challenge_type' => null, // To be determined: 'contested', 'static', or 'narrative'
        'challenge_notes' => null // To be written based on mechanics
    ];
}
mysqli_free_result($powers_result);

// Save update data structure
$output_file = __DIR__ . '/../tmp/paths_complete_update_data.json';
$tmp_dir = dirname($output_file);
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}

file_put_contents($output_file, json_encode($update_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($is_cli) {
    echo "Update Data Structure Generated\n";
    echo "Path descriptions to update: " . count($update_data['paths_master_updates']) . "\n";
    echo "Powers needing updates: " . count($update_data['path_powers_updates']) . "\n";
    echo "Data saved to: $output_file\n";
    echo "\nNote: Power system_text, challenge_type, and challenge_notes still need to be populated.\n";
} else {
    echo "<!DOCTYPE html><html><head><title>Update Data</title></head><body>";
    echo "<h1>Update Data Generated</h1>";
    echo "<p>Data saved to: <code>" . htmlspecialchars($output_file) . "</code></p>";
    echo "</body></html>";
}

mysqli_close($conn);
?>

