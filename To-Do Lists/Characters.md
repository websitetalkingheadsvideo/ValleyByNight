# Characters To-Do List

## A. Missing Histories - Read/Mapping Issue

### Problem Analysis


### Solution: Update Summary Generator
- [ ] Explicitly list which column the summary generator is reading
- [ ] Add logging for "present but unread" vs "actually null"
- [ ] Prevent rewriting canon that already exists
- [ ] Fix JOIN loss issues (LEFT JOIN incorrectly or filtering on nullable field)

---

## B. Appearance as "Later-Stage Enrichment" - ✅ Confirmed Correct

**Status:** No system changes needed. Current behavior is correct.

- [x] Appearance ≠ identity (confirmed)
- [x] Appearance is revealed, not mandatory (correct approach)
- [x] Current behavior maps well to cinematic intros, art pipeline, NPC discovery

---

## C. Relationships - Must Address at Data Model Level

### Problem: Current Model is Insufficient
- [ ] Existing relationships JSON array is:
  - [ ] Descriptive only
  - [ ] Unidirectional
  - [ ] Non-authoritative
- [ ] Current model insufficient for:
  - [ ] Sire–Childe lineage
  - [ ] Blood Bonds
  - [ ] Status inheritance
  - [ ] Discipline teaching permissions
  - [ ] Political consequences

### Solution: Create First-Class Relationships Table
- [ ] Design `character_relationships` table schema:
  - [ ] `id` (primary key)
  - [ ] `character_id` (foreign key)
  - [ ] `related_character_id` (foreign key)
  - [ ] `relationship_type` (sire, childe, blood_bond, ally, rival, ghoul_domitor, etc.)
  - [ ] `strength` / `stage` (for blood bonds especially)
  - [ ] `is_primary` (boolean)
  - [ ] `created_at` (timestamp)

### Key Rules for Implementation
- [ ] Sire → Childe is one row
- [ ] Childe → Sire is a second row (both rows canonical and enforced)
- [ ] Aligns with Vampire's obsession with lineage

---

## D. Blood System - Almost Entirely Correct, Not Missing Rules

### 1. blood_pool_maximum
- [x] Derived from generation (correct)
- [x] Should not be hand-edited per character (correct)
- [ ] Create lookup table `generation_blood_limits`:
  - [ ] `generation` (primary key)
  - [ ] `blood_pool_max`
  - [ ] `blood_per_turn`
- [ ] This aligns with Laws of the Night Revised

### 2. blood_pool_current
- [x] This is character state (correct)
- [x] Most of the time equals max (correct)
- [ ] Keep on character sheet as volatile state
- [ ] Update via:
  - [ ] Feeding
  - [ ] Discipline use
  - [ ] Frenzy outcomes
  - [ ] Downtime resolution
- [ ] Needed for scene continuity, edge cases, Masquerade failures

### 3. blood_per_turn
- [x] Should not live on character sheet long-term (correct)
- [ ] Look up by agent based on generation
- [ ] Never manually edit
- [ ] If currently stored per character, treat as cached/derived data

### 4. Optional Later Additions (Not Required Now)
- [ ] Per-night free rouse abstraction
- [ ] Blood bond tracking (already planned)
- [ ] Vitae flavor flags (Giovanni, Setite, etc.)

---

## E. Humans & Ghouls Split + Phreak Correction

### Humans & Ghouls
- [ ] They should not be Kindred records long-term
- [ ] Correct direction:
  - [ ] `characters` → Kindred only
  - [ ] Separate `mortals` table
  - [ ] Separate `ghouls` table
- [ ] Optional: Use shared `base_characters` ID if polymorphism needed later
- [ ] Plan now to avoid pain later (don't need to implement immediately)

### Phreak Data Integrity Bug
- [ ] **Facts:**
  - [x] Phreak is Nosferatu
  - [x] Has appearance (art exists)
  - [x] Has biography
  - [ ] Labeled human (incorrect)
  - [ ] Missing mechanics

- [ ] **Investigation:**
  - [ ] Import/update scripts are overwriting or skipping fields
  - [ ] Possibly keyed off `clan = N/A`
  - [ ] Use Phreak as test case for repair tooling

---

## F. Character Repair Subsystem

### Create `character_repair.php`

#### Scan Phase
- [ ] Iterate over Kindred
- [ ] Flag missing:
  - [ ] Biography
  - [ ] Appearance
  - [ ] Abilities
  - [ ] Disciplines
  - [ ] Backgrounds
  - [ ] Merits/Flaws
  - [ ] Clan mismatch
  - [ ] Generation anomalies

#### Display
- [ ] Table view showing:
  - [ ] Character name
  - [ ] Clan
  - [ ] Generation
  - [ ] List of missing components (with icons)

#### Action
- [ ] Admin clicks character
- [ ] Modal opens with:
  - [ ] Only missing fields editable
  - [ ] Pre-filled where partial data exists
- [ ] Save → updates DB
- [ ] Create audit log entry

---

## G. Discipline Changes - Requires Two Coordinated Actions

### 1. Character Creator Page Update
- [ ] Update to reflect new discipline structure
- [ ] Respect:
  - [ ] Clan access
  - [ ] Paths vs base disciplines
  - [ ] Revised power handling
- [ ] **Critical:** Until this is done, any new character risks corruption

### 2. Discipline Migration Script
- [ ] Create one-time updater script
- [ ] Scan `character_disciplines` table
- [ ] Map old discipline names → new schema
- [ ] Normalize:
  - [ ] Dots
  - [ ] Powers
  - [ ] Path linkage
- [ ] Log:
  - [ ] Clean conversions
  - [ ] Warnings
  - [ ] Failures
- [ ] Script requirements:
  - [ ] Idempotent
  - [ ] Read-only preview first
  - [ ] Then committed version

---

## H. Sire–Childe Relationships - Non-Negotiable in VtM

### Why This Must Be Elevated
- [ ] Sire defines:
  - [ ] Clan legitimacy
  - [ ] Status inheritance
  - [ ] Teaching rights
  - [ ] Blood bond risk
  - [ ] Political guilt

### Requirements
- [ ] Must be explicit
- [ ] Must be directional
- [ ] Must be queryable
- [ ] Move into new table (instinct is correct - see Section C)

---

## Notes

- **Key Principle:** Fix root causes, not symptoms
- **Test Case:** Use Phreak for repair tooling validation
- **Priority:** Discipline changes and Sire–Childe relationships are critical for system integrity
- **Timing:** Humans/Ghouls split can be planned now but implemented later

