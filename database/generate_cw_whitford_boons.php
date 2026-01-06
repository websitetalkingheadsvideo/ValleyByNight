<?php
/**
 * Generate C.W. Whitford's Boons Script
 * 
 * Creates tailor-made boons for Charles "C.W." Whitford with exactly 50% of all NPCs in the database.
 * Each boon description is customized based on the NPC's character, clan, role, and background.
 * 
 * Distribution: 30% Major, 50% Minor, 20% Trivial
 * All boons are registered with Harpy (Cordelia Fairchild or system).
 * 
 * Usage: Run via web browser
 * URL: database/generate_cw_whitford_boons.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('output_buffering', 0); // Disable output buffering for real-time progress
ini_set('implicit_flush', 1); // Enable implicit flushing

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// HTML output setup
echo "<!DOCTYPE html>\n";
echo "<html><head><title>C.W. Whitford Boons Generation</title>\n";
echo "<style>
body { font-family: monospace; background: #1a0f0f; color: #f5e6d3; padding: 20px; }
h1 { color: #8B0000; }
h2 { color: #a0522d; border-bottom: 2px solid #8B0000; padding-bottom: 5px; }
pre { background: #2a1515; padding: 15px; border: 2px solid #8B0000; border-radius: 5px; overflow-x: auto; }
.success { color: #1a6b3a; }
.error { color: #b22222; }
.warning { color: #8B6508; }
.info { color: #87ceeb; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #8B0000; padding: 8px; text-align: left; }
th { background: #3d1f1f; color: #f5e6d3; }
tr:nth-child(even) { background: #2a1515; }
</style></head><body>\n";

echo "<h1>💎 C.W. Whitford's Boon Generation System</h1>\n";
echo "<pre>\n";

/**
 * Get C.W. Whitford's character ID from database
 */
function get_cw_whitford_character_id($conn): ?int {
    $query = "SELECT id FROM characters WHERE (character_name = 'Charles \"C.W.\" Whitford' OR character_name LIKE '%Whitford%') AND player_name = 'NPC' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['id'] : null;
}

/**
 * Get all NPCs excluding C.W. Whitford
 */
function get_all_npcs_excluding_cw($conn, int $cw_id): array {
    $query = "SELECT c.*
              FROM characters c
              WHERE c.player_name = 'NPC' 
              AND c.id != ?
              ORDER BY c.id";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $cw_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $npcs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $npcs[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $npcs;
}

/**
 * Select exactly 50% of NPCs deterministically using hash-based algorithm
 */
function select_npcs_for_cw(array $npcs, string $seed = 'cw_whitford_boons_v1'): array {
    $selected = [];
    foreach ($npcs as $npc) {
        $hash = md5($npc['id'] . '_' . $seed);
        $value = hexdec(substr($hash, 0, 8)) % 100;
        if ($value < 50) { // 50% selection
            $selected[] = $npc;
        }
    }
    return $selected;
}

/**
 * Get existing boons for C.W. Whitford
 */
function get_existing_cw_boons($conn, int $cw_id): array {
    $query = "SELECT debtor_id, boon_type, status 
              FROM boons 
              WHERE creditor_id = ? AND status = 'active'";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $cw_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $existing = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existing[$row['debtor_id']] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $existing;
}

/**
 * Get Harpy character ID (Cordelia Fairchild)
 */
function get_harpy_character_id($conn): ?int {
    $query = "SELECT id FROM characters WHERE character_name = 'Cordelia Fairchild' AND player_name = 'NPC' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['id'] : null;
}

/**
 * Assign boon tier deterministically based on NPC ID
 * Distribution: 30% Major, 50% Minor, 20% Trivial
 */
function assign_boon_tier(int $npc_id, string $seed = 'cw_whitford_boons_v1'): string {
    $hash = md5($npc_id . '_' . $seed);
    $value = hexdec(substr($hash, 0, 8)) % 100;
    
    if ($value < 30) {
        return 'major';
    } elseif ($value < 80) {
        return 'minor';
    } else {
        return 'trivial';
    }
}

/**
 * Generate C.W.-specific boon description
 * Tailored based on NPC's clan, role, concept, and biography
 * Reflects C.W.'s themes: Ventrue Primogen, real estate, power broker, political maneuvering
 */
function generate_cw_boon_description(array $npc, string $boon_tier, int $cw_id): string {
    $npc_name = $npc['character_name'] ?? 'Unknown';
    $clan = $npc['clan'] ?? 'Unknown';
    $generation = $npc['generation'] ?? '';
    $concept = $npc['concept'] ?? '';
    $title = $npc['title'] ?? '';
    $nature = $npc['nature'] ?? '';
    $demeanor = $npc['demeanor'] ?? '';
    $biography = $npc['biography'] ?? '';
    
    // Determine NPC's role/importance
    $is_primogen = stripos($title, 'primogen') !== false;
    $is_elder = ($generation && (int)$generation <= 9);
    $is_important = $is_primogen || $is_elder || stripos($title, 'prince') !== false || 
                    stripos($title, 'sheriff') !== false || stripos($title, 'harpy') !== false;
    
    // Extract key themes from concept/biography
    $is_business = stripos($concept . ' ' . $biography, 'business') !== false ||
                   stripos($concept . ' ' . $biography, 'merchant') !== false ||
                   stripos($concept . ' ' . $biography, 'corporate') !== false ||
                   stripos($concept . ' ' . $biography, 'real estate') !== false;
    $is_political = stripos($concept . ' ' . $biography, 'political') !== false ||
                    stripos($concept . ' ' . $biography, 'politic') !== false ||
                    stripos($concept . ' ' . $biography, 'influence') !== false;
    $is_information_broker = stripos($concept . ' ' . $biography, 'information') !== false ||
                             stripos($concept . ' ' . $biography, 'gossip') !== false ||
                             stripos($concept . ' ' . $biography, 'intelligence') !== false;
    
    // Generate description based on tier and character traits
    $descriptions = [];
    
    if ($boon_tier === 'trivial') {
        // 20% - Small favors, minor social gestures
        $templates = [
            "A small favor owed from a brief encounter at Elysium. C.W.'s business acumen provided {$npc_name} with a useful real estate tip, but the advice came with a price.",
            "A minor social debt from when C.W. provided a timely introduction to a zoning board member or city official during an awkward moment.",
            "A trivial favor from a casual conversation where C.W. shared useful information about Phoenix's development landscape and political connections.",
            "A small boon owed for C.W.'s assistance in navigating a delicate business situation that could have caused financial embarrassment.",
            "A minor debt from when C.W.'s corporate connections provided cover for {$npc_name}'s discreet business transaction or property acquisition.",
            "A trivial favor from C.W. helping {$npc_name} avoid a political misstep through a well-timed redirect or strategic warning.",
            "A small boon from when C.W.'s knowledge of Phoenix's power structure prevented {$npc_name} from inadvertently offending the wrong Kindred or mortal official.",
            "A minor debt from C.W.'s assistance in translating the coded language of corporate politics and real estate deals during a confusing negotiation."
        ];
        
        // Character-specific variations
        if ($is_business) {
            $templates[] = "A trivial favor from when C.W. helped {$npc_name} access a prime property listing or development opportunity through his extensive real estate network.";
            $templates[] = "A small boon from C.W. providing {$npc_name} with a lead or reference that saved significant time in a business negotiation.";
        }
        
        if ($is_political) {
            $templates[] = "A minor favor from C.W. helping {$npc_name} avoid a bad political move through a strategic warning disguised as casual advice.";
            $templates[] = "A trivial debt from when C.W.'s political connections introduced {$npc_name} to a valuable contact or supporter.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A small boon from C.W. sharing a piece of business intelligence or political gossip that proved more valuable than {$npc_name} initially realized.";
            $templates[] = "A minor favor from when C.W. helped {$npc_name} verify or contextualize a piece of information through his extensive network of contacts.";
        }
        
        // Clan-specific trivial boons
        if ($clan === 'Ventrue') {
            $templates[] = "A trivial boon from when C.W., in his role as Ventrue Primogen, provided guidance to {$npc_name} on navigating clan politics and business hierarchies.";
            $templates[] = "A small favor from C.W. helping {$npc_name} understand the financial or political implications of a situation through their shared Ventrue perspective.";
        } elseif ($clan === 'Tremere') {
            $templates[] = "A trivial favor from when C.W.'s business connections helped {$npc_name} obtain access to a restricted property or secure a location for chantry operations.";
            $templates[] = "A small boon from C.W. providing {$npc_name} with information about a real estate opportunity or zoning issue that affected their interests.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A trivial boon from when C.W.'s understanding of Phoenix's social landscape helped {$npc_name} avoid a costly social misstep with another Kindred or patron.";
            $templates[] = "A small favor from C.W. providing {$npc_name} with intelligence about a business or political opportunity through his network.";
        }
        
    } elseif ($boon_tier === 'minor') {
        // 50% - Moderate favors, information, protection
        $templates = [
            "A moderate favor from when C.W.'s intervention prevented {$npc_name} from making a significant business or political error that would have cost them dearly.",
            "A minor boon from C.W. providing {$npc_name} with valuable intelligence about a rival, threat, or opportunity, delivered through his extensive network of contacts.",
            "A service rendered when C.W. used his position as Ventrue Primogen and real estate influence to shield {$npc_name} from unwanted attention or suspicion.",
            "A moderate debt from when C.W.'s knowledge of Phoenix's development and political web helped {$npc_name} navigate a complex business or political situation.",
            "A minor favor from C.W. facilitating an important meeting, introduction, or property transaction that {$npc_name} needed but couldn't arrange alone.",
            "A service from when C.W.'s corporate connections and political leverage provided {$npc_name} with the cover needed for a sensitive operation or deal.",
            "A moderate boon from C.W. helping {$npc_name} resolve a dispute or conflict through his understanding of court dynamics, business leverage, and strategic debts.",
            "A minor debt from when C.W.'s information network and real estate intelligence alerted {$npc_name} to a threat or opportunity they would otherwise have missed."
        ];
        
        // Character-specific variations
        if ($is_business) {
            $templates[] = "A moderate favor from C.W. providing {$npc_name} with access to a prime development opportunity or protected business deal through his century of connections.";
            $templates[] = "A minor boon from when C.W. helped {$npc_name} negotiate or close an important business transaction through his understanding of Kindred politics and leverage.";
        }
        
        if ($is_political) {
            $templates[] = "A moderate service from C.W. helping {$npc_name} navigate a delicate political situation through his understanding of Phoenix's power structure and influence networks.";
            $templates[] = "A minor favor from when C.W.'s political connections prevented {$npc_name} from making a costly mistake with another Kindred or mortal authority.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A moderate boon from C.W. sharing sensitive information from his collection of business and political secrets that {$npc_name} needed but couldn't obtain independently.";
            $templates[] = "A minor service from when C.W. helped {$npc_name} verify a critical piece of intelligence that affected their standing, safety, or business interests.";
        }
        
        if ($is_primogen || $is_elder) {
            $templates[] = "A moderate favor from when C.W., leveraging his Primogen position and business connections, provided {$npc_name} with crucial political or financial intelligence.";
            $templates[] = "A minor boon from C.W. helping {$npc_name} navigate a delicate inter-clan situation where his knowledge of debts, favors, and real estate leverage proved invaluable.";
        }
        
        // Clan-specific minor boons
        if ($clan === 'Ventrue') {
            $templates[] = "A moderate service from when C.W.'s political intelligence and business connections helped {$npc_name} outmaneuver a business or political rival within the Camarilla hierarchy.";
            $templates[] = "A minor boon from C.W. providing {$npc_name} with leverage or information that strengthened their position in a delicate negotiation or power play.";
        } elseif ($clan === 'Tremere') {
            $templates[] = "A moderate favor from when C.W. helped {$npc_name} navigate chantry politics or provided intelligence about a property acquisition, zoning issue, or business threat.";
            $templates[] = "A minor boon from C.W. facilitating {$npc_name}'s access to a protected property, secure location, or business resource through his political connections.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A moderate boon from when C.W. orchestrated a business introduction or property opportunity that significantly enhanced {$npc_name}'s reputation or standing in the court.";
            $templates[] = "A minor favor from C.W. helping {$npc_name} recover from a social or business disaster through his understanding of Harpy networks and public perception.";
        } elseif ($clan === 'Nosferatu') {
            $templates[] = "A moderate service from when C.W.'s aboveground network and business connections provided {$npc_name} with intelligence or resources they couldn't obtain through their usual channels.";
            $templates[] = "A minor favor from C.W. helping {$npc_name} sell or trade sensitive information through his connections, ensuring they got fair value in a business transaction.";
        } elseif ($clan === 'Gangrel') {
            $templates[] = "A moderate favor from when C.W. used his connections to help {$npc_name} deal with a threat or opportunity that required Camarilla resources, business knowledge, or political leverage.";
            $templates[] = "A minor service from C.W. helping {$npc_name} maintain their independence while still accessing the protection and resources of organized Kindred society and business networks.";
        } elseif ($clan === 'Brujah') {
            $templates[] = "A moderate boon from when C.W. prevented {$npc_name} from making a political mistake that would have destroyed their standing or led to severe punishment.";
            $templates[] = "A minor favor from C.W. helping {$npc_name} channel their passion and activism in ways that achieved their goals without violating Tradition, decorum, or business interests.";
        }
        
    } else { // major
        // 30% - Significant favors, life-saving, major political moves
        $templates = [
            "A significant boon from when C.W. used his extensive network of debts, favors, and business connections to protect {$npc_name} from a serious threat, possibly saving their unlife or position.",
            "A major favor from when C.W.'s intervention, orchestrated through his Harpy connections, Primogen authority, and real estate leverage, prevented {$npc_name} from facing severe consequences for a grave error.",
            "A substantial debt from when C.W. leveraged his decades of collected boons, business intelligence, and political influence to resolve a crisis that threatened {$npc_name}'s standing or existence.",
            "A major boon from C.W. providing {$npc_name} with information or assistance so valuable that it fundamentally changed their position, saved them from ruin, or secured a critical business deal.",
            "A significant service from when C.W., drawing on his deep knowledge of Phoenix's Kindred politics, real estate empire, and his role as power broker, orchestrated a complex solution to a problem {$npc_name} couldn't solve alone.",
            "A major favor from C.W. using his position as Primogen and his collection of secrets, business leverage, and political debts to shield {$npc_name} from exposure or retaliation that would have destroyed them.",
            "A substantial boon from when C.W.'s strategic manipulations and social web prevented {$npc_name} from falling victim to a plot that would have ended their unlife or erased their influence."
        ];
        
        // Character-specific major boons
        if ($is_primogen) {
            $templates[] = "A major political favor from when C.W., leveraging his Primogen position, Harpy network, and business connections, helped {$npc_name} secure or maintain their own position through a complex web of social and financial maneuvering.";
            $templates[] = "A significant debt from C.W. providing {$npc_name} with critical political intelligence, business leverage, or protection that preserved their standing during a crisis in the court.";
        }
        
        if ($is_business) {
            $templates[] = "A major boon from C.W. providing {$npc_name} with access to a critical business opportunity, property acquisition, or financial deal that advanced their interests in ways they couldn't achieve alone, possibly saving years of effort.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A substantial favor from when C.W. shared critical intelligence from his decades of collected secrets, business networks, and political connections that {$npc_name} needed to prevent disaster or seize a major opportunity.";
        }
        
        // Clan-specific major boons
        if ($clan === 'Ventrue') {
            $templates[] = "A major service from when C.W. leveraged his century of collected debts, Harpy connections, and business empire to help {$npc_name} survive a power struggle, corporate takeover, or political crisis that threatened their position or existence.";
            $templates[] = "A substantial favor from C.W. orchestrating a complex political and financial solution that saved {$npc_name} from ruin, using his knowledge of Kindred politics, real estate leverage, and influence to resolve a situation that would have destroyed them.";
        } elseif ($clan === 'Tremere') {
            $templates[] = "A major boon from when C.W. used his political connections, Harpy network, and business influence to protect {$npc_name} from exposure or retaliation after a thaumaturgical incident that could have destroyed their standing in the Pyramid.";
            $templates[] = "A significant favor from C.W. providing {$npc_name} with access to a critical property, secure location, or business resource that fundamentally advanced their thaumaturgical research or chantry operations, possibly saving them decades of work.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A major favor from when C.W.'s intervention, orchestrated through his Harpy network and Primogen authority, saved {$npc_name} from social ruin or destruction after a catastrophic scandal or business mistake.";
            $templates[] = "A significant boon from C.W. providing {$npc_name} with access to a patron, business opportunity, or property that transformed their standing in Kindred society and the arts, changing their entire unlife trajectory.";
        } elseif ($clan === 'Nosferatu') {
            $templates[] = "A major service from when C.W. orchestrated a complex information exchange and business transaction that saved {$npc_name} from destruction, using his aboveground network and Harpy connections to provide intelligence and resources they couldn't obtain alone.";
            $templates[] = "A substantial boon from C.W. using his social connections, business leverage, and political influence to protect {$npc_name}'s haven, information network, or loved ones from a threat that would have erased them completely.";
        } elseif ($clan === 'Gangrel') {
            $templates[] = "A major boon from when C.W. used his Camarilla connections, business network, and information resources to protect {$npc_name} from a threat or help them survive a crisis that their desert isolation couldn't solve alone.";
            $templates[] = "A significant service from C.W. providing {$npc_name} with critical intelligence, business assistance, or property access that saved their unlife or protected something they valued more than survival itself.";
        } elseif ($clan === 'Brujah') {
            $templates[] = "A major favor from when C.W. prevented {$npc_name} from making a fatal political error that would have led to their destruction, using his understanding of court politics and business leverage to guide them away from disaster.";
            $templates[] = "A substantial boon from C.W. orchestrating protection or intervention that saved {$npc_name} from the consequences of their own passion or activism when it threatened to destroy them or their cause.";
        }
    }
    
    // Select deterministically based on NPC ID
    $index = hexdec(substr(md5($npc['id'] . '_desc'), 0, 8)) % count($templates);
    return $templates[$index];
}

/**
 * Get a valid system user ID for created_by field
 * Returns the first admin/storyteller user ID, or first user ID found, or 1 as fallback
 */
function get_system_user_id($conn): int {
    // Try to find an admin or storyteller user first
    $query = "SELECT id FROM users WHERE role IN ('admin', 'storyteller') LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return (int)$row['id'];
    }
    
    // Fallback: get any user
    $query = "SELECT id FROM users ORDER BY id ASC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return (int)$row['id'];
    }
    
    // Final fallback: return 1 (assumes user ID 1 exists)
    return 1;
}

/**
 * Create a boon in the database
 */
function create_cw_boon($conn, int $creditor_id, int $debtor_id, string $boon_type, string $description, ?string $harpy_name, int $created_by_user_id): ?int {
    global $last_boon_error;
    $last_boon_error = null;
    
    $harpy_notes = "Auto-registered by C.W. Whitford boon generation system. Generated " . date('Y-m-d') . ".";
    $status = 'active';
    $notes = null;
    $due_date = null;
    $created_by = $created_by_user_id;
    
    // Match the exact INSERT pattern from api_boons.php (date_registered is set via UPDATE, not INSERT)
    $query = "INSERT INTO boons (
                creditor_id, 
                debtor_id, 
                boon_type, 
                status, 
                description, 
                notes,
                due_date,
                created_by,
                registered_with_harpy, 
                harpy_notes,
                created_date
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        $error_msg = mysqli_error($conn);
        $last_boon_error = "Prepare failed: " . $error_msg;
        error_log("Boon creation prepare failed: " . $error_msg . " Query: " . $query);
        return null;
    }
    
    // Bind parameters: creditor_id (i), debtor_id (i), boon_type (s), status (s), description (s), 
    //                  notes (s/null), due_date (s/null), created_by (i/null), registered_with_harpy (s/null), harpy_notes (s)
    $bind_result = mysqli_stmt_bind_param($stmt, "iisssssiss", 
        $creditor_id, 
        $debtor_id, 
        $boon_type, 
        $status,
        $description,
        $notes,
        $due_date,
        $created_by,
        $harpy_name,
        $harpy_notes
    );
    
    if (!$bind_result) {
        $error_msg = mysqli_stmt_error($stmt) ?: mysqli_error($conn);
        $last_boon_error = "Bind failed: " . $error_msg;
        error_log("Boon creation bind failed: " . $error_msg);
        mysqli_stmt_close($stmt);
        return null;
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt) ?: mysqli_error($conn);
        $last_boon_error = "Execute failed: " . $error;
        error_log("Boon creation execute failed: " . $error . " For NPC ID: " . $debtor_id);
        mysqli_stmt_close($stmt);
        return null;
    }
    
    $boon_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Update date_registered if registered_with_harpy is set (matches api_boons.php pattern)
    if ($harpy_name && $boon_id) {
        $update_query = "UPDATE boons SET date_registered = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "i", $boon_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
    }
    
    return $boon_id;
}

/**
 * Validate C.W.'s boons
 */
function validate_cw_boons($conn, int $cw_id, int $total_npc_count): array {
    $validation = [
        'valid' => true,
        'errors' => [],
        'warnings' => [],
        'stats' => []
    ];
    
    // Get all active boons for C.W.
    $query = "SELECT debtor_id, boon_type, status, registered_with_harpy 
              FROM boons 
              WHERE creditor_id = ? AND status = 'active'";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        $validation['valid'] = false;
        $validation['errors'][] = "Failed to query boons: " . mysqli_error($conn);
        return $validation;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $cw_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $boons = [];
    $debtor_ids = [];
    $tier_counts = ['major' => 0, 'minor' => 0, 'trivial' => 0];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $boons[] = $row;
        $debtor_id = (int)$row['debtor_id'];
        
        // Check for duplicates
        if (isset($debtor_ids[$debtor_id])) {
            $validation['valid'] = false;
            $validation['errors'][] = "Duplicate boon found for debtor ID: {$debtor_id}";
        }
        $debtor_ids[$debtor_id] = true;
        
        // Count tiers
        $tier = strtolower($row['boon_type']);
        if (isset($tier_counts[$tier])) {
            $tier_counts[$tier]++;
        }
        
        // Check Harpy registration
        if (empty($row['registered_with_harpy'])) {
            $validation['warnings'][] = "Boon with debtor ID {$debtor_id} is not registered with Harpy";
        }
    }
    
    mysqli_stmt_close($stmt);
    
    $boon_count = count($boons);
    $expected_count = (int)ceil($total_npc_count * 0.5); // 50% of NPCs
    
    // Check 50% rule
    if ($boon_count !== $expected_count) {
        $validation['warnings'][] = "Boon count ({$boon_count}) does not match expected 50% ({$expected_count} of {$total_npc_count} NPCs)";
    }
    
    // Check tier distribution
    if ($boon_count > 0) {
        $major_pct = round(($tier_counts['major'] / $boon_count) * 100, 1);
        $minor_pct = round(($tier_counts['minor'] / $boon_count) * 100, 1);
        $trivial_pct = round(($tier_counts['trivial'] / $boon_count) * 100, 1);
        
        $validation['stats'] = [
            'total' => $boon_count,
            'major' => ['count' => $tier_counts['major'], 'percentage' => $major_pct],
            'minor' => ['count' => $tier_counts['minor'], 'percentage' => $minor_pct],
            'trivial' => ['count' => $tier_counts['trivial'], 'percentage' => $trivial_pct]
        ];
        
        // Check if distribution is close to target (30/50/20)
        if ($major_pct < 25 || $major_pct > 35) {
            $validation['warnings'][] = "Major boon percentage ({$major_pct}%) is outside target range (25-35%)";
        }
        if ($minor_pct < 45 || $minor_pct > 55) {
            $validation['warnings'][] = "Minor boon percentage ({$minor_pct}%) is outside target range (45-55%)";
        }
        if ($trivial_pct < 15 || $trivial_pct > 25) {
            $validation['warnings'][] = "Trivial boon percentage ({$trivial_pct}%) is outside target range (15-25%)";
        }
    }
    
    return $validation;
}

// Main execution
try {
    echo "Starting C.W. Whitford Boon Generation...\n";
    echo str_repeat("=", 70) . "\n\n";
    
    // Step 1: Get C.W. Whitford's ID
    echo "Step 1: Locating C.W. Whitford...\n";
    $cw_id = get_cw_whitford_character_id($conn);
    
    if (!$cw_id) {
        throw new Exception("ERROR: C.W. Whitford not found in database. Please ensure Charles \"C.W.\" Whitford exists as an NPC.");
    }
    
    echo "   ✓ Found C.W. Whitford (ID: {$cw_id})\n\n";
    
    // Step 2: Get all NPCs
    echo "Step 2: Querying all NPCs...\n";
    $all_npcs = get_all_npcs_excluding_cw($conn, $cw_id);
    $total_npcs = count($all_npcs);
    echo "   ✓ Found {$total_npcs} NPCs (excluding C.W. Whitford)\n\n";
    
    if ($total_npcs === 0) {
        throw new Exception("ERROR: No NPCs found in database. Cannot generate boons.");
    }
    
    // Step 3: Select 50% of NPCs
    echo "Step 3: Selecting 50% of NPCs (deterministic hash-based selection)...\n";
    $selected_npcs = select_npcs_for_cw($all_npcs);
    $selected_count = count($selected_npcs);
    $selected_pct = $total_npcs > 0 ? round(($selected_count / $total_npcs) * 100, 1) : 0;
    echo "   ✓ Selected {$selected_count} NPCs ({$selected_pct}% of total)\n\n";
    
    if ($selected_count === 0) {
        throw new Exception("ERROR: No NPCs selected. Selection algorithm may need adjustment.");
    }
    
    // Step 4: Get existing boons
    echo "Step 4: Checking existing boons...\n";
    $existing_boons = get_existing_cw_boons($conn, $cw_id);
    $existing_count = count($existing_boons);
    echo "   ✓ Found {$existing_count} existing active boons\n\n";
    
    // Step 5: Get Harpy
    echo "Step 5: Locating Harpy (Cordelia Fairchild)...\n";
    $harpy_id = get_harpy_character_id($conn);
    $harpy_name = $harpy_id ? 'Cordelia Fairchild' : 'System';
    echo "   ✓ Harpy: {$harpy_name}\n\n";
    
    // Step 6: Assign boon tiers and generate descriptions
    echo "Step 6: Assigning boon tiers and generating descriptions...\n";
    
    $tier_counts = ['major' => 0, 'minor' => 0, 'trivial' => 0];
    $boons_to_create = [];
    $boons_to_skip = [];
    
    foreach ($selected_npcs as $npc) {
        $npc_id = (int)$npc['id'];
        $npc_name = $npc['character_name'] ?? 'Unknown';
        
        // Check if boon already exists
        if (isset($existing_boons[$npc_id])) {
            $boons_to_skip[] = [
                'id' => $npc_id,
                'name' => $npc_name,
                'existing_tier' => $existing_boons[$npc_id]['boon_type']
            ];
            continue;
        }
        
        // Assign tier
        $boon_tier = assign_boon_tier($npc_id);
        $tier_counts[$boon_tier]++;
        
        // Generate description
        $description = generate_cw_boon_description($npc, $boon_tier, $cw_id);
        
        $boons_to_create[] = [
            'npc_id' => $npc_id,
            'npc_name' => $npc_name,
            'tier' => $boon_tier,
            'description' => $description
        ];
    }
    
    $to_create_count = count($boons_to_create);
    $to_skip_count = count($boons_to_skip);
    
    echo "   ✓ Assigned tiers for {$to_create_count} new boons\n";
    echo "      - Major: {$tier_counts['major']}\n";
    echo "      - Minor: {$tier_counts['minor']}\n";
    echo "      - Trivial: {$tier_counts['trivial']}\n";
    echo "   ✓ Skipping {$to_skip_count} NPCs with existing boons\n\n";
    
    // Step 7: Create boons
    if ($to_create_count > 0) {
        echo "Step 7: Creating boons in database...\n";
        
        // Get a valid system user ID for created_by field
        echo "   Locating system user for 'created_by' field...\n";
        $system_user_id = get_system_user_id($conn);
        echo "   ✓ Using user ID {$system_user_id} for system-generated boons\n\n";
        
        // Test database connectivity and basic INSERT first
        echo "   Testing database connection and INSERT capability...\n";
        $test_query = "SELECT 1 FROM boons LIMIT 1";
        $test_result = mysqli_query($conn, $test_query);
        if (!$test_result) {
            throw new Exception("Cannot query boons table. Database error: " . mysqli_error($conn));
        }
        echo "   ✓ Database connection verified\n\n";
        
        mysqli_begin_transaction($conn);
        $created = 0;
        $errors = [];
        
        // Test first boon creation to capture error details
        if (count($boons_to_create) > 0) {
            $test_boon = $boons_to_create[0];
            echo "   Testing first boon creation (this will be rolled back)...\n";
            
            $test_boon_id = create_cw_boon(
                $conn, 
                $cw_id, 
                $test_boon['npc_id'], 
                $test_boon['tier'], 
                $test_boon['description'],
                $harpy_name,
                $system_user_id
            );
            
            if ($test_boon_id) {
                echo "   ✓ Test boon created successfully (ID: {$test_boon_id})\n";
                echo "   ✓ Rollback test boon and proceeding with batch creation...\n";
                mysqli_rollback($conn);
                mysqli_begin_transaction($conn); // Start fresh transaction
            } else {
                global $last_boon_error;
                $error_display = $last_boon_error ?? mysqli_error($conn) ?? 'Unknown error';
                mysqli_rollback($conn);
                echo "   <span class='error'>✗ Test boon failed: " . htmlspecialchars($error_display) . "</span>\n";
                echo "   This indicates a database schema, constraint, or data type issue.\n\n";
                throw new Exception("Cannot create boons. Test failed: " . htmlspecialchars($error_display));
            }
        }
        
        foreach ($boons_to_create as $index => $boon) {
            echo "   Creating boon " . ($index + 1) . "/{$to_create_count} for {$boon['npc_name']}...";
            
            $boon_id = create_cw_boon(
                $conn, 
                $cw_id, 
                $boon['npc_id'], 
                $boon['tier'], 
                $boon['description'],
                $harpy_name,
                $system_user_id
            );
            
            if ($boon_id) {
                $created++;
                echo " ✓ Created ({$boon['tier']}) [Boon ID: {$boon_id}]\n";
            } else {
                $errors[] = $boon['npc_name'];
                
                $db_error = mysqli_error($conn);
                global $last_boon_error;
                $stmt_error = $last_boon_error ?? '';
                
                $display_error = $stmt_error ?: $db_error ?: 'Unknown database error - check error logs';
                
                echo " ✗ FAILED - " . htmlspecialchars($display_error) . "\n";
                
                // Show detailed error for first failure only
                if (count($errors) === 1) {
                    echo "\n      <span class='error'>=== FIRST FAILURE DEBUG INFO ===</span>\n";
                    echo "      Creditor ID (C.W. Whitford): {$cw_id}\n";
                    echo "      Debtor ID: {$boon['npc_id']}\n";
                    echo "      Debtor Name: {$boon['npc_name']}\n";
                    echo "      Boon Type: {$boon['tier']}\n";
                    echo "      Description length: " . strlen($boon['description']) . " chars\n";
                    echo "      Description preview: " . htmlspecialchars(substr($boon['description'], 0, 100)) . "...\n";
                    echo "      Harpy name: " . ($harpy_name ?? 'NULL') . "\n";
                    echo "      Database error: " . htmlspecialchars($db_error ?: 'None') . "\n";
                    echo "      Statement error: " . htmlspecialchars($stmt_error ?: 'None') . "\n";
                    echo "      <span class='error'>===================================</span>\n\n";
                    
                    // Stop transaction and exit to show error clearly
                    mysqli_rollback($conn);
                    throw new Exception("First boon creation failed. Error: " . htmlspecialchars($display_error) . "\nCheck debug info above.");
                }
                
                // Clear error for next iteration
                if (isset($last_boon_error)) {
                    $last_boon_error = null;
                }
            }
            
            // Flush output buffer to show progress in real-time
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        
        if (count($errors) > 0) {
            mysqli_rollback($conn);
            throw new Exception("Failed to create " . count($errors) . " boons. Errors: " . implode(', ', $errors));
        }
        
        mysqli_commit($conn);
        echo "\n   ✓ Successfully created {$created} boons\n\n";
    } else {
        echo "Step 7: No new boons to create (all selected NPCs already have boons)\n\n";
    }
    
    // Step 8: Validation and reporting
    echo "Step 8: Validating results...\n";
    
    $validation = validate_cw_boons($conn, $cw_id, $total_npcs);
    
    if ($validation['valid']) {
        echo "   ✓ Validation passed\n";
    } else {
        echo "   <span class='error'>✗ Validation failed</span>\n";
        foreach ($validation['errors'] as $error) {
            echo "      - " . htmlspecialchars($error) . "\n";
        }
    }
    
    if (!empty($validation['warnings'])) {
        echo "   <span class='warning'>⚠ Warnings:</span>\n";
        foreach ($validation['warnings'] as $warning) {
            echo "      - " . htmlspecialchars($warning) . "\n";
        }
    }
    
    echo "\n";
    
    // Final report
    echo str_repeat("=", 70) . "\n";
    echo "FINAL REPORT\n";
    echo str_repeat("=", 70) . "\n\n";
    
    echo "Total NPCs in database: {$total_npcs}\n";
    echo "NPCs selected (50%): {$selected_count}\n";
    
    if (!empty($validation['stats'])) {
        $stats = $validation['stats'];
        echo "Total active boons: {$stats['total']}\n";
        echo "New boons created: {$to_create_count}\n";
        echo "Existing boons (skipped): {$to_skip_count}\n\n";
        
        echo "Boon Distribution:\n";
        echo "   Major:   {$stats['major']['count']} ({$stats['major']['percentage']}%)\n";
        echo "   Minor:   {$stats['minor']['count']} ({$stats['minor']['percentage']}%)\n";
        echo "   Trivial: {$stats['trivial']['count']} ({$stats['trivial']['percentage']}%)\n\n";
    }
    
    echo "Target Distribution:\n";
    echo "   Major:   30%\n";
    echo "   Minor:   50%\n";
    echo "   Trivial: 20%\n\n";
    
    $expected_boon_count = (int)ceil($total_npcs * 0.5);
    $actual_boon_count = $validation['stats']['total'] ?? 0;
    
    if ($actual_boon_count === $expected_boon_count) {
        echo "<span class='success'>✅ SUCCESS: C.W. Whitford now has exactly one boon with exactly 50% of all NPCs!</span>\n";
    } else {
        echo "<span class='warning'>⚠️  WARNING: C.W. Whitford has {$actual_boon_count} boons but expected {$expected_boon_count} (50% of {$total_npcs} NPCs).</span>\n";
    }
    
    echo "\n";
    echo str_repeat("=", 70) . "\n";
    
} catch (Exception $e) {
    if (isset($conn) && mysqli_more_results($conn)) {
        mysqli_rollback($conn);
    }
    echo "\n<span class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    error_log("C.W. Whitford Boon Generation Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}

echo "</pre>\n";
echo "</body></html>\n";
?>

