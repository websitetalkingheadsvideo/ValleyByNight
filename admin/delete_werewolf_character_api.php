<?php
/**
 * Delete Werewolf (Garou) Character API
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$character_id = isset($input['character_id']) ? (int)$input['character_id'] : 0;
if ($character_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid character ID']);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM werewolf_characters WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $character_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
echo json_encode(['success' => true]);
