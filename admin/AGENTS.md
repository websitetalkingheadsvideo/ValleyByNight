# Admin Panel - Agent Guide

## Package Identity

**Purpose**: Administrative interface for managing characters, items, locations, positions, and game data  
**Tech**: PHP with Bootstrap, Vanilla JavaScript, Supabase REST  
**Location**: `admin/` directory

## Setup & Run

### Access
- **URL**: `http://your-local-url/admin/admin_panel.php`
- **Authentication**: Requires admin role (checked via `includes/verify_role.php`)
- **Navigation**: Uses `includes/admin_header.php` for consistent navigation

### Development
```bash
# No special setup - uses the project's Supabase environment
# Ensure .env file is configured (see root AGENTS.md)
```

## Patterns & Conventions

### File Organization
- **CRUD Operations**: `api_*.php` files handle API endpoints
- **View Pages**: `admin_*.php` files for display/management interfaces
- **Navigation**: All admin pages include `includes/admin_header.php`

### Naming Conventions
- **API Files**: `api_<resource>_crud.php` or `api_<action>_<resource>.php`
  - ✅ Example: `api_admin_items_crud.php`, `api_locations.php`
- **Admin Pages**: `admin_<resource>.php` or `<resource>_admin_panel.php`
  - ✅ Example: `admin_panel.php`, `admin_items.php`, `ghoul_admin_panel.php`
- **Viewer Pages**: `<resource>_viewer.php` or `view_<resource>_api.php`
  - ✅ Example: `boon_agent_viewer.php`, `view_character_api.php`

### API Pattern
All API endpoints follow this structure:
```php
<?php
require_once __DIR__ . '/../includes/supabase_client.php';
require_once __DIR__ . '/../includes/verify_role.php';

// Verify admin role
verify_admin_role();

header('Content-Type: application/json');

// Handle request method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET logic
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST logic with validation
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // PUT logic
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // DELETE logic
}

$rows = supabase_table_get('table', ['select' => '*', 'id' => 'eq.' . $id]);
?>
```

### Example Files
- ✅ **CRUD API**: `api_admin_items_crud.php` - Full CRUD pattern
- ✅ **View API**: `view_character_api.php` - Read-only API pattern
- ✅ **Admin Page**: `admin_items.php` - Full admin interface with Bootstrap
- ✅ **Navigation**: `includes/admin_header.php` - Bootstrap nav component

### Bootstrap Integration
- Use Bootstrap grid system: `row`, `col-*` classes
- Admin navigation uses responsive buttons: `btn btn-outline-danger btn-sm`
- Tables use Bootstrap table classes: `table table-striped table-hover`
- Modals use Bootstrap modal components (see `includes/character_view_modal.php`)

## Touch Points / Key Files

### Core Admin Files
- **Main Dashboard**: `admin_panel.php` - Character management hub
- **Navigation**: `includes/admin_header.php` - Shared admin navigation
- **Role Verification**: `includes/verify_role.php` - Admin access control

### Resource Management
- **Characters**: `admin_panel.php`, `view_character_api.php`, `delete_character_api.php`
- **Items**: `admin_items.php`, `api_admin_items_crud.php`
- **Locations**: `admin_locations.php`, `api_admin_locations_crud.php`
- **Equipment**: `admin_equipment.php`, `api_admin_equipment_crud.php`
- **Positions**: `admin_camarilla_positions.php`, `api_add_position.php`

### Specialized Admin Tools
- **Sire/Childe**: `admin_sire_childe.php`, `api_sire_childe.php`
- **Boon Agent**: `boon_agent_viewer.php`, `boon_ledger.php`
- **NPC Briefing**: `admin_npc_briefing.php`, `api_npc_briefing.php`
- **Questionnaire**: `questionnaire_admin.php`

## JIT Index Hints

```bash
# Find admin API endpoints
rg -n "api_" admin/

# Find admin pages
rg -n "admin_.*\.php" admin/

# Find Supabase usage in admin
rg -n "supabase_table_get|supabase_rest_request" admin/

# Find Bootstrap classes in admin
rg -n "class=.*btn\|class=.*table\|class=.*modal" admin/
```

## Common Gotchas

- **Path Prefixes**: Admin files are in subdirectory - use `__DIR__ . '/../includes/` for includes
- **Role Verification**: Always call `verify_admin_role()` before admin operations
- **JSON Responses**: Set `Content-Type: application/json` header for API endpoints
- **Error Handling**: Return JSON error objects: `{"error": "message"}` not plain text
- **Bootstrap Modals**: Use `includes/character_view_modal.php` as pattern for modal components

## Pre-PR Checks

```bash
# Verify PHP syntax
find admin/ -name "*.php" -exec php -l {} \;

# Check for legacy DB helpers (should be gone)
rg -n "db_fetch_|db_select|db_execute" admin/

# Verify all API files have role verification
rg -n "verify_admin_role\|verify_role" admin/api_*.php
```
