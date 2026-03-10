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
        "name" => "Laws Agent v3",
        "slug" => "laws_agent_v3",
        "description" => "Ask VTM/MET rules questions via Laws-agent-v3 AI search (Cloudflare AI Search + Anthropic). Supports follow-up questions.",
        "data_access" => [
            "/agents/laws_agent_v3/index.php",
            "/agents/laws_agent_v3/api_query.php"
        ],
        "purpose" => "Provide storytellers with lore, mechanics, and citation support on demand.",
        "status" => "Active",
        "last_event" => "Ready for rules questions.",
        "actions" => [
            [
                "label" => "Launch Laws Agent v3",
                "url" => "../agents/laws_agent_v3/"
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
    [
        "name" => "Blood Bonds Agent",
        "slug" => "blood_bonds_agent",
        "description" => "Reads blood drink events, derives bond stage (0-3), provides narrative context for Dialogue Agent. Never enforces behavior.",
        "data_access" => [
            "character_blood_drinks table",
            "/agents/blood_bonds_agent/"
        ],
        "purpose" => "Provide bond context for dialogue branching and narrative systems. Diagnostics for orphaned records and invalid creature pairs.",
        "status" => "Active",
        "last_event" => "Ready to derive bond stage from drink history.",
        "actions" => [
            [
                "label" => "Launch Blood Bonds Agent",
                "url" => "../agents/blood_bonds_agent/index.php"
            ]
        ]
    ],
    [
        "name" => "Ability Agent",
        "slug" => "ability_agent",
        "description" => "Validates and maps ability data from external/source formats into the project's canonical ability schema. Provides validation, alias resolution, and integration with character import workflow.",
        "data_access" => [
            "/agents/ability_agent/",
            "abilities table"
        ],
        "purpose" => "Validate ability data, resolve aliases, handle deprecations, and ensure consistent ability naming across the system.",
        "status" => "Active",
        "last_event" => "Ready to validate and map abilities.",
        "actions" => [
            [
                "label" => "View Abilities",
                "url" => "../agents/ability_agent/abilities_display.php"
            ]
        ]
    ],
    [
        "name" => "Rituals Agent",
        "slug" => "rituals_agent",
        "description" => "Displays and manages all rituals from the rituals_master table. Provides comprehensive ritual information including type, level, name, description, and source.",
        "data_access" => [
            "/agents/rituals_agent/",
            "rituals_master table"
        ],
        "purpose" => "Display all rituals in a sortable table format with key attributes for easy reference and management.",
        "status" => "Active",
        "last_event" => "Ready to display rituals.",
        "actions" => [
            [
                "label" => "View Rituals",
                "url" => "../agents/rituals_agent/rituals_display.php"
            ]
        ]
    ],
    [
        "name" => "Paths Agent",
        "slug" => "paths_agent",
        "description" => "Displays and manages all paths from the paths_master table. Features include sortable columns, real-time search, statistics, and detailed path view with powers organized by level.",
        "data_access" => [
            "/agents/paths_agent/",
            "paths_master table"
        ],
        "purpose" => "Provide comprehensive path information including all powers, system text, challenge types, and challenge notes.",
        "status" => "Active",
        "last_event" => "Ready to display paths.",
        "actions" => [
            [
                "label" => "View Paths",
                "url" => "../agents/paths_agent/paths_display.php"
            ]
        ]
    ],
    [
        "name" => "Discipline Agent",
        "slug" => "discipline_agent",
        "description" => "Manages and displays discipline information for characters. Provides test and debugging interface for discipline data.",
        "data_access" => [
            "/agents/discipline_agent/",
            "discipline-related tables"
        ],
        "purpose" => "Display and manage character disciplines with testing and debugging capabilities.",
        "status" => "Active",
        "last_event" => "Ready to display disciplines.",
        "actions" => [
            [
                "label" => "View Disciplines",
                "url" => "../agents/discipline_agent/discipline_test.php"
            ]
        ]
    ],
    [
        "name" => "Clanbook Viewer",
        "slug" => "clanbook_viewer",
        "description" => "Allows selection and viewing of Phoenix-localized clanbooks. Provides a viewer for clan-specific documentation.",
        "data_access" => [
            "/reference/docs/clanbook_viewer.php",
            "/reference/docs/clanbooks/"
        ],
        "purpose" => "Provide access to Phoenix-localized clanbook documentation for reference.",
        "status" => "Active",
        "last_event" => "Ready to display clanbooks.",
        "actions" => [
            [
                "label" => "View Clanbooks",
                "url" => "../reference/docs/clanbook_viewer.php"
            ]
        ]
    ],
    [
        "name" => "Music Agent",
        "slug" => "music_agent",
        "description" => "Manages music assets, playback cues, and bindings for NPCs and locations. Provides comprehensive music registry administration system.",
        "data_access" => [
            "/agents/music_agent/",
            "music registry tables"
        ],
        "purpose" => "Manage music assets, create playback cues, add music bindings to NPCs and locations, and configure music system settings.",
        "status" => "Active",
        "last_event" => "Ready to manage music assets.",
        "actions" => [
            [
                "label" => "Music Registry",
                "url" => "../agents/music_agent/index.php"
            ]
        ]
    ],
    [
        "name" => "Coterie Agent",
        "slug" => "coterie_agent",
        "description" => "Manages character coterie associations and relationships. Track which characters belong to which coteries, their roles, and coterie types.",
        "data_access" => [
            "/agents/coterie_agent/",
            "character_coteries table"
        ],
        "purpose" => "Provide comprehensive coterie management including viewing, creating, editing, and deleting character-coterie associations.",
        "status" => "Active",
        "last_event" => "Ready to manage coterie associations.",
        "actions" => [
            [
                "label" => "Manage Coteries",
                "url" => "../agents/coterie_agent/index.php"
            ]
        ]
    ],
    [
        "name" => "Influence Agent",
        "slug" => "influence_agent",
        "description" => "Look up what each level of Influence can do in Laws of the Night. Provides quick reference for all 15 types of Influence and their effects at levels 1-5.",
        "data_access" => [
            "/agents/influence_agent/",
            "influence_types table",
            "influence_effects_lookup table"
        ],
        "purpose" => "Provide quick lookup of Influence effects by type and level, helping storytellers and players understand what actions are available at each Influence level.",
        "status" => "Active",
        "last_event" => "Ready to look up Influence effects.",
        "actions" => [
            [
                "label" => "Launch Influence Agent",
                "url" => "../agents/influence_agent/index.php"
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
                                                data-agent-slug="<?= htmlspecialchars($agent['slug']); ?>">
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

<script src="../js/admin_agents.js" defer></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
