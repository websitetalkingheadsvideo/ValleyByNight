# Wraith Character System - Field Mapping Reference

## Quick Reference: VtM → Wraith Field Mappings

### ✅ FIELDS TO KEEP (Unchanged)
- `id`, `user_id`
- `character_name` → Keep (add `shadow_name` as additional field)
- `player_name`
- `chronicle`
- `nature`, `demeanor`, `concept`
- `appearance` → Keep for mortal appearance
- `biography`
- `notes`
- `character_image`
- `status` (active/inactive/archived)
- `pc` (0/1 for NPC/PC)
- `created_at`, `updated_at`
- `custom_data`

### ✅ FIELDS TO KEEP (With Modifications)
- `traits` → Keep structure, remove any vampiric traits
- `negativeTraits` → Keep structure
- `abilities` → Keep 30 standard WoD abilities, remove vampiric ones
- `specializations` → Keep as-is
- `backgrounds` → Replace VtM backgrounds with Wraith backgrounds
- `willpower_permanent`, `willpower_current` → Keep
- `xp_total`, `xp_spent`, `xp_available` → Keep, add shadow XP versions
- `relationships` → Keep structure
- `artifacts` → Keep (Wraith also has artifacts)
- `timeline.birthplace` → Keep
- `timeline.notable_mortal_past` → Keep
- `timeline.world_war_two_experience` → Keep if relevant

### ❌ FIELDS TO REMOVE (VtM-Specific)
- `clan`
- `generation`
- `sire`
- `sect`
- `camarilla_status`
- `title`
- `epithet`
- `description_tags` (or repurpose)
- `timeline.embrace_period`
- `timeline.bunker_acquisition`
- `timeline.years_in_bunker`
- `domain_and_haven` (entire section)
- `current_residents` (entire section)
- `disciplines` → Replace with `arcanoi`
- `rituals` → Remove (or repurpose for Rites)
- `morality.path_name`
- `morality.path_rating`
- `morality.humanity`
- `morality.conscience`
- `morality.self_control`
- `morality.courage`
- `status.blood_pool_current`
- `status.blood_pool_maximum`
- `status.blood_per_turn`

### ➕ FIELDS TO ADD (Wraith-Specific)

#### Core Identity
- `shadow_name` (string)
- `circle` (string)
- `guild` (string)
- `legion_at_death` (string)
- `date_of_death` (date)
- `cause_of_death` (text)
- `ghostly_appearance` (text)

#### Wraith Systems
- `fetters` (array of objects)
  ```json
  [{"name": "...", "rating": 1-5, "description": "..."}]
  ```
- `passions` (array of objects)
  ```json
  [{"passion": "...", "rating": 1-5}]
  ```
- `arcanoi` (array of objects)
  ```json
  [{"name": "...", "rating": 1-5, "arts": [{"level": 1, "power": "..."}]}]
  ```
- `shadow` (object)
  ```json
  {
    "archetype": "...",
    "angst_current": 0-10,
    "angst_permanent": 0-10,
    "dark_passions": [{"passion": "...", "rating": 1-5}],
    "thorns": [],
    "shadow_traits": [],
    "shadow_notes": ""
  }
  ```
- `pathos_corpus` (object)
  ```json
  {
    "pathos_current": 0-10,
    "pathos_max": 0-10,
    "corpus_current": 0-10,
    "corpus_max": 0-10,
    "health_levels": ["Healthy", "Bruised", ...]
  }
  ```
- `harrowing` (object)
  ```json
  {
    "last_harrowing_date": "",
    "harrowing_notes": ""
  }
  ```
- `shadow_xp_total` (int)
- `shadow_xp_spent` (int)
- `shadow_xp_available` (int)

### 🔄 FIELDS TO REPURPOSE

#### Backgrounds Mapping
**VtM Backgrounds** → **Wraith Backgrounds**
- Resources → Memories
- Status → Status (in Stygia)
- Allies → Allies (among the dead)
- Retainers → (Remove, or repurpose)
- Herd → (Remove)
- Contacts → (Remove, or merge into Allies)
- Influence → (Remove)
- Fame → Notoriety
- Generation → (Remove)
- Mentor → (Remove, or repurpose)
- **New Wraith Backgrounds**: Relic, Artifact, Haunt, Past Life, Requiem, Destiny

#### Timeline Modifications
- Keep: `birthplace`, `notable_mortal_past`, `world_war_two_experience`
- Remove: `embrace_period`, `bunker_acquisition`, `years_in_bunker`
- Modify: `arrival_in_phoenix` → Could become `arrival_in_shadowlands` or remove

---

## Complete Wraith Character JSON Structure

```json
{
  "id": 0,
  "user_id": 0,
  "character_name": "",
  "shadow_name": "",
  "player_name": "",
  "chronicle": "Valley by Night",
  "nature": "",
  "demeanor": "",
  "concept": "",
  "circle": "",
  "guild": "",
  "legion_at_death": "",
  "date_of_death": "",
  "cause_of_death": "",
  "pc": 1,
  "appearance": "",
  "ghostly_appearance": "",
  "biography": "",
  "notes": "",
  "equipment": "",
  "character_image": "",
  "status": "active",
  "timeline": {
    "birthplace": "",
    "notable_mortal_past": "",
    "world_war_two_experience": ""
  },
  "personality": {
    "tagline": "",
    "narrative": ""
  },
  "traits": {
    "Physical": [],
    "Social": [],
    "Mental": []
  },
  "negativeTraits": {
    "Physical": [],
    "Social": [],
    "Mental": []
  },
  "abilities": [],
  "specializations": {},
  "fetters": [],
  "passions": [],
  "arcanoi": [],
  "backgrounds": {},
  "backgroundDetails": {},
  "willpower_permanent": 5,
  "willpower_current": 5,
  "pathos_corpus": {
    "pathos_current": 0,
    "pathos_max": 0,
    "corpus_current": 0,
    "corpus_max": 0,
    "health_levels": []
  },
  "shadow": {
    "archetype": "",
    "angst_current": 0,
    "angst_permanent": 0,
    "dark_passions": [],
    "thorns": [],
    "shadow_traits": [],
    "shadow_notes": ""
  },
  "harrowing": {
    "last_harrowing_date": "",
    "harrowing_notes": ""
  },
  "merits_flaws": [],
  "status": {
    "health_levels": 7,
    "xp_total": 0,
    "xp_spent": 0,
    "xp_available": 0,
    "shadow_xp_total": 0,
    "shadow_xp_spent": 0,
    "shadow_xp_available": 0,
    "notes": ""
  },
  "relationships": [],
  "artifacts": [],
  "custom_data": null,
  "actingNotes": "",
  "agentNotes": "",
  "health_status": "",
  "created_at": "",
  "updated_at": ""
}
```

---

## Database Column Mapping

### New Columns for `wraith_characters` Table

| Column Name | Type | Null | Default | Description |
|------------|------|------|---------|-------------|
| `shadow_name` | VARCHAR(255) | YES | NULL | Wraith's shadow name |
| `circle` | VARCHAR(100) | YES | NULL | Circle affiliation |
| `guild` | VARCHAR(100) | YES | NULL | Guild membership |
| `legion_at_death` | VARCHAR(100) | YES | NULL | Legion at time of death |
| `date_of_death` | DATE | YES | NULL | Date character died |
| `cause_of_death` | TEXT | YES | NULL | How character died |
| `ghostly_appearance` | TEXT | YES | NULL | Appearance in Shadowlands |
| `fetters` | JSON | YES | NULL | Fetters array |
| `passions` | JSON | YES | NULL | Passions array |
| `arcanoi` | JSON | YES | NULL | Arcanoi array |
| `shadow_data` | JSON | YES | NULL | Shadow information |
| `pathos_corpus` | JSON | YES | NULL | Pathos/Corpus data |
| `harrowing` | JSON | YES | NULL | Harrowing information |
| `shadow_xp_total` | INT | NO | 0 | Total shadow XP |
| `shadow_xp_spent` | INT | NO | 0 | Spent shadow XP |
| `shadow_xp_available` | INT | NO | 0 | Available shadow XP |

### Removed Columns (from VtM table)
- `clan`
- `generation`
- `sire`
- `camarilla_status`
- `blood_pool_current`
- `blood_pool_max`
- `blood_per_turn`

---

**Last Updated**: 2025-01-24

