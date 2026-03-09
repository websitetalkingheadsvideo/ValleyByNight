<?php
/**
 * Character Search Agent
 * 
 * Interface for searching and querying character information from the database.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.10.9');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Check for AJAX requests BEFORE including header (which outputs HTML)
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// If AJAX request, handle it and return JSON without loading the full page (Supabase)
if ($isAjax && $action === 'search') {
    require_once __DIR__ . '/../../includes/supabase_client.php';
    header('Content-Type: application/json');
    $query = isset($_POST['query']) ? trim((string) $_POST['query']) : '';
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Query is required']);
        exit;
    }
    try {
        $results = searchCharactersSupabase($query);
        echo json_encode(['success' => true, 'results' => $results, 'query' => $query]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * Search characters using Supabase (used by AJAX search).
 */
function searchCharactersSupabase(string $query): array {
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    if (preg_match('/\b(which|what)\s+clans?\s+(have|has)\b/i', $query) && preg_match('/\b(more\s+than|fewer\s+than|less\s+than|greater\s+than|at\s+least|at\s+most|between|exactly|equal\s+to)\b.*\d+/i', $query)) {
        $rangeResult = parseClanRangeQuerySupabase($query);
        if ($rangeResult !== null) {
            return $rangeResult;
        }
    }

    if (preg_match('/\b(embraced|born|from|outside of|outside|in|location)\b/i', $query)) {
        return handleLocationQuerySupabase($query);
    }

    if (preg_match('/\b(most|highest|lowest|fewest|count|how many|which clan|what clan)\b/i', $query)) {
        return handleAnalyticalQuerySupabase($query);
    }

    return performKeywordSearchSupabase(strtolower($query));
}

function getCharacterFullDataSupabase(int $charId, array $char): array {
    $traits = supabase_table_get('character_traits', ['select' => 'trait_name,trait_category,trait_level', 'character_id' => 'eq.' . $charId]);
    $abilities = supabase_table_get('character_abilities', ['select' => 'ability_name,ability_category,level,specialization', 'character_id' => 'eq.' . $charId]);
    $disciplines = supabase_table_get('character_disciplines', ['select' => 'discipline_name,level,is_custom', 'character_id' => 'eq.' . $charId]);
    $backgrounds = supabase_table_get('character_backgrounds', ['select' => 'background_name,level', 'character_id' => 'eq.' . $charId]);
    $relationships = supabase_table_get('character_relationships', ['select' => 'related_character_name,relationship_type,relationship_subtype,strength,description', 'character_id' => 'eq.' . $charId]);
    return [
        'character' => $char,
        'traits' => is_array($traits) ? $traits : [],
        'abilities' => is_array($abilities) ? $abilities : [],
        'disciplines' => is_array($disciplines) ? $disciplines : [],
        'backgrounds' => is_array($backgrounds) ? $backgrounds : [],
        'relationships' => is_array($relationships) ? $relationships : []
    ];
}

/**
 * Search characters based on query (legacy – uses MySQL; prefer searchCharactersSupabase for new code).
 */
function searchCharacters($conn, $query) {
    return searchCharactersSupabase((string) $query);
}

/**
 * Handle location-based queries (embraced outside of, born in, etc.)
 */
function handleLocationQuery($conn, $query, $queryLower) {
    return handleLocationQuerySupabase((string) $query);
}

/**
 * Find characters embraced/born outside of a specific location
 */
function findCharactersOutsideLocation($conn, $location, $queryLower) {
    return handleLocationQuerySupabase('outside of ' . (string) $location);
}

/**
 * Find characters embraced/born in a specific location
 */
function findCharactersInLocation($conn, $location, $queryLower) {
    return handleLocationQuerySupabase('in ' . (string) $location);
}

/**
 * Handle analytical queries (most, highest, count, etc.)
 */
function handleAnalyticalQuery($conn, $query, $queryLower) {
    return handleAnalyticalQuerySupabase((string) $query);
}

/**
 * Find characters with most traits in a specific category
 */
function findCharactersWithMostTraits($conn, $category) {
    return findCharactersWithMostTraitsSupabase((string) $category);
}

/**
 * Find characters with most abilities in a category
 */
function findCharactersWithMostAbilities($conn, $category) {
    return findCharactersWithMostAbilitiesSupabase((string) $category);
}

/**
 * Find characters with most disciplines
 */
function findCharactersWithMostDisciplines($conn) {
    return findCharactersWithMostDisciplinesSupabase();
}

/**
 * Find characters by generation (highest/lowest)
 */
function findCharactersByGeneration($conn, $order) {
    return findCharactersByGenerationSupabase((string) $order);
}

/**
 * Count traits by category across all characters
 */
function countTraitsByCategory($conn, $category) {
    return countTraitsByCategorySupabase((string) $category);
}

/**
 * Find which clan has the most NPCs
 */
function findClanWithMostNPCs($conn) {
    return findClanWithMostNPCsSupabase();
}

/**
 * Find which clan has the most PCs
 */
function findClanWithMostPCs($conn) {
    return findClanWithMostPCsSupabase();
}

/**
 * Find which clan has the most characters overall
 */
function findClanWithMostCharacters($conn) {
    return findClanWithMostCharactersSupabase();
}

/**
 * Count characters by clan (NPCs and PCs)
 */
function countCharactersByClan($conn) {
    return countCharactersByClanSupabase();
}

/**
 * Parse and handle clan range queries (e.g., "more than 0 and fewer than 3")
 */
function parseClanRangeQuery($conn, $query, $queryLower) {
    return parseClanRangeQuerySupabase((string) $query);
}

/**
 * Perform keyword-based search
 */
function performKeywordSearch($conn, $queryLower) {
    return performKeywordSearchSupabase((string) $queryLower);
}

/**
 * Get full character data including all related information
 */
function getCharacterFullData($conn, $charId, $char) {
    return getCharacterFullDataSupabase((int) $charId, $char);
}

function getCharacterRowsSupabase(): array {
    static $rows = null;

    if ($rows !== null) {
        return $rows;
    }

    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name,player_name,clan,generation,sire,concept,biography,status,pc',
        'order' => 'character_name.asc'
    ]);

    return is_array($rows) ? $rows : [];
}

function indexCharactersByIdSupabase(): array {
    $indexed = [];
    foreach (getCharacterRowsSupabase() as $row) {
        $indexed[(int) $row['id']] = $row;
    }
    return $indexed;
}

function buildCharacterResultsSupabase(array $characters): array {
    $results = [];
    foreach (array_slice($characters, 0, 50) as $char) {
        $results[] = getCharacterFullDataSupabase((int) $char['id'], $char);
    }
    return $results;
}

function handleLocationQuerySupabase(string $query): array {
    $location = null;

    if (preg_match('/\boutside of\s+([a-z\s]+?)(?:\?|$|embraced|born|from)/i', $query, $matches)) {
        $location = trim($matches[1]);
    } elseif (preg_match('/\boutside\s+([a-z\s]+?)(?:\?|$|embraced|born|from)/i', $query, $matches)) {
        $location = trim($matches[1]);
    } elseif (preg_match('/\b(phoenix|tucson|flagstaff|scottsdale|tempe|mesa|glendale|chandler|peoria|surprise|yuma|prescott|sedona|arizona|california|nevada|new mexico|texas|mexico|europe|asia|africa|australia|south america|north america|united states|usa|america)\b/i', $query, $matches)) {
        $location = trim($matches[1]);
    }

    if ($location === null || $location === '') {
        return performKeywordSearchSupabase(strtolower($query));
    }

    $locationLower = strtolower($location);
    $isOutsideQuery = preg_match('/\boutside\s+(?:of\s+)?/i', $query) === 1;
    $results = [];

    foreach (getCharacterRowsSupabase() as $char) {
        $bio = strtolower((string) ($char['biography'] ?? ''));
        $sire = strtolower((string) ($char['sire'] ?? ''));
        $concept = strtolower((string) ($char['concept'] ?? ''));
        $haystack = $bio . ' ' . $sire . ' ' . $concept;
        $hasLocation = strpos($haystack, $locationLower) !== false;

        if (($isOutsideQuery && !$hasLocation) || (!$isOutsideQuery && $hasLocation)) {
            $results[] = $char;
        }
    }

    return buildCharacterResultsSupabase($results);
}

function handleAnalyticalQuerySupabase(string $query): array {
    if (preg_match('/\bmost\b.*\b(social|physical|mental)\s+traits?\b/i', $query, $matches)) {
        return findCharactersWithMostTraitsSupabase(ucfirst(strtolower($matches[1])));
    }

    if (preg_match('/\bmost\b.*\b(talents?|skills?|knowledges?)\b/i', $query, $matches)) {
        $category = ucfirst(rtrim(strtolower($matches[1]), 's'));
        if ($category === 'Talent') {
            $category = 'Talents';
        } elseif ($category === 'Skill') {
            $category = 'Skills';
        } elseif ($category === 'Knowledge') {
            $category = 'Knowledges';
        }
        return findCharactersWithMostAbilitiesSupabase($category);
    }

    if (preg_match('/\bmost\s+disciplines?\b/i', $query)) {
        return findCharactersWithMostDisciplinesSupabase();
    }

    if (preg_match('/\b(highest|lowest)\s+generation\b/i', $query, $matches)) {
        return findCharactersByGenerationSupabase(strtolower($matches[1]) === 'highest' ? 'desc' : 'asc');
    }

    if (preg_match('/\bhow many\b.*\b(social|physical|mental)\s+traits?\b/i', $query, $matches)) {
        return countTraitsByCategorySupabase(ucfirst(strtolower($matches[1])));
    }

    if (preg_match('/\b(which|what)\s+clan\s+(has|have)\s+(the\s+)?most\b/i', $query)) {
        if (preg_match('/\b(npcs?|non-?player|non\s+player)\b/i', $query)) {
            return findClanWithMostNPCsSupabase();
        }
        if (preg_match('/\b(pcs?|player\s+characters?)\b/i', $query)) {
            return findClanWithMostPCsSupabase();
        }
        return findClanWithMostCharactersSupabase();
    }

    if (preg_match('/\bhow many\s+(npcs?|non-?player|non\s+player|pcs?|player\s+characters?|characters?)\s+(are|in|does)\s+(each|all)\s+clan\b/i', $query)) {
        return countCharactersByClanSupabase();
    }

    $rangeResult = parseClanRangeQuerySupabase($query);
    if ($rangeResult !== null) {
        return $rangeResult;
    }

    return performKeywordSearchSupabase(strtolower($query));
}

function findCharactersWithMostTraitsSupabase(string $category): array {
    $rows = supabase_table_get('character_traits', ['select' => 'character_id,trait_category']);
    $counts = [];

    foreach ($rows as $row) {
        if (($row['trait_category'] ?? '') !== $category) {
            continue;
        }
        $characterId = (int) $row['character_id'];
        $counts[$characterId] = ($counts[$characterId] ?? 0) + 1;
    }

    arsort($counts);
    $characters = indexCharactersByIdSupabase();
    $results = [];

    foreach (array_slice($counts, 0, 10, true) as $characterId => $count) {
        if (!isset($characters[$characterId])) {
            continue;
        }
        $result = getCharacterFullDataSupabase($characterId, $characters[$characterId]);
        $result['analytical_result'] = ['type' => 'most_traits', 'category' => $category, 'count' => $count];
        $results[] = $result;
    }

    return $results;
}

function findCharactersWithMostAbilitiesSupabase(string $category): array {
    $rows = supabase_table_get('character_abilities', ['select' => 'character_id,ability_category']);
    $counts = [];

    foreach ($rows as $row) {
        if (($row['ability_category'] ?? '') !== $category) {
            continue;
        }
        $characterId = (int) $row['character_id'];
        $counts[$characterId] = ($counts[$characterId] ?? 0) + 1;
    }

    arsort($counts);
    $characters = indexCharactersByIdSupabase();
    $results = [];

    foreach (array_slice($counts, 0, 10, true) as $characterId => $count) {
        if (!isset($characters[$characterId])) {
            continue;
        }
        $result = getCharacterFullDataSupabase($characterId, $characters[$characterId]);
        $result['analytical_result'] = ['type' => 'most_abilities', 'category' => $category, 'count' => $count];
        $results[] = $result;
    }

    return $results;
}

function findCharactersWithMostDisciplinesSupabase(): array {
    $rows = supabase_table_get('character_disciplines', ['select' => 'character_id']);
    $counts = [];

    foreach ($rows as $row) {
        $characterId = (int) $row['character_id'];
        $counts[$characterId] = ($counts[$characterId] ?? 0) + 1;
    }

    arsort($counts);
    $characters = indexCharactersByIdSupabase();
    $results = [];

    foreach (array_slice($counts, 0, 10, true) as $characterId => $count) {
        if (!isset($characters[$characterId])) {
            continue;
        }
        $result = getCharacterFullDataSupabase($characterId, $characters[$characterId]);
        $result['analytical_result'] = ['type' => 'most_disciplines', 'count' => $count];
        $results[] = $result;
    }

    return $results;
}

function findCharactersByGenerationSupabase(string $order): array {
    $characters = array_values(array_filter(getCharacterRowsSupabase(), static function ($row) {
        return isset($row['generation']) && (int) $row['generation'] > 0;
    }));

    usort($characters, static function ($a, $b) use ($order) {
        $cmp = ((int) ($a['generation'] ?? 0)) <=> ((int) ($b['generation'] ?? 0));
        if ($cmp === 0) {
            return strcmp((string) ($a['character_name'] ?? ''), (string) ($b['character_name'] ?? ''));
        }
        return $order === 'desc' ? -$cmp : $cmp;
    });

    $results = [];
    foreach (array_slice($characters, 0, 10) as $char) {
        $result = getCharacterFullDataSupabase((int) $char['id'], $char);
        $result['analytical_result'] = ['type' => 'generation', 'generation' => $char['generation']];
        $results[] = $result;
    }

    return $results;
}

function countTraitsByCategorySupabase(string $category): array {
    $rows = supabase_table_get('character_traits', ['select' => 'trait_category']);
    $total = 0;

    foreach ($rows as $row) {
        if (($row['trait_category'] ?? '') === $category) {
            $total++;
        }
    }

    return [[
        'analytical_result' => [
            'type' => 'count',
            'category' => $category,
            'total' => $total
        ]
    ]];
}

function buildClanCountsSupabase(): array {
    $counts = [];

    foreach (getCharacterRowsSupabase() as $row) {
        $clan = trim((string) ($row['clan'] ?? ''));
        if ($clan === '') {
            continue;
        }

        if (!isset($counts[$clan])) {
            $counts[$clan] = ['clan' => $clan, 'total_count' => 0, 'pc_count' => 0, 'npc_count' => 0];
        }

        $counts[$clan]['total_count']++;
        if ((int) ($row['pc'] ?? 0) === 1) {
            $counts[$clan]['pc_count']++;
        } else {
            $counts[$clan]['npc_count']++;
        }
    }

    $result = array_values($counts);
    usort($result, static function ($a, $b) {
        $cmp = $b['total_count'] <=> $a['total_count'];
        return $cmp !== 0 ? $cmp : strcmp($a['clan'], $b['clan']);
    });

    return $result;
}

function findClanWithMostNPCsSupabase(): array {
    $counts = buildClanCountsSupabase();
    if (empty($counts)) {
        return [['analytical_result' => ['type' => 'clan_most_npcs', 'clans' => [], 'count' => 0]]];
    }

    usort($counts, static function ($a, $b) {
        $cmp = $b['npc_count'] <=> $a['npc_count'];
        return $cmp !== 0 ? $cmp : strcmp($a['clan'], $b['clan']);
    });

    $max = (int) $counts[0]['npc_count'];
    $clans = [];
    foreach ($counts as $row) {
        if ((int) $row['npc_count'] !== $max) {
            break;
        }
        $clans[] = ['clan' => $row['clan'], 'count' => $row['npc_count']];
    }

    return [['analytical_result' => ['type' => 'clan_most_npcs', 'clans' => $clans, 'count' => $max]]];
}

function findClanWithMostPCsSupabase(): array {
    $counts = buildClanCountsSupabase();
    if (empty($counts)) {
        return [['analytical_result' => ['type' => 'clan_most_pcs', 'clans' => [], 'count' => 0]]];
    }

    usort($counts, static function ($a, $b) {
        $cmp = $b['pc_count'] <=> $a['pc_count'];
        return $cmp !== 0 ? $cmp : strcmp($a['clan'], $b['clan']);
    });

    $max = (int) $counts[0]['pc_count'];
    $clans = [];
    foreach ($counts as $row) {
        if ((int) $row['pc_count'] !== $max) {
            break;
        }
        $clans[] = ['clan' => $row['clan'], 'count' => $row['pc_count']];
    }

    return [['analytical_result' => ['type' => 'clan_most_pcs', 'clans' => $clans, 'count' => $max]]];
}

function findClanWithMostCharactersSupabase(): array {
    $counts = buildClanCountsSupabase();
    if (empty($counts)) {
        return [['analytical_result' => ['type' => 'clan_most_characters', 'clans' => [], 'count' => 0]]];
    }

    $max = (int) $counts[0]['total_count'];
    $clans = [];
    foreach ($counts as $row) {
        if ((int) $row['total_count'] !== $max) {
            break;
        }
        $clans[] = ['clan' => $row['clan'], 'count' => $row['total_count']];
    }

    return [['analytical_result' => ['type' => 'clan_most_characters', 'clans' => $clans, 'count' => $max]]];
}

function countCharactersByClanSupabase(): array {
    return [[
        'analytical_result' => [
            'type' => 'clan_counts',
            'clans' => buildClanCountsSupabase()
        ]
    ]];
}

function parseClanRangeQuerySupabase(string $query): ?array {
    if (!preg_match('/\b(characters?|npcs?|pcs?|non-?player|player\s+characters?)\b/i', $query)) {
        return null;
    }

    $countType = 'total';
    if (preg_match('/\b(npcs?|non-?player|non\s+player)\b/i', $query)) {
        $countType = 'npc';
    } elseif (preg_match('/\b(pcs?|player\s+characters?)\b/i', $query)) {
        $countType = 'pc';
    }

    $minCount = null;
    $maxCount = null;
    $exactCount = null;

    if (preg_match('/\b(more\s+than|greater\s+than|over|above)\s+(\d+)\b/i', $query, $matches)) {
        $minCount = (int) $matches[2];
    }
    if (preg_match('/\b(at\s+least)\s+(\d+)\b/i', $query, $matches)) {
        $minCount = (int) $matches[2] - 1;
    }
    if (preg_match('/\b(fewer\s+than|less\s+than|under|below)\s+(\d+)\b/i', $query, $matches)) {
        $maxCount = (int) $matches[2];
    }
    if (preg_match('/\b(at\s+most)\s+(\d+)\b/i', $query, $matches)) {
        $maxCount = (int) $matches[2] + 1;
    }
    if (preg_match('/\bexactly\s+(\d+)\b/i', $query, $matches)) {
        $exactCount = (int) $matches[1];
    }
    if (preg_match('/\bbetween\s+(\d+)\s+and\s+(\d+)\b/i', $query, $matches)) {
        $minCount = (int) $matches[1];
        $maxCount = (int) $matches[2];
    }
    if ($exactCount === null && $minCount === null && $maxCount === null && preg_match('/\b(equal\s+to|equals?)\s+(\d+)\b/i', $query, $matches)) {
        $exactCount = (int) $matches[2];
    }

    if ($exactCount === null && $minCount === null && $maxCount === null) {
        return null;
    }

    $rows = buildClanCountsSupabase();
    $filtered = [];

    foreach ($rows as $row) {
        $value = $countType === 'npc' ? (int) $row['npc_count'] : ($countType === 'pc' ? (int) $row['pc_count'] : (int) $row['total_count']);
        $matchesRange = true;

        if ($exactCount !== null) {
            $matchesRange = $value === $exactCount;
        } else {
            if ($minCount !== null && $value <= $minCount) {
                $matchesRange = false;
            }
            if ($maxCount !== null && $value >= $maxCount) {
                $matchesRange = false;
            }
        }

        if ($matchesRange) {
            $filtered[] = [
                'clan' => $row['clan'],
                'count' => $value,
                'total_count' => $row['total_count'],
                'pc_count' => $row['pc_count'],
                'npc_count' => $row['npc_count']
            ];
        }
    }

    usort($filtered, static function ($a, $b) {
        $cmp = $a['count'] <=> $b['count'];
        return $cmp !== 0 ? $cmp : strcmp($a['clan'], $b['clan']);
    });

    return [[
        'analytical_result' => [
            'type' => 'clan_range',
            'clans' => $filtered,
            'count_type' => $countType,
            'min' => $minCount,
            'max' => $maxCount,
            'exact' => $exactCount
        ]
    ]];
}

function performKeywordSearchSupabase(string $queryLower): array {
    $results = [];

    foreach (getCharacterRowsSupabase() as $char) {
        $fields = [
            strtolower((string) ($char['character_name'] ?? '')),
            strtolower((string) ($char['player_name'] ?? '')),
            strtolower((string) ($char['clan'] ?? '')),
            strtolower((string) ($char['sire'] ?? '')),
            strtolower((string) ($char['concept'] ?? '')),
            strtolower((string) ($char['biography'] ?? ''))
        ];

        foreach ($fields as $field) {
            if ($field !== '' && strpos($field, $queryLower) !== false) {
                $results[] = $char;
                break;
            }
        }
    }

    return buildCharacterResultsSupabase($results);
}

// Normal page load - include header and continue (Supabase; search is AJAX via searchCharactersSupabase)
require_once __DIR__ . '/../../includes/supabase_client.php';
$extra_css = ['css/admin-agents.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="admin-panel-container agents-panel container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h1 class="display-5 text-light fw-bold mb-1">🔍 Search Character Information</h1>
        <p class="agents-intro lead fst-italic mb-0">Ask questions about characters stored in the database.</p>
    </div>

    <div class="mb-3">
        <a href="../../admin/agents.php" class="btn btn-outline-secondary btn-sm">
            ← Back to Agents
        </a>
    </div>

    <!-- Search Form -->
    <div class="card bg-dark border-danger mb-4">
        <div class="card-body">
            <h3 class="card-title text-light mb-3">Character Search</h3>
            <form id="characterSearchForm">
                <div class="mb-3">
                    <label for="searchQuery" class="form-label text-light">Enter your question or search term:</label>
                    <textarea 
                        class="form-control bg-dark text-light border-danger" 
                        id="searchQuery" 
                        name="query" 
                        rows="3" 
                        placeholder="e.g., 'Who is Eddy Valiant?' or 'Which clan has the most NPCs?' or 'Which clans have more than 0 and fewer than 3 characters?' or 'Show me all Ventrue characters'"
                        required></textarea>
                    <div class="mt-1 text-light">Search by name, clan, or ask analytical questions like &quot;which clan has the most NPCs&quot;, &quot;which clans have more than 0 and fewer than 3 characters&quot;, &quot;most social traits&quot;, &quot;highest generation&quot;, or &quot;most disciplines&quot;.</div>
                </div>
                <button type="submit" class="btn btn-outline-danger" id="searchBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Search
                </button>
            </form>
        </div>
    </div>

    <!-- Results Area -->
    <div id="resultsArea" class="d-none">
        <div class="card bg-dark border-success mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title text-light mb-0">Search Results</h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearResults()">Clear</button>
                </div>
                <div id="resultsContent"></div>
            </div>
        </div>
    </div>

    <!-- Error Area -->
    <div id="errorArea" class="d-none">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('characterSearchForm');
    const searchBtn = document.getElementById('searchBtn');
    const spinner = searchBtn.querySelector('.spinner-border');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const query = document.getElementById('searchQuery').value.trim();
        if (!query) {
            showError('Please enter a search query');
            return;
        }
        
        // Show loading state
        searchBtn.disabled = true;
        spinner.classList.remove('d-none');
        hideError();
        hideResults();
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'characters.php?action=search', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            searchBtn.disabled = false;
            spinner.classList.add('d-none');
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        displayResults(response.results, response.query);
                    } else {
                        showError(response.error || 'Search failed');
                    }
                } catch (e) {
                    showError('Failed to parse response: ' + e.message);
                }
            } else {
                showError('Server error: ' + xhr.status);
            }
        };
        
        xhr.onerror = function() {
            searchBtn.disabled = false;
            spinner.classList.add('d-none');
            showError('Network error occurred');
        };
        
        xhr.send('action=search&query=' + encodeURIComponent(query));
    });
});

function displayResults(results, query) {
    const resultsArea = document.getElementById('resultsArea');
    const resultsContent = document.getElementById('resultsContent');
    
    if (!results || results.length === 0) {
        resultsContent.innerHTML = '<p class="text-light">No characters found matching your query: <strong>' + escapeHtml(query) + '</strong></p>';
        resultsArea.classList.remove('d-none');
        return;
    }
    
    // Check if this is a count-only result (analytical query)
    if (results.length === 1 && results[0].analytical_result) {
        const analyticalResult = results[0].analytical_result;
        
        // Count-only result
        if (analyticalResult.type === 'count') {
            let html = '<div class="alert alert-info">';
            html += '<h5 class="alert-heading">Query Result</h5>';
            html += '<p class="mb-0 text-light">Total <strong>' + escapeHtml(analyticalResult.category) + '</strong> traits in database: <strong>' + analyticalResult.total + '</strong></p>';
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
        
        // Clan analytical results
        if (analyticalResult.type === 'clan_most_npcs') {
            let html = '<div class="alert alert-blood">';
            html += '<h5 class="alert-heading">Which Clan Has the Most NPCs?</h5>';
            if (analyticalResult.clans && analyticalResult.clans.length > 0) {
                const clanNames = analyticalResult.clans.map(c => escapeHtml(c.clan)).join(', ');
                const count = analyticalResult.count;
                html += '<p class="mb-2 text-light"><strong>' + clanNames + '</strong> with <strong>' + count + '</strong> NPC' + (count !== 1 ? 's' : '') + '</p>';
                if (analyticalResult.clans.length > 1) {
                    html += '<p class="mb-0 small text-light">(Tied for most)</p>';
                }
            } else {
                html += '<p class="mb-0 text-light">No NPCs found in database.</p>';
            }
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
        
        if (analyticalResult.type === 'clan_most_pcs') {
            let html = '<div class="alert alert-blood">';
            html += '<h5 class="alert-heading">Which Clan Has the Most PCs?</h5>';
            if (analyticalResult.clans && analyticalResult.clans.length > 0) {
                const clanNames = analyticalResult.clans.map(c => escapeHtml(c.clan)).join(', ');
                const count = analyticalResult.count;
                html += '<p class="mb-2 text-light"><strong>' + clanNames + '</strong> with <strong>' + count + '</strong> PC' + (count !== 1 ? 's' : '') + '</p>';
                if (analyticalResult.clans.length > 1) {
                    html += '<p class="mb-0 small text-light">(Tied for most)</p>';
                }
            } else {
                html += '<p class="mb-0 text-light">No PCs found in database.</p>';
            }
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
        
        if (analyticalResult.type === 'clan_most_characters') {
            let html = '<div class="alert alert-blood">';
            html += '<h5 class="alert-heading">Which Clan Has the Most Characters?</h5>';
            if (analyticalResult.clans && analyticalResult.clans.length > 0) {
                const clanNames = analyticalResult.clans.map(c => escapeHtml(c.clan)).join(', ');
                const count = analyticalResult.count;
                html += '<p class="mb-2 text-light"><strong>' + clanNames + '</strong> with <strong>' + count + '</strong> character' + (count !== 1 ? 's' : '') + '</p>';
                if (analyticalResult.clans.length > 1) {
                    html += '<p class="mb-0 small text-light">(Tied for most)</p>';
                }
            } else {
                html += '<p class="mb-0 text-light">No characters found in database.</p>';
            }
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
        
        if (analyticalResult.type === 'clan_counts') {
            let html = '<div class="alert alert-info">';
            html += '<h5 class="alert-heading">Character Count by Clan</h5>';
            if (analyticalResult.clans && analyticalResult.clans.length > 0) {
                html += '<table class="table table-dark table-striped table-sm mt-3">';
                html += '<thead><tr><th>Clan</th><th>Total</th><th>PCs</th><th>NPCs</th></tr></thead>';
                html += '<tbody>';
                analyticalResult.clans.forEach(function(clan) {
                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(clan.clan) + '</strong></td>';
                    html += '<td>' + (clan.total_count || 0) + '</td>';
                    html += '<td>' + (clan.pc_count || 0) + '</td>';
                    html += '<td>' + (clan.npc_count || 0) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            } else {
                html += '<p class="mb-0 text-light">No characters found in database.</p>';
            }
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
        
        if (analyticalResult.type === 'clan_range') {
            let html = '<div class="alert alert-blood">';
            
            // Build query description
            let queryDesc = 'Clans with ';
            if (analyticalResult.exact !== null && analyticalResult.exact !== undefined) {
                queryDesc += 'exactly ' + analyticalResult.exact;
            } else {
                if (analyticalResult.min !== null && analyticalResult.min !== undefined) {
                    queryDesc += 'more than ' + analyticalResult.min;
                }
                if (analyticalResult.min !== null && analyticalResult.max !== null) {
                    queryDesc += ' and ';
                }
                if (analyticalResult.max !== null && analyticalResult.max !== undefined) {
                    queryDesc += 'fewer than ' + analyticalResult.max;
                }
            }
            
            let countTypeName = 'characters';
            if (analyticalResult.count_type === 'npc') {
                countTypeName = 'NPCs';
            } else if (analyticalResult.count_type === 'pc') {
                countTypeName = 'PCs';
            }
            queryDesc += ' ' + countTypeName;
            
            html += '<h5 class="alert-heading">' + escapeHtml(queryDesc) + '</h5>';
            
            if (analyticalResult.clans && analyticalResult.clans.length > 0) {
                html += '<p class="mb-3 text-light">Found <strong>' + analyticalResult.clans.length + '</strong> clan(s):</p>';
                html += '<table class="table table-dark table-striped table-sm mt-3">';
                html += '<thead><tr><th>Clan</th><th>Count</th><th>Total</th><th>PCs</th><th>NPCs</th></tr></thead>';
                html += '<tbody>';
                analyticalResult.clans.forEach(function(clan) {
                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(clan.clan) + '</strong></td>';
                    html += '<td><strong>' + (clan.count || 0) + '</strong></td>';
                    html += '<td>' + (clan.total_count || 0) + '</td>';
                    html += '<td>' + (clan.pc_count || 0) + '</td>';
                    html += '<td>' + (clan.npc_count || 0) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            } else {
                html += '<p class="mb-0 text-light">No clans found matching the criteria.</p>';
            }
            html += '</div>';
            resultsContent.innerHTML = html;
            resultsArea.classList.remove('d-none');
            return;
        }
    }
    
    let html = '<p class="text-light mb-3">Found <strong>' + results.length + '</strong> character(s) matching: <strong>' + escapeHtml(query) + '</strong></p>';
    
    results.forEach(function(result) {
        const char = result.character;
        if (!char) {
            // Skip if no character data
            return;
        }
        
        html += '<div class="card bg-dark border-secondary mb-3">';
        html += '<div class="card-body">';
        
        // Show analytical result badge if present
        if (result.analytical_result) {
            const analytical = result.analytical_result;
            let badgeText = '';
            let badgeClass = 'success';
            
            if (analytical.type === 'most_traits') {
                badgeText = 'Has the most ' + escapeHtml(analytical.category) + ' traits: ' + analytical.count;
            } else if (analytical.type === 'most_abilities') {
                badgeText = 'Has the most ' + escapeHtml(analytical.category) + ': ' + analytical.count;
            } else if (analytical.type === 'most_disciplines') {
                badgeText = 'Has the most disciplines: ' + analytical.count;
            } else if (analytical.type === 'generation') {
                badgeText = 'Generation: ' + analytical.generation;
                badgeClass = 'info';
            }
            
            if (badgeText) {
                html += '<div class="mb-3">';
                html += '<span class="badge bg-' + badgeClass + ' fs-6">' + badgeText + '</span>';
                html += '</div>';
            }
        }
        
        html += '<h4 class="text-light mb-3">' + escapeHtml(char.character_name) + '</h4>';
        
        // Basic Info
        html += '<div class="row mb-3">';
        html += '<div class="col-md-6">';
        html += '<p class="mb-1"><strong class="text-light">Player:</strong> <span class="text-light">' + escapeHtml(char.player_name || 'N/A') + '</span></p>';
        html += '<p class="mb-1"><strong class="text-light">Clan:</strong> <span class="text-light">' + escapeHtml(char.clan || 'N/A') + '</span></p>';
        html += '<p class="mb-1"><strong class="text-light">Generation:</strong> <span class="text-light">' + escapeHtml(char.generation || 'N/A') + '</span></p>';
        html += '<p class="mb-1"><strong class="text-light">Sire:</strong> <span class="text-light">' + escapeHtml(char.sire || 'N/A') + '</span></p>';
        html += '</div>';
        html += '<div class="col-md-6">';
        html += '<p class="mb-1"><strong class="text-light">Status:</strong> <span class="text-light">' + escapeHtml(char.status || 'N/A') + '</span></p>';
        html += '<p class="mb-1"><strong class="text-light">Type:</strong> <span class="text-light">' + (char.pc ? 'PC' : 'NPC') + '</span></p>';
        html += '<p class="mb-1"><strong class="text-light">Concept:</strong> <span class="text-light">' + escapeHtml(char.concept || 'N/A') + '</span></p>';
        html += '</div>';
        html += '</div>';
        
        // Traits (grouped by category for analytical queries)
        if (result.traits && result.traits.length > 0) {
            const traitsByCategory = {};
            result.traits.forEach(t => {
                const cat = t.trait_category || 'Other';
                if (!traitsByCategory[cat]) traitsByCategory[cat] = [];
                traitsByCategory[cat].push(t);
            });
            
            html += '<div class="mb-3">';
            html += '<strong class="text-light">Traits:</strong> ';
            const traitParts = [];
            Object.keys(traitsByCategory).forEach(cat => {
                const count = traitsByCategory[cat].length;
                traitParts.push(cat + ': ' + count);
            });
            html += '<span class="text-light">' + traitParts.join(', ') + '</span>';
            html += '</div>';
        }
        
        // Abilities
        if (result.abilities && result.abilities.length > 0) {
            html += '<div class="mb-3">';
            html += '<strong class="text-light">Abilities:</strong> ';
            const abilityList = result.abilities.map(a => escapeHtml(a.ability_name) + ' x' + a.level).join(', ');
            html += '<span class="text-light">' + abilityList + '</span>';
            html += '</div>';
        }
        
        // Disciplines
        if (result.disciplines && result.disciplines.length > 0) {
            html += '<div class="mb-3">';
            html += '<strong class="text-light">Disciplines:</strong> ';
            const discList = result.disciplines.map(d => escapeHtml(d.discipline_name) + ' ' + d.level).join(', ');
            html += '<span class="text-light">' + discList + '</span>';
            html += '</div>';
        }
        
        // Relationships
        if (result.relationships && result.relationships.length > 0) {
            html += '<div class="mb-3">';
            html += '<strong class="text-light">Relationships:</strong> ';
            const relList = result.relationships.map(r => escapeHtml(r.related_character_name) + ' (' + escapeHtml(r.relationship_type) + ')').join(', ');
            html += '<span class="text-light">' + relList + '</span>';
            html += '</div>';
        }
        
        // Biography (truncated)
        if (char.biography) {
            const bio = char.biography.length > 200 ? char.biography.substring(0, 200) + '...' : char.biography;
            html += '<div class="mb-0">';
            html += '<strong class="text-light">Biography:</strong> ';
            html += '<span class="text-light">' + escapeHtml(bio) + '</span>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
    });
    
    resultsContent.innerHTML = html;
    resultsArea.classList.remove('d-none');
}

function clearResults() {
    document.getElementById('resultsArea').classList.add('d-none');
    document.getElementById('resultsContent').innerHTML = '';
}

function showError(message) {
    const errorArea = document.getElementById('errorArea');
    const errorMessage = document.getElementById('errorMessage');
    errorMessage.textContent = message;
    errorArea.classList.remove('d-none');
}

function hideError() {
    document.getElementById('errorArea').classList.add('d-none');
}

function hideResults() {
    document.getElementById('resultsArea').classList.add('d-none');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

