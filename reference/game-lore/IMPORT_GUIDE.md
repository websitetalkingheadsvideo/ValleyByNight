# Character Import Guide

## Quick Start

**To import character JSON files:**

1. **Place JSON file** in `reference/Characters/` folder (e.g., `Misfortune.json`)
2. **Run import script** via web browser or CLI:
   
   **Web Browser:**
   ```
database/import_characters.php?file=Misfortune.json
database/import_characters.php?all=1
   ```
   
   **Command Line:**
   ```bash
   php database/import_characters.php Misfortune.json
   php database/import_characters.php  # Imports all JSON files
   ```
3. **Check results** - Script will show success or detailed error summary

## JSON File Format

Characters must be in JSON format matching `character-example.json`:

### Required Fields:
- `character_name` - Character's full name
- `player_name` - Player or "ST/NPC"
- `chronicle` - Campaign name
- `nature`, `demeanor` - Personality archetypes
- `concept` - One-line character concept
- `clan` - Vampire clan
- `generation` - Generation number (1-13+)
- `sire` - Who embraced them
- `pc` - 0 for NPC, 1 for PC
- `biography` - Full backstory
- `equipment` - Items and gear

### Character Stats:
- `traits` - Object with Physical/Social/Mental arrays
- `negativeTraits` - Object with Physical/Social/Mental arrays
- `abilities` - Array of {name, category, level}
- `specializations` - Object mapping ability names to specializations
- `disciplines` - Array of {name, level, powers: [{level, power}]}
- `backgrounds` - Object with name: level pairs
- `backgroundDetails` - Object with name: description pairs
- `morality` - Object with path_name, path_rating, virtues, willpower
- `merits_flaws` - Array of {name, type, category, cost, description}
- `status` - Object with xp_total, xp_available, blood_pool, notes

## Database Mapping

### Column Name Mappings:
- **characters table:**
  - JSON `xp_total` → DB `experience_total`
  - JSON `xp_available` → DB `experience_unspent`
  - JSON `blood_pool` → DB `blood_pool_current`

- **character_abilities table:**
  - No `ability_category` column (not stored)
  - `specialization` in same table (not separate)

- **character_disciplines table:**
  - Each **power is a separate row** (not child table)
  - `level` is ENUM: 'Basic', 'Intermediate', 'Advanced'
  - Power levels 1-2 = Basic, 3 = Intermediate, 4-5 = Advanced

- **character_backgrounds table:**
  - Column is `level` not `background_level`
  - Column is `description` not `background_details`

- **character_merits_flaws table:**
  - Columns: `name`, `type`, `category`, `point_value`, `point_cost`, `description`
  - `type` ENUM: 'Merit' or 'Flaw' (capitalized)

## Examples

### Import Single Character (Misfortune):
**Web:**
```
database/import_characters.php?file=Misfortune.json
```

**CLI:**
```bash
php database/import_characters.php Misfortune.json
```

### Import All Characters:
**Web:**
```
database/import_characters.php?all=1
```

**CLI:**
```bash
php database/import_characters.php
```

### Import Another Character:
1. Place JSON file in `reference/Characters/` folder (e.g., `Alice Tremere.json`)
2. Run import script with filename
3. Check output for success or errors

## Troubleshooting

### Common Issues:

1. **"No file specified"**
   - Add `?file=Filename.json` to URL

2. **"JSON file not found"**
   - Ensure file is in `reference/Characters/` folder
   - Check filename spelling (case-sensitive)
   - URL-encode spaces as `%20` in web URLs

3. **"Failed to parse JSON"**
   - Validate JSON at jsonlint.com
   - Check for missing commas or quotes
   - Ensure all required fields present

4. **Foreign key constraint error**
   - Character requires valid user_id (currently hardcoded to 1)
   - Check user ID 1 exists: `data/check_users.php`

## Files

- **`database/import_characters.php`** - Main import script (use this)
- **`reference/Characters/`** - Directory containing character JSON files
- **`reference/Characters/character.json`** - Template/example format
- **`reference/Characters/Misfortune.json`** - Complete example character
- **`admin/view_character_api.php`** - View character data via API

## Character Management

### View Characters
- **[Character List](https://www.websitetalkingheads.com/vbn/data/list_characters.php)** - See all characters

### Delete Characters
- Delete from character list (🗑️ Delete button)
- Delete from character sheet (top-right button)
- Two-step confirmation prevents accidents
- See `DELETE_GUIDE.md` for details

## Notes

- All imports use **user_id = 1** (admin/ST account)
- Imports are **transactional** - rolls back on error
- **Upsert behavior**: Characters are identified by `character_name`
  - If character exists: Updates all fields and related tables
  - If character is new: Inserts new record
- Character IDs auto-increment
- Script handles multiple JSON format variations automatically
- Database connection info in `includes/connect.php`
- Remote database at `vdb5.pit.pair.com`

## Supported JSON Format Variations

The import script automatically handles:
- Field name variations (`name` → `character_name`, `affiliation` → `camarilla_status`)
- Nested status objects (`status.xp_total`, `status_details.xp_total`)
- Multiple ability formats (array of objects, array of strings, object with categories)
- Multiple discipline formats (array of objects, array of strings, object format)
- Experience field variations (`xp_total`, `total_xp`, `status.xp_total`)
- Appearance as object or string

See `reference/Characters/CHARACTER_DATABASE_ANALYSIS.md` for detailed format documentation.

