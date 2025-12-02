# Art Bible vs Current Implementation - Differences Analysis

## Overview
This document compares the Art Bible specifications (Part V — UI & WEB ART SYSTEM) with the current CSS implementation to identify discrepancies.

## Implementation Status Summary

### ✅ Completed Implementations
- **Color Palette:** Muted Gold (#d4b06d) and Teal Moonlight (#0B3C49) added to CSS variables
- **Typography:** IM Fell English SC (Small Caps) and Source Code Pro fonts loaded and styled
- **Cards:** Border radius updated to 0.75rem, gold border option for active cards
- **Buttons:** Secondary buttons use transparent gold style, danger buttons match Art Bible spec
- **Modals:** Gold borders (3px) and radial gradient background implemented
- **Tables:** Blood-red radial gradient background implemented
- **Forms:** Focus states with blood-red glow, labels use Libre Baskerville
- **Sidebars:** Fully implemented with gold headings, parchment text, and soft gold glow on hover

### ⚠️ Design Decisions (Intentionally Different)
- **Form borders:** Red borders maintained (per user request)
- **Table headers:** Red headers maintained (gold option available)
- **Navbar:** Current implementation documented in Art Bible (gradient background, red hover)
- **Body font:** Source Serif Pro maintained (Libre Baskerville used for labels/titles)

### ⏳ Pending/Not Implemented
- **Clan-specific UI theming:** Not implemented (low priority)
- **UI Images & Banners:** Resolutions not verified
- **Movement Layers:** Optional parallax/animations not implemented

---

## 1. COLOR PALETTE DIFFERENCES

### Missing Colors
- **Muted Gold: `#d4b06d`** — ✅ **IMPLEMENTED** - Added to CSS variables (`--muted-gold`) and used throughout
  - Status: ✅ Complete - Used for text-mid, borders, headings, and accents
  - Location: `css/global.css` line 25

- **Teal Moonlight: `#0B3C49`** — ✅ **IMPLEMENTED** - Added to CSS variables (`--teal-moonlight`)
  - Status: ✅ Complete - Available for info panels and alternate accents
  - Location: `css/global.css` line 26

### Present Colors (Matches)
- ✅ Gothic Black: `#0d0606` (--bg-dark)
- ✅ Dusk Brown-Black: `#1a0f0f` (--bg-darker)
- ✅ Blood Red: `#8B0000` (--blood-red)
- ✅ Parchment Light: `#f5e6d3` (--text-light)
- ✅ Deep Maroon: `#2a1515` (used in gradients)

---

## 2. TYPOGRAPHY DIFFERENCES

### Font Family Mismatches

**Headers:**
- **Bible:** `IM Fell English SC` (Small Caps variant)
- **Current:** ✅ **IMPLEMENTED** - `IM Fell English SC` available via `--font-brand-sc` variable
  - Status: ✅ Complete - Font loaded, variable defined, used in demo page
  - Location: `css/global.css` line 20, `includes/header.php` line 67

**Body Text:**
- **Bible:** `Georgia` or `Libre Baskerville`
- **Current:** `Source Serif Pro` (with Times New Roman fallback)
- **Status:** ⚠️ Design decision - Using Source Serif Pro instead of Georgia/Libre Baskerville for body text
- **Note:** Libre Baskerville is used for labels/titles via `--font-title`

**Code/Monospace:**
- **Bible:** `Source Code Pro`
- **Current:** ✅ **IMPLEMENTED** - `Source Code Pro` loaded and styled
  - Status: ✅ Complete - Font loaded, styled in `bootstrap-overrides.css` and `global.css`
  - Location: `includes/header.php` line 67, `css/bootstrap-overrides.css` line 22-25, `css/global.css` line 55-58

---

## 3. CARD COMPONENT DIFFERENCES

### Border Radius
- **Bible:** `.75rem - 1rem` (12px - 16px)
- **Current:** ✅ **IMPLEMENTED** - Updated to `0.75rem` (12px) per Art Bible
  - Status: ✅ Complete - Cards now use 0.75rem border radius
  - Location: `css/bootstrap-overrides.css` line 195

### Border Style
- **Bible:** `2-3px gold or red` border
- **Current:** ✅ **IMPLEMENTED** - Red border (2px) for default, gold border (2px) for active cards
  - Status: ✅ Complete - Use `.active` or `.card-active` class for gold border
  - Location: `css/bootstrap-overrides.css` line 200-207

### Hover Effect
- **Bible:** "soft gold highlight"
- **Current:** Red border color change and shadow (current implementation maintained per design decision)
- **Status:** ⚠️ Design decision - Current hover effect maintained, not changed to gold

---

## 4. BUTTON COMPONENT DIFFERENCES

### Secondary Button
- **Bible:** 
  - Transparent background
  - Gold border
  - Gold text
- **Current:** ✅ **IMPLEMENTED** - Transparent background, gold border (#d4b06d), gold text
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/bootstrap-overrides.css` line 100-115

### Primary Button
- **Bible:** Blood red background, parchment text, gold hover glow
- **Current:** Blood red gradient, parchment text, red shadow on hover
- **Status:** ⚠️ Design decision - Red hover glow maintained instead of gold

### Danger Button
- **Bible:** Dark red, shadowed, brighter red on hover
- **Current:** ✅ **IMPLEMENTED** - Dark red gradient (#660000 to #4a0000), shadowed, brighter red on hover
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/bootstrap-overrides.css` line 144-160

---

## 5. MODAL COMPONENT DIFFERENCES

### Border
- **Bible:** `3px blood red or gold` border
- **Current:** ✅ **IMPLEMENTED** - Gold border (3px solid var(--muted-gold)) for modals
  - Status: ✅ Complete - All modals use gold border per Art Bible
  - Location: `css/modal.css` line 30-35

### Border Radius
- **Bible:** Not explicitly specified (assumed .75-1rem)
- **Current:** `10px` (0.625rem)
- **Status:** ⚠️ Close to Bible range - Within acceptable range

### Background
- **Bible:** Radial gradient background
- **Current:** ✅ **IMPLEMENTED** - Radial gradient (blood red #8B0000 → #820000 → #1a0f0f)
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/modal.css` line 20-25

### Header
- **Bible:** Serif font, gold text
- **Current:** `IM Fell English` (serif), body text uses muted gold (#d4b06d) for text-mid
  - Status: ⚠️ Design decision - Header titles use parchment, body text uses muted gold

---

## 6. TABLE COMPONENT DIFFERENCES

### Background
- **Bible:** Blood-red gradient background
- **Current:** ✅ **IMPLEMENTED** - Radial gradient background (blood-red radial gradient)
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/global.css` line 34-36

### Header Row
- **Bible:** "Gold header row" (optional)
- **Current:** Red gradient background (`linear-gradient(135deg, #8B0000 0%, #600000 100%)`)
  - Status: ⚠️ Design decision - Red headers maintained, gold header option available via `.table-gold-header` class

### Text Colors
- **Bible:** Parchment text
- **Current:** ✅ **IMPLEMENTED** - Parchment text (#f5e6d3) for headers, muted gold (#d4b06d) for body text
  - Status: ✅ Complete - Matches Art Bible
  - Location: `css/global.css` line 39-42

### Dividers
- **Bible:** "Red or gold dividers"
- **Current:** Red dividers (`rgba(139, 0, 0, 0.2)`)
  - Status: ⚠️ Design decision - Red dividers maintained

---

## 7. FORM COMPONENT DIFFERENCES

### Input Borders
- **Bible:** "Thin gold borders"
- **Current:** `2px solid rgba(139, 0, 0, 0.4)` (red borders)
  - Status: ⚠️ Design decision - Red borders maintained per explicit user request
  - Note: Gold border option available via `.form-gold-border` class for special cases

### Focus State
- **Bible:** "Blood-red glow"
- **Current:** ✅ **IMPLEMENTED** - `box-shadow: 0 0 10px rgba(139, 0, 0, 0.3)` (blood-red glow)
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/bootstrap-overrides.css` line 42

### Labels
- **Bible:** Serif font (Libre Baskerville)
- **Current:** ✅ **IMPLEMENTED** - `Libre Baskerville` (serif) via `--font-title`
  - Status: ✅ Complete - Matches Art Bible specification
  - Location: `css/bootstrap-overrides.css` line 46-50

---

## 8. NAVBAR/NAVIGATION DIFFERENCES

### Hover Effects
- **Bible:** "Gold hover lines"
- **Current:** Red text shadow glow (not gold lines)
  - Status: ⚠️ Design decision - Current implementation documented in Art Bible
  - Note: Art Bible updated to reflect current implementation (gradient background, red hover glow)

### Active State
- **Bible:** "Red underline for active state"
- **Current:** Bootstrap button active state (background fill)
  - Status: ⚠️ Design decision - Current implementation documented in Art Bible
  - Note: Art Bible updated to reflect current implementation

---

## 9. SIDEBAR DIFFERENCES

### Headings
- **Bible:** "Gold headings" (#d4b06d)
- **Current:** ✅ **IMPLEMENTED** - Gold headings (#d4b06d) with text shadow in `css/style.css`
- **Status:** ✅ Matches Art Bible

### Hover Effect
- **Bible:** "Soft glow on hover"
- **Current:** ✅ **IMPLEMENTED** - Soft gold glow (box-shadow: 0 0 15px rgba(212, 176, 109, 0.2)) on stat-groups and stat-lines
- **Status:** ✅ Matches Art Bible

### Background
- **Bible:** "Dark panel"
- **Current:** ✅ **IMPLEMENTED** - Dark panel background (rgba(26, 15, 15, 0.8)) with red border
- **Status:** ✅ Matches Art Bible

### Text
- **Bible:** "Parchment text"
- **Current:** ✅ **IMPLEMENTED** - Parchment text (#f5e6d3) for labels and values
- **Status:** ✅ Matches Art Bible

**Note:** Sidebar styling has been fully implemented in `css/style.css` per Art Bible specifications.

---

## 10. BORDER RADIUS STANDARDIZATION

### Current Implementation
- Cards: ✅ **UPDATED** - `0.75rem` (12px) - Art Bible compliant
- Buttons: `5px`
- Alerts: `4px`
- Modals: `10px` (0.625rem) - Within acceptable range
- Form inputs: `5px`
- Sidebars: ✅ **IMPLEMENTED** - `0.75rem` (12px) - Art Bible compliant

### Bible Specification
- Cards: `.75rem - 1rem` (12px - 16px)
- Other components: Not explicitly specified

### Status
- ✅ Cards updated to 0.75rem per Art Bible
- ✅ Sidebars use 0.75rem per Art Bible
- ⚠️ Other components remain at current values (within acceptable range)

---

## 11. MISSING IMPLEMENTATIONS

### Clan-Specific UI Integration
- **Bible:** Specifies clan motifs (Toreador: crimson silk, Brujah: cracked concrete, etc.)
- **Current:** Not implemented
- **Impact:** Clan-specific visual theming is absent

### UI Images & Banners
- **Bible:** Specifies resolutions (1920×1080, 1024×1024, 512×512)
- **Current:** Not verified
- **Status:** Needs review

### Movement Layers
- **Bible:** Mentions optional parallax, noise drift, vignette animation
- **Current:** Not implemented
- **Impact:** Missing optional cinematic effects

---

## 12. SUMMARY OF KEY GAPS

### Critical Differences (Visual Impact)
1. ✅ **Gold color usage** — ✅ **IMPLEMENTED** - Muted gold (#d4b06d) added and used for borders, text, headings, accents
2. ✅ **Muted Gold (`#d4b06d`)** — ✅ **IMPLEMENTED** - Added to CSS variables and used throughout
3. ✅ **Teal Moonlight (`#0B3C49`)** — ✅ **IMPLEMENTED** - Added to CSS variables, available for info panels
4. ✅ **Secondary buttons** — ✅ **IMPLEMENTED** - Transparent gold style matches Art Bible
5. ⚠️ **Table headers** — Red maintained (design decision), gold option available via `.table-gold-header`
6. ✅ **Card borders** — ✅ **IMPLEMENTED** - Gold border option available via `.active` or `.card-active` class
7. ⚠️ **Form borders** — Red maintained (design decision), gold option available via `.form-gold-border`

### Typography Gaps
1. ✅ **IM Fell English SC** — ✅ **IMPLEMENTED** - Font loaded and available via `--font-brand-sc` variable
2. ⚠️ **Source Serif Pro** — Design decision - Using Source Serif Pro for body (Libre Baskerville used for labels/titles)
3. ✅ **Source Code Pro** — ✅ **IMPLEMENTED** - Font loaded and styled for code blocks

### Component Gaps
1. ✅ **Card border-radius** — ✅ **IMPLEMENTED** - Updated to 0.75rem (12px) per Art Bible
2. ⚠️ **Clan-specific theming** — Not implemented (low priority)
3. ⚠️ **Gold hover effects** — Design decisions - Some components use red hover (maintained), others use gold (sidebars, secondary buttons)

---

## 13. RECOMMENDATIONS

### High Priority
1. ✅ Add `#d4b06d` (Muted Gold) to CSS variables - **COMPLETE**
2. ✅ Add `#0B3C49` (Teal Moonlight) to CSS variables - **COMPLETE**
3. ✅ Create gold border variant for cards - **COMPLETE** (`.active` or `.card-active` class)
4. ✅ Update secondary buttons to transparent gold style - **COMPLETE**
5. ⚠️ Change table headers to gold background - **DESIGN DECISION** (Red maintained, gold option available)
6. ⚠️ Update form input borders to gold - **DESIGN DECISION** (Red maintained, gold option available)

### Medium Priority
1. ✅ Update card border-radius to `.75rem` or `1rem` - **COMPLETE** (0.75rem implemented)
2. ✅ Add `IM Fell English SC` font variant - **COMPLETE**
3. ⚠️ Consider adding `Georgia` as body font option - **DESIGN DECISION** (Source Serif Pro maintained)
4. ✅ Add `Source Code Pro` for code blocks - **COMPLETE**

### Low Priority
1. Implement clan-specific UI theming
2. Add optional movement layers (parallax, etc.)
3. Review and standardize all border-radius values

---

## 14. FILES TO UPDATE

### CSS Variables (`css/global.css`)
- ✅ Add `--muted-gold: #d4b06d;` - **COMPLETE** (line 25)
- ✅ Add `--teal-moonlight: #0B3C49;` - **COMPLETE** (line 26)

### Bootstrap Overrides (`css/bootstrap-overrides.css`)
- ✅ Update secondary button to transparent gold - **COMPLETE** (line 100-115)
- ✅ Add gold border variant for cards - **COMPLETE** (line 200-207, `.card-active` class)
- ✅ Update danger button to Art Bible spec - **COMPLETE** (line 144-160)
- ⚠️ Update table header backgrounds to gold - **DESIGN DECISION** (Red maintained, gold option available)
- ⚠️ Update form input borders to gold - **DESIGN DECISION** (Red maintained, gold option available)

### Typography
- ✅ Add `IM Fell English SC` font loading - **COMPLETE** (`includes/header.php` line 67)
- ✅ Add `Source Code Pro` for code blocks - **COMPLETE** (`includes/header.php` line 67, styled in `bootstrap-overrides.css`)
- ⚠️ Consider `Georgia` as body font fallback - **DESIGN DECISION** (Source Serif Pro maintained)

### Additional Files
- ✅ Create `css/style.css` for sidebar styling - **COMPLETE** (Art Bible compliant sidebar styles)
- ✅ Update `css/modal.css` for gold borders and radial gradient - **COMPLETE**
- ✅ Update `css/global.css` for table radial gradient background - **COMPLETE**

---

*Generated: 2025-01-XX*
*Art Bible Version: Part V — UI & WEB ART SYSTEM (Full, Exhaustive Version)*
*Current Implementation: Bootstrap 5.3.2 with custom gothic theme*

