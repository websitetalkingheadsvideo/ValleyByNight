# Valley by Night (VbN) - Visual Style Guide

**Version:** 1.0  
**Last Updated:** 2025-01-30  
**Purpose:** Comprehensive reference document for visual design consistency across the VbN application

---

## Table of Contents

1. [Core Design Philosophy](#core-design-philosophy)
2. [Color System](#color-system)
3. [Typography](#typography)
4. [Accessibility & Readability Rules](#accessibility--readability-rules)
5. [Component Patterns](#component-patterns)
6. [Layout & Spacing](#layout--spacing)
7. [Bootstrap Integration](#bootstrap-integration)
8. [Do's and Don'ts](#dos-and-donts)
9. [Common Violations to Fix](#common-violations-to-fix)

---

## Core Design Philosophy

### Visual Identity
- **Gothic-Noir Aesthetic**: Dark, moody, atmospheric
- **Phoenix 1994 Setting**: Period-accurate details and environments
- **Emotional Restraint**: Subtle, controlled expressions and movement
- **Desert Modern**: Concrete, glass, dust, sparse vegetation

### Design Principles
1. **Dark backgrounds with gold highlights** - Never pure white or neon colors
2. **Red used strategically** - For danger, emphasis, or clan cues
3. **Parchment text for readability** - Always fully opaque and high contrast
4. **Serif typography dominant** - Minimal sans-serif use
5. **Gradient-based depth** - Radial and linear gradients create atmospheric depth
6. **Consistent spacing and hierarchy** - Clear visual organization

---

## Color System

### Primary Color Palette (CSS Variables)

```css
:root {
    --bg-dark: #0d0606;           /* Gothic Black - darkest background */
    --bg-darker: #1a0f0f;         /* Dusk Brown-Black - standard dark background */
    --deep-maroon: #2a1515;       /* Deep Maroon - card backgrounds, panels */
    --dusk-brown-black: #1a0f0f;  /* Alias for bg-darker */
    --blood-red: #8B0000;         /* Blood Red - primary accent, borders, danger */
    --muted-gold: #d4b06d;        /* Muted Gold - text-mid, active borders, accents */
    --teal-moonlight: #0B3C49;    /* Teal Moonlight - info panels, alternate accents */
    --text-light: #f5e6d3;        /* Parchment Light - primary text on dark */
    --text-mid: #d4b06d;          /* Muted Gold - secondary text (same as muted-gold) */
}
```

### Color Usage Guidelines

#### Backgrounds
- **Primary Dark Background**: Use `var(--bg-darker)` or `var(--dusk-brown-black)` for main page backgrounds
- **Card Backgrounds**: Use linear gradient from `var(--deep-maroon)` to `var(--dusk-brown-black)`
- **Body Background**: Use gradient `linear-gradient(135deg, #0d0606 0%, #1a0f0f 50%, #0d0606 100%)` with `background-attachment: fixed`

#### Text Colors
- **Primary Text on Dark**: `var(--text-light)` (#f5e6d3) - Always fully opaque
- **Secondary Text on Dark**: `var(--text-mid)` (#d4b06d) - Muted gold, fully opaque
- **Never use**: `text-muted`, `opacity-*` utilities on text, or Bootstrap's `form-text` class

#### Accent Colors
- **Blood Red** (`#8B0000`): Borders, buttons, danger states, emphasis, clan accents
- **Muted Gold** (`#d4b06d`): Active states, selected items, secondary text, hover highlights
- **Teal Moonlight** (`#0B3C49`): Info panels, alternate borders, special sections

#### Table Backgrounds
- **Table Gradient**: `radial-gradient(circle at center, rgba(139, 0, 0, 0.4) 0%, rgba(139, 0, 0, 0.2) 40%, rgba(26, 15, 15, 0.6) 100%)`
- **Table Header**: `linear-gradient(135deg, #8B0000 0%, #600000 100%)` with `var(--text-light)` text
- **Table Rows**: `rgba(139, 0, 0, 0.1)` background, `rgba(139, 0, 0, 0.2)` on hover

---

## Typography

### Font Families (CSS Variables)

```css
:root {
    --font-body: 'Source Serif Pro', serif;           /* Body text, descriptions */
    --font-brand: 'IM Fell English', serif;          /* Brand/logo, major headings */
    --font-brand-sc: 'IM Fell English SC', serif;    /* Small caps variant for branding */
    --font-title: 'Libre Baskerville', serif;        /* Section titles, labels, buttons */
}
```

### Font Usage

#### Headings (h1, h2, h3, etc.)
- **Font**: `var(--font-brand)` (IM Fell English)
- **Fallback**: `'IM Fell English', serif`
- **Color**: `var(--text-light)` (#f5e6d3)
- **Weight**: Bold for major headings, normal for subheadings
- **Text Shadow**: Use `text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8)` for depth

#### Body Text
- **Font**: `var(--font-body)` (Source Serif Pro)
- **Fallback**: `'Source Serif Pro', 'Times New Roman', serif`
- **Color**: `var(--text-light)` or `var(--text-mid)` depending on context
- **Size**: 16px base (prevents iOS zoom on form inputs)
- **Line Height**: 1.6

#### Labels & Form Elements
- **Font**: `var(--font-title)` (Libre Baskerville)
- **Fallback**: `'Libre Baskerville', serif`
- **Color**: `var(--text-light)`
- **Weight**: 600 for labels
- **Size**: Typically 0.85em - 1em`

#### Code/Monospace
- **Font**: `'Source Code Pro', 'Courier New', monospace`
- **Color**: `var(--text-light)`
- **Background**: Dark backgrounds with subtle borders

### Typography Hierarchy

```css
/* Site Title */
.site-title {
    font-family: var(--font-brand);
    font-size: 2.2em;
    color: var(--text-light);
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8), 0 0 10px rgba(139, 0, 0, 0.3);
}

/* Section Headings */
.section-heading {
    font-family: var(--font-title);
    font-size: 2.2em;
    color: var(--text-light);
    border-bottom: 2px solid var(--blood-red);
    padding-bottom: 10px;
}

/* Card Titles */
.card-title {
    font-family: var(--font-title);
    color: var(--text-light);
}

/* Body Text */
body, .card-text {
    font-family: var(--font-body);
    color: var(--text-mid); /* or var(--text-light) depending on context */
}
```

---

## Accessibility & Readability Rules

### ⚠️ CRITICAL: Text Readability Rules

**NEVER use the following on text elements:**
- ❌ `text-muted` Bootstrap class
- ❌ `opacity-75`, `opacity-50`, `opacity-25` utilities on text
- ❌ Bootstrap's `form-text` class (applies grey color by default)
- ❌ Any opacity utility combined with text elements
- ❌ Grey/unreadable helper text

**ALWAYS ensure:**
- ✅ Text is **fully opaque** (opacity: 1)
- ✅ Sufficient contrast ratio (WCAG AA minimum 4.5:1 for normal text)
- ✅ Use `var(--text-light)` or `var(--text-mid)` for dark backgrounds
- ✅ Helper text uses plain `<div>` or `<p>` with readable colors, NOT `<small class="form-text">`

### Correct Text Patterns

```html
<!-- ✅ CORRECT: Fully readable text on dark background -->
<div class="bg-dark">
    <p class="text-white">Readable primary text</p>
    <div class="text-light mt-1">Readable helper text (no form-text class)</div>
</div>

<!-- ✅ CORRECT: Helper text with readable color -->
<div class="mt-1 text-white">Enter your account username.</div>

<!-- ❌ WRONG: Opacity reduces readability -->
<p class="text-white opacity-75">Unreadable faded text</p>

<!-- ❌ WRONG: Bootstrap form-text makes text grey -->
<small class="form-text">Unreadable grey helper text</small>

<!-- ❌ WRONG: text-muted has poor contrast on dark -->
<p class="text-muted">Hard to read on dark backgrounds</p>
```

### Placeholder & Form Input Text

- **Placeholder Text**: Handled by CSS in `bootstrap-overrides.css` - uses `var(--text-light)` color with `opacity: 1`
- **Default Select Options**: Uses `var(--text-light)` color for readability
- **Form Controls**: Dark background `rgba(26, 15, 15, 0.6)` with `var(--text-light)` text
- **Focus States**: `var(--blood-red)` border with `box-shadow: 0 0 10px rgba(139, 0, 0, 0.3)`

### Focus States & Keyboard Navigation

```css
/* Visible focus indicators - CRITICAL for accessibility */
:focus-visible,
button:focus,
a:focus,
input:focus,
select:focus,
textarea:focus {
    outline: 2px solid var(--blood-red);
    outline-offset: 2px;
    box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.25);
}
```

---

## Component Patterns

### Cards

**Default Card Styling:**
```css
.card {
    background: linear-gradient(135deg, var(--deep-maroon) 0%, var(--dusk-brown-black) 100%);
    border: 2px solid var(--blood-red);
    border-radius: 0.75rem; /* Art Bible spec: 0.75rem - 1rem */
    box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
    color: var(--text-mid); /* or var(--text-light) for body text */
    transition: all 0.3s ease;
}

.card:hover:not(.disabled):not(.opacity-50) {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(139, 0, 0, 0.5);
    border-color: #b30000;
}
```

**Active Card (Selected/Highlighted):**
```css
.card.active,
.card.card-active {
    border: 2px solid var(--muted-gold);
    box-shadow: 0 4px 15px rgba(212, 176, 109, 0.3);
}

.card.active:hover {
    border-color: #e5c77d;
    box-shadow: 0 6px 25px rgba(212, 176, 109, 0.5);
}
```

**Card Elements:**
- `.card-header`: Transparent background, `var(--text-light)` color, border-bottom
- `.card-body`: `var(--text-mid)` or `var(--text-light)` text color
- `.card-footer`: Transparent background, `var(--text-mid)` color, border-top

### Buttons

**Primary Button:**
```css
.btn-primary {
    background: linear-gradient(135deg, var(--blood-red) 0%, #600000 100%);
    border: 2px solid #b30000;
    color: var(--text-light);
    border-radius: 0.75rem; /* Standard: 0.75rem - 1rem */
    font-family: var(--font-title);
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover,
.btn-primary:focus {
    background: linear-gradient(135deg, #b30000 0%, var(--blood-red) 100%);
    border-color: #b30000;
    color: var(--text-light);
    box-shadow: 0 5px 20px rgba(139, 0, 0, 0.4);
}
```

**Secondary Button:**
```css
.btn-secondary {
    background: transparent;
    border: 2px solid var(--muted-gold);
    color: var(--muted-gold);
    border-radius: 0.75rem; /* Standard: 0.75rem - 1rem */
    font-family: var(--font-title);
    font-weight: 600;
}

.btn-secondary:hover {
    background: var(--muted-gold);
    color: var(--bg-dark);
    border-color: var(--muted-gold);
    box-shadow: 0 5px 20px rgba(212, 176, 109, 0.4);
}
```

**Danger Button:**
```css
.btn-danger {
    background: linear-gradient(135deg, #660000 0%, #4a0000 100%);
    border: 2px solid #770000;
    color: var(--text-light);
    border-radius: 0.75rem; /* Standard: 0.75rem - 1rem */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5), 0 2px 8px rgba(102, 0, 0, 0.4);
}

.btn-danger:hover {
    background: linear-gradient(135deg, var(--blood-red) 0%, #660000 100%);
    border-color: #990000;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6), 0 3px 10px rgba(139, 0, 0, 0.5);
    transform: translateY(-2px);
}
```

### Modals

**Modal Styling:**
```css
.modal-content {
    background: linear-gradient(135deg, var(--deep-maroon) 0%, var(--dusk-brown-black) 100%);
    border: 3px solid var(--muted-gold); /* Art Bible spec: 3px gold border */
    border-radius: 0.75rem;
    color: var(--text-light);
}

.modal-header {
    border-bottom: 1px solid rgba(139, 0, 0, 0.35);
    background: transparent;
    color: var(--text-light);
    font-family: var(--font-title);
}

.modal-body {
    color: var(--text-mid); /* or var(--text-light) */
}

.modal-footer {
    border-top: 1px solid rgba(139, 0, 0, 0.35);
    background: transparent;
}
```

**Fullscreen Modal Support:**
- Use `.modal.fullscreen` class for fullscreen modals
- Fullscreen modals remove padding and use 100vh height
- Maintains same styling as regular modals

### Forms

**Form Controls:**
```css
.form-control,
.form-select {
    background-color: rgba(26, 15, 15, 0.6);
    border: 2px solid rgba(139, 0, 0, 0.4);
    border-radius: 0.75rem; /* Standard: 0.75rem - 1rem */
    color: var(--text-light);
    font-family: var(--font-body);
}

.form-control:focus,
.form-select:focus {
    background-color: rgba(26, 15, 15, 0.8);
    border-color: var(--blood-red);
    box-shadow: 0 0 10px rgba(139, 0, 0, 0.3);
    color: var(--text-light);
}

/* Placeholder text - MUST be readable */
.form-control::placeholder {
    color: var(--text-light);
    opacity: 1; /* Fully opaque */
}

/* Default select option - MUST be readable */
.form-select option:first-child,
.form-select option[value=""] {
    color: var(--text-light);
}

.form-label {
    font-family: var(--font-title);
    color: var(--text-light);
    font-weight: 600;
}
```

**Helper Text (CRITICAL - No form-text class):**
```html
<!-- ✅ CORRECT: Readable helper text -->
<div class="mt-1 text-white">Enter your account username.</div>

<!-- ❌ WRONG: Bootstrap form-text makes text grey -->
<small class="form-text">Unreadable grey text</small>
```

### Info Panels

**Teal Moonlight Info Panels:**
```css
.info-panel-teal {
    background: linear-gradient(135deg, rgba(11, 60, 73, 0.2) 0%, rgba(26, 15, 15, 0.4) 100%);
    border: 2px solid var(--teal-moonlight);
    border-radius: 0.75rem; /* Standard: 0.75rem - 1rem */
    padding: 1.5rem;
}
```

### Tables

**Table Styling:**
```css
.table-dark,
.table {
    background: radial-gradient(circle at center, rgba(139, 0, 0, 0.4) 0%, rgba(139, 0, 0, 0.2) 40%, rgba(26, 15, 15, 0.6) 100%);
}

.table-dark thead th,
.table thead th {
    background: linear-gradient(135deg, #8B0000 0%, #600000 100%);
    color: var(--text-light);
}

.table-dark tbody tr,
.table tbody tr {
    background: rgba(139, 0, 0, 0.1);
}

.table-dark tbody tr:hover,
.table tbody tr:hover {
    background: rgba(139, 0, 0, 0.2);
}
```

---

## Border & Border Radius Standards

### Border Width Standards
- **Cards, Buttons, Forms, Info Panels**: `2px solid` - Standard interactive elements
- **Modals**: `3px solid` - Prominent focus for modal dialogs
- **Separators/Dividers**: `1px solid` or `2px solid` - Context-dependent (section dividers use 2px, subtle dividers use 1px)

### Border Radius Standards (Art Bible Spec)
- **Standard**: `0.75rem` (12px) - Used for cards, buttons, forms, info panels, images
- **Alternative**: `1rem` (16px) - For larger panels or special emphasis
- **Never use**: Pixel values like `5px`, `8px`, etc. - Always use rem units for consistency and scalability

### Border Color Standards
- **Default cards**: `2px solid var(--blood-red)`
- **Active/selected cards**: `2px solid var(--muted-gold)`
- **Modals**: `3px solid var(--muted-gold)`
- **Info panels**: `2px solid var(--teal-moonlight)`
- **Form controls (default)**: `2px solid rgba(139, 0, 0, 0.4)`
- **Form controls (focus)**: `2px solid var(--blood-red)`

## Layout & Spacing

### Page Structure

```html
<div class="page-wrapper">
    <header class="valley-header">
        <!-- Header content -->
    </header>
    
    <main id="main-content" class="main-wrapper" role="main">
        <!-- Main content -->
    </main>
    
    <footer class="valley-footer">
        <!-- Footer content -->
    </footer>
</div>
```

### Container Usage

- **Bootstrap `.container`**: Max-width with auto margins, responsive padding
- **Bootstrap `.container-fluid`**: Full width with responsive padding
- **Responsive Containers**: Bootstrap handles breakpoints automatically

### Spacing Utilities

**Preferred Bootstrap Utilities:**
- Margin: `.m-*`, `.mt-*`, `.mb-*`, `.ms-*`, `.me-*`
- Padding: `.p-*`, `.pt-*`, `.pb-*`, `.ps-*`, `.pe-*`
- Gap: `.gap-*`, `.gap-1`, `.gap-2`, `.gap-3`, `.gap-4`

**Common Spacing Patterns:**
- Section spacing: `mb-4` or `mb-5` (1.5rem - 3rem)
- Card spacing: `p-3` or `p-4` (1rem - 1.5rem)
- Button spacing: `gap-3` (1rem) for button groups
- Form spacing: `mb-3` (1rem) between form groups

### Grid System

Use Bootstrap's responsive grid:
```html
<div class="container">
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <!-- Card content -->
        </div>
    </div>
</div>
```

---

## Bootstrap Integration

### CSS Loading Order (ENFORCED)

```html
<!-- 1. Bootstrap CDN - Base framework styles -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- 2. bootstrap-overrides.css - Neutralizes Bootstrap reset while preserving existing design -->
<link rel="stylesheet" href="css/bootstrap-overrides.css">

<!-- 3. global.css - Global/base styles, CSS variables, shared components -->
<link rel="stylesheet" href="css/global.css">

<!-- 4. Page-specific CSS (via $extra_css array) -->
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/character_view.css">

<!-- 5. modal_fullscreen.css - Component styles (modals) -->
<link rel="stylesheet" href="css/modal_fullscreen.css">
```

### Bootstrap Enhancement Principle

**Core Principle**: Bootstrap is the foundation. All custom CSS must **enhance** Bootstrap's default behavior, never **override** or **replace** it.

**Guidelines:**
- ✅ **ENHANCE**: Add custom properties that Bootstrap doesn't provide
- ✅ **ENHANCE**: Extend Bootstrap components with additional styling
- ✅ **ENHANCE**: Add project-specific utilities that complement Bootstrap
- ❌ **OVERRIDE**: Change Bootstrap's default spacing, colors, or typography
- ❌ **OVERRIDE**: Replace Bootstrap's flexbox/grid behavior
- ❌ **OVERRIDE**: Remove Bootstrap's default margins/padding with `!important`

### Preferred Bootstrap Utilities

**Layout:**
- `.d-flex`, `.d-grid`, `.d-none`, `.d-block`
- `.flex-column`, `.flex-row`, `.flex-wrap`
- `.justify-content-*`, `.align-items-*`, `.gap-*`

**Spacing:**
- `.m-*`, `.p-*`, `.mt-*`, `.mb-*`, `.ms-*`, `.me-*`
- `.g-*` for grid gaps

**Text:**
- `.text-center`, `.text-start`, `.text-end`
- `.text-white`, `.text-light` (NEVER `.text-muted`)
- `.fw-bold`, `.fw-normal`, `.fst-italic`

**Responsive:**
- `.d-none .d-md-block` (hidden on mobile, visible on md+)
- `.d-block .d-md-none` (visible on mobile, hidden on md+)
- `.col-*`, `.col-md-*`, `.col-lg-*`

---

## Do's and Don'ts

### ✅ DO's

1. **Use CSS Variables**: Always reference color and font variables from `:root`
   ```css
   color: var(--text-light);
   background: var(--bg-darker);
   border-color: var(--blood-red);
   ```

2. **Use Bootstrap Utilities**: Prefer Bootstrap utilities over custom CSS when possible
   ```html
   <div class="d-flex justify-content-between align-items-center gap-4">
   ```

3. **Maintain Readability**: Always ensure text is fully opaque with sufficient contrast
   ```html
   <p class="text-white">Readable text</p>
   <div class="mt-1 text-light">Readable helper text</div>
   ```

4. **Use Consistent Borders & Border Radius**: 
   - **Border Width Standards:**
     - Cards, buttons, forms, info panels: `2px solid`
     - Modals: `3px solid` (prominent focus)
     - Separators/dividers: `1px solid` or `2px solid` (context-dependent)
   - **Border Radius Standard:** `0.75rem - 1rem` (Art Bible spec)
     - Default: `0.75rem` (12px) for cards, buttons, forms, info panels
     - Alternative: `1rem` (16px) for larger panels or special emphasis
     - Never use pixel values like `5px` or `8px` - use rem units for consistency
   - **Border Color Standards:**
     - Default cards: `2px solid var(--blood-red)`
     - Active cards: `2px solid var(--muted-gold)`
     - Modals: `3px solid var(--muted-gold)`
     - Info panels: `2px solid var(--teal-moonlight)`

5. **Use Gradients for Depth**: Apply gradients to backgrounds for atmospheric depth
   ```css
   background: linear-gradient(135deg, var(--deep-maroon) 0%, var(--dusk-brown-black) 100%);
   ```

6. **Include Focus States**: Always provide visible focus indicators for accessibility
   ```css
   :focus-visible {
       outline: 2px solid var(--blood-red);
       outline-offset: 2px;
   }
   ```

7. **Use Semantic HTML**: Maintain proper HTML structure with ARIA labels where needed
   ```html
   <main id="main-content" role="main" aria-label="Main content">
   ```

### ❌ DON'Ts

1. **NEVER use `text-muted`**: Poor contrast on dark backgrounds
   ```html
   <!-- ❌ WRONG -->
   <p class="text-muted">Hard to read</p>
   
   <!-- ✅ CORRECT -->
   <p class="text-light">Readable text</p>
   ```

2. **NEVER use opacity utilities on text**: Makes text unreadable
   ```html
   <!-- ❌ WRONG -->
   <p class="text-white opacity-75">Faded and unreadable</p>
   
   <!-- ✅ CORRECT -->
   <p class="text-white">Fully readable text</p>
   ```

3. **NEVER use Bootstrap's `form-text` class**: Applies grey color by default
   ```html
   <!-- ❌ WRONG -->
   <small class="form-text">Grey unreadable helper text</small>
   
   <!-- ✅ CORRECT -->
   <div class="mt-1 text-white">Readable helper text</div>
   ```

4. **NEVER override Bootstrap core behavior**: Enhance, don't replace
   ```css
   /* ❌ WRONG: Overrides Bootstrap's flexbox centering */
   .modal-dialog {
       margin: 0 !important;
       display: block !important;
   }
   
   /* ✅ CORRECT: Enhances Bootstrap while respecting it */
   .modal-dialog {
       max-width: min(calc(100vw - 4rem), 1280px);
   }
   ```

5. **NEVER use pure white backgrounds**: Violates gothic aesthetic
   ```css
   /* ❌ WRONG */
   background: #ffffff;
   
   /* ✅ CORRECT */
   background: var(--bg-darker);
   ```

6. **NEVER use neon colors**: Violates design philosophy
   ```css
   /* ❌ WRONG */
   color: #00ff00;
   
   /* ✅ CORRECT */
   color: var(--muted-gold);
   ```

7. **NEVER skip focus states**: Required for keyboard navigation accessibility
   ```css
   /* ❌ WRONG: No focus indicator */
   button { /* ... */ }
   
   /* ✅ CORRECT: Visible focus indicator */
   button:focus-visible {
       outline: 2px solid var(--blood-red);
       outline-offset: 2px;
   }
   ```

---

## Common Violations to Fix

### Identified Issues in Codebase

Based on codebase audit, the following violations were found and should be fixed:

1. **Multiple uses of `opacity-75` on text** (112 instances found):
   - `login.php:67` - `<p class="text-center opacity-75 mb-4">`
   - `questionnaire.php:125,137` - Multiple opacity-75 uses
   - `phoenix_map.php:147` - `<span class="opacity-75 small">`
   - `admin/camarilla_positions.php` - Multiple opacity-75 instances
   - `includes/position_view_modal.php` - Multiple opacity-75 instances
   - Many more throughout codebase

2. **Uses of Bootstrap's `form-text` class** (3 instances found):
   - `login.php:90` - `<div id="usernameHelp" class="form-text">`
   - `admin/admin_equipment.php:203` - `<small class="form-text opacity-75">`
   - `agents/coterie_agent/index.php:811` - `<small class="form-text opacity-75">`

3. **Uses of `opacity-50` on disabled cards** (acceptable for non-text elements):
   - `index.php:227` - `<div class="card col-md-4 col-sm-6 disabled opacity-50">`
   - This is acceptable for disabled visual state, but should verify text inside is still readable

### Fix Pattern

**Before (Violation):**
```html
<p class="text-center opacity-75 mb-4">Enter your credentials to access the chronicle</p>
<small class="form-text">Helper text here</small>
<span class="opacity-75">Vacant</span>
```

**After (Compliant):**
```html
<p class="text-center text-light mb-4">Enter your credentials to access the chronicle</p>
<div class="mt-1 text-white">Helper text here</div>
<span class="text-light">Vacant</span>
```

### Priority Fix List

**High Priority (Accessibility Issues):**
1. Replace all `opacity-75` on text with `text-light` or `text-white`
2. Replace all `form-text` class usage with plain `<div>` or `<p>` with readable colors
3. Verify all placeholder text uses `var(--text-light)` with `opacity: 1`

**Medium Priority (Consistency):**
4. Ensure all helper text uses consistent styling (`.mt-1 text-white` pattern)
5. Verify all form labels use `var(--font-title)` font family
6. Check all buttons use correct font family (`var(--font-title)`)

**Low Priority (Enhancement):**
7. Review all disabled states to ensure text remains readable
8. Standardize info panel usage (prefer `.info-panel-teal` class)
9. Verify all modals use consistent border styling (3px gold)

---

## File Organization

### CSS File Structure

```
css/
├── bootstrap-overrides.css    # Bootstrap theme customization
├── global.css                 # Global styles, CSS variables, header/footer
├── login.css                  # Login page specific styles
├── dashboard.css              # Dashboard page styles
├── character_view.css         # Character view modal styles
├── admin_panel.css            # Admin panel styles
├── modal_fullscreen.css       # Modal fullscreen functionality
├── [page-specific].css        # Other page-specific styles
└── [component].css            # Component-specific styles
```

### CSS Loading via PHP

```php
// In PHP files, use $extra_css array
$extra_css = ['css/dashboard.css', 'css/character_view.css'];
include 'includes/header.php';
```

The `header.php` file automatically loads:
1. Bootstrap CDN
2. `bootstrap-overrides.css`
3. `global.css`
4. Files from `$extra_css` array
5. `modal_fullscreen.css`

---

## Responsive Design

### Breakpoints (Bootstrap Standard)

- **Mobile**: < 576px
- **Tablet (sm)**: ≥ 576px
- **Desktop (md)**: ≥ 768px
- **Large (lg)**: ≥ 992px
- **Extra Large (xl)**: ≥ 1200px
- **XXL (xxl)**: ≥ 1400px

### Mobile-First Approach

```css
/* Base styles (mobile-first) */
.card {
    padding: 1rem;
}

/* Tablet and up */
@media (min-width: 768px) {
    .card {
        padding: 1.5rem;
    }
}

/* Desktop and up */
@media (min-width: 992px) {
    .card {
        padding: 2rem;
    }
}
```

### Responsive Utilities

**Display:**
- `.d-none .d-md-block` - Hidden on mobile, visible on md+
- `.d-block .d-md-none` - Visible on mobile, hidden on md+

**Grid:**
- `.col-12 .col-md-6 .col-lg-4` - Full width mobile, half tablet, third desktop

**Spacing:**
- `.p-2 .p-md-3 .p-lg-4` - Responsive padding

---

## Clan-Specific Styling

### Character Card Hover Effects

Clan-specific hover effects are implemented for character cards in `dashboard.css`:

```css
.character-list .card.clan-toreador:hover {
    border-color: rgba(220, 20, 60, 1);
    background: linear-gradient(135deg, rgba(220, 20, 60, 0.3) 0%, var(--deep-maroon) 40%, var(--dusk-brown-black) 100%);
}

.character-list .card.clan-ventrue:hover {
    border-color: var(--muted-gold);
    background: linear-gradient(135deg, rgba(212, 176, 109, 0.2) 0%, rgba(139, 0, 0, 0.15) 30%, var(--deep-maroon) 60%, var(--dusk-brown-black) 100%);
}
```

### Clan Badge Colors

Clan badges use CSS custom properties for dynamic colors:

```css
.clan-badge {
    --clan-badge-color: [clan-specific-color];
    background-color: var(--clan-badge-color);
}
```

---

## Version History

- **v1.0** (2025-01-30): Initial comprehensive style guide created from codebase audit

---

## References

- **Bootstrap 5.3.2 Documentation**: https://getbootstrap.com/docs/5.3/
- **WCAG 2.1 Accessibility Guidelines**: https://www.w3.org/WAI/WCAG21/quickref/
- **Art Bible**: `agents/style_agent/docs/Art_Bible_V_UI_&_WEB_ART_SYSTEM__.md`
- **Bootstrap Integration Rules**: `.cursor/rules/bootstrap-integreation.mdc`
- **Text Muted Rules**: `.cursor/rules/text-muted-dark-background.mdc`

---

**End of Style Guide**
