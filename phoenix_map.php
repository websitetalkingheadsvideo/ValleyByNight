<?php
/**
 * Phoenix Map Viewer
 * Interactive map display of Phoenix locations
 */

define('LOTN_VERSION', '0.8.30');
session_start();

require_once __DIR__ . '/includes/connect.php';
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
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Check if map_pixel_x and map_pixel_y columns exist
// SECURITY: Using prepared statement helper for consistency
$columns_result = db_select($conn, "SHOW COLUMNS FROM locations LIKE ?", "s", ['map_pixel_x']);
$has_pixel_columns = $columns_result ? mysqli_num_rows($columns_result) > 0 : false;

// Get all locations for potential markers/overlay
// Try to use map_pixel_x/y first, fallback to lat/lng if needed
if ($has_pixel_columns) {
    $locations_query = "SELECT id, name, type, district, latitude, longitude, status,
                        map_pixel_x, map_pixel_y
                        FROM locations 
                        WHERE status = 'Active'
                        AND (
                            (map_pixel_x IS NOT NULL AND map_pixel_y IS NOT NULL)
                            OR (latitude IS NOT NULL AND longitude IS NOT NULL)
                        )
                        ORDER BY name";
} else {
    // Fallback if pixel columns don't exist yet
    $locations_query = "SELECT id, name, type, district, latitude, longitude, status
                        FROM locations 
                        WHERE status = 'Active'
                        AND (latitude IS NOT NULL AND longitude IS NOT NULL)
                        ORDER BY name";
}

// SECURITY: Using prepared statement helper for consistency
// Note: These queries are static (no user input), but using helpers maintains security standards
if ($has_pixel_columns) {
    $locations = db_fetch_all($conn, 
        "SELECT id, name, type, district, latitude, longitude, status, map_pixel_x, map_pixel_y
         FROM locations 
         WHERE status = 'Active'
         AND (
             (map_pixel_x IS NOT NULL AND map_pixel_y IS NOT NULL)
             OR (latitude IS NOT NULL AND longitude IS NOT NULL)
         )
         ORDER BY name"
    );
} else {
    $locations = db_fetch_all($conn,
        "SELECT id, name, type, district, latitude, longitude, status
         FROM locations 
         WHERE status = 'Active'
         AND (latitude IS NOT NULL AND longitude IS NOT NULL)
         ORDER BY name"
    );
}

// Ensure map_pixel_x/y are set to null if columns don't exist
if (!$has_pixel_columns) {
    foreach ($locations as &$loc) {
        $loc['map_pixel_x'] = null;
        $loc['map_pixel_y'] = null;
    }
    unset($loc);
}
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
                            // SECURITY: Using prepared statement helper for consistency
                            $all_locs = db_fetch_all($conn, "SELECT id, name FROM locations WHERE status = 'Active' ORDER BY name");
                            foreach ($all_locs as $loc) {
                                echo '<option value="' . htmlspecialchars($loc['id']) . '">' . htmlspecialchars($loc['name']) . '</option>';
                            }
                            ?>
                        </select>
                        <?php endif; ?>
                        <div class="vr"></div>
                        <span class="text-muted small">
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

