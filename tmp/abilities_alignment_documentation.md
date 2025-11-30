# Abilities System Alignment Documentation

## Canonical Abilities Table (from `lotn_char_create.php`)

### Physical Abilities (8 total)
| Category | Ability Name | Key/Code | Min | Max | Default | Notes |
|----------|-------------|----------|-----|-----|---------|-------|
| Physical | Athletics | `Athletics` | 0 | 5 | 0 | Physical fitness and sports |
| Physical | Brawl | `Brawl` | 0 | 5 | 0 | Unarmed combat |
| Physical | Dodge | `Dodge` | 0 | 5 | 0 | Evading attacks |
| Physical | Firearms | `Firearms` | 0 | 5 | 0 | Ranged weapons |
| Physical | Melee | `Melee` | 0 | 5 | 0 | Close combat weapons |
| Physical | Security | `Security` | 0 | 5 | 0 | Locks, alarms, traps |
| Physical | Stealth | `Stealth` | 0 | 5 | 0 | Hiding and sneaking |
| Physical | Survival | `Survival` | 0 | 5 | 0 | Wilderness survival |

### Social Abilities (9 total)
| Category | Ability Name | Key/Code | Min | Max | Default | Notes |
|----------|-------------|----------|-----|-----|---------|-------|
| Social | Animal Ken | `Animal Ken` | 0 | 5 | 0 | Understanding animals |
| Social | Empathy | `Empathy` | 0 | 5 | 0 | Reading emotions |
| Social | Expression | `Expression` | 0 | 5 | 0 | Artistic expression |
| Social | Intimidation | `Intimidation` | 0 | 5 | 0 | Frightening others |
| Social | Leadership | `Leadership` | 0 | 5 | 0 | Commanding others |
| Social | Subterfuge | `Subterfuge` | 0 | 5 | 0 | Deception and lies |
| Social | Streetwise | `Streetwise` | 0 | 5 | 0 | Urban knowledge |
| Social | Etiquette | `Etiquette` | 0 | 5 | 0 | Social graces |
| Social | Performance | `Performance` | 0 | 5 | 0 | Acting, singing, etc. |

### Mental Abilities (10 total)
| Category | Ability Name | Key/Code | Min | Max | Default | Notes |
|----------|-------------|----------|-----|-----|---------|-------|
| Mental | Academics | `Academics` | 0 | 5 | 0 | Scholarly knowledge |
| Mental | Computer | `Computer` | 0 | 5 | 0 | Technology and programming |
| Mental | Finance | `Finance` | 0 | 5 | 0 | Money and economics |
| Mental | Investigation | `Investigation` | 0 | 5 | 0 | Research and deduction |
| Mental | Law | `Law` | 0 | 5 | 0 | Legal knowledge |
| Mental | Linguistics | `Linguistics` | 0 | 5 | 0 | Languages |
| Mental | Medicine | `Medicine` | 0 | 5 | 0 | Medical knowledge |
| Mental | Occult | `Occult` | 0 | 5 | 0 | Supernatural knowledge |
| Mental | Politics | `Politics` | 0 | 5 | 0 | Political systems |
| Mental | Science | `Science` | 0 | 5 | 0 | Scientific knowledge |

### Optional Abilities (5 total)
| Category | Ability Name | Key/Code | Min | Max | Default | Notes |
|----------|-------------|----------|-----|-----|---------|-------|
| Optional | Alertness | `Alertness` | 0 | 5 | 0 | General awareness |
| Optional | Awareness | `Awareness` | 0 | 5 | 0 | Supernatural awareness |
| Optional | Drive | `Drive` | 0 | 5 | 0 | Vehicle operation |
| Optional | Crafts | `Crafts` | 0 | 5 | 0 | Handicrafts and making things |
| Optional | Firecraft | `Firecraft` | 0 | 5 | 0 | Fire-related skills |

---

## Validation Rules (Canonical)

- **Physical Abilities**: Minimum 3 dots required, maximum 5 dots per ability
- **Social Abilities**: Minimum 3 dots required, maximum 5 dots per ability
- **Mental Abilities**: Minimum 3 dots required, maximum 5 dots per ability
- **Optional Abilities**: No minimum required, maximum 5 dots per ability

---

## Wraith Page Alignment Plan

### Differences Identified

#### Physical Abilities
1. ❌ **REMOVE**: `Drive` from Physical category (currently line 313)
   - **Action**: Move `Drive` to Optional category

#### Social Abilities
2. ❌ **REMOVE**: `Persuasion` (currently line 340)
   - **Action**: Remove entirely - not in canonical system
3. ❌ **ADD**: `Etiquette` (missing)
   - **Action**: Add `Etiquette` button in Social category, after `Streetwise` and before `Subterfuge` to match canonical order

#### Mental Abilities
4. ❌ **REMOVE**: `Crafts` from Mental category (currently line 360)
   - **Action**: Move `Crafts` to Optional category
5. ❌ **ADD**: `Finance` (missing)
   - **Action**: Add `Finance` button in Mental category, after `Computer`
6. ❌ **ADD**: `Law` (missing)
   - **Action**: Add `Law` button in Mental category, after `Investigation`
7. ❌ **ADD**: `Linguistics` (missing)
   - **Action**: Add `Linguistics` button in Mental category, after `Law`

#### Optional Abilities
8. ❌ **MISSING ENTIRE SECTION**: Optional Abilities category does not exist
   - **Action**: Add complete Optional Abilities section with:
     - `Alertness`
     - `Awareness`
     - `Drive` (moved from Physical)
     - `Crafts` (moved from Mental)
     - `Firecraft`

### Summary of Changes Required

1. **Remove** `Drive` from Physical abilities
2. **Remove** `Persuasion` from Social abilities
3. **Add** `Etiquette` to Social abilities
4. **Remove** `Crafts` from Mental abilities
5. **Add** `Finance` to Mental abilities
6. **Add** `Law` to Mental abilities
7. **Add** `Linguistics` to Mental abilities
8. **Create** Optional Abilities section with all 5 abilities
9. **Update** JavaScript handlers to support Optional category
10. **Update** progress display labels to match canonical format ("3 required | 5 max per ability")

---

## Implementation Checklist

- [ ] Update Physical Abilities section (remove Drive)
- [ ] Update Social Abilities section (remove Persuasion, add Etiquette)
- [ ] Update Mental Abilities section (remove Crafts, add Finance, Law, Linguistics)
- [ ] Add Optional Abilities section (complete new section)
- [ ] Update JavaScript ability handlers to support Optional category
- [ ] Update progress display labels to match canonical format
- [ ] Test ability selection and removal for all categories
- [ ] Verify validation rules match canonical system
- [ ] Update save function to handle Optional abilities

