<?php
/**
 * Admin Items Management
 * CRUD operations for items database and character equipment assignment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.2.2');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
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
$stats = mysqli_fetch_assoc($stats_result);

// Get all unique types and categories for filters
$types_query = "SELECT DISTINCT type FROM items ORDER BY type";
$types_result = mysqli_query($conn, $types_query);
$item_types = [];
while ($type_row = $types_result->fetch_assoc()) {
    $item_types[] = $type_row['type'];
}

$categories_query = "SELECT DISTINCT category FROM items ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);
$item_categories = [];
while ($cat_row = $categories_result->fetch_assoc()) {
    $item_categories[] = $cat_row['category'];
}

// Get all characters for equipment assignment
$characters_query = "SELECT id, character_name, clan, player_name FROM characters ORDER BY character_name";
$characters_result = mysqli_query($conn, $characters_query);
$all_characters = [];
while ($char = $characters_result->fetch_assoc()) {
    $all_characters[] = $char;
}
?>

<div class="admin-items-container">
    <h1 class="panel-title">⚔️ Items Database Management</h1>
    <p class="panel-subtitle">Manage items database and assign equipment to characters</p>
    
    <!-- Admin Navigation -->
    <nav class="admin-nav" aria-label="Admin Navigation">
        <a href="admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👥 Characters</a>
        <a href="admin_sire_childe.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🧛 Sire/Childe</a>
        <a href="admin_items.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center active">⚔️ Items</a>
        <a href="admin_locations.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📍 Locations</a>
        <a href="questionnaire_admin.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📝 Questionnaire</a>
        <a href="admin_npc_briefing.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📋 NPC Briefing</a>
    </nav>
    
    <!-- Items Statistics -->
    <div class="items-stats">
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['total'] ?? 0; ?></span>
            <span class="stat-label">Total Items</span>
        </div>
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['weapons'] ?? 0; ?></span>
            <span class="stat-label">Weapons</span>
        </div>
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['armor'] ?? 0; ?></span>
            <span class="stat-label">Armor</span>
        </div>
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['tools'] ?? 0; ?></span>
            <span class="stat-label">Tools</span>
        </div>
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['consumables'] ?? 0; ?></span>
            <span class="stat-label">Consumables</span>
        </div>
        <div class="stat-mini">
            <span class="stat-number"><?php echo $stats['artifacts'] ?? 0; ?></span>
            <span class="stat-label">Artifacts</span>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="filter-buttons">
            <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Items</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="weapons">Weapons</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="armor">Armor</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="tools">Tools</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="consumables">Consumables</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="artifacts">Artifacts</button>
        </div>
        <div class="type-filter">
            <label for="typeFilter">Type:</label>
            <select id="typeFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Types</option>
                <?php foreach ($item_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rarity-filter">
            <label for="rarityFilter">Rarity:</label>
            <select id="rarityFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Rarities</option>
                <option value="common">Common</option>
                <option value="uncommon">Uncommon</option>
                <option value="rare">Rare</option>
                <option value="epic">Epic</option>
                <option value="legendary">Legendary</option>
            </select>
        </div>
        <div class="search-box">
            <input type="text" id="itemSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by name..." />
        </div>
        <div class="page-size-control">
            <label for="pageSize">Per page:</label>
            <select id="pageSize" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <!-- Add Item Button -->
    <div style="margin-bottom: 20px;">
        <button class="modal-btn confirm-btn btn btn-primary" onclick="openAddItemModal()">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>

    <!-- Items Table -->
    <div class="items-table-wrapper table-responsive">
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
                    <th data-sort="created_at">Created <span class="sort-icon">⇅</span></th>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body vbn-modal-body">
                <form id="itemForm" class="needs-validation" novalidate>
            <input type="hidden" id="itemId" name="id">
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemName" class="form-label">Name *</label>
                    <input type="text" id="itemName" name="name" class="form-control" required>
                    <div class="invalid-feedback">Please enter an item name.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemType" class="form-label">Type *</label>
                    <select id="itemType" name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Weapon">Weapon</option>
                        <option value="Armor">Armor</option>
                        <option value="Tool">Tool</option>
                        <option value="Consumable">Consumable</option>
                        <option value="Artifact">Artifact</option>
                        <option value="Misc">Misc</option>
                    </select>
                    <div class="invalid-feedback">Please select a type.</div>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemCategory" class="form-label">Category *</label>
                    <input type="text" id="itemCategory" name="category" class="form-control" required>
                    <div class="invalid-feedback">Please enter a category.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemRarity" class="form-label">Rarity *</label>
                    <select id="itemRarity" name="rarity" class="form-select" required>
                        <option value="">Select Rarity</option>
                        <option value="common">Common</option>
                        <option value="uncommon">Uncommon</option>
                        <option value="rare">Rare</option>
                        <option value="epic">Epic</option>
                        <option value="legendary">Legendary</option>
                    </select>
                    <div class="invalid-feedback">Please select a rarity.</div>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemDamage" class="form-label">Damage</label>
                    <input type="text" id="itemDamage" name="damage" class="form-control" placeholder="e.g., 2L, 3B">
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="itemRange" class="form-label">Range</label>
                    <input type="text" id="itemRange" name="range" class="form-control" placeholder="e.g., Close, Medium">
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="itemPrice" class="form-label">Price *</label>
                <input type="number" id="itemPrice" name="price" class="form-control" required min="0">
                <div class="invalid-feedback">Please provide a valid price.</div>
            </div>
            
            <div class="form-group mb-3">
                <label for="itemDescription" class="form-label">Description *</label>
                <textarea id="itemDescription" name="description" class="form-control" required></textarea>
                <div class="invalid-feedback">Please enter a description.</div>
            </div>
            
            <div class="form-group mb-3">
                <label for="itemRequirements" class="form-label">Requirements (JSON)</label>
                <textarea id="itemRequirements" name="requirements" class="form-control" rows="3" placeholder='{"strength": 3, "dexterity": 2}'></textarea>
                <small class="form-text text-muted" style="color: #d4c4b0; font-size: 0.85em;">Format: JSON object with attribute: value pairs</small>
            </div>
            
            <div class="form-group mb-3">
                <label for="itemImage" class="form-label">Image URL</label>
                <input type="url" id="itemImage" name="image" class="form-control" placeholder="https://example.com/image.jpg">
            </div>
            
            <div class="form-group mb-3">
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

<!-- Include external CSS -->
<link rel="stylesheet" href="../css/admin_items.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Pass PHP data to JavaScript -->
<script>
    const allCharacters = <?php echo json_encode($all_characters); ?>;
    const itemTypes = <?php echo json_encode($item_types); ?>;
    const itemCategories = <?php echo json_encode($item_categories); ?>;
</script>

<!-- Include the external JavaScript file -->
<script src="../js/admin_items.js"></script>
<script src="../js/form_validation.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
