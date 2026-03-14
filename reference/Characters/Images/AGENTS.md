# Character Images Directory - Agent Guide

## Purpose
This directory (`reference/Characters/Images/`) is where character portrait images are stored as **reference files**. However, the application does **not** load images directly from this location.

## Image Deployment Workflow

### Important: Three-Step Process Required

1. **Reference Storage**: Character images are stored here in `reference/Characters/Images/` for version control and organization.

2. **Application Copy**: Images must be **copied** to `uploads/characters/` for the application to load them.

3. **Database Update**: The portrait filename in the database **must be updated** for the image to display. JSON files are reference only - the database is the source of truth. **Supabase**: the column is `characters.portrait_name` (not `character_image`). Use `character_name` in WHERE; `id` is UUID.

### Why Two Locations?

- **`reference/Characters/Images/`**: Reference storage for version control and backup
- **`uploads/characters/`**: Active directory where the application loads images from

### Image Loading Path

The application constructs image paths as:
```
PATH_PREFIX + 'uploads/characters/' + character_image
```

The app resolves the filename from the **database** via `characters.portrait_name` (Supabase; the resolver also checks legacy `character_image` if present). Example: `"Trace Element.png"`.

**Important**: The application loads character data from the database, not from JSON files. JSON files are reference files only.

**Code Location**: See `includes/character_view_modal.php:374` and `admin/admin_panel.php:284`

## Adding New Character Images

When adding new character images:

1. **Store image** in `reference/Characters/Images/` with the desired filename (e.g., `"Trace Element.png"`)

2. **Copy image** to `uploads/characters/` directory:
   ```powershell
   Copy-Item -Path "V:\reference\Characters\Images\Trace Element.png" -Destination "V:\uploads\characters\Trace Element.png" -Force
   ```

3. **Update database record** - **This is required for the image to display**. Run in **Supabase Dashboard → SQL Editor** (or use the PHP script below):
   ```sql
   UPDATE characters SET portrait_name = 'Trace Element.png' WHERE character_name = 'Trace Element';
   ```
   **Note**: Column is `portrait_name`. Use `character_name` in WHERE (do not use `id = 70`; `id` is UUID).

   **Alternative**: PHP script (copies file and updates DB): `php tools/repeatable/php/database-tools/assign_character_image.php --id=UUID --image="Name.png" [--source=path/to/file]`. For SQL-only (file already in uploads): see `tools/repeatable/php/database-tools/assign_character_image_alessandro.sql` as a template.

4. **Update character JSON** `character_image` field for reference (optional, but recommended):
   ```json
   "character_image": "Trace Element.png"
   ```

5. **Verify**: The application will now load the character image from the database instead of falling back to the clan logo.

## Filename Format

- Use spaces in filenames if the character name contains spaces (e.g., `"Trace Element.png"`)
- Match the filename exactly in the **database** `portrait_name` column and (optionally) in JSON `character_image` field
- Case-sensitive: `"Trace Element.png"` not `"trace element.png"`

## Fallback Behavior

If `portrait_name` (and any legacy `character_image`) is empty in the **database** or the image file doesn't exist in `uploads/characters/`, the application will:
- For Wraith characters: Use `images/Clan Logos/WtOlogo.webp`
- For VtM characters: Use the clan logo from `clan_logo_url` or `clanLogoUrl(clan)` function

**Note**: Updating the JSON file alone will NOT update the character view. The database record must be updated.

**Code Location**: See `includes/character_view_modal.php:365-374`

## File Naming Examples

✅ **Correct**:
- `"character_image": "Trace Element.png"` → File: `Trace Element.png`
- `"character_image": "Butch Reed.png"` → File: `Butch Reed.png`
- `"character_image": "Evan Mercer.png"` → File: `Evan Mercer.png`

❌ **Incorrect**:
- `"character_image": "trace_element.png"` → Doesn't match `Trace Element.png`
- `"character_image": "Trace Element.png"` but file missing from `uploads/characters/`
- `"character_image": ""` → Will fallback to clan logo

## Database vs JSON Files

**Critical Understanding**: The application loads character data from the database, not from JSON files.

- **Database**: Source of truth for the application - must be updated for images to display
- **JSON files**: Reference files for version control and backup - updating JSON alone will NOT change what displays in the application

**Example**: If you update the JSON file but the database still has `portrait_name = NULL`, the character will show the clan logo, not the image.

## Update Scripts

- **SQL (recommended for one-off)**: Run in Supabase SQL Editor: `UPDATE characters SET portrait_name = 'Character Name.png' WHERE character_name = 'Character Name';` Example: `tools/repeatable/php/database-tools/assign_character_image_alessandro.sql`.
- **PHP**: `tools/repeatable/php/database-tools/assign_character_image.php` — copies a file to `uploads/characters/` and sets `portrait_name` (see that script’s `--id` for UUID; script uses Supabase PATCH). Documented in `tools/repeatable/php/database-tools/README.md`.

## Automation Note

Currently, copying images from `reference/Characters/Images/` to `uploads/characters/` and updating the database must be done manually. Future automation could:
- Sync images from reference to uploads directory
- Auto-update database records based on JSON file changes
- Sync JSON and database for consistency
