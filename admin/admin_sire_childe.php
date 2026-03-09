<?php
/**
 * Admin Panel - Sire/Childe Relationship Tracker
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/version.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
include __DIR__ . '/../includes/header.php';

// Fetch all characters for stats, table, and dropdowns
$all_chars = supabase_table_get('characters', ['select' => 'id,character_name,clan,generation,sire,player_name', 'order' => 'character_name.asc']);
$all_chars = is_array($all_chars) ? $all_chars : [];

$name_to_id = [];
foreach ($all_chars as $c) {
    $name_to_id[$c['character_name'] ?? ''] = (int) ($c['id'] ?? 0);
}

// Stats
$total_vampires = count($all_chars);
$with_sire = 0;
$without_sire = 0;
foreach ($all_chars as $c) {
    $s = trim((string) ($c['sire'] ?? ''));
    if ($s !== '') {
        $with_sire++;
    } else {
        $without_sire++;
    }
}
$childer_count = 0;
foreach ($all_chars as $c) {
    $sire_name = trim((string) ($c['sire'] ?? ''));
    if ($sire_name !== '' && isset($name_to_id[$sire_name])) {
        $childer_count++;
    }
}
$stats = ['total_vampires' => $total_vampires, 'with_sire' => $with_sire, 'without_sire' => $without_sire, 'childer_count' => $childer_count];

// Childe counts and names per character
$childe_counts = [];
$childe_names = [];
foreach ($all_chars as $c) {
    $name = $c['character_name'] ?? '';
    $childe_counts[$name] = 0;
    $childe_names[$name] = [];
}
foreach ($all_chars as $c) {
    $sire_name = trim((string) ($c['sire'] ?? ''));
    if ($sire_name !== '' && isset($childe_counts[$sire_name])) {
        $childe_counts[$sire_name]++;
        $childe_names[$sire_name][] = $c['character_name'] ?? '';
    }
}
?>

<link rel="stylesheet" href="../css/admin_sire_childe.css">

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 text-light fw-bold mb-2">🧛 Sire/Childe Relationships</h1>
            <p class="lead text-light fst-italic">Track vampire lineage and blood bonds in the city</p>
        </div>
    </div>
    
    <!-- Admin Navigation -->
    <nav class="row g-2 g-md-3 mb-4" aria-label="Admin Navigation">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_panel.php" class="btn btn-outline-danger btn-sm w-100 text-center">👥 Characters</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_sire_childe.php" class="btn btn-outline-danger btn-sm w-100 text-center active">🧛 Sire/Childe</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_sire_childe_enhanced.php" class="btn btn-outline-danger btn-sm w-100 text-center">🔍 Enhanced Analysis</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_equipment.php" class="btn btn-outline-danger btn-sm w-100 text-center">⚔️ Equipment</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_locations.php" class="btn btn-outline-danger btn-sm w-100 text-center">📍 Locations</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="questionnaire_admin.php" class="btn btn-outline-danger btn-sm w-100 text-center">📝 Questionnaire</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_npc_briefing.php" class="btn btn-outline-danger btn-sm w-100 text-center">📋 NPC Briefing</a>
        </div>
    </nav>
    
    <!-- Relationship Statistics -->
    <div class="row g-3 mb-4">
        <?php
        ?>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <div class="display-6 text-danger fw-bold"><?php echo $stats['total_vampires'] ?? 0; ?></div>
                    <div class="text-light small">Total Vampires</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <div class="display-6 text-danger fw-bold"><?php echo $stats['with_sire'] ?? 0; ?></div>
                    <div class="text-light small">With Sire</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <div class="display-6 text-danger fw-bold"><?php echo $stats['without_sire'] ?? 0; ?></div>
                    <div class="text-light small">Sireless</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <div class="display-6 text-danger fw-bold"><?php echo $stats['childer_count'] ?? 0; ?></div>
                    <div class="text-light small">Childer</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="row g-3 mb-4 align-items-end">
        <div class="col-12 col-md-auto">
            <div class="btn-group" role="group" aria-label="Filter relationships">
                <button type="button" class="filter-btn btn btn-outline-danger active" data-filter="all">All Relationships</button>
                <button type="button" class="filter-btn btn btn-outline-danger" data-filter="sires">Sires Only</button>
                <button type="button" class="filter-btn btn btn-outline-danger" data-filter="childer">Childer Only</button>
                <button type="button" class="filter-btn btn btn-outline-danger" data-filter="sireless">Sireless</button>
            </div>
        </div>
        <div class="col-12 col-md">
            <input type="text" id="relationshipSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search by name or sire..." />
        </div>
        <div class="col-12 col-md-auto">
            <div class="btn-group" role="group" aria-label="Action buttons">
                <button type="button" class="btn btn-success btn-sm" id="addRelationshipBtn">
                    <span class="d-none d-sm-inline">+ </span>Add Relationship
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="showFamilyTreeBtn">
                    <span class="d-none d-sm-inline">🌳 </span>Family Tree
                </button>
            </div>
        </div>
    </div>

    <!-- Relationship Table -->
    <div class="card bg-dark border-danger">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" id="relationshipTable">
            <thead>
                <tr>
                    <th data-sort="character_name">Vampire <span class="sort-icon">⇅</span></th>
                    <th data-sort="clan">Clan <span class="sort-icon">⇅</span></th>
                    <th data-sort="generation">Gen <span class="sort-icon">⇅</span></th>
                    <th data-sort="sire">Sire <span class="sort-icon">⇅</span></th>
                    <th>Childer</th>
                    <th data-sort="player_name">Player <span class="sort-icon">⇅</span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($all_chars) > 0) {
                    foreach ($all_chars as $char) {
                        $char['childe_count'] = $childe_counts[$char['character_name'] ?? ''] ?? 0;
                        $char['childe_names'] = implode(', ', $childe_names[$char['character_name'] ?? ''] ?? []);
                        $char['sire_exists'] = 0;
                        if (!empty(trim((string) ($char['sire'] ?? '')))) {
                            $char['sire_exists'] = isset($name_to_id[trim($char['sire'])]) ? 1 : 0;
                        }
                        $is_npc = (($char['player_name'] ?? '') === 'NPC');
                        $has_sire = !empty(trim((string) ($char['sire'] ?? '')));
                        $has_childer = $char['childe_count'] > 0;
                        $sire_exists = $has_sire && ($char['sire_exists'] > 0);
                ?>
                    <tr class="relationship-row" 
                        data-type="<?php echo $is_npc ? 'npc' : 'pc'; ?>" 
                        data-name="<?php echo htmlspecialchars($char['character_name']); ?>" 
                        data-sire="<?php echo htmlspecialchars($char['sire'] ?? ''); ?>"
                        data-has-sire="<?php echo $has_sire ? 'true' : 'false'; ?>"
                        data-has-childer="<?php echo $has_childer ? 'true' : 'false'; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($char['character_name']); ?></strong>
                            <?php if ($has_sire): ?>
                                <span class="badge bg-purple ms-2">Childe</span>
                            <?php endif; ?>
                            <?php if ($has_childer): ?>
                                <span class="badge bg-danger ms-2">Sire</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($char['clan'] ?? 'Unknown'); ?></td>
                        <td><?php echo $char['generation']; ?>th</td>
                        <td>
                            <?php if ($has_sire): ?>
                                <span class="sire-name <?php echo $sire_exists ? 'sire-valid' : ''; ?>"><?php echo htmlspecialchars($char['sire']); ?></span>
                            <?php else: ?>
                                <span class="no-sire">Sireless</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_childer): ?>
                                <div class="childe-list">
                                    <span class="childe-count"><?php echo $char['childe_count']; ?> childe<?php echo $char['childe_count'] > 1 ? 's' : ''; ?></span>
                                    <div class="childe-names"><?php echo htmlspecialchars($char['childe_names']); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="no-childer">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_npc): ?>
                                <span class="badge bg-purple">NPC</span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($char['player_name']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions text-center">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Character actions">
                                <button type="button" class="action-btn view-btn btn btn-primary" 
                                        data-id="<?php echo $char['id']; ?>"
                                        title="View Details">👁️</button>
                                <button type="button" class="action-btn edit-btn btn btn-warning" 
                                        data-id="<?php echo $char['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($char['character_name']); ?>"
                                        data-sire="<?php echo htmlspecialchars($char['sire'] ?? ''); ?>"
                                        title="Edit Relationship">✏️</button>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7' class='empty-state'>No characters found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Relationship Modal -->
<div class="modal fade" id="relationshipModal" tabindex="-1" aria-labelledby="relationshipModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h2 class="modal-title text-light" id="relationshipModalLabel">🧛 <span id="modalTitle">Add Relationship</span></h2>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="relationshipForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="characterId" name="character_id">
                    
                    <div class="mb-3">
                        <label for="characterSelect" class="form-label text-light">Vampire:</label>
                        <select id="characterSelect" name="character_name" class="form-select bg-dark text-light border-danger" required>
                            <option value="">Select a vampire...</option>
                            <?php foreach ($all_chars as $char): ?>
                                <option value="<?php echo (int) ($char['id'] ?? 0); ?>"><?php echo htmlspecialchars($char['character_name'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a vampire.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sireSelect" class="form-label text-light">Sire:</label>
                        <select id="sireSelect" name="sire" class="form-select bg-dark text-light border-danger">
                            <option value="">No sire (Sireless)</option>
                            <?php foreach ($all_chars as $sire): ?>
                                <option value="<?php echo htmlspecialchars($sire['character_name'] ?? ''); ?>"><?php echo htmlspecialchars($sire['character_name'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="relationshipNotes" class="form-label text-light">Notes:</label>
                        <textarea id="relationshipNotes" name="notes" class="form-control bg-dark text-light border-danger" rows="3" placeholder="Additional notes about this relationship..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-danger">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Relationship</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Family Tree Modal -->
<div class="modal fade" id="treeModal" tabindex="-1" aria-labelledby="treeModalLabel" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-danger">
            <div class="modal-header border-danger">
                <h2 class="modal-title text-light" id="treeModalLabel">🌳 Vampire Family Tree</h2>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="familyTreeContent">
                    <div class="text-center text-light py-4">Loading family tree...</div>
                </div>
            </div>
            <div class="modal-footer border-danger">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- JavaScript moved to external file -->
<script src="../js/admin_sire_childe.js" defer></script>

<?php
// Include character view modal
$apiEndpoint = '/admin/view_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';
?>

<script src="../js/form_validation.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
