# Version History

## Current Version: 0.8.15

**Date:** 2025-01-30  
**Type:** Patch (Error Analysis & Remediation Planning)

### Changes:
- **Error Remediation Plan Creation** - Comprehensive error analysis and remediation planning system
  - Created `errors_plan.md` - Complete remediation plan document (809 lines)
  - Analyzed all 34 errors from `errors.md` (ERR-001 through ERR-034)
  - Organized errors into 9 groups by type: JavaScript runtime errors, dropdown errors, syntax errors, null element access, UI/styling, JSON/AJAX loading, HTTP 403/500, UX modal conversion, HTTP 404 missing pages
  - Classified each error by difficulty (Easy: 9, Medium: 12, Hard: 13)
  - Created step-by-step fix plans for each error (8-10 steps per error)
  - Cross-referenced similar errors and identified common patterns
  - Ordered errors from easiest to hardest within each group
  - Provides actionable remediation guidance for all documented issues
- **Error Analysis Methodology** - Established systematic error analysis process
  - Error extraction and classification system
  - Difficulty assessment heuristics (Easy/Medium/Hard)
  - Error grouping by underlying cause and type
  - Similar error pattern identification
  - Comprehensive fix plan templates

## Previous Version: 0.8.14

**Date:** 2025-01-30  
**Type:** Patch (Character Content Creation & Error Documentation)

### Changes:
- **Helena Crowly Character Creation** - Complete Tremere Primogen character reference
  - Created `reference/Characters/Helena_Crowly.json` - Full character data (331 lines)
  - Tremere Primogen of Phoenix, "The Archivist of the Desert"
  - 9th generation Tremere with mastery of Thaumaturgy (Path of Blood, Path of Mercury, Path of Conjuring)
  - Complete character profile with appearance, biography, personality, traits, abilities, disciplines, timeline, domain/haven, relationships, rituals, and artifacts
  - Egyptian archaeology background and forensic thaumaturgy focus
- **CW Whitford Boon Generation Prompt** - Comprehensive boon generation system specification
  - Created `reference/Characters/cw_whitford_boons_cursor_prompt.md` - Detailed implementation prompt (170 lines)
  - Defines system for generating boons for Charles "C.W." Whitford with exactly 50% of NPCs
  - Specifies boon tier distribution: 5% Major, 25% Minor, 70% Trivial
  - Includes Taskmaster integration requirements, validation logic, and Harpy logging requirements
- **Error Documentation Expansion** - Significantly expanded error tracking system
  - Added 6 new error entries (ERR-013 through ERR-018) to `errors.md` (+655 lines)
  - Documented 404 errors for multiple admin pages: Rumor Viewer, Wraith Panel, Questionnaire, Admin Agents, Enhanced Sire/Childe, Boon Agent Viewer
  - Enhanced existing error entries (ERR-001, ERR-002) with additional details and JavaScript syntax error documentation
  - Improved error tracking format and consistency across all documented errors
- **Documentation Updates** - Minor updates to session notes and character database analysis
  - Updated session notes for boon agent UI improvements, wraith character system, and camarilla positions
  - Updated character database analysis documentation

## Previous Version: 0.8.13

**Date:** 2025-11-30  
**Type:** Patch (MCP Configuration Updates & Character Content Creation)

### Changes:
- **MCP Server Configuration** - Enhanced MCP server setup for better agent integration
  - Added Style Agent MCP server configuration to `.cursor/mcp.json`
  - Changed Git MCP to GitHub MCP (http-based) for better integration
  - Style Agent server accessible at `G:/VbN/agents/style_agent/server.php`
  - Improved MCP server architecture for multi-agent support
- **Storyteller Prompt Enhancements** - Expanded character generation prompts
  - Added cinematic introduction generation prompt to Storyteller file
  - Integrated Style Agent MCP usage for cinematic content creation
  - Enhanced prompt structure for character biography and appearance generation
  - Improved formatting and clarity in prompt instructions
- **C.W. Whitford Character Content** - Complete character reference package
  - Created `CW_Whitford_Cinematic_Introduction.md` - Full 8-shot cinematic introduction (232 lines)
  - Created `CW_Whitford_Image_Prompt.md` - Character portrait generation prompt
  - Added character portrait image (`CW Whitford.png`)
  - 45-second cinematic sequence following Valley by Night Art Bible format
  - Ventrue Primogen character with construction site setting (unique among Ventrue)
  - Includes technical notes, audio breakdown, GM notes, and discipline references
- **FTP Upload Documentation** - Created deployment notes for case sensitivity issues
  - Documented case sensitivity problem between local `agents/` and remote `Agent/` folders
  - Provided three solution options for FTP upload workflow
  - Database path updated to match remote server structure
  - Verification script reference for post-upload validation

## Previous Version: 0.8.12

**Date:** 2025-01-30  
**Type:** Patch (Database Maintenance - Primogen Character Import)

### Changes:
- **Primogen Character Database Integration** - Created script to import and assign primogen characters
  - Created `database/import_primogen_characters.php` - Import script for primogen characters (271 lines)
  - Imports CW Whitford (Ventrue Primogen) - Database ID: 136, Position ID: primogen_ventrue, Assignment ID: 7
  - Imports Naomi Blackbird (Gangrel Primogen) - Database ID: 137, Position ID: primogen_gangrel, Assignment ID: 8
  - Automatic position creation if primogen positions don't exist
  - Single atomic transaction for character import and position assignment
  - Inline helper functions to avoid executing unwanted code from import_characters.php
  - Comprehensive error handling with transaction rollback
  - Verifies character clan matches expected primogen clan
- **Database Consistency** - Both characters verified in database with correct assignments
  - Characters table: Both characters inserted/updated with all fields
  - Camarilla positions table: Both primogen positions created
  - Position assignments table: Both assignments created with proper character ID format
  - No duplicates detected, all data normalized and validated

## Previous Version: 0.8.11

**Date:** 2025-01-30  
**Type:** Patch (Laws Agent Restoration & File Recovery)

### Changes:
- **Laws Agent Restoration** - Restored complete Laws Agent directory structure from git history
  - Fixed forbidden error when accessing `/agents/laws_agent/` by restoring missing files
  - Restored 19 files from commit `5e8f308` (Nov 14, 2025) including:
    - `index.php` - Main Laws Agent interface with email verification and query system
    - `api.php` - API endpoint for rulebook queries (824 lines)
    - `markdown_loader.php` - File-based markdown indexing system (410 lines)
    - `README.md` - Comprehensive documentation (171 lines)
    - Knowledge base files (11 markdown files covering Six Traditions, enforcement, character creation)
    - MCP scripts (`mcp_laws_agent.js`, `mcp_laws_agent_v2.js`) and package files
  - Total of 3,063 lines of code restored
  - Files were previously removed or missing from HEAD, causing 403 Forbidden errors
  - Laws Agent now fully functional with database and file-based search integration
- **Git History Investigation** - Traced Laws Agent files through git history
  - Identified original commit where files were added (5e8f308)
  - Verified file structure and contents from git repository
  - Restored complete directory structure including knowledge-base and scripts subdirectories
- **Code Quality** - Restored complete, functional Laws Agent system
  - All files match original implementation from Nov 14, 2025
  - Includes integration with rulebook database and Laws of the Night markdown files
  - Maintains email verification requirement for access
  - Full query interface and API functionality restored

## Previous Version: 0.8.10

**Date:** 2025-01-30  
**Type:** Patch (Style Agent MCP System & Reorganization)

### Changes:
- **Style Agent MCP Reorganization** - Complete reorganization of Style Agent into MCP structure
  - Moved all 11 Art Bible chapter files to `agents/style_agent/docs/` folder
  - Moved index files to `agents/style_agent/indexes/` folder
  - Removed duplicate/old Art Bible files from root (13 files, ~5000 lines removed)
  - Created standardized MCP documentation structure (README, RULES, PROMPTS, INDEX)
- **MCP Database Infrastructure** - Created foundational MCP system
  - Created `mcp_style_packs` table for MCP registry (name, slug, version, filesystem_path, enabled)
  - Created `mcp_style_chapters` table for chapter-level metadata (optional)
  - Database migration script with idempotent table creation
  - Path correction and update utility scripts
- **MCP Documentation & Usage** - Comprehensive MCP system documentation
  - Created `docs/MCP_USAGE.md` with complete usage guide (457 lines)
  - Database schema documentation and integration examples
  - Query patterns and best practices for agents
  - Discovery and loading patterns
- **MCP Configuration Files** - MCP server infrastructure
  - `mcp.json` - MCP server configuration
  - `server.php` - MCP server implementation
  - `db.php` - Database connection for MCP system
- **Verification & Testing Tools** - MCP system validation utilities
  - Structure validation script (`verify_mcp_structure.php`)
  - Loading test script (`test_mcp_loading.php`)
  - Connection test (`connect_to_style_agent_mcp.php`)
  - Directory setup utility (`create_mcp_directories.php`)
- **Path Verification** - Created path verification documentation
  - `agents_path_verification.md` - Documents correct agent paths and structure
- **Code Quality** - Infrastructure improvements
  - Clean file organization and structure
  - Comprehensive documentation
  - Database migration scripts with proper error handling
  - Follows project coding standards

## Previous Version: 0.8.9

**Date:** 2025-01-30  
**Type:** Patch (Admin Interface Refactoring & Code Consolidation)

### Changes:
- **CSS Consolidation & Cleanup** - Consolidated modal styling and reduced CSS duplication
  - Unified modal styling into shared `css/modal.css` file (145 lines consolidated)
  - Removed duplicate CSS from `admin_items.css` (121 lines cleaned) and `admin_locations.css` (120 lines cleaned)
  - Improved consistency in modal appearance and behavior across all admin pages
  - Enhanced modal layout, spacing, and visual hierarchy
- **JavaScript Refactoring** - Major refactoring of admin JavaScript files
  - Refactored `admin_equipment.js` (325 lines changed) with improved code organization
  - Refactored `admin_items.js` (272 lines changed) with streamlined structure
  - Refactored `admin_locations.js` (280 lines changed) with better error handling
  - Enhanced `admin_panel.js`, `admin_boons.js`, and `admin_camarilla_positions.js`
  - Reduced code duplication in `main.js` module
  - Improved error handling, validation, and modal management across all admin pages
- **Admin Page Refactoring** - Enhanced admin interface pages
  - Refactored Equipment, Items, Locations, Panel, Boon Ledger, and Camarilla Positions pages
  - Improved code structure, organization, and maintainability
  - Better integration with modal systems and form handling
  - Enhanced search, filter, and validation functionality
- **Modal System Improvements** - Enhanced character view and position view modals
  - Improved data handling, rendering, and error handling
  - Better visual presentation and user experience
- **Character Creation Improvements** - Enhanced character creation form
  - Improved form handling, validation, and state management
- **Error Documentation** - Comprehensive error tracking system
  - Updated `errors.md` with 10 confirmed errors (ERR-001 through ERR-010)
  - Added detailed reproduction steps, severity ratings, and testing notes
  - Documented 1000+ successful test cases
  - Established error tracking format for future testing
- **Code Quality** - Overall code quality improvements
  - Reduced code duplication across files (net -101 lines)
  - Improved code organization and structure
  - Enhanced error handling and validation
  - Better separation of concerns and maintainability

## Previous Version: 0.8.8

**Date:** 2025-11-30  
**Type:** Patch (Comprehensive Chrome UI Testing & Error Documentation)

### Changes:
- **Comprehensive Chrome UI Testing** - Systematic testing of all interactive elements across the application
  - Tested 1000+ interactive elements including buttons, links, forms, modals, dropdowns, and navigation
  - Covered all major admin pages: Characters, Equipment, Items, Positions, Boons, NPC Briefing, Locations, Agents, Questionnaire, Rumor Viewer
  - Tested character management features: view, edit, delete modals, filters, search, sorting, pagination
  - Verified agent pages: Character Agent, Boon Agent, Positions Agent, Rumor Agent
  - Tested form submissions, modal interactions, table operations, and navigation flows
  - Confirmed functionality of filter buttons, dropdown menus, column headers, action buttons, and pagination controls
- **Error Documentation System** - Created comprehensive error tracking system
  - Created `errors.md` file to systematically track all discovered bugs and issues
  - Documented 7 confirmed errors with detailed reproduction steps, expected vs actual behavior, and severity ratings
  - Established error tracking format for consistent documentation
  - Identified errors across multiple pages: Locations table loading, Locations modal JavaScript, Chat page character loading, Account page accessibility warning, NPC Briefing modal JSON parsing, Items page view button, Boon Agent Reports directory 500 error
- **Testing Infrastructure** - Established testing workflow and documentation
  - Created structured error logging format for future testing sessions
  - Documented successful test cases for reference
  - Identified areas needing styling improvements (Boon Ledger, Item Edit popup, Character Agent Configuration)

## Previous Version: 0.8.7

**Date:** 2025-01-26  
**Type:** Patch (Character Agent Analytical Query System)

### Changes:
- **Character Agent Search Interface** - Complete character search and analytical query system
  - Added new character search interface at `agents/character_agent/characters.php`
  - Supports natural language queries about characters, clans, NPCs, and analytical questions
  - Implemented analytical query processing for questions like "which clan has the most NPCs"
  - Added clan-based range queries (e.g., "which clans have more than 0 and fewer than 3 characters")
  - Created specialized functions for clan statistics (most NPCs, most PCs, most characters overall)
  - Added count-by-clan functionality with PC/NPC breakdown
  - Implemented range query parsing for numeric comparisons (more than, fewer than, between, exactly, at least, at most)
- **Character Agent Styling** - Custom blood red alert styling
  - Created new `alert-blood` CSS class with blood red gradient background (#8B0000 to #600000)
  - Added lighter red border (#b30000) matching project theme
  - Implemented drop shadow effects matching clan logo styling (glow and inset shadows)
  - Styled results content area with blood red gradient background
  - Applied alert-blood styling to all clan analytical query results

## Previous Version: 0.8.6

**Date:** 2025-01-26  
**Type:** Patch (Character View Modal Improvements & Character Creation Enhancements)

### Changes:
- **Character View Modal Enhancements** - Improved Wraith character support and field-aware rendering
  - Enhanced modal to better handle Wraith character data transformation
  - Improved rendering logic to preserve differences between VtM and Wraith character types
  - Added more robust error handling for character data loading
  - Better integration with admin panels for both character types
- **Character Creation Form Improvements** - Enhanced state management and form handling
  - Improved character loading and state persistence in character creation form
  - Fixed issues with form field population when loading existing characters
  - Enhanced character image upload and display handling
  - Improved ability data handling and display
- **JavaScript Improvements** - Fixed syntax errors and improved compatibility
  - Fixed multiple JavaScript syntax errors in character view modal
  - Improved browser compatibility by converting arrow functions where needed
  - Added better error handling and logging in main.js
  - Enhanced module communication and state synchronization
- **Character Reference File Cleanup** - Organized character reference files
  - Moved character JSON files to "Added to Database" folder for better organization
  - Updated CHARACTER_DATABASE_ANALYSIS.md with latest field information
  - Improved organization of character reference files
- **Admin Panel Updates** - Enhanced admin panels with improved functionality
  - Enhanced Wraith character admin panel with improved view functionality
  - Improved character view API to handle both VtM and Wraith characters
  - Better integration between admin panels and character view modals
- **MCP Configuration Updates** - Updated MCP server configuration
  - Updated MCP server configuration for better agent integration
  - Refined MCP server settings for improved communication

## Previous Version: 0.8.5

**Date:** 2025-01-26  
**Type:** Patch (Character View Modal Unification & Bug Fixes)

### Changes:
- **Unified Character View Modal** - Integrated Wraith and VtM character views into shared modal component
  - Replaced custom Wraith modal with shared `character_view_modal.php` component
  - Added client-side data transformation for Wraith API responses to match VtM format
  - Implemented field-aware rendering that preserves differences between character types
  - Added Wraith-specific fallback image (`WtOlogo.webp`) when character portrait is missing
  - Maintained visual and structural consistency between both character type views
- **JavaScript Syntax Fixes** - Resolved multiple syntax errors in character view modal
  - Converted arrow functions to regular functions for better browser compatibility
  - Fixed template literal syntax issues causing parsing errors
  - Fixed missing closing brace in `renderCharacterView` function (else block)
  - Simplified ternary operators and array destructuring patterns
  - Fixed Unicode regex pattern compatibility issues
- **Wraith Logo Display Fix** - Improved logo rendering for non-square images
  - Added `character-portrait-logo` CSS class for Wraith fallback images
  - Changed `object-fit: cover` to `object-fit: contain` for logo display
  - Added semi-transparent background for better logo visibility
  - Ensures full logo is displayed without cropping

## Previous Version: 0.8.4

**Date:** 2025-01-26  
**Type:** Patch (Configuration Updates & Documentation Refinements)

### Changes:
- **MCP Configuration Updates** - Enhanced MCP server configuration for better agent integration
  - Updated MCP server settings for improved agent communication
  - Refined configuration for Character Agent, Boon Agent, and other agent systems
- **Character Database Analysis** - Enhanced character database documentation
  - Updated CHARACTER_DATABASE_ANALYSIS.md with field mapping details
  - Improved character schema documentation and field consistency notes
- **Character Reference Updates** - Updated character reference files
  - Updated Eddy Valiant character sheet with latest data
  - Enhanced character.json template with improved field documentation
- **Session Documentation** - Updated session notes with implementation details
  - Enhanced boon agent UI improvements documentation
  - Updated Camarilla positions session notes
  - Added Wraith character system implementation notes
- **Agent Configuration** - Refined agent dashboard configuration
  - Updated agents.php with improved agent status and action configurations

## Previous Version: 0.8.3

**Date:** 2025-01-26  
**Type:** Patch (Wraith Character System Foundation)

### Changes:
- **Wraith Character System Foundation** - Complete foundation for Wraith: The Oblivion character management
  - New `wraith_characters` database table with all Wraith-specific fields (shadow name, circle, guild, arcanoi, pathos/corpus, angst)
  - Database migration script (`database/create_wraith_characters_table.php`) with idempotent table creation
  - Character creation form (`wraith_char_create.php`) with 5-page wizard structure
  - Admin panel (`admin/wraith_admin_panel.php`) for viewing and managing Wraith characters
  - Save handler (`includes/save_wraith_character.php`) for persisting character data
  - View API (`admin/view_wraith_character_api.php`) for character data retrieval
  - JavaScript module (`js/wraith_char_create.js`) for form handling
  - CSS styling (`css/wraith_char_create.css`) for Wraith-specific pages
  - Complete schema template (`reference/Characters/wraith_character.json`)
  - Implementation plan and field mapping documentation
  - Parallel system design - does not affect existing VtM character system
  - Removed VtM-specific fields (clan, generation, sire, disciplines, blood pool)
  - Added Wraith-specific mechanics (fetters, passions, arcanoi, shadow, pathos/corpus, harrowing)

## Previous Version: 0.8.2

**Date:** 2025-01-26  
**Type:** Patch (Universal Modal Fullscreen System & Boon Relationships Graph)

### Changes:
- **Universal Modal Fullscreen System** - Reusable fullscreen functionality for all Bootstrap modals
  - Shared CSS (`css/modal_fullscreen.css`) and JavaScript (`js/modal_fullscreen.js`) files
  - Automatic fullscreen button injection via `data-fullscreen="true"` attribute
  - Custom resize handler support for graphs/charts via `data-fullscreen-resize-handler`
  - State management with automatic reset when modal closes
  - Integrated into header.php and footer.php for global availability
  - Added to 7 modals across the project (Character View, Position View, Boon Relationships, Report Result, View Rumor, Tree Modal, Report View)
- **Boon Relationships Graph Visualization** - Interactive network graph for boon relationships
  - Visual representation of creditor → debtor relationships between characters
  - Uses vis-network library for interactive graph rendering
  - Color-coded edges by boon type (Trivial=gray, Minor=gold, Major=dark red, Life=black)
  - Edge width varies by boon importance (1-4px)
  - Physics-based automatic layout with zoom, pan, and drag support
  - Only displays active boons (excludes fulfilled/cancelled)
  - Custom resize handler for fullscreen mode
- **Path Prefix Fix** - Fixed path prefix calculation in footer.php to match header.php logic
- **Modal Integration** - Added fullscreen support to key modals throughout admin interface

## Previous Version: 0.8.1

**Date:** 2025-01-26  
**Type:** Patch (Version sync update)

## Previous Version: 0.8.0

**Date:** 2025-01-26  
**Type:** Minor (Misfortune's Boon Generation System - New Working Feature)

### Changes:
- **Misfortune's Boon Generation System** - Complete automated boon generation system for character "Misfortune"
  - Creates character-specific boons with every NPC in the database (34 NPCs)
  - Deterministic tier distribution: 5% Major, 25% Minor, 70% Trivial (achieved 5.9%, 23.5%, 70.6%)
  - Character-tailored descriptions based on NPC attributes (clan, generation, concept, biography, title, role)
  - Clan-specific templates for all major clans (Malkavian, Tremere, Nosferatu, Toreador, Ventrue, Gangrel, Brujah, Followers of Set, Giovanni)
  - Role-aware descriptions for Primogen, elders, and important characters
  - Theme-based variations for researchers, merchants, information brokers
  - Hash-based deterministic assignment ensures consistent results across runs
  - Idempotent operation checks for existing boons and skips NPCs already having boons
  - Full Harpy integration: auto-registers all boons with Harpy system (Cordelia Fairchild)
  - Comprehensive error handling with detailed debugging information
  - Transaction-based database operations for data integrity
  - Distribution validation and reporting
- **Database Integration Improvements**
  - Fixed foreign key constraint handling for `created_by` field (auto-locates valid user ID)
  - Proper NULL handling for optional fields
  - System user ID lookup function for system-generated records
- **Boon Description Generator** - Advanced character-specific description generation
  - 50+ unique templates across all boon tiers
  - Incorporates Misfortune's role as "Boon Collector" and Harpy network facilitator
  - Authentic to Laws of the Night Revised mechanics and World of Darkness lore
  - Tailored to Valley by Night setting

## Previous Version: 0.7.5

**Date:** 2025-11-26  
**Type:** Patch (Boon Agent Integration, Storyteller Prompt Updates, Import Guide Improvements)

### Changes:
- **Boon Agent Integration** - Added Boon Agent to admin agents dashboard
  - Registered Boon Agent as active agent with full configuration
  - Added action buttons for "Launch Boon Agent" and "View Boon Ledger"
  - Removed Boon Agent from planned agents list (now active)
  - Agent monitors and validates boons according to Laws of the Night Revised mechanics
  - Tracks favor-debt, detects violations, integrates with Harpy systems
- **Storyteller Prompt Updates** - Updated character generation prompts
  - Changed from appearance description to biography generation
  - Adjusted word count guidelines (100-200 words, aim for 150)
  - Updated prompt structure for biography creation
  - Maintained World of Darkness gothic/horror atmosphere requirements
- **Misfortune Character Storyboard** - Created full 7-shot cinematic storyboard
  - Complete director's version with timing, framing, lighting, motion, and audio cues
  - 30-second runtime with neo-noir gothic theatrical tone
  - Ready for animators, editors, or video directors
  - Follows Valley by Night Cinematic Intro Guide format
- **Import Guide Improvements** - Enhanced character import documentation
  - Updated file paths from `data/` to `reference/Characters/` folder
  - Added CLI support for character imports (web and command-line options)
  - Documented upsert behavior (updates existing, inserts new)
  - Added supported JSON format variations section
  - Updated examples with correct URLs and file locations
  - Documented automatic field name variation handling
- **Documentation Updates** - Minor updates to character database analysis and session notes

## Previous Version: 0.7.4

**Date:** 2025-11-25  
**Type:** Patch (Admin Agents Page Bootstrap Card Refactor)

### Changes:
- **Bootstrap Card Refactor** - Refactored admin agents page to use Bootstrap card components
  - Replaced custom card structure with Bootstrap `card-body` and `card-footer` components
  - Added `d-flex flex-column` layout to cards for proper flexbox structure
  - Moved action buttons to `card-footer` with `mt-auto` to pin buttons to bottom of cards
  - Replaced custom classes with Bootstrap utilities (`card-title`, `card-text`, `badge`, `list-unstyled`, `code`)
  - Used Bootstrap typography classes (`small`, `fw-bold`, `text-white`) for consistent styling
  - Changed button layout from `w-100` to `flex-fill` for better responsive behavior
- **Card Styling** - Added dark red borders to agent cards
  - Removed `border-0` from cards
  - Added custom CSS rule using `--blood-red` CSS variable for consistent theming
- **Text Contrast Improvements** - Fixed text visibility on dark background
  - Replaced all `text-muted` instances with `text-white` for better contrast
  - Applied to Purpose labels, Data Access labels, code elements, and Last event text
- **Rumor Agent URL Fix** - Fixed placeholder URL for Rumor Agent
  - Changed from `"RUMOR_AGENT_URL"` placeholder to `"rumor_viewer.php"`
  - Button now correctly links to the admin rumor viewer interface
- **CSS Updates** - Enhanced `css/admin-agents.css` with minimal custom styling
  - Added status badge styling with active state support
  - Added card footer border and background adjustments
  - Added planned agents section styling
  - Preserved existing JSON display styles for report modals

## Previous Version: 0.7.3

**Date:** 2025-01-24  
**Type:** Patch (Character Database Analysis & Lilith Nightshade Character Creation)

### Changes:
- **Character Database Analysis** - Created comprehensive database schema analysis document
  - Documented all database fields and related tables
  - Identified field name inconsistencies across JSON files
  - Cataloged fields in JSON files that don't exist in database
  - Identified missing required fields in JSON files
  - Documented format inconsistencies (abilities, disciplines, backgrounds, traits, morality, status)
  - Provided recommendations for standardization with priority rankings
- **Character Template & Documentation** - Created standardized character template and field documentation
  - `character.json` - Complete template matching database schema with all required/optional fields
  - `character.json.documentation.md` - Comprehensive field documentation with examples and format guidelines
  - Proper format examples for arrays, objects, and nested structures
  - Database mapping information for all fields
- **Lilith Nightshade Character Creation** - Complete character reference package
  - `lilith_nightshade.json` - Full character data (Malkavian Primogen, "The Porcelain Oracle")
  - Character portrait image following art guide specifications
  - Cinematic introduction scene following established format
  - Complete profile with appearance, biography, personality, traits, abilities, disciplines, timeline, domain/haven
- **Storyteller Prompt Updates** - Enhanced character generation prompts
  - Added appearance description guidelines (200-500 words, 340 target)
  - Added character history guidelines
  - World of Darkness gothic/horror atmosphere specifications
  - Structure and specificity requirements
- **Dreamweaver Ignore Rule** - Created rule to ignore Dreamweaver metadata files (`_notes` folders, `dwsync.xml`)

## Previous Version: 0.7.2

**Date:** 2025-01-24  
**Type:** Patch (Character Reference Files - Alistaire)

### Changes:
- **Character Reference Files** - Added Alistaire character reference files
  - Created `reference/Characters/alistaire.json` with complete character data
  - Added `reference/Characters/Images/Alistaire.png` character portrait
  - Expanded truncated fields in character JSON:
    - **Appearance** - Detailed description of Nosferatu features, movement, and presence
    - **Personality** - Expanded narrative on "Stillness is a weapon" philosophy and Survivor/Judge nature
    - **Biography** - Complete backstory from Venice through WWII survival, journey to Phoenix, and current status as Primogen
  - Character data includes timeline, domain/haven details, current residents, and description tags
  - All fields now contain complete, rich content consistent with character concept

## Previous Version: 0.7.1

**Date:** 2025-11-23  
**Type:** Patch (Camarilla Positions Action Column with Modals)

### Changes:
- **Action Column with Modals** - Added comprehensive action buttons to Camarilla Positions table
  - 👁️ View button opens read-only modal with position details, current holder, and assignment history
  - ✏️ Edit button opens editable modal for updating position information
  - 🗑️ Delete button with confirmation modal for position deletion
  - Modal system matches admin_panel.php character view pattern for consistency
- **API Endpoints** - Created three new API endpoints for position management
  - `view_position_api.php` - Returns complete position data with current holder and history
  - `update_position_api.php` - Handles position updates (name, category, description, importance_rank)
  - `delete_position_api.php` - Handles position deletion with assignment validation
- **Position View Modal** - Created reusable modal component (`includes/position_view_modal.php`)
  - Bootstrap modal with read-only and editable modes
  - Displays position details, current holder information, and assignment history
  - Form submission for editing position fields
  - Integrated with existing helper functions for data retrieval
- **JavaScript Enhancements** - Updated `admin_camarilla_positions.js` with modal functionality
  - `viewPosition()` function for opening read-only modal
  - `editPosition()` function for opening editable modal
  - `deletePosition()` function with confirmation dialog
  - Form submission handlers and error handling
- **CSS Updates** - Enhanced `admin_camarilla_positions.css` with modal styling
  - Action button group styling matching admin_panel.php
  - Modal layout and form field styling
  - Badge styling for position status indicators

## Previous Version: 0.7.0

**Date:** 2025-01-22  
**Type:** Minor (Camarilla Positions Management System)

### Changes:
- **Camarilla Positions Management Page** - Complete admin interface for viewing and querying Camarilla position holders
  - Displays all positions with current holders in sortable, filterable table
  - Category and clan filtering for quick navigation
  - Search functionality for position names
  - Shows position status (Permanent, Acting, Vacant) with visual badges
  - Position history viewing for each office
  - Character position history lookup
  - Agent interface with position and character lookup forms
  - Historical assignment tracking with start/end dates
- **Helper Functions** - Created `camarilla_positions_helper.php` with database query functions
  - `get_current_holder_for_position()` - Finds current holder for a position on a given night
  - `get_all_positions_with_current_holders()` - Retrieves all positions with their current holders
  - `get_position_history()` - Gets complete assignment history for a position
  - `get_character_position_history()` - Gets all positions a character has held
- **Integration** - Added Positions link to admin navigation panel
- **Agent Registration** - Added Camarilla Positions Agent to agents dashboard
  - Provides quick access to position queries and historical data
  - Integrated with existing agent system architecture

## Previous Version: 0.6.12

**Date:** 2025-11-22  
**Type:** Patch (Character Art Guide creation and code improvements)

### Changes:
- **Character Art Guide** - Created comprehensive `Valley_by_Night_Character_Art_Guide.json` for standardized character portrait generation
  - Defines visual style parameters (genre, mood, composition, lighting, color palette, texture)
  - Includes aesthetic rules and elements to avoid
  - Provides prompt template for consistent AI-generated portraits
  - Includes clan-specific visual variants (Toreador, Gangrel, Malkavian, Setite, Giovanni)
  - Ensures consistent tone, lighting, and atmosphere for all NPC and PC images
- **Code Improvements** - Minor updates to character view modal, login process, and report generation
- **File Cleanup** - Removed outdated location and character teaser files that were reorganized

## Previous Version: 0.6.11

**Date:** 2025-11-22  
**Type:** Patch (Location and Room JSON schema standardization)

### Changes:
- **Location Template** - Created standardized `location_template.json` template through schema induction
  - Analyzed all existing location JSON files (Mesa Storm Drains, The Bunker - Computer Room, The Warrens)
  - Identified and unified all unique keys/variables across location files (52 total fields)
  - Preserves naming conventions and structure from existing locations
  - Includes comprehensive fields: basic info, status/location, ownership/access, security details, utility flags, supernatural/magical properties, relationships, and metadata
- **Room Template** - Created comprehensive `room_template.json` template for rooms within locations
  - Designed with 39 fields covering physical properties (dimensions, ceiling height, capacity)
  - Includes environmental details (lighting, temperature, atmosphere)
  - Security features (level, locks, alarms, traps)
  - Utility flags aligned with location template
  - Special properties for unique room features (Faraday cages, pressure plates, etc.)
  - Connection system for linking rooms together
  - Location relationship via location_id foreign key
- Templates provide consistent format for future location and room creation and AI-assisted generation

## Previous Version: 0.6.10

**Date:** 2025-01-18  
**Type:** Patch (Plot Hooks JSON schema standardization)

### Changes:
- **Plot Hooks Template** - Created standardized `Plot Hooks.json` template through schema induction
  - Analyzed three existing plot hook JSON files (Barry Horowitz, Layla al-Sahr, Marisol Roadrunner Vega)
  - Unified field naming conventions and structure
  - Documented required vs optional fields
  - Supports both simple and complex multi-act plot hooks
  - Includes meta_hooks and usage_notes structures
- Template provides consistent format for future plot hook creation and AI-assisted generation

## Previous Version: 0.6.9

**Date:** 2025-01-18  
**Type:** Patch (Restored accidentally purged reference folders from git)

### Changes:
- **Restored Reference Folders** - Recovered 7 folders that were accidentally purged:
  - **Items** - Items.txt, PR Marble.html, and supporting files
  - **Companies** - Aegis Solutions Group documentation
  - **field-references** - LOCATIONS_FIELD_REFERENCE.md
  - **game-lore** - Setting files, Harpy.md, IMPORT_GUIDE.md, and chronicle PDFs
  - **mechanics** - Complete mechanics documentation including Abilities, Backgrounds, Disciplines, Ghouls, Humanity, Merits/Flaws, Willpower, and clans subfolder
  - **Plot Hooks** - The Unsettling Favor plot hook files
  - **Questionaire** - Character creation questionnaire files (Questions_2.md, Questions_3.md, etc.)
  - **Locations** - Hawthorne Estate.md, Mesa Storm Drains.json, The Bunker - Computer Room.json, The Warrens.json
- All folders restored from commit fee692c^ (before folder reorganization)
- Files staged and ready for commit

## Previous Version: 0.6.8

**Date:** 2025-01-18  
**Type:** Patch (Completed all remaining cinematic character teasers - final batch)

### Changes:
- **Completed Character Teaser Collection** - All 37 characters now have cinematic teasers
- Created final 5 character teasers following Valley_by_Night_Cinematic_Intro_Guide.md format:
  - Mr. Harold Ashby (Malkavian serial killer obsessed with preserving grief)
  - Tariq Ibrahim (Setite nightclub impresario curating VIP corruption experiences)
  - Layla al-Sahr (Assamite assassin stranded in Phoenix, maintaining Malkavian cover)
  - Tor (Ghoul of Mr. Ashby, caught between loyalty and growing realization)
  - Marisol "Roadrunner" Vega (Gangrel tracker mapping supernatural safe trails)
- **File Organization:**
  - Moved supplementary scene teaser "Rembrandt and Jax.md" from Character Teasers to Scene Teasers folder
- **Tracking System:**
  - Updated missing-character-teasers.json with hasTeaser flags for all characters
  - Created update_teaser_flags.py script for automated tracking updates
  - All 37 characters now marked with hasTeaser: true
- All teasers include cinematic scene cards, GM notes, hooks, and plot foreshadowing
- Consistent neo-noir gothic style with proper formatting per style guide
- Each teaser captures character essence through environment, action, and voice

## Previous Version: 0.6.7

**Date:** 2025-01-18  
**Type:** Patch (Additional cinematic character teasers - batch 3)

### Changes:
- Created 5 new cinematic character teasers following Valley_by_Night_Cinematic_Intro_Guide.md format:
  - Alessandro Vescari (Giovanni investment liaison targeting Setite Temple)
  - Jennifer Kwan (Setite Security Director of The Hole)
  - Marcus Webb (Setite General Manager who cultivates corruption)
  - Kerry, the Gangrel (Witness to the 1973 Prince's staking)
  - Roland Cross (Toreador Sheriff who treats killing as art)
- All teasers include cinematic scene cards, GM notes, hooks, and plot foreshadowing
- Consistent neo-noir gothic style with proper formatting per style guide
- Each teaser captures character essence through environment, action, and voice

## Previous Version: 0.6.6

**Date:** 2025-01-18  
**Type:** Patch (Additional cinematic character teaser - Andrei Radulescu)

### Changes:
- Created cinematic character teaser for Andrei Radulescu (Tremere refugee scholar)
  - Features experimental Dehydrate Thaumaturgy path research
  - Includes scene card, GM notes, hooks, and plot foreshadowing
  - Follows Valley_by_Night_Cinematic_Intro_Guide.md format
  - Neo-noir gothic style with desert ritual atmosphere
  - Connects to Garou threat and Tremere politics

## Previous Version: 0.6.5

**Date:** 2025-01-18  
**Type:** Patch (Additional cinematic character teasers - batch 2)

### Changes:
- Created 11 new cinematic character teasers following Valley_by_Night_Cinematic_Intro_Guide.md format:
  - Jax 'The Ghost Dealer' (Ravnos street grifter with slipping Chimerstry)
  - Bayside Bob (Toreador Anarch liaison and tiki bar owner)
  - Duke Tiki (Toreador elder tiki artist and mentor)
  - Sabine (Toreador twin talon and logistics mastermind)
  - Sebastian (Toreador twin tactician and records keeper)
  - Leo (Nosferatu informatics engineer and underground command center operator)
  - Betty (Nosferatu tech savant and Shrecknet architect)
  - Lucien Marchand (Toreador ghoul sculptor and living chisel)
  - Sofia Alvarez (Toreador ghoul interior designer and social choreographer)
  - Piston (Brujah Anarch biker marked by diablerie)
  - Étienne Duvalier (Toreador Primogen and gallery master)
- All teasers include cinematic scene cards, GM notes, hooks, and plot foreshadowing
- Consistent neo-noir gothic style with proper formatting per style guide

## Previous Version: 0.6.4

**Date:** 2025-01-18  
**Type:** Patch (Cinematic Character Intro generation system and initial character intros)

## Previous Version: 0.6.3

**Date:** 2025-01-XX  
**Type:** Patch (Admin Equipment Management improvements and modal CSS consolidation)

### Changes:
- Consolidated modal CSS into shared `css/modal.css` file
- Refactored Admin Equipment page with improved UI/UX
- Converted Type and Category fields to dropdowns (database-populated)
- Requirements field now displays/accepts readable format instead of JSON
- Fixed 3-column layout in equipment view modal (Basic Info, Combat Stats, Requirements)
- Fixed character assignment modal issues (equipment ID handling)
- Improved modal accessibility and focus management
- Reduced vertical spacing in view modal to eliminate scrollbar
- Added indentation to content under section headers
- Fixed page freezing issues with action buttons

## Previous Version: 0.6.2

**Date:** 2025-01-XX  
**Type:** Patch (Admin navigation refactoring)

### Changes:
- Created reusable admin navigation component (`includes/admin_header.php`)
- Updated admin locations page to use Bootstrap grid layout
- Navigation items now display in responsive columns instead of stacked rows

## Previous Version: 0.6.1

**Date:** 2025-01-XX  
**Type:** Patch (Bug fixes and report viewer improvements)

### Changes:
- Fixed 404 error on Generate Reports button (path corrections)
- Fixed 500 error on report generation page (config handling, Bootstrap initialization)
- Fixed Forbidden error when loading reports (created secure API endpoint)
- Implemented modal-based report viewer with styled JSON display
- Added "Back to Agents" navigation button
- Created secure API endpoint for serving report files
- Improved file name visibility and UI layout

## Previous Version: 0.6.0

**Date:** 2025-01-XX  
**Type:** Minor (Character view modal system)

## Version: 0.5.1

**Date:** 2025-01-XX  
**Type:** Patch (Code organization and cleanup)

### Changes:
- Implemented automated website file usage scanner
- Cleaned up hundreds of unused files (notes, old scripts, test files, etc.)
- Reorganized file structure: moved API handlers and process files to `includes/` folder
- Deleted legacy redirect files (`dashboard.php`, `users.php`)
- Updated all references to moved files across codebase
- Created centralized version management (`includes/version.php`)
- Fixed path references in all moved files
- Updated entrypoint scanner to reflect new structure

## Previous Versions

### 0.5.0
- Login and registration system
- Dashboard/Home page
- Basic character management

### 0.4.x and earlier
- Initial development and feature additions

