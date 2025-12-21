# Main To-Do List

## Core Systems & Agents

### Implement Ability Agent and Discipline Agent
Validate and assist character conversion by checking whether source data can include Abilities and Disciplines, and map them into `character.json`.

### Implement Grapevine-style Influences system
Create `character_influences` table (influence_type + level 0–5), UI, and importer from Grapevine XML. Only a few characters will have influences initially.

### Implement mandatory pre-session Character Questionnaire system
Based on the legacy character quiz. Structured JSON storage, completion gating before first session, and a 10–20 XP award rubric with ST approval.

### Implement Rituals Agent
*(Structure complete)* Integrate with `rituals_master`, `character_rituals`, and the Rules database for casting, learning, and validation logic.

### Implement Paths Agent
Build agent logic around `paths_master`, `path_powers`, and `character_paths`, including challenge handling and Laws Agent integration.

---

## Data Completion / Audit Tasks

- **Fill out missing data in `paths_master` and `path_powers`**
  - Add descriptions to `paths_master`
  - Complete `path_powers.system_text`
  - Assign `path_powers.challenge_type`
  - Add `path_powers.challenge_notes`

- **Audit all Necromancy and Thaumaturgy paths for completeness**
  - *(Necromancy and Thaumaturgy audit specifically noted as a required pass)*

- **Audit all Necromancy and Thaumaturgy ritual data**
  - Ensure ingredients, requirements, system text, and sources are complete and consistent.

---

## Content & Lore Expansion

### Create Phoenix-localized clanbooks for all clans
After clan book analyses and document generation; incorporate remaining NPC creations into the workflow.

### Incorporate remaining NPC creations into the database
Ensure all NPCs follow the standardized JSON and DB formats.

---

## Music & Presentation

### Create a music system for major NPCs and key locations
Use Envato Elements (leitmotifs + ambient loops), aligned with cinematic intros and gameplay.

---

## Mapping & World Systems

### Map Agent development and integration
Resume work on spatial/location logic when ready.

---

## Timeline / Reminder Items

- **Start BYU basketball live stats Android app project**
  - Reminder set for March 3, 2026.
