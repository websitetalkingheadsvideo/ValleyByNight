# Valley by Night — Location Questionnaire

**Version:** 1.0  
**Purpose:** Rapidly define a location's identity, tone, and rules before committing to full JSON, layouts, or visuals.  
**Usage:** Conversational, short answers encouraged. "I don't know yet" is valid.

This questionnaire feeds into:
- Location Instance JSON
- Location Style Summary JSON
- Layout / Visual artifacts

---

## ✅ Questionnaire Mode — Updated Output Contract

### Core Change (Plain Language)

A location questionnaire is **not considered "complete"** until it produces:

- A visual blueprint (spatial diagram / floorplan-style image)
- A PNG mood board / image board

**Text-only completion is no longer sufficient.**

---

## Revised Questionnaire Workflow (Authoritative)

### STEP 1 — Fill Questionnaire (unchanged)

You answer the lightweight questionnaire:

- Identity
- Tone
- Visual language
- Flow
- Supernatural state
- Design rules

This remains conversational and fast.

### STEP 2 — Lock Intent (implicit)

Once answers are confirmed:

- Emotional tone
- Visual rules
- Movement logic
- Constraints (what must / must not appear)

At this point, design intent is frozen for this iteration.

### STEP 3 — Mandatory Visual Outputs (NEW, REQUIRED)

#### 🔷 Output A — Visual Blueprint (PNG)

**What it is**

A top-down or sectional visual layout

**Shows:**

- Entrances
- Major zones
- Movement logic
- Defensive or liminal areas

**Not technical CAD**  
**Not decorative art**

**Purpose**

- Immediate spatial understanding
- 2D stand-in for gameplay
- Future 3D blockout reference

**Rule**

> If someone can't understand how the space works by looking at it, it's not valid.

#### 🔷 Output B — Mood Board / Image Board (PNG)

**What it is**

A curated image collage

**Focused on:**

- Materials
- Lighting
- Density
- Contrast
- Emotional texture

**Purpose**

- Visual grounding
- Artist reference
- Prevents aesthetic drift

**Rule**

> If the mood board could apply to a different location, it failed.

### STEP 4 — Optional Text Artifacts (Secondary)

Only after visuals exist do we generate:

- Location Style Summary JSON
- Layout JSON
- Instance JSON updates

**Text supports visuals — not the other way around.**

---

## Updated Definition of "Done"

A location is **DONE** (for this phase) when:

- ✅ Questionnaire answered
- ✅ Visual blueprint PNG exists
- ✅ Mood board PNG exists

Everything else is supplementary.

---

## How This Will Work Going Forward (Operationally)

**When you say:**

> "Run questionnaire mode for a new location"

**I will:**

1. Ask the questionnaire questions
2. Confirm answers
3. Automatically generate:
   - A blueprint image
   - A mood board image
4. Then (only if you want):
   - Generate JSON / text artifacts

**No more drifting into abstraction without visuals.**

---

## SECTION 1 — Core Identity (Required)

1. **Location name**  
   (Working title is fine.)

2. **What role does this location serve in the story or game?**  
   (e.g. haven, social hub, battleground, investigation site, fallback shelter)

3. **Who controls or claims this place?**  
   (Individual, faction, institution, contested, "no one officially")

4. **What emotions should dominate this location?**  
   (Choose 1–3: safe, paranoid, seductive, oppressive, prison-like, chaotic, sterile, etc.)

---

## SECTION 2 — Emotional & Cinematic Tone (Required)

5. **First reaction on arrival:**  
   One sentence. Gut feeling only.

6. **Is the location loud or quiet? Busy or still? Watched or ignored?**

7. **Does the place invite entry, or make people hesitate? Why?**

---

## SECTION 3 — Visual Language (Required)

8. **Dominant visual motif**  
   (e.g. fabric, neon, decay, clutter, sterility, industrial utility)

9. **Color restraint / palette rules**  
   (Are there accents? Is everything muted? Desaturated? Extreme contrast?)

10. **Defining materials**  
    (Concrete, velvet, steel, cardboard, glass block, wood, etc.)

11. **Lighting behavior**  
    (Even / harsh / high-contrast / shadow-heavy / practical / theatrical)

12. **What must NEVER appear visually in this location?**

---

## SECTION 4 — Era & Reality Anchor (Required)

13. **What makes this unmistakably 1994?**  
    (Technology, brands, objects, infrastructure.)

14. **How visible is this place to ordinary mortals?**  
    (Highly visible / hidden in plain sight / actively concealed.)

---

## SECTION 5 — Flow & Access (Required)

15. **Primary way in**  
    (Street door, stairwell, alley, service access, lobby, etc.)

16. **How does movement feel inside?**  
    (Open, guided, restricted, maze-like, hierarchical.)

17. **Unspoken rules of behavior**  
    (What should occupants *not* do?)

---

## SECTION 6 — Supernatural & Masquerade (Conditional)

18. **Is the entire location affected by something supernatural?**  
    (If no, mark as not applicable.)

19. **How does the location avoid Masquerade violations?**

> If `has_supernatural = 0`, related fields will be **null** in the location JSON.

---

## SECTION 7 — Design Rules (High Value)

20. **List 1–3 truths that must always be true about this location.**

21. **One way this location should never be portrayed.**

---

## SECTION 8 — Production Reality (Optional)

22. **How often will the player visit this location?**  
    (One-off / repeated / major arc.)

23. **Current representation:**  
    - Text only  
    - 2D stand-in  
    - Minimal interactive  
    - Future 3D environment

---

## Completion Notes

- This questionnaire is **not a schema** — it is an intent capture tool.
- Answers map cleanly to:
  - Style Summary JSON
  - Layout JSON
  - Visual reference boards
- Fields may be revised over time as the location evolves.

**Design principle:**  
> Write truth first. Optimize later.
