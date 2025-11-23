<?php
/**
 * Character Agent Configuration Viewer
 * Displays agent configuration settings
 */
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

require_once __DIR__ . '/../../../includes/version.php';
$extra_css = ['css/admin-agents.css'];
require_once __DIR__ . '/../../../includes/header.php';

$config_file = __DIR__ . '/settings.json';
$config_exists = file_exists($config_file);
$config_data = null;
$config_error = null;

if ($config_exists) {
    $config_content = file_get_contents($config_file);
    if ($config_content !== false) {
        $config_data = json_decode($config_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $config_error = 'Invalid JSON: ' . json_last_error_msg();
        }
    } else {
        $config_error = 'Unable to read config file';
    }
}
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h1 class="display-5 text-light fw-bold mb-1">⚙️ Character Agent Configuration</h1>
        <p class="lead fst-italic mb-0">View and manage Character Agent settings</p>
    </div>

    <div class="card bg-dark border-danger mb-4">
        <div class="card-body">
            <?php if (!$config_exists): ?>
                <div class="alert alert-warning mb-0">
                    <h5 class="alert-heading">Configuration File Not Found</h5>
                    <p class="mb-0">The configuration file <code>settings.json</code> does not exist yet. The agent will use default settings.</p>
                </div>
            <?php elseif ($config_error): ?>
                <div class="alert alert-danger mb-0">
                    <h5 class="alert-heading">Configuration Error</h5>
                    <p class="mb-0"><?= htmlspecialchars($config_error); ?></p>
                </div>
            <?php else: ?>
                <h3 class="text-light mb-3">Current Configuration</h3>
                <pre class="bg-dark border border-danger rounded p-3 text-light" style="max-height: 600px; overflow-y: auto;"><code><?= htmlspecialchars(json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
            <?php endif; ?>
        </div>
    </div>

    <div class="card bg-dark border-danger">
        <div class="card-body">
            <h3 class="text-light mb-3">Configuration Information</h3>
            <ul class="text-light mb-0">
                <li><strong>Config File Path:</strong> <code><?= htmlspecialchars($config_file); ?></code></li>
                <li><strong>File Status:</strong> <?= $config_exists ? '<span class="text-success">Exists</span>' : '<span class="text-warning">Not Found</span>'; ?></li>
                <?php if ($config_exists): ?>
                    <li><strong>Last Modified:</strong> <?= date('Y-m-d H:i:s', filemtime($config_file)); ?></li>
                    <li><strong>File Size:</strong> <?= number_format(filesize($config_file)) . ' bytes'; ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="mt-4">
        <a href="/admin/agents.php" class="btn btn-outline-danger">← Back to Agents</a>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

