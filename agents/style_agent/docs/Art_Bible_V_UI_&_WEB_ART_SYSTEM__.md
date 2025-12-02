# PART V — UI & WEB ART SYSTEM  
## (Full, Exhaustive Version)

## 1. Overview
The UI & Web Art System defines the full visual identity of all Valley by Night web interfaces, admin tools, player-facing UI screens, modals, tables, cards, banners, and interactive layouts.  
All UI must integrate with:
- the noir art palette
- the cinematic visual language
- portrait system
- 3D and location tones
- 1994 Phoenix inspiration
- Bootstrap-based components
- custom VbN CSS overrides

---

# 2. Color Palette (Canonical)

## Primary Colors
- Gothic Black: **#0d0606**
- Dusk Brown-Black: **#1a0f0f**
- Blood Red: **#8B0000**
- Muted Gold: **#d4b06d** (used for text-mid, borders, and accents)
- Parchment Light: **#f5e6d3**
- Deep Maroon: **#2a1515**
- Teal Moonlight: **#0B3C49** (used for info panels and alternate accents)

## UI Color Philosophy
- Dark backgrounds with gold highlights
- Red used for danger, emphasis, or clan cues
- Parchment text for readability
- No pure white
- No neon colors

---

# 3. Typography

## Fonts
- Headers: *IM Fell English SC* (Small Caps variant preferred), *Libre Baskerville*
- Body: *Source Serif Pro* (with Times New Roman fallback)
- **Labels: *Libre Baskerville*** (form labels, UI labels, titles)
- Code/Monospace: *Source Code Pro* (implemented)

## Rules
- Serif dominant
- Minimal sans-serif use
- Strong title hierarchy
- Gold or parchment text

---

# 4. Core UI Components

## 4.1 Cards
- Background: Linear gradient (#2a1515 to #1a0f0f)
- Border: 2px red (#8B0000) for default cards
- Border: 2px gold (muted gold #d4b06d) for active cards (use `.active` or `.card-active` class)
- Rounded corners: **0.75rem** (12px) - implemented
- Hover: Red border highlight with shadow lift (current implementation maintained)

## 4.2 Modals
- Background: Radial gradient (blood red #8B0000 → #820000 → #1a0f0f from center)
- Border: **3px gold** (muted gold #d4b06d) - implemented
- Header: serif font (IM Fell English), parchment text (#f5e6d3)
- Body text: muted gold (#d4b06d) for text-mid
- Shadow: soft noir vignette
- Portrait fallback: WtOlogo.webp

## 4.3 Buttons
**Primary Button**
- Blood red background
- Parchment text
- Rounded corners
- Gold hover glow

**Secondary Button**
- Transparent background
- Gold border (muted gold #d4b06d), gold text
- Hover: Fills with gold background, dark text
- **Implemented** - All `.btn-secondary` use this style

**Danger Button**
- Dark red, shadowed
- Hover: brighter red

---

# 5. Tables & Forms

## Tables
- **Background: Blood-red radial gradient** (radial-gradient from center: rgba(139, 0, 0, 0.4) → rgba(139, 0, 0, 0.2) → rgba(26, 15, 15, 0.6)) - implemented
- Red gradient header row (linear-gradient: #8B0000 → #600000)
- Parchment text (#f5e6d3) for headers
- Muted gold (#d4b06d) for body text
- Red dividers
- Subtle red background for body rows (rgba(139, 0, 0, 0.1))
- Enhanced red on hover (rgba(139, 0, 0, 0.2))

## Forms
- Input fields: dark background (rgba(26, 15, 15, 0.6))
- Borders: **2px red** (rgba(139, 0, 0, 0.4)) - red borders maintained per design decision
- **Focus state: blood-red glow** - `box-shadow: 0 0 10px rgba(139, 0, 0, 0.3)` with blood-red border color (#8B0000)
- Background on focus: slightly lighter (rgba(26, 15, 15, 0.8))
- **Labels: *Libre Baskerville*** (font-family: var(--font-title), 'Libre Baskerville', serif)

---

# 6. Layout & Navigation

## Navbars
- **Background: Linear gradient** (180deg: #1a0f0f → #0d0606) with red bottom border (2px solid #8B0000)
- **Box shadow:** `0 4px 15px rgba(139, 0, 0, 0.3)` for depth
- **Hover effect:** Red text shadow glow (`0 0 15px rgba(139, 0, 0, 0.6)`) - text color changes to #ffffff
- **Active state:** Bootstrap button active state (background fill: `rgba(139, 0, 0, 0.3)`) with border-radius
- **Text styling:** Parchment text (#f5e6d3) with red text shadow on default state
- **Position:** Sticky header (position: sticky, top: 0, z-index: 1000)

## Sidebars
- **Background:** Dark panel (rgba(26, 15, 15, 0.8)) with red border (2px solid rgba(139, 0, 0, 0.3))
- **Border radius:** 0.75rem (12px) - Art Bible standard
- **Gold headings:** All `h3` and `h4` headings use muted gold (#d4b06d) with text shadow
- **Parchment text:** Body text uses #f5e6d3 (var(--text-light))
- **Soft glow on hover:** Gold glow effect (box-shadow: 0 0 15px rgba(212, 176, 109, 0.2)) on stat-groups and stat-lines
- **Stat groups:** Dark background sections with gold headings, soft gold glow on hover
- **Stat labels:** Parchment text (#f5e6d3) using Libre Baskerville font
- **Stat values:** Parchment text with color variations (negative: red, positive: green, total: gold)
- **Character preview:** Included in sidebar with gold section headings and parchment text

## Spacing
- Generous padding
- Clean layout
- Noir breathing room

---

# 7. UI Images & Banners

## Resolutions
- 1920×1080: hero banners
- 1024×1024: UI tiles
- 512×512 or SVG: icons

## Tone
- Noir, low saturation
- Strong shadows
- Gold and crimson accents

## Movement Layers (Optional)
- Slow parallax
- Soft noise drift
- Mild vignette animation

---

# 8. Integration with CSS Files

This section consolidates rules from:
- global.css
- bootstrap-overrides.css
- dashboard.css
- modal.css
- character_view.css
- login.css

## Key CSS Concepts
- Unified card shadows
- Consistent modal size and border style (gold borders, radial red gradient background)
- Standardized UI typography (IM Fell English SC for headers, Source Serif Pro for body, Source Code Pro for code)
- Avoid Bootstrap defaults where overridden
- Custom gradients: dark-to-darker, radial gradients for modals
- Consistent spacing in grids
- CSS Variables: `--muted-gold` (#d4b06d), `--teal-moonlight` (#0B3C49), `--font-brand-sc` (Small Caps variant)

## Info Panels - Teal Moonlight
- `.info-panel-teal` - Border and gradient background
- `.info-panel-teal-border` - Border only
- `.info-panel-teal-gradient` - Gradient background only
- Use for informational sections, special content panels

## Active Cards
- `.active` or `.card-active` class applies gold border (muted gold #d4b06d)
- Default cards use red border (#8B0000)
- Border radius: 0.75rem (12px) for all cards

---

# 9. Clan-Specific UI Integration

Clan motifs appear in:
- modal headers
- portrait frames
- banners
- clan info cards

Each clan gets:
- Toreador: crimson silk
- Brujah: cracked concrete
- Ventrue: marble gold
- Nosferatu: industrial grime
- Malkavian: neon flicker
- Setite: red velvet + gold
- Giovanni: grayscale candlelight

---

# 10. UI Prompt Templates

## Master UI Prompt
(Referenced in 05b)

## Clan Glyph Prompt
(Referenced in 05b)

## Banner Templates
(Referenced in 05b)

---

# 11. Responsive Rules

## Mobile Navigation
- Simplified layout
- Larger tap targets
- Darker overlay gradient
- Reduced clutter

## Portrait Mode Support
- Alternate background crops
- Flexible column stacking

---

# 12. Accessibility

Minimum contrast:
- Gold/Parchment text on black
- Avoid red text alone (use gold outline)

Keyboard navigation:
- **Focus states: blood-red glow and outline**
  - Form controls: `box-shadow: 0 0 10px rgba(139, 0, 0, 0.3)` with blood-red border
  - Modal content: `outline: 2px solid var(--blood-red)` with `box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.25)`
  - Buttons/links: blood-red outline (2px solid) with offset
  - All focus states use blood-red (#8B0000) for consistency

---

# 13. Integration with Backend

UI communicates with:
- Character Agent UI
- Rumor/Influence/Boon systems
- Modal viewer for Wraith and WoD types
- PHP pages for admin interface

Folder structure summarized in Part IX.

---


---
