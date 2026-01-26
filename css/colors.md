# Valley by Night - Color Reference

This document lists all the colors used on the site, defined as CSS variables in `global.css`.

## Primary UI Theme Colors

These are the foundational colors used throughout the entire project for backgrounds, text, borders, and UI elements.

### Background Colors
- **Gothic Black** → `#0d0606` (`--bg-dark`) - Primary dark background
- **Dusk Brown-Black** → `#1a0f0f` (`--bg-darker`) - Secondary dark background, gradients
- **Deep Maroon** → `#2a1515` (`--deep-maroon`) - Card backgrounds, layered elements

### Accent Colors
- **Blood Red** → `#8B0000` (`--blood-red`) - Primary accent, borders, danger, emphasis
- **Muted Gold** → `#d4b06d` (`--muted-gold`) - Text-mid, borders, highlights, accents
- **Teal Moonlight** → `#0B3C49` (`--teal-moonlight`) - Info panels, alternate accents

### Text Colors
- **Parchment Light** → `#f5e6d3` (`--text-light`) - Primary text color, light elements
- **Muted Gold** → `#d4b06d` (`--text-mid`) - Secondary text color (same as muted-gold)

## CSS Variables Usage

All colors are defined as CSS custom properties in `global.css`:

```css
:root {
    --bg-dark: #0d0606;
    --bg-darker: #1a0f0f;
    --deep-maroon: #2a1515;
    --dusk-brown-black: #1a0f0f;
    --blood-red: #8B0000;
    --muted-gold: #d4b06d;
    --teal-moonlight: #0B3C49;
    --text-light: #f5e6d3;
    --text-mid: #d4b06d;
}
```

## Usage Guidelines

### Backgrounds
- Use `var(--bg-darker)` or `var(--dusk-brown-black)` for main page backgrounds
- Use `var(--deep-maroon)` for card backgrounds and layered elements
- Body background uses gradient: `linear-gradient(135deg, #0d0606 0%, #1a0f0f 50%, #0d0606 100%)`

### Text
- Primary text on dark backgrounds: `var(--text-light)` (#f5e6d3)
- Secondary text: `var(--text-mid)` (#d4b06d)
- **Never use**: `text-muted`, `opacity-*` utilities on text, or Bootstrap's `form-text` class

### Accents
- **Blood Red**: Borders, buttons, danger states, emphasis
- **Muted Gold**: Active states, selected items, hover highlights
- **Teal Moonlight**: Info panels, alternate borders, special sections

### Tables
- Table background: `radial-gradient(circle at center, rgba(139, 0, 0, 0.4) 0%, rgba(139, 0, 0, 0.2) 40%, rgba(26, 15, 15, 0.6) 100%)`
- Table header: `linear-gradient(135deg, #8B0000 0%, #600000 100%)` with `var(--text-light)` text

## Related Documentation

- `global.css` - CSS variable definitions
- `VbN_styles.md` - Comprehensive visual style guide
- `agents/style_agent/docs/Colors.md` - Complete color system including clan badges, item types, and location colors
