<?php
/**
 * Wraith Character Admin Panel
 * Displays table of all Wraith characters with management options
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/verify_role.php';
$user_role = verifyUserRole(null, $user_id);
if (!isAdminUser($user_role)) {
    header('Location: ../login.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Helper function to get highest Arcanoi rating
function getHighestArcanoi($arcanoiJson) {
    if (empty($arcanoiJson)) return '—';
    $arcanoi = json_decode($arcanoiJson, true);
    if (!is_array($arcanoi) || empty($arcanoi)) return '—';
    
    $maxRating = 0;
    $maxName = '';
    foreach ($arcanoi as $arc) {
        if (isset($arc['rating']) && $arc['rating'] > $maxRating) {
            $maxRating = $arc['rating'];
            $maxName = $arc['name'] ?? '';
        }
    }
    
    return $maxRating > 0 ? ($maxName ? "$maxName $maxRating" : "Rating $maxRating") : '—';
}

// Helper function to get fetter summary
function getFetterSummary($fettersJson) {
    if (empty($fettersJson)) return '0';
    $fetters = json_decode($fettersJson, true);
    if (!is_array($fetters) || empty($fetters)) return '0';
    
    $count = count($fetters);
    $maxRating = 0;
    foreach ($fetters as $fetter) {
        if (isset($fetter['rating']) && $fetter['rating'] > $maxRating) {
            $maxRating = $fetter['rating'];
        }
    }
    
    return $maxRating > 0 ? "$count (max $maxRating)" : "$count";
}

// Helper function to format pathos/corpus
function formatPathosCorpus($pathosJson) {
    if (empty($pathosJson)) return '—';
    $data = json_decode($pathosJson, true);
    if (!is_array($data)) return '—';
    
    $pathos = ($data['pathos_current'] ?? 0) . '/' . ($data['pathos_max'] ?? 0);
    $corpus = ($data['corpus_current'] ?? 0) . '/' . ($data['corpus_max'] ?? 0);
    
    return "P:$pathos C:$corpus";
}

// Helper function to format angst
function formatAngst($shadowJson) {
    if (empty($shadowJson)) return '—';
    $shadow = json_decode($shadowJson, true);
    if (!is_array($shadow)) return '—';
    
    $current = $shadow['angst_current'] ?? 0;
    $permanent = $shadow['angst_permanent'] ?? 0;
    
    return "$current/$permanent";
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Wraith Character Management</h1>
            
            <!-- Character Table -->
            <div class="character-table-wrapper table-responsive rounded-3">
                <table class="character-table table table-dark table-hover align-middle mb-0" id="wraithCharacterTable">
                    <thead>
                        <tr>
                            <th data-sort="character_name" class="text-start">Name <span class="sort-icon">⇅</span></th>
                            <th data-sort="shadow_name" class="text-start">Shadow Name <span class="sort-icon">⇅</span></th>
                            <th data-sort="guild" class="text-center text-nowrap">Guild <span class="sort-icon">⇅</span></th>
                            <th data-sort="circle" class="text-center text-nowrap">Circle <span class="sort-icon">⇅</span></th>
                            <th data-sort="legion_at_death" class="text-center text-nowrap">Legion <span class="sort-icon">⇅</span></th>
                            <th data-sort="date_of_death" class="text-center text-nowrap">Date of Death <span class="sort-icon">⇅</span></th>
                            <th class="text-center text-nowrap">Highest Arcanoi</th>
                            <th class="text-center text-nowrap">Pathos/Corpus</th>
                            <th class="text-center text-nowrap">Angst</th>
                            <th class="text-center text-nowrap">Fetters</th>
                            <th data-sort="status" class="text-center text-nowrap">Status <span class="sort-icon">⇅</span></th>
                            <th class="text-center text-nowrap" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $char_rows = supabase_table_get('wraith_characters', ['select' => '*', 'order' => 'id.desc']);
                        } catch (Throwable $e) {
                            $char_rows = [];
                        }
                        $currentAdminUrl = $_SERVER['REQUEST_URI'] ?? '/admin/wraith_admin_panel.php';
                        $encodedReturnUrl = rawurlencode($currentAdminUrl);
                        
                        if (empty($char_rows)) {
                            echo "<tr><td colspan='12' class='text-center'>No Wraith characters found.</td></tr>";
                        } else {
                            foreach ($char_rows as $char) {
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
                                
                                $owner = '—';
                                $dateOfDeath = $char['date_of_death'] ? date('Y-m-d', strtotime($char['date_of_death'])) : '—';
                        ?>
                            <tr class="character-row" 
                                data-id="<?php echo $char['id']; ?>"
                                data-type="<?php echo $is_npc ? 'npc' : 'pc'; ?>" 
                                data-name="<?php echo htmlspecialchars($char['character_name']); ?>"
                                data-guild="<?php echo htmlspecialchars($char['guild'] ?? ''); ?>"
                                data-status="<?php echo htmlspecialchars($status); ?>">
                                <td class="character-cell align-top text-light">
                                    <strong><?php echo htmlspecialchars($char['character_name']); ?></strong>
                                </td>
                                <td class="align-top text-light">
                                    <?php echo htmlspecialchars($char['shadow_name'] ?? '—'); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($char['guild'] ?? '—'); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($char['circle'] ?? '—'); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($char['legion_at_death'] ?? '—'); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars($dateOfDeath); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars(getHighestArcanoi($char['arcanoi'] ?? '')); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars(formatPathosCorpus($char['pathos_corpus'] ?? '')); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars(formatAngst($char['shadow'] ?? '')); ?>
                                </td>
                                <td class="align-top text-center text-nowrap">
                                    <?php echo htmlspecialchars(getFetterSummary($char['fetters'] ?? '')); ?>
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
                                                data-type="wraith"
                                                title="Delete Character">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include character view modal with Wraith API endpoint
$apiEndpoint = '/admin/view_wraith_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';
?>

<!-- Edit Wraith Character Modal -->
<div class="modal fade" id="editWraithCharacterModal" tabindex="-1" aria-labelledby="editWraithCharacterModalLabel" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width: 100%; width: 100%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWraithCharacterModalLabel">Edit Wraith Character</h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: calc(100vh - 120px);">
                <iframe id="editWraithCharacterIframe" 
                        src="" 
                        style="width: 100%; height: 100%; border: none;"
                        title="Edit Wraith Character"></iframe>
            </div>
        </div>
    </div>
</div>

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
            const iframeUrl = '../wraith_char_create.php?id=' + encodeURIComponent(characterId) + 
                             '&returnUrl=' + encodeURIComponent(returnUrl) + 
                             '&modal=1';
            
            // Get modal and iframe elements
            const modalEl = document.getElementById('editWraithCharacterModal');
            const iframeEl = document.getElementById('editWraithCharacterIframe');
            
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
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

