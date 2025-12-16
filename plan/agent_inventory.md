# VbN Agent System Inventory

**Generated:** 2025-01-30  
**Scope:** Read-only structural documentation of agent system  
**Target Roots:** `G:\VbN\agents\` and any `*_agent` folders at repo root

---

## Summary

This inventory documents all agent folders found in the VbN project, their structure, interfaces, data flows, and dependencies. This is an observation report only—no modifications were made to the codebase.

---

## Agent Inventory

### boon_agent
- **Location:** `G:\VbN\agents\boon_agent\`
- **Purpose (observed):** Monitors and validates boons according to Laws of the Night Revised mechanics. Tracks favor-debt, detects violations, integrates with Harpy systems, and analyzes the social economy of prestation.
- **Primary interfaces:** 
  - `viewer.php` - Main admin interface (redirects from `index.php`)
  - `api_get_boon_report.php` - API endpoint for serving JSON report files
  - `reports/daily/index.php` - Daily report viewer
  - `reports/validation/index.php` - Validation report viewer
  - `reports/character/index.php` - Character-specific report viewer
  - `config/index.php` - Configuration interface
- **Reads:** 
  - Database: `boons` table (via `includes/connect.php`)
  - Database: `characters` table (for character lookups)
  - Configuration: `config/settings.json`
- **Writes/Generates:** 
  - JSON report files in `reports/daily/`, `reports/validation/`, `reports/character/`
  - Log files in `logs/` directory
- **Data stores:** 
  - Database tables: `boons`, `characters`
  - File system: `reports/` subdirectories (JSON files)
  - File system: `logs/` directory
- **AuthZ/AuthN (observed):** 
  - Session-based authentication required
  - Role check: `$_SESSION['role'] !== 'admin'` enforced on all entry points
  - All API endpoints and viewers require admin role
- **Dependencies (observed):** 
  - `includes/connect.php` - Database connection
  - `includes/version.php` - Version constants
  - `includes/header.php` - Page header/template
  - `includes/footer.php` - Page footer
  - `css/admin-agents.css` - Styling
  - Core classes: `BoonAgent.php`, `BoonAnalyzer.php`, `BoonValidator.php`, `ReportGenerator.php` in `src/`
- **Risks / Notes:** 
  - Well-structured with clear separation of concerns
  - Comprehensive schema documentation in `SCHEMA_UPDATE_NOTES.md`
  - All authentication checks appear consistent

---

### character_agent
- **Location:** `G:\VbN\agents\character_agent\`
- **Purpose (observed):** Monitors character data to keep lore consistent, generate briefs, and suggest plot hooks. Provides character search and analytical query capabilities.
- **Primary interfaces:** 
  - `characters.php` - Main search interface with analytical query support
  - `api_get_report.php` - API endpoint for serving JSON report files
  - `api_get_config.php` - API endpoint for configuration
  - `view_reports.php` - Report viewing interface
  - `generate_reports.php` - Report generation interface
  - `search.php` - Alternative search interface (observed but not fully examined)
  - `server.php` - MCP server (STDIN/STDOUT) for character operations
- **Reads:** 
  - Database: `characters` table
  - Database: `character_traits`, `character_abilities`, `character_disciplines`, `character_backgrounds`, `character_relationships` tables
  - Configuration: `config/settings.json`
  - File system: `data/` directory (if used for JSON character files)
- **Writes/Generates:** 
  - JSON report files in `reports/daily/`, `reports/continuity/`
  - Log files in `logs/` directory
  - Character data updates via MCP server
- **Data stores:** 
  - Database tables: `characters`, `character_traits`, `character_abilities`, `character_disciplines`, `character_backgrounds`, `character_relationships`
  - File system: `reports/` subdirectories (JSON files)
  - File system: `data/` directory (structure unclear)
  - File system: `logs/` directory
  - File system: `tmp/` directory
- **AuthZ/AuthN (observed):** 
  - Session-based authentication required
  - Role check: `$_SESSION['role'] !== 'admin'` enforced on all web interfaces
  - MCP server (`server.php`) uses separate `db.php` with hardcoded credentials (security concern)
- **Dependencies (observed):** 
  - `includes/connect.php` - Database connection (web interfaces)
  - `db.php` - Direct database connection (MCP server, contains hardcoded credentials)
  - `includes/version.php` - Version constants
  - `includes/header.php` - Page header/template
  - `includes/footer.php` - Page footer
  - `css/admin-agents.css` - Styling
  - MCP configuration: `mcp.json` defines MCP server tools
- **Risks / Notes:** 
  - MCP server has hardcoded database credentials in `db.php` (security risk)
  - Complex analytical query system for character searches
  - Supports both web interface and MCP server protocols
  - Multiple report generation paths

---

### laws_agent
- **Location:** `G:\VbN\agents\laws_agent\`
- **Purpose (observed):** AI-powered rulebook search and Q&A system. Provides answers about VTM/MET rules, disciplines, clans, mechanics, and lore using database-backed rulebook search and file-based Laws of the Night content.
- **Primary interfaces:** 
  - `index.php` - Main chat interface for asking questions
  - `api.php` - API endpoint for question answering and rulebook search
  - `markdown_loader.php` - Loads Laws of the Night markdown files
- **Reads:** 
  - Database: `rulebooks` table
  - Database: `rulebook_pages` table (full-text search)
  - File system: `knowledge-base/` directory (markdown files: traditions, masquerade, domain, progeny, accounting, hospitality, destruction, enforcement, status_boons, character_creation_traits)
  - File system: `agents/Laws_of_the_Night/` directory (if exists, for file-based search)
- **Writes/Generates:** 
  - AI-generated answers (via Anthropic API)
  - Search results combining database and file-based sources
- **Data stores:** 
  - Database tables: `rulebooks`, `rulebook_pages`
  - File system: `knowledge-base/` directory (markdown files)
  - File system: `agents/Laws_of_the_Night/` (if used)
- **AuthZ/AuthN (observed):** 
  - Session-based authentication required
  - Email verification check: `email_verified` must be true
  - Public endpoints: `health`, `public_traditions` (no auth required)
  - All other endpoints require authenticated and verified users
- **Dependencies (observed):** 
  - `includes/connect.php` - Database connection
  - `includes/anthropic_helper.php` - AI API integration
  - `includes/header.php` - Page header/template
  - `includes/footer.php` - Page footer
  - `css/global.css`, `css/header.css` - Styling
  - MCP scripts: `scripts/mcp_laws_agent.js`, `scripts/mcp_laws_agent_v2.js` (Node.js)
- **Risks / Notes:** 
  - Hybrid search system (database + file-based)
  - Requires Anthropic API key configuration
  - Complex tradition-specific search logic
  - MCP scripts present but not fully examined

---

### map_agent
- **Location:** `G:\VbN\agents\map_agent\`
- **Purpose (observed):** Provides map data for Phoenix 1994 setting. Returns JSON map data for cities, markers, and roads.
- **Primary interfaces:** 
  - `map_agent.php` - Main API endpoint (returns JSON map data)
  - `map_config.php` - Configuration file defining available maps and data files
- **Reads:** 
  - File system: `data/phoenix_cities.json`
  - File system: `data/phoenix_markers.json`
  - File system: `data/phoenix_roads.json`
  - Configuration: `map_config.php`
- **Writes/Generates:** 
  - JSON responses with map data
- **Data stores:** 
  - File system: `data/` directory (JSON files)
- **AuthZ/AuthN (observed):** 
  - No authentication observed (public API endpoint)
  - Input validation: map ID and layer validated against whitelist in config
  - Path traversal protection implemented
- **Dependencies (observed):** 
  - `map_config.php` - Map configuration
  - No database dependencies observed
- **Risks / Notes:** 
  - Public endpoint with no authentication
  - Security measures: whitelist validation and path traversal protection
  - Minimal structure - appears to be a simple data service

---

### positions_agent
- **Location:** `G:\VbN\agents\positions_agent\`
- **Purpose (observed):** Viewer interface for Camarilla Positions Agent. Provides access to position query functionality integrated into the main admin panel.
- **Primary interfaces:** 
  - `viewer.php` - Simple redirect/viewer page that links to main positions admin page
- **Reads:** 
  - Unknown (functionality appears integrated into `admin/camarilla_positions.php`)
- **Writes/Generates:** 
  - None observed (viewer only)
- **Data stores:** 
  - Unknown (likely uses database tables for positions, but not directly accessed from this agent)
- **AuthZ/AuthN (observed):** 
  - Session-based authentication required
  - Role check: `$_SESSION['role'] !== 'admin'` enforced
- **Dependencies (observed):** 
  - `includes/version.php` - Version constants
  - `includes/header.php` - Page header/template
  - `includes/footer.php` - Page footer
  - `css/admin-agents.css` - Styling
- **Risks / Notes:** 
  - Minimal implementation - appears to be a placeholder/wrapper
  - Actual functionality likely in `admin/camarilla_positions.php`
  - May be incomplete or in transition

---

### rumor_agent
- **Location:** `G:\VbN\agents\rumor_agent\`
- **Purpose (observed):** Viewer interface for Rumor Agent. Provides access to rumor management functionality integrated into the main admin panel.
- **Primary interfaces:** 
  - `index.php` - Simple redirect/viewer page that links to rumor viewer admin page
- **Reads:** 
  - Unknown (functionality appears integrated into `admin/rumor_viewer.php`)
- **Writes/Generates:** 
  - None observed (viewer only)
- **Data stores:** 
  - Unknown (likely uses database tables for rumors, but not directly accessed from this agent)
- **AuthZ/AuthN (observed):** 
  - Session-based authentication required
  - Role check: `$_SESSION['role'] !== 'admin'` enforced
- **Dependencies (observed):** 
  - `includes/version.php` - Version constants
  - `includes/header.php` - Page header/template
  - `includes/footer.php` - Page footer
  - `css/admin-agents.css` - Styling
- **Risks / Notes:** 
  - Minimal implementation - appears to be a placeholder/wrapper
  - Actual functionality likely in `admin/rumor_viewer.php`
  - May be incomplete or in transition
  - Note: Separate from `rumors_agent` (see below)

---

### rumors_agent
- **Location:** `G:\VbN\agents\rumors_agent\`
- **Purpose (observed):** Class-based rumor selection system. Selects nightly rumors for characters based on spread likelihood, plot connections, clan/location tags, and character backgrounds.
- **Primary interfaces:** 
  - `RumorAgent.php` - PHP class (not a web interface)
- **Reads:** 
  - Database: `rumors` table
  - Database: `character_heard_rumors` table (to avoid repeating rumors)
- **Writes/Generates:** 
  - Database: Inserts into `character_heard_rumors` table when rumors are selected
  - HTML rendering via `renderRumorsAsHtml()` method
- **Data stores:** 
  - Database tables: `rumors`, `character_heard_rumors`
- **AuthZ/AuthN (observed):** 
  - None (class-based, no direct web interface)
  - Authentication would be handled by calling code
- **Dependencies (observed):** 
  - `includes/connect.php` or direct `$conn` mysqli object passed to constructor
  - No other dependencies observed
- **Risks / Notes:** 
  - Class-based implementation (not a standalone agent interface)
  - Designed to be called from admin Agent pages
  - Separate from `rumor_agent` (see above) - naming inconsistency
  - Well-documented class with clear method structure

---

### status_agent
- **Location:** `G:\VbN\agents\status_agent\`
- **Purpose (observed):** Unknown - contains only a single `.mb` file (MindBody file format, possibly a MindMap or other specialized format).
- **Primary interfaces:** 
  - None observed (single file: `Camarilla Status System.mb`)
- **Reads:** 
  - Unknown
- **Writes/Generates:** 
  - Unknown
- **Data stores:** 
  - File system: `Camarilla Status System.mb` (binary file, ~1MB)
- **AuthZ/AuthN (observed):** 
  - None (no executable code observed)
- **Dependencies (observed):** 
  - None observed
- **Risks / Notes:** 
  - Appears to be a data/reference file rather than an active agent
  - `.mb` file format unclear (possibly MindBody, MindMap, or proprietary format)
  - May be documentation or reference material
  - Not a functional agent in the traditional sense

---

### style_agent
- **Location:** `G:\VbN\agents\style_agent\`
- **Purpose (observed):** MCP (Modular Content Pack) providing access to Valley by Night Art Bible documentation. Packages visual style guidelines, rules, prompts, and chapters for art generation and validation.
- **Primary interfaces:** 
  - `server.php` - MCP server (STDIN/STDOUT) exposing Art Bible tools
  - MCP tools: `getMCPInfo`, `listChapters`, `getChapter`, `getRules`, `getPrompts`, `searchArtBible`
- **Reads:** 
  - Database: `mcp_style_packs` table (for MCP metadata)
  - Database: `mcp_style_chapters` table (for chapter metadata)
  - File system: `docs/` directory (11 Art Bible chapter markdown files)
  - File system: `RULES.md`, `PROMPTS.md`, `INDEX.md`
  - File system: `indexes/` directory (index files)
- **Writes/Generates:** 
  - MCP responses (JSON via STDOUT)
  - No persistent writes observed
- **Data stores:** 
  - Database tables: `mcp_style_packs`, `mcp_style_chapters`
  - File system: `docs/` directory (markdown files)
  - File system: `indexes/` directory
  - File system: `prompts/`, `rules/` directories (if used)
- **AuthZ/AuthN (observed):** 
  - MCP server uses `db.php` with hardcoded credentials (security concern)
  - No web-based authentication (MCP protocol only)
- **Dependencies (observed):** 
  - `db.php` - Direct database connection (contains hardcoded credentials)
  - MCP configuration: `mcp.json` defines MCP server tools
- **Risks / Notes:** 
  - MCP server has hardcoded database credentials in `db.php` (security risk)
  - Comprehensive Art Bible documentation system
  - Well-structured MCP implementation
  - Database-driven MCP discovery system

---

### Laws_of_the_Night
- **Location:** `G:\VbN\agents\Laws_of_the_Night\`
- **Purpose (observed):** Empty directory - intended for Laws of the Night Revised markdown files for file-based search integration with laws_agent.
- **Primary interfaces:** 
  - None (empty directory)
- **Reads:** 
  - Intended: Markdown files with YAML frontmatter (per laws_agent documentation)
- **Writes/Generates:** 
  - None
- **Data stores:** 
  - File system: Empty directory (intended for markdown files)
- **AuthZ/AuthN (observed):** 
  - None (not an active agent)
- **Dependencies (observed):** 
  - Referenced by `laws_agent/markdown_loader.php` for file-based search
- **Risks / Notes:** 
  - Empty directory - placeholder for future content
  - Referenced in laws_agent documentation but not currently populated
  - Part of hybrid search system (database + file-based)

---

## Summary Table

| Agent Name | Purpose (1 line) | Writes to (reports/json/db) | Has auth? (Y/N) |
|------------|------------------|----------------------------|------------------|
| boon_agent | Monitors and validates boons according to Laws of the Night mechanics | JSON reports, logs | Y (admin role) |
| character_agent | Monitors character data, generates briefs, provides search/analytical queries | JSON reports, logs, DB updates | Y (admin role) |
| laws_agent | AI-powered rulebook Q&A system for VTM/MET rules and lore | AI responses (no persistent writes) | Y (email verified) |
| map_agent | Provides Phoenix 1994 map data (cities, markers, roads) | JSON responses | N (public API) |
| positions_agent | Viewer wrapper for Camarilla positions functionality | None | Y (admin role) |
| rumor_agent | Viewer wrapper for rumor management functionality | None | Y (admin role) |
| rumors_agent | Class-based rumor selection system for nightly rumors | DB (character_heard_rumors) | N (class-based) |
| status_agent | Reference file only (Camarilla Status System.mb) | None | N |
| style_agent | MCP providing Art Bible documentation access | MCP responses | N (MCP protocol) |
| Laws_of_the_Night | Empty directory placeholder for markdown files | None | N |

---

## Cross-Agent Observations

### Authentication Patterns
- **Admin-only agents:** boon_agent, character_agent, positions_agent, rumor_agent all require `$_SESSION['role'] === 'admin'`
- **User-verified agents:** laws_agent requires `email_verified === true` (less restrictive than admin)
- **Public agents:** map_agent has no authentication
- **Protocol-based:** style_agent, character_agent MCP servers use MCP protocol (no web auth)

### Database Usage
- **Heavy DB usage:** boon_agent, character_agent, laws_agent, rumors_agent all interact with multiple database tables
- **Light DB usage:** style_agent uses database only for MCP metadata
- **No DB usage:** map_agent, positions_agent, rumor_agent (viewer only), status_agent

### Security Concerns
- **Hardcoded credentials:** character_agent (`db.php`), style_agent (`db.php`) contain hardcoded database credentials
- **Public endpoints:** map_agent has no authentication (though has input validation)
- **Path traversal protection:** boon_agent, character_agent, map_agent implement path traversal protection

### Naming Inconsistencies
- **rumor_agent vs rumors_agent:** Two separate agents with similar names but different purposes (viewer wrapper vs class-based system)
- **Laws_of_the_Night:** Directory name uses underscores, not consistent with other agent naming (hyphens)

### Integration Patterns
- **Standalone agents:** boon_agent, character_agent, laws_agent, map_agent are fully functional
- **Wrapper agents:** positions_agent, rumor_agent are minimal wrappers redirecting to main admin pages
- **Class-based agents:** rumors_agent is a PHP class, not a standalone interface
- **MCP agents:** character_agent, style_agent expose MCP servers for programmatic access

---

## Related Planning Documents

### Candidate Agents Backlog
- **Location:** `G:\VbN\plan\Cadidate Agents Backlog.md`
- **Purpose:** Tracks potential agents that logically extend the existing VbN agent ecosystem
- **Content:** Non-binding design candidates for planning and prioritization, including:
  - High-value candidate agents (Influence, Status & Prestation Ledger, Timeline/Nightly Turn, Event, Investigation/Caseboard, Elysium/Court Session, Compliance/Consistency, Scene Builder, Asset Pipeline)
  - Explicitly avoided agents (Master Orchestrator, Automatic Consequence/Enforcement)
  - Notes on agent scope, authority, and prioritization criteria
- **Status:** Planning document only - no implementations exist

---

## Notes

- This inventory is based on file structure and code examination only
- Some agents may have additional functionality not visible in entry points
- Database schema details are inferred from code usage, not direct schema examination
- MCP servers require external tooling to interact with (not web-based)
- Some agents appear to be in transition (minimal implementations, empty directories)
- See `Cadidate Agents Backlog.md` for planned future agents

