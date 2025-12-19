<?php
/**
 * Ability Agent - Abilities Display Page
 * 
 * Displays all abilities from abilities table in a sortable table format.
 * Shows key attributes: category, name, description, min_level, max_level.
 * 
 * TM-05: Ability Agent Implementation - Display Interface
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

// Verify includes path exists (agents/ability_agent -> up 2 levels to root)
$version_path = __DIR__ . '/../../includes/version.php';
$header_path = __DIR__ . '/../../includes/header.php';

if (!file_exists($version_path)) {
    die("Error: version.php not found at: " . $version_path);
}

if (!file_exists($header_path)) {
    die("Error: header.php not found at: " . $header_path);
}

require_once $version_path;
require_once __DIR__ . '/../../includes/connect.php';

// Load Ability Agent
require_once __DIR__ . '/src/AbilityAgent.php';

$extra_css = ['css/admin-agents.css', 'css/rituals-display.css', 'css/character_view.css'];
require_once $header_path;

// Fetch all abilities
$abilities = [];
$stats = [
    'total' => 0,
    'by_category' => []
];

try {
    $agent = new AbilityAgent($conn);
    $abilities = $agent->getCanonicalAbilities(null);
    
    $stats['total'] = count($abilities);
    
    // Count by category
    foreach ($abilities as $ability) {
        $category = $ability['category'] ?? 'Unknown';
        if (!isset($stats['by_category'][$category])) {
            $stats['by_category'][$category] = 0;
        }
        $stats['by_category'][$category]++;
    }
} catch (Exception $e) {
    $error_message = "Error loading abilities: " . htmlspecialchars($e->getMessage());
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
            <h1 class="display-5 text-light fw-bold mb-1">⚡ Abilities Overview</h1>
            <p class="lead fst-italic mb-0">Browse and sort all available abilities</p>
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
                    <div class="vbn-stat-label">Total Abilities</div>
                </div>
            </div>
        </div>
        <?php foreach ($stats['by_category'] as $category => $count): ?>
            <div class="col-md-3">
                <div class="card bg-dark border-danger">
                    <div class="card-body text-center">
                        <div class="vbn-stat-number text-light"><?= $count ?></div>
                        <div class="vbn-stat-label"><?= htmlspecialchars($category) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Search Box -->
    <div class="search-box mb-4">
        <input type="text" id="abilitySearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search abilities by category, name, description..." />
    </div>

    <!-- Abilities Table -->
    <div class="table-responsive rounded-3">
        <table id="abilitiesTable" class="table table-dark rituals-table">
            <thead>
                <tr>
                    <th data-column="category" class="sortable">
                        Category <span class="sort-icon"></span>
                    </th>
                    <th data-column="name" class="sortable">
                        Name <span class="sort-icon"></span>
                    </th>
                    <th data-column="description" class="sortable">
                        Description <span class="sort-icon"></span>
                    </th>
                    <th data-column="min_level" class="sortable">
                        Min Level <span class="sort-icon"></span>
                    </th>
                    <th data-column="max_level" class="sortable">
                        Max Level <span class="sort-icon"></span>
                    </th>
                    <th class="text-center text-nowrap" style="width: 120px; min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($abilities)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4" style="color: #d4c4b0;">No abilities found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($abilities as $ability): ?>
                        <tr data-ability-id="<?= htmlspecialchars($ability['id'] ?? '') ?>">
                            <td><?= htmlspecialchars($ability['category'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($ability['name'] ?? '') ?></td>
                            <td title="<?= htmlspecialchars($ability['description'] ?? '') ?>"><?= htmlspecialchars(truncateText($ability['description'] ?? '', 60)) ?></td>
                            <td><?= htmlspecialchars($ability['min_level'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($ability['max_level'] ?? 'N/A') ?></td>
                            <td class="text-center text-nowrap" style="width: 120px; min-width: 120px;">
                                <button class="btn btn-primary btn-sm view-ability-btn" 
                                        data-id="<?= htmlspecialchars($ability['id'] ?? '') ?>"
                                        title="View Ability Details"
                                        style="min-width: 40px; padding: 0.25rem 0.5rem;">👁️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ability View Modal -->
<div class="modal fade" id="viewAbilityModal" tabindex="-1" aria-labelledby="viewAbilityName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2" id="viewAbilityName">
                        <span aria-hidden="true">⚡</span>
                        <span>Ability Details</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <div id="viewAbilityContent" class="view-content" aria-live="polite">
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/abilities-display.js"></script>
<script>
// Ability View Modal Functionality
(function() {
    'use strict';
    
    const API_ENDPOINT = 'api_view_ability.php';
    const MODAL_ID = 'viewAbilityModal';
    let viewModalInstance = null;
    
    // Global function to open ability view
    window.viewAbility = function(abilityId) {
        if (!abilityId) return;
        
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Ability view modal not found');
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
        
        const content = document.getElementById('viewAbilityContent');
        const title = document.getElementById('viewAbilityName');
        
        if (title) {
            title.innerHTML = '<span aria-hidden="true">⚡</span><span>Ability Details</span>';
        }
        
        if (content) {
            content.setAttribute('aria-busy', 'true');
            content.textContent = 'Loading...';
        }
        
        viewModalInstance.show();
        
        const requestUrl = API_ENDPOINT + '?id=' + encodeURIComponent(abilityId) + '&_t=' + Date.now();
        
        fetch(requestUrl)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
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
                if (data && data.success && data.ability) {
                    renderAbilityView(data.ability);
                    if (title) {
                        title.innerHTML = '<span aria-hidden="true">⚡</span><span>' + escapeHtml(data.ability.name || 'Ability Details') + '</span>';
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
                console.error('view_ability_api error', error);
                if (content) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger mb-0';
                    alert.setAttribute('role', 'alert');
                    alert.innerHTML = 'Error loading ability.<br><small>' + escapeHtml(error.message || 'Network or server error') + '</small>';
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
    
    function renderAbilityView(ability) {
        const contentEl = document.getElementById('viewAbilityContent');
        if (!contentEl) return;
        
        let html = '<div class="ability-details">';
        
        // Basic Information
        html += '<h3>Basic Information</h3>';
        html += '<div class="row g-3 mt-2">';
        html += '<div class="col-md-6"><p><strong>Name:</strong> ' + escapeHtml(ability.name || 'N/A') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Category:</strong> ' + escapeHtml(ability.category || 'Unknown') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Min Level:</strong> ' + escapeHtml(ability.min_level ?? 'N/A') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Max Level:</strong> ' + escapeHtml(ability.max_level ?? 'N/A') + '</p></div>';
        if (ability.display_order !== null && ability.display_order !== undefined) {
            html += '<div class="col-md-6"><p><strong>Display Order:</strong> ' + escapeHtml(ability.display_order) + '</p></div>';
        }
        html += '</div>';
        
        // Description
        if (ability.description) {
            html += '<h3>Description</h3>';
            const descEscaped = escapeHtml(ability.description).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + descEscaped + '</div>';
        } else {
            html += '<h3>Description</h3>';
            html += '<p class="text-muted">No description available.</p>';
        }
        
        html += '</div>';
        
        contentEl.innerHTML = html;
        contentEl.setAttribute('aria-busy', 'false');
    }
    
    // Initialize view buttons
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-ability-btn');
        viewButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const abilityId = this.dataset.id;
                if (abilityId) {
                    viewAbility(parseInt(abilityId, 10));
                }
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

