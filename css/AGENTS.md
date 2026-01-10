# CSS Directory - Agent Guide

## Package Identity

**Purpose**: External stylesheets for the application (Bootstrap-enhanced, component-based)  
**Tech**: CSS with Bootstrap 4 as foundation  
**Location**: `css/` directory

## Setup & Run

### CSS Loading Order
CSS files are loaded in a specific order via `includes/header.php`:

1. **Bootstrap CDN** - Base framework styles
2. **bootstrap-overrides.css** - Neutralizes Bootstrap reset while preserving design
3. **global.css** - Global styles, CSS variables, shared components
4. **Page-specific CSS** (via `$extra_css` array)
5. **Component CSS** (e.g., `modal_fullscreen.css`)

### Development
```bash
# No build step - CSS is loaded directly
# Changes take effect on page refresh
```

## Patterns & Conventions

### File Organization

#### Component-Based (Shared)
- `header.css` - Header component styles
- `footer.css` - Footer component styles (if exists)
- `modal_fullscreen.css` - Modal component styles
- `modal.css` - Standard modal styles

#### Page-Specific
- `login.css` - Login page styles
- `dashboard.css` - Dashboard styles
- `admin_panel.css` - Admin panel styles
- `character_view.css` - Character view styles
- `rituals_view.css` - Rituals display styles

#### Admin-Specific
- `admin_*.css` - Admin panel page styles
  - `admin_panel.css`, `admin_items.css`, `admin_locations.css`, etc.

### Bootstrap Integration Rules

**CRITICAL**: Bootstrap is the foundation - enhance, never override.

#### ✅ DO: Enhance Bootstrap
```css
/* Add custom spacing Bootstrap doesn't provide */
#mainModal.modal {
    padding: 2rem !important;
}

/* Constrain width while respecting Bootstrap */
#mainModal .modal-dialog {
    max-width: min(calc(100vw - 4rem), 1280px);
}
```

#### ❌ DON'T: Override Bootstrap
```css
/* DON'T break Bootstrap's centering */
#mainModal .modal-dialog {
    margin: 0 !important;  /* Breaks Bootstrap flexbox */
    display: block !important;  /* Overrides Bootstrap display */
}
```

### CSS Variable Pattern
Use CSS custom properties for dynamic values from PHP:
```css
.clan-badge {
    background-color: var(--clan-color);
}
```

In PHP:
```php
echo '<span class="clan-badge" style="--clan-color:' . $color . ';">';
```

### Naming Conventions
- **Files**: Lowercase with hyphens: `admin-panel.css` not `adminPanel.css`
- **Classes**: Follow Bootstrap naming: `btn`, `card`, `modal`, etc.
- **IDs**: Use descriptive names: `#mainModal`, `#characterView`

### File Examples
- ✅ **Bootstrap Overrides**: `bootstrap-overrides.css` - Neutralizes reset
- ✅ **Global Styles**: `global.css` - Base styles and variables
- ✅ **Modal Component**: `modal_fullscreen.css` - Fullscreen modal pattern
- ✅ **Page-Specific**: `login.css` - Login page styles
- ✅ **Admin Styles**: `admin_panel.css` - Admin interface styles

## Touch Points / Key Files

### Core Stylesheets
- **Bootstrap Overrides**: `css/bootstrap-overrides.css` - Foundation adjustments
- **Global Styles**: `css/global.css` - Base styles, variables, shared components
- **Modal Styles**: `css/modal_fullscreen.css` - Modal component

### Page Styles
- **Login**: `css/login.css`
- **Dashboard**: `css/dashboard.css`
- **Character View**: `css/character_view.css`
- **Rituals**: `css/rituals_view.css`

### Admin Styles
- **Admin Panel**: `css/admin_panel.css`
- **Admin Items**: `css/admin_items.css`
- **Admin Locations**: `css/admin_locations.css`
- **Admin Equipment**: `css/admin_equipment.css`

## JIT Index Hints

```bash
# Find CSS files
ls css/*.css

# Find CSS class usage in PHP
rg -n "class=.*" --type php | head -20

# Find CSS variable usage
rg -n "var\(--" css/

# Find !important usage (should be minimal)
rg -n "!important" css/

# Find Bootstrap class overrides (should be avoided)
rg -n "\.btn\|\.card\|\.modal" css/ | grep -v "bootstrap-overrides"
```

## Common Gotchas

- **No Embedded Styles**: All CSS must be in external files (see root rules)
- **Bootstrap First**: Bootstrap is foundation - enhance, don't override
- **Path Prefixes**: CSS files use `$path_prefix` from `header.php` for subdirectories
- **Loading Order**: CSS loading order matters - don't change the sequence in `header.php`
- **!important Usage**: Only use `!important` for enhancements, not overrides
- **Text-Muted**: Never use `text-muted` class (poor contrast) - use `opacity-75` instead
- **Dynamic Styles**: Use CSS custom properties for PHP-generated values, not inline styles

## Pre-PR Checks

```bash
# Verify CSS syntax (if css-validator available)
# Or manually check in browser dev tools

# Check for embedded styles (should be none)
rg -n "<style>" --type php | grep -v "<!--"

# Verify Bootstrap isn't overridden
rg -n "\.modal-dialog.*margin.*0.*!important" css/
rg -n "\.btn.*display.*block.*!important" css/
```
