# Version History

## Current Version: 0.6.11

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

