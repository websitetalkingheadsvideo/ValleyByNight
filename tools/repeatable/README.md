# Repeatable Tools

This directory contains reusable, repeatable tools for the VbN project, organized by language and function.

## Structure

- **PHP Tools** - [PHP Tools Documentation](php/README.md)
  - [MCP Tools](php/mcp-tools/README.md) - MCP configuration and verification tools
  - [Database Tools](php/database-tools/README.md) - Database imports, exports, and audits
  - [Data Tools](php/data-tools/README.md) - Data reporting and summary generation

- **Python Tools** - [Python Tools Documentation](python/README.md)
  - [JSON Tools](python/json-tools/README.md) - JSON processing and parsing
  - [Text Tools](python/text-tools/README.md) - Text processing and cleanup
  - [PDF Tools](python/pdf-tools/README.md) - PDF extraction and processing
  - [Text Cleanup Tools](python/text-cleanup-tools/README.md) - Clean up OCR-extracted text
  - [OCR Tools](python/ocr-tools/README.md) - Perform OCR on image-based PDFs
  - [API Tools](python/api-tools/README.md) - API interaction and data fetching
  - [Data Extraction](python/data-extraction/README.md) - Data extraction from various sources
  - [Analysis Tools](python/analysis-tools/README.md) - Data analysis and reporting

---

## Character Maintenance Scripts

This directory also contains idempotent maintenance scripts for the VbN project.

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

---

## Sync Missing Abilities

**Script:** `tools/repeatable/character-data/sync_abilities_from_json.php`

### Purpose
Scans character JSON files in `reference/Characters/Added to Database/` for characters that have no abilities in the database but do have abilities in JSON, then inserts those abilities. Handles multiple JSON formats (string e.g. `"Ability 3"`, object with `name`/`ability_name`, `category`/`ability_category`, `level`, `specialization`; optional `specializations` map).

### Usage

```bash
# Dry run (preview, default)
php tools/repeatable/character-data/sync_abilities_from_json.php --dry-run

# Execute
php tools/repeatable/character-data/sync_abilities_from_json.php --execute
```

- **Web:** Use the "Sync Missing Abilities" card on `tools/repeatable/character-data/index.php`: "Dry run" link or "Run sync" button (POST with `execute=1`).

### Output

- Log: `tools/repeatable/abilities-sync-YYYYMMDD-HHMMSS.log`

### Safety

- Default is dry-run; use `--execute` or POST `execute=1` to write. Only inserts for characters with 0 abilities; skips others. Uses prepared statements.

---

## Character Nature Backfill

**Script:** `backfill_character_nature.php`

### Purpose
Identifies characters with missing nature fields in the database and backfills them by searching JSON and Markdown files in the project.

### Usage

```bash
# Dry run (preview changes without updating database)
php tools/repeatable/backfill_character_nature.php --dry-run

# Run with verbose output
php tools/repeatable/backfill_character_nature.php --verbose

# Set minimum nature length (default: 3)
php tools/repeatable/backfill_character_nature.php --min-length=5

# Combine options
php tools/repeatable/backfill_character_nature.php --dry-run --verbose --min-length=4
```

### Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-length=N`: Minimum nature length threshold (default: 3 characters)
- `--help`: Show help message

### Output Files

All output files are generated in `tools/repeatable/`:

1. **`missing_nature_report.json`**
   - Lists all characters with missing nature
   - Includes character ID, name, reason for missing status, and timestamp

2. **`nature_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, content length, and hash
   - Appends to existing log file on subsequent runs

3. **`nature_not_found.json`**
   - Lists characters where no nature was found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts `nature` field (simple string)

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Extracts content from patterns: `Nature: X`, `## Nature`, `# Nature:`
- Cleans markdown formatting and extracts first word/phrase (nature is typically a single word)

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with empty/missing nature
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail

### Example Output

```
=== Character Nature Backfill Summary ===
Total characters scanned: 150
Missing initially: 25
Natures backfilled: 18
Still missing: 7
Skipped (already had nature): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_nature_report.json
- tools/repeatable/nature_updates.log
- tools/repeatable/nature_not_found.json
```

---

## Character Backgrounds Backfill

**Script:** `backfill_character_backgrounds.php`

### Purpose
Identifies characters with missing backgrounds in the `character_backgrounds` table and backfills them by searching JSON and Markdown files in the project.

### Usage

```bash
# Dry run (preview changes without updating database)
php tools/repeatable/backfill_character_backgrounds.php --dry-run

# Run with verbose output
php tools/repeatable/backfill_character_backgrounds.php --verbose

# Set minimum background level to import (default: 1, use 0 to include all)
php tools/repeatable/backfill_character_backgrounds.php --min-level=0

# Combine options
php tools/repeatable/backfill_character_backgrounds.php --dry-run --verbose --min-level=1
```

### Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-level=N`: Minimum background level to import (default: 1, use 0 to include all backgrounds including level 0)
- `--help`: Show help message

### Output Files

All output files are generated in `tools/repeatable/`:

1. **`missing_backgrounds_report.json`**
   - Lists all characters with missing backgrounds
   - Includes character ID, name, reason for missing status, and timestamp

2. **`backgrounds_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, background count, and list of backgrounds
   - Appends to existing log file on subsequent runs

3. **`backgrounds_not_found.json`**
   - Lists characters where no backgrounds were found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts `backgrounds` object (name: level pairs)
- Extracts `backgroundDetails` object for descriptions (optional)
- Filters backgrounds by minimum level threshold

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Extracts content from sections: `# Backgrounds:`, `## Backgrounds`
- Parses patterns like "Background Name: 3" to extract name and level

### Database Structure

Backgrounds are stored in the `character_backgrounds` table with:
- `character_id` (foreign key)
- `background_name` (e.g., "Allies", "Resources", "Status")
- `level` (1-5 typically)
- `description` (optional text description)

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with no existing backgrounds in the table
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail
- **Level filtering**: Can filter out low-level backgrounds using `--min-level`

### Example Output

```
=== Character Backgrounds Backfill Summary ===
Total characters scanned: 150
Missing initially: 25
Backgrounds backfilled: 18
Still missing: 7
Skipped (already had backgrounds): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_backgrounds_report.json
- tools/repeatable/backgrounds_updates.log
- tools/repeatable/backgrounds_not_found.json
```

### JSON Format Expected

The script expects JSON files with this structure:

```json
{
  "character_name": "Character Name",
  "backgrounds": {
    "Allies": 3,
    "Resources": 4,
    "Status": 2,
    "Generation": 6
  },
  "backgroundDetails": {
    "Allies": "Description of allies",
    "Resources": "Description of resources"
  }
}
```

---

## Generic Field Backfill (All Fields)

**Script:** `backfill_character_field.php`

### Purpose
A universal script that can backfill **any** character field by specifying the field name. This script includes pre-configured settings for common fields (biography, appearance, notes, nature, demeanor, concept, sire, equipment) but can also be used for any other field with custom options.

### Usage

```bash
# Backfill a pre-configured field
php tools/repeatable/backfill_character_field.php --field=biography --dry-run
php tools/repeatable/backfill_character_field.php --field=demeanor --min-length=3

# Backfill a custom field with custom JSON path
php tools/repeatable/backfill_character_field.php --field=custom_field --json-path=custom.nested.field --min-length=10

# Run with verbose output
php tools/repeatable/backfill_character_field.php --field=concept --verbose
```

### Required Options

- `--field=<name>`: Database field name to backfill
  - Pre-configured fields: `biography`, `appearance`, `notes`, `nature`, `demeanor`, `concept`, `sire`, `equipment`
  - Any other field name can be used (will use sensible defaults)

### Optional Options

- `--dry-run`: Show what would be updated without writing to database
- `--verbose`: Show detailed progress for each character
- `--min-length=N`: Minimum field length threshold (default: auto-detected based on field)
- `--json-path=<path>`: JSON field path using dot notation (e.g., `"nature"` or `"status.notes"` or `"appearance_detailed.detailed_description"`)
- `--help`: Show help message

### Pre-Configured Fields

The script includes optimized configurations for:

| Field | Min Length | JSON Paths | Notes |
|-------|------------|------------|-------|
| `biography` | 50 | `biography` | Full backstory text |
| `appearance` | 30 | `appearance`, `appearance_detailed.detailed_description`, `appearance_detailed.short_summary` | Physical description |
| `notes` | 20 | `notes`, `status.notes` | Player/storyteller notes |
| `nature` | 3 | `nature` | Single word archetype |
| `demeanor` | 3 | `demeanor` | Single word archetype |
| `concept` | 10 | `concept` | One-line character concept |
| `sire` | 3 | `sire` | Who embraced the character |
| `equipment` | 10 | `equipment` | Items and gear |

### Output Files

All output files are generated in `tools/repeatable/` with field-specific names:

1. **`missing_<field>_report.json`**
   - Lists all characters with missing field
   - Includes character ID, name, reason for missing status, and timestamp

2. **`<field>_updates.log`**
   - Plain text log of all database updates
   - Includes character ID, name, source file, content length, and hash
   - Appends to existing log file on subsequent runs

3. **`<field>_not_found.json`**
   - Lists characters where no data was found in source files
   - Includes search paths attempted

### Search Strategy

The script uses a two-pass approach:

**Pass A: JSON Files**
- Searches `reference/Characters/**/*.json`
- Searches `agents/character_agent/data/Characters/**/*.json` (if exists)
- Matches by `character_name` field (case-insensitive, fuzzy matching)
- Extracts field using dot-notation paths (e.g., `status.notes`)

**Pass B: Markdown Files**
- Searches `reference/Characters/**/*.md`
- Searches `agents/character_agent/data/Characters/**/*.md` (if exists)
- Matches by filename and/or content
- Uses field-specific regex patterns to extract content
- Cleans markdown formatting while preserving text

### Safety Features

- **Idempotent**: Safe to run multiple times
- **Safeguards**: Re-checks database state before each update
- **No overwrites**: Only updates characters with empty/missing field
- **Prepared statements**: Prevents SQL injection
- **Error handling**: Continues processing if individual files fail
- **Field validation**: Validates field names and uses safe SQL escaping

### Example Output

```
=== Character demeanor Backfill Script ===
Mode: DRY RUN (no database writes)
Min length: 3 characters
JSON paths: demeanor

Step 1: Scanning database for characters with missing demeanor...
Found 50 total characters
Found 5 characters with missing demeanor

Step 2: Searching for demeanor in JSON and Markdown files...

Step 3: Generating reports...
  Generated: missing_demeanor_report.json
  Generated: demeanor_updates.log
  Generated: demeanor_not_found.json

=== Character demeanor Backfill Summary ===
Total characters scanned: 50
Missing initially: 5
Demeanors backfilled: 4
Still missing: 1
Skipped (already had demeanor): 0
Errors: 0

Reports generated:
- tools/repeatable/missing_demeanor_report.json
- tools/repeatable/demeanor_updates.log
- tools/repeatable/demeanor_not_found.json
```

### Advantages Over Individual Scripts

- **Single script** to maintain instead of multiple
- **Consistent behavior** across all fields
- **Easy to extend** with new field configurations
- **Flexible** for custom fields with `--json-path` option
- **Less code duplication** - all common logic in one place

