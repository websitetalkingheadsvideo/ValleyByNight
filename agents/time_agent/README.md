# Time Agent

## Purpose

The **Time Agent** is responsible for managing the passage of time within a *Valley by Night* chronicle.

It acts as the **authoritative clock** for the game world, ensuring that time advances in a controlled, consistent, and auditable way.  
The Time Agent **does not directly modify world state**. Instead, it emits time-based events that are consumed by the Chronicle Agent (World State Manager), which applies the appropriate updates to the chronicle.

This separation ensures:
- A single source of truth for time
- A single writer for world state
- Predictable, debuggable progression across a playthrough

---

## Core Responsibilities

### 1. Time Advancement Requests

The Time Agent handles requests such as:
- Advance to the next phase of the night
- Advance to the next night
- Advance multiple nights (downtime, time skips)
- Set time explicitly (GM or debug use)

These requests are recorded as **time events**, not applied directly to the chronicle.

---

### 2. Time-of-Night Modeling

The Time Agent enforces the concept of **night phases**, which may include (but are not limited to):

- `early` — shortly after sunset
- `mid` — peak activity hours
- `late` — dangerous, quiet, or desperate hours
- `pre_dawn` — final moments before sunrise

Certain systems may restrict behavior based on these phases:
- Encounters that can only occur late at night
- NPC availability windows
- Location access rules
- Heightened danger or exposure near dawn

The Time Agent does **not** decide outcomes — it only defines *when* things are allowed to occur.

---

### 3. Event Emission (Not State Mutation)

When time advances, the Time Agent:
- Writes a time event to the event queue
- Includes the type of advancement and relevant payload
- Records the source (UI, GM action, system rule, etc.)

The Chronicle Agent is responsible for:
- Applying the time change to the chronicle
- Updating derived fields (current night, day of week, phase)
- Triggering world rules and scheduled changes

This guarantees that **time itself never bypasses world logic**.

---

## Design Constraints (Intentional)

- ❌ The Time Agent **never writes to the `chronicles` table**
- ❌ The Time Agent **never applies plot logic**
- ❌ The Time Agent **never mutates faction states, meters, or flags**

Its role is **purely temporal**.

---

## Why This Agent Exists Separately

Time in *Valley by Night* is not cosmetic.

It affects:
- Encounter eligibility
- Rumor spread
- Faction pressure
- Masquerade risk
- Scheduled plot escalations
- Environmental storytelling

By isolating time into its own agent:
- Plot logic remains deterministic
- World state remains consistent
- Debugging “how did we get here?” becomes possible

---

## Future Responsibilities (Not Yet Finalized)

The following capabilities are **planned but not fully specified** and may evolve:

- Time-based encounter eligibility checks
- Phase-based encounter weighting
- Location open/closed schedules
- Time pressure mechanics (countdowns, deadlines)
- Pre-dawn danger escalation
- GM-controlled time freezes or rewinds (debug / narrative tools)

These will be added incrementally and documented as they stabilize.

---

## Summary

> The Time Agent answers one question only:  
> **“What time is it in this version of the world?”**

It does not decide *what happens* — only *when it is allowed to happen*.

All world consequences of time passing are handled elsewhere, preserving clarity, control, and long-term maintainability.
