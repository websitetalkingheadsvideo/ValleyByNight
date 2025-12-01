<?php
// /admin/agents.php
// Agents dashboard for Valley by Night
// Updated 2025-11-09: VbN Agents Page Styling + Link Integration
// NOTE: This page is read-only and uses a local array for now.
// Do NOT add CLI, Powershell, or remote DB discovery here.

$extra_css = ['css/admin-agents.css'];

$agents = [
    [
        "name" => "Character Agent",
        "slug" => "character_agent",
        "description" => "Monitors character JSON files to keep lore consistent, generate briefs, and suggest plot hooks.",
        "data_access" => [
            "/agents/character_agent/data/Characters/",
            "/agents/character_agent/data/History/",
            "/agents/character_agent/data/Plots/"
        ],
        "purpose" => "Detect new character sheets, validate fields, create character briefs, and flag continuity issues.",
        "status" => "Active",
        "last_event" => "Waiting for new character JSON...",
        "actions" => [
            [
                "label" => "Search Character Information",
                "url" => "../agents/character_agent/characters.php"
            ],
            [
                "label" => "Generate Reports",
                "url" => "../agents/character_agent/generate_reports.php"
            ],
            [
                "label" => "View Reports",
                "url" => "../agents/character_agent/reports/"
            ],
            [
                "label" => "View Config",
                "url" => "../agents/character_agent/config/"
            ]
        ]
    ],
    [
        "name" => "Laws Agent",
        "slug" => "laws_agent",
        "description" => "Ask canon questions across the MET library, surface rule citations, and validate mechanics before pushing updates.",
        "data_access" => [
            "/agents/laws_agent/index.php",
            "/agents/laws_agent/knowledge-base/"
        ],
        "purpose" => "Provide storytellers with lore, mechanics, and citation support on demand.",
        "status" => "Active",
        "last_event" => "Responded to Camarilla tradition query moments ago.",
        "actions" => [
            [
                "label" => "Launch Laws Agent",
                "url" => "/agents/laws_agent/"
            ]
        ]
    ],
    [
        "name" => "Camarilla Positions Agent",
        "slug" => "camarilla_positions_agent",
        "description" => "Query current Camarilla position holders and historical assignments. Answers questions about who holds which office and when.",
        "data_access" => [
            "/admin/camarilla_positions.php",
            "camarilla_positions table",
            "camarilla_position_assignments table"
        ],
        "purpose" => "Provide quick access to current position holders and position history for any character or office.",
        "status" => "Active",
        "last_event" => "Ready to answer position queries.",
        "actions" => [
            [
                "label" => "Launch Positions Agent",
                "url" => "camarilla_positions.php"
            ]
        ]
    ],
    [
        "name" => "Rumor Agent",
        "slug" => "rumor_agent",
        "description" => "Manage and monitor rumor-related interactions. Generates ambient rumors based on recent character or location changes.",
        "data_access" => [
            "/agents/rumors_agent/",
            "/reference/rumors/",
            "data/state/rumor_history_*.json"
        ],
        "purpose" => "Spin ambient rumors based on recent character or location changes, and manage rumor distribution throughout the chronicle.",
        "status" => "Active",
        "last_event" => "Ready to generate and manage rumors.",
        "actions" => [
            [
                "label" => "Launch Rumor Agent",
                "url" => "rumor_viewer.php"
            ]
        ]
    ],
    [
        "name" => "Boon Agent",
        "slug" => "boon_agent",
        "description" => "Monitors and validates boons according to Laws of the Night Revised mechanics. Tracks favor-debt, detects violations, integrates with Harpy systems, and analyzes the social economy of prestation.",
        "data_access" => [
            "/admin/boon_ledger.php",
            "/admin/api_boons.php",
            "boons table",
            "/agents/boon_agent/reports/",
            "/agents/boon_agent/logs/"
        ],
        "purpose" => "Validate boon mechanics, detect dead debts, flag unregistered boons, track scandal violations, and provide insights into the boon economy. Integrates with Harpy positions for registration tracking.",
        "status" => "Active",
        "last_event" => "Ready to monitor boons and validate mechanics.",
        "actions" => [
            [
                "label" => "Launch Boon Agent",
                "url" => "boon_agent_viewer.php"
            ],
            [
                "label" => "View Boon Ledger",
                "url" => "boon_ledger.php"
            ]
        ]
    ],
    // Future agents can be appended here.
];

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-panel-container agents-panel container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h1 class="display-5 text-light fw-bold mb-1">👥 Agents</h1>
        <p class="agents-intro lead fst-italic mb-0">Automated helpers that keep Valley by Night data fresh, consistent, and actionable.</p>
    </div>

    <div class="agents-grid row g-3 g-lg-4 mb-4">
        <?php if (empty($agents)): ?>
            <div class="col-12">
                <div class="card shadow-sm text-center py-5">
                    <div class="card-body">
                        <p class="mb-0 text-light">No agents configured yet.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($agents as $agent): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card h-100 d-flex flex-column shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h3 class="card-title fw-bold mb-0"><?= htmlspecialchars($agent['name']); ?></h3>
                                <span class="badge agent-status-badge" data-status="<?= htmlspecialchars(strtolower($agent['status'])); ?>">
                                    <?= htmlspecialchars($agent['status']); ?>
                                </span>
                            </div>
                            <p class="card-text mb-3">
                                <?= htmlspecialchars($agent['description']); ?>
                            </p>
                            <div class="mb-3">
                                <p class="small fw-bold text-white mb-2">Purpose</p>
                                <p class="card-text small mb-0"><?= htmlspecialchars($agent['purpose']); ?></p>
                            </div>
                            <div class="mb-3">
                                <p class="small fw-bold text-white mb-2">Data Access</p>
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach ($agent['data_access'] as $path): ?>
                                        <li class="mb-1"><code class="text-white"><?= htmlspecialchars($path); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <p class="small text-white mb-0">Last event: <span class="text-light"><?= htmlspecialchars($agent['last_event']); ?></span></p>
                        </div>
                        <?php if (!empty($agent['actions'])): ?>
                            <div class="card-footer mt-auto d-flex flex-column flex-sm-row gap-2">
                                <?php foreach ($agent['actions'] as $action): ?>
                                    <?php if ($action['label'] === 'View Config' && $agent['slug'] === 'character_agent'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm flex-fill"
                                                onclick="openConfigModal('<?= htmlspecialchars($agent['slug']); ?>')">
                                            <?= htmlspecialchars($action['label']); ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($action['url']); ?>"
                                           class="btn btn-outline-danger btn-sm flex-fill"
                                           <?php if (!empty($action['target'])): ?>target="<?= htmlspecialchars($action['target']); ?>"<?php endif; ?>
                                           <?php if (!empty($action['rel'])): ?>rel="<?= htmlspecialchars($action['rel']); ?>"<?php endif; ?>>
                                            <?= htmlspecialchars($action['label']); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <section class="planned-agents-panel" id="planned-agents">
        <h2 class="mb-2">Planned Agents</h2>
        <p class="mb-3">Coming soon — additional automated agents to deepen chronicle support:</p>
        <ul class="planned-agents-list mb-0">
            <li>Influence Agent — surfaces mortal influence opportunities across Bureaucracy, Law, Finance, and more.</li>
            <li>Lore/History Agent — answers city history questions and maintains a living timeline.</li>
        </ul>
    </section>
</div>

<!-- Agent Config Modal -->
<?php
$modalId = 'agentConfigModal';
$labelId = 'agentConfigModalLabel';
$size = 'lg';
include __DIR__ . '/../includes/modal_base.php';
?>

<script>
async function openConfigModal(agentSlug) {
    const modalElement = document.getElementById('agentConfigModal');
    if (!modalElement) {
        console.error('agentConfigModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete');
        return;
    }
    
    // Set title and show loading state
    modalTitle.textContent = '⚙️ Agent Configuration';
    modalBody.setAttribute('aria-busy', 'true');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-light">Loading configuration...</p></div>';
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    
    // Show modal
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
    
    try {
        // Fetch config data
        const response = await fetch(`../agents/${agentSlug}/api_get_config.php`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load configuration');
        }
        
        // Build modal content
        let content = '';
        
        if (!data.config_exists) {
            content = `
                <div class="alert alert-warning mb-0">
                    <h5 class="alert-heading">Configuration File Not Found</h5>
                    <p class="mb-0">The configuration file <code>settings.json</code> does not exist yet. The agent will use default settings.</p>
                </div>
            `;
        } else if (data.config_error) {
            content = `
                <div class="alert alert-danger mb-0">
                    <h5 class="alert-heading">Configuration Error</h5>
                    <p class="mb-0">${escapeHtml(data.config_error)}</p>
                </div>
            `;
        } else {
            content = `
                <div class="mb-4">
                    <h3 class="text-light mb-3">Current Configuration</h3>
                    <pre class="bg-dark border border-danger rounded p-3 text-light" style="max-height: 400px; overflow-y: auto; font-size: 0.85em;"><code>${escapeHtml(JSON.stringify(data.config_data, null, 2))}</code></pre>
                </div>
                <div class="card bg-dark border-danger">
                    <div class="card-body">
                        <h3 class="text-light mb-3">Configuration Information</h3>
                        <ul class="text-light mb-0">
                            <li><strong>Config File Path:</strong> <code>${escapeHtml(data.config_file)}</code></li>
                            <li><strong>File Status:</strong> <span class="text-success">Exists</span></li>
                            <li><strong>Last Modified:</strong> ${escapeHtml(data.file_info.last_modified)}</li>
                            <li><strong>File Size:</strong> ${escapeHtml(data.file_info.file_size_formatted)}</li>
                        </ul>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = content;
        modalBody.setAttribute('aria-busy', 'false');
        
    } catch (error) {
        console.error('Error loading configuration:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger mb-0">
                <h5 class="alert-heading">Error Loading Configuration</h5>
                <p class="mb-0">${escapeHtml(error.message)}</p>
            </div>
        `;
        modalBody.setAttribute('aria-busy', 'false');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
