<?php
/**
 * Paths Agent - Paths Display Page
 * 
 * Displays all paths from paths_master table in a sortable table format.
 * Shows key attributes: type, name, description, source.
 * 
 * TM-03: Paths Agent Core Implementation - Display Interface
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

// Verify includes path exists (agents/paths_agent -> up 2 levels to root)
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

// Load Paths Agent
require_once __DIR__ . '/src/PathsAgent.php';

$extra_css = ['css/admin-agents.css', 'css/rituals-display.css', 'css/character_view.css'];
require_once $header_path;

// Fetch all paths
$paths = [];
$stats = [
    'total' => 0,
    'by_type' => []
];

try {
    $agent = new PathsAgent($conn);
    $result = $agent->listPathsByType(null, 10000, 0);
    $paths = $result['paths'] ?? [];
    
    $stats['total'] = count($paths);
    
    // Count by type
    foreach ($paths as $path) {
        $type = $path['type'] ?? 'Unknown';
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = 0;
        }
        $stats['by_type'][$type]++;
    }
} catch (Exception $e) {
    $error_message = "Error loading paths: " . htmlspecialchars($e->getMessage());
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
            <h1 class="display-5 text-light fw-bold mb-1">🪄 Paths Overview</h1>
            <p class="lead fst-italic mb-0">Browse and sort all available paths</p>
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
                    <div class="vbn-stat-label">Total Paths</div>
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

    <!-- Search Box -->
    <div class="search-box mb-4">
        <input type="text" id="pathSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="🔍 Search paths by type, name, description, source..." />
    </div>

    <!-- Paths Table -->
    <div class="table-responsive rounded-3">
        <table id="pathsTable" class="table table-dark rituals-table">
            <thead>
                <tr>
                    <th data-column="type" class="sortable">
                        Type <span class="sort-icon"></span>
                    </th>
                    <th data-column="name" class="sortable">
                        Name <span class="sort-icon"></span>
                    </th>
                    <th data-column="description" class="sortable">
                        Description <span class="sort-icon"></span>
                    </th>
                    <th data-column="source" class="sortable">
                        Source <span class="sort-icon"></span>
                    </th>
                    <th class="text-center text-nowrap" style="width: 120px; min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paths)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4" style="color: #d4c4b0;">No paths found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paths as $path): ?>
                        <tr data-path-id="<?= htmlspecialchars($path['id'] ?? '') ?>">
                            <td><?= htmlspecialchars($path['type'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($path['name'] ?? '') ?></td>
                            <td title="<?= htmlspecialchars($path['description'] ?? '') ?>"><?= htmlspecialchars(truncateText($path['description'] ?? '', 60)) ?></td>
                            <td><?= htmlspecialchars($path['source'] ?? 'N/A') ?></td>
                            <td class="text-center text-nowrap" style="width: 120px; min-width: 120px;">
                                <button class="btn btn-primary btn-sm view-path-btn" 
                                        data-id="<?= htmlspecialchars($path['id'] ?? '') ?>"
                                        title="View Path Details"
                                        style="min-width: 40px; padding: 0.25rem 0.5rem;">👁️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Path View Modal -->
<div class="modal fade" id="viewPathModal" tabindex="-1" aria-labelledby="viewPathName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2" id="viewPathName">
                        <span aria-hidden="true">🪄</span>
                        <span>Path Details</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <div id="viewPathContent" class="view-content" aria-live="polite">
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/paths-display.js"></script>
<script>
// Path View Modal Functionality
(function() {
    'use strict';
    
    const API_ENDPOINT = 'api_view_path.php';
    const MODAL_ID = 'viewPathModal';
    let viewModalInstance = null;
    
    // Global function to open path view
    window.viewPath = function(pathId) {
        if (!pathId) return;
        
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Path view modal not found');
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
        
        const content = document.getElementById('viewPathContent');
        const title = document.getElementById('viewPathName');
        
        if (title) {
            title.innerHTML = '<span aria-hidden="true">🪄</span><span>Path Details</span>';
        }
        
        if (content) {
            content.setAttribute('aria-busy', 'true');
            content.textContent = 'Loading...';
        }
        
        viewModalInstance.show();
        
        const requestUrl = API_ENDPOINT + '?id=' + encodeURIComponent(pathId) + '&_t=' + Date.now();
        
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
                if (data && data.success && data.path) {
                    renderPathView(data.path, data.powers || []);
                    if (title) {
                        title.innerHTML = '<span aria-hidden="true">🪄</span><span>' + escapeHtml(data.path.name || 'Path Details') + '</span>';
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
                console.error('view_path_api error', error);
                if (content) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger mb-0';
                    alert.setAttribute('role', 'alert');
                    alert.innerHTML = 'Error loading path.<br><small>' + escapeHtml(error.message || 'Network or server error') + '</small>';
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
    
    function renderPathView(path, powers) {
        const contentEl = document.getElementById('viewPathContent');
        if (!contentEl) return;
        
        let html = '<div class="path-details">';
        
        // Basic Information
        html += '<h3>Basic Information</h3>';
        html += '<div class="row g-3 mt-2">';
        html += '<div class="col-md-6"><p><strong>Name:</strong> ' + escapeHtml(path.name || 'N/A') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Type:</strong> ' + escapeHtml(path.type || 'Unknown') + '</p></div>';
        html += '<div class="col-md-6"><p><strong>Source:</strong> ' + escapeHtml(path.source || 'N/A') + '</p></div>';
        if (path.created_at) {
            const created = new Date(path.created_at);
            html += '<div class="col-md-6"><p><strong>Created:</strong> ' + created.toLocaleString() + '</p></div>';
        }
        html += '</div>';
        
        // Description
        if (path.description) {
            html += '<h3>Description</h3>';
            const descEscaped = escapeHtml(path.description).replace(/\n/g, '<br>');
            html += '<div class="text-content">' + descEscaped + '</div>';
        }
        
        // Path Powers
        if (powers && powers.length > 0) {
            html += '<h3>Path Powers</h3>';
            html += '<div class="powers-list">';
            
            // Group powers by level
            const powersByLevel = {};
            powers.forEach(function(power) {
                const level = power.level || 0;
                if (!powersByLevel[level]) {
                    powersByLevel[level] = [];
                }
                powersByLevel[level].push(power);
            });
            
            // Display powers by level
            const levels = Object.keys(powersByLevel).sort(function(a, b) {
                return parseInt(a) - parseInt(b);
            });
            
            levels.forEach(function(level) {
                html += '<div class="power-level mb-4">';
                html += '<h4>Level ' + level + '</h4>';
                
                powersByLevel[level].forEach(function(power) {
                    html += '<div class="power-item mb-3 p-3 bg-dark rounded">';
                    html += '<h5>' + escapeHtml(power.power_name || 'Unnamed Power') + '</h5>';
                    
                    if (power.system_text) {
                        const systemEscaped = escapeHtml(power.system_text).replace(/\n/g, '<br>');
                        html += '<div class="text-content mb-2"><strong>System:</strong> ' + systemEscaped + '</div>';
                    }
                    
                    if (power.challenge_type) {
                        html += '<div class="mb-2"><strong>Challenge Type:</strong> <span class="badge bg-info">' + escapeHtml(power.challenge_type) + '</span></div>';
                    }
                    
                    if (power.challenge_notes) {
                        const notesEscaped = escapeHtml(power.challenge_notes).replace(/\n/g, '<br>');
                        html += '<div class="text-content"><strong>Challenge Notes:</strong> ' + notesEscaped + '</div>';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            html += '<h3>Path Powers</h3>';
            html += '<p class="opacity-75">No powers found for this path.</p>';
        }
        
        html += '</div>';
        
        contentEl.innerHTML = html;
        contentEl.setAttribute('aria-busy', 'false');
    }
    
    // Initialize view buttons
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-path-btn');
        viewButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const pathId = this.dataset.id;
                if (pathId) {
                    viewPath(parseInt(pathId, 10));
                }
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

