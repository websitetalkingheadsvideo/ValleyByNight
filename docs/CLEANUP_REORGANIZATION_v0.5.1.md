# Cleanup and File Reorganization - v0.5.1

**Date:** 2025-01-XX  
**Version:** 0.5.1 (Patch increment)  
**Type:** Code organization, cleanup, and file restructuring

## Summary

Completed a comprehensive cleanup and reorganization of the VbN website project, removing unused files and organizing the codebase structure for better maintainability.

## Changes Made

### 1. Website File Usage Scanner Implementation
- **Created:** `scripts/scan_used_website_files.py`
  - Static analysis tool that traces file dependencies starting from entrypoints
  - Parses PHP includes, HTML links, JavaScript fetch calls, and CSS references
  - Automatically discovers which files are actually used by the live website
  - Respects protected directories (Prompts, .cursor, .taskmaster, images, game-lore, etc.)
  - Generates `do_not_delete.txt` manifest of files required by the website

### 2. Aggressive Cleanup Execution
- **Deleted:** Hundreds of unused files including:
  - Old programming notes and TODO lists
  - One-shot PHP scripts that uploaded data to database (already executed)
  - Old session-notes and NEXT_SESSION files
  - Test files in tests/ folder that are no longer useful
  - Summaries and documentation files that were one-time use
  - Archive files and temporary scripts

### 3. File Reorganization
**Moved to `includes/` folder:**
- `login_process.php` → `includes/login_process.php`
- `register_process.php` → `includes/register_process.php`
- `update_account.php` → `includes/update_account.php`
- `save_character.php` → `includes/save_character.php`
- `upload_character_image.php` → `includes/upload_character_image.php`
- `api_get_character_names.php` → `includes/api_get_character_names.php`

**Deleted legacy files:**
- `dashboard.php` (legacy redirect - only redirected to index.php)
- `users.php` (one-shot database setup script - already executed)

### 4. Reference Updates
Updated all references to moved files across the codebase:
- **PHP files:** Updated form actions and redirects
  - `login.php`: Form action → `includes/login_process.php`
  - `register.php`: Form action → `includes/register_process.php`
  - `account.php`: Form actions (2 places) → `includes/update_account.php`
  - `includes/login_process.php`: Redirect → `index.php` (was `dashboard.php`)
  - `admin/admin_equipment.php`: Links → `index.php` (was `dashboard.php`)

- **JavaScript files:** Updated fetch/API calls
  - `lotn_char_create.php`: Updated 3 fetch calls for save/upload/API endpoints
  - `js/modules/core/DataManager.js`: Updated save character API path
  - `js/character_image.js`: Updated upload API path
  - `js/exit-editor.js`: Updated redirect → `index.php` (was `dashboard.php`)

- **Path fixes in moved files:**
  - Fixed all `require_once` statements to use correct relative paths
  - Updated config file paths using `dirname(__DIR__)`
  - Fixed upload directory path in `upload_character_image.php`
  - Removed unused `version.php` include from `save_character.php`

### 5. Version Management
- **Created:** `includes/version.php` - Centralized version management
- **Version:** Incremented to 0.5.1 (patch increment for cleanup/organization work)
- **Updated:** Entrypoint scanner to remove `dashboard.php` and `users.php` from seed files

### 6. Project Structure
**Root directory now contains only:**
- Full-page PHP files (index.php, login.php, register.php, account.php, questionnaire.php, lotn_char_create.php, chat.php)
- Configuration files (config.env, package.json, etc.)
- Essential directories (admin/, includes/, css/, js/, images/, etc.)

**Protected directories (never scanned/deleted):**
- `Prompts/`
- `.cursor/`
- `.taskmaster/`
- `images/`
- `game-lore/`
- `Added to Database/` (if exists)
- `Books/` (if exists)
- `.git/`

## Technical Details

### File Scanner Algorithm
1. Discovers protected directories by basename matching
2. Seeds usage graph from entrypoint PHP files
3. Recursively traces dependencies:
   - PHP: `include`, `include_once`, `require`, `require_once`
   - HTML: `href`, `src`, `action` attributes
   - JavaScript: `fetch()`, `$.ajax()`, `axios.get/post/put/delete()`
   - CSS: `url()` references
4. Resolves relative paths to absolute paths under project root
5. Generates manifest of all used files
6. Deletes everything not in manifest (except protected dirs)

### Path Resolution
- All moved files updated to use `__DIR__` for reliable relative paths
- Config file references use `dirname(__DIR__)` to reach parent directory
- Upload paths properly reference parent directory structure

## Files Modified

### Core Files
- `index.php` (uses centralized version.php)
- `login.php` (form action updated)
- `register.php` (form action updated)
- `account.php` (form actions updated)
- `lotn_char_create.php` (3 API endpoint paths updated)

### JavaScript
- `js/modules/core/DataManager.js`
- `js/character_image.js`
- `js/exit-editor.js`

### Includes (moved)
- `includes/login_process.php`
- `includes/register_process.php`
- `includes/update_account.php`
- `includes/save_character.php`
- `includes/upload_character_image.php`
- `includes/api_get_character_names.php`

### Admin
- `admin/admin_equipment.php`

### Scripts
- `scripts/scan_used_website_files.py` (new utility)

### Configuration
- `includes/version.php` (new centralized version file)

## Testing Recommendations

1. Verify all login/registration flows work correctly
2. Test character creation and saving functionality
3. Verify image upload functionality
4. Check all form submissions redirect properly
5. Confirm API endpoints return correct data
6. Verify no broken links in admin panel

## Impact

- **Codebase size:** Significantly reduced by removing hundreds of unused files
- **Organization:** Clear separation between full pages and utility files
- **Maintainability:** Easier to find and manage website components
- **Clarity:** Root directory now clearly shows only navigable pages

## Notes

- The cleanup script (`scripts/scan_used_website_files.py`) can be re-run in the future to maintain cleanliness
- The manifest file (`do_not_delete.txt`) documents which files are actually used
- All deleted files can be recovered from git history if needed

