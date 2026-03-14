# Database Tools

Reusable tools for database operations including imports, exports, audits, and maintenance.

## Tools

### assign_character_image.php

**Purpose:** Set a character's official portrait: copy an image to `uploads/characters/` and set `characters.portrait_name` in the database (Supabase). Use after generating a portrait (e.g. via 速推AI) to make it the character's display image.

**Usage:**
```bash
# DB only (file already in uploads/characters/)
php tools/repeatable/php/database-tools/assign_character_image.php --id=70 --image="Alessandro Vescari.png"

# Copy from source path then update DB
php tools/repeatable/php/database-tools/assign_character_image.php --id=70 --image="Alessandro Vescari.png" --source=images-generated/alessandro-vescari_20260315-clean.png

# Dry-run
php tools/repeatable/php/database-tools/assign_character_image.php --id=70 --image="Alessandro Vescari.png" --dry-run
```

**Inputs:** `--id` (character id), `--image` (filename in uploads/characters), optional `--source` (path to copy from). Loads `.env` from project root for Supabase.

**Outputs:** Copies file to `uploads/characters/` when `--source` is given; PATCHes `characters.portrait_name` for the given id.

**Reference:** [reference/Characters/Images/AGENTS.md](../../../reference/Characters/Images/AGENTS.md) — three-step workflow: copy to uploads, update DB, optionally update JSON.

---

### audit_rituals_duplicates.php

**Purpose:** Detects duplicate ritual entries in the database.

**Usage:**
```bash
# CLI with dry-run (preview only)
php tools/repeatable/php/database-tools/audit_rituals_duplicates.php --dry-run

# CLI with execution
php tools/repeatable/php/database-tools/audit_rituals_duplicates.php

# Web interface
# Access via browser: tools/repeatable/php/database-tools/audit_rituals_duplicates.php?dry_run=1
```

**Features:**
- Detects ID collisions
- Finds name collisions (case/punctuation variants)
- Identifies content similarity
- Supports dry-run mode

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rituals_master` table

---

### audit_rituals_sources.php

**Purpose:** Audits ritual source information in the database.

**Usage:**
```bash
# CLI with dry-run
php tools/repeatable/php/database-tools/audit_rituals_sources.php --dry-run

# CLI with execution
php tools/repeatable/php/database-tools/audit_rituals_sources.php
```

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rituals_master` table

---

### export_npcs.php

**Purpose:** Exports NPC characters from database to JSON files.

**Usage:**
```bash
# Export all NPCs
php tools/repeatable/php/database-tools/export_npcs.php

# Export with limit
php tools/repeatable/php/database-tools/export_npcs.php --limit=10

# Export specific character
php tools/repeatable/php/database-tools/export_npcs.php --id=123

# Specify output directory
php tools/repeatable/php/database-tools/export_npcs.php --out=/path/to/output

# Dry-run (preview only)
php tools/repeatable/php/database-tools/export_npcs.php --dry-run
```

**Output:** JSON files in `reference/Characters/Added to Database/` (or specified directory)

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table and related tables

---

### update_paths_completion.php

**Purpose:** Updates path completion data in the database.

**Usage:**
```bash
# CLI with dry-run
php tools/repeatable/php/database-tools/update_paths_completion.php --dry-run

# CLI with execution
php tools/repeatable/php/database-tools/update_paths_completion.php

# Web interface
# Access via browser: tools/repeatable/php/database-tools/update_paths_completion.php?dry_run=1
```

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Paths-related tables

---

### import_wraith_characters.php

**Purpose:** Imports Wraith characters into the database.

**Usage:**
```bash
# CLI with file parameter
php tools/repeatable/php/database-tools/import_wraith_characters.php /path/to/file.json

# Web interface
# Access via browser: tools/repeatable/php/database-tools/import_wraith_characters.php?file=filename.json
```

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `wraith_characters` table

---

### import_characters.php

**Purpose:** Imports character JSON files into the database.

**Usage:**
```bash
# CLI with specific file
php tools/repeatable/php/database-tools/import_characters.php filename.json

# Import all files in directory
php tools/repeatable/php/database-tools/import_characters.php

# Web interface - single file
# Access via browser: tools/repeatable/php/database-tools/import_characters.php?file=filename.json

# Web interface - all files
# Access via browser: tools/repeatable/php/database-tools/import_characters.php?all=1
```

**Input:** JSON files from `reference/Characters/Added to Database/`

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table and related tables

---

### import_locations.php

**Purpose:** Imports location JSON files into the database.

**Usage:**
```bash
# CLI with specific file
php tools/repeatable/php/database-tools/import_locations.php filename.json

# Import all files in directory
php tools/repeatable/php/database-tools/import_locations.php

# Web interface - single file
# Access via browser: tools/repeatable/php/database-tools/import_locations.php?file=filename.json

# Web interface - all files
# Access via browser: tools/repeatable/php/database-tools/import_locations.php?all=1
```

**Input:** JSON files from `reference/Locations/`

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `locations` table

---

### db_cleanup_0863.php

**Purpose:** Database cleanup script (specific to version 0.8.63).

**Usage:**
```bash
# Preview only
php tools/repeatable/php/database-tools/db_cleanup_0863.php

# Execute cleanup
php tools/repeatable/php/database-tools/db_cleanup_0863.php --execute
```

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Various database tables (depends on cleanup operations)

---

## Common Workflows

1. **Importing data:** Use `import_characters.php` or `import_locations.php` to bulk import JSON data
2. **Exporting data:** Use `export_npcs.php` to export database records to JSON
3. **Auditing data:** Use `audit_rituals_duplicates.php` or `audit_rituals_sources.php` to check data quality
4. **Maintenance:** Use `db_cleanup_0863.php` for version-specific cleanup operations

## Database Connection

All tools require database connection configured in `includes/connect.php`. Ensure database credentials are properly configured before running tools.
