<?php
/**
 * Admin Panel - Character Management
 */
// Updated 2025-11-09: VbN Agents Page Styling + Link Integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.10.9');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
$extra_css = ['css/modal.css', 'css/admin-agents.css', 'css/admin_panel.css', 'css/character_view.css'];
include __DIR__ . '/../includes/header.php';

function render_status_badge($status) {
    $status = strtolower($status ?? '');
    if ($status === 'draft' || $status === 'finalized') {
        return '';
    }

    $classMap = [
        'npc' => 'badge-npc',
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'archived' => 'badge-archived',
        'dead' => 'badge-dead',
        'missing' => 'badge-missing',
        'unknown' => 'badge-neutral'
    ];

    $labelMap = [
        'npc' => 'NPC',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
        'dead' => 'Dead',
        'missing' => 'Missing',
        'unknown' => 'Unknown'
    ];

    if ($status === '' || !isset($labelMap[$status])) {
        return '';
    }

    $class = $classMap[$status] ?? 'badge-neutral';
    $label = $labelMap[$status];

    return sprintf('<span class="%s">%s</span>', $class, htmlspecialchars($label));
}

function render_clan_badge(string $clan): string {
    $name = trim($clan);
    if ($name === '') {
        return '<span class="clan-badge clan-badge-empty">—</span>';
    }

    // Base clans and creature types. Antitribu use a shade close to parent (same hue, darker or variant).
    static $palette = [
        // Vampire clans
        'assamite' => '#2E3192',
        'banu haqim' => '#2E3192',
        'banu haqim (assamite)' => '#2E3192',
        'brujah' => '#B22222',
        'brujah antitribu' => '#8B1A1A',
        'caitiff' => '#708090',
        'followers of set' => '#8B6C37',
        'setite' => '#8B6C37',
        'followers of set antitribu' => '#6B4C27',
        'setite antitribu' => '#6B4C27',
        'daughter of cacophony' => '#FF69B4',
        'daughters of cacophony' => '#FF69B4',
        'gangrel' => '#228B22',
        'gangrel antitribu' => '#1A6B1A',
        'giovanni' => '#556B2F',
        'lasombra' => '#1A1A40',
        'lasombra antitribu' => '#2A2A60',
        'malkavian' => '#6A0DAD',
        'malkavian antitribu' => '#5A0A8D',
        'nosferatu' => '#556B5D',
        'nosferatu antitribu' => '#455B4D',
        'ravnos' => '#008B8B',
        'ravnos antitribu' => '#006B6B',
        'toreador' => '#C71585',
        'toreador antitribu' => '#A71265',
        'tremere' => '#8B008B',
        'tremere antitribu' => '#6B006B',
        'tzimisce' => '#99CC00',
        'tzimisce antitribu' => '#79AC00',
        'ventrue' => '#1F3A93',
        'ventrue antitribu' => '#172A73',
        // Creature types (shown in clan column on main panel)
        'ghoul' => '#8B4513',
        'wraith' => '#4A4A6A',
        'garou' => '#2D5A27',
        'werewolf' => '#2D5A27',
        'mage' => '#6B4E9E',
        'demon' => '#8B2500',
        'mortal' => '#5C5C5C',
        'supernatural' => '#4A3A6A',
        'unknown' => '#5C5C5C',
    ];

    $key = strtolower($name);
    if (isset($palette[$key])) {
        $color = $palette[$key];
        return sprintf(
            '<span class="clan-badge" style="--clan-badge-color:%s;background-color:var(--clan-badge-color);">%s</span>',
            $color,
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );
    }

    // Unknown clan/creature: deterministic color from name (hash to hue, fixed saturation/lightness)
    $hue = (int) abs(crc32($key)) % 360;
    $color = sprintf('hsl(%d, 45%%, 38%%)', $hue);
    return sprintf(
        '<span class="clan-badge" style="--clan-badge-color:%s;background-color:var(--clan-badge-color);">%s</span>',
        $color,
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
    );
}
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <h1 class="panel-title display-5 text-light fw-bold mb-1">🔧 Character Management</h1>
    <p class="panel-subtitle lead text-light fst-italic mb-4">Manage all characters across the chronicle</p>
    
    <!-- Admin Navigation -->
    <nav class="admin-nav row g-2 g-md-3 mb-4" aria-label="Admin Navigation">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center active">👥 Characters</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_sire_childe.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🧛 Sire/Childe</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_equipment.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">⚔️ Equipment</a>
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
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="character_images_audit.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🖼️ Image Audit</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="camarilla_positions.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👑 Positions</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="boon_ledger.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">💎 Boons</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="agents.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👥 Agents</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="rumor_viewer.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">📰 Rumors</a>
        </div>
    </nav>
    
    <!-- Additional Navigation Row: Character Types -->
    <nav class="admin-nav row g-2 g-md-3 mb-4" aria-label="Character Types" id="character-types">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="wraith_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👻 Wraith</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="werewolf_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🐺 Garou</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="ghoul_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🩸 Ghoul</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="mage_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">✨ Mage</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="demon_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">😈 Demon</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="mortal_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🧑 Mortal</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="supernatural_entities_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">🔮 Supernatural</a>
        </div>
    </nav>
    
    <!-- Character Statistics -->
    <div class="character-stats row g-3 mb-4">
    <?php
        try {
            $allChars = supabase_table_get('characters', ['select' => 'id,player_name']);
        } catch (Throwable $e) {
            $allChars = [];
        }
        $total = count($allChars);
        $npcs = count(array_filter($allChars, fn($c) => trim((string)($c['player_name'] ?? '')) === 'NPC'));
        $pcs = $total - $npcs;
        $stats = ['total' => $total, 'npcs' => $npcs, 'pcs' => $pcs];
        ?>
        <div class="col-12 col-sm-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number" id="statTotal"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="vbn-stat-label">Total</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number" id="statPcs"><?php echo $stats['pcs'] ?? 0; ?></div>
                    <div class="vbn-stat-label">PCs</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="vbn-stat-number" id="statNpcs"><?php echo $stats['npcs'] ?? 0; ?></div>
                    <div class="vbn-stat-label">NPCs</div>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-4">
        <h2 class="h5 text-light mb-3 text-uppercase">Agents</h2>
        <div class="row g-2 g-md-3">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="agents.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">👥 Agents Dashboard</a>
            </div>
        </div>
    </section>

    <!-- Questionnaire Statistics -->
    <div class="questionnaire-stats d-flex flex-wrap gap-3 mb-4 align-items-center">
        <h2 class="h6 text-light mb-0">Story Questionnaire</h2>
        <?php
        try {
            $questionnaire_rows = supabase_table_get('questionnaire_questions', ['select' => 'id']);
            $questionnaire_count = count($questionnaire_rows);
        } catch (Throwable $e) {
            $questionnaire_count = 0;
        }
        ?>
        <div class="card text-center min-w-100px">
            <div class="card-body p-3">
                <div class="vbn-stat-number"><?php echo $questionnaire_count; ?></div>
                <div class="vbn-stat-label">Questions</div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <a href="questionnaire_admin.php" class="btn btn-outline-danger btn-sm">📝 Manage</a>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="filter-controls row gy-3 align-items-center mb-4 flex-md-nowrap">
        <div class="filter-buttons col-12 col-md-auto d-flex flex-wrap gap-2">
            <button class="filter-btn btn btn-outline-danger active" data-filter="all">All Characters</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="kindred">Kindred Only</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="pcs">PCs Only</button>
            <button class="filter-btn btn btn-outline-danger" data-filter="npcs">NPCs Only</button>
        </div>
        <div class="clan-filter col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="clanFilter" class="text-light text-uppercase small mb-0">Sort by Clan:</label>
            <select id="clanFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Clans</option>
                <option value="Assamite">Assamite</option>
                <option value="Brujah">Brujah</option>
                <option value="Brujah Antitribu">Brujah Antitribu</option>
                <option value="Caitiff">Caitiff</option>
                <option value="Followers of Set">Followers of Set</option>
                <option value="Followers of Set Antitribu">Followers of Set Antitribu</option>
                <option value="Daughter of Cacophony">Daughter of Cacophony</option>
                <option value="Gangrel">Gangrel</option>
                <option value="Gangrel Antitribu">Gangrel Antitribu</option>
                <option value="Giovanni">Giovanni</option>
                <option value="Lasombra">Lasombra</option>
                <option value="Lasombra Antitribu">Lasombra Antitribu</option>
                <option value="Malkavian">Malkavian</option>
                <option value="Malkavian Antitribu">Malkavian Antitribu</option>
                <option value="Nosferatu">Nosferatu</option>
                <option value="Nosferatu Antitribu">Nosferatu Antitribu</option>
                <option value="Ravnos">Ravnos</option>
                <option value="Ravnos Antitribu">Ravnos Antitribu</option>
                <option value="Toreador">Toreador</option>
                <option value="Toreador Antitribu">Toreador Antitribu</option>
                <option value="Tremere">Tremere</option>
                <option value="Tremere Antitribu">Tremere Antitribu</option>
                <option value="Tzimisce">Tzimisce</option>
                <option value="Tzimisce Antitribu">Tzimisce Antitribu</option>
                <option value="Ventrue">Ventrue</option>
                <option value="Ventrue Antitribu">Ventrue Antitribu</option>
                <option value="Ghoul">Ghoul</option>
                <option value="Wraith">Wraith</option>
                <option value="Garou">Garou</option>
                <option value="Mage">Mage</option>
                <option value="Demon">Demon</option>
                <option value="Mortal">Mortal</option>
            </select>
        </div>
        <div class="search-box col-12 col-md col-lg-3 col-xl-4">
            <input type="text" id="characterSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search name, clan, player, generation..." />
        </div>
        <div class="page-size-control col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="pageSize" class="text-light text-uppercase small mb-0">Per page:</label>
            <select id="pageSize" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="sort-date-control col-12 col-md-auto d-flex align-items-center gap-2">
            <button type="button" id="sortByDateBtn" class="btn btn-outline-danger btn-sm">
                📅 Sort by Date
            </button>
        </div>
    </div>

    <!-- Character Table -->
    <div class="character-table-wrapper table-responsive rounded-3">
        <table class="character-table table table-dark table-hover align-middle mb-0" id="characterTable">
            <thead>
                <tr>
                    <th data-sort="character_name" class="text-start">Name <span class="sort-icon">⇅</span></th>
                    <th data-sort="player_name" class="text-center text-nowrap">NPC <span class="sort-icon">⇅</span></th>
                    <th data-sort="clan" class="text-center text-nowrap">Clan <span class="sort-icon">⇅</span></th>
                    <th data-sort="generation" class="text-center text-nowrap">Gen <span class="sort-icon">⇅</span></th>
                    <th data-sort="status" class="text-center text-nowrap">Status <span class="sort-icon">⇅</span></th>
                    <th class="text-center text-nowrap w-150px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $characters = supabase_table_get('characters', [
                        'select' => '*',
                        'order' => 'created_at.desc,id.desc',
                    ]);
                } catch (Throwable $e) {
                    $characters = [];
                }
                foreach ($characters as &$c) {
                    $c['owner_username'] = '—';
                }
                unset($c);
                $currentAdminUrl = $_SERVER['REQUEST_URI'] ?? '/admin/admin_panel.php';
                $encodedReturnUrl = rawurlencode($currentAdminUrl);
                
                if (empty($characters)) {
                    echo "<tr><td colspan='6'>No characters found.</td></tr>";
                } else {
                    foreach ($characters as $char) {
                        $is_npc = ($char['player_name'] === 'NPC');
                        $playerName = trim($char['player_name'] ?? '') !== '' ? $char['player_name'] : ($is_npc ? 'NPC' : '—');
                        $clanName = $char['clan'] ?? 'Unknown';
                        $generation = $char['generation'] ?? '';
                        $statusRaw = trim((string)($char['status'] ?? ''));
                        $status = strtolower($statusRaw);
                        if ($status === '') {
                            $status = 'active';
                        }

                        $allowedStatuses = ['active', 'inactive', 'archived', 'dead', 'missing', 'npc', 'unknown'];
                        if (!in_array($status, $allowedStatuses, true)) {
                            $status = 'unknown';
                        }

                        $camarilla = $char['camarilla_status'] ?? 'Unknown';
                        $owner = $char['owner_username'] ?? '—';
                ?>
                    <tr class="character-row" 
                        data-id="<?php echo $char['id']; ?>"
                        data-type="<?php echo $is_npc ? 'npc' : 'pc'; ?>" 
                        data-name="<?php echo htmlspecialchars($char['character_name']); ?>"
                        data-clan="<?php echo htmlspecialchars($clanName); ?>"
                        data-player="<?php echo htmlspecialchars($playerName); ?>"
                        data-generation="<?php echo htmlspecialchars($generation); ?>"
                        data-status="<?php echo htmlspecialchars($status); ?>"
                        data-camarilla="<?php echo htmlspecialchars($camarilla); ?>"
                        data-owner="<?php echo htmlspecialchars($owner); ?>"
                        data-created="<?php echo htmlspecialchars($char['created_at'] ?? ''); ?>">
                        <td class="character-cell align-top text-light">
                            <strong><?php echo htmlspecialchars($char['character_name']); ?></strong>
                        </td>
                        <td class="align-top text-center text-nowrap">
                            <?php
                            if ($is_npc) {
                                echo render_status_badge('npc');
                            } elseif ($playerName && $playerName !== '—') {
                                echo '<span class="text-light">' . htmlspecialchars($playerName) . '</span>';
                            } else {
                                echo '<span class="opacity-75">—</span>';
                            }
                            ?>
                        </td>
                        <td class="align-top text-center text-nowrap"><?php echo render_clan_badge($clanName); ?></td>
                        <td class="align-top text-center text-nowrap">
                            <?php echo htmlspecialchars($generation ?: '—'); ?>
                        </td>
                        <td class="align-top text-center text-nowrap">
                            <?php
                                $statusBadge = render_status_badge($status);
                                if ($statusBadge === '') {
                                    echo '<span class="badge-neutral">' . htmlspecialchars(ucfirst($status)) . '</span>';
                                } else {
                                    echo $statusBadge;
                                }
                            ?>
                        </td>
                        <td class="actions text-center align-top w-150px">
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
                                        data-status="<?php echo $status; ?>"
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

    <!-- Pagination Controls -->
    <div class="pagination-controls d-flex flex-column flex-lg-row gap-3 align-items-center justify-content-between" id="paginationControls">
        <div class="pagination-info fw-semibold">
            <span id="paginationInfo">Showing 1-<?php echo count($characters); ?> of <?php echo count($characters); ?> characters</span>
        </div>
        <div class="pagination-buttons d-flex flex-wrap gap-2 justify-content-center" id="paginationButtons">
            <!-- Buttons will be generated by JavaScript -->
        </div>
    </div>
</div>

<?php
// Include character view modal
$apiEndpoint = '/admin/view_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';
?>

<!-- Edit Character Modal -->
<div class="modal fade" id="editCharacterModal" tabindex="-1" aria-labelledby="editCharacterName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2">
                        <span aria-hidden="true">✏️</span>
                        <span id="editCharacterName">Edit Character</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body p-0 modal-body-overflow-hidden">
                <iframe id="editCharacterIframe" 
                        src="" 
                        class="iframe-fullscreen"
                        title="Edit Character"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize edit buttons to open modal with iframe
document.addEventListener('DOMContentLoaded', function() {
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
            const modalEl = document.getElementById('editCharacterModal');
            const iframeEl = document.getElementById('editCharacterIframe');
            const titleEl = document.getElementById('editCharacterName');
            
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
    
    // Listen for messages from iframe when character is saved
    window.addEventListener('message', function(event) {
        // Verify message is from our iframe (basic security check)
        if (event.data && event.data.type === 'characterSaved') {
            const modalEl = document.getElementById('editCharacterModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    // Close the modal
                    modalInstance.hide();
                    
                    // Optionally refresh the page to show updated data
                    // You can remove this if you don't want auto-refresh
                    setTimeout(function() {
                        window.location.reload();
                    }, 300);
                }
            }
        }
    });
});
</script>

<!-- Delete Modal -->
<?php
$modalId = 'deleteModal';
$size = 'md';
$fullscreen = false;
$scrollable = false;
include __DIR__ . '/../includes/modal_base.php';
?>

<script>
(function() {
    'use strict';
    // Populate delete modal content after modal_base.php is included
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
// No auto-scroll needed - Name and Actions columns are sticky and always visible
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
