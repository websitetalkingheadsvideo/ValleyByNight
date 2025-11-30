# PART VIII — FLOORPLAN & BLUEPRINT SYSTEM  
## (Full, Exhaustive Version)

## 1. Overview
The Floorplan & Blueprint System defines all top‑down architectural representations used in Valley by Night:
- Havens
- Locations
- Organization headquarters
- Elysium layouts
- Desert outposts
- Institutional zones
- Dungeon-like structures
- Setite theater interiors
- Giovanni estate architecture

Floorplans provide a unified visual language for:
- storytelling reference
- UI presentation
- cinematic layout planning
- game design
- location consistency

This section is exhaustive and binds all spatial logic across the chronicle.

---

# 2. Visual Philosophy

## 2.1 Blueprint Identity
All VbN maps follow a strict noir‑blueprint hybrid style:
- gold linework on black backgrounds  
- parchment linework on parchment backgrounds  
- clean, readable schematics  
- gothic border motifs allowed  
- subtle vignette  

## 2.2 Aesthetic Themes
Floorplans must feel:
- old  
- functional  
- secretive  
- architectural  
- noir  
- dramatic  

They convey a hidden world layered beneath Phoenix 1994.

## 2.3 Forbidden Aesthetics
Do NOT use:
- neon outlines  
- brightly colored rooms  
- modern CAD-perfect lines  
- cute icons  
- isometric perspective  

All maps must be **flat, top-down, architecturally grounded.**

---

# 3. Technical Specifications

## 3.1 Resolutions
- 1920×1080 (large maps)
- 2048×2048 (detailed)
- 1024×1024 (UI tiles)

## 3.2 Color Schemes

### Style A — Gold-on-Black (Primary)
- Background: #0d0606  
- Walls/Lines: #d4b06d  
- Text: #f5e6d3  
- Highlights: #8B0000  

### Style B — Parchment Blueprint (Secondary)
- Background: #f5e6d3  
- Lines: sepia  
- Text: dark brown  
- Shadows: muted gray  

---

# 4. Line Weights

| Element | Weight |
|--------|--------|
| Exterior walls | 5–7 px |
| Interior walls | 3–5 px |
| Doors | 2–3 px |
| Furniture | 2 px |
| Detail lines | 1 px |
| Secret walls | dashed 3 px |

Additional rules:
- Staircases use directional arrows  
- Windows use broken gold lines  
- Columns are filled gold circles  

---

# 5. Labeling & Typography

## Fonts
- Headers: IM Fell English SC  
- Labels: Libre Baskerville serif  
- Notes: Georgia  

## Rules
- All room names must be legible at 100%  
- Keep labels inside rooms whenever possible  
- Use uppercase for room names  

## Format
```
ROOM NAME (Function)
Sub-note if needed
```

Example:
```
ARCHIVE ROOM (Restricted)
Files 1978–1993
```

---

# 6. Architectural Symbols

## Doors
- Swing arc  
- Double arc for double doors  
- Parallel lines for sliding doors  
- Dashed arc for hidden access  

## Windows
- Light gold line with small breaks  

## Stairs
- Up arrow + ascending bars  
- Down arrow + descending bars  

## Elevators
- Square with “E” label  

## Secret Areas
- Dotted lines  
- Golden triangles as hints (optional)  

## Ritual Indicators
- Gold circles  
- Triangles  
- Serpent glyphs for Setites  
- Candle icons for Giovanni  

---

# 7. Shading & Texture

## Interiors
- Light stipple for carpet  
- Parallel hatching for concrete  
- Dotted texture for tile  
- Soft grain everywhere  

## Exteriors
- Desert sand: speckled noise  
- Rock: irregular stipple  
- Grass: sparse cross-hatch  

## Lighting Direction
- Must match VbN global direction: **upper-right (135°)**  

---

# 8. Location-Specific Mapping Styles

## 8.1 Camarilla Elysium
- large open halls  
- balconies  
- stage area  
- long gallery walkways  
- ornamented corners  

## 8.2 Giovanni Estate
- symmetrical layouts  
- central marble foyer  
- ancestral rooms  
- hidden corridors  
- ritual chambers  

## 8.3 Setite Theater
- stage  
- orchestra pit  
- backstage wings  
- VIP section  
- snake-motif ornaments allowed  

## 8.4 Anarch 24th Street
- cramped rooms  
- barricades  
- graffiti icons  
- broken walls  
- makeshift gathering hall  

## 8.5 State Hospital Perimeter
- long corridors  
- observation rooms  
- locked wards  
- fluorescent lighting patterns  

## 8.6 Gangrel Desert Zones
- rugged shapes  
- cabins/trailers  
- animal paths  
- dry riverbeds  

## 8.7 Mesa Mage Skyscraper
- geometric perfection  
- subtly impossible shapes  
- mirrored hallway loops  

## 8.8 Guadalupe Rumor Zones
- tight homes  
- backyards  
- cellars  
- occult marks hidden subtly  

---

# 9. Grid System

Optional:
- 1 square = 5 ft  
- 1 square = 1 m  
- Gold grid at low opacity  

Use grid only when needed.

---

# 10. Templates (Structural)

## 10.1 Small Haven
```
[Entrance]
[Common Room]
[Private Quarters]
[Bathroom]
[Hidden Exit]
```

## 10.2 Medium Haven / Coterie Base
```
[Foyer]
[Common Space]
[Meeting Room]
[Bedrooms]
[Storage]
[Feeding Area]
[Emergency Tunnel]
```

## 10.3 Theater Map (Setite)
```
[Main Stage]
[Backstage]
[Wings]
[Balcony]
[Green Room]
[Storage]
[Trapdoor]
```

## 10.4 Mansion Map (Giovanni/Elysium)
```
[Grand Hall]
[Gallery]
[Library]
[Study]
[Private Rooms]
[Secret Passage]
[Crypt Area]
```

## 10.5 Dungeon / Tunnel
```
[Corridors]
[Cells]
[Chamber]
[Storage]
[Maintenance]
[Tunnel Exit]
```

---

# 11. Prompt Templates

See Part 08b for full detailed prompt library.  
Summary:

### Master Blueprint Prompt
```
Gold-on-black top-down blueprint, gothic noir style, clear walls, serif labels, directional shading from 135°, subtle vignette, desert-modern details.
```

### Parchment Blueprint Prompt
```
Parchment background, sepia lines, aged texture, gothic border, serif labels, subtle wear marks.
```

---

# 12. Purpose Within Chronicle

Floorplans are used for:
- game UI reference  
- storyteller tools  
- cinematic scene planning  
- designing encounters  
- linking narrative beats to physical spaces  

They enforce *spatial continuity* across all chronicle arcs.

---


---
