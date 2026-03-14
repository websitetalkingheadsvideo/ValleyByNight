<?php
/**
 * Valley by Night - Home Dashboard
 * Main landing page with role-based views
 */

// Define version constant
// Include centralized version management
require_once __DIR__ . '/includes/version.php';

// Start session
session_start();

require_once __DIR__ . '/includes/supabase_client.php';

// Check for authentication bypass
require_once 'includes/auth_bypass.php';

// Check if user is logged in (or bypass is enabled)
if (!isset($_SESSION['user_id']) && !isAuthBypassEnabled()) {
    header('Location: login.php');
    exit;
}

// If bypass is enabled, set up guest session
if (isAuthBypassEnabled() && !isset($_SESSION['user_id'])) {
    setupBypassSession();
}

// Get user information
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';

// SECURITY: Verify role against database to prevent session tampering
require_once 'includes/verify_role.php';
$user_role = verifyUserRole(null, $user_id);

// Determine if user is admin/storyteller
$is_admin = isAdminUser($user_role);

// Chronicle information
$tagline = "On your first night among the Kindred, the Prince dies—and the city of Phoenix bleeds intrigue";
$chronicle_summary = "Phoenix, 1994. On the very night you're introduced to Kindred society, the Prince is murdered, plunging the Camarilla into chaos. As a neonate with everything to prove, you must navigate shifting alliances, enforce the Masquerade, and survive a city where Anarchs, Sabbat, Giovanni, and darker powers all compete for control. The Prince's death is only the beginning.";

// Include header with dashboard CSS
$extra_css = ['css/dashboard.css'];
include 'includes/header.php';
?>

<div class="page-content container py-4">
    <!-- Join Game Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card join-game-card">
                <div class="card-body text-center">
                    <h3 class="card-title">Join Game</h3>
                    <p class="card-text">Join an active game session and participate in the chronicle's ongoing story.</p>
                    <a href="#" class="btn btn-primary" onclick="alert('Coming soon!'); return false;">Join Game</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Player Resources Section -->
    <div class="row mb-4 player-resources-row">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Learn About the Clans</h3>
                    <p class="card-text">Explore the thirteen clans of the Camarilla and their unique characteristics, histories, and roles in Phoenix.</p>
                    <a href="clans/index.php" class="btn btn-primary">View Clans</a>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Take the Character Quiz</h3>
                    <p class="card-text">Discover which clan best matches your personality and playstyle through our interactive questionnaire.</p>
                    <a href="questionnaire.php" class="btn btn-primary">Start Quiz</a>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Phoenix Map</h3>
                    <p class="card-text">Explore the interactive map of Phoenix and discover key locations throughout the city.</p>
                    <a href="phoenix_map.php" class="btn btn-primary">View Map</a>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Chat Room</h3>
                    <p class="card-text">Connect with other kindred and engage in character roleplay conversations.</p>
                    <a href="chat.php" class="btn btn-primary">Enter Chat</a>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Glossary</h3>
                    <p class="card-text">Browse definitions of World of Darkness terms and concepts from the source material.</p>
                    <a href="reference/glossary.php" class="btn btn-primary">View Glossary</a>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Pre-Game Primer</h3>
                    <p class="card-text">Learn what your character knows before Session 1 and understand the chronicle's opening context.</p>
                    <a href="reference/pre_game_primer.php" class="btn btn-primary">Read Primer</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
        <!-- ADMIN/STORYTELLER VIEW -->
        <div class="dashboard-admin">
            <h2 class="section-heading">Storyteller's Domain</h2>
            <p class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?>. The chronicle awaits your guidance.</p>
            
            <!-- Statistics Panel -->
            <div class="stats-panel row g-4 mb-5">
                <?php
                // Get character statistics
                $stats = ['total' => 0, 'npcs' => 0, 'pcs' => 0];
                $stats_error = '';
                try {
                    $characterRows = supabase_table_get('characters', [
                        'select' => 'player_name'
                    ]);
                    $stats['total'] = count($characterRows);
                    foreach ($characterRows as $characterRow) {
                        $playerName = trim((string)($characterRow['player_name'] ?? ''));
                        if ($playerName === 'NPC') {
                            $stats['npcs']++;
                        } elseif ($playerName !== '') {
                            $stats['pcs']++;
                        }
                    }
                } catch (Throwable $e) {
                    error_log('index.php stats query failed: ' . $e->getMessage());
                    $stats_error = $e->getMessage();
                }
                ?>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="vbn-stat-label">Total Characters</div>
                    </div>
                </div>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-stat-number"><?php echo $stats['pcs'] ?? 0; ?></div>
                        <div class="vbn-stat-label">Player Characters</div>
                    </div>
                </div>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-stat-number"><?php echo $stats['npcs'] ?? 0; ?></div>
                        <div class="vbn-stat-label">NPCs</div>
                    </div>
                </div>
                <?php if ($stats_error !== ''): ?>
                <div class="col-12">
                    <div class="alert alert-warning mb-0">Character stats temporarily unavailable.</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Admin Actions -->
            <nav aria-label="Admin Actions">
            <div class="row g-4 mb-5">
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">🧪</div>
                        <h3 class="card-title">Clan Discovery Quiz</h3>
                        <p class="card-text">Test the character creation questionnaire</p>
                        <a href="questionnaire.php" class="btn btn-primary">Take Quiz</a>
                    </div>
                </div>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">🤖</div>
                        <h3 class="card-title">Agents Dashboard</h3>
                        <p class="card-text">Monitor automation helpers and review agent activity</p>
                        <a href="admin/agents.php" class="btn btn-primary">Open Agents</a>
                    </div>
                </div>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">📜</div>
                        <h3 class="card-title">Character List</h3>
                        <p class="card-text">View, edit, and delete characters</p>
                        <a href="admin/admin_panel.php" class="btn btn-primary">View Characters</a>
                    </div>
                </div>
                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">✨</div>
                        <h3 class="card-title">Create Character</h3>
                        <p class="card-text">Bring a new kindred into the world</p>
                        <a href="lotn_char_create.php" class="btn btn-primary">Create New</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">📍</div>
                        <h3 class="card-title">Locations Database</h3>
                        <p class="card-text">Manage game locations and character assignments</p>
                        <a href="admin/admin_locations.php" class="btn btn-primary">Manage Locations</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">🗺️</div>
                        <h3 class="card-title">Phoenix Map</h3>
                        <p class="card-text">Explore the interactive map of Phoenix locations</p>
                        <a href="phoenix_map.php" class="btn btn-primary">View Map</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">🧰</div>
                        <h3 class="card-title">Items Database</h3>
                        <p class="card-text">Manage equipment and artifacts</p>
                        <a href="admin/admin_items.php" class="btn btn-primary">Manage Items</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">📋</div>
                        <h3 class="card-title">Questionnaire Admin</h3>
                        <p class="card-text">Manage and configure the character creation questionnaire questions, answers, and clan weightings</p>
                        <a href="admin/questionnaire_admin.php" class="btn btn-primary">Manage Questions</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">📊</div>
                        <h3 class="card-title">Project Summary</h3>
                        <p class="card-text">Comprehensive overview of the Valley by Night project</p>
                        <a href="PROJECT_SUMMARY.html" class="btn btn-primary">View Summary</a>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon" aria-hidden="true">✏️</div>
                        <h3 class="card-title">Character Editor</h3>
                        <p class="card-text">Quick character editing and data quality tools</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="tools/repeatable/character-data/quick-edit.php" class="btn btn-primary btn-sm">Quick Edit</a>
                            <a href="tools/repeatable/character-data/index.php" class="btn btn-secondary btn-sm">Data Quality</a>
                        </div>
                    </div>
                </div>

                <div class="card col-md-4 col-sm-6 disabled opacity-50">
                    <div class="card-body text-center">
                        <div class="vbn-card-icon">📖</div>
                        <h3 class="card-title">AI Plots Manager</h3>
                        <p class="card-text">Coming soon: Weave storylines with AI</p>
                        <span class="vbn-gothic-button-disabled">Coming Soon</span>
                    </div>
                </div>
            </div>
            </nav>
        </div>
        
    <?php else: ?>
        <!-- PLAYER VIEW -->
        <div class="dashboard-player">
            <!-- Chronicle Tagline -->
            <div class="dashboard-hero">
                <div class="chronicle-tagline">
                    <p class="tagline-text"><?php echo htmlspecialchars($tagline); ?></p>
                </div>
            </div>
            
            <!-- Chronicle Summary -->
            <div class="chronicle-summary mb-5">
                <div class="card">
                    <div class="card-body">
                        <h2 class="chronicle-title card-title text-center">The Chronicle Begins</h2>
                        <p class="chronicle-text card-text"><?php echo htmlspecialchars($chronicle_summary); ?></p>
                    </div>
                </div>
            </div>
            
            <h2 class="section-heading">Your Domain</h2>
            <p class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?>. The night is yours to command.</p>
            
            <!-- Player Actions -->
            <nav aria-label="Player Actions">
            <div class="d-flex justify-content-center gap-4 mb-5 flex-wrap">
                <a href="lotn_char_create.php" class="btn btn-primary btn-lg d-flex align-items-center gap-3">
                    <span class="btn-icon">✏️</span>
                    <span>Create New Character</span>
                </a>
                
                <a href="questionnaire.php" class="btn btn-primary btn-lg d-flex align-items-center gap-3 btn-questionnaire">
                    <span class="btn-icon">🌟</span>
                    <span>Discover Your Clan</span>
                </a>
            </div>
            </nav>
            
            <!-- Player's Characters -->
            <div class="character-list" role="region" aria-labelledby="playerCharactersHeading">
                <h3 id="playerCharactersHeading" class="list-heading">Your Characters</h3>
                <?php
                // Get player's characters
                $playerCharacters = [];
                $player_chars_error = '';
                try {
                    $playerCharacters = supabase_table_get('characters', [
                        'select' => 'id,character_name,status,concept,clan,user_id',
                        'user_id' => 'eq.' . (string) $user_id,
                        'order' => 'status.desc,character_name.asc'
                    ]);
                } catch (Throwable $e) {
                    error_log('index.php player characters query failed: ' . $e->getMessage());
                    $player_chars_error = $e->getMessage();
                }

                if ($player_chars_error !== ''):
                    ?>
                    <div class="alert alert-warning">Your characters could not be loaded. Please try again later.</div>
                    <?php
                elseif (!empty($playerCharacters)):
                    // Helper function to convert clan name to CSS class
                    function getClanClass($clan_name) {
                        if (empty($clan_name)) return '';
                        $normalized = strtolower(trim($clan_name));
                        // Handle special cases
                        $mapping = [
                            'followers of set' => 'setite',
                            'setite' => 'setite',
                            'toreador' => 'toreador',
                            'brujah' => 'brujah',
                            'ventrue' => 'ventrue',
                            'nosferatu' => 'nosferatu',
                            'malkavian' => 'malkavian',
                            'giovanni' => 'giovanni',
                            'gangrel' => 'gangrel',
                            'tremere' => 'tremere',
                            'assamite' => 'assamite',
                            'banu haqim' => 'assamite',
                            'lasombra' => 'lasombra',
                            'tzimisce' => 'tzimisce',
                            'ravnos' => 'ravnos',
                        ];
                        if (isset($mapping[$normalized])) {
                            return 'clan-' . $mapping[$normalized];
                        }
                        // Default: convert to lowercase, replace spaces with hyphens
                        return 'clan-' . preg_replace('/[^a-z0-9]+/', '-', $normalized);
                    }
                    foreach ($playerCharacters as $character):
                        $character['clan_name'] = $character['clan'] ?? '';
                        $clan_class = getClanClass($character['clan_name'] ?? '');
                ?>
                    <div class="card mb-4 <?php echo htmlspecialchars($clan_class); ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                <h4 class="card-title d-flex align-items-center gap-2 mb-0">
                                    <?php echo htmlspecialchars($character['character_name']); ?>
                                    <?php if ($character['status'] == 'draft'): ?>
                                        <span class="badge bg-warning text-dark">DRAFT</span>
                                    <?php endif; ?>
                                </h4>
                                <span class="vbn-character-clan"><?php echo htmlspecialchars($character['clan_name']); ?></span>
                            </div>
                            <div class="mb-3">
                                <p class="card-text mb-0">
                                    <strong>Concept:</strong> <?php echo htmlspecialchars($character['concept'] ?? 'Unknown'); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <a href="lotn_char_create.php?id=<?php echo $character['id']; ?>" class="btn btn-secondary">
                                    View/Edit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="empty-state">
                        <p>You have not created any characters yet.</p>
                        <p class="empty-hint">Begin your journey by creating your first kindred.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Additional Player Links -->
            <nav aria-label="Player Links">
            <div class="player-links">

                <div class="vbn-link-card">
                    <div class="vbn-card-icon">💬</div>
                    <h3>Chat Room</h3>
                    <p>Connect with other kindred (Coming Soon)</p>
                    <span class="vbn-gothic-button-disabled">Unavailable</span>
                </div>
            </div>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
// Defensive JavaScript for index.php navigation links
// Prevents "Element not found" errors from global scripts
document.addEventListener('DOMContentLoaded', function() {
    // Ensure all navigation links work correctly
    const navLinks = document.querySelectorAll('.vbn-action-card a.btn-primary, .vbn-link-card a');
    navLinks.forEach(function(link) {
        // Add null check before any potential enhancement
        if (link) {
            // Ensure links are clickable
            link.addEventListener('click', function(e) {
                // Allow default navigation behavior
                // No prevention needed for standard anchor tags
            });
        }
    });
    
    // Defensive check for any modal-related scripts that might fail
    const modals = document.querySelectorAll('.modal');
    if (modals.length === 0) {
        // No modals on this page - that's fine
        // This prevents errors from scripts expecting modals
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>

