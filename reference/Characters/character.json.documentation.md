# Character.json Field Documentation

This document provides detailed explanations for all fields in the `character.json` template.

## Core Identity Fields

### `character_name` (STRING, REQUIRED)
- Character's full name
- Example: `"Bayside Bob"`, `"Sarah Hansen"`

### `player_name` (STRING, REQUIRED)
- Player's name or "NPC" for Storyteller-controlled characters
- Example: `"Player Name"`, `"NPC"`

### `chronicle` (STRING, REQUIRED)
- Campaign/chronicle name
- Default: `"Valley by Night"`

### `clan` (STRING, REQUIRED)
- Vampire clan name
- Valid values: `"Assamite"`, `"Brujah"`, `"Followers of Set"`, `"Gangrel"`, `"Giovanni"`, `"Lasombra"`, `"Malkavian"`, `"Nosferatu"`, `"Ravnos"`, `"Toreador"`, `"Tremere"`, `"Tzimisce"`, `"Ventrue"`, `"Caitiff"`

### `generation` (INTEGER, REQUIRED)
- Generation number (1-15)
- Typical starting characters: 8-13
- Lower numbers = more powerful (closer to Caine)

### `sire` (STRING)
- Name of the vampire who embraced this character
- Example: `"Unknown"`, `"Toreador Primogen (Phoenix)"`

### `pc` (INTEGER, REQUIRED)
- Character type: `0` = NPC, `1` = PC (Player Character)

## Personality & Concept

### `nature` (STRING)
- Character's true nature archetype
- Examples: `"Survivor"`, `"Perfectionist"`, `"Bon Vivant"`, `"Conformist"`

### `demeanor` (STRING)
- Character's outward personality archetype
- Examples: `"Judge"`, `"Gallant"`, `"Visionary"`, `"Conformist"`

### `concept` (STRING)
- One-line character concept
- Example: `"Tiki bar owner who knows a lot about tiki culture"`

## Status & Affiliation

### `status` (STRING, REQUIRED)
- Character status: `"active"`, `"inactive"`, or `"archived"`
- Default: `"active"`

### `camarilla_status` (STRING, REQUIRED)
- Sect affiliation: `"Camarilla"`, `"Anarch"`, `"Independent"`, `"Sabbat"`, or `"Unknown"`
- Default: `"Unknown"`

### `sect` (STRING)
- Alternative sect field (complementary to `camarilla_status`)
- May contain more detailed sect information

### `title` (STRING)
- Character's title or position
- Example: `"Nosferatu Primogen of Phoenix"`

### `epithet` (STRING)
- Character's epithet or nickname
- Example: `"The Silent Authority of the Bunker"`

## Appearance & Description

### `appearance` (STRING)
- Physical appearance description (plain text)
- Used for database storage

### `appearance_detailed` (OBJECT)
- Detailed appearance breakdown:
  - `short_summary`: Brief one-line description
  - `detailed_description`: Full narrative description

### `biography` (TEXT)
- Full character backstory and history

### `description_tags` (ARRAY)
- Array of descriptive tags
- Example: `["ancient", "calculating", "politically unshakable"]`

## Traits System

### `traits` (OBJECT, REQUIRED)
- Positive traits organized by category
- Format: `{"Physical": ["Quick", "Nimble"], "Social": ["Charismatic"], "Mental": ["Intelligent"]}`
- Each category contains an array of trait names (strings)
- Traits are used for challenge comparisons (card-based system, NOT dots)

### `negativeTraits` (OBJECT)
- Negative/damaging traits (same format as `traits`)
- Examples: `["Awkward"]`, `["Clumsy"]`, `["Distracted"]`

## Abilities

### `abilities` (ARRAY)
- **Format Option 1 (Recommended)**: Array of objects
  ```json
  [
    {"name": "Occult", "category": "Mental", "level": 4},
    {"name": "Academics", "category": "Mental", "level": 3},
    {"name": "Stealth", "category": "Physical", "level": 2}
  ]
  ```
- **Format Option 2**: Array of strings
  ```json
  ["Performance (Voice) 4", "Subterfuge 2", "Empathy 2"]
  ```
- Categories: `"Physical"`, `"Social"`, `"Mental"`, `"Optional"`
- Levels: 1-5 (typically)

### `specializations` (OBJECT)
- Maps ability names to their specializations
- Format: `{"Occult": "Specific area of expertise", "Academics": "Specific academic field"}`
- Example: `{"Performance": "Voice", "Empathy": "Emotional Insight"}`

## Disciplines

### `disciplines` (ARRAY)
- **Format Option 1 (Recommended)**: Array of objects with powers
  ```json
  [
    {
      "name": "Auspex",
      "level": 3,
      "powers": [
        {"level": 1, "power": "Heightened Senses"},
        {"level": 2, "power": "The Spirit's Touch"},
        {"level": 3, "power": "Psychic Projection"}
      ]
    }
  ]
  ```
- **Format Option 2**: Array of strings
  ```json
  ["Melpominee 4", "Presence 3", "Auspex 2"]
  ```
- Common Disciplines: `"Animalism"`, `"Auspex"`, `"Celerity"`, `"Dominate"`, `"Fortitude"`, `"Obfuscate"`, `"Potence"`, `"Presence"`, `"Protean"`, etc.
- Levels: 1-5 (Basic, Intermediate, Advanced)

## Backgrounds

### `backgrounds` (OBJECT)
- Backgrounds with name:level pairs
- Format: `{"Resources": 3, "Allies": 2, "Status": 1, "Mentor": 2}`
- Common backgrounds: `"Allies"`, `"Contacts"`, `"Fame"`, `"Generation"`, `"Herd"`, `"Influence"`, `"Mentor"`, `"Resources"`, `"Retainers"`, `"Status"`
- Levels: 0-5

### `backgroundDetails` (OBJECT)
- Detailed descriptions for each background
- Format: `{"Resources": "Owner of The Velvet Door nightclub", "Allies": "Local musicians and staff"}`
- Maps background names to descriptive text

## Morality & Virtues

### `morality` (OBJECT)
- Complete morality/path information
- Fields:
  - `path_name`: String - `"Humanity"` (default) or Path of Enlightenment name
  - `path_rating`: Integer (1-10) - Current morality rating
  - `humanity`: Integer (1-10) - Humanity rating (if on Humanity path)
  - `conscience`: Integer (1-5) - Conscience virtue rating
  - `self_control`: Integer (1-5) - Self-Control virtue rating
  - `courage`: Integer (1-5) - Courage virtue rating
  - `willpower_permanent`: Integer (1-10) - Permanent willpower rating
  - `willpower_current`: Integer (1-10) - Current willpower (can be lower than permanent)

## Merits & Flaws

### `merits_flaws` (ARRAY)
- Array of merit/flaw objects
- Format:
  ```json
  [
    {
      "name": "Natural Linguist",
      "type": "merit",
      "category": "Mental",
      "cost": 2,
      "description": "Learns languages easily"
    },
    {
      "name": "Inferiority Complex",
      "type": "flaw",
      "category": "Mental",
      "cost": 2,
      "description": "Feels need to constantly prove worth"
    }
  ]
  ```
- `type`: `"merit"` or `"flaw"`
- `category`: `"Physical"`, `"Social"`, or `"Mental"`
- `cost`: Point value (positive for merits, negative for flaws)

## Status Object

### `status` (OBJECT)
- Complete character status information
- Fields:
  - `sect_status`: String - Status within sect
  - `clan_status`: String - Status within clan
  - `city_status`: String - Status within city
  - `health_levels`: Integer - Number of health levels (typically 7)
  - `blood_pool_current`: Integer - Current blood points
  - `blood_pool_maximum`: Integer - Maximum blood points (varies by generation: gen 8-13 typically 10-15)
  - `blood_per_turn`: Integer - Blood points spendable per turn (typically 1)
  - `xp_total`: Integer - Total experience points earned (maps to DB `experience_total`)
  - `xp_spent`: Integer - Experience points spent
  - `xp_available`: Integer - Unspent experience (xp_total - xp_spent)
  - `notes`: String - Storyteller notes, gameplay notes, etc.

## Relationships & Social

### `coteries` (ARRAY)
- Array of coterie memberships
- Format:
  ```json
  [
    {
      "coterie_name": "The Night Owls",
      "coterie_type": "Social",
      "role": "Information Broker",
      "description": "...",
      "notes": "..."
    }
  ]
  ```

### `relationships` (ARRAY)
- Array of character relationships
- Format:
  ```json
  [
    {
      "related_character_name": "Character Name",
      "relationship_type": "Sire",
      "relationship_subtype": "Mentor",
      "strength": "Strong",
      "description": "Relationship description"
    }
  ]
  ```

## Equipment & Possessions

### `equipment` (STRING)
- Items, gear, and possessions
- Plain text description
- Example: `"Tiki bar tools and decorations, Coast Guard memorabilia"`

### `artifacts` (ARRAY)
- Array of artifact names or descriptions
- Example: `["Ancient Toreador Locket", "Giovanni Family Seal"]`

## Rituals & Special Abilities

### `rituals` (ARRAY)
- Array of Thaumaturgy/Necromancy rituals known
- Example: `["Ritual Name 1", "Ritual Name 2"]`

## Custom Data & Research

### `research_notes` (OBJECT)
- Custom research data or character-specific information
- Format: `{"custom_field_name": "Any character-specific data that doesn't fit elsewhere"}`

### `custom_data` (JSON, nullable)
- Database JSON column for additional custom fields
- Can store any valid JSON structure
- Use for fields not in the standard schema

## Timeline & History

### `timeline` (OBJECT)
- Historical timeline information
- Fields:
  - `birthplace`: String
  - `embrace_period`: String (e.g., "Early 1900s (circa 1905–1915)")
  - `notable_mortal_past`: String
  - `world_war_two_experience`: String
  - `arrival_in_phoenix`: String
  - `bunker_acquisition`: String
  - `years_in_bunker`: String

### `domain_and_haven` (OBJECT)
- Haven and domain information
- Fields:
  - `primary_haven_name`: String
  - `primary_haven_type`: String
  - `location_notes`: String
  - `bunker_history`: String
  - `bunker_function`: Array of strings
  - `false_public_assumptions`: String

### `current_residents` (OBJECT)
- Current residents of haven
- Format: `{"nosferatu": ["Name1", "Name2"], "ghouls": ["Name1"]}`

## Personality

### `personality` (OBJECT)
- Personality information
- Fields:
  - `tagline`: String - Character's tagline or motto
  - `narrative`: String - Detailed personality narrative

## Database Fields (Auto-managed)

### `id` (INTEGER)
- Auto-increment primary key (set by database)

### `user_id` (INTEGER)
- Creator/owner user ID (set by system)

### `character_image` (STRING)
- Path to character image file

### `clan_logo_url` (STRING)
- URL to clan logo image

### `current_state` (STRING)
- Additional state tracking field

### `experience_total` (INTEGER)
- Database field: Total experience (maps from `status.xp_total`)

### `experience_spent` (INTEGER)
- Database field: Spent experience (maps from `status.xp_spent`)

### `created_at` (TIMESTAMP)
- Auto-set by database on creation

### `updated_at` (TIMESTAMP)
- Auto-updated by database on modification

## Notes

### `notes` (TEXT)
- Storyteller notes, gameplay notes, personality traits, roleplaying hints, mechanical reminders, etc.

## Format Guidelines

1. **Always use `character_name`** (never `name`)
2. **Status should be a string**, not a nested object
3. **Use `xp_total`, `xp_available`, `xp_spent`** in the status object (not `total_xp`, `spent_xp`)
4. **Use `camarilla_status`** (not `affiliation`)
5. **Abilities and Disciplines** can use either object or string format - choose one and be consistent
6. **Traits are arrays of names**, not numeric values
7. **Backgrounds are name:level pairs**, not nested objects
8. **Blood pool belongs in status object**, not at root level

## Database Mapping Notes

- JSON `xp_total` → DB `experience_total`
- JSON `xp_available` → DB `experience_unspent`
- JSON `blood_pool` → DB `blood_pool_current`
- Abilities are stored in `character_abilities` table with `specialization` field
- Disciplines are stored in `character_disciplines` table (one row per discipline)
- Powers are stored in `character_discipline_powers` table (one row per power)

