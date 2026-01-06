# COLORS — COMPREHENSIVE COLOR SYSTEM

## Overview
This document defines the canonical color palette for all color-coded elements throughout the Valley by Night project, including:
- Primary UI theme colors
- Clan and political faction badges
- Item type and rarity badges
- Location type and status badges
- Character status badges

All colors must maintain consistency across:
- Admin panel character listings
- Character view pages
- Badge displays in tables and cards
- CSS styling systems
- Database-driven badge rendering
- UI components and modals

---

## Clan Badge Colors

Each clan has a unique color assigned for badge display. Colors are case-insensitive and support multiple name variations.

### Camarilla Clans

- **Assamite** / **Banu Haqim** → `#2E3192` (Deep Blue)
- **Brujah** → `#B22222` (Fire Brick Red)
- **Malkavian** → `#6A0DAD` (Purple)
- **Nosferatu** → `#556B5D` (Dark Slate Gray)
- **Toreador** → `#C71585` (Medium Violet Red)
- **Tremere** → `#8B008B` (Dark Magenta)
- **Ventrue** → `#1F3A93` (Royal Blue)

### Independent Clans

- **Followers of Set** / **Setite** → `#8B6C37` (Dark Khaki)
- **Gangrel** → `#228B22` (Forest Green)
- **Giovanni** → `#556B2F` (Dark Olive Green)
- **Ravnos** → `#008B8B` (Dark Cyan)
- **Tzimisce** → `#99CC00` (Yellow Green)

### Sabbat Clans

- **Lasombra** → `#1A1A40` (Dark Navy)

### Other

- **Caitiff** → `#708090` (Slate Gray)
- **Daughter(s) of Cacophony** → `#FF69B4` (Hot Pink)
- **Ghoul** → `#8B4513` (Saddle Brown)

---

## Political Faction Badge Colors

Political factions represent major sect affiliations and are distinct from clan membership.

- **Anarch** / **Anarchs** → `#DC143C` (Crimson)
- **Camarilla** → `#8B6508` (Bronze Gold)
- **Sabbat** → `#1A0F0F` (Dusk Brown-Black)

---

## Primary UI Theme Colors

These are the foundational colors used throughout the entire project for backgrounds, text, borders, and UI elements.

- **Gothic Black** → `#0d0606` - Primary dark background
- **Dusk Brown-Black** → `#1a0f0f` - Secondary dark background, gradients
- **Deep Maroon** → `#2a1515` - Card backgrounds, layered elements
- **Blood Red** → `#8B0000` - Primary accent, borders, danger, emphasis
- **Muted Gold** → `#d4b06d` - Text-mid, borders, highlights, accents
- **Parchment Light** → `#f5e6d3` - Primary text color, light elements
- **Teal Moonlight** → `#0B3C49` - Info panels, alternate accents
- **Desert Amber** → `#C87B3E` - Location lighting, warm tones

### Usage Philosophy
- Dark backgrounds with gold highlights
- Red used for danger, emphasis, or clan cues
- Parchment text for readability
- No pure white
- No neon colors

---

## Item Type Badge Colors

Colors for categorizing different types of items in the inventory system.

- **Weapon** → `#8B0000` (Blood Red) — danger, combat
- **Armor** → `#4a4a4a` (Dark Gray) — protection, defense
- **Tool** → `#8B6508` (Bronze Gold) — utility, craftsmanship
- **Consumable** → `#1a6b3a` (Forest Green) — nature, sustenance
- **Artifact** → `#4a1a6b` (Deep Purple) — mystery, power
- **Ammunition** → `#6b4513` (Saddle Brown) — ammunition, supplies
- **Electronics** → `#1a4a6b` (Steel Blue) — technology, modern
- **Gear** → `#4a4a2a` (Olive Drab) — equipment, military
- **Magical** → `#6b1a4a` (Dark Magenta) — supernatural, mystical
- **Token** → `#8B6508` (Bronze Gold) — currency, value
- **Magical Tool** → `#4a1a6b` (Deep Purple) — enchanted utility
- **Trait** → `#1a6b3a` (Forest Green) — character attribute
- **Misc** → `#5a4a2a` (Brown Gray) — miscellaneous
- **Equipment** → `#6B4423` (Saddle Brown) — general equipment
- **Accessory** → `#4A4A6B` (Slate Blue) — adornment
- **Clothing** → `#6B4A4A` (Dusty Rose) — apparel
- **Vehicle** → `#4A6B4A` (Moss Green) — transportation
- **Book** → `#6B6B4A` (Olive Yellow) — knowledge
- **Food** → `#8B4513` (Sienna) — sustenance
- **Drink** → `#8B008B` (Dark Magenta) — beverages
- **Drug** → `#8B0000` (Blood Red) — substances
- **Electronic** → `#2F4F4F` (Dark Slate Gray) — technology

---

## Item Rarity Badge Colors

Colors indicating the rarity/quality level of items.

- **Common** → `#9E9E9E` (Medium Gray) — standard items
- **Uncommon** → `#4CAF50` (Green) — slightly rare
- **Rare** → `#2196F3` (Blue) — uncommon finds
- **Very Rare** → `rgba(255, 69, 0, 0.2)` background, `#FF4500` text/border (Orange Red) — extremely rare
- **Epic** → `#9C27B0` (Purple) — legendary tier
- **Legendary** → `#FF9800` (Orange) — mythic tier

---

## Location Type Badge Colors

Colors for categorizing different types of locations.

- **Haven** → `#8B0000` (Blood Red) — personal vampire residence
- **Elysium** → `#4A4A6B` (Slate Blue) — neutral gathering place
- **Domain** → `#8B6508` (Bronze Gold) — controlled territory
- **Hunting Ground** → `#1a6b3a` (Forest Green) — feeding territory
- **Nightclub** → `#4A1A6B` (Deep Purple) — entertainment venue
- **Gathering Place** → `#6B4423` (Saddle Brown) — social location
- **Business** → `#6B4423` (Saddle Brown) — commercial establishment
- **Chantry** → `#4A4A6B` (Slate Blue) — Tremere facility
- **Temple** → `#8B6508` (Bronze Gold) — Setite facility
- **Wilderness** → `#1a6b3a` (Forest Green) — natural/outdoor area
- **Other** → `#5a4a2a` (Brown Gray) — miscellaneous location

---

## Location Status Badge Colors

Colors indicating the current state/condition of locations.

- **Active** → `#4CAF50` (Green) — currently in use
- **Abandoned** → `#9E9E9E` (Medium Gray) — no longer in use
- **Destroyed** → `#f44336` (Red) — destroyed/demolished
- **Contested** → `#FF9800` (Orange) — under dispute
- **Hidden** → `#2196F3` (Blue) — concealed/secret

---

## Character Status Badge Colors

Colors for character status indicators in the admin panel.

- **NPC** → `#4a1a6b` (Deep Purple) — non-player character
- **Active** → `#0d7a4a` (Dark Green) — currently active
- **Inactive** → `#8B6508` (Bronze Gold) — not currently active
- **Archived** → `#3a3a3a` (Dark Gray) — archived from active play
- **Dead** → `#3a3a3a` (Dark Gray) — character has died
- **Missing** → `#5a4a2a` (Brown Gray) — status unknown
- **Draft** → `#8B6508` (Bronze Gold) — work in progress
- **Finalized** → `#1a6b3a` (Forest Green) — complete
- **Neutral** → `rgba(139, 0, 0, 0.2)` (Semi-transparent Red) — default/unknown

---

## Implementation Notes

### Color Usage
- Colors are applied via CSS custom properties: `--clan-badge-color`
- Badge background uses: `background-color: var(--clan-badge-color)`
- Text color is standardized: `#f5e6d3` (Parchment Light) for readability

### Name Variations
The system supports multiple name variations for the same clan:
- "Assamite", "Banu Haqim", "Banu Haqim (Assamite)" all map to the same color
- "Followers of Set" and "Setite" map to the same color
- "Daughter of Cacophony" and "Daughters of Cacophony" map to the same color

### Fallback Behavior
If a clan or faction name is not found in the palette:
- The name is displayed as plain text (no badge)
- No color styling is applied
- HTML entities are properly escaped for security

---

## Color Reference Table

| Clan/Faction | Color Code | Color Name | Visual Description |
|--------------|------------|------------|-------------------|
| Assamite / Banu Haqim | `#2E3192` | Deep Blue | Rich, dark blue |
| Brujah | `#B22222` | Fire Brick Red | Deep red-orange |
| Caitiff | `#708090` | Slate Gray | Medium gray-blue |
| Followers of Set / Setite | `#8B6C37` | Dark Khaki | Brownish yellow-green |
| Daughter(s) of Cacophony | `#FF69B4` | Hot Pink | Bright pink |
| Gangrel | `#228B22` | Forest Green | Deep green |
| Giovanni | `#556B2F` | Dark Olive Green | Olive-tinted green |
| Lasombra | `#1A1A40` | Dark Navy | Very dark blue-black |
| Malkavian | `#6A0DAD` | Purple | Deep purple |
| Nosferatu | `#556B5D` | Dark Slate Gray | Gray with green tint |
| Ravnos | `#008B8B` | Dark Cyan | Deep cyan-blue |
| Toreador | `#C71585` | Medium Violet Red | Purple-pink |
| Tremere | `#8B008B` | Dark Magenta | Deep purple-red |
| Tzimisce | `#99CC00` | Yellow Green | Bright yellow-green |
| Ventrue | `#1F3A93` | Royal Blue | Rich blue |
| Ghoul | `#8B4513` | Saddle Brown | Brown |
| **Anarch** | `#DC143C` | Crimson | Bright red |
| **Camarilla** | `#8B6508` | Bronze Gold | Brownish gold |
| **Sabbat** | `#1A0F0F` | Dusk Brown-Black | Near black |

---

## Integration Points

### PHP Function
The `render_clan_badge()` function in `admin/admin_panel.php` handles badge rendering:
- Accepts clan/faction name as string parameter
- Returns HTML badge with appropriate color
- Handles name normalization (lowercase, trimming)
- Supports all name variations listed above

### CSS Classes
Badge styling is defined in `css/admin_panel.css`:
- `.clan-badge` - Base badge class with padding, border-radius, font styling
- Text color: `#f5e6d3` (Parchment Light) for contrast
- Letter spacing: `0.4px` for readability

### Database Integration
Clan names stored in the database should match the palette keys (case-insensitive) for proper badge rendering.

---

## Color Selection Rationale

### Clan Colors
- Colors are chosen to reflect clan themes and lore
- Each color is distinct enough to avoid confusion
- Colors work well on dark backgrounds (site theme)
- High contrast with text color for accessibility

### Political Faction Colors
- **Anarch** (`#DC143C`): Crimson represents rebellion and revolution
- **Camarilla** (`#8B6508`): Bronze Gold represents tradition, authority, and establishment
- **Sabbat** (`#1A0F0F`): Dusk Brown-Black represents darkness, chaos, and the sect's brutal nature

---

## Maintenance

When adding new clans or factions:
1. Add color entry to the palette in `admin/admin_panel.php`
2. Update this documentation
3. Test badge rendering in admin panel
4. Verify color contrast for accessibility
5. Ensure color doesn't conflict with existing palette

---

---

## CSS Variables

The primary UI colors are also defined as CSS custom properties in `css/global.css`:

```css
--bg-dark: #0d0606;
--bg-darker: #1a0f0f;
--deep-maroon: #2a1515;
--dusk-brown-black: #1a0f0f;
--blood-red: #8B0000;
--muted-gold: #d4b06d;
--teal-moonlight: #0B3C49;
--text-light: #f5e6d3;
--text-mid: #d4b06d;
```

Use these CSS variables in stylesheets for consistency across the project.

---

## Related Documentation

- [Art_Bible_V_UI_&_WEB_ART_SYSTEM__.md](Art_Bible_V_UI_&_WEB_ART_SYSTEM__.md) - UI color system and component styling
- [Art_Bible_XI_ITEMS_SYSTEM__.md](Art_Bible_XI_ITEMS_SYSTEM__.md) - Item badge system and item visual standards
- [Art_Bible_III_LOCATION_&_ARCHITECTURE_SYSTEM__.md](Art_Bible_III_LOCATION_&_ARCHITECTURE_SYSTEM__.md) - Location color and lighting standards
- `admin/admin_panel.php` - Clan badge rendering implementation
- `css/admin_panel.css` - Badge styling
- `css/admin_items.css` - Item badge colors
- `css/admin_locations.css` - Location badge colors
- `css/global.css` - Primary UI color variables