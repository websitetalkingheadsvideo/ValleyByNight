<?php
/**
 * Rumor Agent Index
 * Main interface for the Rumor Agent
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../includes/version.php';
$extra_css = ['css/admin-agents.css'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">📰 Rumor Agent</h1>
            <p class="lead fst-italic mb-0">Manage and monitor rumor-related interactions</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">
            ← Back to Agents
        </a>
    </div>

    <div class="card bg-dark border-danger">
        <div class="card-body">
            <p class="text-light mb-3">The Rumor Agent is integrated into the Rumor Viewer admin page.</p>
            <a href="../../admin/rumor_viewer.php" class="btn btn-outline-danger">Launch Rumor Viewer</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

