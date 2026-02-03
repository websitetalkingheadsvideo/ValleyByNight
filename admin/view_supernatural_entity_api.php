<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
require_once __DIR__ . '/../includes/connect.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }
$char = db_fetch_one($conn, "SELECT * FROM supernatural_entities WHERE id = ?", 'i', [$id]);
if (!$char) { echo json_encode(['success' => false, 'message' => 'Not found']); exit; }
$jsonFields = ['attributes', 'abilities', 'powers', 'power_pool', 'backgrounds', 'backgroundDetails', 'special_abilities', 'immunities', 'vulnerabilities', 'health_levels', 'merits_flaws', 'relationships', 'custom_data'];
foreach ($jsonFields as $f) {
    if (isset($char[$f]) && $char[$f] !== null && $char[$f] !== '') { $d = json_decode($char[$f], true); if (json_last_error() === JSON_ERROR_NONE) $char[$f] = $d; } else $char[$f] = null;
}
echo json_encode(['success' => true, 'character' => $char], JSON_PRETTY_PRINT);
mysqli_close($conn);
