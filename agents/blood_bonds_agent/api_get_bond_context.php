<?php
declare(strict_types=1);

/**
 * API: Get blood bond context for Dialogue Agent / narrative systems.
 *
 * GET ?drinker_id=X&source_id=Y  — bond between drinker and source
 * GET ?character_id=X            — all bonds where X is drinker
 *
 * Requires admin or storyteller role.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/supabase_client.php';
require_once __DIR__ . '/../../includes/verify_role.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? 0;
$role    = verifyUserRole($conn, $user_id);
$allowed = ($role === 'admin' || $role === 'storyteller');

if (!$user_id || !$allowed) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/src/BondContextBuilder.php';

$builder = new BondContextBuilder(null);

$drinkerId  = isset($_GET['drinker_id']) ? (int) $_GET['drinker_id'] : 0;
$sourceId   = isset($_GET['source_id']) ? (int) $_GET['source_id'] : 0;
$characterId = isset($_GET['character_id']) ? (int) $_GET['character_id'] : 0;

if ($drinkerId > 0 && $sourceId > 0) {
    $ctx = $builder->buildPairContext($drinkerId, $sourceId);
    if ($ctx) {
        echo json_encode(['success' => true, 'data' => $ctx], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Bond context not found'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($characterId > 0) {
    $ctx = $builder->buildCharacterBondsAsDrinker($characterId);
    echo json_encode(['success' => true, 'data' => $ctx], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Provide drinker_id+source_id or character_id'], JSON_UNESCAPED_UNICODE);
exit;
