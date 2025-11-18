# NPC Character Teaser Tracking System

## Overview

This tracking system scans all NPCs in the VbN database and compares them against existing Character Teaser files to identify which NPCs are missing Character Teasers.

**Goal**: Ensure every NPC in the database has a corresponding Character Teaser file.

## What It Does

1. **Queries the Database**: Retrieves all NPCs from the `characters` table where `player_name = 'NPC'`
2. **Scans Character Teasers**: Reads the `reference/Scenes/Character Teasers/` directory to find existing teaser files
3. **Compares and Identifies**: Determines which NPCs do not have matching Character Teaser files
4. **Generates Report**: Creates a JSON file listing all NPCs missing Character Teasers with their complete data

## How to Run

Run the script from your web browser:

```
https://vbn.talkingheads.video/reference/Scenes/Character%20Teasers/tracking/generate_missing_character_teasers.php
```

The script will display progress information and generate the JSON output file.

## Output

The script generates a JSON file at:

```
reference/Scenes/Character Teasers/tracking/missing-character-teasers.json
```

### JSON Structure

The output is an array of NPC objects, each containing:

```json
[
  {
    "id": 123,
    "name": "Character Name",
    "description": "Character description...",
    "notes": "Additional notes...",
    "biography": "Full backstory...",
    "clan": "Ventrue",
    "nature": "Architect",
    "demeanor": "Director",
    "concept": "Corporate shark",
    "equipmentTraits": ["Phone", "Car"],
    "negativeTraits": ["Arrogant"],
    "morality": {
      "path_name": "Path of Humanity"
    }
  }
]
```

### Field Descriptions

- **id**: Character ID from the database
- **name**: Character's full name
- **description**: Short description of the character
- **notes**: Additional notes about the character
- **biography**: Full character backstory
- **clan**: Vampire clan
- **nature**: Character's nature archetype
- **demeanor**: Character's demeanor archetype
- **concept**: One-line character concept
- **equipmentTraits**: Array of equipment item names assigned to the character (from `character_equipment` table)
- **negativeTraits**: Array of negative trait names (from `character_negative_traits` table)
- **morality**: Object containing `path_name` (from `character_morality` table)

**Note**: If any field is missing in the database, it will be set to `null` in the JSON output.

## Character Teaser File Naming

Character Teaser files are matched by **exact character name** (case-insensitive). The script:

1. Reads all `.md` files from `reference/Scenes/Character Teasers/`
2. Extracts the filename (without `.md` extension) as the character name
3. Compares this against NPC names from the database

**Example**: An NPC named "Violet" should have a file named `Violet.md` in the Character Teasers directory.

## Re-running the Script

The script is **idempotent** and safe to run multiple times:

- Each run completely refreshes the JSON output file
- The output is deterministic (sorted by character ID)
- Running the script does not modify the database or existing Character Teaser files

## Interpreting the Results

### When Creating Character Teasers

Use the `missing-character-teasers.json` file as your **authoritative list** of NPCs that need Character Teasers. Each entry contains all the information you need to write a teaser:

1. **Character Name**: Use the `name` field
2. **Character Details**: Reference `description`, `biography`, `notes` for context
3. **Character Traits**: Use `clan`, `nature`, `demeanor`, `concept` for personality
4. **Equipment & Traits**: Reference `equipmentTraits` and `negativeTraits` for specific details
5. **Morality**: Use `morality.path_name` for character's moral framework

### After Creating a Teaser

1. Create the Character Teaser file in `reference/Scenes/Character Teasers/`
2. Name it exactly as the character's name (e.g., `Character Name.md`)
3. Re-run this script to update the tracking JSON
4. The NPC should no longer appear in `missing-character-teasers.json`

## Requirements

- PHP 7.4+ (or PHP 8.x)
- MySQL database connection (via `includes/connect.php`)
- Read access to `reference/Scenes/Character Teasers/` directory
- Write access to `reference/Scenes/Character Teasers/tracking/` directory

## Database Tables Used

- `characters` - Main character data
- `character_negative_traits` - Negative traits
- `character_equipment` - Equipment assignments
- `items` - Equipment item names
- `character_morality` - Morality/path information

## Troubleshooting

### "Character Teasers directory not found"

- Verify that `reference/Scenes/Character Teasers/` exists
- Check file permissions

### "Failed to query NPCs"

- Verify database connection in `includes/connect.php`
- Check that the `characters` table exists and is accessible

### "Failed to write output file"

- Check write permissions on `reference/Scenes/Character Teasers/tracking/` directory
- Ensure the directory exists

### Empty JSON Output

- If the JSON file is empty (`[]`), it means all NPCs have Character Teasers! ✅
- This is the desired end state.

## Maintenance

Run this script:

- **After adding new NPCs** to the database
- **After creating new Character Teasers** to verify they're being tracked
- **Periodically** to ensure no NPCs are missing teasers

## Notes

- The script only processes NPCs (where `player_name = 'NPC'`)
- PC characters are excluded from tracking
- File matching is case-insensitive but should match character names exactly
- The script does not modify the database or existing files

