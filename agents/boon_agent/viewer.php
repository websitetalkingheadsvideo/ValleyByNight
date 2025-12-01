<?php
/**
 * Boon Agent Viewer
 * Main interface for the Boon Agent
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
            <h1 class="display-5 text-light fw-bold mb-1">💎 Boon Agent</h1>
            <p class="lead fst-italic mb-0">Monitor and validate boons according to Laws of the Night Revised mechanics</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">
            ← Back to Agents
        </a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-header bg-danger text-light">
                    <h3 class="mb-0">📊 Reports</h3>
                </div>
                <div class="card-body">
                    <p class="text-light mb-3">View generated boon agent reports:</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="reports/daily/" class="btn btn-outline-danger">📅 Daily Reports</a>
                        <a href="reports/validation/" class="btn btn-outline-danger">✅ Validation Reports</a>
                        <a href="reports/character/" class="btn btn-outline-danger">👤 Character Reports</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-header bg-danger text-light">
                    <h3 class="mb-0">⚙️ Configuration</h3>
                </div>
                <div class="card-body">
                    <p class="text-light mb-3">Manage Boon Agent settings:</p>
                    <a href="config/" class="btn btn-outline-danger">View Config</a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="card bg-dark border-danger">
            <div class="card-header bg-danger text-light">
                <h3 class="mb-0">🔗 Quick Links</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="../../admin/boon_ledger.php" class="btn btn-outline-danger">💎 Boon Ledger</a>
                    <a href="../../admin/agents.php" class="btn btn-outline-danger">👥 All Agents</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

