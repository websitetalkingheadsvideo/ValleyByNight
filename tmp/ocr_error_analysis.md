# OCR Error Analysis & Fix Plan

## Analysis Date
2025-01-30

## Files Analyzed
- `reference/Books_md_ready_fixed_cleaned/LotNR.md`
- `reference/Books_md_ready_fixed_cleaned/Anarch Guide.md`

## Remaining Error Categories

### 1. Title Case Issues (High Priority)
**Pattern**: Lowercase words at start of sections that should be capitalized
- Line 241: "the Embrace" → "The Embrace"
- Line 278: "the Sabbat" → "The Sabbat"  
- Line 287: "all the rest" → "All the Rest"
- Line 317: "who hunts the hunters" → "Who Hunts the Hunters"

**Fix Strategy**: Pattern-based replacement for common section headers

### 2. Severely Corrupted Titles (High Priority)
**Pattern**: Titles with multiple split characters
- Line 325: "i t n … n hese ights" → "In These Nights"
- Line 5168: "u u F n : C p nited nderthe inal ights rossover owers" → "Crossover Powers"

**Fix Strategy**: Specific pattern replacements for known corrupted titles

### 3. Split Words in Names (Medium Priority)
**Pattern**: Names with spaces inserted
- "Rand i Jo Bruner" → "Randi Jo Bruner"
- "J as on Carl" → "Jason Carl"
- "Fe lds te in" → "Feldstein"

**Fix Strategy**: Dictionary of known names with split patterns

### 4. Split Words in Common Phrases (Medium Priority)
**Pattern**: Common phrases with spaces
- "ma it rC ' d" → "maitre d'"
- "m at will" → "at will"
- "a show to" → "as well as how to"

**Fix Strategy**: Dictionary of common phrases

### 5. Split Words at Line Starts (Low Priority)
**Pattern**: Short words split at line starts
- Various 2-3 character splits that don't form common words

**Fix Strategy**: Conservative pattern matching for short splits

## Fix Plan

### Phase 1: Title Fixes (Most Critical)
1. Fix corrupted titles with specific patterns
2. Fix title case for section headers

### Phase 2: Name Corrections
1. Build dictionary of author/character names
2. Fix split names

### Phase 3: Common Phrase Fixes
1. Build dictionary of common phrases
2. Fix split phrases

### Phase 4: Final Pass
1. Review remaining issues
2. Manual review for context-dependent fixes

## Implementation Notes

- Use pattern-based fixes first (most reliable)
- Add to existing `fix_ocr_spelling.py` script
- Test on single file first before batch processing
- Keep game terminology protection
