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

$extra_css = ['css/admin_camarilla_positions.css'];
include __DIR__ . '/../includes/header.php';

// Get default night
$default_night = CAMARILLA_DEFAULT_NIGHT;

// Get all positions with current holders
$positions_data = get_all_positions_with_current_holders($default_night);

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

// DEBUG: Check character names that should match
$debug_chars = db_fetch_all($conn, "SELECT id, character_name, UPPER(REPLACE(character_name, ' ', '_')) as transformed_name FROM characters WHERE character_name LIKE '%Butch%' OR character_name LIKE '%Alistaire%' OR character_name LIKE '%Reed%' OR character_name LIKE '%Hawthorn%'");
error_log("DEBUG character name check: " . json_encode($debug_chars));

// DEBUG: Check what the assignment table expects vs what we have
$debug_assignments = db_fetch_all($conn, "SELECT cpa.position_id, cpa.character_id as assignment_char_id, c.character_name, c.id as char_id, UPPER(REPLACE(c.character_name, ' ', '_')) as transformed FROM camarilla_position_assignments cpa LEFT JOIN characters c ON UPPER(REPLACE(c.character_name, ' ', '_')) = cpa.character_id WHERE cpa.character_id IN ('BUTCH_REED', 'ALISTAIRE_HAWTHORN')");
error_log("DEBUG assignment vs character match: " . json_encode($debug_assignments));

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
            $position_lookup_result = [
                'position_id' => $position_id,
                'night' => $lookup_night,
                'current_holder' => get_current_holder_for_position($position_id, $lookup_night),
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

<div class="admin-camarilla-positions-container">
    <h1 class="panel-title">👑 Camarilla Positions</h1>
    <p class="panel-subtitle">Current position holders and historical assignments</p>
    
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="category-filter">
            <label for="categoryFilter">Category:</label>
            <select id="categoryFilter" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucwords($cat)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="clan-filter">
            <label for="clanFilter">Clan:</label>
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
        <div class="search-box">
            <input type="text" id="positionSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search positions..." />
        </div>
    </div>
    
    <!-- DEBUG: Character name lookup -->
    <script>
        console.log('DEBUG Character Names in DB:', <?php echo json_encode($debug_chars); ?>);
        console.log('DEBUG Assignment vs Character Match:', <?php echo json_encode($debug_assignments); ?>);
    </script>
    
    <!-- Positions Table -->
    <div class="positions-table-wrapper table-responsive">
        <table class="positions-table table table-dark table-hover align-middle" id="positionsTable">
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
                    $holder = $data['current_holder'];
                ?>
                    <tr class="position-row" 
                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>"
                        data-category="<?php echo htmlspecialchars($position['category'] ?? ''); ?>"
                        data-clan="<?php echo htmlspecialchars($holder['clan'] ?? ''); ?>"
                        data-name="<?php echo htmlspecialchars(strtolower($position['name'] ?? '')); ?>">
                        <td><strong><?php echo htmlspecialchars($position['name'] ?? 'Unknown'); ?></strong></td>
                        <td><?php echo htmlspecialchars(ucwords($position['category'] ?? '—')); ?></td>
                        <td>
                            <?php if ($holder): ?>
                                <?php 
                                // DEBUG: Log holder data
                                $debug_info = "position_id: " . ($position['position_id'] ?? '') . 
                                            ", assignment_character_id: " . ($holder['assignment_character_id'] ?? 'NULL') . 
                                            ", holder character_id: " . ($holder['character_id'] ?? 'NULL') . 
                                            ", holder character_name: " . ($holder['character_name'] ?? 'NULL') . 
                                            ", holder keys: " . implode(', ', array_keys($holder));
                                ?>
                                <script>console.log('DEBUG Holder Data:', <?php echo json_encode($holder); ?>, 'Debug Info:', <?php echo json_encode($debug_info); ?>);</script>
                                <?php 
                                // Use character_name if available, otherwise fall back to assignment_character_id (formatted to title case)
                                if (!empty($holder['character_name'])) {
                                    $display_name = $holder['character_name'];
                                } else {
                                    $display_name = ucwords(strtolower(str_replace('_', ' ', $holder['assignment_character_id'] ?? 'Unknown')));
                                }
                                ?>
                                <?php if (!empty($holder['character_id'])): ?>
                                    <a href="../lotn_char_create.php?id=<?php echo htmlspecialchars($holder['character_id']); ?>" class="character-link">
                                        <?php echo htmlspecialchars($display_name); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="character-link" title="Character not found in database"><?php echo htmlspecialchars($display_name); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Vacant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($holder): ?>
                                <?php if ($holder['is_acting']): ?>
                                    <span class="badge badge-acting">Acting</span>
                                <?php else: ?>
                                    <span class="badge badge-permanent">Permanent</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-vacant">Vacant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($holder && $holder['start_night']): ?>
                                <?php echo date('Y-m-d', strtotime($holder['start_night'])); ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions text-center align-top" style="width: 150px;">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Position actions">
                                <button class="action-btn view-btn btn btn-primary" 
                                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>"
                                        title="View Position">👁️</button>
                                <button class="action-btn edit-btn btn btn-warning" 
                                        data-id="<?php echo htmlspecialchars($position['position_id'] ?? ''); ?>"
                                        title="Edit Position">✏️</button>
                                <button class="action-btn delete-btn btn btn-danger" 
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
    <div class="agent-section">
        <h2 class="agent-section-title">🤖 Ask the Camarilla Positions Agent</h2>
        <p class="agent-section-subtitle">Query position holders and historical assignments</p>
        
        <div class="agent-forms row g-3">
            <!-- Position Lookup Form -->
            <div class="col-12 col-md-6">
                <div class="agent-form-card">
                    <h3 class="agent-form-title">Position Lookup</h3>
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
                    
                    <?php if ($position_lookup_result): ?>
                        <div class="agent-results mt-4">
                            <h4>Results for <?php echo htmlspecialchars($position_lookup_result['position_name']); ?></h4>
                            <p><strong>Night:</strong> <?php echo date('Y-m-d H:i', strtotime($position_lookup_result['night'])); ?></p>
                            
                            <div class="current-holder-result">
                                <h5>Current Holder:</h5>
                                <?php if ($position_lookup_result['current_holder']): ?>
                                    <p>
                                        <?php if (!empty($position_lookup_result['current_holder']['character_id'])): ?>
                                            <a href="../lotn_char_create.php?id=<?php echo $position_lookup_result['current_holder']['character_id']; ?>">
                                                <?php echo htmlspecialchars($position_lookup_result['current_holder']['character_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($position_lookup_result['current_holder']['character_name']); ?>
                                        <?php endif; ?>
                                        <?php if ($position_lookup_result['current_holder']['is_acting']): ?>
                                            <span class="badge badge-acting">Acting</span>
                                        <?php else: ?>
                                            <span class="badge badge-permanent">Permanent</span>
                                        <?php endif; ?>
                                        <br>
                                        <small>Since: <?php echo date('Y-m-d', strtotime($position_lookup_result['current_holder']['start_night'])); ?></small>
                                    </p>
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
                <div class="agent-form-card">
                    <h3 class="agent-form-title">Character Lookup</h3>
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
                    
                    <?php if ($character_lookup_result): ?>
                        <div class="agent-results mt-4">
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
include __DIR__ . '/../includes/position_view_modal.php';
?>

<!-- Delete Modal -->
<div id="deletePositionModal" class="modal" role="dialog" aria-modal="true" aria-label="Confirm Deletion" aria-describedby="deletePositionName deleteWarning">
    <div class="modal-content">
        <h2 class="modal-title">⚠️ Confirm Deletion</h2>
        <p class="modal-message">Delete position:</p>
        <p class="modal-character-name" id="deletePositionName"></p>
        <p class="modal-warning" id="deleteWarning" style="display:none;">
            ⚠️ <strong>Warning</strong> - This position may have assignments!
        </p>
        <div class="modal-actions">
            <button class="modal-btn cancel-btn btn btn-secondary" onclick="closeDeletePositionModal()">Cancel</button>
            <button class="modal-btn confirm-btn btn btn-danger" id="confirmDeletePositionBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Include external JavaScript -->
<script src="../js/admin_camarilla_positions.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

