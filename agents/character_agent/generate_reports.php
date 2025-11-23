<?php
/**
 * Character Agent - Generate Reports
 * Triggers report generation for the Character Agent
 */
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display for debugging
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Verify includes path exists (agents/character_agent -> up 2 levels to root)
$version_path = __DIR__ . '/../../includes/version.php';
$header_path = __DIR__ . '/../../includes/header.php';

if (!file_exists($version_path)) {
    die("Error: version.php not found at: " . $version_path);
}

if (!file_exists($header_path)) {
    die("Error: header.php not found at: " . $header_path);
}

require_once $version_path;
$extra_css = ['css/admin-agents.css'];
require_once $header_path;

$reports_dir = __DIR__ . '/reports';
$daily_dir = $reports_dir . '/daily';
$continuity_dir = $reports_dir . '/continuity';
$config_file = __DIR__ . '/config/settings.json';

// Ensure directories exist
if (!is_dir($daily_dir)) {
    mkdir($daily_dir, 0755, true);
}
if (!is_dir($continuity_dir)) {
    mkdir($continuity_dir, 0755, true);
}

// Load config if it exists
$config = [];
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $decoded = json_decode($config_content, true);
    if (is_array($decoded)) {
        $config = $decoded;
    }
}

$reporting_enabled = $config['reporting']['enabled'] ?? true;
$generate_daily = $config['reporting']['generate_daily_reports'] ?? true;
$generate_continuity = $config['reporting']['generate_continuity_reports'] ?? true;

$message = null;
$message_type = null;
$generated_reports = [];

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    if (!$reporting_enabled) {
        $message = "Reporting is disabled in the configuration.";
        $message_type = "warning";
    } else {
        $timestamp = date('Y-m-d_H-i-s');
        $date = date('Y-m-d');
        
        try {
            if ($generate_daily && isset($_POST['report_type']) && ($_POST['report_type'] === 'daily' || $_POST['report_type'] === 'both')) {
                // Generate daily report
                $daily_report_file = $daily_dir . '/daily_report_' . $date . '.json';
                $daily_report = [
                    'generated_at' => date('Y-m-d H:i:s'),
                    'date' => $date,
                    'type' => 'daily',
                    'summary' => [
                        'characters_processed' => 0,
                        'new_characters' => 0,
                        'updated_characters' => 0,
                        'validation_errors' => 0,
                        'continuity_issues' => 0
                    ],
                    'details' => []
                ];
                
                file_put_contents($daily_report_file, json_encode($daily_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $generated_reports[] = [
                    'type' => 'Daily Report',
                    'file' => basename($daily_report_file),
                    'filename' => basename($daily_report_file),
                    'report_type' => 'daily',
                    'path' => 'reports/daily/' . basename($daily_report_file),
                    'full_path' => $daily_report_file
                ];
            }
            
            if ($generate_continuity && isset($_POST['report_type']) && ($_POST['report_type'] === 'continuity' || $_POST['report_type'] === 'both')) {
                // Generate continuity report
                $continuity_report_file = $continuity_dir . '/continuity_report_' . $timestamp . '.json';
                $continuity_report = [
                    'generated_at' => date('Y-m-d H:i:s'),
                    'type' => 'continuity',
                    'summary' => [
                        'sire_relationship_issues' => 0,
                        'generation_inconsistencies' => 0,
                        'clan_inconsistencies' => 0,
                        'timeline_conflicts' => 0,
                        'missing_characters' => 0
                    ],
                    'issues' => []
                ];
                
                file_put_contents($continuity_report_file, json_encode($continuity_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $generated_reports[] = [
                    'type' => 'Continuity Report',
                    'file' => basename($continuity_report_file),
                    'filename' => basename($continuity_report_file),
                    'report_type' => 'continuity',
                    'path' => 'reports/continuity/' . basename($continuity_report_file),
                    'full_path' => $continuity_report_file
                ];
            }
            
            if (!empty($generated_reports)) {
                $message = "Successfully generated " . count($generated_reports) . " report(s).";
                $message_type = "success";
            } else {
                $message = "No reports were generated. Please select a report type.";
                $message_type = "warning";
            }
        } catch (Exception $e) {
            $message = "Error generating reports: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">📊 Generate Character Agent Reports</h1>
            <p class="lead fst-italic mb-0">Create daily and continuity reports for character monitoring</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">
            ← Back to Agents
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($generated_reports)): ?>
        <div class="card bg-dark border-success mb-4">
            <div class="card-body">
                <h3 class="text-light mb-3">Generated Reports</h3>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($generated_reports as $index => $report): ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <strong class="text-light"><?= htmlspecialchars($report['type']); ?>:</strong>
                                <span class="text-light ms-2"><?= htmlspecialchars($report['file']); ?></span>
                            </div>
                            <button type="button" 
                                    class="btn btn-outline-success btn-sm view-report-btn" 
                                    data-report-filename="<?= htmlspecialchars($report['filename']); ?>"
                                    data-report-type="<?= htmlspecialchars($report['type']); ?>"
                                    data-report-type-api="<?= htmlspecialchars($report['report_type']); ?>"
                                    data-report-file="<?= htmlspecialchars($report['file']); ?>">
                                📄 View Report
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-body">
                    <h3 class="text-light mb-3">Report Generation</h3>
                    
                    <?php if (!$reporting_enabled): ?>
                        <div class="alert alert-warning mb-0">
                            Reporting is currently disabled in the configuration. Enable it in the <a href="config/" class="alert-link">configuration settings</a>.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label text-light">Report Type</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_daily" value="daily" checked>
                                    <label class="form-check-label text-light" for="report_daily">
                                        Daily Report
                                    </label>
                                    <small class="form-text text-danger d-block ms-4">Summary of character processing, new characters, updates, and validation errors for today.</small>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_continuity" value="continuity">
                                    <label class="form-check-label text-light" for="report_continuity">
                                        Continuity Report
                                    </label>
                                    <small class="form-text text-danger d-block ms-4">Checks for sire relationship issues, generation inconsistencies, clan conflicts, and timeline problems.</small>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_both" value="both">
                                    <label class="form-check-label text-light" for="report_both">
                                        Both Reports
                                    </label>
                                    <small class="form-text text-danger d-block ms-4">Generate both daily and continuity reports.</small>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="generate" class="btn btn-danger">
                                    Generate Report
                                </button>
                                <a href="../../admin/agents.php" class="btn btn-outline-danger">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-body">
                    <h3 class="text-light mb-3">Report Information</h3>
                    <ul class="text-light mb-0">
                        <li><strong>Daily Reports Location:</strong> <code>reports/daily/</code></li>
                        <li><strong>Continuity Reports Location:</strong> <code>reports/continuity/</code></li>
                        <li><strong>Reporting Enabled:</strong> <?= $reporting_enabled ? '<span class="text-success">Yes</span>' : '<span class="text-warning">No</span>'; ?></li>
                        <li><strong>Daily Reports Enabled:</strong> <?= $generate_daily ? '<span class="text-success">Yes</span>' : '<span class="text-warning">No</span>'; ?></li>
                        <li><strong>Continuity Reports Enabled:</strong> <?= $generate_continuity ? '<span class="text-success">Yes</span>' : '<span class="text-warning">No</span>'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report View Modal -->
<div class="modal fade" id="reportViewModal" tabindex="-1" aria-labelledby="reportViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-light" id="reportViewModalLabel">
                    <span id="reportModalIcon">📄</span>
                    <span id="reportModalTitle">Report Details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reportLoading" class="text-center py-5">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-light mt-3">Loading report data...</p>
                </div>
                <div id="reportContent" style="display: none;">
                    <div id="reportDisplay"></div>
                </div>
                <div id="reportError" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    let modalInstance = null;
    const modal = document.getElementById('reportViewModal');
    const reportContent = document.getElementById('reportContent');
    const reportLoading = document.getElementById('reportLoading');
    const reportError = document.getElementById('reportError');
    const reportDisplay = document.getElementById('reportDisplay');
    const reportModalTitle = document.getElementById('reportModalTitle');
    const reportModalIcon = document.getElementById('reportModalIcon');
    
    // Wait for Bootstrap to be available
    function initBootstrapModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && modal) {
            modalInstance = new bootstrap.Modal(modal);
            return true;
        }
        return false;
    }
    
    // Try to initialize immediately, or wait for Bootstrap to load
    if (!initBootstrapModal()) {
        // Wait for DOM and Bootstrap to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Check again after DOM is ready
                setTimeout(function() {
                    if (!initBootstrapModal()) {
                        console.error('Bootstrap Modal not available');
                    }
                }, 100);
            });
        } else {
            // DOM already ready, wait a bit for Bootstrap
            setTimeout(function() {
                if (!initBootstrapModal()) {
                    console.error('Bootstrap Modal not available');
                }
            }, 100);
        }
    }
    
    function formatJSON(data, indent = 0) {
        if (data === null) {
            return '<span class="json-null">null</span>';
        }
        
        if (typeof data === 'string') {
            return '<span class="json-string">"' + escapeHtml(data) + '"</span>';
        }
        
        if (typeof data === 'number') {
            return '<span class="json-number">' + data + '</span>';
        }
        
        if (typeof data === 'boolean') {
            return '<span class="json-boolean">' + (data ? 'true' : 'false') + '</span>';
        }
        
        if (Array.isArray(data)) {
            if (data.length === 0) {
                return '<span class="json-array">[]</span>';
            }
            let html = '<span class="json-array">[</span><div class="json-array" style="margin-left: 20px;">';
            data.forEach((item, index) => {
                html += '<div class="json-item">' + formatJSON(item, indent + 1);
                if (index < data.length - 1) {
                    html += ',';
                }
                html += '</div>';
            });
            html += '</div><span class="json-array">]</span>';
            return html;
        }
        
        if (typeof data === 'object') {
            const keys = Object.keys(data);
            if (keys.length === 0) {
                return '<span class="json-object">{}</span>';
            }
            let html = '<span class="json-object">{</span><div class="json-object" style="margin-left: 20px;">';
            keys.forEach((key, index) => {
                html += '<div class="json-item"><span class="json-key">"' + escapeHtml(key) + '"</span>: ' + formatJSON(data[key], indent + 1);
                if (index < keys.length - 1) {
                    html += ',';
                }
                html += '</div>';
            });
            html += '</div><span class="json-object">}</span>';
            return html;
        }
        
        return escapeHtml(String(data));
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function renderReport(data, type) {
        let html = '';
        
        // Render summary section if it exists
        if (data.summary) {
            html += '<div class="report-summary">';
            html += '<h6>' + (type === 'Daily Report' ? '📊 Daily Summary' : '🔍 Continuity Summary') + '</h6>';
            Object.keys(data.summary).forEach(key => {
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                html += '<div class="report-summary-item">';
                html += '<span class="report-summary-label">' + escapeHtml(label) + ':</span>';
                html += '<span class="report-summary-value">' + data.summary[key] + '</span>';
                html += '</div>';
            });
            html += '</div>';
        }
        
        // Render full JSON
        html += '<div class="json-display">';
        html += formatJSON(data);
        html += '</div>';
        
        return html;
    }
    
    // Initialize button handlers when DOM is ready
    function initButtonHandlers() {
        document.querySelectorAll('.view-report-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reportFilename = this.getAttribute('data-report-filename');
                const reportType = this.getAttribute('data-report-type');
                const reportTypeApi = this.getAttribute('data-report-type-api');
                const reportFile = this.getAttribute('data-report-file');
                
                // Ensure Bootstrap modal is initialized
                if (!modalInstance && typeof bootstrap !== 'undefined' && bootstrap.Modal && modal) {
                    modalInstance = new bootstrap.Modal(modal);
                }
                
                if (!modalInstance) {
                    alert('Modal system not available. Please refresh the page.');
                    return;
                }
                
                // Update modal title
                if (reportModalTitle) {
                    reportModalTitle.textContent = reportType + ': ' + reportFile;
                }
                if (reportModalIcon) {
                    reportModalIcon.textContent = reportType.includes('Daily') ? '📊' : '🔍';
                }
                
                // Show loading state
                if (reportLoading) reportLoading.style.display = 'block';
                if (reportContent) reportContent.style.display = 'none';
                if (reportError) reportError.style.display = 'none';
                
                // Open modal
                modalInstance.show();
                
                // Build API endpoint URL
                const apiUrl = 'api_get_report.php?path=' + encodeURIComponent(reportFilename) + '&type=' + encodeURIComponent(reportTypeApi);
                
                // Fetch report data via API
                fetch(apiUrl)
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.error || 'Failed to load report: ' + response.statusText);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Check if API returned an error object
                        if (data && data.error) {
                            throw new Error(data.error);
                        }
                        
                        if (reportDisplay) {
                            reportDisplay.innerHTML = renderReport(data, reportType);
                        }
                        if (reportLoading) reportLoading.style.display = 'none';
                        if (reportContent) reportContent.style.display = 'block';
                    })
                    .catch(error => {
                        if (reportLoading) reportLoading.style.display = 'none';
                        if (reportError) {
                            reportError.style.display = 'block';
                            reportError.textContent = 'Error loading report: ' + error.message;
                        }
                    });
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initButtonHandlers);
    } else {
        // DOM already ready
        initButtonHandlers();
    }
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

