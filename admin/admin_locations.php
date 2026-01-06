<?php
/**
 * Admin Locations Management
 * CRUD operations for locations database and character assignment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.8.66');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
$extra_css = ['css/modal.css'];
include __DIR__ . '/../includes/header.php';

// Get locations statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'Haven' THEN 1 ELSE 0 END) as havens,
    SUM(CASE WHEN type = 'Elysium' THEN 1 ELSE 0 END) as elysiums,
    SUM(CASE WHEN type = 'Domain' THEN 1 ELSE 0 END) as domains,
    SUM(CASE WHEN type = 'Hunting Ground' THEN 1 ELSE 0 END) as hunting_grounds,
    SUM(CASE WHEN type = 'Nightclub' THEN 1 ELSE 0 END) as nightclubs,
    SUM(CASE WHEN type = 'Business' THEN 1 ELSE 0 END) as businesses,
    SUM(CASE WHEN type = 'Other' THEN 1 ELSE 0 END) as other
    FROM locations";
// SECURITY: Using prepared statement helper for consistency
$stats = db_fetch_one($conn, $stats_query);
if (!$stats) {
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'haven' => 0, 'domain' => 0, 'other' => 0];
}

// Get all unique statuses and owner types for filters
$status_rows = db_fetch_all($conn, "SELECT DISTINCT status FROM locations ORDER BY status");
$location_statuses = array_column($status_rows, 'status');

$owner_rows = db_fetch_all($conn, "SELECT DISTINCT owner_type FROM locations ORDER BY owner_type");
$location_owners = array_column($owner_rows, 'owner_type');

// Get all characters for assignment
$all_characters = db_fetch_all($conn, "SELECT id, character_name, clan, player_name FROM characters ORDER BY character_name");
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <h1 class="display-5 text-light fw-bold mb-1">🏠 Locations Database Management</h1>
    <p class="lead text-light fst-italic mb-4">Manage locations database and assign characters to locations</p>
    
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <!-- Locations Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Total Locations</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['havens'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Havens</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['elysiums'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Elysiums</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['domains'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Domains</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['hunting_grounds'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Hunting Grounds</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['nightclubs'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Nightclubs</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['businesses'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Businesses</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="row gy-3 align-items-center mb-4">
        <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
            <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Locations</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="havens">Havens</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="elysiums">Elysiums</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="domains">Domains</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="hunting-grounds">Hunting Grounds</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="nightclubs">Nightclubs</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="businesses">Businesses</button>
            <button class="filter-btn btn btn-outline-danger" id="hideEarnableBtn" data-filter="hide-earnable">Hide Earnable</button>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="statusFilter" class="text-light text-uppercase small mb-0">Status:</label>
            <select id="statusFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Status</option>
                <?php foreach ($location_statuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="ownerFilter" class="text-light text-uppercase small mb-0">Owner:</label>
            <select id="ownerFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Owners</option>
                <?php foreach ($location_owners as $owner): ?>
                    <option value="<?php echo htmlspecialchars($owner); ?>"><?php echo htmlspecialchars($owner); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="pcHavenFilter" class="text-light text-uppercase small mb-0">PC Haven:</label>
            <select id="pcHavenFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All</option>
                <option value="yes">PC Havens Only</option>
                <option value="no">Non-PC Havens</option>
            </select>
        </div>
        <div class="col-12 col-lg col-xl-4">
            <input type="text" id="locationSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by name..." />
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

    <!-- Add Location Button -->
    <div class="mb-4 d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" id="addLocationBtn">
            <i class="fas fa-plus"></i> Add New Location
        </button>
        <a href="../phoenix_map.php" class="btn btn-outline-primary">
            <i class="fas fa-map"></i> View Phoenix Map
        </a>
        <a href="../database/check_location_types.php" class="btn btn-outline-info" target="_blank">
            <i class="fas fa-search"></i> Check Location Types
        </a>
        <a href="../database/check_missing_locations.php" class="btn btn-outline-warning" target="_blank">
            <i class="fas fa-exclamation-triangle"></i> Check Missing Locations
        </a>
        <a href="../database/restore_main_locations.php" class="btn btn-outline-success" target="_blank">
            <i class="fas fa-undo"></i> Restore Main Locations
        </a>
    </div>

    <!-- Locations Table -->
    <div class="table-responsive rounded-3">
        <table class="table table-dark table-hover align-middle mb-0" id="locationsTable">
            <thead>
                <tr>
                    <th data-sort="id">ID <span class="sort-icon">⇅</span></th>
                    <th data-sort="name">Name <span class="sort-icon">⇅</span></th>
                    <th data-sort="type">Type <span class="sort-icon">⇅</span></th>
                    <th data-sort="status">Status <span class="sort-icon">⇅</span></th>
                    <th data-sort="district">District <span class="sort-icon">⇅</span></th>
                    <th data-sort="owner_type">Owner Type <span class="sort-icon">⇅</span></th>
                    <th data-sort="pc_earnable">PC Earnable <span class="sort-icon">⇅</span></th>
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

<!-- Add/Edit Location Modal -->
<?php
$modalId = 'locationModal';
$labelId = 'locationModalLabel';
$size = 'lg';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- View Location Modal -->
<?php
$modalId = 'viewModal';
$labelId = 'viewModalLabel';
$size = 'lg';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Character Assignment Modal -->
<?php
$modalId = 'assignModal';
$labelId = 'assignModalLabel';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Delete Modal -->
<?php
$modalId = 'deleteModal';
$labelId = 'deleteModalLabel';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Include external CSS -->
<link rel="stylesheet" href="../css/admin_table_responsive.css">
<link rel="stylesheet" href="../css/admin_locations.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Pass PHP data to JavaScript via JSON script tags -->
<script type="application/json" id="allCharactersData"><?php echo json_encode($all_characters); ?></script>
<script type="application/json" id="locationStatusesData"><?php echo json_encode($location_statuses); ?></script>
<script type="application/json" id="locationOwnersData"><?php echo json_encode($location_owners); ?></script>

<!-- Include the external JavaScript file -->
<!-- Music System -->
<script src="../js/modules/systems/MusicManager.js"></script>
<script src="../js/music_init.js"></script>
<!-- Admin Locations -->
<script src="../js/admin_locations.js"></script>
<script src="../js/form_validation.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
