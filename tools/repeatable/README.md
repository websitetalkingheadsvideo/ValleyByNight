# Repeatable Maintenance Scripts

This directory contains idempotent maintenance scripts for the VbN project.

## Character Biography Backfill

**Script:** `backfill_character_biography.php`

### Purpose
Identifies characters with missing biography fields in the database and backfills them by searching JSON and Markdown files in the project.

### Usage

```bash
# Dry run (preview changes without updating database)
php tools/repeatable/backfill_character_biography.php --dry-run

# Run with verbose output
php tools/repeatable/backfill_character_biography.php --verbose

# Set minimum biography length (default: 50)
php tools/repeatable/backfill_character_biography.php --min-length=100

# Combine options
php tools/repeatable/backfill_character_biography.php --dry-run --verbose --min-length=75
```

### Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-length=N`: Minimum biography length threshold (default: 50 characters)
- `--help`: Show help message

### Output Files

All output files are generated in `tools/repeatable/`:

1. **`missing_history_report.json`**
   - Lists all characters with missing biography
   - Includes character ID, name, reason for missing status, and timestamp

2. **`history_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, content length, and hash
   - Appends to existing log file on subsequent runs

3. **`history_not_found.json`**
   - Lists characters where no biography was found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts `biography` field

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Extracts content from sections: `# Character History:`, `## History`, `## Biography`, `## Backstory`
- Cleans markdown formatting while preserving text

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with empty/missing biography
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail

### Example Output

```
=== Character Biography Backfill Summary ===
Total characters scanned: 150
Missing initially: 25
Histories backfilled: 18
Still missing: 7
Skipped (already had biography): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_history_report.json
- tools/repeatable/history_updates.log
- tools/repeatable/history_not_found.json
```

---

## Character Appearance Backfill

**Script:** `backfill_character_appearance.php`

### Purpose
Identifies characters with missing appearance fields in the database and backfills them by searching JSON and Markdown files in the project.

### Usage

```bash
# Dry run (preview changes without updating database)
php tools/repeatable/backfill_character_appearance.php --dry-run

# Run with verbose output
php tools/repeatable/backfill_character_appearance.php --verbose

# Set minimum appearance length (default: 30)
php tools/repeatable/backfill_character_appearance.php --min-length=50

# Combine options
php tools/repeatable/backfill_character_appearance.php --dry-run --verbose --min-length=40
```

### Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-length=N`: Minimum appearance length threshold (default: 30 characters)
- `--help`: Show help message

### Output Files

All output files are generated in `tools/repeatable/`:

1. **`missing_appearance_report.json`**
   - Lists all characters with missing appearance
   - Includes character ID, name, reason for missing status, and timestamp

2. **`appearance_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, content length, and hash
   - Appends to existing log file on subsequent runs

3. **`appearance_not_found.json`**
   - Lists characters where no appearance was found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts appearance from multiple possible fields:
  - `appearance` (string)
  - `appearance_detailed.detailed_description` (preferred)
  - `appearance_detailed.short_summary` (fallback)
  - `physical_description` (builds description from height, build, skin, hair, clothing fields)

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Extracts content from sections: `# Character Appearance:`, `# Appearance:`, `## Appearance`, `## Physical Description`, `## Description`
- Cleans markdown formatting while preserving text

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with empty/missing appearance
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail

### Example Output

```
=== Character Appearance Backfill Summary ===
Total characters scanned: 150
Missing initially: 25
Appearances backfilled: 18
Still missing: 7
Skipped (already had appearance): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_appearance_report.json
- tools/repeatable/appearance_updates.log
- tools/repeatable/appearance_not_found.json
```

---

## Character Notes Backfill

**Script:** `backfill_character_notes.php`

### Purpose
Identifies characters with missing notes fields in the database and backfills them by searching JSON and Markdown files in the project.

### Usage

```bash
# Dry run (preview changes without updating database)
php tools/repeatable/backfill_character_notes.php --dry-run

# Run with verbose output
php tools/repeatable/backfill_character_notes.php --verbose

# Set minimum notes length (default: 20)
php tools/repeatable/backfill_character_notes.php --min-length=30

# Combine options
php tools/repeatable/backfill_character_notes.php --dry-run --verbose --min-length=25
```

### Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-length=N`: Minimum notes length threshold (default: 20 characters)
- `--help`: Show help message

### Output Files

All output files are generated in `tools/repeatable/`:

1. **`missing_notes_report.json`**
   - Lists all characters with missing notes
   - Includes character ID, name, reason for missing status, and timestamp

2. **`notes_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, content length, and hash
   - Appends to existing log file on subsequent runs

3. **`notes_not_found.json`**
   - Lists characters where no notes were found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts notes from multiple possible fields:
  - `notes` (string)
  - `status.notes` (if status is an object)

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Extracts content from sections: `# Character Notes:`, `# Notes:`, `## Notes`, `## Player Notes`, `## Storyteller Notes`
- Cleans markdown formatting while preserving text

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with empty/missing notes
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail

### Example Output

```
=== Character Notes Backfill Summary ===
Total characters scanned: 150
Missing initially: 25
Notes backfilled: 18
Still missing: 7
Skipped (already had notes): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_notes_report.json
- tools/repeatable/notes_updates.log
- tools/repeatable/notes_not_found.json
```

