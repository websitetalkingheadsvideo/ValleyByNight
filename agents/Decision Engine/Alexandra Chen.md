# Alexandra Chen — Hidden State Tracker

*(Early Chronicle Decision Engine)*

## Purpose

Determines whether Alexandra becomes **Enemy**, **Conditional Ally**, or **Catalyst Antagonist**, culminating around the 2nd Elysium.

> **Note:** This tracker is never shown to players.

## Core State Variables

Track these quietly from **Night 1 → Night of 2nd Elysium**.

### 1. Vitae Stability (VS)

Represents how viable her current blood supply is.

**Range:** `Stable` → `Thinning` → `Critical`

- **Stable:** early nights, controlled behavior
- **Thinning:** emotional volatility, impatience
- **Critical:** desperation, tunnel vision

**Default Start:** `Thinning`

**Advancement Rules:**

- Automatically degrades one step every 7 nights
- Immediately drops to `Critical` if:
  - she is injured
  - she exerts Disciplines repeatedly
  - she narrowly avoids discovery

### 2. PC Visibility (PV)

How aware Alexandra is of the PC as an independent actor.

**Range:** `None` → `Aware` → `Evaluating`

**Triggers:**

- PC appears in rumors she hears → `Aware`
- PC demonstrates competence or restraint → `Evaluating`
- PC interacts with hunters, ghouls, or logistics → advance one step

### 3. PC Threat Perception (PTP)

How dangerous Alexandra believes the PC is to her personally.

**Range:** `Low` → `Moderate` → `High`

**Increases if PC:**

- aligns publicly with elders
- threatens her directly
- behaves aggressively or dominantly

**Decreases if PC:**

- listens
- withholds judgment
- shows moral hesitation or empathy

### 4. Opportunity Window (OW)

Whether Alexandra sees a viable alternative to abducting a Kindred.

**Values:** `Closed` / `Open`

**OW opens if ANY of the following occur:**

- PC and Alexandra have near contact
- PC is seen outside Elysium behaving cautiously
- Alexandra hears rumors that the PC protects weaker Kindred
- PC investigates without escalating violence

## Decision Checkpoints

There are two mandatory evaluation points.

### Checkpoint 1 — Night 10 (Internal Reassessment)

**Automatic Evaluation:**

- If `VS = Critical` AND `OW = Closed`
  - → Lock in Disappearance Plan (Path A)

- If `VS ≥ Thinning` AND `OW = Open`
  - → Delay decision; observe PC further

- If `VS = Thinning` AND `PV = Evaluating`
  - → Consider Contact Option (flag possible ally)

> **Note:** This is not visible to players. It only determines whether Alexandra is still flexible.

### Checkpoint 2 — Night of the 2nd Elysium (Final Decision)

This is the hinge night.

**Trigger Event**

If the PC:

- notices Alexandra surveilling Elysium
- crosses her path outside the grounds
- confronts her non-aggressively
- or Alexandra initiates a cautious approach

→ **Immediate Final Evaluation**

## Final Resolution Logic

### 🟡 PATH B — Conditional Ally (Best Outcome)

**Occurs if ALL are true:**

- `OW = Open`
- `PV = Evaluating`
- `PTP ≠ High`
- PC engages without dominance

**Result:**

- No disappearance
- Alexandra proposes terms, not loyalty
- She becomes a limited, unstable ally
- Her Vitae Stability temporarily improves

### ⚫ PATH C — Tragic Pivot (Delayed Antagonist)

**Occurs if:**

- `OW = Open`
- `VS = Critical`
- PC hesitates, bargains vaguely, or delays

**Result:**

- She delays the abduction
- Target Kindred may vanish after Elysium
- PC feels personal responsibility
- Alexandra becomes conflicted antagonist

### 🔴 PATH A — Enemy (Disappearance Happens)

**Occurs if ANY are true:**

- `OW = Closed`
- `VS = Critical`
- `PTP = High`
- PC ignores or escalates prematurely

**Result:**

- A known, liked Kindred disappears
- Investigation arc begins
- Alexandra becomes an early-game antagonist

## Design Notes (Why This Works)

- No dice required
- No single interaction forces an outcome
- PC never sees the thresholds
- Alexandra always acts rationally from her perspective
- The disappearance is a consequence, not a script

**Most importantly:**

> Alexandra is not choosing against the PC.  
> She is choosing for survival.
