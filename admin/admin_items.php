<?php
/**
 * Admin Items Management
 * CRUD operations for items database and character equipment assignment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Version managed in includes/version.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

// Check database connection
if (!isset($conn) || !$conn) {
    die('Database connection failed. Please check your configuration.');
}

$extra_css = ['css/admin_table_responsive.css', 'css/admin_items.css', 'css/modal.css', 'css/modal_fullscreen.css'];
$body_class = 'admin-items-page';
include __DIR__ . '/../includes/header.php';

// Get items statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'Weapon' THEN 1 ELSE 0 END) as weapons,
    SUM(CASE WHEN type = 'Armor' THEN 1 ELSE 0 END) as armor,
    SUM(CASE WHEN type = 'Tool' THEN 1 ELSE 0 END) as tools,
    SUM(CASE WHEN type = 'Consumable' THEN 1 ELSE 0 END) as consumables,
    SUM(CASE WHEN type = 'Artifact' THEN 1 ELSE 0 END) as artifacts,
    SUM(CASE WHEN type = 'Misc' THEN 1 ELSE 0 END) as misc
    FROM items";
$stats_result = mysqli_query($conn, $stats_query);
if (!$stats_result) {
    die('Database query failed: ' . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($stats_result);

// Get all unique types and categories for filters
$types_query = "SELECT DISTINCT type FROM items ORDER BY type";
$types_result = mysqli_query($conn, $types_query);
if (!$types_result) {
    die('Types query failed: ' . mysqli_error($conn));
}
$item_types = [];
while ($type_row = $types_result->fetch_assoc()) {
    $item_types[] = $type_row['type'];
}

// Get valid categories from items_categories lookup table for dropdown
$item_categories = [];
$categories_query = "SELECT DISTINCT category_name FROM items_categories ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);
if ($categories_result) {
    while ($cat_row = $categories_result->fetch_assoc()) {
        $item_categories[] = $cat_row['category_name'];
    }
}

// Get all characters for equipment assignment
$characters_query = "SELECT id, character_name, clan, player_name FROM characters ORDER BY character_name";
$characters_result = mysqli_query($conn, $characters_query);
if (!$characters_result) {
    die('Characters query failed: ' . mysqli_error($conn));
}
$all_characters = [];
while ($char = $characters_result->fetch_assoc()) {
    $all_characters[] = $char;
}
?>

<div class="container-fluid py-4 px-3 px-md-4 d-flex flex-column">
    <h1 class="display-5 text-light fw-bold mb-1">⚔️ Items Database Management</h1>
    <p class="lead text-light fst-italic mb-4">Manage items database and assign equipment to characters</p>
    
    <!-- Admin Navigation -->
    <nav class="row g-2 g-md-3 mb-4" aria-label="Admin Navigation">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👥 Characters</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_sire_childe.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🧛 Sire/Childe</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_items.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center active">⚔️ Items</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_locations.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📍 Locations</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="questionnaire_admin.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📝 Questionnaire</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_npc_briefing.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📋 NPC Briefing</a>
        </div>
    </nav>
    
    <!-- Items Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Total Items</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['weapons'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Weapons</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['armor'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Armor</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['tools'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Tools</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['consumables'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Consumables</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number"><?php echo $stats['artifacts'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Artifacts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="row gy-3 align-items-center mb-4">
        <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
            <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Items</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="weapons">Weapons</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="armor">Armor</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="tools">Tools</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="consumables">Consumables</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="artifacts">Artifacts</button>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="typeFilter" class="text-light text-uppercase small mb-0">Type:</label>
            <select id="typeFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Types</option>
                <?php foreach ($item_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="rarityFilter" class="text-light text-uppercase small mb-0">Rarity:</label>
            <select id="rarityFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Rarities</option>
                <option value="common">Common</option>
                <option value="uncommon">Uncommon</option>
                <option value="rare">Rare</option>
                <option value="epic">Epic</option>
                <option value="legendary">Legendary</option>
            </select>
        </div>
        <div class="col-12 col-lg col-xl-4">
            <input type="text" id="itemSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by name..." />
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

    <!-- Add Item Button -->
    <div class="mb-4">
        <button class="btn btn-primary" id="addItemBtn">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>

    <!-- Items Table -->
    <div class="table-responsive rounded-3 flex-fill" style="min-height: 200px;">
        <table class="items-table table table-dark table-hover align-middle" id="itemsTable">
            <thead>
                <tr>
                    <th data-sort="id">ID <span class="sort-icon">⇅</span></th>
                    <th data-sort="name">Name <span class="sort-icon">⇅</span></th>
                    <th data-sort="type">Type <span class="sort-icon">⇅</span></th>
                    <th data-sort="category">Category <span class="sort-icon">⇅</span></th>
                    <th data-sort="damage">Damage <span class="sort-icon">⇅</span></th>
                    <th data-sort="range">Range <span class="sort-icon">⇅</span></th>
                    <th data-sort="rarity">Rarity <span class="sort-icon">⇅</span></th>
                    <th data-sort="price">Price <span class="sort-icon">⇅</span></th>
                    <th>Actions</th>
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

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content vbn-modal-content">
            <div class="modal-header vbn-modal-header">
                <h5 class="modal-title vbn-modal-title" id="itemModalLabel">📦 <span id="itemModalTitle">Add New Item</span></h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-light modal-fullscreen-btn" id="itemModalFullscreenBtn" title="Toggle Fullscreen" aria-label="Toggle Fullscreen">
                        <i class="fas fa-expand modal-fullscreen-icon"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <form id="itemForm" class="needs-validation" novalidate>
            <input type="hidden" id="itemId" name="id">
            
            <div class="row g-3">
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemName" class="form-label">Name *</label>
                    <input type="text" id="itemName" name="name" class="form-control" required>
                    <div class="invalid-feedback">Please enter an item name.</div>
                </div>
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemType" class="form-label">Type *</label>
                    <select id="itemType" name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Weapon">Weapon</option>
                        <option value="Armor">Armor</option>
                        <option value="Tool">Tool</option>
                        <option value="Consumable">Consumable</option>
                        <option value="Artifact">Artifact</option>
                        <option value="Misc">Misc</option>
                        <option value="Ammunition">Ammunition</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Gear">Gear</option>
                        <option value="Magical">Magical</option>
                        <option value="Token">Token</option>
                        <option value="Magical Tool">Magical Tool</option>
                        <option value="Trait">Trait</option>
                    </select>
                    <div class="invalid-feedback">Please select a type.</div>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemCategory" class="form-label">Category *</label>
                    <select id="itemCategory" name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($item_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                </div>
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemRarity" class="form-label">Rarity *</label>
                    <select id="itemRarity" name="rarity" class="form-select" required>
                        <option value="">Select Rarity</option>
                        <option value="common">Common</option>
                        <option value="uncommon">Uncommon</option>
                        <option value="rare">Rare</option>
                        <option value="very rare">Very Rare</option>
                        <option value="epic">Epic</option>
                        <option value="legendary">Legendary</option>
                    </select>
                    <div class="invalid-feedback">Please select a rarity.</div>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemDamage" class="form-label">Damage</label>
                    <input type="text" id="itemDamage" name="damage" class="form-control" placeholder="e.g., 2L, 3B">
                </div>
                <div class="mb-3 col-12 col-md-6">
                    <label for="itemRange" class="form-label">Range</label>
                    <input type="text" id="itemRange" name="range" class="form-control" placeholder="e.g., Close, Medium">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="itemPrice" class="form-label">Price *</label>
                <input type="number" id="itemPrice" name="price" class="form-control" required min="0">
                <div class="invalid-feedback">Please provide a valid price.</div>
            </div>
            
            <div class="mb-3">
                <label for="itemDescription" class="form-label">Description *</label>
                <textarea id="itemDescription" name="description" class="form-control" required></textarea>
                <div class="invalid-feedback">Please enter a description.</div>
            </div>
            
            <div class="mb-3">
                <label for="itemRequirements" class="form-label">Requirements (JSON)</label>
                <textarea id="itemRequirements" name="requirements" class="form-control" rows="3" placeholder='{"strength": 3, "dexterity": 2}'></textarea>
                <small class="form-text opacity-75" style="color: #d4c4b0; font-size: 0.85em;">Format: JSON object with attribute: value pairs</small>
            </div>
            
            <!-- Special Powers & Consequences Section -->
            <div class="mb-3 special-powers-section">
                <div class="special-powers-header" data-bs-toggle="collapse" data-bs-target="#specialPowersCollapse" aria-expanded="false" aria-controls="specialPowersCollapse">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <span aria-hidden="true">⚡</span>
                        <span>Special Powers & Consequences</span>
                        <i class="fas fa-chevron-down ms-auto special-powers-toggle-icon"></i>
                    </h5>
                </div>
                <div class="collapse" id="specialPowersCollapse">
                    <div class="special-powers-content">
                        <div class="mb-3">
                            <label for="itemActivatedPower" class="form-label">Activated Power</label>
                            <textarea id="itemActivatedPower" name="activated_power" class="form-control" rows="4" placeholder="Describe the power that activates when this item is used..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="itemDangerousPower" class="form-label">Dangerous Power</label>
                            <textarea id="itemDangerousPower" name="dangerous_power" class="form-control" rows="4" placeholder="Describe any dangerous or risky aspects of this item's power..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="itemStoryConsequences" class="form-label">Story Consequences</label>
                            <textarea id="itemStoryConsequences" name="story_consequences" class="form-control" rows="4" placeholder="Describe the narrative consequences of using this item..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="itemImage" class="form-label">Image Name</label>
                <input type="text" id="itemImage" name="image" class="form-control" placeholder="image.jpg">
                <small class="form-text opacity-75" style="color: #d4c4b0; font-size: 0.85em;">Image file name from uploads/items/ directory</small>
            </div>
            
            <div class="mb-3">
                <label for="itemNotes" class="form-label">Notes</label>
                <textarea id="itemNotes" name="notes" class="form-control"></textarea>
            </div>
            
                </form>
            </div>
            <div class="modal-footer vbn-modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="itemForm" class="btn btn-primary">Save Item</button>
            </div>
        </div>
    </div>
</div>

<!-- View Item Modal -->
<?php
$modalId = 'viewModal';
$labelId = 'viewModalLabel';
$size = 'lg';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Equipment Assignment Modal -->
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

<script>
// Pass character data to JavaScript for assign modal
const allCharactersForItems = <?php echo json_encode($all_characters); ?>;
</script>

<!-- admin_items.css already included via $extra_css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Pass PHP data to JavaScript -->
<script>
    const allCharacters = <?php echo json_encode($all_characters); ?>;
    const itemTypes = <?php echo json_encode($item_types); ?>;
    const itemCategories = <?php echo json_encode($item_categories); ?>;
    const PATH_PREFIX = <?php echo json_encode($path_prefix); ?>;
</script>

<!-- Include the external JavaScript file -->
<script src="../js/admin_items.js"></script>
<script src="../js/form_validation.js"></script>
<script>
// Handle special powers section collapse toggle
(function() {
    'use strict';
    
    function setupSpecialPowersCollapse() {
        const collapseEl = document.getElementById('specialPowersCollapse');
        const headerEl = document.querySelector('.special-powers-header');
        
        if (!collapseEl || !headerEl) {
            setTimeout(setupSpecialPowersCollapse, 100);
            return;
        }
        
        collapseEl.addEventListener('show.bs.collapse', function() {
            headerEl.setAttribute('aria-expanded', 'true');
        });
        
        collapseEl.addEventListener('hide.bs.collapse', function() {
            headerEl.setAttribute('aria-expanded', 'false');
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupSpecialPowersCollapse);
    } else {
        setupSpecialPowersCollapse();
    }
})();

// Handle fullscreen button for viewModal
(function() {
    'use strict';
    
    function toggleFullscreen(modalId) {
        const modal = document.getElementById(modalId);
        const btn = document.getElementById(modalId + 'FullscreenBtn');
        const icon = btn?.querySelector('.modal-fullscreen-icon');
        
        if (!modal || !btn) return;
        
        const isFullscreen = modal.classList.contains('fullscreen');
        
        if (isFullscreen) {
            modal.classList.remove('fullscreen');
            if (icon) {
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
            }
        } else {
            modal.classList.add('fullscreen');
            if (icon) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            }
        }
    }
    
    function setupViewModalFullscreen() {
        const modal = document.getElementById('viewModal');
        const btn = document.getElementById('viewModalFullscreenBtn');
        
        if (!modal || !btn) {
            setTimeout(setupViewModalFullscreen, 100);
            return;
        }
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleFullscreen('viewModal');
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            if (modal.classList.contains('fullscreen')) {
                modal.classList.remove('fullscreen');
                const icon = btn.querySelector('.modal-fullscreen-icon');
                if (icon) {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            }
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupViewModalFullscreen);
    } else {
        setupViewModalFullscreen();
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
