<?php
/**
 * Coterie Agent - Coterie Management
 * CRUD operations for character coterie associations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.2.0');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../includes/connect.php';
$extra_css = ['css/modal.css'];
include __DIR__ . '/../../includes/header.php';

// Get coterie statistics
try {
    $stats_query = "SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT coterie_name) as unique_coteries,
        SUM(CASE WHEN coterie_type = 'faction' THEN 1 ELSE 0 END) as factions,
        SUM(CASE WHEN coterie_type = 'role' THEN 1 ELSE 0 END) as roles,
        SUM(CASE WHEN coterie_type = 'membership' THEN 1 ELSE 0 END) as memberships,
        SUM(CASE WHEN coterie_type = 'informal_group' THEN 1 ELSE 0 END) as informal_groups
        FROM character_coteries";
    $stats = db_fetch_one($conn, $stats_query);
    if (!$stats) {
        $stats = ['total' => 0, 'unique_coteries' => 0, 'factions' => 0, 'roles' => 0, 'memberships' => 0, 'informal_groups' => 0];
    }
} catch (Exception $e) {
    $stats = ['total' => 0, 'unique_coteries' => 0, 'factions' => 0, 'roles' => 0, 'memberships' => 0, 'informal_groups' => 0];
}

// Get all unique coterie names, types for filters
try {
    $coterie_names_result = db_fetch_all($conn, "SELECT DISTINCT coterie_name FROM character_coteries WHERE coterie_name != '' ORDER BY coterie_name");
    $coterie_names = array_column($coterie_names_result ?: [], 'coterie_name');
} catch (Exception $e) {
    $coterie_names = [];
}

try {
    $coterie_types_result = db_fetch_all($conn, "SELECT DISTINCT coterie_type FROM character_coteries WHERE coterie_type != '' ORDER BY coterie_type");
    $coterie_types = array_column($coterie_types_result ?: [], 'coterie_type');
} catch (Exception $e) {
    $coterie_types = [];
}

// Get all characters for dropdown
try {
    $all_characters = db_fetch_all($conn, "SELECT id, character_name, clan, player_name FROM characters ORDER BY character_name");
    if (!$all_characters) {
        $all_characters = [];
    }
} catch (Exception $e) {
    $all_characters = [];
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <h1 class="display-5 text-light fw-bold mb-1">👥 Coterie Management</h1>
    <p class="lead text-light fst-italic mb-4">Manage character coterie associations and relationships</p>
    
    <?php include __DIR__ . '/../../includes/admin_header.php'; ?>
    
    <!-- Coterie Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Total Associations</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['unique_coteries'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Unique Coteries</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['factions'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Factions</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['roles'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Roles</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['memberships'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Memberships</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['informal_groups'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Informal Groups</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="row gy-3 align-items-center mb-4">
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="coterieNameFilter" class="text-light text-uppercase small mb-0">Coterie:</label>
            <select id="coterieNameFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Coteries</option>
                <?php foreach ($coterie_names as $name): ?>
                    <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="coterieTypeFilter" class="text-light text-uppercase small mb-0">Type:</label>
            <select id="coterieTypeFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Types</option>
                <?php foreach ($coterie_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="characterFilter" class="text-light text-uppercase small mb-0">Character:</label>
            <select id="characterFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Characters</option>
                <?php foreach ($all_characters as $char): ?>
                    <option value="<?php echo $char['id']; ?>"><?php echo htmlspecialchars($char['character_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg col-xl-4">
            <input type="text" id="coterieSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by coterie name or character..." />
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="pageSize" class="text-light text-uppercase small mb-0">Per page:</label>
            <select id="pageSize" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <!-- Add Coterie Association Button -->
    <div class="mb-4 d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" id="addCoterieBtn">
            <i class="fas fa-plus"></i> Add New Coterie Association
        </button>
        <button class="btn btn-info" id="manageCoterieBtn">
            <i class="fas fa-users"></i> Manage Coterie Members
        </button>
    </div>

    <!-- Coteries Table -->
    <div class="table-responsive rounded-3">
        <table class="table table-dark table-hover align-middle mb-0" id="coteriesTable">
            <thead>
                <tr>
                    <th data-sort="character_id">Character <span class="sort-icon">⇅</span></th>
                    <th data-sort="coterie_name">Coterie Name <span class="sort-icon">⇅</span></th>
                    <th data-sort="coterie_type">Type <span class="sort-icon">⇅</span></th>
                    <th data-sort="role">Role <span class="sort-icon">⇅</span></th>
                    <th data-sort="description">Description</th>
                    <th class="text-center text-nowrap w-150px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div class="pagination-controls" id="paginationControls">
        <div class="pagination-info">
            <span id="paginationInfo" aria-live="polite" aria-atomic="true">Loading...</span>
        </div>
        <div class="pagination-buttons" id="paginationButtons">
            <!-- Buttons will be generated by JavaScript -->
        </div>
    </div>
</div>

<!-- Add/Edit Coterie Modal -->
<?php
$modalId = 'coterieModal';
$labelId = 'coterieModalLabel';
$size = 'lg';
include __DIR__ . '/../../includes/modal_base.php';
?>

<!-- View Coterie Modal -->
<?php
$modalId = 'viewModal';
$labelId = 'viewModalLabel';
$size = 'lg';
include __DIR__ . '/../../includes/modal_base.php';
?>

<!-- Delete Modal -->
<?php
$modalId = 'deleteModal';
$labelId = 'deleteModalLabel';
include __DIR__ . '/../../includes/modal_base.php';
?>

<!-- Manage Coterie Modal -->
<?php
$modalId = 'manageCoterieModal';
$labelId = 'manageCoterieModalLabel';
$size = 'lg';
include __DIR__ . '/../../includes/modal_base.php';
?>

<!-- Include external CSS -->
<link rel="stylesheet" href="../../css/admin_locations.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Pass PHP data to JavaScript via JSON script tags -->
<script type="application/json" id="allCharactersData"><?php echo json_encode($all_characters, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS); ?></script>
<script type="application/json" id="coterieNamesData"><?php echo json_encode($coterie_names, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS); ?></script>
<script type="application/json" id="coterieTypesData"><?php echo json_encode($coterie_types, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS); ?></script>

<!-- Include the external JavaScript file -->
<script src="../../js/admin_coteries.js"></script>
<script src="../../js/form_validation.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

