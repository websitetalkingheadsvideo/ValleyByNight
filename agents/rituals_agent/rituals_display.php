<?php
/**
 * Rituals Agent - Rituals Display Page
 * 
 * Displays all rituals from rituals_master table in a sortable table format.
 * Shows key attributes: type, level, name, description, source.
 * 
 * TM-07: Ritual Data Audit - Display Page
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Verify includes path exists (agents/rituals_agent -> up 2 levels to root)
$version_path = __DIR__ . '/../../includes/version.php';
$header_path = __DIR__ . '/../../includes/header.php';

if (!file_exists($version_path)) {
    die("Error: version.php not found at: " . $version_path);
}

if (!file_exists($header_path)) {
    die("Error: header.php not found at: " . $header_path);
}

require_once $version_path;
require_once __DIR__ . '/../../includes/supabase_client.php';

// Load Rituals Agent
require_once __DIR__ . '/src/RitualsAgent.php';

$extra_css = ['css/admin-agents.css', 'css/rituals-display.css', 'css/character_view.css'];
require_once $header_path;

// Fetch all rituals
$rituals = [];
$stats = [
    'total' => 0,
    'by_type' => []
];

try {
    $agent = new RitualsAgent(null);
    $rituals = $agent->listRituals(null, null, false, 10000, 0);
    
    $stats['total'] = count($rituals);
    
    // Count by type
    foreach ($rituals as $ritual) {
        $type = $ritual['type'] ?? 'Unknown';
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = 0;
        }
        $stats['by_type'][$type]++;
    }
} catch (Exception $e) {
    $error_message = "Error loading rituals: " . htmlspecialchars($e->getMessage());
}

/**
 * Truncate text to specified length
 */
function truncateText(string $text, int $maxLength = 100): string {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">🔮 Rituals Overview</h1>
            <p class="lead fst-italic mb-0">Browse and sort all available rituals</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">
            ← Back to Agents
        </a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row gy-3 align-items-center mb-4">
        <div class="col-md-3">
            <div class="card bg-dark border-danger">
                <div class="card-body text-center">
                    <div class="vbn-stat-number text-light"><?= $stats['total'] ?></div>
                    <div class="vbn-stat-label">Total Rituals</div>
                </div>
            </div>
        </div>
        <?php foreach ($stats['by_type'] as $type => $count): ?>
            <div class="col-md-3">
                <div class="card bg-dark border-danger">
                    <div class="card-body text-center">
                        <div class="vbn-stat-number text-light"><?= $count ?></div>
                        <div class="vbn-stat-label"><?= htmlspecialchars($type) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Search and pagination controls -->
    <div class="row g-2 align-items-center mb-4">
        <div class="col-12 col-md col-lg-4">
            <input type="text" id="ritualSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search rituals by type, level, name, description..." />
        </div>
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <label for="ritualPageSize" class="text-light text-uppercase small mb-0">Per page:</label>
            <select id="ritualPageSize" class="form-select form-select-sm bg-dark text-light border-danger">
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <!-- Rituals Table -->
    <div class="table-responsive rounded-3">
        <table id="ritualsTable" class="table table-dark rituals-table">
            <thead>
                <tr>
                    <th data-column="type" class="sortable">
                        Type <span class="sort-icon"></span>
                    </th>
                    <th data-column="level" class="sortable">
                        Level <span class="sort-icon"></span>
                    </th>
                    <th data-column="name" class="sortable">
                        Name <span class="sort-icon"></span>
                    </th>
                    <th data-column="description" class="sortable">
                        Description <span class="sort-icon"></span>
                    </th>
                    <th class="text-center text-nowrap" style="width: 120px; min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rituals)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4" style="color: #d4c4b0;">No rituals found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rituals as $ritual): ?>
                        <tr class="ritual-row" data-ritual-id="<?= htmlspecialchars($ritual['id'] ?? '') ?>">
                            <td><?= htmlspecialchars($ritual['type'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($ritual['level'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ritual['name'] ?? '') ?></td>
                            <td title="<?= htmlspecialchars($ritual['description'] ?? '') ?>"><?= htmlspecialchars(truncateText($ritual['description'] ?? '', 60)) ?></td>
                            <td class="text-center text-nowrap" style="width: 120px; min-width: 120px;">
                                <button class="btn btn-primary btn-sm view-ritual-btn" 
                                        data-id="<?= htmlspecialchars($ritual['id'] ?? '') ?>"
                                        title="View Ritual Details"
                                        style="min-width: 40px; padding: 0.25rem 0.5rem;">👁️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div class="pagination-controls d-flex flex-column flex-lg-row gap-3 align-items-center justify-content-between mt-4" id="ritualPaginationControls">
        <div class="pagination-info fw-semibold">
            <span id="ritualPaginationInfo" aria-live="polite">Showing rituals</span>
        </div>
        <div class="pagination-buttons d-flex flex-wrap gap-2 justify-content-center" id="ritualPaginationButtons">
            <!-- Buttons generated by JavaScript -->
        </div>
    </div>
</div>

<!-- Ritual View Modal -->
<div class="modal fade" id="viewRitualModal" tabindex="-1" aria-labelledby="viewRitualName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2" id="viewRitualName">
                        <span aria-hidden="true">🔮</span>
                        <span>Ritual Details</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <div id="viewRitualContent" class="view-content" aria-live="polite">
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/rituals-display.js"></script>
<script>
// Ritual View Modal Functionality
(function() {
    'use strict';
    
    const API_ENDPOINT = 'api_view_ritual.php';
    const MODAL_ID = 'viewRitualModal';
    let viewModalInstance = null;
    
    // Global function to open ritual view
    window.viewRitual = function(ritualId) {
        if (!ritualId) return;
        
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Ritual view modal not found');
            return;
        }
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error('Bootstrap modal runtime not loaded');
            return;
        }
        
        viewModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true
        });
        
        const content = document.getElementById('viewRitualContent');
        const title = document.getElementById('viewRitualName');
        
        if (title) {
            title.innerHTML = '<span aria-hidden="true">🔮</span><span>Ritual Details</span>';
        }
        
        if (content) {
            content.setAttribute('aria-busy', 'true');
            content.textContent = 'Loading...';
        }
        
        viewModalInstance.show();
        
        const requestUrl = API_ENDPOINT + '?id=' + encodeURIComponent(ritualId) + '&_t=' + Date.now();
        
        fetch(requestUrl)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                // Get response as text first to check if it's valid JSON
                return response.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server. Response: ' + text.substring(0, 200));
                    }
                });
            })
            .then(function(data) {
                if (data && data.success && data.ritual) {
                    renderRitualView(data.ritual);
                    if (title) {
                        title.innerHTML = '<span aria-hidden="true">🔮</span><span>' + escapeHtml(data.ritual.name || 'Ritual Details') + '</span>';
                    }
                } else {
                    if (content) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger mb-0';
                        alert.setAttribute('role', 'alert');
                        alert.textContent = 'Error: ' + (data && data.message ? data.message : 'Unknown error.');
                        content.innerHTML = '';
                        content.appendChild(alert);
                        content.setAttribute('aria-busy', 'false');
                    }
                }
            })
            .catch(function(error) {
                console.error('view_ritual_api error', error);
                if (content) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger mb-0';
                    alert.setAttribute('role', 'alert');
                    alert.innerHTML = 'Error loading ritual.<br><small>' + escapeHtml(error.message || 'Network or server error') + '</small>';
                    content.innerHTML = '';
                    content.appendChild(alert);
                    content.setAttribute('aria-busy', 'false');
                }
            });
    };
    
    function escapeHtml(input) {
        return String(input)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    function renderRitualView(ritual) {
        const contentEl = document.getElementById('viewRitualContent');
        if (!contentEl) return;
        
        let html = '<div class="ritual-details">';
        
        // Basic Information
        html += '<h3>Basic Information</h3>';
        html += '<div class="row g-3 mt-2">';
        html += '<div class="col-md-6"><p><strong>Name:</strong> ' + escapeHtml(ritual.name || 'N/A') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Type:</strong> ' + escapeHtml(ritual.type || 'Unknown') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Level:</strong> ' + escapeHtml(ritual.level || 'N/A') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Source:</strong> ' + escapeHtml(ritual.source || 'N/A') + '</p></div>';
        if (ritual.created_at) {
            const created = new Date(ritual.created_at);
            html += '<div class="col-md-6"><p><strong>Created:</strong> ' + created.toLocaleString() + '</p></div>';
        }
        html += '</div>';
        
        // Description
        if (ritual.description) {
            html += '<h3>Description</h3>';
            const descEscaped = escapeHtml(ritual.description).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + descEscaped + '</div>';
        }
        
        // System Text
        if (ritual.system_text) {
            html += '<h3>System Text</h3>';
            const systemEscaped = escapeHtml(ritual.system_text).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + systemEscaped + '</div>';
        }
        
        // Requirements
        if (ritual.requirements) {
            html += '<h3>Requirements</h3>';
            let reqText = ritual.requirements;
            // Try to parse as JSON
            try {
                const reqData = JSON.parse(ritual.requirements);
                if (typeof reqData === 'object') {
                    reqText = JSON.stringify(reqData, null, 2);
                }
            } catch (e) {
                // Not JSON, use as-is
            }
            const reqEscaped = escapeHtml(reqText).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + reqEscaped + '</div>';
        }
        
        // Ingredients
        if (ritual.ingredients) {
            html += '<h3>Ingredients</h3>';
            let ingText = ritual.ingredients;
            // Try to parse as JSON
            try {
                const ingData = JSON.parse(ritual.ingredients);
                if (typeof ingData === 'object') {
                    ingText = JSON.stringify(ingData, null, 2);
                }
            } catch (e) {
                // Not JSON, use as-is
            }
            const ingEscaped = escapeHtml(ingText).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + ingEscaped + '</div>';
        }
        
        // Rules (if attached)
        if (ritual.rules && Array.isArray(ritual.rules) && ritual.rules.length > 0) {
            html += '<h3>Rules</h3>';
            html += '<div class="rules-list">';
            ritual.rules.forEach(function(rule) {
                html += '<div class="rule-item mb-3">';
                if (rule.title) {
                    html += '<h4>' + escapeHtml(rule.title) + '</h4>';
                }
                if (rule.content) {
                    const contentEscaped = escapeHtml(rule.content).replace(/\n/g, '<br>');
                    html += '<div class="text-content">' + contentEscaped + '</div>';
                }
                if (rule.source) {
                    html += '<p class="text-light small"><strong>Source:</strong> ' + escapeHtml(rule.source) + '</p>';
                }
                html += '</div>';
            });
            html += '</div>';
        }
        
        html += '</div>';
        
        contentEl.innerHTML = html;
        contentEl.setAttribute('aria-busy', 'false');
    }
    
    // Initialize view buttons
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-ritual-btn');
        viewButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const ritualId = this.dataset.id;
                if (ritualId) {
                    viewRitual(parseInt(ritualId, 10));
                }
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

