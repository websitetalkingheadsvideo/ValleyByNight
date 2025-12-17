# Character Database Field Analysis

## Database Schema (from `includes/save_character.php` and `admin/view_character_api.php`)

### Main `characters` Table Fields:
- `id` (auto-increment)
- `user_id` (creator)
- `character_name` ⚠️
- `player_name`
- `chronicle`
- `nature`
- `demeanor`
- `concept`
- `clan`
- `generation`
- `sire`
- `pc` (0 = NPC, 1 = PC)
- `appearance`
- `biography`
- `notes`
- `equipment`
- `character_image`
- `status` (string: 'active', 'inactive', 'archived')
- `camarilla_status` (string: 'Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown')
- `custom_data` (JSON column)
- `experience_total` (mapped from JSON `xp_total` or `total_xp`)
- `experience_spent` (mapped from JSON `xp_spent` or `spent_xp`)
- `clan_logo_url`
- `created_at`
- `updated_at`

### Related Tables (stored separately):
- `character_traits` (positive traits)
- `character_negative_traits` (negative traits)
- `character_abilities` (abilities with level and specialization)
- `character_disciplines` (disciplines with level)
- `character_backgrounds` (backgrounds with level)
- `character_status` (status details: health_levels, blood_pool_current, blood_pool_maximum, etc.)
- `character_coteries`
- `character_relationships`
- `character_merits_flaws`

---

## Issues Found in JSON Files

### 1. FIELD NAME INCONSISTENCIES

#### Character Name Field:
- ✅ **Correct**: `character_name` (used in Ardvark.json, Sarah_Hansen.json, Bayside Bob.json, etc.)
- ❌ **Wrong**: `name` (used in Warner Jefferson.json, Pistol Pete.json)

#### Status Fields:
- ✅ **Correct**: `status` as string ('active', 'inactive', 'archived')
- ❌ **Wrong**: `status` as nested object with `status_details` (Sarah_Hansen.json)
- ❌ **Wrong**: `current_state` separate from `status` (Sarah_Hansen.json)

#### Experience Fields:
- ✅ **Correct**: `xp_total`, `xp_available`, `xp_spent` (Bayside Bob.json)
- ❌ **Wrong**: `total_xp`, `spent_xp` (Sarah_Hansen.json, Ardvark.json)
- ❌ **Wrong**: `status.xp_total`, `status.xp_available` (nested in status object)

#### Blood Pool Fields:
- ✅ **Correct**: `status.blood_pool` or `status.blood_pool_current` (Bayside Bob.json, Ardvark.json)
- ❌ **Wrong**: `blood_pool` at root level (Butch Reed.json)
- ❌ **Wrong**: `status_details.blood_pool_current` (Sarah_Hansen.json)

---

### 2. FIELDS IN JSON FILES THAT DON'T EXIST IN DATABASE

These fields appear in JSON files but are **NOT stored in the database**:

#### Butch Reed.json specific:
- `epithet` - Character epithet/title
- `embraced` - Year of embrace (1989)
- `apparent_age` - Apparent age description
- `physical_description` - Object with height, build, skin, hair, clothing, signature_items
- `attributes` - Object with physical/social/mental attribute scores
- `abilities` - Object format (talents/skills/knowledges) instead of array
- `disciplines` - Object format (celerity: 1, potence: 2) instead of array with powers
- `backgrounds` - Object format instead of array
- `virtues` - Object with conscience, self_control, courage
- `path` - String (separate from morality.path_name)
- `humanity` - Number (separate from morality.humanity)
- `willpower` - Number (separate from morality.willpower_permanent)
- `blood_pool` - Number at root (should be in status object)
- `merits_flaws` - Object format instead of array
- `haven` - Object with location and description
- `role_in_chronicle` - Object with primary, secondary, story_hooks
- `description` - Long narrative description (separate from appearance)

#### Warner Jefferson.json specific:
- `name` - Should be `character_name`
- `affiliation` - Should map to `camarilla_status`
- `embrace_info` - Object with date, location, sire, mortal_background, circumstances
- `haven` - Object with location, description, security, symbolism
- `goal` - Current character goal
- `appearance` - Object with description, distinguishing_features, style (should be string)
- `attributes` - Object with physical/social/mental numbers
- `abilities` - Object format (talents/skills/knowledges) instead of array
- `disciplines` - Object format instead of array
- `backgrounds` - Object format with nested influence object
- `backgrounds_detail` - Object with detailed descriptions
- `virtues` - Object format
- `traits` - Object with willpower, blood_pool, health_levels, morality (wrong structure)
- `feeding_restriction` - Object with restriction details
- `background_mortal_life` - Extensive nested object
- `vulture_finance_expertise` - Object
- `embrace_story_option_1` - Object
- `embrace_story_option_2` - Object
- `current_situation_1994` - Object
- `story_hooks` - Object
- `questions_for_development` - Object
- `current_operations` - Object
- `retainers_detail` - Object
- `sire_relationship` - Object
- `personality` - Object

#### Sarah_Hansen.json specific:
- `current_state` - Duplicate of `status`
- `status_details` - Nested object (should be flattened or in status table)
- `agentNotes` - Not in database
- `actingNotes` - Not in database
- `ability_specializations` - Array format (should be in abilities array or specializations object)

#### General fields found in multiple files:
- `description` - Long narrative (separate from `appearance` field)
- `embraced` / `embrace_info` - Embrace date/info
- `apparent_age` - Age description
- `attributes` - Physical/Social/Mental scores (not stored in DB)
- `virtues` - Conscience, Self-Control, Courage (stored in morality object)
- `path` - Path name (duplicate of morality.path_name)
- `humanity` - Humanity rating (duplicate of morality.humanity)
- `willpower` - Willpower (duplicate of morality.willpower_permanent)
- `blood_pool` - At root level (should be in status object)
- `haven` - Haven location/description
- `goal` - Current character goal
- `research_notes` - Custom research data
- `artifacts` - Artifact list
- `rituals` - Ritual list

---

### 3. DATABASE FIELDS MISSING FROM JSON FILES

#### Butch Reed.json is missing:
- ❌ `chronicle` - Campaign name
- ❌ `sire` - Who embraced them
- ❌ `pc` - 0 for NPC, 1 for PC
- ❌ `status` - active/inactive/archived
- ❌ `camarilla_status` - Camarilla/Anarch/Independent/Sabbat/Unknown
- ❌ `character_image` - Image path
- ❌ `custom_data` - JSON for custom fields

#### Ardvark.json is missing:
- ❌ `appearance` - Physical appearance description (has `description` instead)
- ❌ `notes` - Storyteller notes
- ❌ `character_image` - Image path
- ❌ `custom_data` - JSON for custom fields
- ❌ `status` - As string (has nested status object)
- ❌ `camarilla_status` - Sect status

#### Sarah_Hansen.json is missing:
- ❌ `notes` - Storyteller notes (has extensive `notes` field but it's a string, not the DB field)
- ❌ `custom_data` - JSON for custom fields

#### Warner Jefferson.json is missing:
- ❌ `character_name` - Uses `name` instead
- ❌ `player_name` - Not present
- ❌ `chronicle` - Present but verify format
- ❌ `sire` - Present in embrace_info but not at root
- ❌ `pc` - Not present
- ❌ `appearance` - Has object, should be string
- ❌ `biography` - Not present (has extensive background_mortal_life instead)
- ❌ `notes` - Not present
- ❌ `equipment` - Not present
- ❌ `status` - Not present
- ❌ `camarilla_status` - Has `affiliation` instead
- ❌ `character_image` - Not present
- ❌ `custom_data` - Not present

---

### 4. FORMAT INCONSISTENCIES

#### Abilities Format:
- ✅ **Correct**: Array of objects `[{"name": "Occult", "category": "Mental", "level": 4}]` (Bayside Bob.json)
- ✅ **Correct**: Array of strings `["Performance (Voice) 4", "Subterfuge 2"]` (Sarah_Hansen.json)
- ❌ **Wrong**: Object with categories `{"talents": {"brawl": 4}, "skills": {...}}` (Butch Reed.json, Warner Jefferson.json)

#### Disciplines Format:
- ✅ **Correct**: Array with powers `[{"name": "Auspex", "level": 3, "powers": [...]}]` (Bayside Bob.json)
- ✅ **Correct**: Array of strings `["Melpominee 4", "Presence 3"]` (Sarah_Hansen.json)
- ❌ **Wrong**: Object format `{"celerity": 1, "potence": 2}` (Butch Reed.json, Warner Jefferson.json)

#### Backgrounds Format:
- ✅ **Correct**: Object with name:level `{"Resources": 3, "Allies": 2}` (Sarah_Hansen.json, Bayside Bob.json)
- ❌ **Wrong**: Object with nested objects `{"resources": 5, "influence": {"finance": 4}}` (Warner Jefferson.json)

#### Traits Format:
- ✅ **Correct**: Object with Physical/Social/Mental arrays `{"Physical": ["Trait1", "Trait2"]}` (Ardvark.json, Sarah_Hansen.json)
- ❌ **Wrong**: Object with numeric attributes `{"Physical": 2, "Social": 5}` (Warner Jefferson.json)
- ❌ **Wrong**: Object with willpower/blood_pool `{"willpower": 6, "blood_pool": 12}` (Pistol Pete.json)

#### Morality Format:
- ✅ **Correct**: Object with path_name, path_rating, virtues, willpower `{"path_name": "Humanity", "path_rating": 7, "conscience": 3, ...}` (Ardvark.json, Bayside Bob.json)
- ❌ **Wrong**: Separate fields at root `"path": "Humanity", "humanity": 8, "willpower": 5` (Butch Reed.json)

#### Status Format:
- ✅ **Correct**: Object with nested fields `{"xp_total": 0, "blood_pool": 11, "health_levels": 7}` (Bayside Bob.json)
- ❌ **Wrong**: Nested in status_details `{"status_details": {"xp_total": 40, ...}}` (Sarah_Hansen.json)
- ❌ **Wrong**: Fields at root level `"blood_pool": 13, "willpower": 5` (Butch Reed.json)

---

## Recommendations

### 1. Standardize Field Names
- Always use `character_name` (never `name`)
- Always use `status` as string, not nested object
- Always use `xp_total`, `xp_available`, `xp_spent` (not `total_xp`, `spent_xp`)
- Always use `camarilla_status` (not `affiliation`)

### 2. Move Non-Database Fields to `custom_data`
Fields like `epithet`, `embraced`, `apparent_age`, `physical_description`, `haven`, `role_in_chronicle`, `goal`, `research_notes`, `artifacts`, `rituals` should be stored in the `custom_data` JSON field if they need to be preserved.

### 3. Flatten Nested Objects
- `status_details` should be flattened or moved to `character_status` table
- `appearance` should be a string, not an object
- `attributes` should be removed (not stored in DB)

### 4. Standardize Data Formats
- Abilities: Use array format `[{"name": "...", "category": "...", "level": X}]` or array of strings
- Disciplines: Use array format `[{"name": "...", "level": X, "powers": [...]}]` or array of strings
- Backgrounds: Use object format `{"Name": level}` (not nested objects)
- Traits: Use object with arrays `{"Physical": ["Trait1", "Trait2"]}`
- Morality: Use single object with all fields, not separate root fields

### 5. Add Missing Required Fields
All JSON files should have:
- `character_name`
- `player_name`
- `chronicle`
- `status` (string)
- `camarilla_status` (string)
- `pc` (0 or 1)

---

## Files Needing Updates

### High Priority (Major Issues):
1. **Butch Reed.json** - Missing required fields, wrong formats
2. **Warner Jefferson.json** - Uses `name` instead of `character_name`, completely different structure
3. **Pistol Pete.json** - Uses `name` instead of `character_name`, wrong formats

### Medium Priority (Format Issues):
4. **Sarah_Hansen.json** - Nested status_details, duplicate fields
5. **Ardvark.json** - Missing appearance field, has description instead

### Low Priority (Minor Issues):
6. All other files in `Added to Database/` - Review for consistency







































