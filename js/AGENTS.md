# JavaScript Directory - Agent Guide

## Package Identity

**Purpose**: Client-side JavaScript for application interactivity (modular, vanilla JS)  
**Tech**: Vanilla JavaScript (ES6+), modular architecture  
**Location**: `js/` directory

## Setup & Run

### Loading
JavaScript files are loaded via `<script>` tags in PHP files or `includes/header.php`:
```html
<script src="<?php echo $path_prefix; ?>js/script.js"></script>
```

### Development
```bash
# No build step - JavaScript is loaded directly
# Changes take effect on page refresh
# Use browser dev tools for debugging
```

## Patterns & Conventions

### File Organization

#### Modular Structure
```
js/
├── modules/
│   ├── core/           # Core utilities
│   │   ├── DataManager.js
│   │   ├── EventManager.js
│   │   ├── StateManager.js
│   │   └── UIManager.js
│   ├── systems/        # Game systems
│   │   ├── AbilitySystem.js
│   │   ├── DisciplineSystem.js
│   │   ├── TraitSystem.js
│   │   └── ...
│   ├── ui/             # UI components
│   │   ├── TabManager.js
│   │   └── PreviewManager.js
│   └── main.js         # Module initialization
├── (page-specific files)
└── (component files)
```

#### Page-Specific
- `lotn_char_create.js` - Character creation interface
- `wraith_char_create.js` - Wraith character creation
- `admin_*.js` - Admin panel scripts

#### Component-Specific
- `character_view_modal.js` - Character modal interactions
- `modal_fullscreen.js` - Fullscreen modal handling
- `modal_a11y.js` - Modal accessibility

### Module Pattern
Modules use ES6 module syntax or IIFE pattern:
```javascript
// modules/core/DataManager.js
class DataManager {
    static save(data) {
        // Save logic
    }
}

// Export if using modules, or attach to window
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataManager;
} else {
    window.DataManager = DataManager;
}
```

### Initialization Pattern
```javascript
// Wait for DOM
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modules
    if (typeof DataManager !== 'undefined') {
        DataManager.init();
    }
});
```

### API Call Pattern
```javascript
// Use fetch for API calls
fetch('api_endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => {
    // Handle response
})
.catch(error => {
    console.error('Error:', error);
});
```

### Naming Conventions
- **Files**: Lowercase with hyphens: `character-view-modal.js` or camelCase: `characterViewModal.js`
- **Classes**: PascalCase: `DataManager`, `TabManager`
- **Functions**: camelCase: `saveCharacter()`, `loadData()`
- **Constants**: UPPER_SNAKE_CASE: `API_BASE_URL`

### File Examples
- ✅ **Core Module**: `js/modules/core/DataManager.js` - Data management pattern
- ✅ **System Module**: `js/modules/systems/TraitSystem.js` - Game system pattern
- ✅ **UI Module**: `js/modules/ui/TabManager.js` - UI component pattern
- ✅ **Page Script**: `js/lotn_char_create.js` - Page-specific script
- ✅ **Modal Handler**: `js/modal_fullscreen.js` - Modal interaction pattern

## Touch Points / Key Files

### Core Modules
- **Data Manager**: `js/modules/core/DataManager.js` - Data persistence
- **Event Manager**: `js/modules/core/EventManager.js` - Event handling
- **State Manager**: `js/modules/core/StateManager.js` - State management
- **UI Manager**: `js/modules/core/UIManager.js` - UI utilities

### System Modules
- **Ability System**: `js/modules/systems/AbilitySystem.js`
- **Discipline System**: `js/modules/systems/DisciplineSystem.js`
- **Trait System**: `js/modules/systems/TraitSystem.js`
- **Background System**: `js/modules/systems/BackgroundSystem.js`

### UI Modules
- **Tab Manager**: `js/modules/ui/TabManager.js` - Tab switching
- **Preview Manager**: `js/modules/ui/PreviewManager.js` - Preview display

### Page Scripts
- **Character Creation**: `js/lotn_char_create.js`
- **Admin Panel**: `js/admin_panel.js`
- **Chat**: `js/chat.js`

## JIT Index Hints

```bash
# Find JavaScript files
find js/ -name "*.js"

# Find function definitions
rg -n "function \w+\|const \w+.*=.*function\|class \w+" js/

# Find API calls
rg -n "fetch\|XMLHttpRequest\|\.ajax" js/

# Find event listeners
rg -n "addEventListener\|\.on\(|\.click\|\.submit" js/

# Find module exports
rg -n "module\.exports\|export " js/
```

## Common Gotchas

- **No Embedded Scripts**: All JavaScript must be in external files (see root rules)
- **Path Prefixes**: Use `$path_prefix` from PHP for script src paths
- **DOM Ready**: Always wait for `DOMContentLoaded` before accessing DOM elements
- **Error Handling**: Use try/catch for async operations, handle fetch errors
- **Module Loading**: Check if modules exist before using: `if (typeof DataManager !== 'undefined')`
- **Bootstrap Integration**: Use Bootstrap's JavaScript API for modals, not custom implementations
- **No jQuery**: Project uses vanilla JavaScript - don't add jQuery dependencies

## Pre-PR Checks

```bash
# Verify JavaScript syntax (if node available)
node -c js/file.js

# Check for embedded scripts (should be none)
rg -n "<script>" --type php | grep -v "src=" | grep -v "<!--"

# Find console.log (remove in production)
rg -n "console\.log" js/

# Verify error handling in fetch calls
rg -n "fetch" js/ | xargs grep -L "\.catch"
```
