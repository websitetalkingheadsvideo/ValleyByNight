<?php
/**
 * Boon Agent Configuration Viewer
 * Displays agent configuration settings using modals
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

// Define configuration sections
$config_sections = [
    'agent' => ['icon' => '⚙️', 'title' => 'Agent Information', 'description' => 'Basic agent metadata and status'],
    'paths' => ['icon' => '📁', 'title' => 'Paths', 'description' => 'File and directory paths'],
    'validation' => ['icon' => '✅', 'title' => 'Validation Rules', 'description' => 'Boon validation settings'],
    'monitoring' => ['icon' => '👁️', 'title' => 'Monitoring', 'description' => 'Monitoring and detection settings'],
    'analysis' => ['icon' => '📊', 'title' => 'Analysis', 'description' => 'Analysis and insights configuration'],
    'harpy_integration' => ['icon' => '🎭', 'title' => 'Harpy Integration', 'description' => 'Harpy system integration settings'],
    'reporting' => ['icon' => '📋', 'title' => 'Reporting', 'description' => 'Report generation settings'],
    'logging' => ['icon' => '📝', 'title' => 'Logging', 'description' => 'Logging configuration'],
    'notifications' => ['icon' => '🔔', 'title' => 'Notifications', 'description' => 'Notification settings'],
    'advanced' => ['icon' => '⚡', 'title' => 'Advanced', 'description' => 'Advanced performance settings']
];
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h1 class="display-5 text-light fw-bold mb-1">⚙️ Boon Agent Configuration</h1>
        <p class="lead fst-italic mb-0">View and manage Boon Agent settings</p>
    </div>

    <?php if (!$config_exists): ?>
        <div class="alert alert-warning mb-4">
            <h5 class="alert-heading">Configuration File Not Found</h5>
            <p class="mb-0">The configuration file <code>settings.json</code> does not exist yet. The agent will use default settings.</p>
        </div>
    <?php elseif ($config_error): ?>
        <div class="alert alert-danger mb-4">
            <h5 class="alert-heading">Configuration Error</h5>
            <p class="mb-0"><?= htmlspecialchars($config_error); ?></p>
        </div>
    <?php else: ?>
        <!-- Configuration Sections Grid -->
        <div class="row g-3 mb-4">
            <?php foreach ($config_sections as $section_key => $section_info): ?>
                <?php if (isset($config_data[$section_key])): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card bg-dark border-danger h-100 config-section-card" 
                             style="cursor: pointer; transition: all 0.3s ease;"
                             onclick="viewConfigSection('<?= htmlspecialchars($section_key); ?>')"
                             onmouseover="this.style.transform='translateY(-5px)'; this.style.borderColor='#b30000';"
                             onmouseout="this.style.transform=''; this.style.borderColor='#8B0000';">
                            <div class="card-body text-center">
                                <div class="mb-2" style="font-size: 2.5em;"><?= $section_info['icon']; ?></div>
                                <h5 class="text-light mb-2"><?= htmlspecialchars($section_info['title']); ?></h5>
                                <p class="text-muted small mb-0"><?= htmlspecialchars($section_info['description']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Configuration Information Card -->
    <div class="card bg-dark border-danger mb-4">
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
        <a href="/admin/boon_agent_viewer.php" class="btn btn-outline-danger">💎 Boon Agent Dashboard</a>
    </div>
</div>

<!-- Configuration Section Modal -->
<div class="modal fade" id="configSectionModal" tabindex="-1" aria-labelledby="configSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-light" id="configSectionModalLabel">
                    <span id="configSectionIcon"></span>
                    <span id="configSectionTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="configSectionContent" class="json-display"></div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const configData = <?= json_encode($config_data ?? []); ?>;
const configSections = <?= json_encode($config_sections); ?>;

function viewConfigSection(sectionKey) {
    if (!configData[sectionKey]) {
        console.error('Section not found:', sectionKey);
        return;
    }

    const section = configSections[sectionKey];
    const sectionData = configData[sectionKey];

    // Update modal title
    document.getElementById('configSectionIcon').textContent = section.icon + ' ';
    document.getElementById('configSectionTitle').textContent = section.title;

    // Format and display JSON
    const formattedJson = JSON.stringify(sectionData, null, 2);
    document.getElementById('configSectionContent').textContent = formattedJson;

    // Show modal
    const modalEl = document.getElementById('configSectionModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true,
            keyboard: true
        });
        modalInstance.show();
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

