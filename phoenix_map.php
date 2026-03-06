<?php
/**
 * Phoenix Map Viewer
 * Interactive map display of Phoenix locations
 */

define('LOTN_VERSION', '0.8.30');
session_start();

require_once __DIR__ . '/includes/supabase_client.php';
require_once __DIR__ . '/includes/auth_bypass.php';

// Check if user is logged in (or bypass is enabled)
if (!isset($_SESSION['user_id']) && !isAuthBypassEnabled()) {
    header('Location: login.php');
    exit;
}

// If bypass is enabled, set up guest session
if (isAuthBypassEnabled() && !isset($_SESSION['user_id'])) {
    setupBypassSession();
}

include __DIR__ . '/includes/header.php';

// Check if user is admin for edit mode
require_once __DIR__ . '/includes/verify_role.php';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = verifyUserRole(null, $user_id);
$is_admin = isAdminUser($user_role);

$has_pixel_columns = true;
$locations = supabase_table_get('locations', [
    'select' => 'id,name,type,district,latitude,longitude,status,map_pixel_x,map_pixel_y',
    'status' => 'eq.Active',
    'order' => 'name.asc'
]);
$locations = array_values(array_filter($locations, static function (array $location): bool {
    $hasPixels = isset($location['map_pixel_x'], $location['map_pixel_y'])
        && $location['map_pixel_x'] !== null
        && $location['map_pixel_y'] !== null;
    $hasCoordinates = isset($location['latitude'], $location['longitude'])
        && $location['latitude'] !== null
        && $location['longitude'] !== null;
    return $hasPixels || $hasCoordinates;
}));
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">🗺️ Phoenix Map</h1>
            <p class="lead text-light fst-italic mb-0">Explore the domains and havens of Phoenix</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin/admin_locations.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Manage Locations
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Controls -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button id="zoomInBtn" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-search-plus"></i> Zoom In
                        </button>
                        <button id="zoomOutBtn" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-search-minus"></i> Zoom Out
                        </button>
                        <button id="resetZoomBtn" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-expand"></i> Reset Zoom
                        </button>
                        <div class="vr"></div>
                        <label class="text-light mb-0">
                            <input type="checkbox" id="showMarkers" checked class="form-check-input me-2">
                            Show Location Markers
                        </label>
                        <?php if ($is_admin): ?>
                        <div class="vr"></div>
                        <label class="text-light mb-0">
                            <input type="checkbox" id="editMode" class="form-check-input me-2">
                            <strong>Edit Mode</strong> (Click map to place markers)
                        </label>
                        <select id="locationSelect" class="form-select form-select-sm bg-dark text-light border-danger ms-2" style="display: none; max-width: 200px;">
                            <option value="">Select location...</option>
                            <?php
                            $all_locs = supabase_table_get('locations', [
                                'select' => 'id,name',
                                'status' => 'eq.Active',
                                'order' => 'name.asc'
                            ]);
                            foreach ($all_locs as $loc) {
                                echo '<option value="' . htmlspecialchars($loc['id']) . '">' . htmlspecialchars($loc['name']) . '</option>';
                            }
                            ?>
                        </select>
                        <?php endif; ?>
                        <div class="vr"></div>
                        <span class="opacity-75 small">
                            <i class="fas fa-info-circle"></i> Click and drag to pan, use controls to zoom
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0 position-relative" style="background: #1a0f0f; overflow: hidden;">
                    <div id="mapContainer" class="map-container">
                        <img id="phoenixMap" 
                             src="reference/Locations/Phoenix Map.webp" 
                             alt="Phoenix Map" 
                             class="map-image"
                             draggable="false">
                        <div id="markersOverlay" class="markers-overlay">
                            <!-- Location markers will be added here via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Locations Legend (if markers are enabled) -->
    <div class="row mt-4" id="locationsLegend">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Locations on Map</h5>
                </div>
                <div class="card-body">
                    <div id="locationsList" class="row g-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End container-fluid -->

<link rel="stylesheet" href="css/phoenix_map.css">

<script>
// Map data from PHP
const mapLocations = <?php echo json_encode($locations); ?>;
const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
</script>
<script src="js/phoenix_map.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>

