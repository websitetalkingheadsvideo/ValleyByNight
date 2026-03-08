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
    $q = trim($query);
    if ($q === '') {
        return [];
    }
    $pat = '*' . $q . '*';
    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name,player_name,clan,generation,sire,concept,biography,status,pc',
        'or' => '(character_name.ilike.' . $pat . ',player_name.ilike.' . $pat . ',clan.ilike.' . $pat . ',sire.ilike.' . $pat . ',concept.ilike.' . $pat . ',biography.ilike.' . $pat . ')',
        'order' => 'character_name.asc',
        'limit' => '50'
    ]);
    $results = [];
    foreach ($rows as $char) {
        $results[] = getCharacterFullDataSupabase((int) $char['id'], $char);
    }
    return $results;
}

function getCharacterFullDataSupabase(int $charId, array $char): array {
    $traits = supabase_table_get('character_traits', ['select' => 'trait_name,trait_category', 'character_id' => 'eq.' . $charId]);
    $abilities = supabase_table_get('character_abilities', ['select' => 'ability_name,ability_category,level,specialization', 'character_id' => 'eq.' . $charId]);
    $disciplines = supabase_table_get('character_disciplines', ['select' => 'discipline_name,level', 'character_id' => 'eq.' . $charId]);
    $backgrounds = supabase_table_get('character_backgrounds', ['select' => 'background_name,level', 'character_id' => 'eq.' . $charId]);
    $relationships = supabase_table_get('character_relationships', ['select' => '*', 'character_id' => 'eq.' . $charId]);
    return array_merge($char, [
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds,
        'relationships' => $relationships
    ]);
}

/**
 * Search characters based on query (legacy – uses MySQL; prefer searchCharactersSupabase for new code).
 */
function searchCharacters($conn, $query) {
    $queryLower = strtolower($query);
    
    // Check for location-based queries (embraced, born, from, outside of, etc.)
    if (preg_match('/\b(embraced|born|from|outside of|outside|in|location)\b.*\b(phoenix|tucson|flagstaff|scottsdale|tempe|mesa|glendale|chandler|peoria|surprise|yuma|prescott|sedona|flagstaff|show low|kingman|lake havasu|bullhead city|payson|winslow|page|nogales|sierra vista|douglas|bisbee|clifton|morenci|globe|miami|apache junction|queen creek|casa grande|maricopa|eloy|coolidge|florence|san tan valley|goodyear|avondale|litchfield park|buckeye|wickenburg|sun city|sun city west|fountain hills|paradise valley|cave creek|carefree|rio verde|anthem|new river|black canyon city|dewey-humboldt|chino valley|paulden|williams|grand canyon|tuba city|kayenta|chinle|window rock|fort defiance|ganado|holbrook|snowflake|taylor|pinetop-lakeside|springerville|eagar|alpine|greer|payson|pine|strawberry|young|heber-overgaard|show low|lakeside|pinetop|taylor|snowflake|concho|st. johns|springerville|eagar|alpine|greer|clifton|morenci|safford|thatcher|pima|fort thomas|bylas|san carlos|peridot|bylas|san carlos|peridot|whiteriver|hon-dah|mcnary|vernon|show low|lakeside|pinetop|taylor|snowflake|concho|st. johns|springerville|eagar|alpine|greer|clifton|morenci|safford|thatcher|pima|fort thomas|bylas|san carlos|peridot|whiteriver|hon-dah|mcnary|vernon|arizona|california|nevada|new mexico|texas|mexico|europe|asia|africa|australia|south america|north america|united states|usa|america)\b/i', $query)) {
        return handleLocationQuery($conn, $query, $queryLower);
    }
    
    // Check for range queries about clans (e.g., "which clans have more than X and fewer than Y")
    // This must come before the analytical query check to catch range queries
    if (preg_match('/\b(which|what)\s+clans?\s+(have|has)\b/i', $query)) {
        // Check if it contains range indicators
        if (preg_match('/\b(more\s+than|fewer\s+than|less\s+than|greater\s+than|at\s+least|at\s+most|between|exactly|equal\s+to)\b.*\d+/i', $query)) {
            $rangeResult = parseClanRangeQuery($conn, $query, $queryLower);
            if ($rangeResult !== null) {
                return $rangeResult;
            }
        }
    }
    
    // Check for analytical questions
    if (preg_match('/\b(most|highest|lowest|fewest|count|how many|who has|which character|what character|which clan|what clan)\b/i', $query)) {
        return handleAnalyticalQuery($conn, $query, $queryLower);
    }
    
    // Default: Simple text search
    $results = [];
    $characters = db_fetch_all($conn, 
        "SELECT id, character_name, player_name, clan, generation, sire, concept, biography, status, pc
         FROM characters 
         WHERE LOWER(character_name) LIKE ? 
            OR LOWER(player_name) LIKE ?
            OR LOWER(clan) LIKE ?
            OR LOWER(sire) LIKE ?
            OR LOWER(concept) LIKE ?
            OR LOWER(biography) LIKE ?
         ORDER BY character_name ASC
         LIMIT 50",
        'ssssss',
        ["%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%"]
    );
    
    foreach ($characters as $char) {
        $charId = $char['id'];
        $charData = getCharacterFullData($conn, $charId, $char);
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Handle location-based queries (embraced outside of, born in, etc.)
 */
function handleLocationQuery($conn, $query, $queryLower) {
    // Extract location from query
    $location = null;
    if (preg_match('/\boutside of\s+([a-z\s]+?)(?:\s|$|embraced|born|from)/i', $query, $matches)) {
        $location = trim($matches[1]);
    } elseif (preg_match('/\boutside\s+([a-z\s]+?)(?:\s|$|embraced|born|from)/i', $query, $matches)) {
        $location = trim($matches[1]);
    } elseif (preg_match('/\b(phoenix|tucson|flagstaff|scottsdale|tempe|mesa|glendale|chandler|peoria|surprise|yuma|prescott|sedona|arizona|california|nevada|new mexico|texas|mexico|europe|asia|africa|australia|south america|north america|united states|usa|america)\b/i', $query, $matches)) {
        $location = strtolower($matches[1]);
    }
    
    if (!$location) {
        return performKeywordSearch($conn, $queryLower);
    }
    
    $locationLower = strtolower($location);
    
    // Check if query is "outside of [location]" or "embraced outside of [location]"
    $isOutsideQuery = preg_match('/\boutside\s+(?:of\s+)?/i', $query);
    
    if ($isOutsideQuery) {
        // Find characters embraced/born OUTSIDE the specified location
        // This means their biography mentions embrace/birth in a different location
        // OR doesn't mention the location at all in context of embrace/birth
        return findCharactersOutsideLocation($conn, $locationLower, $queryLower);
    } else {
        // Find characters embraced/born IN the specified location
        return findCharactersInLocation($conn, $locationLower, $queryLower);
    }
}

/**
 * Find characters embraced/born outside of a specific location
 */
function findCharactersOutsideLocation($conn, $location, $queryLower) {
    // Get all characters with biographies
    $allCharacters = db_fetch_all($conn,
        "SELECT id, character_name, player_name, clan, generation, sire, concept, biography, status, pc
         FROM characters
         WHERE biography IS NOT NULL AND biography != ''",
        '', []
    );
    
    $results = [];
    $locationLower = strtolower($location);
    $locationPattern = '/\b' . preg_quote($locationLower, '/') . '\b/i';
    
    foreach ($allCharacters as $char) {
        $bio = $char['biography'] ?? '';
        if (empty($bio)) {
            // No biography - can't determine, but include them
            $charData = getCharacterFullData($conn, $char['id'], $char);
            $results[] = $charData;
            continue;
        }
        
        // Check if biography mentions embrace or birth
        $mentionsEmbrace = preg_match('/\b(embraced|embrace|embracing|embracement|turned|sired|siring)\b/i', $bio);
        $mentionsBirth = preg_match('/\b(born|birth|birthplace|originated)\b/i', $bio);
        
        if (!$mentionsEmbrace && !$mentionsBirth) {
            // No mention of embrace/birth - include (can't determine location)
            $charData = getCharacterFullData($conn, $char['id'], $char);
            $results[] = $charData;
            continue;
        }
        
        // Check if location is mentioned in context of embrace/birth
        // Look for location within 50 characters of embrace/birth keywords
        $bioLower = strtolower($bio);
        $locationInEmbraceContext = false;
        
        // Find all positions of embrace/birth keywords
        preg_match_all('/\b(embraced|embrace|embracing|embracement|turned|sired|siring|born|birth|birthplace|originated)\b/i', $bio, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $match) {
            $keywordPos = $match[1];
            // Extract context around keyword (50 chars before and after)
            $start = max(0, $keywordPos - 50);
            $length = min(strlen($bio), $keywordPos + strlen($match[0]) + 50) - $start;
            $context = substr($bio, $start, $length);
            
            // Check if location appears in this context
            if (preg_match($locationPattern, $context)) {
                $locationInEmbraceContext = true;
                break;
            }
        }
        
        // Include character if location is NOT mentioned in embrace context
        if (!$locationInEmbraceContext) {
            $charData = getCharacterFullData($conn, $char['id'], $char);
            $results[] = $charData;
        }
    }
    
    return $results;
}

/**
 * Find characters embraced/born in a specific location
 */
function findCharactersInLocation($conn, $location, $queryLower) {
    $locationPattern = '%' . $location . '%';
    
    $characters = db_fetch_all($conn,
        "SELECT id, character_name, player_name, clan, generation, sire, concept, biography, status, pc
         FROM characters
         WHERE LOWER(biography) LIKE ?
            OR LOWER(sire) LIKE ?
            OR LOWER(concept) LIKE ?
         ORDER BY character_name ASC
         LIMIT 50",
        'sss',
        [$locationPattern, $locationPattern, $locationPattern]
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Handle analytical queries (most, highest, count, etc.)
 */
function handleAnalyticalQuery($conn, $query, $queryLower) {
    $results = [];
    
    // Most/Highest Social/Physical/Mental traits
    if (preg_match('/\bmost\b.*\b(social|physical|mental)\s+traits?\b/i', $query, $matches)) {
        $category = ucfirst(strtolower($matches[1]));
        return findCharactersWithMostTraits($conn, $category);
    }
    
    // Most/Highest abilities in a category
    if (preg_match('/\bmost\b.*\b(talents?|skills?|knowledges?)\b/i', $query, $matches)) {
        $category = ucfirst(rtrim(strtolower($matches[1]), 's'));
        if ($category === 'talent') $category = 'Talents';
        elseif ($category === 'skill') $category = 'Skills';
        elseif ($category === 'knowledge') $category = 'Knowledges';
        return findCharactersWithMostAbilities($conn, $category);
    }
    
    // Most disciplines
    if (preg_match('/\bmost\s+disciplines?\b/i', $query)) {
        return findCharactersWithMostDisciplines($conn);
    }
    
    // Highest generation / Lowest generation
    if (preg_match('/\b(highest|lowest)\s+generation\b/i', $query, $matches)) {
        $order = strtolower($matches[1]) === 'highest' ? 'DESC' : 'ASC';
        return findCharactersByGeneration($conn, $order);
    }
    
    // Count questions
    if (preg_match('/\bhow many\b.*\b(social|physical|mental)\s+traits?\b/i', $query, $matches)) {
        $category = ucfirst(strtolower($matches[1]));
        return countTraitsByCategory($conn, $category);
    }
    
    // Clan-based analytical queries
    if (preg_match('/\b(which|what)\s+clan\s+(has|have)\s+(the\s+)?most\b/i', $query)) {
        // Check if it's asking about NPCs specifically
        if (preg_match('/\b(npcs?|non-?player|non\s+player)\b/i', $query)) {
            return findClanWithMostNPCs($conn);
        }
        // Check if it's asking about PCs specifically
        if (preg_match('/\b(pcs?|player\s+characters?)\b/i', $query)) {
            return findClanWithMostPCs($conn);
        }
        // Default: most characters overall
        return findClanWithMostCharacters($conn);
    }
    
    // Count by clan
    if (preg_match('/\bhow many\s+(npcs?|non-?player|non\s+player|pcs?|player\s+characters?|characters?)\s+(are|in|does)\s+(each|all)\s+clan\b/i', $query)) {
        return countCharactersByClan($conn);
    }
    
    // Range queries for clan character counts
    if (preg_match('/\b(which|what)\s+clans?\s+(have|has)\b/i', $query)) {
        $rangeResult = parseClanRangeQuery($conn, $query, $queryLower);
        if ($rangeResult !== null) {
            return $rangeResult;
        }
    }
    
    // Default: try to find characters matching any keywords
    return performKeywordSearch($conn, $queryLower);
}

/**
 * Find characters with most traits in a specific category
 */
function findCharactersWithMostTraits($conn, $category) {
    $characters = db_fetch_all($conn,
        "SELECT c.id, c.character_name, c.player_name, c.clan, c.generation, c.sire, c.concept, c.biography, c.status, c.pc,
                COUNT(ct.id) as trait_count
         FROM characters c
         LEFT JOIN character_traits ct ON c.id = ct.character_id AND ct.trait_category = ?
         GROUP BY c.id
         HAVING trait_count > 0
         ORDER BY trait_count DESC, c.character_name ASC
         LIMIT 10",
        's', [$category]
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $charData['analytical_result'] = [
            'type' => 'most_traits',
            'category' => $category,
            'count' => $char['trait_count']
        ];
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Find characters with most abilities in a category
 */
function findCharactersWithMostAbilities($conn, $category) {
    $characters = db_fetch_all($conn,
        "SELECT c.id, c.character_name, c.player_name, c.clan, c.generation, c.sire, c.concept, c.biography, c.status, c.pc,
                COUNT(ca.id) as ability_count
         FROM characters c
         LEFT JOIN character_abilities ca ON c.id = ca.character_id AND ca.ability_category = ?
         GROUP BY c.id
         HAVING ability_count > 0
         ORDER BY ability_count DESC, c.character_name ASC
         LIMIT 10",
        's', [$category]
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $charData['analytical_result'] = [
            'type' => 'most_abilities',
            'category' => $category,
            'count' => $char['ability_count']
        ];
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Find characters with most disciplines
 */
function findCharactersWithMostDisciplines($conn) {
    $characters = db_fetch_all($conn,
        "SELECT c.id, c.character_name, c.player_name, c.clan, c.generation, c.sire, c.concept, c.biography, c.status, c.pc,
                COUNT(cd.id) as discipline_count
         FROM characters c
         LEFT JOIN character_disciplines cd ON c.id = cd.character_id
         GROUP BY c.id
         HAVING discipline_count > 0
         ORDER BY discipline_count DESC, c.character_name ASC
         LIMIT 10",
        '', []
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $charData['analytical_result'] = [
            'type' => 'most_disciplines',
            'count' => $char['discipline_count']
        ];
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Find characters by generation (highest/lowest)
 */
function findCharactersByGeneration($conn, $order) {
    $characters = db_fetch_all($conn,
        "SELECT id, character_name, player_name, clan, generation, sire, concept, biography, status, pc
         FROM characters
         WHERE generation IS NOT NULL AND generation > 0
         ORDER BY generation $order, character_name ASC
         LIMIT 10",
        '', []
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $charData['analytical_result'] = [
            'type' => 'generation',
            'generation' => $char['generation']
        ];
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Count traits by category across all characters
 */
function countTraitsByCategory($conn, $category) {
    $total = db_fetch_one($conn,
        "SELECT COUNT(*) as total
         FROM character_traits
         WHERE trait_category = ?",
        's', [$category]
    );
    
    return [[
        'analytical_result' => [
            'type' => 'count',
            'category' => $category,
            'total' => $total['total'] ?? 0
        ]
    ]];
}

/**
 * Find which clan has the most NPCs
 */
function findClanWithMostNPCs($conn) {
    $results = db_fetch_all($conn,
        "SELECT clan, COUNT(*) as npc_count
         FROM characters
         WHERE (pc = 0 OR pc IS NULL) AND clan IS NOT NULL AND clan != ''
         GROUP BY clan
         ORDER BY npc_count DESC, clan ASC
         LIMIT 10",
        '', []
    );
    
    if (empty($results)) {
        return [[
            'analytical_result' => [
                'type' => 'clan_most_npcs',
                'clans' => [],
                'count' => 0
            ]
        ]];
    }
    
    $maxCount = $results[0]['npc_count'];
    $topClans = [];
    
    // Get all clans with the maximum count
    foreach ($results as $row) {
        if ($row['npc_count'] == $maxCount) {
            $topClans[] = [
                'clan' => $row['clan'],
                'count' => $row['npc_count']
            ];
        } else {
            break;
        }
    }
    
    return [[
        'analytical_result' => [
            'type' => 'clan_most_npcs',
            'clans' => $topClans,
            'count' => $maxCount
        ]
    ]];
}

/**
 * Find which clan has the most PCs
 */
function findClanWithMostPCs($conn) {
    $results = db_fetch_all($conn,
        "SELECT clan, COUNT(*) as pc_count
         FROM characters
         WHERE pc = 1 AND clan IS NOT NULL AND clan != ''
         GROUP BY clan
         ORDER BY pc_count DESC, clan ASC
         LIMIT 10",
        '', []
    );
    
    if (empty($results)) {
        return [[
            'analytical_result' => [
                'type' => 'clan_most_pcs',
                'clans' => [],
                'count' => 0
            ]
        ]];
    }
    
    $maxCount = $results[0]['pc_count'];
    $topClans = [];
    
    // Get all clans with the maximum count
    foreach ($results as $row) {
        if ($row['pc_count'] == $maxCount) {
            $topClans[] = [
                'clan' => $row['clan'],
                'count' => $row['pc_count']
            ];
        } else {
            break;
        }
    }
    
    return [[
        'analytical_result' => [
            'type' => 'clan_most_pcs',
            'clans' => $topClans,
            'count' => $maxCount
        ]
    ]];
}

/**
 * Find which clan has the most characters overall
 */
function findClanWithMostCharacters($conn) {
    $results = db_fetch_all($conn,
        "SELECT clan, COUNT(*) as character_count
         FROM characters
         WHERE clan IS NOT NULL AND clan != ''
         GROUP BY clan
         ORDER BY character_count DESC, clan ASC
         LIMIT 10",
        '', []
    );
    
    if (empty($results)) {
        return [[
            'analytical_result' => [
                'type' => 'clan_most_characters',
                'clans' => [],
                'count' => 0
            ]
        ]];
    }
    
    $maxCount = $results[0]['character_count'];
    $topClans = [];
    
    // Get all clans with the maximum count
    foreach ($results as $row) {
        if ($row['character_count'] == $maxCount) {
            $topClans[] = [
                'clan' => $row['clan'],
                'count' => $row['character_count']
            ];
        } else {
            break;
        }
    }
    
    return [[
        'analytical_result' => [
            'type' => 'clan_most_characters',
            'clans' => $topClans,
            'count' => $maxCount
        ]
    ]];
}

/**
 * Count characters by clan (NPCs and PCs)
 */
function countCharactersByClan($conn) {
    $results = db_fetch_all($conn,
        "SELECT 
            clan,
            COUNT(*) as total_count,
            SUM(CASE WHEN pc = 1 THEN 1 ELSE 0 END) as pc_count,
            SUM(CASE WHEN pc = 0 OR pc IS NULL THEN 1 ELSE 0 END) as npc_count
         FROM characters
         WHERE clan IS NOT NULL AND clan != ''
         GROUP BY clan
         ORDER BY total_count DESC, clan ASC",
        '', []
    );
    
    return [[
        'analytical_result' => [
            'type' => 'clan_counts',
            'clans' => $results
        ]
    ]];
}

/**
 * Parse and handle clan range queries (e.g., "more than 0 and fewer than 3")
 */
function parseClanRangeQuery($conn, $query, $queryLower) {
    // Check if query mentions characters/NPCs/PCs and numbers
    if (!preg_match('/\b(characters?|npcs?|pcs?|non-?player|player\s+characters?)\b/i', $query)) {
        return null;
    }
    
    // Determine if we're counting NPCs, PCs, or all characters
    $countType = 'total';
    if (preg_match('/\b(npcs?|non-?player|non\s+player)\b/i', $query)) {
        $countType = 'npc';
    } elseif (preg_match('/\b(pcs?|player\s+characters?)\b/i', $query)) {
        $countType = 'pc';
    }
    
    // Extract numeric comparisons
    $minCount = null;
    $maxCount = null;
    $exactCount = null;
    
    // Pattern for "more than X" or "greater than X" or "at least X"
    if (preg_match('/\b(more\s+than|greater\s+than|over|above)\s+(\d+)\b/i', $query, $matches)) {
        $minCount = intval($matches[2]); // More than X means > X
    }
    if (preg_match('/\b(at\s+least)\s+(\d+)\b/i', $query, $matches)) {
        $minCount = intval($matches[2]) - 1; // At least X means >= X, so we use > (X-1)
    }
    
    // Pattern for "fewer than X" or "less than X" or "under X" or "below X"
    if (preg_match('/\b(fewer\s+than|less\s+than|under|below)\s+(\d+)\b/i', $query, $matches)) {
        $maxCount = intval($matches[2]); // Fewer than X means < X
    }
    if (preg_match('/\b(at\s+most)\s+(\d+)\b/i', $query, $matches)) {
        $maxCount = intval($matches[2]) + 1; // At most X means <= X, so we use < (X+1)
    }
    
    // Pattern for "exactly X" or "X characters"
    if (preg_match('/\bexactly\s+(\d+)\b/i', $query, $matches)) {
        $exactCount = intval($matches[2]);
    }
    
    // Pattern for "between X and Y" (takes precedence over individual patterns)
    if (preg_match('/\bbetween\s+(\d+)\s+and\s+(\d+)\b/i', $query, $matches)) {
        $minCount = intval($matches[1]);
        $maxCount = intval($matches[2]);
        // Clear individual patterns since "between" is more specific
        // Note: We'll keep any individually matched patterns and combine them
    }
    
    // Pattern for "equal to X" or just a number followed by characters
    if (!$exactCount && !$minCount && !$maxCount) {
        if (preg_match('/\b(equal\s+to|equals?)\s+(\d+)\b/i', $query, $matches)) {
            $exactCount = intval($matches[2]);
        }
    }
    
    // Handle "more than X and fewer than Y" - ensure both patterns are found
    // The individual patterns above should catch both, but let's make sure they work together
    
    // Build SQL query based on count type and range
    $whereConditions = ["clan IS NOT NULL", "clan != ''"];
    $havingConditions = [];
    
    if ($countType === 'npc') {
        $countExpr = "SUM(CASE WHEN pc = 0 OR pc IS NULL THEN 1 ELSE 0 END)";
    } elseif ($countType === 'pc') {
        $countExpr = "SUM(CASE WHEN pc = 1 THEN 1 ELSE 0 END)";
    } else {
        $countExpr = "COUNT(*)";
    }
    
    if ($exactCount !== null) {
        $havingConditions[] = "$countExpr = " . intval($exactCount);
    } else {
        if ($minCount !== null) {
            $havingConditions[] = "$countExpr > " . intval($minCount);
        }
        if ($maxCount !== null) {
            $havingConditions[] = "$countExpr < " . intval($maxCount);
        }
    }
    
    // If no range conditions found, return null
    if (empty($havingConditions)) {
        return null;
    }
    
    $havingClause = !empty($havingConditions) ? "HAVING " . implode(" AND ", $havingConditions) : "";
    
    $sql = "SELECT 
                clan,
                $countExpr as character_count,
                COUNT(*) as total_count,
                SUM(CASE WHEN pc = 1 THEN 1 ELSE 0 END) as pc_count,
                SUM(CASE WHEN pc = 0 OR pc IS NULL THEN 1 ELSE 0 END) as npc_count
            FROM characters
            WHERE " . implode(" AND ", $whereConditions) . "
            GROUP BY clan
            $havingClause
            ORDER BY character_count ASC, clan ASC";
    
    $results = db_fetch_all($conn, $sql, '', []);
    
    if (empty($results)) {
        return [[
            'analytical_result' => [
                'type' => 'clan_range',
                'clans' => [],
                'count_type' => $countType,
                'min' => $minCount,
                'max' => $maxCount,
                'exact' => $exactCount
            ]
        ]];
    }
    
    $clans = [];
    foreach ($results as $row) {
        $clans[] = [
            'clan' => $row['clan'],
            'count' => $row['character_count'],
            'total_count' => $row['total_count'],
            'pc_count' => $row['pc_count'],
            'npc_count' => $row['npc_count']
        ];
    }
    
    return [[
        'analytical_result' => [
            'type' => 'clan_range',
            'clans' => $clans,
            'count_type' => $countType,
            'min' => $minCount,
            'max' => $maxCount,
            'exact' => $exactCount
        ]
    ]];
}

/**
 * Perform keyword-based search
 */
function performKeywordSearch($conn, $queryLower) {
    $characters = db_fetch_all($conn, 
        "SELECT id, character_name, player_name, clan, generation, sire, concept, biography, status, pc
         FROM characters 
         WHERE LOWER(character_name) LIKE ? 
            OR LOWER(player_name) LIKE ?
            OR LOWER(clan) LIKE ?
            OR LOWER(sire) LIKE ?
            OR LOWER(concept) LIKE ?
            OR LOWER(biography) LIKE ?
         ORDER BY character_name ASC
         LIMIT 50",
        'ssssss',
        ["%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%", "%$queryLower%"]
    );
    
    $results = [];
    foreach ($characters as $char) {
        $charData = getCharacterFullData($conn, $char['id'], $char);
        $results[] = $charData;
    }
    
    return $results;
}

/**
 * Get full character data including all related information
 */
function getCharacterFullData($conn, $charId, $char) {
    // Get traits
    $traits = db_fetch_all($conn,
        "SELECT trait_name, trait_category, trait_level 
         FROM character_traits 
         WHERE character_id = ?",
        'i', [$charId]
    );
    
    // Get abilities
    $abilities = db_fetch_all($conn,
        "SELECT ability_name, ability_category, level, specialization
         FROM character_abilities
         WHERE character_id = ?
         ORDER BY ability_category, ability_name",
        'i', [$charId]
    );
    
    // Get disciplines
    $disciplines = db_fetch_all($conn,
        "SELECT discipline_name, level, is_custom
         FROM character_disciplines
         WHERE character_id = ?
         ORDER BY discipline_name",
        'i', [$charId]
    );
    
    // Get backgrounds
    $backgrounds = db_fetch_all($conn,
        "SELECT background_name, level
         FROM character_backgrounds
         WHERE character_id = ?
         ORDER BY background_name",
        'i', [$charId]
    );
    
    // Get relationships
    $relationships = db_fetch_all($conn,
        "SELECT related_character_name, relationship_type, relationship_subtype, strength, description
         FROM character_relationships
         WHERE character_id = ?
         ORDER BY relationship_type, related_character_name",
        'i', [$charId]
    );
    
    return [
        'character' => $char,
        'traits' => $traits,
        'abilities' => $abilities,
        'disciplines' => $disciplines,
        'backgrounds' => $backgrounds,
        'relationships' => $relationships
    ];
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
                    <div class="form-text text-light">Search by name, clan, or ask analytical questions like &quot;which clan has the most NPCs&quot;, &quot;which clans have more than 0 and fewer than 3 characters&quot;, &quot;most social traits&quot;, &quot;highest generation&quot;, or &quot;most disciplines&quot;.</div>
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
                    html += '<p class="mb-0 opacity-75 small">(Tied for most)</p>';
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
                    html += '<p class="mb-0 opacity-75 small">(Tied for most)</p>';
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
                    html += '<p class="mb-0 opacity-75 small">(Tied for most)</p>';
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

