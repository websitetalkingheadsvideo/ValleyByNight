<?php
/**
 * View Werewolf (Garou) Character API
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$character_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit;
}

$rows = supabase_table_get('werewolf_characters', ['select' => '*', 'id' => 'eq.' . $character_id, 'limit' => '1']);
$char = !empty($rows) ? $rows[0] : null;
if (!$char) {
    echo json_encode(['success' => false, 'message' => 'Character not found']);
    exit;
}

$jsonFields = ['attributes', 'abilities', 'forms', 'gifts', 'rites', 'renown', 'backgrounds', 'backgroundDetails', 'merits_flaws', 'touchstones', 'harano_hauglosk', 'health_levels', 'relationships', 'custom_data'];
foreach ($jsonFields as $f) {
    if (isset($char[$f]) && $char[$f] !== null && $char[$f] !== '') {
        $dec = json_decode($char[$f], true);
        if (json_last_error() === JSON_ERROR_NONE) $char[$f] = $dec;
    } else {
        $char[$f] = null;
    }
}

$response = ['success' => true, 'character' => $char];
echo json_encode($response, JSON_PRETTY_PRINT);
