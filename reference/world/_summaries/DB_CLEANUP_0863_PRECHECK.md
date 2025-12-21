# VbN Database Cleanup - PRECHECK Report
**Version:** 0.8.63 (shorthand: 0863)
**Date:** 2025-12-21 10:43:52
**Mode:** EXECUTION

## Step 1: Remote Database Verification

- **DB Host (env):** vdb5.pit.pair.com
- **DB Host (connection):** vdb5.pit.pair.com via TCP/IP
- **DB Name:** working_vbn
- **Server Info:** 8.0.44
- **✓ Remote DB Verified** (not localhost)

## Step 2: Audit Queries (Read-Only)

### 2A) Duplicate Character Detection

**Characters table columns:** id, user_id, character_name, player_name, chronicle, nature, demeanor, concept, clan, generation, sire, pc, morality_path, conscience, self_control, courage, path_rating, willpower_permanent, willpower_current, blood_pool_max, blood_pool_current, health_status, experience_total, experience_unspent, appearance, biography, agentNotes, actingNotes, equipment, total_xp, spent_xp, notes, custom_data, status, camarilla_status, created_at, updated_at, character_image, Coterie, Relationships

**Primary Key:** id
**Total duplicate clusters found:** 0

✓ No duplicate character names found.

### 2B) Kerry-Specific Inspection

**Kerry matches found:** 2

#### Kerry Rows:

| ID | Name | Clan | Generation | Sire | PC | Player | Created | Updated |
|----|------|------|------------|------|----|--------|---------|---------|
| 87 | Kerry, the Gangrel | Gangrel | 10 | Old Tom | 0 | NPC | 2025-10-31 09:19:36 | 2025-11-08 19:35:08 |
| 130 | Kerry, the Desert-Wandering Gangrel | Gangrel | 10 | 0 | 0 | NPC | 2025-11-26 06:02:14 | 2025-11-26 06:02:14 |

**Alias/Nickname field present:** No

### 2C) Invalid Sire Values Detection

**Invalid sire values found:** 11

#### Characters with Invalid Sire Values:

| ID | Name | Clan | Generation | Current Sire | PC |
|----|------|------|------------|--------------|----|
| 70 | Alessandro Vescari | Giovanni | 10 | (empty string) | 1 |
| 125 | Roxanne Murphy | Followers of Set | 9 | (empty string) | 0 |
| 48 | Duke Tiki | Toreador | 10 | 'bob' | 0 |
| 50 | Sabine | Toreador | 10 | 'both' | 0 |
| 52 | Sebastian | Toreador | 10 | 'both' | 0 |
| 42 | Rembrandt Jones | Toreador | 12 | 'during' | 0 |
| 102 | Mr. Harold Ashby | Malkavian | 10 | 'him' | 0 |
| 92 | Adrian Leclair | Toreador | 9 | 'in' | 0 |
| 57 | Betty | Nosferatu | 9 | 'in' | 0 |
| 47 | Cordelia Fairchild | Toreador | 8 | 'in' | 0 |
| 68 | Étienne Duvalier | Toreador | 9 | 'in' | 0 |

#### Suspicious Short Sire Values (< 4 chars, not matching known names):
*Note: These are REPORTED ONLY - not auto-fixed*

| ID | Name | Clan | Generation | Sire |
|----|------|------|------------|------|
| 131 | Alistaire | Nosferatu | 13 | '0' |
| 129 | Butch Reed | Brujah | 10 | '0' |
| 136 | Charles "C.W." Whitford | Ventrue | 9 | '0' |
| 95 | Core (Alexandra Chen) | N/A - Human | 0 | '0' |
| 138 | Helena Crowly | Tremere | 9 | '0' |
| 130 | Kerry, the Desert-Wandering Gangrel | Gangrel | 10 | '0' |
| 107 | Layla al-Sahr | Assamite | 10 | '0' |
| 133 | Lilith Nightshade | Malkavian | 9 | '0' |
| 139 | Lorenzo Giovanni | Giovanni | 9 | '0' |
| 60 | Lucien Marchand | Ghoul (Domitor: Étienne Duvalier) | 0 | '0' |
| 140 | Marianna Giovanni | Giovanni | 9 | '0' |
| 124 | Marisol "Roadrunner" Vega | Gangrel | 11 | '0' |
| 108 | Misfortune | Malkavian | 9 | '0' |
| 137 | Naomi Blackbird | Gangrel | 10 | '0' |
| 141 | Paris Giovanni | Giovanni | 8 | '0' |
| 142 | Paulo Benedicto Giovanni | Giovanni | 8 | '0' |
| 97 | Phreak | N/A - Human (Cybernetically Enhanced) | 0 | '0' |
| 62 | Sofia Alvarez | Ghoul (Domitor: Étienne Duvalier) | 0 | '0' |
| 104 | Tariq Ibrahim | Followers of Set | 10 | '0' |

## Step 3: Proposed Fix Strategy

### 3A) Duplicate Handling Strategy

For each duplicate cluster, we will:
1. **Analyze completeness** - Compare all fields between duplicates
2. **Choose canonical row** - Select the most complete row (prefer newer updated_at if tied)
3. **Check foreign key dependencies** - Identify related tables referencing character IDs
4. **Merge or deprecate** - Move related records to canonical ID, then:
   - If schema supports soft-delete: mark duplicates as inactive/deleted
   - Otherwise: hard-delete only if no FK references exist

### 3B) Invalid Sire Handling Strategy

For invalid sire values:
- Convert exact matches ('in', 'bob', 'both', 'during', 'him') → NULL
- Convert empty strings and whitespace-only → NULL
- **DO NOT** replace with guessed names
- If sire_id FK exists and sire_name text, prefer FK integrity

## Foreign Key Dependency Analysis

**Tables with foreign keys to characters:** 20

| Table | Column | References | Constraint Name |
|-------|--------|------------|-----------------|
| boons | creditor_id | characters.id | boons_ibfk_1 |
| boons | debtor_id | characters.id | boons_ibfk_2 |
| character_abilities | character_id | characters.id | character_abilities_ibfk_1 |
| character_ability_specializations | character_id | characters.id | character_ability_specializations_ibfk_1 |
| character_backgrounds | character_id | characters.id | character_backgrounds_ibfk_1 |
| character_coteries | character_id | characters.id | character_coteries_ibfk_1 |
| character_derangements | character_id | characters.id | character_derangements_ibfk_1 |
| character_disciplines | character_id | characters.id | character_disciplines_ibfk_1 |
| character_equipment | character_id | characters.id | character_equipment_ibfk_1 |
| character_heard_rumors | character_id | characters.id | fk_heard_character |
| character_influences | character_id | characters.id | character_influences_ibfk_1 |
| character_location_assignments | character_id | characters.id | character_location_assignments_ibfk_1 |
| character_merits_flaws | character_id | characters.id | character_merits_flaws_ibfk_1 |
| character_morality | character_id | characters.id | character_morality_ibfk_1 |
| character_negative_traits | character_id | characters.id | character_negative_traits_ibfk_1 |
| character_relationships | character_id | characters.id | character_relationships_ibfk_1 |
| character_relationships | related_character_id | characters.id | character_relationships_ibfk_2 |
| character_rituals | character_id | characters.id | character_rituals_ibfk_1 |
| character_status | character_id | characters.id | character_status_ibfk_1 |
| character_traits | character_id | characters.id | character_traits_ibfk_1 |
