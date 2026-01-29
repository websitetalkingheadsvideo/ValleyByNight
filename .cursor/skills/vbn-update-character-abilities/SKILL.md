---
name: vbn-update-character-abilities
description: Update a Valley by Night character's abilities and specializations in the database from a JSON payload. Use when the user provides a character name and a JSON object with abilities (and optional specializations) and wants to sync them to the character_abilities table.
---

# VbN Update Character Abilities

## Goal

Update `character_abilities` for a given character using a JSON payload. Replaces all existing abilities for that character with the provided list; optional top-level `specializations` object is applied by ability name.

## Inputs

- **character_name**: Exact character name as stored in `characters.character_name` (e.g. `"Barry Washington"`).
- **JSON** with:
  - **abilities** (required): Array of `{ "name": string, "category": "Mental"|"Social"|"Physical"|"Optional", "level": 1-5 }`. Category can be omitted and will be looked up from the `abilities` table.
  - **specializations** (optional): Object mapping ability name to specialization text, e.g. `{ "Academics": "Business Administration", "Finance": "Venture Capital" }`.

## Process

1. **Build the JSON**  
   Ensure it has at least `abilities`; add `specializations` if provided.

2. **Write a temp file**  
   Save the JSON to `V:\reference\Characters\Added to Database\<slug>_abilities.json` where `<slug>` is the character name lowercased, spaces replaced with underscores (e.g. `barry_washington_abilities.json`).

3. **Run the PHP script**  
   From project root (or with correct paths):
   ```bash
   php V:\tools\repeatable\update_abilities_from_json.php --json=<slug>_abilities.json --character="<Character Name>"
   ```
   Use the exact character name including casing (e.g. `--character="Barry Washington"`).

4. **Confirm output**  
   Script resolves character name to ID, deletes existing rows in `character_abilities` for that character, then inserts each ability (with level and specialization). Report success or any errors from the script.

## JSON format reference

```json
{
  "abilities": [
    { "name": "Academics", "category": "Mental", "level": 4 },
    { "name": "Finance", "category": "Mental", "level": 4 }
  ],
  "specializations": {
    "Academics": "Business Administration",
    "Finance": "Venture Capital"
  }
}
```

- Ability names must match canonical names in the `abilities` table (e.g. "Academics", "Etiquette", "Brawl").
- Category is optional; if missing, the script looks it up from `abilities`.
- Level must be 1–5.

## Notes

- The script requires the JSON file to live under `V:\reference\Characters\Added to Database\`. Do not delete the temp file unless the user asks; leaving it is acceptable.
- Database access is done only via the PHP script (no CLI DB access).
