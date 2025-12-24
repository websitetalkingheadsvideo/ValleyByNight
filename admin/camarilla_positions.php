<?php
/**
 * Camarilla Positions Management
 * Display current position holders and provide agent interface for queries
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.6.2');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

$extra_css = ['css/modal.css', 'css/admin_camarilla_positions.css'];
include __DIR__ . '/../includes/header.php';

// Get default night
$default_night = CAMARILLA_DEFAULT_NIGHT;

// Get all positions with current holders
$positions_data = get_all_positions_with_current_holders($default_night);

// Get vacant positions - filter from positions_data (matching table display logic)
$vacant_positions = [];
foreach ($positions_data as $data) {
    $holders = $data['current_holders'] ?? [];
    // Match table logic exactly: if empty holders array shows "Vacant"
    if (empty($holders)) {
        $vacant_positions[] = $data['position'];
    }
}

// Get unique categories for filter
$categories_query = "SELECT DISTINCT category FROM camarilla_positions WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = db_fetch_all($conn, $categories_query);
$categories = [];
foreach ($categories_result as $cat) {
    $categories[] = $cat['category'];
}

// Get all positions for dropdown
$all_positions_query = "SELECT position_id, name, category FROM camarilla_positions ORDER BY category, name";
$all_positions = db_fetch_all($conn, $all_positions_query);

// Get all characters for dropdown
$characters_query = "SELECT id, character_name, clan FROM characters ORDER BY character_name";
$all_characters = db_fetch_all($conn, $characters_query);


// Handle agent queries
$position_lookup_result = null;
$character_lookup_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['position_lookup'])) {
        $position_id = $_POST['position_id'] ?? '';
        $lookup_night_raw = $_POST['lookup_night'] ?? '';
        // Convert datetime-local format (YYYY-MM-DDTHH:MM) to DATETIME format (YYYY-MM-DD HH:MM:SS)
        if ($lookup_night_raw) {
            $lookup_night = str_replace('T', ' ', $lookup_night_raw) . ':00';
        } else {
            $lookup_night = $default_night;
        }
        
        if ($position_id) {
            $all_holders = get_all_current_holders_for_position($position_id, $lookup_night);
            $position_lookup_result = [
                'position_id' => $position_id,
                'night' => $lookup_night,
                'current_holders' => $all_holders, // Array of all holders
                'current_holder' => !empty($all_holders) ? $all_holders[0] : null, // First holder for backward compatibility
                'history' => get_position_history($position_id)
            ];
            
            // Get position name
            $pos_info = db_fetch_one($conn, "SELECT name FROM camarilla_positions WHERE position_id = ?", "s", [$position_id]);
            $position_lookup_result['position_name'] = $pos_info['name'] ?? 'Unknown Position';
        }
    }
    
    if (isset($_POST['character_lookup'])) {
        $character_id = $_POST['character_id'] ?? '';
        
        if ($character_id) {
            $character_lookup_result = [
                'character_id' => $character_id,
                'history' => get_character_position_history($character_id)
            ];
            
            // Get character name
            $char_info = db_fetch_one($conn, "SELECT character_name FROM characters WHERE id = ?", "i", [$character_id]);
            $character_lookup_result['character_name'] = $char_info['character_name'] ?? 'Unknown Character';
            
            // Separate current vs past positions
            $current = [];
            $past = [];
            foreach ($character_lookup_result['history'] as $assignment) {
                if ($assignment['end_night'] === null || $assignment['end_night'] >= $default_night) {
                    $current[] = $assignment;
                } else {
                    $past[] = $assignment;
                }
            }
            $character_lookup_result['current_positions'] = $current;
            $character_lookup_result['past_positions'] = $past;
        }
    }
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <h1 class="display-5 text-light fw-bold mb-1">👑 Camarilla Positions</h1>
    <p class="lead text-light fst-italic mb-4">Current position holders and historical assignments</p>
    
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <!-- Filter Controls -->
    <div class="row gy-3 align-items-center mb-4">
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="categoryFilter" class="text-light text-uppercase small mb-0">Category:</label>
            <select id="categoryFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucwords($cat)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="clanFilter" class="text-light text-uppercase small mb-0">Clan:</label>
            <select id="clanFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Clans</option>
                <option value="Brujah">Brujah</option>
                <option value="Gangrel">Gangrel</option>
                <option value="Malkavian">Malkavian</option>
                <option value="Nosferatu">Nosferatu</option>
                <option value="Toreador">Toreador</option>
                <option value="Tremere">Tremere</option>
                <option value="Ventrue">Ventrue</option>
                <option value="Caitiff">Caitiff</option>
            </select>
        </div>
        <div class="col-12 col-lg col-xl-4">
            <input type="text" id="positionSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search positions..." />
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" class="btn btn-outline-warning btn-sm" id="showVacantPositionsBtn" data-bs-toggle="modal" data-bs-target="#vacantPositionsModal">
                📋 Show Vacant Positions (<?php echo count($vacant_positions); ?>)
            </button>
        </div>
    </div>
    
    <!-- Positions Table -->
    <div class="table-responsive rounded-3">
        <table class="table table-dark table-hover align-middle mb-0" id="positionsTable">
            <thead>
                <tr>
                    <th data-sort="name">Position Name <span class="sort-icon">⇅</span></th>
                    <th data-sort="category">Category <span class="sort-icon">⇅</span></th>
                    <th data-sort="holder">Current Holder <span class="sort-icon">⇅</span></th>
                    <th>Status</th>
                    <th data-sort="start">Start Night <span class="sort-icon">⇅</span></th>
                    <th class="text-center text-nowrap" style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions_data as $data): 
                    $position = $data['position'];
                    $holders = $data['current_holders'] ?? []; // Array of holders (supports multiple)
                    $holder = $data['current_holder'] ?? null; // First holder for backward compatibility
                    $has_holders = !empty($holders);
                    $is_multiple = count($holders) > 1;
                    // Get first holder's clan for filtering (or empty if vacant)
                    $first_clan = !empty($holders) ? ($holders[0]['clan'] ?? '') : '';
                ?>
                    <tr class="position-row" 
                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>" 
                        data-category="<?php echo htmlspecialchars($position['category'] ?? ''); ?>"
                        data-clan="<?php echo htmlspecialchars($first_clan); ?>"
                        data-name="<?php echo htmlspecialchars(strtolower($position['name'] ?? '')); ?>">
                        <td>
                            <?php 
                            $description = $position['description'] ?? '';
                            $has_description = !empty(trim($description));
                            ?>
                            <strong 
                                <?php if ($has_description): ?>
                                data-bs-toggle="popover" 
                                data-bs-trigger="hover"
                                data-bs-placement="top"
                                data-bs-content="<?php echo htmlspecialchars($description); ?>"
                                style="cursor: help;"
                                <?php endif; ?>
                            >
                                <?php echo htmlspecialchars($position['name'] ?? 'Unknown'); ?>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars(ucwords($position['category'] ?? '—')); ?></td>
                        <td>
                            <?php if ($has_holders): ?>
                                <?php if ($is_multiple): ?>
                                    <div class="multiple-holders">
                                        <?php foreach ($holders as $idx => $h): ?>
                                            <?php 
                                            $display_name = !empty($h['character_name']) 
                                                ? $h['character_name'] 
                                                : ucwords(strtolower(str_replace('_', ' ', $h['assignment_character_id'] ?? 'Unknown')));
                                            ?>
                                            <?php if ($idx > 0): ?>, <?php endif; ?>
                                            <?php if (!empty($h['character_id'])): ?>
                                                <a href="../lotn_char_create.php?id=<?php echo htmlspecialchars($h['character_id']); ?>" class="character-link">
                                                    <?php echo htmlspecialchars($display_name); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="character-link" title="Character not found in database"><?php echo htmlspecialchars($display_name); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $h = $holders[0];
                                    $display_name = !empty($h['character_name']) 
                                        ? $h['character_name'] 
                                        : ucwords(strtolower(str_replace('_', ' ', $h['assignment_character_id'] ?? 'Unknown')));
                                    ?>
                                    <?php if (!empty($h['character_id'])): ?>
                                        <a href="../lotn_char_create.php?id=<?php echo htmlspecialchars($h['character_id']); ?>" class="character-link">
                                            <?php echo htmlspecialchars($display_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="character-link" title="Character not found in database"><?php echo htmlspecialchars($display_name); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Vacant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_holders): ?>
                                <?php if ($is_multiple): ?>
                                    <span class="badge badge-permanent">Multiple</span>
                                <?php else: ?>
                                    <?php if ($holders[0]['is_acting']): ?>
                                        <span class="badge badge-acting">Acting</span>
                                    <?php else: ?>
                                        <span class="badge badge-permanent">Permanent</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-vacant">Vacant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_holders && !empty($holders[0]['start_night'])): ?>
                                <?php echo date('Y-m-d', strtotime($holders[0]['start_night'])); ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center align-top" style="width: 150px;">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Position actions">
                                <button class="btn btn-primary view-btn" 
                                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>"
                                        title="View Position">👁️</button>
                                <button class="btn btn-warning edit-btn" 
                                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>"
                                        title="Edit Position">✏️</button>
                                <button class="btn btn-danger delete-btn" 
                                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>" 
                                        data-name="<?php echo htmlspecialchars($position['name'] ?? 'Unknown'); ?>"
                                        title="Delete Position">🗑️</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Camarilla Positions Agent Section -->
    <div class="mb-5">
        <h2 class="h4 mb-3">🤖 Ask the Camarilla Positions Agent</h2>
        <p class="text-muted mb-4">Query position holders and historical assignments</p>
        
        <div class="row g-3">
            <!-- Position Lookup Form -->
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Position Lookup</h3>
                    </div>
                    <div class="card-body">
                    <form method="POST" action="" class="agent-form">
                        <input type="hidden" name="position_lookup" value="1">
                        <div class="mb-3">
                            <label for="position_id" class="form-label">Position:</label>
                            <select id="position_id" name="position_id" class="form-select bg-dark text-light border-danger" required>
                                <option value="">Select a position...</option>
                                <?php foreach ($all_positions as $pos): ?>
                                    <option value="<?php echo htmlspecialchars($pos['position_id']); ?>">
                                        <?php echo htmlspecialchars($pos['name']); ?> (<?php echo htmlspecialchars(ucwords($pos['category'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="lookup_night" class="form-label">In-Game Night:</label>
                            <input type="datetime-local" id="lookup_night" name="lookup_night" 
                                   class="form-control bg-dark text-light border-danger" 
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($default_night)); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Lookup Position</button>
                    </form>
                    </div>
                    <?php if ($position_lookup_result): ?>
                        <div class="card-body border-top">
                            <div class="agent-results">
                            <h4>Results for <?php echo htmlspecialchars($position_lookup_result['position_name']); ?></h4>
                            <p><strong>Night:</strong> <?php echo date('Y-m-d H:i', strtotime($position_lookup_result['night'])); ?></p>
                            
                            <div class="current-holder-result">
                                <h5>Current <?php echo count($position_lookup_result['current_holders'] ?? []) > 1 ? 'Holders' : 'Holder'; ?>:</h5>
                                <?php if (!empty($position_lookup_result['current_holders'])): ?>
                                    <?php if (count($position_lookup_result['current_holders']) > 1): ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($position_lookup_result['current_holders'] as $holder): ?>
                                                <li class="mb-2">
                                                    <?php 
                                                    $display_name = !empty($holder['character_name']) 
                                                        ? $holder['character_name'] 
                                                        : ucwords(strtolower(str_replace('_', ' ', $holder['assignment_character_id'] ?? 'Unknown')));
                                                    ?>
                                                    <?php if (!empty($holder['character_id'])): ?>
                                                        <a href="../lotn_char_create.php?id=<?php echo $holder['character_id']; ?>">
                                                            <?php echo htmlspecialchars($display_name); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($display_name); ?>
                                                    <?php endif; ?>
                                                    <?php if ($holder['is_acting']): ?>
                                                        <span class="badge badge-acting">Acting</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-permanent">Permanent</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small>Since: <?php echo date('Y-m-d', strtotime($holder['start_night'])); ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <?php $holder = $position_lookup_result['current_holders'][0]; ?>
                                        <p>
                                            <?php 
                                            $display_name = !empty($holder['character_name']) 
                                                ? $holder['character_name'] 
                                                : ucwords(strtolower(str_replace('_', ' ', $holder['assignment_character_id'] ?? 'Unknown')));
                                            ?>
                                            <?php if (!empty($holder['character_id'])): ?>
                                                <a href="../lotn_char_create.php?id=<?php echo $holder['character_id']; ?>">
                                                    <?php echo htmlspecialchars($display_name); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($display_name); ?>
                                            <?php endif; ?>
                                            <?php if ($holder['is_acting']): ?>
                                                <span class="badge badge-acting">Acting</span>
                                            <?php else: ?>
                                                <span class="badge badge-permanent">Permanent</span>
                                            <?php endif; ?>
                                            <br>
                                            <small>Since: <?php echo date('Y-m-d', strtotime($holder['start_night'])); ?></small>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">Position is vacant</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($position_lookup_result['history'])): ?>
                                <div class="position-history-result mt-3">
                                    <h5>Position History:</h5>
                                    <table class="table table-sm table-dark">
                                        <thead>
                                            <tr>
                                                <th>Holder</th>
                                                <th>Start</th>
                                                <th>End</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($position_lookup_result['history'] as $hist): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($hist['character_name']): ?>
                                                            <?php if (!empty($hist['character_id'])): ?>
                                                                <a href="../lotn_char_create.php?id=<?php echo $hist['character_id']; ?>">
                                                                    <?php echo htmlspecialchars($hist['character_name']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <?php echo htmlspecialchars($hist['character_name']); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Unknown</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('Y-m-d', strtotime($hist['start_night'])); ?></td>
                                                    <td><?php echo $hist['end_night'] ? date('Y-m-d', strtotime($hist['end_night'])) : 'Current'; ?></td>
                                                    <td>
                                                        <?php if ($hist['is_acting']): ?>
                                                            <span class="badge badge-acting">Acting</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-permanent">Permanent</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Character Lookup Form -->
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Character Lookup</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="agent-form">
                            <input type="hidden" name="character_lookup" value="1">
                            <div class="mb-3">
                                <label for="character_id" class="form-label">Character:</label>
                                <select id="character_id" name="character_id" class="form-select bg-dark text-light border-danger" required>
                                    <option value="">Select a character...</option>
                                    <?php foreach ($all_characters as $char): ?>
                                        <option value="<?php echo $char['id']; ?>">
                                            <?php echo htmlspecialchars($char['character_name']); ?> (<?php echo htmlspecialchars($char['clan'] ?? 'Unknown'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Lookup Character</button>
                        </form>
                    </div>
                    <?php if ($character_lookup_result): ?>
                        <div class="card-body border-top">
                            <div class="agent-results">
                            <h4>Results for <?php echo htmlspecialchars($character_lookup_result['character_name']); ?></h4>
                            
                            <?php if (!empty($character_lookup_result['current_positions'])): ?>
                                <div class="current-positions-result mt-3">
                                    <h5>Current Positions:</h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($character_lookup_result['current_positions'] as $pos): ?>
                                            <li>
                                                <strong><?php echo htmlspecialchars($pos['position_name'] ?? 'Unknown'); ?></strong>
                                                (<?php echo htmlspecialchars(ucwords($pos['category'] ?? '')); ?>)
                                                <?php if ($pos['is_acting']): ?>
                                                    <span class="badge badge-acting">Acting</span>
                                                <?php else: ?>
                                                    <span class="badge badge-permanent">Permanent</span>
                                                <?php endif; ?>
                                                <br>
                                                <small>Since: <?php echo date('Y-m-d', strtotime($pos['start_night'])); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No current positions</p>
                            <?php endif; ?>
                            
                            <?php if (!empty($character_lookup_result['past_positions'])): ?>
                                <div class="past-positions-result mt-3">
                                    <h5>Past Positions:</h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($character_lookup_result['past_positions'] as $pos): ?>
                                            <li>
                                                <strong><?php echo htmlspecialchars($pos['position_name'] ?? 'Unknown'); ?></strong>
                                                (<?php echo htmlspecialchars(ucwords($pos['category'] ?? '')); ?>)
                                                <br>
                                                <small>
                                                    <?php echo date('Y-m-d', strtotime($pos['start_night'])); ?> - 
                                                    <?php echo $pos['end_night'] ? date('Y-m-d', strtotime($pos['end_night'])) : 'Current'; ?>
                                                </small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include position view modal
$apiEndpoint = '/admin/view_position_api.php';
$modalId = 'viewPositionModal';
// Pass characters list to modal for dropdown
$modal_characters = $all_characters;
include __DIR__ . '/../includes/position_view_modal.php';
?>

<!-- Vacant Positions Modal -->
<?php
$modalId = 'vacantPositionsModal';
$size = 'lg';
$fullscreen = false;
$scrollable = true;
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Delete Modal -->
<?php
$modalId = 'deletePositionModal';
$size = 'md';
$fullscreen = false;
$scrollable = false;
include __DIR__ . '/../includes/modal_base.php';
?>

<script>
(function() {
    'use strict';
    // Populate delete position modal content after modal_base.php is included
    const modalEl = document.getElementById('deletePositionModal');
    if (modalEl) {
        const modalTitle = modalEl.querySelector('.vbn-modal-title');
        const modalBody = modalEl.querySelector('.vbn-modal-body');
        const modalFooter = modalEl.querySelector('.vbn-modal-footer');
        
        if (modalTitle) {
            modalTitle.textContent = '⚠️ Confirm Deletion';
            modalTitle.id = 'deletePositionModalLabel';
        }
        
        if (modalBody) {
            modalBody.innerHTML = `
                <p class="vbn-modal-message">Delete position:</p>
                <p class="vbn-modal-character-name" id="deletePositionName"></p>
                <p class="vbn-modal-warning" id="deleteWarning" style="display:none;">
                    ⚠️ <strong>Warning</strong> - This position may have assignments!
                </p>
            `;
        }
        
        if (modalFooter) {
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePositionBtn">Delete</button>
            `;
        }
    }
})();
</script>

<!-- Include external JavaScript -->
<script src="../js/admin_camarilla_positions.js"></script>

<script>
// Initialize Bootstrap popovers for position descriptions
(function() {
    'use strict';
    
    let retryCount = 0;
    const maxRetries = 50; // Max 5 seconds of retries
    
    function initPopovers() {
        // Wait for Bootstrap to be available
        if (typeof bootstrap === 'undefined' || !bootstrap.Popover) {
            retryCount++;
            if (retryCount < maxRetries) {
                // Retry after a short delay
                setTimeout(initPopovers, 100);
            } else {
                console.warn('Bootstrap Popover not available after retries');
            }
            return;
        }
        
        try {
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (popoverTriggerEl) {
                try {
                    const content = popoverTriggerEl.getAttribute('data-bs-content');
                    // Only initialize if content exists and is not empty
                    if (content && content.trim() !== '' && content.trim() !== 'No description available') {
                        const positionName = popoverTriggerEl.textContent.trim() || 'Position';
                        new bootstrap.Popover(popoverTriggerEl, {
                            html: true,
                            container: 'body',
                            title: positionName,
                            content: content
                        });
                    } else {
                        // Remove popover attributes if no valid content
                        popoverTriggerEl.removeAttribute('data-bs-toggle');
                        popoverTriggerEl.removeAttribute('data-bs-content');
                        popoverTriggerEl.style.cursor = '';
                    }
                } catch (e) {
                    console.error('Error initializing popover:', e, popoverTriggerEl);
                }
            });
        } catch (e) {
            console.error('Error initializing popovers:', e);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPopovers);
    } else {
        initPopovers();
    }
})();
</script>

<script>
// Populate vacant positions modal content
(function() {
    'use strict';
    
    const modal = document.getElementById('vacantPositionsModal');
    if (!modal) return;
    
    const modalTitle = modal.querySelector('.vbn-modal-title');
    const modalBody = modal.querySelector('.vbn-modal-body');
    const modalFooter = modal.querySelector('.vbn-modal-footer');
    
    // Build modal content in PHP, set via JavaScript
    const vacantPositionsHtml = <?php 
        if (count($vacant_positions) > 0) {
            // Group by category
            $grouped = [];
            foreach ($vacant_positions as $pos) {
                $category = $pos['category'] ?? 'Uncategorized';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $pos;
            }
            ksort($grouped);
            
            $html = '<p class="text-light mb-3">There are <strong>' . count($vacant_positions) . '</strong> vacant position(s) in the Camarilla:</p>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-dark table-hover table-sm">';
            $html .= '<thead><tr><th>Position Name</th><th>Category</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($grouped as $category => $positions) {
                foreach ($positions as $pos) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . htmlspecialchars($pos['name'] ?? 'Unknown', ENT_QUOTES) . '</strong></td>';
                    $html .= '<td>' . htmlspecialchars(ucwords($category), ENT_QUOTES) . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</tbody></table></div>';
            echo json_encode($html, JSON_HEX_QUOT | JSON_HEX_APOS);
        } else {
            echo json_encode('<p class="text-light">No vacant positions found. All positions are currently filled.</p>');
        }
    ?>;
    
    if (modalTitle) {
        modalTitle.textContent = '📋 Vacant Camarilla Positions';
    }
    
    if (modalBody) {
        modalBody.innerHTML = vacantPositionsHtml;
    }
    
    if (modalFooter) {
        modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

