<?php
/**
 * Ghoul Character Admin Panel
 * Displays table of all Ghoul characters with management options
 */
declare(strict_types=1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Helper function to get domitor name
function getDomitorName($conn, $domitorId) {
    if (empty($domitorId)) return '—';
    $stmt = $conn->prepare("SELECT character_name FROM characters WHERE id = ? LIMIT 1");
    if (!$stmt) return '—';
    $stmt->bind_param('i', $domitorId);
    if (!$stmt->execute()) {
        $stmt->close();
        return '—';
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? htmlspecialchars($row['character_name']) : '—';
}

// Helper function to format blood bond stage
function formatBloodBondStage($stage) {
    if ($stage === null || $stage === '') return '—';
    $stage = (int)$stage;
    if ($stage === 0) return '0 (None)';
    if ($stage === 1) return '1 (Weak)';
    if ($stage === 2) return '2 (Strong)';
    if ($stage === 3) return '3 (Complete)';
    return (string)$stage;
}

// Helper function to get highest discipline rating
function getHighestDiscipline($disciplinesJson) {
    if (empty($disciplinesJson)) return '—';
    $disciplines = json_decode($disciplinesJson, true);
    if (!is_array($disciplines) || empty($disciplines)) return '—';
    
    $maxRating = 0;
    $maxName = '';
    foreach ($disciplines as $disc) {
        $rating = 0;
        if (isset($disc['dots'])) {
            $rating = (int)$disc['dots'];
        } elseif (isset($disc['rating'])) {
            $rating = (int)$disc['rating'];
        } elseif (isset($disc['level'])) {
            $rating = (int)$disc['level'];
        }
        
        if ($rating > $maxRating) {
            $maxRating = $rating;
            $maxName = $disc['name'] ?? '';
        }
    }
    
    return $maxRating > 0 ? ($maxName ? "$maxName $maxRating" : "Rating $maxRating") : '—';
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Ghoul Character Management</h1>
            
            <!-- Character Statistics -->
            <div class="character-stats row g-3 mb-4">
                <?php
                $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pc = 0 THEN 1 ELSE 0 END) as npcs,
                    SUM(CASE WHEN pc = 1 THEN 1 ELSE 0 END) as pcs
                    FROM characters WHERE clan = 'Ghoul'";
                $stats_result = mysqli_query($conn, $stats_query);
                $stats = mysqli_fetch_assoc($stats_result);
                ?>
                <div class="col-12 col-sm-4 col-lg-3">
                    <div class="card text-center bg-dark border-danger text-light">
                        <div class="card-body">
                            <div class="vbn-stat-number h2 fw-bold" id="statTotal"><?php echo $stats['total'] ?? 0; ?></div>
                            <div class="vbn-stat-label text-uppercase small opacity-75">Total Ghouls</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4 col-lg-3">
                    <div class="card text-center bg-dark border-danger text-light">
                        <div class="card-body">
                            <div class="vbn-stat-number h2 fw-bold" id="statPcs"><?php echo $stats['pcs'] ?? 0; ?></div>
                            <div class="vbn-stat-label text-uppercase small opacity-75">PC Ghouls</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4 col-lg-3">
                    <div class="card text-center bg-dark border-danger text-light">
                        <div class="card-body">
                            <div class="vbn-stat-number h2 fw-bold" id="statNpcs"><?php echo $stats['npcs'] ?? 0; ?></div>
                            <div class="vbn-stat-label text-uppercase small opacity-75">NPC Ghouls</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls row gy-3 align-items-center mb-4">
                <div class="filter-buttons col-12 col-md-auto d-flex flex-wrap gap-2">
                    <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Ghouls</button>
                    <button class="filter-btn btn btn-outline-danger" data-filter="pcs">PCs Only</button>
                    <button class="filter-btn btn btn-outline-danger" data-filter="npcs">NPCs Only</button>
                </div>
                <div class="search-box col-12 col-lg col-xl-4">
                    <input type="text" id="characterSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search name, player, status, domitor..." />
                </div>
                <div class="page-size-control col-12 col-md-auto d-flex align-items-center gap-2">
                    <label for="pageSize" class="text-light text-uppercase small mb-0">Per page:</label>
                    <select id="pageSize" class="form-select form-select-sm bg-dark text-light border-danger">
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
            
            <!-- Character Table -->
            <div class="character-table-wrapper table-responsive rounded-3">
                <table class="character-table table table-dark table-hover align-middle mb-0" id="ghoulCharacterTable">
                    <thead>
                        <tr>
                            <th data-sort="character_name" class="text-start">Name <span class="sort-icon">⇅</span></th>
                            <th data-sort="player_name" class="text-center text-nowrap">Player <span class="sort-icon">⇅</span></th>
                            <th class="text-center text-nowrap">Domitor</th>
                            <th class="text-center text-nowrap">Blood Bond</th>
                            <th class="text-center text-nowrap">Highest Discipline</th>
                            <th data-sort="status" class="text-center text-nowrap">Status <span class="sort-icon">⇅</span></th>
                            <th class="text-center text-nowrap" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $char_query = "SELECT c.*, g.domitor_character_id, 
                                      g.blood_bond_stage, g.is_active, g.retainer_level,
                                      u.username as owner_username
                                      FROM characters c
                                      LEFT JOIN ghouls g ON c.id = g.character_id
                                      LEFT JOIN users u ON c.user_id = u.id
                                      WHERE c.clan = 'Ghoul'
                                      ORDER BY c.id DESC";
                        $char_result = mysqli_query($conn, $char_query);
                        $currentAdminUrl = $_SERVER['REQUEST_URI'] ?? '/admin/ghoul_admin_panel.php';
                        $encodedReturnUrl = rawurlencode($currentAdminUrl);
                        
                        if (!$char_result) {
                            echo "<tr><td colspan='7'>Query Error: " . mysqli_error($conn) . "</td></tr>";
                        } elseif (mysqli_num_rows($char_result) > 0) {
                            while ($char = mysqli_fetch_assoc($char_result)) {
                                $is_npc = ($char['pc'] == 0);
                                $playerName = trim($char['player_name'] ?? '') !== '' ? $char['player_name'] : ($is_npc ? 'NPC' : '—');
                                $statusRaw = trim((string)($char['status'] ?? ''));
                                $status = strtolower($statusRaw);
                                if ($status === '') {
                                    $status = 'active';
                                }
                                
                                $allowedStatuses = ['active', 'inactive', 'archived', 'dead', 'missing', 'npc', 'unknown'];
                                if (!in_array($status, $allowedStatuses, true)) {
                                    $status = 'unknown';
                                }
                                
                                // Get domitor ID from domitor_character_id
                                $domitorId = null;
                                if (!empty($char['domitor_character_id'])) {
                                    $domitorId = (int)$char['domitor_character_id'];
                                }
                                
                                $domitorName = $domitorId ? getDomitorName($conn, $domitorId) : '—';
                                $bloodBondStage = formatBloodBondStage($char['blood_bond_stage'] ?? null);
                                $highestDiscipline = getHighestDiscipline($char['disciplines'] ?? '');
                        ?>
                            <tr class="character-row" 
                                data-id="<?php echo $char['id']; ?>"
                                data-type="<?php echo $is_npc ? 'npc' : 'pc'; ?>" 
                                data-name="<?php echo htmlspecialchars($char['character_name']); ?>"
                                data-player="<?php echo htmlspecialchars($playerName); ?>"
                                data-status="<?php echo htmlspecialchars($status); ?>"
                                data-clan="Ghoul"
                                data-owner="<?php echo htmlspecialchars($char['owner_username'] ?? '—'); ?>"
                                data-domitor="<?php echo htmlspecialchars($domitorName); ?>">
                                <td class="character-cell align-top text-light">
                                    <strong><?php echo htmlspecialchars($char['character_name']); ?></strong>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($playerName); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo $domitorName; ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($bloodBondStage); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($highestDiscipline); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php
                                    $statusBadge = '';
                                    if ($status === 'active') {
                                        $statusBadge = '<span class="badge bg-success">Active</span>';
                                    } elseif ($status === 'inactive') {
                                        $statusBadge = '<span class="badge bg-secondary">Inactive</span>';
                                    } elseif ($status === 'archived') {
                                        $statusBadge = '<span class="badge bg-dark">Archived</span>';
                                    } else {
                                        $statusBadge = '<span class="badge bg-warning">' . htmlspecialchars(ucfirst($status)) . '</span>';
                                    }
                                    echo $statusBadge;
                                    ?>
                                </td>
                                <td class="actions text-center align-top" style="width: 150px;">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Character actions">
                                        <button class="action-btn view-btn btn btn-primary" 
                                                data-id="<?php echo $char['id']; ?>"
                                                title="View Character">👁️</button>
                                        <button class="action-btn edit-btn btn btn-warning" 
                                                data-id="<?php echo $char['id']; ?>"
                                                data-return-url="<?php echo $encodedReturnUrl; ?>"
                                                title="Edit Character">✏️</button>
                                        <button class="action-btn delete-btn btn btn-danger" 
                                                data-id="<?php echo $char['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($char['character_name']); ?>"
                                                data-type="character"
                                                title="Delete Character">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No Ghoul characters found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="pagination-controls d-flex flex-column flex-lg-row gap-3 align-items-center justify-content-between mt-4 pb-4" id="paginationControls">
                <div class="pagination-info fw-semibold text-light">
                    <span id="paginationInfo">Showing 1-20 of <?php echo $stats['total'] ?? 0; ?> characters</span>
                </div>
                <div class="pagination-buttons d-flex flex-wrap gap-2 justify-content-center" id="paginationButtons">
                    <!-- Buttons will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include character view modal with standard character API endpoint
$apiEndpoint = '/admin/view_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';

// Include delete modal
$modalId = 'deleteModal';
include __DIR__ . '/../includes/modal_base.php';
?>

<!-- Edit Ghoul Character Modal -->
<div class="modal fade" id="editGhoulCharacterModal" tabindex="-1" aria-labelledby="editGhoulCharacterModalLabel" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width: 100%; width: 100%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGhoulCharacterModalLabel">Edit Ghoul Character</h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: calc(100vh - 120px);">
                <iframe id="editGhoulCharacterIframe" 
                        src="" 
                        style="width: 100%; height: 100%; border: none;"
                        title="Edit Ghoul Character"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Populate delete modal content after modal_base.php is included
(function() {
    'use strict';
    const modalEl = document.getElementById('deleteModal');
    if (modalEl) {
        const modalTitle = modalEl.querySelector('.vbn-modal-title');
        const modalBody = modalEl.querySelector('.vbn-modal-body');
        const modalFooter = modalEl.querySelector('.vbn-modal-footer');
        
        if (modalTitle) {
            modalTitle.textContent = '⚠️ Confirm Deletion';
            modalTitle.id = 'deleteModalLabel';
        }
        
        if (modalBody) {
            modalBody.innerHTML = `
                <p class="vbn-modal-message">Delete character:</p>
                <p class="vbn-modal-character-name" id="deleteCharacterName"></p>
                <p class="vbn-modal-warning hidden" id="deleteWarning">
                    ⚠️ <strong>Finalized character</strong> - all data will be lost!
                </p>
            `;
        }
        
        if (modalFooter) {
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            `;
        }
    }
})();
</script>

<!-- Include the external JavaScript file for admin panel functionality -->
<script src="../js/admin_panel.js"></script>

<script>
// Initialize view buttons to use shared modal
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (window.viewCharacter && this.dataset.id) {
                window.viewCharacter(this.dataset.id);
            }
        });
    });
    
    // Initialize edit buttons to open modal with iframe
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const characterId = this.dataset.id;
            const returnUrl = this.dataset.returnUrl || '';
            
            if (!characterId) {
                console.error('Edit button missing character ID');
                return;
            }
            
            // Build iframe URL
            const iframeUrl = '../lotn_char_create.php?id=' + encodeURIComponent(characterId) + 
                             '&returnUrl=' + encodeURIComponent(returnUrl) + 
                             '&modal=1';
            
            // Get modal and iframe elements
            const modalEl = document.getElementById('editGhoulCharacterModal');
            const iframeEl = document.getElementById('editGhoulCharacterIframe');
            
            if (!modalEl || !iframeEl) {
                console.error('Edit modal or iframe not found');
                return;
            }
            
            // Check if Bootstrap is available
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                console.error('Bootstrap modal runtime not loaded');
                return;
            }
            
            // Set iframe source
            iframeEl.src = iframeUrl;
            
            // Show modal
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: true,
                focus: true
            });
            modalInstance.show();
            
            // Clear iframe source when modal is hidden to prevent lingering content
            modalEl.addEventListener('hidden.bs.modal', function clearIframe() {
                iframeEl.src = '';
                modalEl.removeEventListener('hidden.bs.modal', clearIframe);
            }, { once: true });
        });
    });
    
    // Initialize delete buttons (from admin_panel.js)
    if (typeof initializeDeleteButtons === 'function') {
        initializeDeleteButtons();
    }
    
    // Handle delete success - reload page to refresh table
    if (typeof handleDeleteSuccess === 'function') {
        // Override the default handleDeleteSuccess to reload the page
        window.handleDeleteSuccess = function(characterId) {
            location.reload();
        };
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

