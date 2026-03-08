<?php
declare(strict_types=1);

/**
 * Blood Bonds Agent — Summary View
 *
 * Displays bond diagnostics and links to API. Requires admin or storyteller.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../includes/verify_role.php';

$user_id = $_SESSION['user_id'] ?? 0;
$role    = verifyUserRole($conn, $user_id);
$allowed = ($role === 'admin' || $role === 'storyteller');

if (!$user_id || !$allowed) {
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $segment_count = substr_count(trim($script_dir, '/'), '/') + 1;
    $path_prefix = str_repeat('../', $segment_count);
    header('Location: ' . $path_prefix . 'login.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container py-4">
    <h1>Blood Bonds Agent</h1>
    <p class="lead">Read-only bond context. Never enforces behavior.</p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">API Endpoints</h5>
            <ul class="mb-0">
                <li><code>api_get_bond_context.php?drinker_id=X&amp;source_id=Y</code> — Single pair</li>
                <li><code>api_get_bond_context.php?character_id=X</code> — All bonds for character</li>
                <li><code>api_get_diagnostics.php</code> — System diagnostics</li>
            </ul>
        </div>
    </div>

    <p><a href="<?php echo htmlspecialchars($path_prefix . 'admin/admin_panel.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Back to Admin</a></p>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
