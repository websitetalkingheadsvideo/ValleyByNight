# 🔧 TO-DO LIST UPDATES (Add These Items)

## TM-10 — Laws / Rules Agent Completion (Post-Rescan)

**Type:** Core System / Rules  
**Priority:** High  
**Depends on:** Clanbooks complete (done)

### Description

Update and complete the central Laws / Rules Agent using the newly rescanned source books. This agent is the authoritative rules brain for all other agents.

### Tasks

- Integrate rescanned core rulebooks into the Rules corpus
- Normalize rule sections (ritual rules, challenge resolution, disciplines, paths, status, boons, influence)
- Ensure rules are not duplicated into ritual/path/discipline records
- Add canonical identifiers for rule fragments (for later RAG linking)
- Verify agent can answer:
  - "What rules apply here?"
  - "Where does this rule come from?"
  - "What overrides or exceptions exist?"

### Acceptance Criteria

- Laws Agent covers all core non-clanbook rules
- No NULL / placeholder rule sections remain
- Other agents can query Laws Agent for rule text by tag or reference

### Definition of Done

- Laws Agent updated and committed
- Spot-checked against Rituals and Paths agents

---

## TM-11 — Rules Context Service (RAG-Ready, Deferred)

**Type:** Architecture / Integration  
**Priority:** Low (Deferred)  
**Depends on:** TM-10

### Description

Design a shared Rules Context Service that allows agents to request relevant rule snippets at runtime. This ticket defines interfaces only — no full RAG implementation yet.

### Tasks

- Define a standard `rules_context` response shape:
  - short snippets
  - source identifiers
  - applicability tags (challenge_type, tradition, sect, location, etc.)
- Define how Rituals, Paths, Disciplines, and Abilities agents will call it
- Add placeholder hooks in agents (no RAG logic yet)

### Acceptance Criteria

- Interface spec exists
- No behavior change yet
- Zero hard dependency on RAG infrastructure

### Definition of Done

- Interface documented
- Agents reference the interface but still use static rules

---

## TM-12 — GraphRAG Integration (World & Politics Layer)

**Type:** Advanced Systems / World Modeling  
**Priority:** Low (Future Phase)  
**Depends on:** TM-11, Nightly Briefing system

### Description

Introduce GraphRAG to model relationships between characters, locations, factions, events, rumors, and rules for emergent storytelling and political reasoning.

### Scope (Deferred)

- Nightly Briefings
- Rumor seeding and belief bias
- Political consequence modeling
- Multi-hop "who is affected?" reasoning

### Definition of Done

- Explicitly not required now
- Ticket exists to prevent scope drift