# Version History

## Current Version: 0.6.3

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

