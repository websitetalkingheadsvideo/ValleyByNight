<?php
/**
 * Admin Panel - Boon Agent Viewer
 * 
 * Interface for viewing Boon Agent reports, validation results, and running agent operations.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.9.22');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check for AJAX requests BEFORE including header (which outputs HTML)
$action = $_GET['action'] ?? '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// If AJAX request, handle it and return JSON without loading the full page
if ($isAjax && $action) {
    require_once __DIR__ . '/../includes/connect.php';
    require_once __DIR__ . '/../agents/boon_agent/src/BoonAgent.php';
    
    $boonAgent = null;
    $actionResult = null;
    
    try {
        $config_file = __DIR__ . '/../agents/boon_agent/config/settings.json';
        $config = [];
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
        }
        
        $boonAgent = new BoonAgent($conn, $config);
        
        if ($boonAgent) {
            switch ($action) {
                case 'validate':
                    $actionResult = $boonAgent->validateAllBoons();
                    break;
                case 'analyze':
                    $actionResult = $boonAgent->analyzeBoonEconomy();
                    break;
                case 'dead_debts':
                    $actionResult = $boonAgent->detectDeadDebts();
                    break;
                case 'broken':
                    $actionResult = $boonAgent->detectBrokenBoons();
                    break;
                case 'unregistered':
                    $actionResult = $boonAgent->detectUnregisteredBoons();
                    break;
                case 'combinations':
                    $actionResult = $boonAgent->findCombinationOpportunities();
                    break;
                case 'generate_daily':
                    $actionResult = $boonAgent->generateDailyReport();
                    break;
                case 'generate_validation':
                    $actionResult = $boonAgent->generateValidationReport();
                    break;
                case 'generate_economy':
                    $actionResult = $boonAgent->generateEconomyReport();
                    break;
            }
        }
    } catch (Exception $e) {
        $actionResult = ['success' => false, 'error' => $e->getMessage()];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($actionResult !== null ? $actionResult : ['success' => false, 'error' => 'No result'], JSON_PRETTY_PRINT);
    exit;
}

// Normal page load - include header and continue
require_once __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/header.php';

// Load Boon Agent
require_once __DIR__ . '/../agents/boon_agent/src/BoonAgent.php';

$boonAgent = null;
$error = null;

try {
    // Pass the database connection and load config
    $config_file = __DIR__ . '/../agents/boon_agent/config/settings.json';
    $config = [];
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
    }
    
    $boonAgent = new BoonAgent($conn, $config);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle actions for non-AJAX requests (for backward compatibility)
$actionResult = null;
if ($boonAgent && $action) {
    switch ($action) {
        case 'validate':
            $actionResult = $boonAgent->validateAllBoons();
            break;
        case 'analyze':
            $actionResult = $boonAgent->analyzeBoonEconomy();
            break;
        case 'dead_debts':
            $actionResult = $boonAgent->detectDeadDebts();
            break;
        case 'broken':
            $actionResult = $boonAgent->detectBrokenBoons();
            break;
        case 'unregistered':
            $actionResult = $boonAgent->detectUnregisteredBoons();
            break;
        case 'combinations':
            $actionResult = $boonAgent->findCombinationOpportunities();
            break;
        case 'generate_daily':
            $actionResult = $boonAgent->generateDailyReport();
            break;
        case 'generate_validation':
            $actionResult = $boonAgent->generateValidationReport();
            break;
        case 'generate_economy':
            $actionResult = $boonAgent->generateEconomyReport();
            break;
    }
}

$extra_css = ['css/admin-agents.css'];
?>

<div class="admin-panel-container agents-panel container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h1 class="display-5 text-light fw-bold mb-1">💎 Boon Agent</h1>
        <p class="agents-intro lead fst-italic mb-0">Monitor, validate, and analyze boons according to Laws of the Night Revised mechanics.</p>
    </div>

    <div class="mb-3">
        <a href="agents.php" class="btn btn-outline-secondary btn-sm">
            ← Back to Agents
        </a>
        <a href="boon_ledger.php" class="btn btn-outline-danger btn-sm">
            📋 Boon Ledger
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-4">
            <h5 class="alert-heading">Error Loading Boon Agent</h5>
            <p class="mb-0"><?= htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($actionResult): ?>
        <div class="alert alert-info mb-4">
            <h5 class="alert-heading">Action Result</h5>
            <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code><?= htmlspecialchars(json_encode($actionResult, JSON_PRETTY_PRINT)); ?></code></pre>
        </div>
    <?php endif; ?>

    <?php if ($boonAgent): ?>
        <!-- Agent Actions -->
        <div class="card bg-dark border-danger mb-4">
            <div class="card-body">
                <h3 class="card-title text-light mb-3">Agent Operations</h3>
                <div class="row g-2">
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('validate', 'Validate All Boons')">
                            🔍 Validate All Boons
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('analyze', 'Analyze Economy')">
                            📊 Analyze Economy
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('dead_debts', 'Detect Dead Debts')">
                            💀 Detect Dead Debts
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('broken', 'Find Broken Boons')">
                            ⚠️ Find Broken Boons
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('unregistered', 'Find Unregistered')">
                            📝 Find Unregistered
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="runAction('combinations', 'Find Combinations')">
                            🔗 Find Combinations
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Generation -->
        <div class="card bg-dark border-danger mb-4">
            <div class="card-body">
                <h3 class="card-title text-light mb-3">Report Generation</h3>
                <div class="row g-2">
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-primary w-100" onclick="generateReport('generate_daily', 'Daily Report')">
                            📅 Generate Daily Report
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-primary w-100" onclick="generateReport('generate_validation', 'Validation Report')">
                            ✓ Generate Validation Report
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-primary w-100" onclick="generateReport('generate_economy', 'Economy Report')">
                            💰 Generate Economy Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card bg-dark border-danger mb-4">
            <div class="card-body">
                <h3 class="card-title text-light mb-3">Quick Statistics</h3>
                <?php
                $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as owed,
                    0 as called,
                    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status IN ('disputed', 'cancelled') THEN 1 ELSE 0 END) as broken
                FROM boons";
                $statsResult = mysqli_query($conn, $statsQuery);
                $stats = mysqli_fetch_assoc($statsResult);
                ?>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="display-6 text-light"><?= $stats['total'] ?? 0; ?></div>
                            <div class="text-muted small">Total Boons</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="display-6 text-warning"><?= $stats['owed'] ?? 0; ?></div>
                            <div class="text-muted small">Owed</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="display-6 text-danger"><?= $stats['called'] ?? 0; ?></div>
                            <div class="text-muted small">Called</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="display-6 text-success"><?= $stats['paid'] ?? 0; ?></div>
                            <div class="text-muted small">Paid</div>
                        </div>
                    </div>
                    <?php if (($stats['broken'] ?? 0) > 0): ?>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="display-6 text-dark"><?= $stats['broken']; ?></div>
                                <div class="text-muted small">Broken</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reports Directory Links -->
        <div class="card bg-dark border-danger">
            <div class="card-body">
                <h3 class="card-title text-light mb-3">View Reports</h3>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a href="../agents/boon_agent/reports/daily/" class="text-light" target="_blank">
                            📅 Daily Reports
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="../agents/boon_agent/reports/character/" class="text-light" target="_blank">
                            👤 Character Reports
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="../agents/boon_agent/reports/validation/" class="text-light" target="_blank">
                            ✓ Validation Reports
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="../agents/boon_agent/config/" class="text-light">
                            ⚙️ View Configuration
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Report Result Modal -->
<div class="modal fade" id="reportResultModal" tabindex="-1" aria-labelledby="reportResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-light" id="reportResultModalLabel">Report Generation Result</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reportResultContent"></div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function runAction(action, actionName) {
    showActionModal(action, actionName);
}

function generateReport(action, reportName) {
    showActionModal(action, reportName);
}

function showActionModal(action, actionName) {
    // Show loading state
    const modalEl = document.getElementById('reportResultModal');
    const modalTitle = document.getElementById('reportResultModalLabel');
    const modalContent = document.getElementById('reportResultContent');
    
    modalTitle.textContent = 'Running ' + actionName + '...';
    modalContent.innerHTML = '<div class="text-center text-light"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Please wait...</p></div>';
    
    // Show modal
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true,
            keyboard: true
        });
        modalInstance.show();
    }
    
    // Make AJAX request
    fetch('?action=' + encodeURIComponent(action), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        modalTitle.textContent = actionName + ' Result';
        modalContent.innerHTML = formatJsonAsHtml(data);
    })
    .catch(error => {
        modalTitle.textContent = 'Error';
        modalContent.innerHTML = '<div class="alert alert-danger">Error: ' + escapeHtml(error.message) + '</div>';
    });
}

function formatJsonAsHtml(data, level = 0, key = '') {
    if (data === null) {
        return '<span class="text-muted fst-italic">null</span>';
    }
    
    if (data === undefined) {
        return '<span class="text-muted fst-italic">undefined</span>';
    }
    
    const type = typeof data;
    
    if (type === 'boolean') {
        return '<span class="text-warning">' + (data ? 'true' : 'false') + '</span>';
    }
    
    if (type === 'number') {
        return '<span class="text-info">' + data + '</span>';
    }
    
    if (type === 'string') {
        let displayValue = data;
        // Clean up paths by removing server path prefix
        if (key.toLowerCase().includes('path') || data.includes('/usr/home/working/public_html/')) {
            displayValue = data.replace(/^\/usr\/home\/working\/public_html\//, '');
        }
        return '<span class="text-success">"' + escapeHtml(displayValue) + '"</span>';
    }
    
    if (Array.isArray(data)) {
        if (data.length === 0) {
            return '<span class="text-muted">[]</span>';
        }
        
        let html = '<ul class="list-unstyled mb-0" style="margin-left: ' + (level * 20) + 'px;">';
        data.forEach((item, index) => {
            html += '<li class="mb-2">';
            html += '<span class="text-muted">[' + index + ']:</span> ';
            html += formatJsonAsHtml(item, level + 1, '');
            html += '</li>';
        });
        html += '</ul>';
        return html;
    }
    
    if (type === 'object') {
        const keys = Object.keys(data);
        if (keys.length === 0) {
            return '<span class="text-muted">{}</span>';
        }
        
        let html = '<div class="mb-2" style="margin-left: ' + (level * 20) + 'px;">';
        keys.forEach(keyName => {
            const value = data[keyName];
            html += '<div class="mb-2 pb-2 border-bottom border-secondary" style="border-width: 1px !important;">';
            html += '<strong class="text-danger">' + escapeHtml(keyName) + ':</strong> ';
            
            if (typeof value === 'object' && value !== null) {
                html += '<div class="mt-1">' + formatJsonAsHtml(value, level + 1, keyName) + '</div>';
            } else {
                html += formatJsonAsHtml(value, level + 1, keyName);
            }
            html += '</div>';
        });
        html += '</div>';
        return html;
    }
    
    return escapeHtml(String(data));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

