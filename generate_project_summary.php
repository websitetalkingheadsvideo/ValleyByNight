<?php
/**
 * Valley by Night - Comprehensive Project Summary Generator
 * 
 * Generates an HTML summary document showcasing:
 * - Game content richness (characters, locations, items)
 * - Technical achievements (agents, systems)
 * - Current status and roadmap
 * - Historical context
 * 
 * Target audience: Storytellers/GMs familiar with Laws of the Night Revised
 */

require_once __DIR__ . '/includes/connect.php';
$conn = connect_db();

// Get database statistics
$stats = [];

// Character counts
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM characters WHERE pc = 0");
$stats['npcs'] = mysqli_fetch_assoc($result)['count'];
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM characters WHERE pc = 1");
$stats['pcs'] = mysqli_fetch_assoc($result)['count'];
$stats['total_characters'] = $stats['npcs'] + $stats['pcs'];

// Locations
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM locations");
$stats['locations'] = mysqli_fetch_assoc($result)['count'];

// Items
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
$stats['items'] = mysqli_fetch_assoc($result)['count'];

// Clan distribution
$result = mysqli_query($conn, "SELECT clan, COUNT(*) as count FROM characters WHERE clan IS NOT NULL AND clan != '' AND clan != 'N/A' GROUP BY clan ORDER BY count DESC");
$stats['clans'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['clans'][$row['clan']] = $row['count'];
}

// Get example character (Cordelia Fairchild - well-developed Harpy)
$result = mysqli_query($conn, "SELECT * FROM characters WHERE character_name = 'Cordelia Fairchild' LIMIT 1");
$example_character = mysqli_fetch_assoc($result);

// Generate HTML
$html = generateHTML($stats, $example_character);

// Output to file
file_put_contents(__DIR__ . '/PROJECT_SUMMARY.html', $html);
echo "Project summary generated: PROJECT_SUMMARY.html\n";

function generateHTML($stats, $example_char) {
    $date = date('F j, Y');
    $clan_list = implode(', ', array_keys($stats['clans']));
    $clan_count = count($stats['clans']);
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valley by Night - Project Summary</title>
    <style>
        /* Valley by Night Gothic Theme */
        :root {
            --bg-dark: #0d0606;
            --bg-darker: #1a0f0f;
            --blood-red: #8B0000;
            --muted-gold: #d4b06d;
            --text-light: #f5e6d3;
            --text-mid: #d4b06d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Source Serif Pro', 'Times New Roman', serif;
            background: linear-gradient(135deg, #0d0606 0%, #1a0f0f 50%, #0d0606 100%);
            background-attachment: fixed;
            color: var(--text-mid);
            line-height: 1.8;
            padding: 2rem 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(26, 15, 15, 0.8);
            border: 3px solid var(--muted-gold);
            border-radius: 8px;
            padding: 3rem;
            box-shadow: 0 0 30px rgba(139, 0, 0, 0.5);
        }
        
        h1 {
            font-family: 'IM Fell English', serif;
            color: var(--muted-gold);
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            border-bottom: 2px solid var(--blood-red);
            padding-bottom: 1rem;
        }
        
        h2 {
            font-family: 'IM Fell English', serif;
            color: var(--muted-gold);
            font-size: 2rem;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(139, 0, 0, 0.5);
            padding-bottom: 0.5rem;
        }
        
        h3 {
            font-family: 'Libre Baskerville', serif;
            color: var(--text-light);
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            font-style: italic;
            color: var(--text-light);
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .meta {
            background: rgba(139, 0, 0, 0.2);
            border-left: 4px solid var(--blood-red);
            padding: 1rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: radial-gradient(circle at center, rgba(139, 0, 0, 0.3) 0%, rgba(26, 15, 15, 0.6) 100%);
            border: 2px solid var(--muted-gold);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--muted-gold);
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }
        
        .clan-list {
            columns: 3;
            column-gap: 2rem;
            margin: 1rem 0;
        }
        
        .clan-item {
            break-inside: avoid;
            margin-bottom: 0.5rem;
        }
        
        .example-character {
            background: rgba(139, 0, 0, 0.15);
            border: 2px solid var(--blood-red);
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .character-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .character-name {
            font-family: 'IM Fell English', serif;
            font-size: 2rem;
            color: var(--muted-gold);
        }
        
        .character-clan {
            font-size: 1.2rem;
            color: var(--text-light);
            font-style: italic;
        }
        
        .character-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .detail-section {
            background: rgba(26, 15, 15, 0.6);
            padding: 1rem;
            border-radius: 4px;
        }
        
        .detail-section h4 {
            color: var(--muted-gold);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .agent-list {
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .agent-item {
            background: rgba(139, 0, 0, 0.1);
            border-left: 4px solid var(--blood-red);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .agent-name {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.3rem;
            color: var(--muted-gold);
            margin-bottom: 0.5rem;
        }
        
        .agent-description {
            color: var(--text-light);
            margin-top: 0.5rem;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .status-section {
            background: rgba(139, 0, 0, 0.15);
            border: 2px solid var(--muted-gold);
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .status-section h3 {
            color: var(--muted-gold);
            margin-top: 0;
        }
        
        .status-list {
            list-style: none;
            margin-top: 1rem;
        }
        
        .status-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(139, 0, 0, 0.3);
        }
        
        .status-list li:last-child {
            border-bottom: none;
        }
        
        .link {
            color: var(--muted-gold);
            text-decoration: underline;
        }
        
        .link:hover {
            color: var(--text-light);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .character-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Valley by Night</h1>
        <p class="subtitle">A Faithful Digital Adaptation of Laws of the Night Revised</p>
        
        <div class="meta">
            <strong>Generated:</strong> {$date}<br>
            <strong>Project Version:</strong> 0.9.15<br>
            <strong>Setting:</strong> Phoenix, Arizona (1994)<br>
            <strong>System:</strong> Mind's Eye Theatre - Laws of the Night Revised
        </div>
        
        <h2>Project Overview</h2>
        <p>
            Valley by Night (VbN) is a comprehensive web-based system for running and managing a Laws of the Night Revised 
            chronicle. Built as a faithful digital adaptation of the Mind's Eye Theatre system, VbN provides storytellers 
            and players with tools to create characters, manage the social and political landscape, track boons and status, 
            and access the complete rules system through AI-powered agents.
        </p>
        <p>
            The system is built on PHP, MySQL, HTML, CSS, and JavaScript, maintaining compatibility with the original 
            tabletop rules while adding digital conveniences like automated rulebook search, character relationship tracking, 
            and interactive location mapping.
        </p>
        
        <h2>Game Content Richness</h2>
        
        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-number">{$stats['total_characters']}</span>
                <span class="stat-label">Total Characters</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{$stats['npcs']}</span>
                <span class="stat-label">NPCs</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{$stats['pcs']}</span>
                <span class="stat-label">PCs</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{$stats['locations']}</span>
                <span class="stat-label">Locations</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{$stats['items']}</span>
                <span class="stat-label">Items</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{$clan_count}</span>
                <span class="stat-label">Clans Represented</span>
            </div>
        </div>
        
        <h3>Character Development</h3>
        <p>
            The database contains {$stats['total_characters']} fully-developed characters, with detailed biographies, 
            complete trait systems, disciplines, backgrounds, and relationships. Each character follows the Laws of the 
            Night Revised rules system, with proper generation limits, clan-specific disciplines, and morality paths.
        </p>
        
        <p><strong>Clan Distribution:</strong></p>
        <div class="clan-list">
HTML;
    
    foreach ($stats['clans'] as $clan => $count) {
        $html .= "            <div class=\"clan-item\"><strong>{$clan}:</strong> {$count}</div>\n";
    }
    
    $html .= <<<HTML
        </div>
        
        <h3>Example Character: Cordelia Fairchild</h3>
        <div class="example-character">
            <div class="character-header">
                <div>
                    <div class="character-name">Cordelia Fairchild</div>
                    <div class="character-clan">Toreador • 8th Generation • Harpy of Phoenix</div>
                </div>
            </div>
HTML;
    
    if ($example_char) {
        $html .= "<p><strong>Concept:</strong> " . htmlspecialchars($example_char['concept'] ?? 'Elegant manipulator and long-standing Harpy of Phoenix') . "</p>\n";
        $html .= "<p><strong>Biography:</strong> " . htmlspecialchars(substr($example_char['biography'] ?? '', 0, 300)) . "...</p>\n";
        
        // Get character details from JSON if available
        $json_path = __DIR__ . '/reference/Characters/Added to Database/Cordelia Fairchild.json';
        if (file_exists($json_path)) {
            $char_json = json_decode(file_get_contents($json_path), true);
            if ($char_json) {
                $html .= "<div class=\"character-details\">\n";
                
                if (isset($char_json['traits'])) {
                    $html .= "<div class=\"detail-section\">\n";
                    $html .= "<h4>Key Traits</h4>\n";
                    $traits = [];
                    if (isset($char_json['traits']['Physical'])) $traits = array_merge($traits, $char_json['traits']['Physical']);
                    if (isset($char_json['traits']['Social'])) $traits = array_merge($traits, $char_json['traits']['Social']);
                    if (isset($char_json['traits']['Mental'])) $traits = array_merge($traits, $char_json['traits']['Mental']);
                    $html .= "<p>" . implode(', ', array_slice($traits, 0, 8)) . "</p>\n";
                    $html .= "</div>\n";
                }
                
                if (isset($char_json['disciplines'])) {
                    $html .= "<div class=\"detail-section\">\n";
                    $html .= "<h4>Disciplines</h4>\n";
                    $disciplines = [];
                    foreach ($char_json['disciplines'] as $disc) {
                        $disciplines[] = $disc['name'] . " x" . $disc['level'];
                    }
                    $html .= "<p>" . implode(', ', $disciplines) . "</p>\n";
                    $html .= "</div>\n";
                }
                
                if (isset($char_json['backgrounds'])) {
                    $html .= "<div class=\"detail-section\">\n";
                    $html .= "<h4>Key Backgrounds</h4>\n";
                    $bgs = [];
                    foreach ($char_json['backgrounds'] as $bg => $level) {
                        if ($level > 0) $bgs[] = "{$bg} x{$level}";
                    }
                    $html .= "<p>" . implode(', ', array_slice($bgs, 0, 5)) . "</p>\n";
                    $html .= "</div>\n";
                }
                
                $html .= "</div>\n";
            }
        }
    }
    
    $html .= <<<HTML
        </div>
        
        <h3>Locations</h3>
        <p>
            The database contains {$stats['locations']} locations across Phoenix, including havens, Elysiums, businesses, 
            and points of interest. Each location includes detailed descriptions, security information, ownership details, 
            and story hooks. The system includes PC-accessible havens for each major clan, as well as key locations like 
            the Hawthorne Estate (Elysium), Violet Reliquary, and various clan-specific havens.
        </p>
        
        <h3>Items & Equipment</h3>
        <p>
            {$stats['items']} items are catalogued in the system, including weapons, armor, tools, consumables, artifacts, 
            and magical items. Each item includes combat statistics, requirements, rarity, and pricing information according 
            to Laws of the Night Revised rules.
        </p>
        
        <h2>Technical Achievements</h2>
        <p>
            Valley by Night features a sophisticated agent-based architecture that exposes (makes accessible) the game 
            content through intelligent, AI-powered systems. These agents handle everything from rulebook queries to 
            character relationship analysis.
        </p>
        
        <ul class="agent-list">
            <li class="agent-item">
                <div class="agent-name">🧛 Laws Agent</div>
                <p class="agent-description">
                    AI-powered rulebook search system with access to 31 PDF rulebooks (4,500+ pages) in a searchable 
                    database. Provides natural language queries about Laws of the Night Revised rules, with citations 
                    and context. Includes file-based knowledge system for Laws of the Night content with markdown indexing.
                    <br><strong>Links:</strong> <a href="agents/laws_agent/" class="link">Laws Agent Interface</a> | 
                    <a href="reference/Books/README.md" class="link">Rulebooks Database Documentation</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">👤 Character Agent</div>
                <p class="agent-description">
                    Monitors character JSON files for consistency, generates character briefs, suggests plot hooks, and 
                    performs continuity checks. Validates character data, checks sire relationships, generation consistency, 
                    and timeline conflicts. Generates daily and continuity reports.
                    <br><strong>Links:</strong> <a href="agents/character_agent/characters.php" class="link">Character Search</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">🤝 Boon Agent</div>
                <p class="agent-description">
                    Monitors and validates boons according to Laws of the Night Revised mechanics. Tracks favor-debt 
                    relationships, detects violations, integrates with Harpy systems, and generates boon relationship 
                    graphs. Can generate character-specific boon networks.
                    <br><strong>Links:</strong> <a href="admin/admin_boons.php" class="link">Boon Ledger</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">⚔️ Discipline Agent</div>
                <p class="agent-description">
                    Provides searchable interface for all vampire disciplines with levels, powers, and mechanics. Displays 
                    discipline information in a clean, organized format with full power descriptions.
                    <br><strong>Links:</strong> <a href="agents/discipline_agent/discipline_test.php" class="link">Discipline Agent</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">📚 Ability Agent</div>
                <p class="agent-description">
                    Searchable interface for character abilities with sorting and filtering. Displays ability categories, 
                    levels, and specializations according to Laws of the Night Revised rules.
                    <br><strong>Links:</strong> <a href="agents/ability_agent/abilities_display.php" class="link">Ability Agent</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">🛤️ Paths Agent</div>
                <p class="agent-description">
                    Provides interface for viewing morality paths (Humanity, Path of Enlightenment, etc.) with path ratings, 
                    powers, and descriptions. Displays character path assignments and progression.
                    <br><strong>Links:</strong> <a href="agents/paths_agent/paths_display.php" class="link">Paths Agent</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">🔮 Rituals Agent</div>
                <p class="agent-description">
                    Searchable interface for Thaumaturgy and Necromancy rituals with levels, requirements, and full 
                    descriptions. Tracks which characters know which rituals.
                    <br><strong>Links:</strong> <a href="rituals_view.php" class="link">Rituals View</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">🗺️ Map Agent</div>
                <p class="agent-description">
                    Interactive map of Phoenix showing all locations with zoom, pan, and location details. Displays 
                    location types, districts, and allows filtering by various criteria.
                    <br><strong>Links:</strong> <a href="phoenix_map.php" class="link">Phoenix Map</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">👥 Coterie Agent</div>
                <p class="agent-description">
                    Manages coterie (group) information including members, roles, strengths, gaps, story hooks, and 
                    internal tensions. Tracks coterie focus and provides relationship mapping.
                    <br><strong>Links:</strong> <a href="agents/coterie_agent/index.php" class="link">Coterie Agent</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">💼 Positions Agent</div>
                <p class="agent-description">
                    Tracks Camarilla positions (Primogen, Harpy, Sheriff, etc.) with current holders, assignment history, 
                    and position details. Provides queries for position lookups and character position history.
                    <br><strong>Links:</strong> <a href="admin/camarilla_positions.php" class="link">Camarilla Positions</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">📊 Influence Agent</div>
                <p class="agent-description">
                    Provides lookup interface for Influence types and levels with effects, contacts, and allies information 
                    according to Laws of the Night rules.
                    <br><strong>Links:</strong> <a href="agents/influence_agent/index.php" class="link">Influence Agent</a>
                </p>
            </li>
            
            <li class="agent-item">
                <div class="agent-name">🎨 Style Agent</div>
                <p class="agent-description">
                    MCP-based system for accessing the Valley by Night Art Bible, providing visual style guidelines, 
                    asset specifications, and design standards for character portraits, locations, and cinematic content.
                    <br><strong>Links:</strong> <a href="agents/style_agent/README.md" class="link">Style Agent Documentation</a>
                </p>
            </li>
        </ul>
        
        <h2>Current Status & Roadmap</h2>
        
        <div class="status-grid">
            <div class="status-section">
                <h3>✅ Completed Systems</h3>
                <ul class="status-list">
                    <li>Character creation and management system</li>
                    <li>Complete character database with full rules compliance</li>
                    <li>Location management and mapping system</li>
                    <li>Items and equipment database</li>
                    <li>Boon tracking and relationship system</li>
                    <li>Camarilla positions management</li>
                    <li>Rulebook database and search (31 PDFs, 4,500+ pages)</li>
                    <li>All 12 major agent systems operational</li>
                    <li>Phoenix map with interactive locations</li>
                    <li>Admin panel for content management</li>
                    <li>User authentication and session management</li>
                    <li>Gothic-themed UI matching Art Bible specifications</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3>🚧 In Progress</h3>
                <ul class="status-list">
                    <li>Character creation form completion (8 tabs, some incomplete)</li>
                    <li>Wraith character system (foundation complete, needs expansion)</li>
                    <li>Blood Bonds system (planning stage)</li>
                    <li>Additional character content (ongoing additions)</li>
                    <li>Location descriptions and scene content</li>
                    <li>Ritual and path content expansion</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3>📋 Planned Features</h3>
                <ul class="status-list">
                    <li>Complete character creation workflow</li>
                    <li>Player dashboard with character sheets</li>
                    <li>Scene management and tracking</li>
                    <li>Rumor system integration</li>
                    <li>Chronicle timeline and event tracking</li>
                    <li>Enhanced relationship mapping</li>
                    <li>Mobile-responsive improvements</li>
                    <li>Additional agent systems as needed</li>
                </ul>
            </div>
        </div>
        
        <h2>Historical Context</h2>
        <p>
            Valley by Night began as a character creation system for Laws of the Night Revised, evolving into a 
            comprehensive chronicle management platform. The project maintains strict adherence to the original 
            Mind's Eye Theatre rules while adding digital conveniences that enhance gameplay without changing 
            the core mechanics.
        </p>
        <p>
            The system has been developed with input from experienced LARP storytellers and players, ensuring 
            that digital tools complement rather than replace the tabletop experience. All agents and systems 
            are designed to support storytellers in running complex, character-driven chronicles while maintaining 
            the social and political dynamics that make Laws of the Night engaging.
        </p>
        <p>
            The project follows a version-controlled development process with comprehensive documentation, ensuring 
            that all changes maintain compatibility with Laws of the Night Revised rules and the established 
            Valley by Night setting.
        </p>
        
        <h2>Additional Resources</h2>
        <ul style="list-style: none; margin: 1.5rem 0;">
            <li style="margin: 0.5rem 0;">📖 <a href="VERSION.md" class="link">Version History</a> - Complete changelog and feature history</li>
            <li style="margin: 0.5rem 0;">📚 <a href="reference/Books/README.md" class="link">Rulebooks Database</a> - Complete list of available rulebooks</li>
            <li style="margin: 0.5rem 0;">🎨 <a href="agents/style_agent/README.md" class="link">Art Bible</a> - Visual style guidelines and asset specifications</li>
            <li style="margin: 0.5rem 0;">🔧 <a href="admin/admin_panel.php" class="link">Admin Panel</a> - Content management interface</li>
            <li style="margin: 0.5rem 0;">🗺️ <a href="phoenix_map.php" class="link">Phoenix Map</a> - Interactive location map</li>
        </ul>
        
        <div style="margin-top: 4rem; padding-top: 2rem; border-top: 2px solid var(--blood-red); text-align: center; color: var(--text-light); font-size: 0.9rem;">
            <p>Valley by Night v0.9.15 • Generated {$date}</p>
            <p style="margin-top: 0.5rem; font-style: italic;">A Faithful Digital Adaptation of Laws of the Night Revised</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    return $html;
}
