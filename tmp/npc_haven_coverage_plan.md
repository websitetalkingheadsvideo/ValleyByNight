# NPC Haven Coverage - Planning Phase

## Objective
Create `reference/Locations/plan/character list.json` containing a complete list of all NPCs currently in the game with their haven status and recommendations.

## Step-by-Step Plan

### 1. NPC Discovery
**Goal:** Identify every canonical source of "NPCs currently in the game"

**Sources to check:**
- **Database:** `characters` table where `pc = 0` OR `pc IS NULL`
  - Query: `SELECT id, character_name, clan, biography, status FROM characters WHERE (pc = 0 OR pc IS NULL) ORDER BY character_name`
- **Files:** `reference/Characters/*.json` files
  - Scan all JSON files in `reference/Characters/` directory
  - Check for `pc` field = 0 or missing (defaults to NPC)
  - Include subdirectories: `Added to Database/`, `Ghouls/`, `Wraiths/`

**Inclusion Rule:**
- NPC is included if:
  - Present in database with `pc = 0` or `pc IS NULL`, OR
  - Present in JSON file with `pc = 0` or `pc` field missing/undefined
- Exclude: Characters explicitly marked as `pc = 1` (Player Characters)

**Action:** Query database and scan file system to build master NPC list with deduplication by `character_name`.

---

### 2. NPC Identity/Linking
**Goal:** Define how an NPC in files maps to a DB row

**Linking Strategy:**
- **Primary Key:** `character_name` (case-insensitive matching)
- **Matching Rules:**
  1. Exact match: `character_name` in DB = `character_name` in JSON (case-insensitive)
  2. If multiple DB matches: Use most recent (`updated_at DESC`) or `id` with lowest value
  3. If no DB match but JSON exists: `inDatabase = "no"`
  4. If DB match but no JSON: `inDatabase = "yes"` (DB-only)
  5. If ambiguous (multiple candidates with same name): `inDatabase = "unknown"` + note in `sourceRefs`

**Tie-break Rules:**
- If multiple DB rows with same `character_name`: Use `id` ASC (lowest ID = canonical)
- If multiple JSON files with same name: Use file with most complete data or most recent modification date

**Action:** For each NPC, attempt to link DB row to JSON file. Set `inDatabase` field accordingly.

---

### 3. Haven Detection
**Goal:** Check `locations` table for each NPC and extract numeric `locations.id` into `havenId`

**Database Schema:**
- Table: `locations` (NOT `havens` - the prompt mentions "havens table" but codebase uses `locations`)
- Haven filter: `type = 'Haven'`
- Haven ID: `locations.id` (integer)

**Linking Method:**
- **Option 1:** Check `owner_notes` field in `locations` table for character name mentions
  - Query: `SELECT id, name, owner_notes FROM locations WHERE type = 'Haven' AND (owner_notes LIKE '%[character_name]%' OR name LIKE '%[character_name]%')`
- **Option 2:** Check if character name appears in location name
- **Option 3:** Manual review of `data/havens.json` export file for character references

**Detection Logic:**
1. For each NPC, search `locations` table where `type = 'Haven'`
2. Match if:
   - `owner_notes` contains character name (case-insensitive), OR
   - `name` contains character name (case-insensitive), OR
   - `owner_type = 'Individual'` AND `owner_notes` matches character name
3. If match found: `hasHaven = "yes"`, `havenId = locations.id`
4. If no match: `hasHaven = "no"`, `havenId = null`

**Action:** Query database for each NPC to find associated haven. Extract `id` as `havenId`.

---

### 4. District/Town Classification
**Goal:** Define allowed values based on Phoenix map labels and infer district from existing location data

**Allowed District/Town Values** (from `data/havens.json` analysis):
- "Downtown Phoenix"
- "Southern Scottsdale"
- "Mesa - Industrial District (East Mesa, near Superstition Springs)"
- "Central Phoenix / 24th Street Area (between Indian School and Camelback roads)"
- "Northern Scottsdale"
- "Mesa"
- "Outer Phoenix / Desert Park Area"
- "Scottsdale / Camelback Mountain Area"
- "North Phoenix / Dunlap and 32nd Avenue Area"
- "South Phoenix / South Mountain Area"
- "Downtown Phoenix / Roosevelt Row (Roosevelt Street and Grand Avenue Area)"
- "West Phoenix / Camelback Mountain Area (Horse Zoning District)"
- "Downtown Phoenix / Central Avenue and Indian School Road Area"
- "Downtown Phoenix / Industrial District"

**Inference Rules:**
1. If NPC has haven (`hasHaven = "yes"`):
   - Extract `district` field from `locations.district` for that `havenId`
   - Use exact value from database
2. If NPC has no haven but has location info in JSON:
   - Check `domain_and_haven.primary_haven_name` or `domain_and_haven.location_notes`
   - Parse for district/town keywords
   - Match to allowed values list
3. If NPC has no haven and no location info:
   - Set `districtOrTown = null`

**Action:** For each NPC, extract district from haven record (if exists) or infer from character data. Validate against allowed values list.

---

### 5. Recommendation Generation
**Goal:** Produce consistent `recommendedHavenDescription` including special-needs accommodations

**Data Sources:**
- **Clan:** From `characters.clan` or JSON `clan` field
- **Biography:** From `characters.biography` or JSON `biography` field
- **Special Needs:** Extract from:
  - Biography mentions of: security, accessibility, feeding restrictions, ritual space, library, medical facilities, workshop, armory
  - Clan-specific needs (e.g., Tremere need ritual space, Nosferatu need hidden/secure, Toreador need aesthetic spaces)

**Recommendation Template:**
"[Clan-appropriate haven type] with [special needs]. [Security considerations]. [Feeding/accessibility notes]."

**Examples:**
- Tremere: "A secure, warded haven with dedicated ritual space for Thaumaturgical practices; includes reinforced entry points, warding rituals, and a library for occult research."
- Nosferatu: "A hidden, highly secure haven with multiple concealed entrances; includes blackout measures, reinforced security, and feeding accommodations that minimize exposure."
- Toreador: "An aesthetically pleasing haven that blends with mortal society; includes security measures appropriate for the district and feeding opportunities in cultural/artistic venues."

**Action:** For each NPC, analyze clan, biography, and special needs. Generate concise recommendation (2-3 sentences max).

---

### 6. File Construction
**Goal:** Ensure every NPC appears exactly once with all required keys

**Required Fields (exact keys):**
- `characterName` (string) - from `character_name`
- `hasHaven` ("yes" | "no") - from Step 3
- `havenId` (number | null) - from Step 3
- `districtOrTown` (string | null) - from Step 4
- `inDatabase` ("yes" | "no" | "unknown") - from Step 2
- `havenJsonExists` ("yes" | "no" | "unknown") - check if JSON file exists in `reference/Locations/` matching haven name
- `recommendedHavenDescription` (string) - from Step 5
- `sourceRefs` (array of strings) - file paths and DB keys

**Deduplication:**
- Group by `characterName` (case-insensitive)
- If duplicate found: Merge data, prefer DB data over JSON, note conflicts in `sourceRefs`

**JSON Structure:**
```json
{
  "generatedAt": "YYYY-MM-DD",
  "characters": [
    {
      "characterName": "Example NPC",
      "hasHaven": "no",
      "havenId": null,
      "districtOrTown": "Central Phoenix",
      "inDatabase": "yes",
      "havenJsonExists": "unknown",
      "recommendedHavenDescription": "...",
      "sourceRefs": ["DB:characters.id=123", "reference/Characters/example-npc.json"]
    }
  ]
}
```

**Action:** Build JSON structure, ensure all NPCs included once, all required fields present, valid JSON format.

---

### 7. Haven JSON Existence Check
**Goal:** Report if haven JSON file exists (without creating files)

**Check Method:**
1. If `hasHaven = "yes"` and `havenId` is set:
   - Query: `SELECT name FROM locations WHERE id = [havenId]`
   - Check if file exists: `reference/Locations/[haven_name].json` (normalized filename)
   - Normalize: Convert to filename-safe format (spaces to underscores, special chars removed)
2. If file exists: `havenJsonExists = "yes"`
3. If file doesn't exist: `havenJsonExists = "no"`
4. If `hasHaven = "no"`: `havenJsonExists = "unknown"`

**Action:** For each NPC with haven, check for corresponding JSON file in `reference/Locations/`.

---

### 8. Role Transition Step
**Upon explicit plan approval, transition to the Execution Phase role.**

**Execution Phase will:**
1. Create directory: `reference/Locations/plan/` (if missing)
2. Create/update file: `reference/Locations/plan/character list.json`
3. Populate from identified NPC sources and DB haven checks
4. **NOT** create haven JSON files (only set `havenJsonExists` appropriately)

**Stop Conditions:**
- If NPC sources conflict (duplicate names/aliases/multiple records): STOP and ask which is canonical
- If DB linking rule is unclear for specific NPC: Set `inDatabase = "unknown"` and add note to `sourceRefs` indicating ambiguity

---

## Questions to Resolve Before Execution

1. **Haven-Character Linking:** How exactly do characters link to havens in the database?
   - Is it through `owner_notes` field?
   - Is there a separate junction table?
   - Should we check `owner_type = 'Individual'` and match `owner_notes` to character name?

2. **Phoenix Map Districts:** Are the district values from `data/havens.json` the canonical list, or should we reference an actual map file/image?

3. **NPC Inclusion:** Should we include:
   - Ghouls (in `reference/Characters/Ghouls/`)?
   - Wraiths (in `reference/Characters/Wraiths/`)?
   - Characters marked as `status = 'archived'` or `status = 'inactive'`?

4. **Character Name Variations:** How should we handle:
   - Nicknames vs full names?
   - Name changes over time?
   - Characters with multiple aliases?

---

## Waiting for plan approval before proceeding.
