# Narrative Compiler Agent (NCA) — Spec (Valley by Night)

**Project:** Valley by Night (VbN)  
**Purpose:** Convert *narrative intent* (premise, themes, factions, constraints) into *structured, inspectable, game-ready artifacts* (graphs, seeds, beats, and database-safe JSON), without “writing canon” automatically.

---

## 1) Mission Statement

The Narrative Compiler Agent (NCA) transforms story design inputs into a structured “World Package” that other agents and systems can validate, enrich, and publish. It does **not** author final canon scenes; it **compiles** narrative intent into modular components that can be reviewed, edited, versioned, and imported.

**Core output types:**
- Relationship/conflict graphs
- Location roles & usage
- Mystery scaffolding (clues, alibis, red herrings, reveals)
- Faction pressure map (who wants what, where, why)
- “Scene seeds” (not finished scenes)
- Import-safe JSON bundles for downstream agents

---

## 2) Non-Goals / Hard Boundaries

NCA must **not**:
- Invent new major canon without explicit allowance (Prince identity, primogen roster, etc.)
- Generate mechanical rules text (that’s Laws Agent)
- Create finished NPC sheets (Character Agent)
- Create finalized cinematic intros (Cinematic Intro Agent)
- Mutate the database directly (import scripts are produced, applied by human or separate Import Agent)
- “Decide outcomes” at runtime (no live improvisational authority)

---

## 3) Inputs

### 3.1 Required Inputs
- **Chronicle Intent Pack**
  - Chronicle logline + tone pillars
  - Start date (Oct 8, 1994)
  - Core inciting incident (Prince dies at Elysium)
  - Setting constraints (Phoenix recognizable, not strictly real)
  - Faction flags (Anarch territory 24th St, Giovanni Scottsdale, Setite Mesa theater club, etc.)

### 3.2 Optional Inputs
- Existing NPC roster (names + roles if any)
- Known locations list
- “Do not touch” canon list
- Desired output scope (chapter, arc, session, or location-focused)
- Seed prompts (e.g., “Blood Brothers moving into Apache Junction”)

### 3.3 Context Sources (Read-only)
- `Valley_by_Night_Chronicle_Summary.md` (story baseline)
- Existing NPC JSONs and location docs if present
- Any “locked” visual identity notes for characters (if referenced)

---

## 4) Output: The “World Package”

NCA outputs a single structured bundle (JSON-first) with a human-readable summary.

### 4.1 Top-Level JSON Schema (WorldPackage.json)
```json
{
  "package_id": "NCA-YYYYMMDD-HHMMSS",
  "scope": {
    "type": "chapter|arc|session|location|faction",
    "label": "Chapter One: The Prince is Dead",
    "time_window": {"start": "1994-10-08", "end": "1994-10-22"}
  },
  "assumptions": {
    "canon_locked": ["Prince identity unknown", "Elysium at Hawthorn Estate"],
    "soft_facts": ["Guadalupe rumored Sabbat pack"],
    "hard_constraints": ["No Garou desert deep-dive early", "Mesa mage tower late-game"]
  },
  "world_model": {
    "factions": [],
    "locations": [],
    "npcs": [],
    "relationships": [],
    "mysteries": [],
    "events": [],
    "rumor_seeds": [],
    "scene_seeds": []
  },
  "validation_hints": {
    "must_validate": ["timeline", "clue solvability", "faction motivation consistency"],
    "risk_flags": []
  },
  "exports": {
    "for_rumor_agent": [],
    "for_character_agent": [],
    "for_location_agent": [],
    "for_dialogue_agent": [],
    "for_music_agent": []
  },
  "versioning": {
    "source_notes": "Freeform notes about what was used",
    "compatibility": {"schema_version": "1.0"}
  }
}
