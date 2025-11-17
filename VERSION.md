# Version History

## Current Version: 0.6.1

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

