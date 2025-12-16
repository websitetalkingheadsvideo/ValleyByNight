# Valley by Night — Candidate Agents Backlog

This document tracks **potential agents** that logically extend the existing VbN agent ecosystem.
All entries below are *non-binding* design candidates, intended for planning and prioritization only.

---

## High-Value Candidate Agents

### 1. Influence Agent
**Purpose:**  
Track and manage Grapevine-style mortal and institutional influence.

**Responsibilities:**
- Track `character_influences` (type + level 0–5)
- Validate limits, stacking, and scope (if defined)
- Generate influence-based opportunities and pressures

**Capability Class:**  
Class S — State & Integrity

---

### 2. Status & Prestation Ledger Agent
**Purpose:**  
Formalize Camarilla status tracking alongside boons.

**Responsibilities:**
- Track status traits and granting authority
- Maintain audit trail of changes
- Surface status risk and scandal exposure

**Capability Class:**  
Class S — State & Integrity

**Notes:**  
Distinct from the existing `boon_agent`; complements it.

---

### 3. Timeline / Nightly Turn Agent
**Purpose:**  
Maintain authoritative in-game time and cadence.

**Responsibilities:**
- Track current night, date, and weekday
- Compute milestones (e.g., Elysium nights, deadlines)
- Expose time queries to other agents

**Capability Class:**  
Class S — State & Integrity

**Canon Dependencies:**
- Game start: Saturday, October 8th, 1994
- Elysium: 2nd and 4th Saturdays

---

### 4. Event Agent
**Purpose:**  
Manage scheduled, recurring, and time-bound events.

**Responsibilities:**
- Track event windows and expiration
- Mark events as upcoming, active, or missed
- Expose event metadata to Rumor and Narrative agents

**Capability Class:**  
Class S (state) + Class N (narrative outputs)

**Notes:**  
Does not notify players directly; relies on rumor propagation.

---

### 5. Investigation / Caseboard Agent
**Purpose:**  
Support investigations (e.g., Prince’s assassination).

**Responsibilities:**
- Track clues, witnesses, contradictions
- Link NPCs, locations, rumors, and boons
- Surface competing theories (advisory only)

**Capability Class:**  
Hybrid — Class S + Class N

---

### 6. Elysium / Court Session Agent
**Purpose:**  
Generate court-session context and pressure.

**Responsibilities:**
- Identify likely attendees
- Generate agendas and political tensions
- Highlight status and faction dynamics

**Capability Class:**  
Class N — Narrative

---

### 7. Compliance / Consistency Agent
**Purpose:**  
Maintain structural and canonical integrity.

**Responsibilities:**
- Detect missing files and schema drift
- Flag naming and casing inconsistencies
- Validate JSON against documentation

**Capability Class:**  
Class S — Read-only preferred

---

### 8. Scene Builder Agent
**Purpose:**  
Assist in scene framing without adjudication.

**Responsibilities:**
- Generate scene beats and sensory details
- Offer branching options, not outcomes
- Maintain 1994 accuracy and WoD tone

**Capability Class:**  
Class N — Narrative

---

### 9. Asset Pipeline Agent
**Purpose:**  
Track content production and asset completeness.

**Responsibilities:**
- Track portraits, stingers, music motifs, maps
- Output missing-asset checklists per NPC/location

**Capability Class:**  
Class S — State & Integrity

---

## Explicitly Avoided (For Now)

- Master Orchestrator / God Agent
- Automatic Consequence or Enforcement Agent
- Any agent that adjudicates outcomes or applies penalties

---

## Notes

- All candidates must conform to the **VbN Agent Scope & Authority Charter**
- Adding any agent that asserts authority requires a charter update
- Prioritization should align with:
  - Gameplay loop needs
  - Social economy depth
  - Narrative discovery, not automation

---

*End of document*
