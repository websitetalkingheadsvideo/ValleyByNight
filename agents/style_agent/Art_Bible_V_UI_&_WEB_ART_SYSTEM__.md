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
- Muted Gold: **#d4b06d**
- Parchment Light: **#f5e6d3**
- Deep Maroon: **#2a1515**
- Teal Moonlight: **#0B3C49**

## UI Color Philosophy
- Dark backgrounds with gold highlights
- Red used for danger, emphasis, or clan cues
- Parchment text for readability
- No pure white
- No neon colors

---

# 3. Typography

## Fonts
- Headers: *IM Fell English SC*, *Libre Baskerville*
- Body: *Georgia* or *Libre Baskerville*
- Code/Monospace: *Source Code Pro*

## Rules
- Serif dominant
- Minimal sans-serif use
- Strong title hierarchy
- Gold or parchment text

---

# 4. Core UI Components

## 4.1 Cards
- Background: #1a0f0f
- Border: 2–3px gold or red
- Rounded corners: .75–1rem
- Hover: soft gold highlight

## 4.2 Modals
- Background: #1a0f0f
- Border: 3px blood red or gold
- Header: serif font, gold text
- Body text: parchment
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
- Gold border, gold text

**Danger Button**
- Dark red, shadowed
- Hover: brighter red

---

# 5. Tables & Forms

## Tables
- Dark backgrounds
- Gold header row
- Parchment text
- Red or gold dividers

## Forms
- Input fields: dark
- Borders: thin gold
- Focus state: blood-red glow
- Labels: serif

---

# 6. Layout & Navigation

## Navbars
- Background: #1a0f0f
- Gold hover lines
- Red underline for active state

## Sidebars
- Dark panel
- Gold headings
- Parchment text
- Soft glow on hover

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
- Consistent modal size and border style
- Standardized UI typography
- Avoid Bootstrap defaults where overridden
- Custom gradients: dark-to-darker
- Consistent spacing in grids

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
- Focus outlines in red or gold

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
