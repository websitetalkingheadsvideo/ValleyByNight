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

require_once __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/header.php';
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
        // Get relationship statistics
        $stats_query = "SELECT 
            COUNT(*) as total_vampires,
            COUNT(CASE WHEN sire IS NOT NULL AND sire != '' THEN 1 END) as with_sire,
            COUNT(CASE WHEN sire IS NULL OR sire = '' THEN 1 END) as without_sire,
            (SELECT COUNT(*) FROM characters c1 WHERE c1.sire IN (SELECT character_name FROM characters c2 WHERE c1.sire = c2.character_name)) as childer_count
            FROM characters";
        $stats_result = mysqli_query($conn, $stats_query);
        
        if ($stats_result) {
            $stats = mysqli_fetch_assoc($stats_result);
        } else {
            $stats = ['total_vampires' => 0, 'with_sire' => 0, 'without_sire' => 0, 'childer_count' => 0];
        }
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
                <button type="button" class="btn btn-success btn-sm" onclick="openAddRelationshipModal()">
                    <span class="d-none d-sm-inline">+ </span>Add Relationship
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="showFamilyTree()">
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
                $relationship_query = "SELECT c.*, 
                    (SELECT COUNT(*) FROM characters c2 WHERE c2.sire = c.character_name) as childe_count,
                    (SELECT GROUP_CONCAT(c2.character_name SEPARATOR ', ') FROM characters c2 WHERE c2.sire = c.character_name) as childe_names,
                    (SELECT COUNT(*) FROM characters c3 WHERE c3.character_name = c.sire) as sire_exists
                    FROM characters c 
                    ORDER BY c.character_name";
                $relationship_result = mysqli_query($conn, $relationship_query);
                
                if (!$relationship_result) {
                    echo "<tr><td colspan='7'>Query Error: " . mysqli_error($conn) . "</td></tr>";
                } elseif (mysqli_num_rows($relationship_result) > 0) {
                    while ($char = mysqli_fetch_assoc($relationship_result)) {
                        $is_npc = ($char['player_name'] === 'NPC');
                        $has_sire = !empty($char['sire']);
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="relationshipForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="characterId" name="character_id">
                    
                    <div class="mb-3">
                        <label for="characterSelect" class="form-label text-light">Vampire:</label>
                        <select id="characterSelect" name="character_name" class="form-select bg-dark text-light border-danger" required>
                            <option value="">Select a vampire...</option>
                            <?php
                            $char_query = "SELECT id, character_name FROM characters ORDER BY character_name";
                            $char_result = mysqli_query($conn, $char_query);
                            if ($char_result) {
                                while ($char = mysqli_fetch_assoc($char_result)) {
                                    echo "<option value='{$char['id']}'>{$char['character_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select a vampire.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sireSelect" class="form-label text-light">Sire:</label>
                        <select id="sireSelect" name="sire" class="form-select bg-dark text-light border-danger">
                            <option value="">No sire (Sireless)</option>
                            <?php
                            $sire_query = "SELECT id, character_name FROM characters ORDER BY character_name";
                            $sire_result = mysqli_query($conn, $sire_query);
                            if ($sire_result) {
                                while ($sire = mysqli_fetch_assoc($sire_result)) {
                                    echo "<option value='{$sire['character_name']}'>{$sire['character_name']}</option>";
                                }
                            }
                            ?>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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


<script>
let currentFilter = 'all';
let currentRelationshipId = null;
let currentSort = { column: null, direction: 'asc' };

document.addEventListener('DOMContentLoaded', function() {
    initializeAll();
});

function initializeAll() {
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });
    
    // Search
    document.getElementById('relationshipSearch').addEventListener('input', applyFilters);
    
    // Sort buttons - using pattern from admin_panel.js
    const headers = document.querySelectorAll('#relationshipTable th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            
            // Toggle direction if same column, otherwise start with ascending
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update header styling
            headers.forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add('sorted-' + currentSort.direction);
            
            // Sort table
            sortTable(column, currentSort.direction);
        });
    });
    
    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            editRelationship(this.dataset.id, this.dataset.name, this.dataset.sire);
        });
    });
    
    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            viewCharacter(this.dataset.id);
        });
    });
    
    // Form submission
    document.getElementById('relationshipForm').addEventListener('submit', handleFormSubmit);
    
    // Initial filter
    applyFilters();
}

function sortTable(column, direction) {
    const tbody = document.querySelector('#relationshipTable tbody');
    const rows = Array.from(tbody.querySelectorAll('.relationship-row'));
    
    rows.sort((a, b) => {
        let aVal = '';
        let bVal = '';

        switch(column) {
            case 'character_name':
                aVal = (a.dataset.name || '').toLowerCase();
                bVal = (b.dataset.name || '').toLowerCase();
                break;
            case 'clan':
                aVal = (a.cells[1]?.textContent?.trim() || '').toLowerCase();
                bVal = (b.cells[1]?.textContent?.trim() || '').toLowerCase();
                break;
            case 'generation':
                aVal = parseInt(a.cells[2]?.textContent?.replace('th', '').trim() || '0', 10) || 0;
                bVal = parseInt(b.cells[2]?.textContent?.replace('th', '').trim() || '0', 10) || 0;
                break;
            case 'sire':
                aVal = (a.dataset.sire || '').toLowerCase();
                bVal = (b.dataset.sire || '').toLowerCase();
                break;
            case 'player_name':
                aVal = (a.cells[5]?.textContent?.trim() || '').toLowerCase();
                bVal = (b.cells[5]?.textContent?.trim() || '').toLowerCase();
                break;
            default:
                aVal = (a.dataset.name || '').toLowerCase();
                bVal = (b.dataset.name || '').toLowerCase();
        }
        
        let comparison = 0;
        if (aVal > bVal) comparison = 1;
        if (aVal < bVal) comparison = -1;
        
        return direction === 'asc' ? comparison : -comparison;
    });
    
    // Re-append rows in sorted order
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort icons
    document.querySelectorAll('#relationshipTable th[data-sort] .sort-icon').forEach(icon => {
        icon.textContent = '⇅';
    });
    const activeTh = document.querySelector(`#relationshipTable th[data-sort="${column}"]`);
    if (activeTh) {
        const icon = activeTh.querySelector('.sort-icon');
        if (icon) {
            icon.textContent = direction === 'asc' ? '↑' : '↓';
        }
    }
}

function applyFilters() {
    const searchTerm = document.getElementById('relationshipSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.relationship-row');
    
    rows.forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const sire = row.dataset.sire.toLowerCase();
        const hasSire = row.dataset.hasSire === 'true';
        const hasChilder = row.dataset.hasChilder === 'true';
        
        let show = true;
        
        // Apply filter
        if (currentFilter === 'sires' && !hasChilder) show = false;
        if (currentFilter === 'childer' && !hasSire) show = false;
        if (currentFilter === 'sireless' && hasSire) show = false;
        
        // Apply search
        if (searchTerm && !name.includes(searchTerm) && !sire.includes(searchTerm)) show = false;
        
        if (show) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function openAddRelationshipModal() {
    currentRelationshipId = null;
    document.getElementById('modalTitle').textContent = 'Add Relationship';
    document.getElementById('relationshipForm').reset();
    document.getElementById('characterId').value = '';
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('relationshipModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
}

function editRelationship(id, name, sire) {
    currentRelationshipId = id;
    document.getElementById('modalTitle').textContent = 'Edit Relationship';
    document.getElementById('characterId').value = id;
    document.getElementById('characterSelect').value = id;
    document.getElementById('sireSelect').value = sire;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('relationshipModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
}

function closeRelationshipModal() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('relationshipModal'));
        if (modal) modal.hide();
    }
    currentRelationshipId = null;
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    fetch('api_sire_childe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
        .then(result => {
        if (result.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('relationshipModal'));
                if (modal) modal.hide();
            }
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error saving relationship');
        console.error(error);
    });
}

function showFamilyTree() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('treeModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
    document.getElementById('familyTreeContent').innerHTML = '<div class="text-center text-light py-4">Loading family tree...</div>';
    
    // Simple family tree display
    fetch('api_sire_childe.php?action=tree')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderFamilyTree(data.tree);
            } else {
                document.getElementById('familyTreeContent').innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('familyTreeContent').innerHTML = '<div class="alert alert-danger">Error loading family tree</div>';
            console.error(error);
        });
}

function renderFamilyTree(tree) {
    let html = '';
    
    // Group by generation
    const generations = {};
    tree.forEach(char => {
        if (!generations[char.generation]) {
            generations[char.generation] = [];
        }
        generations[char.generation].push(char);
    });
    
    // Sort generations (highest first)
    const sortedGens = Object.keys(generations).sort((a, b) => parseInt(b) - parseInt(a));
    
    sortedGens.forEach(gen => {
        html += `<div class="card bg-dark border-danger mb-3">`;
        html += `<div class="card-header border-danger">`;
        html += `<h3 class="h5 mb-0 text-light">Generation ${gen}</h3>`;
        html += `</div>`;
        html += `<div class="card-body">`;
        html += `<div class="row g-3">`;
        
        generations[gen].forEach(char => {
            html += `<div class="col-md-6 col-lg-4">`;
            html += `<div class="card bg-secondary border-danger h-100">`;
            html += `<div class="card-body">`;
            html += `<h5 class="card-title text-light">${char.character_name}</h5>`;
            html += `<p class="card-text text-muted mb-2">${char.clan}</p>`;
            if (char.sire) {
                html += `<p class="card-text small text-danger mb-1"><strong>Sired by:</strong> ${char.sire}</p>`;
            }
            if (char.childer && char.childer.length > 0) {
                html += `<p class="card-text small text-purple mb-0"><strong>Childer:</strong> ${char.childer.join(', ')}</p>`;
            }
            html += `</div>`;
            html += `</div>`;
            html += `</div>`;
        });
        
        html += `</div>`;
        html += `</div>`;
        html += `</div>`;
    });
    
    document.getElementById('familyTreeContent').innerHTML = html;
}

function closeTreeModal() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('treeModal'));
        if (modal) modal.hide();
    }
}

</script>

<?php
// Include character view modal
$apiEndpoint = '/admin/view_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';
?>

<script src="../js/form_validation.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
