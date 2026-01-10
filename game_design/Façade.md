# Using *Façade* as Design Inspiration for **Valley by Night**

## Overview

*Façade* (2005) is an interactive drama best known for treating **conversation and emotional pressure as gameplay** rather than relying on traditional dialogue trees. While it is **not a system to copy directly**, it provides several valuable design lessons that align strongly with the goals of *Valley by Night* (VbN), especially in political, social, and Elysium-focused play.

This document summarizes how *Façade* can inform VbN design—and where it should *not* be emulated.

---

## What *Façade* Does Well (and Why It Matters for VbN)

### 1. Conversation as Gameplay  
In *Façade*, dialogue choices can escalate, defuse, or completely derail a scene. Speaking at the wrong time—or too much—can be dangerous. Silence and restraint are sometimes the best options.

**Application to VbN:**
- Elysium encounters
- Primogen councils
- Harpy judgments
- Private political confrontations  
Conversation should feel *risky*, not safe or mechanical.

---

### 2. Hidden Emotional and Social State  
Characters in *Façade* track invisible variables such as anger, trust, resentment, and alliance. Player actions push these values past thresholds that trigger new scenes or end the encounter.

**Application to VbN:**
- Respect, suspicion, leverage, offense, fear
- Blood bond pressure
- Status shifts within clan, sect, or city
- Boon tension and obligation tracking  

VbN already has the data model needed to support this kind of system.

---

### 3. State Machines Over Dialogue Trees  
*Façade* uses internal state machines rather than fixed dialogue branches. NPC reactions are based on *patterns of behavior*, not single dialogue lines.

**Application to VbN:**
- NPCs respond to how players behave over time
- Repeated disrespect, evasion, or aggression matters
- Political consequences emerge organically rather than from scripted choices

---

## Where *Façade* Does NOT Translate Well

### 1. Free-Text Input  
Typed natural language input is fragile, immersion-breaking, and hard to control.

**VbN Alternative:**
- Intent-based dialogue choices (e.g., Deflect, Press, Offer Boon, Threaten)
- The system interprets *intent*, not raw text

---

### 2. Single-Scene Focus  
*Façade* is a one-night, one-location experience.

**VbN Difference:**
- Long-form chronicle
- Persistent world state
- Multiple factions, locations, and power centers

*Façade* works best as **micro-scale inspiration**, not a full narrative framework.

---

## Best Use Case for VbN

Treat *Façade* as inspiration for a **Social Encounter Engine**, not a dialogue system.

### Concepts to Adopt
- Hidden emotional and political meters
- Escalation thresholds
- NPC memory of past treatment
- Scenes that can fail without violence

### Concepts to Replace
- Typed dialogue → curated intent choices
- Scripted drama → faction logic + status systems
- Single-scene drama → reusable encounter templates

---

## Bottom Line

*Façade* proves that:
> Social interaction can be tense, dangerous, and mechanically meaningful without combat.

That principle aligns perfectly with *Valley by Night*.  
The value lies not in copying *Façade*, but in applying its **core philosophy** to VbN’s political, faction-driven, and socially lethal world.

--- 

**Design takeaway:**  
Use *Façade* as a reference point when building Elysium scenes, political confrontations, and NPC interaction systems—where words, tone, and restraint can be as deadly as fangs.
