You are an AI pair‑programmer running inside Cursor. Your job is to generate, validate, and log boons for the character **Charles "C.W." Whitford** with **exactly 50% of all NPCs in the existing NPC database**, using **Taskmaster (MCP)** and **Plan Mode** for all code modifications.

## High‑Level Behavior

1. **Always begin with Taskmaster (MCP)**:
   - Analyze the project’s NPC data structures, boon models, and Harpy logging.
   - Produce a **numbered, step‑by‑step implementation plan**.
   - Ask for my confirmation **before executing anything**.
2. After approval:
   - Use **Plan Mode** to apply changes safely and incrementally.
   - Clearly document edits in the diff.
3. Follow all constraints in this prompt exactly.

---

## Project Goal

Implement a system where:

- **Charles “C.W.” Whitford** has **exactly one boon** with **exactly 50% of all NPCs**.
- Of those selected NPCs, boon tiers are distributed as:
  - **5% Major boons**
  - **25% Minor boons**
  - **70% Trivial boons**
- Boons must be:
  - **Validated** for correctness and non‑duplication.
  - **Logged with the Harpy** using established patterns or a newly defined, consistent logging format if none exists.

> If the project uses a different boon representation or tier naming scheme, map these requirements into the existing schema and document the mapping.

---

## Detailed Requirements

### 1. Discover and Understand Current NPC + Boon Models

Using Taskmaster, locate:

1. NPC source of truth:
   - Identify where NPCs are defined (JSON/YAML, ORM models, TS modules, etc.).
2. Existing boon/relationship data structures.
3. Whether **Charles “C.W.” Whitford** already exists in the character database.
4. Summarize findings for confirmation:
   - NPC storage location
   - Existing boon model
   - How “C.W.” is or should be uniquely identified

Wait for my approval before proceeding.

---

### 2. Representing Charles “C.W.” Whitford

1. Ensure a canonical “C.W.” entry exists with a stable ID or slug.
2. If needed, create or extend the boon/relationship model to store:
   - `grantor`  
   - `grantee`  
   - `boon_tier`
   - Any required metadata (timestamp, notes, status)
3. Document exactly how boons for “C.W.” are stored and queried.

---

### 3. Selecting NPCs (50% Rule)

Implement deterministic NPC selection:

1. Compute total NPC count.
2. Select **exactly 50%** of NPCs using one of:
   - A seeded deterministic shuffling function.
   - A hash‑based selection algorithm.
3. Do **not** re‑select different NPCs on each run:
   - Selection must be stable across repeated executions unless the NPC list changes.
4. Store or derive a reproducible selection method and document it.

---

### 4. Boon Generation Rules

For each **selected** NPC:

1. Ensure exactly one boon exists with “C.W.”:
   - Create a new boon if none exists.
   - If one already exists, update/normalize but do not duplicate.
2. For NPCs **not selected**, ensure:
   - No boon exists between that NPC and “C.W.” OR
   - If the project requires historical preservation, mark them inactive/ignored and log appropriately.
3. **Boon tier distribution** within the 50%-selected NPC group:
   - **5% Major**
   - **25% Minor**
   - **70% Trivial**
4. After assignment:
   - Produce a summary report of actual counts + percentages.

---

### 5. Validation

Create validation logic that ensures:

1. “C.W.” has boons with **exactly 50%** of NPCs.
2. No NPC in the selected set has >1 boon with C.W.
3. No NPC outside the selected set has an active boon with C.W.
4. All boons use valid tiers.
5. Distribution approximates 5/25/70.
6. Script is idempotent:
   - Re-running does not create duplicates
   - Selection set stays stable

Add automated tests covering:
- Re-running generator  
- Edge cases (NPCs lacking IDs, pre-existing boons, etc.)  
- Tier distribution confirmation  

---

### 6. Harpy Logging Requirements

1. Search for existing Harpy logging utilities.
2. If found, integrate using existing formats and severity levels.
3. If not found, create a stable Harpy logging mechanism:
   - JSONL or database table
   - Each entry includes:
     - timestamp
     - C.W.’s unique ID
     - NPC ID
     - boon tier
     - action (`CREATED`, `UPDATED`, `REMOVED`, `SKIPPED_EXISTS`)
4. Validation should also confirm that:
   - All boon entries for C.W. are correctly represented in the Harpy log.
   - Any mismatches produce reconciliation entries.

---

### 7. Developer Experience & Documentation

1. Provide an easy CLI or script for:
   - Selecting NPCs  
   - Generating boons  
   - Validating boons  
   - Checking Harpy logs  
2. Update relevant documentation to explain:
   - The 50% selection logic
   - Boon tier distribution
   - How to re-run validation
   - How Harpy logging works

---

## Taskmaster + Plan Mode Requirements

When this prompt is run in Cursor:

1. **Start with Taskmaster (MCP)**:
   - Inspect NPC data, boon models, and Harpy.
   - Generate a detailed, numbered plan.
2. Present the plan to me and wait for approval.
3. After approval:
   - Use **Plan Mode** to implement the plan step-by-step.
   - Summaries after major work (model updates, generator creation, validation, logging).
4. Do not modify unrelated systems.
5. Final state must include:
   - Working generator
   - Stable 50% NPC selection
   - 5/25/70 boon distribution
   - Complete validation
   - Complete Harpy logging
   - Updated documentation

Begin only after plan approval.
