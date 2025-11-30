# Valley by Night Art Bible - Distilled Rules

This document extracts the key aesthetic constraints, do/don't guidelines, and technical requirements from the complete Art Bible. For full details, see the chapter files in `docs/`.

## Core Aesthetic Principles

### Visual Style
- **Gothic-Noir** - Dark, moody, atmospheric
- **Phoenix 1994** - Period-accurate details and environments
- **Emotional Restraint** - Subtle, controlled expressions and movement
- **Desert Modern** - Concrete, glass, dust, sparse vegetation

### Color Palette (MANDATORY)
- Gothic Black: `#0d0606`
- Dusk Brown-Black: `#1a0f0f`
- Blood Red: `#8B0000`
- Parchment Light: `#f5e6d3`
- Muted Gold: `#d4b06d`
- Teal Moonlight: `#0B3C49`
- Desert Amber: `#C87B3E`

## Portrait System Rules

### DO
- Use 1024×1024 resolution (1:1 aspect ratio)
- Key light at 35–45°
- Soft rim light from upper-right
- Warm/candlelight or cold/moonlight palette
- Camera height: eye-level
- Focal length: 50mm or 85mm
- Framing: head + upper torso
- Eyes must be visible and emotional anchors
- Convey: restrained emotion, hidden danger, noir tension, introspection, slow-burning menace
- Format: PNG or WEBP
- Shadows must retain detail

### DON'T
- Exaggerated cartoon emotion
- Bright smiles
- Comedic expressions
- Harsh digital specular
- Compression artifacts

### Clan-Specific Motifs
- **Toreador**: soft bloom, velvet textures, warm candlelight
- **Brujah**: cracked concrete textures, warm sodium lighting, tougher shadows
- **Gangrel**: desert moonlight, earthy tones, slight feral hints (not literal)
- **Nosferatu**: harsh industrial rim, muted palette, shadow concealment
- **Malkavian**: fractured symmetry, violet edge light, subtle distortion
- **Ventrue**: marble & gold accents, businesslike demeanor, cold-blue fill lighting
- **Giovanni**: grayscale candlelight, Italian classic motifs
- **Setite**: red velvet, gold serpents, incense haze

## Cinematic System Rules

### DO
- 30–60 seconds total (ideal: 45 seconds)
- 7–9 shots maximum
- 5–7 VO lines
- Lens: 35mm, 50mm, 85mm
- Movement: slow, deliberate
- Depth of field: shallow to medium
- Lighting: directional, moody, low saturation, filmic
- Color grade: soft vignette, slight film grain, enhanced noir contrast, warm/cool split tones, subtle haze
- Characters: controlled gestures, predatory stillness, emotional restraint
- Disciplines: subtle representation only

### DON'T
- Handheld or chaotic camera movement
- Dutch angle (except Malkavian scenes)
- Pure white LED lighting
- Flat, even lighting
- Full daylight
- Slapstick
- High action
- Superheroic movement
- Exaggerated supernatural effects

### Lighting Rules
- Warm gold practicals
- Sodium amber street lights
- Teal moonlight
- Crimson neon accents
- Marble candlelight for Giovanni
- Red velvet glow for Setites

## Location & Architecture Rules

### DO
- Fuse three styles: Phoenix 1994 Architecture, Gothic Noir, Desert Modern
- Use core color palette (see above)
- Warm interiors, amber streetlights, teal moonlit exteriors
- Deep shadows, soft haze
- Textures: peeling paint, cracked walls, dusty floors, desert sand buildup, rusted metal
- Props: CRTs, rotary phones, paper files, 90s signage, chain-link fences, neon signs
- Camera: cinematic framing (rule of thirds), medium-wide or wide angles, strong silhouettes

### DON'T
- Bright modern LED lighting
- Futuristic architecture
- Clean, sterile visuals
- Crowded streets
- Modern vehicles (use 80s/90s only)

### Location Categories
- **Camarilla Elysium**: candlelit marble halls, gold accents, velvet curtains
- **Giovanni Scottsdale Estate**: Italian influence, candlelight, dark wood, stone floors
- **Setite Theater**: red velvet curtains, gold serpentine décor, incense haze
- **Anarch Territory**: cracked pavement, chain-link fences, graffiti murals, flickering neon
- **Arizona State Hospital**: cold institutional halls, flickering fluorescent lights
- **Gangrel Desert Zones**: moonlit ridges, saguaro silhouettes, dry riverbeds
- **Mesa Mage Skyscraper**: lone skyscraper, cold interior lighting, impossible geometry hints

## 3D Asset System Rules

### DO
- Character Creator 5 → Blender workflow
- 4K textures initially, downscaled to 2K for in-game/UI
- PBR workflow: BaseColor, Roughness, Metallic, Normal, Ambient Occlusion
- 2048×2048 textures standard (1024×1024 for small props, 4096×4096 for hero characters)
- Era-accurate vehicles: 80s/90s sedans, pickup trucks, muscle cars, police cruisers 1990–1994
- Clothing: denim, leather, cotton weave, polyester, velvet (Toreador/Setite), wool blends
- Show wear: slight wear at edges, minor discoloration, dust buildup, stitching imperfections

### DON'T
- Modern vehicles
- Plastic shine on skin
- Lacquered hair shine
- Clean, new-looking clothing
- Spec/gloss workflow (use Metallic/Roughness)

### Technical Standards
- Models: FBX, OBJ
- Textures: PNG (preferred), WEBP for UI, EXR optional
- Blender scenes: BLEND
- SubD 0 during animation, SubD 2 ONLY at render

## UI & Web Art Rules

### DO
- Match core color palette
- Gothic-Noir aesthetic
- Period-appropriate UI elements (1994)
- Consistent typography
- Responsive design principles

### DON'T
- Modern flat design
- Bright, saturated colors
- Contemporary UI patterns

## Storyboard & Animatic Rules

### DO
- Follow cinematic structure (7–9 shots)
- Maintain visual consistency
- Include lighting notes
- Reference location guides

### DON'T
- Deviate from established shot structure
- Ignore lighting requirements

## Marketing Materials Rules

### DO
- Maintain gothic-noir aesthetic
- Use core color palette
- Period-appropriate design (1994)
- Clan-specific visual cues

### DON'T
- Modern marketing aesthetics
- Bright, cheerful designs
- Contemporary design trends

## Floorplan & Blueprint Rules

### DO
- Match location architecture
- Include spatial accuracy
- Reference location guides
- Maintain scale consistency

### DON'T
- Inconsistent scales
- Modern architectural elements
- Ignore location system rules

## Naming & Folder Structure Rules

### DO
- Use consistent naming conventions
- Organize by category (portraits, cinematics, locations, etc.)
- Include version numbers
- Use descriptive filenames

### DON'T
- Inconsistent naming
- Unorganized file structures
- Ambiguous filenames

## Technical Requirements Summary

### Resolutions
- Portraits: 1024×1024
- Cinematics: 1920×1080
- Location Art: 1920×1080 (primary), 1024×1024 (tiles), 2048×2048 (textures)
- 3D Textures: 2048×2048 standard

### File Formats
- Images: PNG or WEBP
- 3D Models: FBX, OBJ
- Blender: BLEND
- Textures: PNG (preferred), WEBP for UI

### Quality Standards
- No compression artifacts
- Noise subtle and filmic
- Shadows must retain detail
- Medium pore detail on skin
- Natural undertones

## Integration Rules

All art systems must integrate with:
- Cinematic system (Part II)
- UI palette (Part V)
- Floorplan system (Part VIII)
- 3D asset system (Part IV)
- Location system (Part III)

## Enforcement

These rules are **mandatory** for all VbN art assets. Deviations must be approved and documented. When in doubt, refer to the full Art Bible chapters in `docs/`.

