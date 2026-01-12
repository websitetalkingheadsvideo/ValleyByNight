# OCR Error Fix Plan

## Analysis Summary
After cleaning 43 markdown files, identified remaining OCR error patterns requiring additional fixes.

## Remaining Error Categories

### 1. Title Case Issues (HIGH PRIORITY - Easy to fix)
**Pattern**: Section headers starting with lowercase words
- `^the Embrace` → `The Embrace`
- `^the Sabbat` → `The Sabbat`
- `^all the rest` → `All the Rest`
- `^who hunts the hunters` → `Who Hunts the Hunters`
- `^the First tradition` → `The First tradition`
- `^the Fourth tradition` → `The Fourth tradition`
- `^the Fifth tradition` → `The Fifth tradition`

**Fix**: Line-start pattern replacements for common headers

### 2. Severely Corrupted Titles (HIGH PRIORITY - Specific patterns)
**Pattern**: Titles with multiple character splits
- `^i t n … n hese ights` → `In These Nights`
- `u u F n : C p nited nderthe inal ights rossover owers` → `Crossover Powers` (likely in context)

**Fix**: Specific regex patterns for known corrupted titles

### 3. Split Words in Names (MEDIUM PRIORITY)
**Pattern**: Author/character names with inserted spaces
- `Rand i Jo Bruner` → `Randi Jo Bruner`
- `J as on Carl` → `Jason Carl`
- `J as on Fe lds te in` → `Jason Feldstein`
- `Fe lds te in` → `Feldstein`
- `Tim Harris` (already correct, but check variations)
- `Peter Wood worth` → `Peter Woodworth` (or keep as two words if correct)

**Fix**: Dictionary of known names with split patterns

### 4. Common Phrase Splits (MEDIUM PRIORITY)
**Pattern**: Common phrases with OCR spacing errors
- `m at will` → `at will` (context-dependent)
- `a show to` → `as well as how to` (context-dependent - be careful!)
- `ma it rC ' d` → `maitre d'`

**Fix**: Dictionary of common phrases with context checks

### 5. Additional Split Patterns (LOW PRIORITY)
**Pattern**: Various remaining splits
- Check for other split patterns in content
- Many may require manual review

## Implementation Strategy

### Phase 1: High-Priority Fixes (Pattern-Based)
1. **Title Case Fixes** - Add to OCR_REPLACEMENTS list
   - Line-start patterns for section headers
   - Simple and reliable

2. **Corrupted Titles** - Add specific patterns
   - Known corrupted titles
   - Test carefully

### Phase 2: Medium-Priority Fixes
3. **Name Corrections** - Create name dictionary
   - Add split name patterns
   - Test on files with credits/author sections

4. **Common Phrases** - Add phrase dictionary
   - Be conservative - many need context
   - Test carefully

### Phase 3: Testing & Refinement
5. **Test on Sample Files**
   - Test on LotNR.md first
   - Test on Anarch Guide.md (has many name errors)
   - Review results before batch processing

6. **Batch Process All Files**
   - Run on all 43 files
   - Review sample outputs
   - Create backup before final run

## Specific Patterns to Add

```python
# Title case fixes (line-start patterns)
(r'^the Embrace ', 'The Embrace '),
(r'^the Sabbat ', 'The Sabbat '),
(r'^all the rest ', 'All the Rest '),
(r'^who hunts the hunters ', 'Who Hunts the Hunters '),
(r'^the First tradition', 'The First tradition'),
(r'^the Fourth tradition', 'The Fourth tradition'),
(r'^the Fifth tradition', 'The Fifth tradition'),

# Corrupted titles
(r'^i t n … n hese ights ', 'In These Nights '),
(r'u u F n : C p nited nderthe inal ights rossover owers', 'Crossover Powers'),

# Name fixes
(r'\bRand i Jo Bruner\b', 'Randi Jo Bruner'),
(r'\bJ as on Carl\b', 'Jason Carl'),
(r'\bJ as on Fe lds te in\b', 'Jason Feldstein'),
(r'\bFe lds te in\b', 'Feldstein'),

# Common phrases (be careful with these)
(r'\bma it rC \' d\b', 'maitre d\''),
```

## Notes

- **Be Conservative**: Many splits require context to fix correctly
- **Test First**: Always test on single file before batch processing
- **Manual Review**: Some errors may require manual review (especially names and context-dependent phrases)
- **Preserve Game Terms**: Continue protecting game terminology
- **Line-Start Patterns**: Use `^` anchor for line-start patterns
- **Word Boundaries**: Use `\b` for word-boundary patterns

## Next Steps

1. Add Phase 1 fixes to script (title case, corrupted titles)
2. Test on LotNR.md
3. Add Phase 2 fixes (names, phrases)
4. Test on Anarch Guide.md
5. Review results
6. Batch process all files if satisfactory
