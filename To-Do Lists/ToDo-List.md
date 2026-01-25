# Valley by Night — Master To-Do List

_Last updated: 2026-01-25_  
_Combined from all to-do lists in this folder_

---

## Core Systems & Agents

- ❌ Implement **Ability Agent** to validate/map Abilities into `character.json`
- ❌ Implement **Discipline Agent** to validate/map Disciplines and powers into `character.json`
- ❌ Implement **Dialogue Agent** integration with blood bond stage (branching only, non-enforcing)
- ❌ Implement **Influence System** (Grapevine-style)
  - ❌ `character_influences` table (influence_type, level 0–5)
  - ❌ UI for viewing/editing influences
  - ❌ Importer from Grapevine XML
- ❌ Implement **Narrative Compiler / Narrative Packages**
  - ❌ Finalize `narrative_packages` schema usage
  - ❌ UI for chapter/arc/session/location/faction scopes
- ❌ Decide and document **Wraith data model**
  - ❌ Keep standalone `wraith_characters` OR
  - ❌ Migrate to unified characters + splat-extension model
  - ❌ Write migration plan
- ❌ Implement **Rituals Agent**
  - ❌ Integrate with `rituals_master`, `character_rituals`, and the Rules database for casting, learning, and validation logic
- ❌ Implement **Paths Agent**
  - ❌ Build agent logic around `paths_master`, `path_powers`, and `character_paths`, including challenge handling and Laws Agent integration
- ❌ Implement **Mandatory Pre-Session Character Questionnaire**
  - ❌ Structured JSON storage
  - ❌ Completion gating before first session
  - ❌ 10–20 XP award rubric (ST approval)
- ❌ Add **read-only helper view**
  - ❌ Derive blood bond stage from `character_blood_drinks`
- ❌ Add **Map Agent**
  - ❌ Development and integration planning
- ❌ Implement **Chronicle Agent** (World State Manager)
  - ❌ Create `chronicles` table schema
  - ❌ Implement world state ownership (current night, time-of-night phase, global stability meters, plotline tracking)
  - ❌ Process time events from Time Agent
  - ❌ Apply rule-driven world changes (scheduled plot escalations, pressure drift, Masquerade degradation)
  - ❌ Manage knowledge & rumor state (threat/faction existence, belief propagation)
  - ❌ Implement auditability & debugging (append-only state log, before/after snapshots)
  - ❌ Enforce single-writer pattern (only Chronicle Agent writes to `chronicles` table)
- ❌ Implement **Time Agent**
  - ❌ Create time event system
  - ❌ Handle time advancement requests (next phase, next night, multiple nights, explicit set)
  - ❌ Model night phases (early, mid, late, pre_dawn)
  - ❌ Emit time events (not direct state mutation)
  - ❌ Integrate with Chronicle Agent for world state updates

---

## Agent Architecture: System-Agnostic Knowledge Access (TM-10)

**Type:** Architecture / Foundation  
**Priority:** High  
**Depends on:** None  
**Applies to:** All current and future VbN agents

**Goal:** Design and continue building VbN agents so they remain usable regardless of whether the project ultimately adopts Vector RAG, Knowledge Graph, GraphRAG, or a hybrid system.

**Core Principle:** Treat **knowledge storage and retrieval as an implementation detail**, not an agent responsibility.

### Architectural Rules

**1. Stable Canonical IDs**
- ❌ All entities must use persistent IDs:
  - ❌ character_id
  - ❌ location_id
  - ❌ rule_id
  - ❌ ritual_id
  - ❌ path_id
  - ❌ discipline_id
  - ❌ plot_id
- ❌ Names are labels, never keys

**2. Knowledge Atom Requirements**
- ❌ Every fact returned to an agent must include:
  - ❌ `entity_id`
  - ❌ `source` (book, page, file, DB row, etc.)
  - ❌ `canon_level` (canon / rumor / inference / placeholder)
  - ❌ `time_context` (baseline 1994-10-08, post-event, etc.)
  - ❌ `confidence` (optional)

**3. Retrieval Abstraction Layer**
- ❌ Agents must never directly call:
  - ❌ vector search
  - ❌ graph traversal
  - ❌ SQL joins beyond their scope
- ❌ All agents use a shared interface
- ❌ Backend implementations may include:
  - ❌ SQLProvider
  - ❌ VectorProvider
  - ❌ GraphProvider
  - ❌ HybridProvider

**4. Data vs Behavior Separation**
- ❌ Agents contain **logic and validation**
- ❌ Lore, rules, and canon live in data stores only
- ❌ No hard-coded lore in agent code

**5. Typed Agent Responses**
- ❌ All agents return structured JSON with stable fields:
  - ❌ entities[]
  - ❌ facts[] (with provenance)
  - ❌ rules[]
  - ❌ constraints[]
  - ❌ next_questions[]
  - ❌ citations[]
- ❌ No free-form lore dumping

**6. Adapter Pattern Enforcement**
- ❌ Future system changes must only affect:
  - ❌ KnowledgeProvider adapters
  - ❌ Retrieval configuration
- ❌ Not agent logic

**Acceptance Criteria:**
- ❌ Existing agents reviewed for direct storage coupling
- ❌ New agents conform to retrieval interface
- ❌ Canonical IDs enforced everywhere
- ❌ Provenance metadata present in agent outputs

**Definition of Done:**
- ❌ Architecture documented
- ❌ At least one agent verified against a mock alternate backend
- ❌ No agent requires refactor when swapping retrieval strategy

---

## Laws / Rules Agent (TM-10)

**Type:** Core System / Rules  
**Priority:** High  
**Depends on:** Clanbooks complete (done)

- ❌ Integrate rescanned core rulebooks into the Rules corpus
- ❌ Normalize rule sections (ritual rules, challenge resolution, disciplines, paths, status, boons, influence)
- ❌ Ensure rules are not duplicated into ritual/path/discipline records
- ❌ Add canonical identifiers for rule fragments (for later RAG linking)
- ❌ Verify agent can answer:
  - ❌ "What rules apply here?"
  - ❌ "Where does this rule come from?"
  - ❌ "What overrides or exceptions exist?"

**Acceptance Criteria:**
- Laws Agent covers all core non-clanbook rules
- No NULL / placeholder rule sections remain
- Other agents can query Laws Agent for rule text by tag or reference

---

## Rules Context Service (TM-11)

**Type:** Architecture / Integration  
**Priority:** Low (Deferred)  
**Depends on:** TM-10

- ❌ Define a standard `rules_context` response shape:
  - ❌ short snippets
  - ❌ source identifiers
  - ❌ applicability tags (challenge_type, tradition, sect, location, etc.)
- ❌ Define how Rituals, Paths, Disciplines, and Abilities agents will call it
- ❌ Add placeholder hooks in agents (no RAG logic yet)

**Acceptance Criteria:**
- Interface spec exists
- No behavior change yet
- Zero hard dependency on RAG infrastructure

---

## GraphRAG Integration (TM-12)

**Type:** Advanced Systems / World Modeling  
**Priority:** Low (Future Phase)  
**Depends on:** TM-11, Nightly Briefing system

**Scope (Deferred):**
- ❌ Nightly Briefings
- ❌ Rumor seeding and belief bias
- ❌ Political consequence modeling
- ❌ Multi-hop "who is affected?" reasoning

---

## Character & Rules Data

- ❌ Audit and replace **Discipline power descriptions**
  - ❌ Add `short_description`
  - ❌ Add `long_description` (full LotN Revised text)
  - ❌ Add `system_text / mechanics` column
  - ❌ Verify accuracy (no simplification)
- ❌ Fill missing data in **paths_master**
  - ❌ Add descriptions for all paths
- ❌ Fill missing data in **path_powers**
  - ❌ Add `system_text`
  - ❌ Add `challenge_type`
  - ❌ Add `challenge_notes`
  - ❌ Audit all Necromancy and Thaumaturgy paths
- ❌ Audit all Necromancy and Thaumaturgy ritual data
  - ❌ Ensure ingredients, requirements, system text, and sources are complete and consistent
- ❌ Create **Phoenix-localized clanbooks**
  - ❌ Complete after clanbook analyses
  - ❌ Incorporate remaining NPC creations
- ❌ Ensure all NPC sheets default to `"player_name": "NPC"`

---

## Character System Issues

### Missing Histories - Read/Mapping Issue

- ❌ Explicitly list which column the summary generator is reading
- ❌ Add logging for "present but unread" vs "actually null"
- ❌ Prevent rewriting canon that already exists
- ❌ Fix JOIN loss issues (LEFT JOIN incorrectly or filtering on nullable field)

### Relationships - Must Address at Data Model Level

**Problem: Current Model is Insufficient**
- ❌ Existing relationships JSON array is:
  - ❌ Descriptive only
  - ❌ Unidirectional
  - ❌ Non-authoritative
- ❌ Current model insufficient for:
  - ❌ Sire–Childe lineage
  - ❌ Blood Bonds
  - ❌ Status inheritance
  - ❌ Discipline teaching permissions
  - ❌ Political consequences

**Solution: Create First-Class Relationships Table**
- ❌ Design `character_relationships` table schema:
  - ❌ `id` (primary key)
  - ❌ `character_id` (foreign key)
  - ❌ `related_character_id` (foreign key)
  - ❌ `relationship_type` (sire, childe, blood_bond, ally, rival, ghoul_domitor, etc.)
  - ❌ `strength` / `stage` (for blood bonds especially)
  - ❌ `is_primary` (boolean)
  - ❌ `created_at` (timestamp)

**Key Rules for Implementation**
- ❌ Sire → Childe is one row
- ❌ Childe → Sire is a second row (both rows canonical and enforced)
- ❌ Aligns with Vampire's obsession with lineage

### Blood System - Almost Entirely Correct, Not Missing Rules

**1. blood_pool_maximum**
- ❌ Create lookup table `generation_blood_limits`:
  - ❌ `generation` (primary key)
  - ❌ `blood_pool_max`
  - ❌ `blood_per_turn`
- ❌ This aligns with Laws of the Night Revised

**2. blood_pool_current**
- ❌ Keep on character sheet as volatile state
- ❌ Update via:
  - ❌ Feeding
  - ❌ Discipline use
  - ❌ Frenzy outcomes
  - ❌ Downtime resolution
- ❌ Needed for scene continuity, edge cases, Masquerade failures

**3. blood_per_turn**
- ❌ Look up by agent based on generation
- ❌ Never manually edit
- ❌ If currently stored per character, treat as cached/derived data

**4. Optional Later Additions (Not Required Now)**
- ❌ Per-night free rouse abstraction
- ❌ Blood bond tracking (already planned)
- ❌ Vitae flavor flags (Giovanni, Setite, etc.)

### Humans & Ghouls Split + Phreak Correction

**Humans & Ghouls**
- ❌ They should not be Kindred records long-term
- ❌ Correct direction:
  - ❌ `characters` → Kindred only
  - ❌ Separate `mortals` table
  - ❌ Separate `ghouls` table
- ❌ Optional: Use shared `base_characters` ID if polymorphism needed later
- ❌ Plan now to avoid pain later (don't need to implement immediately)

**Phreak Data Integrity Bug**
- ❌ **Investigation:**
  - ❌ Import/update scripts are overwriting or skipping fields
  - ❌ Possibly keyed off `clan = N/A`
  - ❌ Use Phreak as test case for repair tooling

### Character Repair Subsystem

**Create `character_repair.php`**

**Scan Phase**
- ❌ Iterate over Kindred
- ❌ Flag missing:
  - ❌ Biography
  - ❌ Appearance
  - ❌ Abilities
  - ❌ Disciplines
  - ❌ Backgrounds
  - ❌ Merits/Flaws
  - ❌ Clan mismatch
  - ❌ Generation anomalies

**Display**
- ❌ Table view showing:
  - ❌ Character name
  - ❌ Clan
  - ❌ Generation
  - ❌ List of missing components (with icons)

**Action**
- ❌ Admin clicks character
- ❌ Modal opens with:
  - ❌ Only missing fields editable
  - ❌ Pre-filled where partial data exists
- ❌ Save → updates DB
- ❌ Create audit log entry

### Discipline Changes - Requires Two Coordinated Actions

**1. Character Creator Page Update**
- ❌ Update to reflect new discipline structure
- ❌ Respect:
  - ❌ Clan access
  - ❌ Paths vs base disciplines
  - ❌ Revised power handling
- ❌ **Critical:** Until this is done, any new character risks corruption

**2. Discipline Migration Script**
- ❌ Create one-time updater script
- ❌ Scan `character_disciplines` table
- ❌ Map old discipline names → new schema
- ❌ Normalize:
  - ❌ Dots
  - ❌ Powers
  - ❌ Path linkage
- ❌ Log:
  - ❌ Clean conversions
  - ❌ Warnings
  - ❌ Failures
- ❌ Script requirements:
  - ❌ Idempotent
  - ❌ Read-only preview first
  - ❌ Then committed version

### Sire–Childe Relationships - Non-Negotiable in VtM

**Why This Must Be Elevated**
- ❌ Sire defines:
  - ❌ Clan legitimacy
  - ❌ Status inheritance
  - ❌ Teaching rights
  - ❌ Blood bond risk
  - ❌ Political guilt

**Requirements**
- ❌ Must be explicit
- ❌ Must be directional
- ❌ Must be queryable
- ❌ Move into new table (instinct is correct - see Relationships section)

---

## Blood Bond System

**Status:** Foundations now, full system later  
**Theme:** Power through intimacy, consent erosion, emotional consequence  
**Scope:** Applies to *all NPCs*, rare in practice, always possible

### PHASE 1 — Foundations (Now / Tonight)

**Data & Tracking (Minimal, Non-Enforcing)**
- ❌ Create `character_blood_drinks` table (event-based, not state-based)
  - ❌ `id`
  - ❌ `drinker_character_id`
  - ❌ `source_character_id`
  - ❌ `drink_date` (night matters)
  - ❌ `notes`
- ❌ Ensure table is **read-only safe** for summaries and agents
- ❌ Do **not** store bond stage as a column (derived only)

**Creature-Type Compatibility**
- ❌ Confirm blood drinks can occur between:
  - ❌ Kindred → Mortal
  - ❌ Kindred → Ghoul
  - ❌ Kindred → Kindred
- ❌ Explicitly disallow invalid cases (e.g. Wraith blood)

**Summary / Diagnostics**
- ❌ Update summaries to:
  - ❌ detect blood-drink history
  - ❌ report bond stage (0–3) **descriptively only**
  - ❌ list orphaned / unresolved blood-drink references
- ❌ Never enforce obedience or block actions in summaries

### PHASE 2 — Dialogue Integration (Later)

**Dialogue Context Flags**
- ❌ Expose derived bond stage to Dialogue Agent:
  - ❌ 0 = none
  - ❌ 1 = fascination
  - ❌ 2 = attachment
  - ❌ 3 = full bond
- ❌ Allow dialogue writers to branch on bond stage
- ❌ Ensure same *intent* yields different dialogue based on stage

**Tone Rules (Narrative, Not Mechanical)**
- ❌ Ghoul NPCs:
  - ❌ may ask for blood
  - ❌ show anxiety / dependence
- ❌ Kindred NPCs:
  - ❌ never ask directly
  - ❌ rationalize, resist, or allow under pressure
- ❌ No "Offer Blood" casual dialogue option

### PHASE 3 — Pressure & Access Rules (Later)

**When Blood Drinking Becomes Possible**
- ❌ Define pressure thresholds:
  - ❌ survival / torpor
  - ❌ severe injury
  - ❌ ritual necessity
  - ❌ emotional collapse
- ❌ Prevent casual or transactional blood exchange
- ❌ Explicitly avoid "boon-for-a-sip" normalization

**Awareness Rules**
- ❌ Decide per NPC:
  - ❌ do they understand blood bond risk at drink #1?
  - ❌ do they recognize danger at drink #2?
- ❌ Support denial, shame, and self-justification arcs

### PHASE 4 — Consequences & World Reaction (Later)

**Social & Political Fallout**
- ❌ Integrate with Rumor Agent:
  - ❌ favoritism
  - ❌ whispers of control
- ❌ Integrate with Status / Harpy reactions
- ❌ Allow Sabbat / rivals to exploit blood-bound NPCs

**Relationship Outcomes**
- ❌ Possible long-term arcs:
  - ❌ devotion
  - ❌ resentment
  - ❌ tragedy
  - ❌ forced separation
  - ❌ death by external forces
- ❌ Avoid binary "good/bad" endings

### PHASE 5 — Agentization (Future)

**Blood Bond Agent (Conceptual)**
- ❌ Inputs:
  - ❌ drink history
  - ❌ dates
  - ❌ creature types
- ❌ Outputs:
  - ❌ bond stage
  - ❌ emotional pressure description
- ❌ Explicitly **do not**:
  - ❌ auto-block player actions
  - ❌ force obedience
- ❌ Agent provides *context*, not control

### PHASE 6 — Expansion Hooks (Far Future)

**Ghoul PC Expansion**
- ❌ Flesh out ghoul-specific stats and progression
- ❌ Enable PC flag for ghouls
- ❌ Reuse existing blood bond system without schema rewrite

**Advanced Mechanics (Optional)**
- ❌ Bond decay over time
- ❌ Ritual bond suppression / breaking
- ❌ Humanity / degeneration interactions

---

## OCR / Text Processing

- ❌ Enforce pinned **OCR cleanup rule**
  - ❌ Rebuild paragraph structure first
  - ❌ Remove layout-induced line breaks
  - ❌ Fix broken/duplicated words
  - ❌ Normalize punctuation and capitalization after structure
- ❌ Perform **structural review pass**
  - ❌ Identify sections missing subheadings
  - ❌ Insert appropriate Markdown subheaders (no prose changes)
- ❌ Perform **semantic review pass**
  - ❌ Identify unlabelled "Example of Play" sections
  - ❌ Decide labeling vs non-destructive tagging approach

---

## Chronicle & Narrative

- ❌ Create **pre-game history / backstory primer**
  - ❌ What PCs would reasonably know before Session 1
  - ❌ Align with opening cutscene acknowledgment
- ❌ Lock **canon timeline rules**
  - ❌ Game start: Saturday, Oct 8, 1994
  - ❌ Elysium: 2nd & 4th Saturdays
  - ❌ Default Elysium: Hawthorn Estate
- ❌ Flesh out **Giovanni presence** in Scottsdale (Camelback area)
- ❌ Flesh out **Mesa mage skyscraper** (late-chronicle reveal)
- ❌ Flesh out **Guadalupe Sabbat rumor** when appropriate
- ❌ Delay detailing **Garou-controlled desert** until later arcs

---

## Music & Audio

- ❌ Create **Music System**
  - ❌ Leitmotifs for major NPCs
  - ❌ Ambient loops for key locations
  - ❌ Align with cinematic intros and gameplay
- ❌ Integrate **Envato Elements API** (future automation)
- ❌ Maintain music usage aligned with Envato library

---

## Art & Visual Pipeline

- ❌ Maintain **VbN Character Art Guide** consistency
- ❌ Maintain **Dark City Noir** style toggle
- ❌ Integrate Flux / LM Studio pipelines where stable
- ❌ Investigate **FLUX 2.0 MCP systems**
- ❌ Standardize color usage (HEX-only when referenced)

---

## Tools, UX, & Workflow

- ❌ Maintain centralized **Project To-Do List** sync

---

## Reminders & Time-Based Tasks

- ❌ Reminder: Investigate **FLUX 2.0 MCP systems** (Saturday afternoon)
- ❌ Reminder: Start **BYU basketball live stats Android app** (March 3, 2026)

---

## Notes

- **Key Principle:** Fix root causes, not symptoms
- **Test Case:** Use Phreak for repair tooling validation
- **Priority:** Discipline changes and Sire–Childe relationships are critical for system integrity
- **Timing:** Humans/Ghouls split can be planned now but implemented later
- **Blood Bond Design Laws:**
  - Blood bonds are **rare, dangerous, and personal**
  - Blood is **never casually offered**
  - Ghouls seek blood; Kindred allow it
  - Player choice is preserved; **angst is the cost**
  - Dialogue is the primary expression of the system

---

_End of file_
