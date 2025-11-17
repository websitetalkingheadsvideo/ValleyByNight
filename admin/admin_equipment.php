<?php
/**
 * Admin Equipment Management
 * CRUD operations for equipment database with character assignment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.6.3');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/header.php';

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

<div class="admin-equipment-container">
    <h1 class="panel-title">⚔️ Equipment Management</h1>
    <p class="panel-subtitle">Create and manage equipment, assign to characters</p>
    
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="filter-buttons">
            <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Equipment</button>
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
            <input type="text" id="equipmentSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by name..." />
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

    <!-- Add Equipment Button -->
    <div style="margin-bottom: 20px;">
        <button class="modal-btn confirm-btn btn btn-primary" onclick="openAddEquipmentModal()">
            <i class="fas fa-plus"></i> Add New Equipment
        </button>
    </div>

    <!-- Equipment Table -->
    <div class="equipment-table-wrapper table-responsive">
        <table class="equipment-table table table-dark table-hover align-middle" id="equipmentTable">
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

<!-- Add/Edit Equipment Modal -->
<div id="equipmentModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="equipmentModalTitle" aria-describedby="equipmentForm">
    <div class="modal-content large-modal">
        <h2 class="modal-title">⚔️ <span id="equipmentModalTitle">Add New Equipment</span></h2>
        <button class="modal-close" onclick="closeEquipmentModal()">×</button>
        
        <form id="equipmentForm" class="needs-validation" novalidate>
            <input type="hidden" id="equipmentId" name="id">
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentName" class="form-label">Name *</label>
                    <input type="text" id="equipmentName" name="name" class="form-control" required>
                    <div class="invalid-feedback">Please enter an equipment name.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentType" class="form-label">Type *</label>
                    <select id="equipmentType" name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <?php foreach ($item_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a type.</div>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentCategory" class="form-label">Category *</label>
                    <select id="equipmentCategory" name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($item_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentRarity" class="form-label">Rarity *</label>
                    <select id="equipmentRarity" name="rarity" class="form-select" required>
                        <option value="">Select Rarity</option>
                        <option value="common">Common</option>
                        <option value="uncommon">Uncommon</option>
                        <option value="rare">Rare</option>
                        <option value="epic">Epic</option>
                        <option value="legendary">Legendary</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentDamage" class="form-label">Damage</label>
                    <input type="text" id="equipmentDamage" name="damage" class="form-control" placeholder="e.g., 2L, 3B">
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="equipmentRange" class="form-label">Range</label>
                    <input type="text" id="equipmentRange" name="range" class="form-control" placeholder="e.g., Close, Medium">
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="equipmentRequirements" class="form-label">Requirements</label>
                <textarea id="equipmentRequirements" name="requirements" class="form-control" rows="3" placeholder='e.g., strength: 3, dexterity: 2'></textarea>
                <small class="form-text text-muted" style="color: #d4c4b0; font-size: 0.85em;">Format: attribute: value, attribute2: value2</small>
            </div>
            
            <div class="form-group mb-3">
                <label for="equipmentPrice" class="form-label">Price *</label>
                <input type="number" id="equipmentPrice" name="price" class="form-control" required min="0">
                <div class="invalid-feedback">Please provide a valid price.</div>
            </div>
            
            <div class="form-group mb-3">
                <label for="equipmentDescription" class="form-label">Description *</label>
                <textarea id="equipmentDescription" name="description" class="form-control" required></textarea>
                <div class="invalid-feedback">Please enter a description.</div>
            </div>
            
            <div class="form-group mb-3">
                <label for="equipmentImage" class="form-label">Image URL</label>
                <input type="url" id="equipmentImage" name="image" class="form-control" placeholder="https://example.com/image.jpg">
            </div>
            
            <div class="form-group mb-3">
                <label for="equipmentNotes" class="form-label">Notes</label>
                <textarea id="equipmentNotes" name="notes" class="form-control"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="modal-btn cancel-btn btn btn-secondary" onclick="closeEquipmentModal()">Cancel</button>
                <button type="button" id="assignEquipmentBtn" class="modal-btn btn btn-success" onclick="openAssignModalFromEdit()" style="display: none;">
                    <i class="fas fa-user-plus"></i> Assign to Characters
                </button>
                <button type="submit" class="modal-btn confirm-btn btn btn-primary">Save Equipment</button>
            </div>
        </form>
    </div>
</div>

<!-- View Equipment Modal -->
<div id="viewModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="viewEquipmentName" aria-describedby="viewEquipmentContent">
    <div class="modal-content large-modal">
        <h2 class="modal-title">📄 <span id="viewEquipmentName">Equipment Details</span></h2>
        <button class="modal-close" onclick="closeViewModal()">×</button>
        
        <div id="viewEquipmentContent" class="view-content" aria-live="polite">
            Loading...
        </div>
        <div class="modal-actions">
            <button class="modal-btn cancel-btn btn btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Character Assignment Modal -->
<div id="assignModal" class="modal" role="dialog" aria-modal="true" aria-label="Assign Equipment to Characters" aria-describedby="assignEquipmentDesc">
    <div class="modal-content">
        <h2 class="modal-title">🎯 Assign Equipment to Characters</h2>
        <button class="modal-close" onclick="closeAssignModal()">×</button>
        
        <div class="modal-message" id="assignEquipmentDesc">
            Assign <strong id="assignEquipmentName"></strong> to characters:
        </div>
        
        <div class="character-selection" id="characterSelection">
            <!-- Populated by JavaScript -->
        </div>
        
        <div class="modal-actions">
            <button class="modal-btn cancel-btn btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
            <button class="modal-btn confirm-btn btn btn-primary" onclick="saveAssignments()">Save Assignments</button>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal" role="dialog" aria-modal="true" aria-label="Confirm Deletion" aria-describedby="deleteEquipmentName deleteWarning">
    <div class="modal-content">
        <h2 class="modal-title">⚠️ Confirm Deletion</h2>
        <p class="modal-message">Delete equipment:</p>
        <p class="modal-character-name" id="deleteEquipmentName"></p>
        <p class="modal-warning" id="deleteWarning" style="display:none;">
            ⚠️ <strong>This equipment is assigned to characters</strong> - remove assignments first!
        </p>
        <div class="modal-actions">
            <button class="modal-btn cancel-btn btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="modal-btn confirm-btn btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Include external CSS -->
<link rel="stylesheet" href="../css/modal.css">
<link rel="stylesheet" href="../css/admin_equipment.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Pass PHP data to JavaScript -->
<script>
    const allCharacters = <?php echo json_encode($all_characters); ?>;
    const itemTypes = <?php echo json_encode($item_types); ?>;
    const itemCategories = <?php echo json_encode($item_categories); ?>;
</script>

<!-- Include the external JavaScript file -->
<script src="../js/admin_equipment.js"></script>
<script src="../js/form_validation.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
