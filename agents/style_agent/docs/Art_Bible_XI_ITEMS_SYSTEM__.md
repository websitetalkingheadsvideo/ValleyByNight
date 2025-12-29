# PART XI — ITEMS SYSTEM  
## (Full, Exhaustive Version)

## 1. Overview
The Items System defines all visual standards, image requirements, UI display rules, badge systems, and aesthetic guidelines for items within the Valley by Night project. Items include weapons, armor, tools, consumables, artifacts, vehicles, and miscellaneous equipment that appear in the admin panel, character equipment interfaces, location inventories, and game documentation.

All item visuals must integrate with:
- the noir art palette
- the UI & Web Art System (Part V)
- the portrait system (for item holders/characters)
- the 1994 Phoenix aesthetic
- Bootstrap-based components
- custom VbN CSS overrides

---

# 2. Item Types & Categories

## Primary Item Types
- **Weapon** — Melee and ranged weapons, firearms, blades, improvised weapons
- **Armor** — Body armor, protective gear, shields
- **Tool** — Utility items, equipment, devices
- **Consumable** — Food, drink, drugs, single-use items
- **Artifact** — Magical items, supernatural objects, rare artifacts
- **Vehicle** — Cars, motorcycles, boats, aircraft
- **Misc** — Miscellaneous items that don't fit other categories
- **Ammunition** — Bullets, arrows, projectiles
- **Electronics** — Tech devices, communication equipment
- **Gear** — General equipment and supplies
- **Magical** — Supernatural items with magical properties
- **Token** — Currency, markers, collectibles
- **Magical Tool** — Tools with supernatural properties
- **Trait** — Character traits represented as items
- **Equipment** — General equipment category
- **Accessory** — Clothing accessories, jewelry
- **Clothing** — Apparel and garments
- **Book** — Books, documents, written materials
- **Food** — Consumable food items
- **Drink** — Beverages
- **Drug** — Pharmaceutical or illicit substances

## Item Categories
Categories are flexible and type-specific:
- **Weapons:** Melee, Ranged, Firearm, Blade, Improvised
- **Armor:** Body, Head, Legs, Arms, Shield
- **Vehicles:** Land, Air, Water
- **Artifacts:** Detection/Information, Power, Protection, Cursed, Blessed
- **Tools:** Utility, Lockpicking, Investigation, Communication
- **Consumables:** Food, Drink, Medical, Drug, Temporary Enhancement

---

# 3. Color Palette (Canonical)

## Primary Colors
Matches Part V — UI & Web Art System:
- Gothic Black: **#0d0606**
- Dusk Brown-Black: **#1a0f0f**
- Blood Red: **#8B0000**
- Muted Gold: **#d4b06d** (used for borders, text-mid, accents)
- Parchment Light: **#f5e6d3**
- Deep Maroon: **#2a1515**
- Teal Moonlight: **#0B3C49** (used for info panels)

## Type Badge Colors
Each item type has a distinct badge color for visual identification:

- **Weapon:** `#8B0000` (Blood Red) — danger, combat
- **Armor:** `#4a4a4a` (Dark Gray) — protection, defense
- **Tool:** `#8B6508` (Bronze Gold) — utility, craftsmanship
- **Consumable:** `#1a6b3a` (Forest Green) — nature, sustenance
- **Artifact:** `#4a1a6b` (Deep Purple) — mystery, power
- **Ammunition:** `#6b4513` (Saddle Brown) — ammunition, supplies
- **Electronics:** `#1a4a6b` (Steel Blue) — technology, modern
- **Gear:** `#4a4a2a` (Olive Drab) — equipment, military
- **Magical:** `#6b1a4a` (Dark Magenta) — supernatural, mystical
- **Token:** `#8B6508` (Bronze Gold) — currency, value
- **Magical Tool:** `#4a1a6b` (Deep Purple) — enchanted utility
- **Trait:** `#1a6b3a` (Forest Green) — character attribute
- **Misc:** `#5a4a2a` (Brown Gray) — miscellaneous
- **Equipment:** `#6B4423` (Saddle Brown) — general equipment
- **Accessory:** `#4A4A6B` (Slate Blue) — adornment
- **Clothing:** `#6B4A4A` (Dusty Rose) — apparel
- **Vehicle:** `#4A6B4A` (Moss Green) — transportation
- **Book:** `#6B6B4A` (Olive Yellow) — knowledge
- **Food:** `#8B4513` (Sienna) — sustenance
- **Drink:** `#8B008B` (Dark Magenta) — beverages
- **Drug:** `#8B0000` (Blood Red) — substances
- **Electronic:** `#2F4F4F` (Dark Slate Gray) — technology

## Rarity Badge Colors
Rarity levels use distinct color coding:

- **Common:** `#9E9E9E` (Medium Gray) — standard items
- **Uncommon:** `#4CAF50` (Green) — slightly rare
- **Rare:** `#2196F3` (Blue) — uncommon finds
- **Very Rare:** `rgba(255, 69, 0, 0.2)` with `#FF4500` text and border — extremely rare
- **Epic:** `#9C27B0` (Purple) — legendary tier
- **Legendary:** `#FF9800` (Orange) — mythic tier

---

# 4. Typography

## Fonts
Matches Part V — UI & Web Art System:
- **Headers:** *IM Fell English SC* (Small Caps variant preferred), *Libre Baskerville*
- **Body Text:** *Source Serif Pro* (with Times New Roman fallback)
- **Labels:** *Libre Baskerville* (form labels, UI labels, item property labels)
- **Code/Monospace:** *Source Code Pro* (for technical data, IDs, codes)

## Text Colors
- **Item Names:** Parchment Light (`#f5e6d3`) — primary text
- **Item Descriptions:** Muted Gold (`#d4b06d`) — body text
- **Property Labels:** Parchment Light (`#f5e6d3`) with Libre Baskerville font
- **Property Values:** Muted Gold (`#d4b06d`) or Parchment Light depending on context
- **Table Headers:** Parchment Light (`#f5e6d3`) with Libre Baskerville font
- **Table Body:** Muted Gold (`#d4b06d`) or `#d4c4b0` (slightly darker gold)

---

# 5. Item Image Standards

## Resolution & Aspect Ratio
- **Primary Resolution:** 1024×1024 (1:1 square format)
- **Alternative:** 512×512 for smaller displays
- **Format:** JPG or PNG (WEBP preferred for web optimization)
- **File Naming:** Lowercase with underscores (e.g., `katana_sword.jpg`, `kevlar_vest.png`)

## Image Display Rules
- **Container:** Radial gradient background (blood red radial gradient: `rgba(160, 0, 0, 0.55)` → `rgba(96, 0, 0, 0.85)`)
- **Border:** 3px solid muted gold (`#c9a96e` or `#d4b06d`)
- **Border Radius:** 1rem (16px) for wrapper, 0.85rem (13.6px) for image
- **Padding:** 12px wrapper padding, 1rem (16px) image padding
- **Aspect Ratio:** 1:1 (square) maintained via CSS `aspect-ratio: 1`
- **Object Fit:** `cover` for primary images, `contain` for fallback images
- **Shadow:** `0 10px 18px rgba(0, 0, 0, 0.6)` for wrapper, `0 6px 14px rgba(0, 0, 0, 0.45)` for image

## Image Fallback System
1. **Primary Image:** Item-specific image from `uploads/Items/` directory
2. **Fallback Image:** Generic placeholder or type-specific default
3. **Placeholder Text:** "No Image" displayed in muted gold (`#d4c4b0`) with Source Serif Pro font

## Image Styling Classes
- `.item-image-wrapper` — Outer container with gradient background and gold border
- `.item-image-media` — Inner container maintaining aspect ratio
- `.item-image` — Primary image with cover fit
- `.item-image-fallback` — Fallback image with contain fit and dark background
- `.item-image-placeholder` — Text placeholder when no image available

---

# 6. UI Display Standards

## Table Display
Items are displayed in tables with the following specifications:

### Table Background
- **Radial Gradient:** `radial-gradient(circle at center, rgba(139, 0, 0, 0.4) 0%, rgba(139, 0, 0, 0.2) 40%, rgba(26, 15, 15, 0.6) 100%)`
- **Border:** 2px solid blood red (`#8B0000`)
- **Border Radius:** 8px (0.5rem)

### Table Headers
- **Background:** Linear gradient (`linear-gradient(to bottom, rgba(139, 0, 0, 0.75) 0%, rgba(96, 0, 0, 0.95) 100%)`)
- **Text Color:** Parchment Light (`#f5e6d3`)
- **Font:** Libre Baskerville (var(--font-title))
- **Font Weight:** 700 (bold)
- **Hover Effect:** Background changes to `rgba(179, 0, 0, 0.3)`
- **Sort Icons:** Opacity 0.4 default, opacity 1 when sorted (▲ ascending, ▼ descending)

### Table Rows
- **Background:** Alternating subtle red backgrounds
  - Even rows: `rgba(26, 15, 15, 0.3)`
  - Odd rows: `rgba(26, 15, 15, 0.1)`
- **Hover Effect:** `rgba(139, 0, 0, 0.15)` background
- **Border:** 1px solid `rgba(139, 0, 0, 0.2)` between rows
- **Text Color:** `#d4c4b0` (muted gold variant)
- **Font:** Source Serif Pro (var(--font-body))

### Table Column Widths
- **ID:** 5% (min-width: 50px)
- **Name:** 20% (truncated with ellipsis, tooltip on hover)
- **Type:** 8%
- **Category:** 15% (truncated with ellipsis)
- **Damage:** 6%
- **Range:** 9% (truncated with ellipsis, title attribute for native tooltip)
- **Rarity:** 8%
- **Price:** 8%
- **Actions:** 10% (min-width: 160px, sticky right column)

## Modal Display
Items are viewed and edited in modals following Part V standards:

### Modal Structure
- **Background:** Radial gradient (blood red `#8B0000` → `#820000` → `#1a0f0f` from center)
- **Border:** 3px solid muted gold (`#d4b06d`)
- **Border Radius:** 10px (0.625rem)
- **Header:** IM Fell English font, parchment text (`#f5e6d3`)
- **Body Text:** Muted gold (`#d4b06d`) for text-mid

### Item View Modal
- **Image Section:** Full-width image wrapper with gradient background and gold border
- **Info Grid:** 2-column grid layout (1 column on mobile)
- **Section Headers:** Gold headings with Libre Baskerville font
- **Property Display:** Label-value pairs with parchment labels and muted gold values

### Item Edit Modal
- **Form Fields:** Dark background (`rgba(26, 15, 15, 0.6)`)
- **Borders:** 2px red (`rgba(139, 0, 0, 0.4)`)
- **Focus State:** Blood-red glow (`box-shadow: 0 0 10px rgba(139, 0, 0, 0.3)`)
- **Labels:** Libre Baskerville font, parchment color
- **Special Powers Section:** Collapsible section with gold header and gradient background

## Badge System
Badges are used to display item types and rarities:

### Badge Styling
- **Padding:** 4px 10px
- **Border Radius:** 4px
- **Font Size:** 0.85em
- **Font Weight:** Bold
- **Text Color:** Parchment Light (`#f5e6d3`)
- **Background:** Type-specific or rarity-specific color

### Badge Usage
- **Type Badges:** Display item type (Weapon, Armor, Tool, etc.)
- **Rarity Badges:** Display rarity level (Common, Uncommon, Rare, etc.)
- **Combined Display:** Type and rarity badges can appear together

---

# 7. Item Properties Display

## Required Properties
All items must display:
- **Name** — Item name (parchment text, bold)
- **Type** — Item type (badge)
- **Category** — Item category (text)
- **Rarity** — Rarity level (badge)
- **Price** — Item price in currency (muted gold text)

## Conditional Properties
Display based on item type:

### Weapons
- **Damage** — Damage value or rating
- **Range** — Melee, Ranged, or specific range
- **Requirements** — Strength, Dexterity, or other attribute requirements

### Armor
- **Defense** — Defense value or rating
- **Requirements** — Strength or other attribute requirements

### Vehicles
- **Speed** — Maximum speed
- **Capacity** — Passenger/load capacity
- **Fuel Efficiency** — Fuel consumption rate

### Artifacts
- **Special Powers** — Magical or supernatural abilities
- **Consequences** — Negative effects or limitations
- **Requirements** — Mental, Occult, or other attribute requirements

### Consumables
- **Effect** — Temporary enhancement or effect description
- **Duration** — How long the effect lasts

## Property Display Format
- **Labels:** Libre Baskerville font, parchment color (`#f5e6d3`), bold
- **Values:** Source Serif Pro font, muted gold (`#d4b06d`) or parchment depending on importance
- **Layout:** Label-value pairs in grid or list format
- **Spacing:** Generous padding between property groups

---

# 8. Special Powers & Consequences Section

## Visual Design
Special powers and consequences are displayed in collapsible sections:

### Header
- **Background:** Radial gradient (`rgba(160, 0, 0, 0.55)` → `rgba(96, 0, 0, 0.85)`)
- **Border:** 3px solid muted gold (`#c9a96e`)
- **Border Radius:** 0.75rem (12px)
- **Padding:** 1rem 1.25rem
- **Text:** Parchment color (`#f5e6d3`), Libre Baskerville font, 700 weight
- **Hover Effect:** Enhanced gradient and gold border glow
- **Toggle Icon:** Rotates 180° when expanded

### Content Area
- **Background:** Linear gradient (`rgba(42, 21, 21, 0.6)` → `rgba(26, 15, 15, 0.65)`)
- **Border:** 2px solid `rgba(139, 0, 0, 0.35)`, no top border
- **Border Radius:** 0 0 0.75rem 0.75rem
- **Padding:** 1.5rem
- **Box Shadow:** Inset highlight and outer shadow for depth

### Text Styling
- **Section Headers (h4):** Muted gold (`#c9a96e`), Libre Baskerville, 600 weight, border-bottom
- **Content Text:** Muted gold variant (`#d4c4b0`), Source Serif Pro, 1rem size, 1.6 line-height
- **Text Blocks:** Dark background (`rgba(0, 0, 0, 0.2)`), border-radius 0.5rem, left border accent

---

# 9. Filter & Search Interface

## Filter Buttons
- **Style:** Bootstrap `.btn-outline-danger` with active state
- **Active State:** Background fill with blood red gradient
- **Hover:** Enhanced border and background
- **Text:** Parchment color, Source Serif Pro font

## Dropdown Filters
- **Background:** Dark (`bg-dark`)
- **Text Color:** Parchment Light (`text-light`)
- **Border:** Red (`border-danger`)
- **Font:** Source Serif Pro

## Search Input
- **Background:** Dark (`bg-dark`)
- **Text Color:** Parchment Light (`text-light`)
- **Border:** Red (`border-danger`)
- **Placeholder:** Muted gold or gray
- **Icon:** Search emoji (🔍) in placeholder

---

# 10. Action Buttons

## Button Types
- **View:** Blue tint (`rgba(0, 100, 200, 0.2)` background, `rgba(0, 100, 200, 0.4)` border)
- **Edit:** Gold tint (`rgba(139, 100, 0, 0.2)` background, `rgba(139, 100, 0, 0.4)` border)
- **Assign:** Green tint (`rgba(0, 150, 0, 0.2)` background, `rgba(0, 150, 0, 0.4)` border)
- **Delete:** Red tint (`rgba(139, 0, 0, 0.2)` background, `rgba(139, 0, 0, 0.4)` border)

## Button Styling
- **Size:** 30px × 30px (28px on mobile)
- **Border Radius:** 4px
- **Background:** Semi-transparent with type-specific color
- **Border:** 1px solid with matching color
- **Hover:** Enhanced background opacity, scale transform (1.1)
- **Icon:** Centered, appropriate icon for action type

---

# 11. Statistics Cards

## Card Display
Item statistics are displayed in Bootstrap cards:

- **Background:** Linear gradient (`#2a1515` → `#1a0f0f`)
- **Border:** 2px red (`#8B0000`)
- **Border Radius:** 0.75rem (12px)
- **Text Alignment:** Center
- **Number Display:** Large, bold, parchment color
- **Label Display:** Smaller, muted gold, italic or normal weight

## Stat Categories
- Total Items
- Weapons
- Armor
- Tools
- Consumables
- Artifacts
- Misc

---

# 12. Integration with Other Systems

## Character Equipment
- Items assigned to characters appear in character view modals
- Equipment display follows item image and badge standards
- Character-specific item notes may be displayed

## Location Inventories
- Items located in specific locations use the same visual standards
- Location-specific notes (hidden, locked, condition) displayed with muted gold text
- Quantity displayed with item name

## Admin Panel Integration
- Items page follows Part V — UI & Web Art System standards
- Navigation buttons use red outline style with active state
- Modal system uses gold borders and radial gradient backgrounds

---

# 13. Technical Rules

## File Formats
- **Images:** JPG, PNG, or WEBP
- **Naming:** Lowercase with underscores, descriptive
- **Storage:** `uploads/Items/` directory
- **Database:** Image filename stored in `items.image` field

## CSS Classes
- **Type Badges:** `.badge-{type}` (e.g., `.badge-weapon`, `.badge-armor`)
- **Rarity Badges:** `.badge-{rarity}` (e.g., `.badge-common`, `.badge-rare`)
- **Item Image:** `.item-image-wrapper`, `.item-image-media`, `.item-image`
- **Special Powers:** `.special-powers-section`, `.special-powers-header`, `.special-powers-content`

## Responsive Design
- **Mobile:** Single column layout, smaller badges, reduced padding
- **Tablet:** 2-column grid where applicable
- **Desktop:** Full multi-column layout with all features

## Accessibility
- **Alt Text:** All item images must have descriptive alt text
- **Tooltips:** Truncated text columns show full text on hover
- **Keyboard Navigation:** All interactive elements keyboard accessible
- **Color Contrast:** All text meets WCAG AA standards (4.5:1 minimum)

---

# 14. Production Guidelines

## Image Creation
- **Style:** Noir, low saturation, strong shadows
- **Lighting:** Controlled, dramatic, noir-inspired
- **Background:** Dark, textured, or blurred environment
- **Composition:** Centered item, clear visibility of key features
- **Color Grading:** Muted palette, gold and crimson accents where appropriate

## Item Description Writing
- **Tone:** Gothic-noir, descriptive, atmospheric
- **Length:** 1-3 sentences for basic items, paragraphs for artifacts
- **Style:** POV-style descriptions when appropriate
- **Details:** Include relevant game mechanics, history, or lore

## Badge Assignment
- **Type Badge:** Always required, matches item type exactly
- **Rarity Badge:** Always required, matches rarity level exactly
- **Display:** Both badges visible in table and modal views

---

# 15. Examples & Reference

## Example Item Display
```
[Item Image: 1024×1024, gold border, radial gradient background]

Name: Katana
Type: [Weapon Badge - Blood Red]
Category: Melee
Rarity: [Common Badge - Medium Gray]

Damage: 2
Range: Melee
Requirements: Strength 2, Dexterity 3
Price: 500

Description: A traditional Japanese sword known for its sharpness and cutting ability.
```

## Example Artifact Display
```
[Item Image: 1024×1024, gold border, radial gradient background]

Name: Ball of Truth
Type: [Artifact Badge - Deep Purple]
Category: Detection/Information
Rarity: [Rare Badge - Blue]

Requirements: Mental 3
Price: 50,000

Description: A polished marble created by a locally famous New York magician in the early 1960s...

[Special Powers Section - Collapsible]
Special Powers: Detects direct lies when held...
Consequences: Only usable once every 24 hours...
```

---

# 16. Future Enhancements

## Planned Features
- **3D Item Models:** Integration with Part IV — 3D Asset System
- **Item Animations:** Subtle hover effects, glow animations for artifacts
- **Clan-Specific Item Theming:** Visual variations based on item origin or clan association
- **Item Sets:** Visual grouping for related items
- **Item Comparison View:** Side-by-side comparison interface

## Integration Opportunities
- **Cinematic System:** Items featured in cinematic sequences
- **Location System:** Items displayed in location blueprints
- **Portrait System:** Characters holding or using items in portraits

---

*This Art Bible defines the complete visual and technical standards for the Items System within Valley by Night. All item-related visuals, UI components, and displays must adhere to these specifications while maintaining integration with the broader Art Bible system.*

