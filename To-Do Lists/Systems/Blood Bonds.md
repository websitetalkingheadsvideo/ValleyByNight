# Valley by Night — Blood Bond System To-Do List

## Status
**Phase:** Foundations now, full system later  
**Theme:** Power through intimacy, consent erosion, emotional consequence  
**Scope:** Applies to *all NPCs*, rare in practice, always possible

---

## PHASE 1 — Foundations (Now / Tonight)

### Data & Tracking (Minimal, Non-Enforcing)
- [ ] Create `character_blood_drinks` table (event-based, not state-based)
  - `id`
  - `drinker_character_id`
  - `source_character_id`
  - `drink_date` (night matters)
  - `notes`
- [ ] Ensure table is **read-only safe** for summaries and agents
- [ ] Do **not** store bond stage as a column (derived only)

### Creature-Type Compatibility
- [ ] Confirm blood drinks can occur between:
  - Kindred → Mortal
  - Kindred → Ghoul
  - Kindred → Kindred
- [ ] Explicitly disallow invalid cases (e.g. Wraith blood)

### Summary / Diagnostics
- [ ] Update summaries to:
  - detect blood-drink history
  - report bond stage (0–3) **descriptively only**
  - list orphaned / unresolved blood-drink references
- [ ] Never enforce obedience or block actions in summaries

---

## PHASE 2 — Dialogue Integration (Later)

### Dialogue Context Flags
- [ ] Expose derived bond stage to Dialogue Agent:
  - 0 = none
  - 1 = fascination
  - 2 = attachment
  - 3 = full bond
- [ ] Allow dialogue writers to branch on bond stage
- [ ] Ensure same *intent* yields different dialogue based on stage

### Tone Rules (Narrative, Not Mechanical)
- [ ] Ghoul NPCs:
  - may ask for blood
  - show anxiety / dependence
- [ ] Kindred NPCs:
  - never ask directly
  - rationalize, resist, or allow under pressure
- [ ] No “Offer Blood” casual dialogue option

---

## PHASE 3 — Pressure & Access Rules (Later)

### When Blood Drinking Becomes Possible
- [ ] Define pressure thresholds:
  - survival / torpor
  - severe injury
  - ritual necessity
  - emotional collapse
- [ ] Prevent casual or transactional blood exchange
- [ ] Explicitly avoid “boon-for-a-sip” normalization

### Awareness Rules
- [ ] Decide per NPC:
  - do they understand blood bond risk at drink #1?
  - do they recognize danger at drink #2?
- [ ] Support denial, shame, and self-justification arcs

---

## PHASE 4 — Consequences & World Reaction (Later)

### Social & Political Fallout
- [ ] Integrate with Rumor Agent:
  - favoritism
  - whispers of control
- [ ] Integrate with Status / Harpy reactions
- [ ] Allow Sabbat / rivals to exploit blood-bound NPCs

### Relationship Outcomes
- [ ] Possible long-term arcs:
  - devotion
  - resentment
  - tragedy
  - forced separation
  - death by external forces
- [ ] Avoid binary “good/bad” endings

---

## PHASE 5 — Agentization (Future)

### Blood Bond Agent (Conceptual)
- [ ] Inputs:
  - drink history
  - dates
  - creature types
- [ ] Outputs:
  - bond stage
  - emotional pressure description
- [ ] Explicitly **do not**:
  - auto-block player actions
  - force obedience
- [ ] Agent provides *context*, not control

---

## PHASE 6 — Expansion Hooks (Far Future)

### Ghoul PC Expansion
- [ ] Flesh out ghoul-specific stats and progression
- [ ] Enable PC flag for ghouls
- [ ] Reuse existing blood bond system without schema rewrite

### Advanced Mechanics (Optional)
- [ ] Bond decay over time
- [ ] Ritual bond suppression / breaking
- [ ] Humanity / degeneration interactions

---

## Design Laws (Canon Intent)

- Blood bonds are **rare, dangerous, and personal**
- Blood is **never casually offered**
- Ghouls seek blood; Kindred allow it
- Player choice is preserved; **angst is the cost**
- Dialogue is the primary expression of the system

---

## Notes
- Inspired by Heather Poe (VtMB) relationship arc
- Intended as a **core theme**, not a side mechanic
- Most NPCs will never reach full bond state — but all *could*

---
