<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
require_once __DIR__ . '/../includes/connect.php';
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['character_id']) ? (int)$input['character_id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }
$stmt = mysqli_prepare($conn, "DELETE FROM mortal_characters WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);
echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => mysqli_error($conn)]);
