# Valley by Night (VbN) - Agent Guide

## Project Snapshot

**Repository Type**: Single PHP web application (not monorepo)  
**Primary Tech Stack**: PHP 7.4+, MySQL (remote), Bootstrap 5.3.2, Vanilla JavaScript  
**Database**: Remote MySQL at `vdb5.pit.pair.com` (no local DB setup)  
**Sub-packages**: Specialized agent modules in `agents/` directory, each with its own AGENTS.md

## Root Setup Commands

### Development Environment
```bash
# No build step required - PHP is interpreted
# Ensure PHP 7.4+ is installed and Apache is running

# Verify PHP version
php -v

# Check database connection (requires .env file)
php test_db_connection.php
```

### Environment Configuration
```bash
# Copy .env.example to .env (if not exists)
# Edit .env and set DB_PASSWORD=your_password
# See LOCAL_DEVELOPMENT.md for full setup
```

### Quick Verification
```bash
# Test database connection
php test_db_connection.php

# Check PHP syntax (if php -l available)
find . -name "*.php" -not -path "./node_modules/*" -not -path "./venv/*" -exec php -l {} \;
```

## Universal Conventions

### Code Style
- **PHP**: Follow WordPress PHP coding standards (from rules)
- **Strict Typing**: Use `declare(strict_types=1);` at top of PHP files
- **CSS**: External files in `css/` folder only (no embedded `<style>`)
- **JavaScript**: External files in `js/` folder only (no embedded `<script>`)
- **Bootstrap**: Foundation framework - enhance, never override core behavior
- **Styling Guidelines**: See [VbN_styles.md](VbN_styles.md) for comprehensive visual style guide, color system, typography, component patterns, and border/border-radius standards

### File Organization
- **CSS**: Component-based (`css/header.css`, `css/login.css`) or page-specific
  - **Style Reference**: Always consult [VbN_styles.md](VbN_styles.md) when creating new pages or styling components
  - Use CSS variables from `:root` defined in `css/global.css`
  - Follow border-radius (`0.75rem`) and border-width standards (2px for cards/buttons, 3px for modals)
- **JavaScript**: Modular structure in `js/modules/` (core, systems, ui)
- **PHP Includes**: Shared components in `includes/`
- **Path Prefixes**: Use `$path_prefix` variable for relative paths in subdirectories

### Version Management
- Version defined in `includes/version.php` as `LOTN_VERSION` constant
- Follow version incrementing rules: Patch (Z) for fixes, Minor (Y) for working features
- Update `VERSION.md` with each release

### Security & Secrets
- **Never commit `.env` files** - contains database credentials
- Database password stored in `.env` file (takes priority over system env vars)
- Use prepared statements for all database queries (via `includes/connect.php`)
- Session management via PHP sessions (started in `includes/header.php`)

## JIT Index (what to open, not what to paste)

### Package Structure
- **Main Application**: Root PHP files → [see Root Patterns below]
- **Admin Panel**: `admin/` → [see admin/AGENTS.md](admin/AGENTS.md)
- **Agent Modules**: `agents/` → [see agents/AGENTS.md](agents/AGENTS.md)
- **Shared Components**: `includes/` → [see includes/AGENTS.md](includes/AGENTS.md)
- **Stylesheets**: `css/` → [see css/AGENTS.md](css/AGENTS.md)
  - **Visual Style Guide**: [VbN_styles.md](VbN_styles.md) - Comprehensive reference for colors, typography, components, and standards
- **JavaScript**: `js/` → [see js/AGENTS.md](js/AGENTS.md)
- **Reference Data**: `reference/` → Static reference materials (books, characters, locations)

### Quick Find Commands
```bash
# Find a PHP function
rg -n "function functionName" --type php

# Find a CSS class
rg -n "\.className" css/

# Find a JavaScript function
rg -n "function functionName" js/

# Find API endpoints (api_*.php files)
rg -n "api_" admin/ agents/

# Find database queries
rg -n "mysqli_query\|->query\|prepare" --type php

# Find Bootstrap usage
rg -n "class=.*btn\|class=.*card\|class=.*modal" --type php
```

### Entry Points
- `index.php` - Main dashboard/homepage
- `login.php` - User authentication
- `register.php` - User registration
- `account.php` - User account management
- `questionnaire.php` - Character questionnaire
- `lotn_char_create.php` - Character creation interface
- `chat.php` - Chat interface
- `admin/admin_panel.php` - Admin dashboard

## Root Patterns

### PHP File Structure
```php
<?php
declare(strict_types=1);

// Session start (if needed)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includes
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/header.php';

// Page logic here

// Footer
require_once __DIR__ . '/includes/footer.php';
?>
```

### Path Prefix Calculation
For files in subdirectories, use the path prefix pattern from `includes/header.php`:
```php
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$segment_count = substr_count(trim($script_dir, '/'), '/') + 1;
$path_prefix = str_repeat('../', $segment_count);
```

### Database Connection
Always use `includes/connect.php`:
```php
require_once __DIR__ . '/includes/connect.php';
// $conn is available after include
```

## Definition of Done

Before creating a PR:
- [ ] PHP syntax is valid (no parse errors)
- [ ] Database queries use prepared statements
- [ ] CSS is in external files (not embedded)
- [ ] JavaScript is in external files (not embedded)
- [ ] Bootstrap enhancements don't override core behavior
- [ ] **Styling compliance**: New pages/components follow [VbN_styles.md](VbN_styles.md) guidelines:
  - [ ] CSS variables used instead of hardcoded colors
  - [ ] Border-radius standardized to `0.75rem` (or `1rem` for emphasis)
  - [ ] Border widths follow standards (2px for cards/buttons, 3px for modals)
  - [ ] No `text-muted`, `opacity-*` utilities on text, or `form-text` class
  - [ ] Text is fully opaque with sufficient contrast
  - [ ] Focus states included for accessibility
- [ ] Version updated in `includes/version.php` and `VERSION.md` if needed
- [ ] `.env` file not committed (already in `.gitignore`)
- [ ] Path prefixes work correctly from subdirectories
