# Repeatable Tools - Complete Guide

This document provides a comprehensive guide to all reusable tools in `tools/repeatable/`. Each tool is documented with its purpose, usage, dependencies, and examples.

## Table of Contents

- [PHP Tools](#php-tools)
  - [MCP Tools](#mcp-tools)
  - [Database Tools](#database-tools)
  - [Character Data Tools](#character-data-tools)
  - [Data Tools](#data-tools)
  - [API Tools](#api-tools-php)
- [Python Tools](#python-tools)
  - [JSON Tools](#json-tools)
  - [Text Tools](#text-tools)
  - [PDF Tools](#pdf-tools)
  - [OCR Tools](#ocr-tools)
  - [API Tools](#api-tools)
  - [Data Extraction](#data-extraction)
  - [Analysis Tools](#analysis-tools)
  - [Books Tools](#books-tools)

---

## PHP Tools

### MCP Tools

Tools for managing and verifying MCP (Model Context Protocol) configuration files and directory structures.

#### check_mcp_json.php

**Purpose:** Validates the `.cursor/mcp.json` file for JSON syntax errors.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/check_mcp_json.php
```

**What it does:**
- Reads `.cursor/mcp.json` from project root
- Validates JSON syntax
- Reports errors with line numbers if invalid
- Displays structure if valid

**Output:** Console output showing validation status

**Dependencies:** None (uses PHP built-in JSON functions)

---

#### fix_mcp_json_paths.php

**Purpose:** Fixes network path references in `.cursor/mcp.json` to use mapped drive `V:\`.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/fix_mcp_json_paths.php
```

**What it does:**
- Reads `.cursor/mcp.json`
- Replaces network paths (`\\\\amber\\htdocs\\`, `G:/VbN/`, etc.) with `V:\`
- Creates backup before modification
- Validates JSON after update

**Output:**
- Creates backup: `.cursor/mcp.json.backup.YYYY-MM-DD_HHMMSS`
- Updates `.cursor/mcp.json` with fixed paths

**Dependencies:** None (modifies `.cursor/mcp.json` file)

**Example:**
```bash
# Before: "\\\\amber\\htdocs\\agents\\style_agent"
# After: "V:\\agents\\style_agent"
```

---

#### verify_mcp_structure.php

**Purpose:** Verifies MCP directory structure (checks Style Agent structure).

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/verify_mcp_structure.php
```

**What it does:**
- Checks required directories (docs, indexes, rules, prompts)
- Verifies required root files (README.md, RULES.md, PROMPTS.md, INDEX.md)
- Lists documentation and index files

**Output:** HTML output showing verification results

**Dependencies:** None (reads filesystem structure)

**Use case:** Run to verify all required files/directories exist in the MCP structure

---

#### create_mcp_directories.php

**Purpose:** Creates missing MCP directories for Style Agent.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/create_mcp_directories.php
```

**What it does:**
- Creates required directories (docs, rules, prompts)
- Verifies directory structure
- Displays current permissions

**Output:** HTML output showing created directories and verification

**Dependencies:** None (creates directories)

**Use case:** Run once to set up directory structure before uploading files

---

### Database Tools

Reusable tools for database operations including imports, exports, audits, and maintenance.

#### audit_rituals_duplicates.php

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

**What it does:**
- Detects ID collisions (multiple rows with same id)
- Finds name collisions (same type, level, name with case/punctuation variants)
- Identifies content similarity (near-identical system_text with shared sources)
- Supports dry-run mode for preview

**Output:** HTML (web) or console output with duplicate detection results

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rituals_master` table

**Use case:** Run periodically to identify and clean duplicate ritual entries

---

#### audit_rituals_sources.php

**Purpose:** Audits and normalizes ritual source information in the database.

**Usage:**
```bash
# CLI with dry-run
php tools/repeatable/php/database-tools/audit_rituals_sources.php --dry-run

# CLI with execution
php tools/repeatable/php/database-tools/audit_rituals_sources.php
```

**What it does:**
- Analyzes source field values for Necromancy, Thaumaturgy, and Assamite rituals
- Standardizes book names, edition markers, page formatting
- Ensures array/singleton consistency
- Supports dry-run mode

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rituals_master` table

**Use case:** Normalize source references across all rituals

---

#### export_npcs.php

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

**What it does:**
- Queries all NPCs from database (pc = 0 or player_name = 'NPC')
- Reconstructs complete character JSON from normalized database tables
- Exports to JSON files in `reference/Characters/Added to Database/` (or specified directory)
- Database is source of truth - script reconstructs JSON

**Output:** JSON files (one per character) in output directory

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table and related tables (abilities, backgrounds, disciplines, etc.)

**Use case:** Export database characters to JSON for backup, migration, or external processing

---

#### update_paths_completion.php

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

**What it does:**
- Updates `paths_master.description` and `path_powers.system_text`, `challenge_type`, `challenge_notes`
- Updates all Necromancy and Thaumaturgy paths and powers
- Uses transaction safety and prepared statements
- Supports dry-run mode

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Paths-related tables (`paths_master`, `path_powers`)

**Use case:** Update path descriptions and power details in database

---

#### import_wraith_characters.php

**Purpose:** Imports Wraith characters into the database.

**Usage:**
```bash
# CLI with file parameter
php tools/repeatable/php/database-tools/import_wraith_characters.php /path/to/file.json

# Web interface
# Access via browser: tools/repeatable/php/database-tools/import_wraith_characters.php?file=filename.json
# Import all: tools/repeatable/php/database-tools/import_wraith_characters.php?all=1
```

**What it does:**
- Imports Wraith character JSON files from `reference/Characters/Wraiths/`
- Supports upsert operations (insert if new, update if exists) based on character_name
- All imported characters use user_id = 1 (admin/ST account)

**Input:** JSON files from `reference/Characters/Wraiths/`

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `wraith_characters` table

**Use case:** Bulk import Wraith characters from JSON files

---

#### import_characters.php

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

**What it does:**
- Imports character JSON files from `reference/Characters/Added to Database/`
- Supports upsert operations (insert if new, update if exists) based on character_name
- Imports all character data (traits, disciplines, backgrounds, abilities, etc.)
- Validates and maps abilities using Ability Agent
- All imported characters use user_id = 1 (admin/ST account)

**Input:** JSON files from `reference/Characters/Added to Database/`

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table and related tables
- Ability Agent (for ability validation)

**Use case:** Bulk import characters from JSON files into database

**Note:** Can be imported as a library by other scripts using `IMPORT_CHARACTERS_AS_LIBRARY` constant

---

#### import_locations.php

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

**What it does:**
- Imports location JSON files from `reference/Locations/`
- Supports upsert operations (insert if new, update if exists) based on name
- Imports all location data (type, district, status, description, etc.)

**Input:** JSON files from `reference/Locations/`

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `locations` table

**Use case:** Bulk import locations from JSON files into database

---

#### db_cleanup_0863.php

**Purpose:** Database cleanup script (specific to version 0.8.63).

**Usage:**
```bash
# Preview only (default)
php tools/repeatable/php/database-tools/db_cleanup_0863.php

# Execute cleanup
php tools/repeatable/php/database-tools/db_cleanup_0863.php --execute
```

**What it does:**
- Performs surgical cleanup of canonical NPC data
- Removes duplicate character rows (starting with Kerry)
- Fixes invalid/placeholder sire values
- Creates summary reports

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Various database tables (depends on cleanup operations)

**Use case:** One-time cleanup for version 0.8.63 migration

---

#### remove_optional_abilities.php

**Purpose:** Removes Optional category abilities (or NULL/empty category abilities) from a character.

**Usage:**
```bash
# CLI with character name
php tools/repeatable/remove_optional_abilities.php --character="Dorikhan Caine"

# CLI with character ID
php tools/repeatable/remove_optional_abilities.php --character-id=188

# Web interface
# Access via browser: tools/repeatable/remove_optional_abilities.php?character=Dorikhan%20Caine
# Or: tools/repeatable/remove_optional_abilities.php?character_id=188
```

**What it does:**
- Finds character by name or ID
- Deletes all abilities with category = 'Optional' OR NULL OR empty string
- Useful for cleaning up incorrectly categorized abilities before re-importing

**Output:** Console/browser output showing count of removed abilities

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table
- `character_abilities` table with `ability_category` column

**Use case:** Clean up Optional/NULL category abilities before re-importing with correct categories

---

#### update_abilities_from_json.php

**Purpose:** Updates character abilities from a JSON file, automatically categorizing them using the `abilities` reference table.

**Usage:**
```bash
# CLI with JSON filename and character name
php tools/repeatable/update_abilities_from_json.php --json="Dorikhan Caine2015.json" --character="Dorikhan Caine"

# CLI with JSON filename and character ID
php tools/repeatable/update_abilities_from_json.php --json="Dorikhan Caine2015.json" --character-id=188

# CLI with character name only (auto-finds JSON file)
php tools/repeatable/update_abilities_from_json.php --character="Dorikhan Caine" --character-id=188

# Web interface
# Access via browser: tools/repeatable/update_abilities_from_json.php?json=Dorikhan+Caine2015.json&character_id=188
# Or: tools/repeatable/update_abilities_from_json.php?character=Dorikhan%20Caine&character_id=188
```

**What it does:**
- Loads character abilities from JSON file in `reference/Characters/Added to Database/`
- Deletes all existing abilities for the character
- Parses abilities from JSON (supports string format like "Ability Name 3" and object format)
- **Automatically looks up correct category** from `abilities` reference table (Physical, Social, Mental, or Optional)
- Re-inserts all abilities with correct categories
- Handles specializations and levels

**Features:**
- Auto-detects JSON file if only character name provided
- Always uses `abilities` table as source of truth for categories
- Handles both string and object ability formats from JSON
- Extracts specializations from parentheses in string format

**Output:** Console/browser output showing count of inserted abilities and any errors

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table
- `character_abilities` table with `ability_category` column
- `abilities` reference table (for category lookup)
- JSON files in `reference/Characters/Added to Database/`

**Use case:** Fix incorrectly categorized abilities by re-importing from JSON with proper category lookup

**Note:** This script is the recommended way to fix ability categories - it ensures all abilities are properly categorized according to the `abilities` reference table.

---

#### check_ability_categories.php

**Purpose:** Diagnostic tool to check what ability categories are currently stored for a character.

**Usage:**
```bash
# CLI with character name
php tools/repeatable/check_ability_categories.php --character="Dorikhan Caine"

# CLI with character ID
php tools/repeatable/check_ability_categories.php --character-id=188

# Web interface
# Access via browser: tools/repeatable/check_ability_categories.php?character=Dorikhan%20Caine
# Or: tools/repeatable/check_ability_categories.php?character_id=188
```

**What it does:**
- Finds character by name or ID
- Queries all abilities for the character
- Groups abilities by category
- Displays count and list of abilities per category
- Shows NULL/empty categories as "(NULL/empty)"

**Output:** Console/browser output showing:
- Total ability count
- Abilities grouped by category with counts
- Each ability with level and specialization

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table
- `character_abilities` table with `ability_category` column

**Use case:** Diagnose ability category issues before fixing them with `update_abilities_from_json.php`

---

### Character Data Tools

Tools for managing character data quality, editing, and export.

#### export_character.php

**Location:** `tools/repeatable/character-data/export_character.php`

**Purpose:** Exports any character from the database to JSON template format.

**Usage:**
```bash
# Export by character ID
php tools/repeatable/character-data/export_character.php 151

# Export by character name (use quotes if name contains spaces)
php tools/repeatable/character-data/export_character.php "Julien Roche"
php tools/repeatable/character-data/export_character.php "Marisol \"Roadrunner\" Vega"
```

**What it does:**
- Accepts character ID (numeric) or character name (string) as argument
- Queries database for complete character data including:
  - Basic info (name, clan, generation, etc.)
  - Traits (Physical, Social, Mental - positive and negative)
  - Abilities with categories and specializations
  - Disciplines with levels
  - Backgrounds with levels
  - Morality/path information
  - Merits & flaws
  - Status information
  - Coteries and relationships
- Exports to JSON file in `reference/Characters/Added to Database/`
- Filename format: `npc__<safe_name>__<id>.json`

**Output:** JSON file in `reference/Characters/Added to Database/` directory

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `characters` table and related tables (character_traits, character_abilities, character_disciplines, character_backgrounds, character_morality, character_merits_flaws, character_status, character_coteries, character_relationships)

**Use case:** Export individual characters from database to JSON for backup, migration, or external processing

---

#### quick-edit.php

**Location:** `tools/repeatable/character-data/quick-edit.php`

**Purpose:** Fast interface for editing common character fields and uploading images.

**Usage:**
- Access via browser: `tools/repeatable/character-data/quick-edit.php`
- Search for characters with missing data: `tools/repeatable/character-data/quick-edit.php?search_missing=1`

**What it does:**
- Provides web interface for editing character fields (name, concept, nature, demeanor, biography, appearance)
- Allows image upload for characters
- Shows only missing fields by default
- Displays character name in missing fields notification
- Supports searching for characters with missing data

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Admin authentication required
- `uploads/characters/` directory for image storage

**Use case:** Quickly fill in missing character data fields and upload character images

---

#### index.php

**Location:** `tools/repeatable/character-data/index.php`

**Purpose:** Character data quality blocker identification and JSON file search.

**Usage:**
- Access via browser: `tools/repeatable/character-data/index.php`

**What it does:**
- Identifies missing/empty fields that prevent accurate character summaries
- Searches JSON files in `reference/Characters/Added to Database/` for missing data
- Updates database with data found in JSON files
- Read-only tool for identification, separate update process for modifications

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Admin authentication required
- JSON files in `reference/Characters/Added to Database/`

**Use case:** Identify characters with missing data and find data in JSON files to backfill database

---

#### sync_abilities_from_json.php

**Location:** `tools/repeatable/character-data/sync_abilities_from_json.php`

**Purpose:** Scans character JSON files for characters missing abilities in the DB and inserts those abilities. Supports multiple JSON formats (string e.g. `"Ability 3"`, object with `name`/`ability_name`, `category`/`ability_category`, `level`, `specialization`; optional `specializations` map).

**Usage:**
```bash
# Dry run (preview, default)
php tools/repeatable/character-data/sync_abilities_from_json.php --dry-run

# Execute
php tools/repeatable/character-data/sync_abilities_from_json.php --execute
```

- Web: `sync_abilities_from_json.php?dry_run=1` (preview), or POST with `execute=1` (run). Button on `character-data/index.php`.

**What it does:**
- Finds characters with 0 abilities in DB
- Scans `reference/Characters/Added to Database/*.json`, matches by `npc__*__id` filename, `id`, or `character_name`
- Parses abilities from JSON (string/object formats), resolves category from `abilities` table when present
- Inserts into `character_abilities`; skips characters that already have abilities
- Writes log to `tools/repeatable/abilities-sync-YYYYMMDD-HHMMSS.log`

**Dependencies:** `includes/connect.php`, admin when run via web, `abilities` / `character_abilities` tables

**Use case:** Backfill missing abilities from JSON into the database for characters that have none

---

### Data Tools

Tools for generating reports, summaries, and data analysis from the database.

#### check_books_when_ready.php

**Purpose:** Checks the rulebooks database and generates a detailed report.

**Usage:**
```bash
php tools/repeatable/php/data-tools/check_books_when_ready.php
```

**What it does:**
- Queries rulebooks table for all books
- Checks which books have extracted content
- Generates statistics (total books, books with/without content, extracted pages)
- Creates detailed report file

**Output:**
- Creates `books_database_report.txt` in project root
- Displays summary in browser/CLI

**Report Contents:**
- Total books count
- Books with/without extracted content
- Extracted pages statistics
- List of all books with details (ID, title, category, system, pages)

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rulebooks` table
- `rulebook_pages` table

**Use case:** Verify rulebook data integrity and check extraction status

---

#### generate_project_summary.php

**Purpose:** Generates a comprehensive HTML project summary document.

**Usage:**
```bash
php tools/repeatable/php/data-tools/generate_project_summary.php
```

**What it does:**
- Queries database for statistics (characters, locations, items, clans)
- Generates beautiful Gothic-themed HTML document
- Includes game content richness metrics
- Lists technical achievements (agents, systems)
- Shows current status and roadmap
- Includes historical context

**Output:**
- Creates `PROJECT_SUMMARY.html` in project root
- HTML document showcasing:
  - Game content statistics
  - Character examples (Cordelia Fairchild)
  - Clan distribution
  - Agent system descriptions
  - Status tracking (completed, in progress, planned)

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Various database tables (characters, locations, items)
- Character JSON files (optional, for detailed examples)

**Target Audience:** Storytellers/GMs familiar with Laws of the Night Revised

**Use case:** Create up-to-date project overview for documentation or presentation

---

### API Tools (PHP)

Tools that call external APIs.

#### cloudflare_dns_proxy_status.php

**Purpose:** List Cloudflare zones and DNS records and show whether each record is **proxied** (orange cloud) or DNS-only. Use to confirm the site is behind Cloudflare so you can host with a dynamic IP.

**Usage:**
```bash
# All zones
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php

# One zone only
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php --zone=vbn-game.com

# Help
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php --help
```

**Web:** Open in browser; optional `?zone=vbn-game.com` to filter.

**What it does:**
- Reads `CLOUDFLARE_API_TOKEN` from `.env` (project root) or environment
- Calls Cloudflare API: list zones, then list DNS records per zone
- Outputs each record's type, name, content, and **proxied** (yes = orange cloud)

**Output:** CLI: text table. Web: HTML table. No DB or file writes.

**Dependencies:** PHP 7.4+, `allow_url_fopen`. API Token with Zone:Read, DNS:Read.

**Use case:** Verify proxy status for dynamic-IP hosting; when proxied, update A record when your IP changes.

---

## Python Tools

### JSON Tools

Tools for processing and parsing JSON files.

#### cleanup_json.py

**Purpose:** Cleans up generated JSON files by fixing common issues.

**Usage:**
```bash
python tools/repeatable/python/json-tools/cleanup_json.py <input_file> [output_file]
```

**What it does:**
- Removes trailing level words (Basic, Intermediate, Advanced, 1st, 2nd, lst)
- Removes duplicates
- Fixes level_number issues (when level_number matches page number)
- Normalizes discipline names
- Cleans up ritual entries

**Features:**
- Skips section labels and invalid entries
- Preserves valid data while cleaning artifacts
- Handles both disciplines and rituals

**Dependencies:** Python 3.7+ (json, re, pathlib)

**Use case:** Clean JSON files after parsing markdown or OCR processing

---

#### parse_disciplines.py

**Purpose:** Parses Mind's Eye Theatre Discipline Deck markdown file and converts to JSON.

**Usage:**
```bash
python tools/repeatable/python/json-tools/parse_disciplines.py
```

**What it does:**
- Reads Discipline Deck markdown file
- Parses discipline entries and rituals
- Normalizes discipline names (handles OCR errors)
- Extracts level information
- Converts to JSON format

**Output:** JSON files for disciplines and rituals

**Dependencies:** Python 3.7+ (re, json, pathlib, typing)

**Use case:** Convert Discipline Deck markdown to structured JSON format

---

#### parse_disciplines_v2.py

**Purpose:** Improved parser for Mind's Eye Theatre Discipline Deck markdown file.

**Usage:**
```bash
python tools/repeatable/python/json-tools/parse_disciplines_v2.py
```

**What it does:**
- Enhanced version of parse_disciplines.py
- Better error handling
- Improved normalization
- Enhanced skip label detection
- More robust parsing logic

**Features:**
- Better handling of OCR errors
- Improved section label detection
- More accurate level extraction

**Dependencies:** Python 3.7+ (re, json, pathlib, typing)

**Use case:** Use this version instead of parse_disciplines.py for better results

---

### Text Tools

Tools for text processing and cleanup.

#### fix_spaces.py

**Purpose:** Fixes spacing issues in text files (split words, OCR errors).

**Usage:**
```bash
python tools/repeatable/python/text-tools/fix_spaces.py
```

**What it does:**
- Fixes common OCR errors (split words like "f or" → "for")
- Splits concatenated words using wordninja library
- Preserves game-specific terminology (vampire, discipline, etc.)
- Processes files in configured input folder
- Outputs to configured output folder

**Configuration:** Edit INPUT_FOLDER and OUTPUT_FOLDER variables in script

**Features:**
- NO_SPLIT_WORDS dictionary prevents splitting game terminology
- Pattern-based OCR error fixes
- Word splitting for concatenated words

**Dependencies:**
- Python 3.7+
- `wordninja` package: `pip install wordninja`

**Use case:** Fix OCR errors and split concatenated words in markdown files

---

#### remove_old_url.py

**Purpose:** Removes references to `https://vbn.talkingheads.video/` from project files.

**Usage:**
```bash
python tools/repeatable/python/text-tools/remove_old_url.py
```

**What it does:**
- Scans project files for URL references
- Processes PHP, Markdown, JSON, TXT, XML files
- Removes all occurrences of `https://vbn.talkingheads.video/`
- Skips .git, node_modules, venv directories
- Safe file processing with error handling

**Output:** Updates files in place, displays list of updated files

**Dependencies:** Python 3.7+ (os, re, pathlib)

**Use case:** Remove old URL references during migration or cleanup

---

### PDF Tools

Tools for PDF extraction and processing.

#### extract_pdf_page.py

**Purpose:** Extracts a single page from a PDF file and outputs as text.

**Usage:**
```bash
python tools/repeatable/python/pdf-tools/extract_pdf_page.py <pdf_file> <page_number>
```

**Example:**
```bash
python tools/repeatable/python/pdf-tools/extract_pdf_page.py book.pdf 42
```

**What it does:**
- Opens PDF file
- Extracts text from specified page number (1-indexed)
- Supports PyPDF2 and pdfplumber libraries
- Better formatting with pdfplumber (preferred)
- Validates page numbers
- Error handling for missing pages/files

**Output:** Prints extracted text to console

**Dependencies:**
- Python 3.7+
- PyPDF2 OR pdfplumber (at least one required)
  - `pip install PyPDF2` OR
  - `pip install pdfplumber` (recommended for better formatting)

**Use case:** Extract specific page from PDF for reference or processing

#### pdf_to_rag_json.py

**Purpose:** Converts a full PDF to RAG JSON (one chunk per page). Output matches the schema used by `agents/laws_agent/Books/*_rag.json`. Run locally so the AI never has to load the full PDF into context.

**Usage:**
```bash
python tools/repeatable/python/pdf-tools/pdf_to_rag_json.py <pdf_file> <output_json> [--source "Book Title"] [--book-code BOOK-CODE] [--content-type general]
```

**Example:**
```bash
python tools/repeatable/python/pdf-tools/pdf_to_rag_json.py "reference/Books/MET - VTM - Laws of Elysium (5012).pdf" agents/laws_agent/Books/laws_of_elysium_rag.json --source "MET - Laws of Elysium" --book-code MET-ELYSIUM
```

**What it does:**
- Extracts text per page (pdfplumber or PyPDF2)
- Writes one RAG object per page (id, page, chunk_index, total_chunks, content, content_type, metadata)
- Optional `--source`, `--book-code`, `--content-type`

**Use case:** Convert large PDFs to RAG JSON without pasting the PDF into chat (avoids context limits).

---

### Text Cleanup Tools

Tools for cleaning up OCR-extracted text (fixing split words, spelling errors, and other OCR artifacts).

**Note:** These tools clean up text that has already been extracted via OCR. To actually perform OCR on image-based PDFs, use the OCR tools below.

#### fix_ocr_spelling.py

**Purpose:** Fixes OCR spelling errors in markdown files while preserving game terminology.

**Usage:**
```bash
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py <input_dir> [--output-dir=<dir>] [--dry-run]
```

**What it does:**
- Uses pattern-based OCR fixes first (f or → for, th at → that, etc.)
- Then uses spell checker for obvious errors
- Preserves game-specific terminology (clans, disciplines, game terms)
- Processes markdown files in directory
- Supports dry-run mode

**Features:**
- Game terminology dictionary prevents incorrect corrections
- Pattern-based fixes before spell checking
- Configurable options

**Dependencies:**
- Python 3.7+ (argparse, re, os, pathlib, typing, collections)
- `spellchecker` package (optional): `pip install pyspellchecker`

**Use case:** Fix OCR spelling errors in markdown files while preserving game terminology

---

#### clean_pdf_text.py

**Purpose:** Cleans common PDF extraction artifacts from text files.

**Usage:**
```bash
python tools/repeatable/python/text-cleanup-tools/clean_pdf_text.py <input_file> [output_file]
```

**What it does:**
- Removes HTML-style image placeholders (`<!-- image -->`)
- Removes header/footer noise (page numbers, order numbers, addresses)
- Removes isolated single characters
- Fixes hyphenation issues
- Handles encoding errors
- Fixes broken paragraphs

**Features:**
- Comprehensive artifact removal
- Preserves actual content
- Handles various PDF extraction issues

**Dependencies:** Python 3.7+ (re, os, pathlib, typing)

**Use case:** Clean text extracted from PDFs before further processing

---

#### ocr_process_folder.py

**Purpose:** Processes a folder of OCR files.

**Usage:**
```bash
python tools/repeatable/python/text-cleanup-tools/ocr_process_folder.py
```

**What it does:**
- Processes multiple OCR files in a folder
- Applies OCR cleanup and processing
- Batch processing for efficiency

**Dependencies:** Python 3.7+

**Use case:** Batch process OCR files from a folder

---

#### clean_ocr_markdown.py

**Purpose:** Cleans OCR markdown files.

**Usage:**
```bash
python tools/repeatable/python/text-cleanup-tools/clean_ocr_markdown.py [--input-dir=<dir>] [--output-dir=<dir>]
```

**What it does:**
- Cleans markdown files after OCR processing
- Removes OCR artifacts specific to markdown
- Formats markdown properly

**Dependencies:**
- Python 3.7+ (argparse)

**Use case:** Clean markdown files after OCR processing

---

#### ocr_process_full_file.py

**Purpose:** Processes full OCR files.

**Usage:**
```bash
python tools/repeatable/python/text-cleanup-tools/ocr_process_full_file.py
```

**What it does:**
- Processes complete OCR-extracted text files to fix split words
- Handles full document processing

**Dependencies:** Python 3.7+

**Use case:** Process complete OCR-extracted text files to fix split words

---

### OCR Tools

Tools for performing OCR (Optical Character Recognition) on image-based PDFs using Tesseract.

**Note:** These tools actually perform OCR. For cleaning up OCR-extracted text, use the Text Cleanup Tools above.

#### ocr_pdf.py

**Purpose:** Extracts text from image-based PDFs using Tesseract OCR.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/ocr_pdf.py <pdf_file> [output_file] [--lang=LANG] [--dpi=DPI]
```

**What it does:**
1. Converts PDF pages to images (using pdf2image)
2. Runs Tesseract OCR on each page image
3. Combines all extracted text with page markers
4. Saves to output file or prints to stdout

**Features:**
- Supports custom language codes
- Configurable DPI for image conversion
- Page markers in output (=== PAGE N ===)
- Automatic cleanup of temporary files

**Dependencies:**
- Python 3.7+
- Tesseract OCR (installed system-wide)
- `pdf2image` package: `pip install pdf2image`
- On Windows: poppler (for pdf2image)

**Use case:** Extract text from scanned PDFs or image-based PDFs that don't have selectable text

---

### API Tools

Tools for API interaction and data fetching.

#### download_envato_images.py

**Purpose:** Downloads images from Envato Photos for items in the database.

**Usage:**
```bash
python tools/repeatable/python/api-tools/download_envato_images.py
```

**What it does:**
- Connects to MySQL database
- Queries items that need images
- Fetches images from Envato Photos API
- Downloads and processes images
- Tracks progress in tracking file
- Updates database with image paths

**Features:**
- Automatic image download
- Progress tracking
- Error handling
- Image processing and optimization

**Dependencies:**
- Python 3.7+
- `requests` package: `pip install requests`
- `mysql-connector-python` package: `pip install mysql-connector-python`
- `PIL` (Pillow) package: `pip install Pillow`
- `.env` file with:
  - `ENVATO_API_KEY`
  - Database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)

**Configuration:** Requires `.env` file with API keys and database credentials

**Use case:** Bulk download item images from Envato Photos API

---

#### fetch_envato_json.py

**Purpose:** Fetches Envato catalog item JSON and saves to file (for debugging API responses).

**Usage:**
```bash
python tools/repeatable/python/api-tools/fetch_envato_json.py <item_id_or_name>
```

**Example:**
```bash
python tools/repeatable/python/api-tools/fetch_envato_json.py "Sword"
python tools/repeatable/python/api-tools/fetch_envato_json.py 12345
```

**What it does:**
- Searches Envato catalog by item ID or name
- Fetches item JSON data from API
- Saves JSON to file in `Envanto/` directory
- Helps debug API response structure

**Output:** JSON file in `Envanto/` directory

**Dependencies:**
- Python 3.7+
- `requests` package: `pip install requests`
- `.env` file with `ENVATO_API_KEY`

**Use case:** Debug Envato API responses to find unwatermarked download URLs

---

### Data Extraction

Tools for extracting data from various sources.

#### extract_locations_from_biographies.py

**Purpose:** Extracts specific, PC-visitable locations from character biographies with strict filtering.

**Usage:**
```bash
python tools/repeatable/python/data-extraction/extract_locations_from_biographies.py
```

**What it does:**
- Reads character biography files
- Extracts location mentions
- Applies strict filtering rules (zero false positives)
- Excludes natural terrain (forest, mountain, etc.)
- Excludes political entities (Camarilla, Sabbat, etc.)
- Excludes vague macros (the city, the area, etc.)
- Excludes real-world locations
- Filters out non-places (dreams, memory, etc.)

**Output:** List of valid, PC-visitable locations

**Features:**
- Strict filtering ensures only valid locations
- Zero false positives approach
- Comprehensive exclusion lists

**Dependencies:** Python 3.7+ (json, os, re, datetime, pathlib, typing, collections)

**Use case:** Extract valid locations from character biographies for database import

---

#### extract_history.py

**Purpose:** Extracts history/biography fields from .gv3 XML files.

**Usage:**
```bash
python tools/repeatable/python/data-extraction/extract_history.py
```

**What it does:**
- Reads .gv3 XML files
- Extracts text content from elements
- Finds history/biography fields (notes, history, biography, bio, background, etc.)
- Handles CDATA sections
- Cleans XML content
- Outputs to JSON or text format

**Features:**
- Handles various field names (case-insensitive)
- Processes CDATA sections properly
- Cleans encoding issues

**Dependencies:** Python 3.7+ (xml.etree.ElementTree, json, os, re, pathlib)

**Use case:** Extract character history from Grapevine (.gv3) XML files

---

### Analysis Tools

Tools for data analysis and reporting.

#### analyze_game_design.py

**Purpose:** Analyzes game design data and generates reports.

**Usage:**
```bash
python tools/repeatable/python/analysis-tools/analyze_game_design.py
```

**What it does:**
- Analyzes game design data
- Generates statistical reports
- Provides insights and analysis

**Dependencies:** Python 3.7+

**Use case:** Analyze game design patterns and generate reports

---

### Books Tools

Tools for scanning `reference/Books` vs `reference/Books_summaries` and OCR status.

#### scan_books_ocr_report.py

**Purpose:** Find PDFs in `reference/Books` without a matching summary in `reference/Books_summaries`, run `extract_full_pdf_text` on each, and record whether they need OCR (image-based). Writes `reference/Books/books_ocr.md`.

**Usage:**
```bash
python tools/repeatable/python/books_tools/scan_books_ocr_report.py [--books-dir DIR] [--summaries-dir DIR] [--output PATH]
```

**What it does:**
- Scans Books for PDFs, Books_summaries for `.md`
- Matches by normalized names (heuristic)
- For PDFs without a match: runs `extract_full_pdf_text.py`; "needs OCR" if very little text extracted
- Writes `reference/Books/books_ocr.md` with findings

**Output:** `reference/Books/books_ocr.md` (or `--output` path)

**Dependencies:** Python 3.7+, `extract_full_pdf_text.py` (pdfplumber or PyPDF2)

**Use case:** Identify books missing summaries and which of those require OCR before summarization

---

## Quick Reference

### Common PHP Tools

```bash
# MCP Setup
php tools/repeatable/php/mcp-tools/create_mcp_directories.php
php tools/repeatable/php/mcp-tools/verify_mcp_structure.php

# Database Operations
php tools/repeatable/php/database-tools/import_characters.php --all
php tools/repeatable/php/database-tools/export_npcs.php
php tools/repeatable/php/database-tools/audit_rituals_duplicates.php --dry-run

# Reporting
php tools/repeatable/php/data-tools/generate_project_summary.php
php tools/repeatable/php/data-tools/check_books_when_ready.php
```

### Common Python Tools

```bash
# JSON Processing
python tools/repeatable/python/json-tools/cleanup_json.py input.json output.json
python tools/repeatable/python/json-tools/parse_disciplines_v2.py

# Text Processing
python tools/repeatable/python/text-tools/fix_spaces.py
python tools/repeatable/python/text-tools/remove_old_url.py

# PDF Processing
python tools/repeatable/python/pdf-tools/extract_pdf_page.py book.pdf 42

# OCR Processing
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py input_dir/ --output-dir=output_dir/
python tools/repeatable/python/text-cleanup-tools/clean_pdf_text.py input.txt output.txt

# Books scan (missing summaries + OCR status)
python tools/repeatable/python/books_tools/scan_books_ocr_report.py
```

---

## Dependencies Summary

### PHP Tools
- PHP 7.4+
- Database connection (via `includes/connect.php`)
- MySQL/MariaDB database

### Python Tools
- Python 3.7+
- Standard library modules (json, re, os, sys, pathlib, etc.)
- See individual tool documentation for specific package requirements

### Common Python Packages
- `wordninja` - For text-tools/fix_spaces.py
- `pyspellchecker` - For text-cleanup-tools/fix_ocr_spelling.py (optional)
- `pdf2image` - For ocr-tools/ocr_pdf.py (required for OCR)
- `PyPDF2` or `pdfplumber` - For pdf-tools/extract_pdf_page.py
- `requests` - For api-tools
- `mysql-connector-python` - For api-tools/download_envato_images.py
- `Pillow` (PIL) - For api-tools/download_envato_images.py

---

## Notes

- All PHP tools use `__DIR__` for relative paths, so they work correctly when moved
- All database tools require proper database connection configuration
- Python tools may require additional packages - check individual tool documentation
- Some tools support both CLI and web interfaces
- Use `--dry-run` flag when available to preview changes before executing
- All tools preserve original files or create backups when modifying data
