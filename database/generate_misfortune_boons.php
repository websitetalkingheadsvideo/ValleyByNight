<?php
/**
 * Generate Misfortune's Boons Script
 * 
 * Creates tailor-made boons for Misfortune with every NPC in the database.
 * Each boon description is customized based on the NPC's character, clan, role, and background.
 * 
 * Distribution: 5% Major, 25% Minor, 70% Trivial
 * All boons are registered with Harpy (Cordelia Fairchild or system).
 * 
 * Usage: Run via web browser
 * URL: database/generate_misfortune_boons.php
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
echo "<html><head><title>Misfortune Boons Generation</title>\n";
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

echo "<h1>💎 Misfortune's Boon Generation System</h1>\n";
echo "<pre>\n";

/**
 * Get Misfortune's character ID from database
 */
function get_misfortune_character_id($conn): ?int {
    $query = "SELECT id FROM characters WHERE character_name = 'Misfortune' AND player_name = 'NPC' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['id'] : null;
}

/**
 * Get all NPCs excluding Misfortune
 */
function get_all_npcs_excluding_misfortune($conn, int $misfortune_id): array {
    $query = "SELECT c.*
              FROM characters c
              WHERE c.player_name = 'NPC' 
              AND c.id != ?
              ORDER BY c.id";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $misfortune_id);
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
 * Get existing boons for Misfortune
 */
function get_existing_boons($conn, int $misfortune_id): array {
    $query = "SELECT debtor_id, boon_type, status 
              FROM boons 
              WHERE creditor_id = ? AND status = 'active'";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $misfortune_id);
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
 * Distribution: 5% Major, 25% Minor, 70% Trivial
 */
function assign_boon_tier(int $npc_id, string $seed = 'misfortune_boons_v1'): string {
    $hash = md5($npc_id . '_' . $seed);
    $value = hexdec(substr($hash, 0, 8)) % 100;
    
    if ($value < 5) {
        return 'major';
    } elseif ($value < 30) {
        return 'minor';
    } else {
        return 'trivial';
    }
}

/**
 * Generate character-specific boon description
 * Tailored based on NPC's clan, role, concept, and biography
 */
function generate_boon_description(array $npc, string $boon_tier, int $misfortune_id): string {
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
    $is_researcher = stripos($concept . ' ' . $biography, 'research') !== false ||
                     stripos($concept . ' ' . $biography, 'scholar') !== false;
    $is_merchant = stripos($concept . ' ' . $biography, 'merchant') !== false ||
                   stripos($concept . ' ' . $biography, 'business') !== false ||
                   stripos($concept . ' ' . $biography, 'trader') !== false;
    $is_information_broker = stripos($concept . ' ' . $biography, 'information') !== false ||
                             stripos($concept . ' ' . $biography, 'gossip') !== false ||
                             stripos($concept . ' ' . $biography, 'intelligence') !== false;
    
    // Generate description based on tier and character traits
    $descriptions = [];
    
    if ($boon_tier === 'trivial') {
        // 70% - Small favors, minor social gestures
        $templates = [
            "A small favor owed from a brief encounter at Elysium. Misfortune's riddles amused {$npc_name}, but the joke came with a price.",
            "A minor social debt from when Misfortune provided a timely distraction during an awkward moment at court.",
            "A trivial favor from a casual conversation where Misfortune shared useful information about the local social landscape.",
            "A small boon owed for Misfortune's assistance in navigating a delicate social situation that could have caused embarrassment.",
            "A minor debt from when Misfortune's theatrical performance provided cover for {$npc_name}'s discreet arrival or departure.",
            "A trivial favor from Misfortune helping {$npc_name} avoid a social misstep through a well-timed jest or redirect.",
            "A small boon from when Misfortune's knowledge of Harpy networks prevented {$npc_name} from inadvertently offending the wrong Kindred.",
            "A minor debt from Misfortune's assistance in translating the coded language of court politics during a confusing exchange."
        ];
        
        // Character-specific variations
        if ($is_researcher) {
            $templates[] = "A trivial favor from when Misfortune helped {$npc_name} access a rare book or research material through his network of contacts.";
            $templates[] = "A small boon from Misfortune providing {$npc_name} with a lead or reference that saved hours of research time.";
        }
        
        if ($is_merchant) {
            $templates[] = "A minor favor from Misfortune helping {$npc_name} avoid a bad business deal through a cryptic warning disguised as a joke.";
            $templates[] = "A trivial debt from when Misfortune's social connections introduced {$npc_name} to a valuable client or supplier.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A small boon from Misfortune sharing a piece of gossip or social intelligence that proved more valuable than {$npc_name} initially realized.";
            $templates[] = "A minor favor from when Misfortune helped {$npc_name} verify or contextualize a piece of information through his extensive network.";
        }
        
        if ($clan === 'Malkavian') {
            $templates[] = "A trivial boon from when Misfortune, in his role as Primogen, provided guidance to {$npc_name} on navigating clan politics and madness.";
            $templates[] = "A small favor from Misfortune helping {$npc_name} understand or communicate something that their shared Malkavian perspective made clearer.";
        } elseif ($clan === 'Tremere') {
            $templates[] = "A trivial favor from when Misfortune's social connections helped {$npc_name} obtain a rare thaumaturgical component or access to a restricted chantry resource.";
            $templates[] = "A small boon from Misfortune providing {$npc_name} with information about a rival thaumaturge or political situation within the Pyramid.";
        } elseif ($clan === 'Nosferatu') {
            $templates[] = "A trivial boon from when Misfortune's information network complemented {$npc_name}'s own intelligence gathering, creating mutual benefit from shared knowledge.";
            $templates[] = "A small favor from Misfortune helping {$npc_name} navigate an aboveground social situation where their appearance would have caused problems.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A trivial favor from when Misfortune's theatrical flair provided {$npc_name} with inspiration or a dramatic moment that enhanced their reputation.";
            $templates[] = "A small boon from Misfortune introducing {$npc_name} to an important patron or connection in the arts or social scene.";
        } elseif ($clan === 'Ventrue') {
            $templates[] = "A trivial boon from when Misfortune's understanding of court politics helped {$npc_name} avoid a costly social misstep with another Ventrue or elder.";
            $templates[] = "A small favor from Misfortune providing {$npc_name} with intelligence about a business or political opportunity through his network.";
        } elseif ($clan === 'Gangrel') {
            $templates[] = "A trivial favor from when Misfortune helped {$npc_name} navigate Camarilla politics during a rare visit to Elysium, avoiding unwanted attention.";
            $templates[] = "A small boon from Misfortune providing {$npc_name} with information about threats or opportunities in the desert that they couldn't learn from their usual sources.";
        } elseif ($clan === 'Brujah') {
            $templates[] = "A trivial boon from when Misfortune's quick thinking prevented {$npc_name} from causing a scene that would have led to censure from the Sheriff or Harpies.";
            $templates[] = "A small favor from Misfortune helping {$npc_name} understand the political implications of a situation before they acted rashly.";
        } elseif ($clan === 'Followers of Set' || $clan === 'Setite') {
            $templates[] = "A trivial favor from when Misfortune's social maneuvering helped {$npc_name} maintain their cover or avoid suspicion during a delicate operation.";
            $templates[] = "A small boon from Misfortune providing {$npc_name} with information about a potential convert or target through his extensive contacts.";
        } elseif ($clan === 'Giovanni') {
            $templates[] = "A trivial boon from when Misfortune's knowledge of financial matters and Kindred politics helped {$npc_name} complete a sensitive transaction.";
            $templates[] = "A small favor from Misfortune facilitating a meeting or introduction that {$npc_name} needed for their family's business interests.";
        }
        
    } elseif ($boon_tier === 'minor') {
        // 25% - Moderate favors, information, protection
        $templates = [
            "A moderate favor from when Misfortune's intervention prevented {$npc_name} from making a significant social or political error.",
            "A minor boon from Misfortune providing {$npc_name} with valuable intelligence about a rival or threat, delivered through his Harpy connections.",
            "A service rendered when Misfortune used his position as Primogen to shield {$npc_name} from unwanted attention or suspicion.",
            "A moderate debt from when Misfortune's knowledge of Phoenix's social web helped {$npc_name} navigate a complex political situation.",
            "A minor favor from Misfortune facilitating an important meeting or introduction that {$npc_name} needed but couldn't arrange alone.",
            "A service from when Misfortune's theatrical distractions provided {$npc_name} with the cover needed for a sensitive operation.",
            "A moderate boon from Misfortune helping {$npc_name} resolve a dispute or conflict through his understanding of court dynamics and debts.",
            "A minor debt from when Misfortune's information network alerted {$npc_name} to a threat they would otherwise have missed."
        ];
        
        // Character-specific variations
        if ($is_researcher) {
            $templates[] = "A moderate favor from Misfortune providing {$npc_name} with access to restricted knowledge or protected research through his century of connections.";
            $templates[] = "A minor boon from when Misfortune helped {$npc_name} interpret or contextualize a discovery by connecting it to older Kindred knowledge.";
        }
        
        if ($is_merchant) {
            $templates[] = "A moderate service from Misfortune helping {$npc_name} negotiate or close an important business deal through his understanding of Kindred politics and leverage.";
            $templates[] = "A minor favor from when Misfortune's social connections prevented {$npc_name} from making a costly business mistake with another Kindred.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A moderate boon from Misfortune sharing sensitive information from his collection of secrets that {$npc_name} needed but couldn't obtain independently.";
            $templates[] = "A minor service from when Misfortune helped {$npc_name} verify a critical piece of intelligence that affected their standing or safety.";
        }
        
        if ($is_primogen || $is_elder) {
            $templates[] = "A moderate favor from when Misfortune, leveraging his Primogen position and Harpy connections, provided {$npc_name} with crucial political intelligence.";
            $templates[] = "A minor boon from Misfortune helping {$npc_name} navigate a delicate inter-clan situation where his knowledge of debts and favors proved invaluable.";
        }
        
        // Clan-specific minor boons
        if ($clan === 'Tremere') {
            $templates[] = "A moderate favor from when Misfortune helped {$npc_name} navigate chantry politics or provided intelligence about a thaumaturgical threat or opportunity.";
            $templates[] = "A minor boon from Misfortune facilitating {$npc_name}'s access to a protected library, ritual site, or thaumaturgical resource through his political connections.";
        } elseif ($clan === 'Nosferatu') {
            $templates[] = "A moderate service from when Misfortune's aboveground network provided {$npc_name} with intelligence or resources they couldn't obtain through their usual channels.";
            $templates[] = "A minor favor from Misfortune helping {$npc_name} sell or trade sensitive information through his connections, ensuring they got fair value.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A moderate boon from when Misfortune orchestrated a social event or introduction that significantly enhanced {$npc_name}'s reputation or standing in the court.";
            $templates[] = "A minor favor from Misfortune helping {$npc_name} recover from a social disaster or scandal through his understanding of Harpy networks and public perception.";
        } elseif ($clan === 'Ventrue') {
            $templates[] = "A moderate service from when Misfortune's political intelligence helped {$npc_name} outmaneuver a business or political rival within the Camarilla hierarchy.";
            $templates[] = "A minor boon from Misfortune providing {$npc_name} with leverage or information that strengthened their position in a delicate negotiation or power play.";
        } elseif ($clan === 'Gangrel') {
            $templates[] = "A moderate favor from when Misfortune used his connections to help {$npc_name} deal with a threat or opportunity that required Camarilla resources or knowledge.";
            $templates[] = "A minor service from Misfortune helping {$npc_name} maintain their independence while still accessing the protection and resources of organized Kindred society.";
        } elseif ($clan === 'Brujah') {
            $templates[] = "A moderate boon from when Misfortune prevented {$npc_name} from making a political mistake that would have destroyed their standing or led to severe punishment.";
            $templates[] = "A minor favor from Misfortune helping {$npc_name} channel their passion and activism in ways that achieved their goals without violating Tradition or decorum.";
        } elseif ($clan === 'Followers of Set' || $clan === 'Setite') {
            $templates[] = "A moderate service from when Misfortune's social connections helped {$npc_name} establish or maintain their cover identity or temple operations without attracting unwanted attention.";
            $templates[] = "A minor boon from Misfortune providing {$npc_name} with intelligence about potential converts, rivals, or threats that affected their corruption work.";
        } elseif ($clan === 'Giovanni') {
            $templates[] = "A moderate favor from when Misfortune facilitated a complex business arrangement or helped {$npc_name} avoid legal or supernatural complications in their family's operations.";
            $templates[] = "A minor service from Misfortune providing {$npc_name} with intelligence about a rival family, potential acquisition target, or threat to their financial interests.";
        }
        
    } else { // major
        // 5% - Significant favors, life-saving, major political moves
        $templates = [
            "A significant boon from when Misfortune used his extensive network of debts and favors to protect {$npc_name} from a serious threat, possibly saving their unlife or position.",
            "A major favor from when Misfortune's intervention, orchestrated through his Harpy connections and Primogen authority, prevented {$npc_name} from facing severe consequences for a grave error.",
            "A substantial debt from when Misfortune leveraged his century of collected boons and social intelligence to resolve a crisis that threatened {$npc_name}'s standing or existence.",
            "A major boon from Misfortune providing {$npc_name} with information or assistance so valuable that it fundamentally changed their position or saved them from ruin.",
            "A significant service from when Misfortune, drawing on his deep knowledge of Phoenix's Kindred politics and his role as Harpy network facilitator, orchestrated a complex solution to a problem {$npc_name} couldn't solve alone.",
            "A major favor from Misfortune using his position as Primogen and his collection of secrets to shield {$npc_name} from exposure or retaliation that would have destroyed them.",
            "A substantial boon from when Misfortune's theatrical manipulations and social web prevented {$npc_name} from falling victim to a plot that would have ended their unlife or erased their influence."
        ];
        
        // Character-specific major boons
        if ($is_primogen) {
            $templates[] = "A major political favor from when Misfortune, leveraging his Primogen position and Harpy network, helped {$npc_name} secure or maintain their own position through a complex web of social maneuvering.";
            $templates[] = "A significant debt from Misfortune providing {$npc_name} with critical political intelligence or protection that preserved their standing during a crisis in the court.";
        }
        
        if ($is_researcher) {
            $templates[] = "A major boon from Misfortune providing {$npc_name} with access to forbidden knowledge or protected research that advanced their work in ways they couldn't achieve alone, possibly saving years of effort.";
        }
        
        if ($is_information_broker) {
            $templates[] = "A substantial favor from when Misfortune shared critical intelligence from his century of collected secrets that {$npc_name} needed to prevent disaster or seize a major opportunity.";
        }
        
        // Clan-specific major boons
        if ($clan === 'Tremere') {
            $templates[] = "A major boon from when Misfortune used his political connections and Harpy network to protect {$npc_name} from exposure or retaliation after a thaumaturgical incident that could have destroyed their standing in the Pyramid.";
            $templates[] = "A significant favor from Misfortune providing {$npc_name} with access to forbidden knowledge or a protected ritual that fundamentally advanced their thaumaturgical research, possibly saving them decades of work.";
        } elseif ($clan === 'Nosferatu') {
            $templates[] = "A major service from when Misfortune orchestrated a complex information exchange that saved {$npc_name} from destruction, using his aboveground network and Harpy connections to provide intelligence they couldn't obtain alone.";
            $templates[] = "A substantial boon from Misfortune using his social connections and political leverage to protect {$npc_name}'s haven, information network, or loved ones from a threat that would have erased them completely.";
        } elseif ($clan === 'Toreador') {
            $templates[] = "A major favor from when Misfortune's intervention, orchestrated through his Harpy network and Primogen authority, saved {$npc_name} from social ruin or destruction after a catastrophic scandal or mistake.";
            $templates[] = "A significant boon from Misfortune providing {$npc_name} with access to a patron, opportunity, or resource that transformed their standing in Kindred society and the arts, changing their entire unlife trajectory.";
        } elseif ($clan === 'Ventrue') {
            $templates[] = "A major service from when Misfortune leveraged his century of collected debts and Harpy connections to help {$npc_name} survive a power struggle, corporate takeover, or political crisis that threatened their position or existence.";
            $templates[] = "A substantial favor from Misfortune orchestrating a complex political solution that saved {$npc_name} from ruin, using his knowledge of Kindred politics and influence to resolve a situation that would have destroyed them.";
        } elseif ($clan === 'Gangrel') {
            $templates[] = "A major boon from when Misfortune used his Camarilla connections and information network to protect {$npc_name} from a threat or help them survive a crisis that their desert isolation couldn't solve alone.";
            $templates[] = "A significant service from Misfortune providing {$npc_name} with critical intelligence or assistance that saved their unlife or protected something they valued more than survival itself.";
        } elseif ($clan === 'Brujah') {
            $templates[] = "A major favor from when Misfortune prevented {$npc_name} from making a fatal political error that would have led to their destruction, using his understanding of court politics to guide them away from disaster.";
            $templates[] = "A substantial boon from Misfortune orchestrating protection or intervention that saved {$npc_name} from the consequences of their own passion or activism when it threatened to destroy them or their cause.";
        } elseif ($clan === 'Followers of Set' || $clan === 'Setite') {
            $templates[] = "A major service from when Misfortune used his social connections and Harpy network to help {$npc_name} escape exposure or destruction, protecting their temple operations or personal safety when discovery would have meant death.";
            $templates[] = "A significant favor from Misfortune providing {$npc_name} with intelligence, protection, or resources that saved them from a threat or advanced their corruption work in ways that fundamentally changed their standing within the clan.";
        } elseif ($clan === 'Giovanni') {
            $templates[] = "A major boon from when Misfortune helped {$npc_name} resolve a family crisis, business disaster, or supernatural threat that would have destroyed their position in the Giovanni hierarchy or exposed their necromantic activities.";
            $templates[] = "A substantial service from Misfortune orchestrating a solution to a problem that threatened {$npc_name}'s family interests, using his knowledge of Kindred politics and debts to protect them from ruin or exposure.";
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
function create_boon($conn, int $creditor_id, int $debtor_id, string $boon_type, string $description, ?string $harpy_name, int $created_by_user_id): ?int {
    global $last_boon_error;
    $last_boon_error = null;
    
    $harpy_notes = "Auto-registered by boon generation system. Generated " . date('Y-m-d') . ".";
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
    // Note: For NULL values in string params, pass null; for integer NULL, pass null
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

// Main execution
try {
    echo "Starting Misfortune Boon Generation...\n";
    echo str_repeat("=", 70) . "\n\n";
    
    // Step 1: Get Misfortune's ID
    echo "Step 1: Locating Misfortune...\n";
    $misfortune_id = get_misfortune_character_id($conn);
    
    if (!$misfortune_id) {
        throw new Exception("ERROR: Misfortune not found in database. Please ensure Misfortune exists as an NPC.");
    }
    
    echo "   ✓ Found Misfortune (ID: {$misfortune_id})\n\n";
    
    // Step 2: Get all NPCs
    echo "Step 2: Querying all NPCs...\n";
    $npcs = get_all_npcs_excluding_misfortune($conn, $misfortune_id);
    $total_npcs = count($npcs);
    echo "   ✓ Found {$total_npcs} NPCs (excluding Misfortune)\n\n";
    
    if ($total_npcs === 0) {
        throw new Exception("ERROR: No NPCs found in database. Cannot generate boons.");
    }
    
    // Step 3: Get existing boons
    echo "Step 3: Checking existing boons...\n";
    $existing_boons = get_existing_boons($conn, $misfortune_id);
    $existing_count = count($existing_boons);
    echo "   ✓ Found {$existing_count} existing active boons\n\n";
    
    // Step 4: Get Harpy
    echo "Step 4: Locating Harpy (Cordelia Fairchild)...\n";
    $harpy_id = get_harpy_character_id($conn);
    $harpy_name = $harpy_id ? 'Cordelia Fairchild' : 'System';
    echo "   ✓ Harpy: {$harpy_name}\n\n";
    
    // Step 5: Assign boon tiers and generate descriptions
    echo "Step 5: Assigning boon tiers and generating descriptions...\n";
    
    $tier_counts = ['major' => 0, 'minor' => 0, 'trivial' => 0];
    $boons_to_create = [];
    $boons_to_skip = [];
    
    foreach ($npcs as $npc) {
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
        $description = generate_boon_description($npc, $boon_tier, $misfortune_id);
        
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
    
    // Step 6: Create boons
    if ($to_create_count > 0) {
        echo "Step 6: Creating boons in database...\n";
        
        // Get a valid system user ID for created_by field (must exist in users table)
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
            
            // Test the exact INSERT that will be used
            $test_boon_id = create_boon(
                $conn, 
                $misfortune_id, 
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
            
            $boon_id = create_boon(
                $conn, 
                $misfortune_id, 
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
                
                // Try multiple ways to get the error
                $db_error = mysqli_error($conn);
                global $last_boon_error;
                $stmt_error = $last_boon_error ?? '';
                
                $display_error = $stmt_error ?: $db_error ?: 'Unknown database error - check error logs';
                
                echo " ✗ FAILED - " . htmlspecialchars($display_error) . "\n";
                
                // Show detailed error for first failure only
                if (count($errors) === 1) {
                    echo "\n      <span class='error'>=== FIRST FAILURE DEBUG INFO ===</span>\n";
                    echo "      Creditor ID (Misfortune): {$misfortune_id}\n";
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
        echo "Step 6: No new boons to create (all NPCs already have boons)\n\n";
    }
    
    // Step 7: Validation and reporting
    echo "Step 7: Validating results...\n";
    
    $final_boons = get_existing_boons($conn, $misfortune_id);
    $final_count = count($final_boons);
    
    // Calculate distribution
    $final_tiers = ['major' => 0, 'minor' => 0, 'trivial' => 0];
    foreach ($final_boons as $boon) {
        $final_tiers[$boon['boon_type']] = ($final_tiers[$boon['boon_type']] ?? 0) + 1;
    }
    
    $major_pct = $final_count > 0 ? round(($final_tiers['major'] / $final_count) * 100, 1) : 0;
    $minor_pct = $final_count > 0 ? round(($final_tiers['minor'] / $final_count) * 100, 1) : 0;
    $trivial_pct = $final_count > 0 ? round(($final_tiers['trivial'] / $final_count) * 100, 1) : 0;
    
    echo "   ✓ Validation complete\n\n";
    
    // Final report
    echo str_repeat("=", 70) . "\n";
    echo "FINAL REPORT\n";
    echo str_repeat("=", 70) . "\n\n";
    
    echo "Total NPCs in database: {$total_npcs}\n";
    echo "Total active boons: {$final_count}\n";
    echo "New boons created: {$to_create_count}\n";
    echo "Existing boons (skipped): {$to_skip_count}\n\n";
    
    echo "Boon Distribution:\n";
    echo "   Major:   {$final_tiers['major']} ({$major_pct}%)\n";
    echo "   Minor:   {$final_tiers['minor']} ({$minor_pct}%)\n";
    echo "   Trivial: {$final_tiers['trivial']} ({$trivial_pct}%)\n\n";
    
    echo "Target Distribution:\n";
    echo "   Major:   5%\n";
    echo "   Minor:   25%\n";
    echo "   Trivial: 70%\n\n";
    
    if ($final_count === $total_npcs) {
        echo "<span class='success'>✅ SUCCESS: Misfortune now has exactly one boon with every NPC!</span>\n";
    } else {
        echo "<span class='warning'>⚠️  WARNING: Misfortune has {$final_count} boons but {$total_npcs} NPCs exist.</span>\n";
    }
    
    echo "\n";
    echo str_repeat("=", 70) . "\n";
    
} catch (Exception $e) {
    if (isset($conn) && mysqli_more_results($conn)) {
        mysqli_rollback($conn);
    }
    echo "\n<span class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    error_log("Misfortune Boon Generation Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}

echo "</pre>\n";
echo "</body></html>\n";
?>

