<?php
declare(strict_types=1);

/**
 * Character Portraits - Display character_image for every character.
 * Reuses admin_panel sorting and creature-type (clan) filter logic.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../includes/character_portrait_resolver.php';

$extra_css = ['css/modal.css', 'css/admin-agents.css', 'css/admin_panel.css', 'css/character_view.css', 'css/character_portraits.css'];
include __DIR__ . '/../includes/header.php';

$base_dir = dirname(__DIR__);
$upload_dir = $base_dir . '/uploads/characters';
$reference_dir = $base_dir . '/reference/Characters/Images';

function render_clan_badge_portraits(string $clan): string {
    $name = trim($clan);
    if ($name === '') {
        return '<span class="clan-badge clan-badge-empty">—</span>';
    }
    static $palette = [
        'assamite' => '#2E3192',
        'banu haqim' => '#2E3192',
        'brujah' => '#B22222',
        'brujah antitribu' => '#8B1A1A',
        'caitiff' => '#708090',
        'followers of set' => '#8B6C37',
        'setite' => '#8B6C37',
        'daughter of cacophony' => '#FF69B4',
        'daughters of cacophony' => '#FF69B4',
        'gangrel' => '#228B22',
        'gangrel antitribu' => '#1A6B1A',
        'giovanni' => '#556B2F',
        'lasombra' => '#1A1A40',
        'malkavian' => '#6A0DAD',
        'malkavian antitribu' => '#5A0A8D',
        'nosferatu' => '#556B5D',
        'nosferatu antitribu' => '#455B4D',
        'ravnos' => '#008B8B',
        'toreador' => '#C71585',
        'toreador antitribu' => '#A71265',
        'tremere' => '#8B008B',
        'tremere antitribu' => '#6B006B',
        'tzimisce' => '#99CC00',
        'tzimisce antitribu' => '#79AC00',
        'ventrue' => '#1F3A93',
        'ventrue antitribu' => '#172A73',
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
    $hue = (int) abs(crc32($key)) % 360;
    $color = sprintf('hsl(%d, 45%%, 38%%)', $hue);
    return sprintf(
        '<span class="clan-badge" style="--clan-badge-color:%s;background-color:var(--clan-badge-color);">%s</span>',
        $color,
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
    );
}

$characters_error = '';
$characters = [];
try {
    $raw = supabase_table_get('characters', [
        'select' => '*',
        'order' => 'created_at.desc,id.desc',
    ]);
    $characters = is_array($raw) ? $raw : [];
} catch (Throwable $e) {
    error_log('character_portraits: characters list failed: ' . $e->getMessage());
    $characters_error = $e->getMessage();
}

function row_val(array $row, string $key, string $altKey = ''): string {
    $v = $row[$key] ?? $row[$altKey] ?? '';
    if (is_array($v) || is_object($v)) {
        return '';
    }
    return trim((string) $v);
}
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <h1 class="panel-title display-5 text-light fw-bold mb-1">Character Portraits</h1>
    <p class="panel-subtitle lead text-light fst-italic mb-4">Character images for every character in the database</p>

    <!-- Navigation: Characters + Portraits only -->
    <nav class="admin-nav row g-2 g-md-3 mb-4" aria-label="Admin Navigation">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Characters</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="character_portraits.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center active">Portraits</a>
        </div>
    </nav>

    <!-- Character Types (creature-type panels) -->
    <nav class="admin-nav row g-2 g-md-3 mb-4" aria-label="Character Types" id="character-types">
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="wraith_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Wraith</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="werewolf_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Garou</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="ghoul_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Ghoul</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="mage_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Mage</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="demon_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Demon</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="mortal_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Mortal</a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg">
            <a href="supernatural_entities_admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center">Supernatural</a>
        </div>
    </nav>

    <!-- Filter Controls (same as admin_panel) -->
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
            <input type="text" id="characterSearch" class="form-control form-control-sm bg-dark text-light border-danger" placeholder="Search name, clan, player, generation..." />
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
            <button type="button" id="sortByDateBtn" class="btn btn-outline-danger btn-sm">Sort by Date</button>
        </div>
    </div>

    <!-- Character portraits grid (table structure kept for filter/sort JS) -->
    <div class="character-portraits-grid-wrapper rounded-3">
        <table class="character-table table table-dark align-middle mb-0" id="characterTable" data-show-all="true">
            <thead>
                <tr>
                    <th data-sort="character_name" class="text-start d-none">Name</th>
                    <th data-sort="clan" class="text-center d-none">Clan</th>
                </tr>
            </thead>
            <tbody class="character-portrait-cards-tbody">
                <?php
                if ($characters_error !== '') {
                    echo '<tr><td colspan="2" class="text-center text-warning">Failed to load characters: ' . htmlspecialchars($characters_error, ENT_QUOTES, 'UTF-8') . '</td></tr>';
                } elseif (empty($characters)) {
                    echo "<tr><td colspan='2'>No characters found.</td></tr>";
                } else {
                    $path_prefix = $path_prefix ?? '';
                    foreach ($characters as $char) {
                        try {
                            $char = is_array($char) ? $char : [];
                            $charName = row_val($char, 'character_name', 'Character_Name');
                            $charId = (int) ($char['id'] ?? $char['Id'] ?? 0);
                            $is_npc = (row_val($char, 'player_name', 'Player_Name') === 'NPC');
                            $playerName = row_val($char, 'player_name', 'Player_Name');
                            $playerName = $playerName !== '' ? $playerName : ($is_npc ? 'NPC' : '—');
                            $clanName = row_val($char, 'clan', 'Clan');
                            $clanName = $clanName !== '' ? $clanName : 'Unknown';
                            $generation = row_val($char, 'generation', 'Generation');
                            $statusRaw = row_val($char, 'status', 'Status');
                            $status = $statusRaw !== '' ? strtolower($statusRaw) : 'active';
                            $allowedStatuses = ['active', 'inactive', 'archived', 'dead', 'missing', 'npc', 'unknown'];
                            if (!in_array($status, $allowedStatuses, true)) {
                                $status = 'unknown';
                            }
                            $camarilla = row_val($char, 'camarilla_status', 'Camarilla_Status');
                            $camarilla = $camarilla !== '' ? $camarilla : 'Unknown';
                            $owner = row_val($char, 'owner_username', 'Owner_Username');
                            $owner = $owner !== '' ? $owner : '—';

                            $raw_character_image = row_val($char, 'character_image', 'Character_Image');
                            $raw_portrait_name = row_val($char, 'portrait_name', 'Portrait_Name');
                            $raw_portrait_name = $raw_portrait_name !== '' ? $raw_portrait_name : null;

                            $resolved = resolve_character_portrait(
                                $charName,
                                $raw_portrait_name,
                                $raw_character_image !== '' ? $raw_character_image : null,
                                $upload_dir,
                                $reference_dir
                            );

                            $portrait_filename = $resolved['resolved_filename'];
                            if ($portrait_filename === null && $raw_character_image !== '') {
                                $portrait_filename = basename(str_replace('\\', '/', $raw_character_image));
                            }
                            $portrait_src = $portrait_filename !== null && $portrait_filename !== ''
                                ? $path_prefix . 'uploads/characters/' . rawurlencode($portrait_filename)
                                : null;
                            $createdAt = row_val($char, 'created_at', 'Created_At');
                        } catch (Throwable $e) {
                            error_log('character_portraits: row error for id ' . ($char['id'] ?? '?') . ': ' . $e->getMessage());
                            $charName = '—';
                            $charId = 0;
                            $is_npc = false;
                            $playerName = '—';
                            $clanName = 'Unknown';
                            $generation = '';
                            $status = 'unknown';
                            $camarilla = 'Unknown';
                            $owner = '—';
                            $portrait_src = null;
                            $createdAt = '';
                        }
                ?>
                <tr class="character-row"
                    data-id="<?php echo $charId; ?>"
                    data-type="<?php echo $is_npc ? 'npc' : 'pc'; ?>"
                    data-name="<?php echo htmlspecialchars($charName, ENT_QUOTES, 'UTF-8'); ?>"
                    data-clan="<?php echo htmlspecialchars($clanName, ENT_QUOTES, 'UTF-8'); ?>"
                    data-player="<?php echo htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'); ?>"
                    data-generation="<?php echo htmlspecialchars($generation, ENT_QUOTES, 'UTF-8'); ?>"
                    data-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
                    data-camarilla="<?php echo htmlspecialchars($camarilla, ENT_QUOTES, 'UTF-8'); ?>"
                    data-owner="<?php echo htmlspecialchars($owner, ENT_QUOTES, 'UTF-8'); ?>"
                    data-created="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>">
                    <td colspan="2" class="character-portrait-card-cell p-2">
                        <div class="character-portrait-card card h-100 bg-dark border-danger">
                            <div class="character-portrait-card-img-wrap">
                                <?php if ($portrait_src !== null): ?>
                                <img src="<?php echo htmlspecialchars($portrait_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($charName, ENT_QUOTES, 'UTF-8'); ?>" class="character-portrait-img card-img-top" width="512" height="512" loading="lazy" />
                                <?php else: ?>
                                <div class="character-portrait-placeholder card-img-top" aria-label="No image">No image</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body text-center">
                                <strong class="card-title text-light d-block"><?php echo htmlspecialchars($charName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="card-text"><?php echo render_clan_badge_portraits($clanName); ?></div>
                            </div>
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

    <!-- Pagination -->
    <div class="pagination-controls d-flex flex-column flex-lg-row gap-3 align-items-center justify-content-between" id="paginationControls">
        <div class="pagination-info fw-semibold">
            <span id="paginationInfo">Showing 1-<?php echo count($characters); ?> of <?php echo count($characters); ?> characters</span>
        </div>
        <div class="pagination-buttons d-flex flex-wrap gap-2 justify-content-center" id="paginationButtons"></div>
    </div>
</div>

<?php
$extra_js = ['js/admin_panel.js'];
include __DIR__ . '/../includes/footer.php';
