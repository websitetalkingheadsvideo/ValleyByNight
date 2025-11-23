# Valley by Night — Location Style Guide

**Version:** 1.0  
**Document Type:** Worldbuilding & Production Style Guide  
**Audience:** 3D Artists, Environment Artists, Writers, and Cursor Automation

---

## 🎯 Purpose
This guide establishes the **visual, architectural, narrative, and cinematic standards** for all *locations* in **Valley by Night**. It ensures that every environment—whether a nightclub, hospital wing, alleyway, mansion exterior, or desert outpost—matches the unified neo-noir, 1994 Phoenix aesthetic used in your character art and cinematic intro guides.

This guide is tightly aligned with:
- **Cinematic Intro Guide** (visual tone, lighting, narrative voice)
- **Character Art Guide** (color, atmosphere, contrast)
- **location_template.json** (database-ready structure)

Locations differ from rooms in that they describe the *overall environment* or *site*, while rooms describe interior spaces within them.

---

## 🧱 Core Principles
Every location must follow these foundational rules:

### **1. Realistic Phoenix Geography (1994)**
- Use recognizable Phoenix atmospheres: heat shimmer, desert flora, suburban and urban textures.
- Streets, signage, and building styles match mid-1990s Arizona.
- Avoid modern architecture (post-2000 angles, LED signage, sleek glass façades).

### **2. Cinematic Neo-Noir Tone**
- High contrast lighting, strong shadows, subtle haze.
- Warm desert ambers against teal or blue-black night tones.
- Presence of dust, haze, cigarette smoke, distant sodium lights.

### **3. Accuracy for 3D Artists**
Every location must include:
- Estimated dimensions (±1 foot outdoors, ±1 inch for entryways/important elements)
- Traffic flow (how people/cars enter and move)
- Primary architectural style
- All notable lighting sources
- Era-appropriate signage or branded objects

### **4. Avoid Redundancy**
- Location-level supernatural, magical, or security notes **should not** be duplicated in room entries.
- Only include site-wide effects here.

### **5. Cinematic Description + GM Summary**
Each location write-up contains:
1. **Cinematic Description**: atmospheric, visual
2. **GM Summary**: functional, gameplay-focused, factual

---

## 🏜️ Section 1 — Cinematic Style Standards

### **Lighting Themes**
Locations use broader lighting rules than rooms. They define:
- Exterior ambient tone (streetlights, neon, moonlight)
- Primary color palette
- Contrast level
- Environmental atmospheric effects

Use the palette from the Character Art & Cinematic Guides:
- **Desert Amber** for warm light
- **Cool Teal** for shadows
- **Deep Crimson** for emotional intensity
- **Noir Blue-Black** for night tone

### **Environmental Motion & Texture**
Include subtle motion or texture:
- Dust drifting across parking lots
- Flickering neon
- Heat ripple
- Insects, distant traffic hum

### **Era Cues (1994)**
Every location must contain at least one recognizable 1994-era reference:
- Store signage (Blockbuster, Radio Shack)
- Vehicles (Ford Taurus, Chevy pickup, Honda Civic)
- Posters, magazines, soda machines
- Brick-style cell towers (primitive) if needed

Avoid smartphones, LED lights, modern security cameras, EV chargers, etc.

### **Brand Placement**
Use era-appropriate but loosely named brands:
- "A Panasonic camcorder poster"
- "Pepsi machine faded by the sun"
- "A Marlboro billboard near the lot"

---

## 🏛️ Section 2 — Architectural Standards

### **Scale & Dimension Requirements**
For location entries, include:
- Lot size estimate (±1 foot)
- Building height (stories + approximate total height)
- Vehicle areas, alleys, parking
- Roofline description

### **Façade Details**
Always describe:
- Main entry (doors, lighting, signage)
- Exterior materials (brick, stucco, glass blocks, steel)
- Window layout (important for lighting)
- Age & condition (new, worn, cracked, faded)

### **Surroundings**
Describe:
- Adjacent buildings
- Street layout
- Noise level
- Visibility to mortals
- Any features that hint at faction ties or secrecy

---

## 🧭 Section 3 — Traffic & Accessibility

### **Entry & Exit Points**
A usable location description must state:
- Primary entrance
- Side entrances
- Service doors
- Vehicle access
- Any concealed entries (if location-level)

### **Movement Flow**
Describe how characters naturally move:
- Long hallways
- Courtyards
- Staircases
- Parking lot through-lines

---

## 🌬️ Section 4 — Atmosphere Guidelines

Atmosphere at the *location level* sets the tone before entering any room.

### **Components**
Must include:
- **Emotional tone** (tense, elegant, decaying, vibrant)
- **Significant scents** (chlorine from a pool, exhaust from the street)
- **Significant sounds** (traffic hum, cicadas, neon buzz)

Avoid supernatural in the location-level atmosphere unless site-wide.

---

## 🛡️ Section 5 — Security & Masquerade Notes

### **When to Include Supernatural**
Only include at the location-level if the *entire site* is:
- Ward-protected
- Cursed or blessed
- Vampire-owned and modified
- Part of ritual grounds

### **Standard Security Elements**
- Locks
- Guards
- Cameras (1994 style)
- Floodlights
- Barriers, gates

Use the fields in the location JSON, but do not duplicate room-level details.

---

## 🏷️ Section 6 — Location JSON Mapping

Each section maps to `location_template.json` fields:

### **Summary** → `summary`
- 1–2 sentences, factual

### **Cinematic Description** → `description`
- 2–5 paragraphs, atmospheric, vivid

### **Notes** → `notes`
- Artistic or structural notes not seen by players

### **District, Address, Coordinates** → `district`, `address`, `latitude`, `longitude`

### **Owner & Faction** → `owner_type`, `faction`, `owner_notes`
- Only include faction markers if essential

### **Security** → `security_*` fields

### **Utilities** → `utility_*` fields

### **Special Traits** → `has_supernatural`, `magical_protection`, etc.

### **Rooms** → `rooms`
- List of interior spaces

---

## 🧪 Section 7 — Validation Checklist
Every completed location must pass this list:

- [ ] Era cues (1994) present
- [ ] At least one brand reference
- [ ] Cinematic description matches palette and lighting standards
- [ ] Traffic flow described
- [ ] Access & security complete
- [ ] No duplication of room-level info
- [ ] Coordinates OR neighborhood conventions used
- [ ] Exterior lighting sources documented
- [ ] Emotional tone clearly stated

---

## 🏁 Section 8 — Example Location
*(A complete example will be added once the style rules are validated.)*

