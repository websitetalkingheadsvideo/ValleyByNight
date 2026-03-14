<?php
declare(strict_types=1);

/**
 * API: Get system-wide blood bond diagnostics.
 *
 * Returns orphaned records, invalid creature pairs, unusual patterns.
 * Read-only; never alters data.
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
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

require_once __DIR__ . '/src/BondContextBuilder.php';

$builder     = new BondContextBuilder(null);
$diagnostics = $builder->buildDiagnostics();

echo json_encode($diagnostics, JSON_THROW_ON_ERROR);
