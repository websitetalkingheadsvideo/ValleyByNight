# Version History

## Current Version: 0.6.7

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

