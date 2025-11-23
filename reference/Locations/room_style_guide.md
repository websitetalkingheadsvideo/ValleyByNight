# Valley by Night — Room Style Guide

**Version:** 1.0  
**Document Type:** Interior Environment & Production Guide  
**Audience:** 3D Artists, Writers, Designers, Cursor Automation

---

## 🎯 Purpose
This guide defines the standards, expectations, and procedures for describing **individual rooms** within any location in *Valley by Night.* Each room entry must:

- Deliver a **cinematic, atmospheric description** (matching the franchise tone)
- Provide a **functional GM summary** for gameplay
- Include **structural and architectural detail** appropriate for 3D artists
- Map cleanly to `room_template.json`
- Maintain continuity with the location-level description

Locations describe *the site as a whole.* Rooms describe the *interior experience.*

This guide is the companion to the **Location Style Guide**, using the same cinematic rules as the Character Art & Intro Guides.

---

## 🧱 Section 1 — Core Principles

### **1. Cinematic + Structural Duality**
Every room must have **two sections:**
1. **Cinematic Description** — rich, atmospheric, for players & artists
2. **GM Summary** — factual, gameplay-friendly, containing no prose

### **2. No Redundancy**
Room entries **must not repeat** information that belongs in the parent location.
Include only unique room-level qualities.

### **3. Accuracy for 3D Artists**
Room details must include:
- Dimensions within **1 inch**
- Ceiling height when unusual (low, vaulted, double-height)
- Door and window placement
- Light sources and angles
- Furniture list
- Textures, materials, condition (brief)
- Traffic flow/pathing

### **4. 1994 Era Cues**
Every room must include **at least one era-appropriate object**:
- Tube TV, VHS tapes
- Panasonic/Sharp electronics
- Radio Shack radios
- Marlboro, Camel, Zippo
- Magazines, posters, packaging from the era

Use loose brand placement (e.g., "a Panasonic VCR").

### **5. Lighting Standard**
Use natural-language cinematic lighting following the Character & Cinematic Guides:
- Warm amber interior bulbs
- Cool teal moonlight through windows
- Soft shadows, high contrast
- Dust particles, haze, smoke where appropriate

---

## 🏛️ Section 2 — Architectural Detail Standards

### **Dimensions (Width x Length)**
- Provide exact estimates within **±1 inch**
- Use format: `14'2" x 22'0"`

### **Ceiling Height**
Include when:
- Higher than 10 ft
- Lower than 7.5 ft
- Vaulted or angled
- Exposed beams

### **Doors & Entry Points**
All room descriptions must include:
- All entrances/exits
- Their position relative to the room (north wall, south corner, right-side hallway)
- Door type (wood, steel, glass, sliding)

If multiple door options exist, **ordinal order** follows:
> Choose the door/path on the right side first.

### **Windows & Natural Light**
Specify:
- Number of windows
- Exterior orientation (east/west/etc.)
- Height from floor
- Light behavior (moonlight, neon bleed, streetlight flicker)

### **Surfaces & Materials**
Describe briefly:
- Wall materials
- Flooring type
- Ceiling surface
- Condition (clean, cracked, stained)

### **Furniture Layout**
Use concise lists in the `contents` field.
Include:
- Material
- Placement
- Quantity

Leave detailed descriptions to the **Cinematic Description** section.

---

## 🌬️ Section 3 — Atmosphere

Each room needs its own atmosphere statement summarizing:
- Emotional tone
- Significant scents (cigarette smoke, mold, perfume)
- Significant sounds (AC hum, buzzing neon, distant traffic)
- Rare supernatural cues (only when room-specific)

Avoid overwhelming the entry with unnecessary sensory overload.

---

## 💡 Section 4 — Lighting Standards (Interior)

### **Lighting Format**
Use natural language:
> "Warm amber table lamps with soft shadows; faint cool moonlight through the east window; medium-high contrast."

### **Required Lighting Elements**
- Primary source (lamps, overhead fixtures)
- Secondary source (moonlight, neon bleed)
- Ambient fill
- Shadow character

### **Environmental Effects**
Use sparingly:
- Dust in beams of light
- Subtle cigarette haze
- Old HVAC hum

---

## 🛋️ Section 5 — Contents & Interactive Elements

### **Contents Field (Short List)**
Enter a concise comma-separated list:
> "sofa, coffee table, two chairs, floor lamp, bookshelf"

### **Hidden Features**
Use this only for:
- Secret storage
- Hidden panels
- Concealed ritual marks
- Unusual architectural anomalies

### **Special Properties**
Use only when:
- Magical effects tied specifically to this room
- Unique supernatural footprint

---

## 🧭 Section 6 — Traffic Flow & Sightlines

### **Movement**
Include:
- Entry→center→exit path
- Any chokepoints
- Primary activity zones

### **Sightlines**
Describe:
- What is visible from the entrance
- Blind spots
- Any intentionally framed views

---

## 🧪 Section 7 — GM Summary Guidelines

### **Purpose**
The GM Summary provides:
- Layout
- Hazards
- Gameplay features
- Hiding spots
- Security notes
- Interactable elements
- Investigation clues

### **Format**
Use short bullet points.
No prose.
No cinematic language.

---

## 🏷️ Section 8 — Room JSON Mapping

Map each part of the write-up to `room_template.json`:

- **Summary** → `summary`
- **Cinematic Description** → `description`
- **Notes** → `notes`
- **Dimensions** → `dimensions`
- **Ceiling Height** → `ceiling_height`
- **Capacity** → `capacity`
- **Lighting** → `lighting`
- **Atmosphere** → `atmosphere`
- **Contents** → `contents`
- **Hidden Features** → `hidden_features`
- **Connections** → `connections` (doors/hallways)
- **Access Requirements** → `access_requirements`
- **Security** → all `security_*` fields
- **Utilities** → all `utility_*` fields
- **Special Properties** → `special_properties`, `magical_properties`
- **Prestige Level** → `prestige_level`

---

## 🧩 Section 9 — Validation Checklist

Every room must meet the following:

- [ ] Cinematic description matches franchise tone
- [ ] Accurate dimensions
- [ ] Door and window placement included
- [ ] Lighting obeys warm/teal cinematic rule
- [ ] Era-appropriate brand object included
- [ ] Atmosphere defined
- [ ] Contents list complete
- [ ] Hidden features included only when relevant
- [ ] GM summary present
- [ ] No duplication of location-level details

---

## 🧰 Section 10 — How Cursor Should Use This Guide

This section specifies exactly how **Cursor** (and Taskmaster, when invoked) should process instructions when generating, validating, or modifying a room entry for the Valley by Night database.

Cursor must follow these rules whenever the user provides a **room name** and **location name**, or instructs Cursor to "create a new room".

---

### 🔧 **1. Procedural Order for Room Creation**
Cursor should follow this sequence **in order**:

1. **Load this Room Style Guide** as a reference document.
2. **Parse user input** for:
   - Room name
   - Parent location name
   - Any special notes the user provides
3. **Check the parent location** (via database, JSON file, or file passed to Cursor) and import parent-level details **without duplicating them**.
4. **Generate the Room JSON structure** using `room_template.json` fields.
5. **Fill in mandatory architectural details**:
   - Dimensions
   - Ceiling height (if unusual)
   - Doors & windows (with orientation)
   - Furniture list
6. **Apply the cinematic standards** from this guide to create:
   - A Cinematic Description (multi‑paragraph)
   - A GM Summary (bullet list)
7. **Insert brand cues (1994 era)** into the Cinematic Description.
8. **Determine ordinal position**:
   - Assign based on spatial logic
   - When ambiguous, choose the room on the **right** first
9. **Validate** using the Room Validation Checklist.
10. **Output** the finished result as:
   - A JSON block ready for database insertion
   - OR a complete `.md` file if asked by the user

---

### 🧠 **2. Mandatory Rules Cursor Must Enforce**
Cursor must always:

- Use **natural-language lighting descriptions**, *not* structured lighting blocks.
- Include at least **one 1994-brand item**.
- Keep **contents** as a list and **description** cinematic.
- Keep GM Summary objective and non‑cinematic.
- Avoid duplicating any information already defined at the location level.
- Follow all architectural, lighting, and atmospheric rules outlined in this guide.
- Use the **right-side-first rule** for ordinal decisions.

If any required information is missing (dimensions, windows, etc.), Cursor must **ask the user for clarification** before generating the final JSON.

---

### 🗄️ **3. Database Output Requirements**
When generating JSON:

- All field names must match `room_template.json` exactly.
- All strings must be plain text (no markdown inside JSON fields).
- The `description` must contain **Cinematic Description only**.
- The GM Summary goes into `notes`, not `description`.
- Connections must list **explicit room IDs or names**.
- Hidden supernatural notes go into `special_properties` or `magical_properties`.

---

### 🤖 **4. Cursor Auto‑Correction Behaviors**
Cursor should automatically correct:

- Missing 1994 references → add one.
- Missing lighting details → infer based on window orientation.
- Missing dimensions → ask user before generating JSON.
- Missing emotional tone → derive from location style.
- Missing contents list → summarize furniture from cinematic description.

Cursor should **never**:
- Invent high‑technology (post‑1994)
- Add supernatural traits unless user indicates
- Duplicate location-level supernatural notes
- Use modern lighting language (no LEDs)

---

### 🧪 **5. Validation Step (Mandatory)**
Before presenting output, Cursor must re-check the entry against the **Room Validation Checklist** in this guide.

If any item fails, Cursor must:
- Ask clarifying questions OR
- Run a second pass to correct the missing content

Cursor must *not* deliver an incomplete room.

---

## 🏁 Section 11 — Example Room
*(A complete example will be added after validation.)*

