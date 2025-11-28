# Version History

## Current Version: 0.8.4

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

