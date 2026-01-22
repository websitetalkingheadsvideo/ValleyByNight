# Valley by Night — Master To-Do List

_Last updated: 2026-01-21_

---

## Core Systems & Agents

- [ ] Implement **Ability Agent** to validate/map Abilities into `character.json`
- [ ] Implement **Discipline Agent** to validate/map Disciplines and powers into `character.json`
- [ ] Implement **Dialogue Agent** integration with blood bond stage (branching only, non-enforcing)
- [ ] Implement **Influence System** (Grapevine-style)
  - [ ] `character_influences` table (influence_type, level 0–5)
  - [ ] UI for viewing/editing influences
  - [ ] Importer from Grapevine XML
- [ ] Implement **Narrative Compiler / Narrative Packages**
  - [ ] Finalize `narrative_packages` schema usage
  - [ ] UI for chapter/arc/session/location/faction scopes
- [ ] Decide and document **Wraith data model**
  - [ ] Keep standalone `wraith_characters` OR
  - [ ] Migrate to unified characters + splat-extension model
  - [ ] Write migration plan

---

## Character & Rules Data

- [ ] Audit and replace **Discipline power descriptions**
  - [ ] Add `short_description`
  - [ ] Add `long_description` (full LotN Revised text)
  - [ ] Add `system_text / mechanics` column
  - [ ] Verify accuracy (no simplification)
- [ ] Fill missing data in **paths_master**
  - [ ] Add descriptions for all paths
- [ ] Fill missing data in **path_powers**
  - [ ] Add `system_text`
  - [ ] Add `challenge_type`
  - [ ] Add `challenge_notes`
  - [ ] Audit all Necromancy and Thaumaturgy paths
- [ ] Create **Phoenix-localized clanbooks**
  - [ ] Complete after clanbook analyses
  - [ ] Incorporate remaining NPC creations
- [ ] Ensure all NPC sheets default to `"player_name": "NPC"`

---

## OCR / Text Processing

- [ ] Enforce pinned **OCR cleanup rule**
  - [ ] Rebuild paragraph structure first
  - [ ] Remove layout-induced line breaks
  - [ ] Fix broken/duplicated words
  - [ ] Normalize punctuation and capitalization after structure
- [ ] Perform **structural review pass**
  - [ ] Identify sections missing subheadings
  - [ ] Insert appropriate Markdown subheaders (no prose changes)
- [ ] Perform **semantic review pass**
  - [ ] Identify unlabelled “Example of Play” sections
  - [ ] Decide labeling vs non-destructive tagging approach

---

## Chronicle & Narrative

- [ ] Create **pre-game history / backstory primer**
  - [ ] What PCs would reasonably know before Session 1
  - [ ] Align with opening cutscene acknowledgment
- [ ] Lock **canon timeline rules**
  - [ ] Game start: Saturday, Oct 8, 1994
  - [ ] Elysium: 2nd & 4th Saturdays
  - [ ] Default Elysium: Hawthorn Estate
- [ ] Flesh out **Giovanni presence** in Scottsdale (Camelback area)
- [ ] Flesh out **Mesa mage skyscraper** (late-chronicle reveal)
- [ ] Flesh out **Guadalupe Sabbat rumor** when appropriate
- [ ] Delay detailing **Garou-controlled desert** until later arcs

---

## Music & Audio

- [ ] Create **Music System**
  - [ ] Leitmotifs for major NPCs
  - [ ] Ambient loops for key locations
  - [ ] Align with cinematic intros and gameplay
- [ ] Integrate **Envato Elements API** (future automation)
- [ ] Maintain music usage aligned with Envato library

---

## Art & Visual Pipeline

- [ ] Maintain **VbN Character Art Guide** consistency
- [ ] Maintain **Dark City Noir** style toggle
- [ ] Integrate Flux / LM Studio pipelines where stable
- [ ] Investigate **FLUX 2.0 MCP systems**
- [ ] Standardize color usage (HEX-only when referenced)

---

## Tools, UX, & Workflow

- [ ] Implement **Mandatory Pre-Session Character Questionnaire**
  - [ ] Structured JSON storage
  - [ ] Completion gating before first session
  - [ ] 10–20 XP award rubric (ST approval)
- [ ] Add **read-only helper view**
  - [ ] Derive blood bond stage from `character_blood_drinks`
- [ ] Add **Map Agent**
  - [ ] Development and integration planning
- [ ] Maintain centralized **Project To-Do List** sync

---

## Reminders & Time-Based Tasks

- [ ] Reminder: Investigate **FLUX 2.0 MCP systems** (Saturday afternoon)
- [ ] Reminder: Start **BYU basketball live stats Android app** (March 3, 2026)

---

_End of file_
