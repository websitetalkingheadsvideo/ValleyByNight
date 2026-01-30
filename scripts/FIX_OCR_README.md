# OCR Spelling Correction Script

This script fixes OCR spelling errors in markdown files while preserving game terminology.

**Location:** `tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py` (run from repo root).

## Features

1. **Pattern-based OCR fixes** (most reliable)
   - Handles common OCR errors like split words: "t s he" → "the"
   - Fixes specific errors found in text: "aBBat" → "Sabbat", "took threw" → "took their"
   - These fixes are applied first and are very reliable

2. **Spell checker corrections** (use with caution)
   - Finds obvious spelling errors
   - Very conservative - only fixes words 5+ chars that are clearly misspelled
   - Preserves game terminology

## Usage

```bash
# Preview changes (dry run)
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py reference/Books_md_ready_fixed_cleaned/LotNR.md --dry-run

# Apply corrections
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py reference/Books_md_ready_fixed_cleaned/LotNR.md

# Process entire directory
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py reference/Books_md_ready_fixed_cleaned/ --dry-run

# Interactive mode (ask before applying)
python tools/repeatable/python/text-cleanup-tools/fix_ocr_spelling.py reference/Books_md_ready_fixed_cleaned/LotNR.md --interactive
```

## Pattern-based Fixes

The script applies these pattern-based fixes first (most reliable):

- Split words: "t s he" → "the", "w ith" → "with"
- Specific OCR errors: "aBBat" → "Sabbat"
- Common errors: "took threw" → "took their"
- And more...

## Game Terminology Protected

The script preserves game terms like:
- Clans: Brujah, Ventrue, Camarilla, Sabbat, etc.
- Disciplines, game terms, proper nouns
- Custom dictionary support

## Notes

- **Pattern-based fixes are reliable** - these handle obvious OCR errors
- **Spell checker may find false positives** - review corrections carefully
- Always use `--dry-run` first to preview changes
- The script creates backups automatically (you can disable with `--no-backup` if needed)

## Installation

```bash
pip install pyspellchecker
```

The script will work without pyspellchecker but will only apply pattern-based fixes.
