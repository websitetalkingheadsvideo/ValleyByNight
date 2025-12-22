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
                case 'get_relationships':
                    $actionResult = getBoonRelationshipsData($conn);
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

/**
 * Get boon relationships data for graph visualization
 */
function getBoonRelationshipsData($conn) {
    // Fetch all boons with character information
    $query = "SELECT 
                b.id as boon_id,
                b.creditor_id,
                b.debtor_id,
                creditor.character_name as creditor_name,
                debtor.character_name as debtor_name,
                b.boon_type,
                b.status,
                b.description
              FROM boons b
              LEFT JOIN characters creditor ON b.creditor_id = creditor.id
              LEFT JOIN characters debtor ON b.debtor_id = debtor.id
              WHERE b.status != 'fulfilled' AND b.status != 'cancelled'
              ORDER BY b.created_date DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ['success' => false, 'error' => mysqli_error($conn)];
    }
    
    $nodes = [];
    $edges = [];
    $characterMap = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Add creditor node if not exists
        if (!isset($characterMap[$row['creditor_id']])) {
            $characterMap[$row['creditor_id']] = [
                'id' => $row['creditor_id'],
                'label' => $row['creditor_name'] ?? 'Unknown',
                'name' => $row['creditor_name'] ?? 'Unknown'
            ];
        }
        
        // Add debtor node if not exists
        if (!isset($characterMap[$row['debtor_id']])) {
            $characterMap[$row['debtor_id']] = [
                'id' => $row['debtor_id'],
                'label' => $row['debtor_name'] ?? 'Unknown',
                'name' => $row['debtor_name'] ?? 'Unknown'
            ];
        }
        
        // Add edge (relationship)
        $edgeColor = getBoonTypeColor($row['boon_type']);
        $edgeTitle = sprintf(
            '%s → %s: %s (%s)',
            htmlspecialchars($row['creditor_name'] ?? 'Unknown'),
            htmlspecialchars($row['debtor_name'] ?? 'Unknown'),
            htmlspecialchars($row['boon_type']),
            htmlspecialchars($row['status'])
        );
        
        $edges[] = [
            'from' => $row['creditor_id'],
            'to' => $row['debtor_id'],
            'label' => ucfirst($row['boon_type']),
            'title' => $edgeTitle,
            'color' => ['color' => $edgeColor],
            'width' => getBoonTypeWidth($row['boon_type']),
            'boon_id' => $row['boon_id'],
            'boon_type' => $row['boon_type'],
            'status' => $row['status']
        ];
    }
    
    // Convert map to array
    $nodes = array_values($characterMap);
    
    return [
        'success' => true,
        'nodes' => $nodes,
        'edges' => $edges
    ];
}

function getBoonTypeColor($type) {
    $colors = [
        'trivial' => '#666666',
        'minor' => '#8B6508',
        'major' => '#8B0000',
        'life' => '#1a0f0f'
    ];
    return $colors[strtolower($type)] ?? '#666666';
}

function getBoonTypeWidth($type) {
    $widths = [
        'trivial' => 1,
        'minor' => 2,
        'major' => 3,
        'life' => 4
    ];
    return $widths[strtolower($type)] ?? 1;
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
                    <div class="col-12 col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="showBoonRelationshipsGraph()">
                            🕸️ Boon Relationships
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
<div class="modal fade" id="reportResultModal" tabindex="-1" aria-labelledby="reportResultModalLabel" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-light" id="reportResultModalLabel">Report Generation Result</h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

<!-- Boon Relationships Graph Modal -->
<div class="modal fade" id="boonRelationshipsModal" tabindex="-1" aria-labelledby="boonRelationshipsModalLabel" aria-hidden="true" data-fullscreen="true" data-fullscreen-resize-handler="handleBoonGraphResize">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" id="boonRelationshipsModalDialog">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-light" id="boonRelationshipsModalLabel">Boon Relationships Graph</h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="boonGraphLoading" class="text-center text-light py-5">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading boon relationships...</p>
                </div>
                <div id="boonGraphError" class="alert alert-danger d-none"></div>
                <div id="boonGraphContainer" style="width: 100%; height: 600px; border: 1px solid #8B0000; border-radius: 5px;"></div>
                <div id="boonGraphLegend" class="mt-3 text-light"></div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- vis-network for graph visualization -->
<link href="https://unpkg.com/vis-network/styles/vis-network.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<!-- JavaScript moved to external file -->
<script src="../js/admin_boon_agent_viewer.js" defer></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

