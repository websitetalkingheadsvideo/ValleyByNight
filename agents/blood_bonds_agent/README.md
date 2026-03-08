# Blood Bonds Agent

Reads blood drink events from `character_blood_drinks`, derives bond stage (0–3), and provides narrative context for Dialogue Agent and other systems. **Never enforces behavior or blocks actions.**

## Design Law

Blood bonds create narrative tension, not mechanical control.

## API

### Bond Context

```
GET api_get_bond_context.php?drinker_id=42&source_id=17
→ Single pair bond context (stage, emotional_pressure, diagnostics)

GET api_get_bond_context.php?character_id=42
→ All bonds where character is drinker
```

### Diagnostics

```
GET api_get_diagnostics.php
→ System-wide: orphaned records, invalid creature pairs, unusual patterns
```

## Bond Stages

| Stage | Label      | Description                                          |
|-------|------------|------------------------------------------------------|
| 0     | No bond    | No drinks recorded                                   |
| 1     | Fascination| First taste; drawn to source                         |
| 2     | Attachment | Strong emotional dependence; denial possible          |
| 3     | Full bond  | Total devotion; obedience feels natural              |

## Prerequisites

- `character_blood_drinks` table exists (run `database/create_character_blood_drinks_table.php`)
- Admin or storyteller role required for API access

## Structure

```
blood_bonds_agent/
├── api_get_bond_context.php
├── api_get_diagnostics.php
├── config/
├── src/
│   ├── BloodDrinkRepository.php
│   ├── BondDerivation.php
│   ├── CreatureCompatibility.php
│   └── BondContextBuilder.php
├── tests/
└── README.md
```
