<?php
/**
 * Update character from JSON file
 * 
 * Usage: php tmp/update_character_from_json.php "To-Do Lists/characters/Rembrandt_Jones_42.json"
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Get JSON file path from command line argument
if ($argc < 2) {
    die("Usage: php tmp/update_character_from_json.php <json_file_path>\n");
}

$json_file = $argv[1];

// Resolve absolute path
if (!file_exists($json_file)) {
    // Try relative to project root
    $project_root = dirname(__DIR__);
    $json_file = $project_root . '/' . $json_file;
    if (!file_exists($json_file)) {
        die("Error: JSON file not found: {$argv[1]}\n");
    }
}

// Read and parse JSON
$json_content = file_get_contents($json_file);
if ($json_content === false) {
    die("Error: Failed to read JSON file\n");
}

$character_data = json_decode($json_content, true);
if ($character_data === null) {
    die("Error: Invalid JSON: " . json_last_error_msg() . "\n");
}

// Extract character ID
$character_id = $character_data['id'] ?? 0;
if ($character_id <= 0) {
    die("Error: Invalid or missing character ID\n");
}

// Database connection
require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() . "\n");
}

// Extract fields to update
$appearance = trim($character_data['appearance'] ?? '');
$biography = trim($character_data['biography'] ?? '');
$notes = trim($character_data['notes'] ?? '');
$character_image = trim($character_data['character_image'] ?? '');
$character_name = trim($character_data['character_name'] ?? '');
$player_name = trim($character_data['player_name'] ?? '');
$chronicle = trim($character_data['chronicle'] ?? '');
$nature = trim($character_data['nature'] ?? '');
$demeanor = trim($character_data['demeanor'] ?? '');
$concept = trim($character_data['concept'] ?? '');
$clan = trim($character_data['clan'] ?? '');
$generation = (int)($character_data['generation'] ?? 13);
$sire = $character_data['sire'] ?? null;
if ($sire !== null) {
    $sire = trim($sire);
    if ($sire === '') {
        $sire = null;
    }
}
$pc = (int)($character_data['pc'] ?? 0);
$custom_data = $character_data['custom_data'] ?? null;
if ($custom_data !== null) {
    if (is_array($custom_data) || is_object($custom_data)) {
        $custom_data = json_encode($custom_data);
    } else {
        $custom_data = trim((string)$custom_data);
        if ($custom_data === '') {
            $custom_data = null;
        }
    }
}
// Handle status field - can be string or object
if (is_array($character_data['status'] ?? null)) {
    $status = trim($character_data['status']['current_state'] ?? 'active');
} else {
    $status = trim($character_data['status'] ?? 'active');
}
$camarilla_status = trim($character_data['camarilla_status'] ?? 'Unknown');

// Validate status
$validStates = ['active', 'inactive', 'archived'];
if (!in_array(strtolower($status), $validStates, true)) {
    $status = 'active';
}

// Validate camarilla_status
$validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
$camarillaValue = ucfirst(strtolower($camarilla_status));
if (!in_array($camarillaValue, $validCamarilla, true)) {
    $camarillaValue = 'Unknown';
}
$camarilla_status = $camarillaValue;

// Build update query - matching save_character.php pattern
$update_sql = "UPDATE characters SET character_name = ?, player_name = ?, chronicle = ?, nature = ?, demeanor = ?, concept = ?, clan = ?, generation = ?, sire = ?, pc = ?, appearance = ?, biography = ?, notes = ?, custom_data = ?, status = ?, camarilla_status = ?" .
             ($character_image !== '' ? ", character_image = ?" : "") .
             ", updated_at = NOW() WHERE id = ?";

$stmt = mysqli_prepare($conn, $update_sql);
if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn) . "\n");
}

// Bind parameters - matching save_character.php exactly
if ($character_image !== '') {
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssisisssssssi',
        $character_name,
        $player_name,
        $chronicle,
        $nature,
        $demeanor,
        $concept,
        $clan,
        $generation,
        $sire,
        $pc,
        $appearance,
        $biography,
        $notes,
        $custom_data,
        $status,
        $camarilla_status,
        $character_image,
        $character_id
    );
} else {
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssisissssssi',
        $character_name,
        $player_name,
        $chronicle,
        $nature,
        $demeanor,
        $concept,
        $clan,
        $generation,
        $sire,
        $pc,
        $appearance,
        $biography,
        $notes,
        $custom_data,
        $status,
        $camarilla_status,
        $character_id
    );
}

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    if ($affected_rows > 0) {
        echo "Successfully updated character ID {$character_id} ({$character_name})\n";
        echo "Affected rows: {$affected_rows}\n";
    } else {
        echo "No rows updated. Character ID {$character_id} may not exist or data is unchanged.\n";
    }
} else {
    die("Error executing update: " . mysqli_stmt_error($stmt) . "\n");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "Update complete.\n";

