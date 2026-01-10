# Includes Directory - Agent Guide

## Package Identity

**Purpose**: Shared PHP components used across the application (headers, footers, database connection, utilities)  
**Tech**: PHP 7.4+ with MySQLi  
**Location**: `includes/` directory

## Setup & Run

### Usage
All files in `includes/` are meant to be included/required by other PHP files:
```php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/connect.php';
```

### No Direct Execution
These files are not meant to be accessed directly via URL - they're included by other pages.

## Patterns & Conventions

### File Organization
- **Headers/Footers**: `header.php`, `footer.php`, `admin_header.php`
- **Database**: `connect.php` - Database connection singleton
- **Authentication**: `login_process.php`, `register_process.php`, `verify_role.php`
- **Character Management**: `save_character.php`, `character_view_modal.php`
- **Utilities**: `version.php`, `api_get_character_names.php`

### Critical Files

#### `connect.php` - Database Connection
**Pattern**: Always use this for database access
```php
require_once __DIR__ . '/includes/connect.php';
// $conn is available after include
// Automatically loads .env file if present
// Handles PHP 8+ compatibility
```

**Key Features**:
- Loads `.env` file from project root
- Creates `$conn` mysqli connection
- Handles PHP 8+ exception mode compatibility
- Error reporting configured

#### `header.php` - Main Header Component
**Pattern**: Include at top of every page
```php
require_once __DIR__ . '/includes/header.php';
// Automatically calculates $path_prefix for CSS/JS
// Starts session if needed
// Includes Bootstrap and CSS files
```

**Key Features**:
- Calculates `$path_prefix` based on directory depth
- Loads CSS in correct order (Bootstrap → overrides → global → page-specific)
- Session management
- Version display

#### `admin_header.php` - Admin Navigation
**Pattern**: Include in admin pages
```php
require_once __DIR__ . '/../includes/admin_header.php';
// Bootstrap-based responsive navigation
// Auto-detects active page
```

#### `version.php` - Version Management
**Pattern**: Centralized version constant
```php
require_once __DIR__ . '/version.php';
$version = LOTN_VERSION;  // '0.9.31'
```

**Update Process**:
1. Update `LOTN_VERSION` in `includes/version.php`
2. Update `VERSION.md` with changelog entry

### Authentication Pattern
```php
// Login processing
require_once __DIR__ . '/includes/login_process.php';
// Handles POST from login form
// Sets session variables
// Redirects on success/failure

// Role verification
require_once __DIR__ . '/includes/verify_role.php';
verify_admin_role();  // Throws if not admin
```

### Modal Components
- **Character View**: `character_view_modal.php` - Bootstrap modal for character display
- **Position View**: `position_view_modal.php` - Bootstrap modal for position display
- **Base Modal**: `modal_base.php` - Base modal structure

**Pattern**:
```php
require_once __DIR__ . '/includes/character_view_modal.php';
// Modal HTML is included, JavaScript handles display
```

## Touch Points / Key Files

### Core Includes
- **Database**: `connect.php` - **MUST USE** for all DB operations
- **Header**: `header.php` - Standard page header
- **Footer**: `footer.php` - Standard page footer
- **Admin Header**: `admin_header.php` - Admin navigation

### Authentication
- **Login**: `login_process.php` - Processes login form
- **Registration**: `register_process.php` - Processes registration
- **Role Check**: `verify_role.php` - Role verification utilities

### Character Management
- **Save**: `save_character.php` - Saves character data
- **View Modal**: `character_view_modal.php` - Character display modal
- **Image Upload**: `upload_character_image.php` - Character image handling

### Utilities
- **Version**: `version.php` - Version constant
- **API Helpers**: `api_get_character_names.php` - API utility functions
- **Position Helper**: `camarilla_positions_helper.php` - Position utilities

## JIT Index Hints

```bash
# Find all includes
ls includes/*.php

# Find database connection usage
rg -n "require.*connect\.php\|include.*connect\.php" --type php

# Find header usage
rg -n "require.*header\.php\|include.*header\.php" --type php

# Find version usage
rg -n "LOTN_VERSION" --type php

# Find session usage
rg -n "session_start\|_SESSION" includes/
```

## Common Gotchas

- **Path Calculation**: `header.php` calculates `$path_prefix` - don't hardcode `../`
- **Session Start**: `header.php` starts session - don't call `session_start()` again
- **Database Connection**: Always use `includes/connect.php` - don't create new connections
- **.env Priority**: `.env` file takes priority over system environment variables
- **PHP 8+ Compatibility**: `connect.php` handles PHP 8+ mysqli exception mode
- **Include Paths**: Use `__DIR__` for reliable relative paths, not `dirname(__FILE__)`

## Pre-PR Checks

```bash
# Verify PHP syntax
find includes/ -name "*.php" -exec php -l {} \;

# Check for direct database connections (should use connect.php)
rg -n "new mysqli\|mysqli_connect" includes/ | grep -v "connect.php"

# Verify version constant is defined
rg -n "define.*LOTN_VERSION" includes/version.php
```
