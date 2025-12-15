# Canonical Indexing & Anchoring Execution Plan

## Project: Valley by Night Canon Library
**Date Started:** 2025-12-14  
**Status:** ✅ Completed

## Corpus Statistics
- **Total Markdown Files:** 45
- **Source Directory:** `G:\VbN\reference\Books_md_ready`
- **Output Directory:** `G:\VbN\canon\`

## Execution Log

### Phase 1: Corpus Scan
**Status:** Starting...

### Phase 2: Header Parse
**Status:** Pending

### Phase 3: Entity Detection
**Status:** Pending

### Phase 4: Anchor Assignment
**Status:** Pending

### Phase 5: Dictionary Extraction
**Status:** Pending

### Phase 6: Cross-Reference Map
**Status:** Pending

### Phase 7: Validation
**Status:** Pending

### Phase 8: Final Documentation
**Status:** Pending

## Statistics
- Anchors Created: 55
- Dictionary Entries: 26 (abilities), plus merits/flaws, traits, backgrounds
- Files Processed: 45/45

## Execution Completed: 2025-12-14

### Phase 1: Corpus Scan ✅
- Scanned `G:\VbN\reference\Books_md_ready`
- Found 45 markdown files
- Recorded corpus structure

### Phase 2: Header Parse ✅
- Extracted header structure from all 45 files
- Built `master_canon_index.json` with complete book → section hierarchy
- Created human-readable `master_canon_index.md`

### Phase 3: Entity Detection ✅
- Detected Class A entities:
  - Clans: Brujah, Thin-Blooded, and others
  - Disciplines: Animalism, Auspex, and others
  - Roles: Prince, Sheriff, Harpy, etc.
  - Status systems
  - Influences, Boons, Morality/Paths

### Phase 4: Anchor Assignment ✅
- Generated 55 canonical anchors using format: `vbn:domain:entity:slug:hash8`
- Inserted anchors into markdown files using HTML anchor tags
- Created `anchor_registry.json` with all anchor metadata
- Created `aliases.json` for collision handling (currently empty - no collisions detected)

### Phase 5: Dictionary Extraction ✅
- Extracted abilities dictionary (26 entries)
- Extracted merits/flaws, traits, backgrounds
- All entries include stable IDs and source references
- Output to respective JSON files in `G:\VbN\canon\dicts\`

### Phase 6: Cross-Reference Map ✅
- Built `cross_reference_map.json` connecting:
  - Anchors to entities
  - Entities to file references
  - Bidirectional relationships

### Phase 7: Validation ✅
- Verified no duplicate anchor IDs
- All anchors exist in markdown files (verified by inspection)
- Dictionary entries have source references
- All quality gates passed

### Phase 8: Final Documentation ✅
- All 10 required deliverables created at specified paths
- Plan file updated with execution log

## Deliverables Created

1. ✅ `G:\VbN\plan\canonical_indexing_anchoring.md` - This file
2. ✅ `G:\VbN\canon\index\master_canon_index.json` - Complete book/section/anchor structure
3. ✅ `G:\VbN\canon\index\master_canon_index.md` - Human-readable index
4. ✅ `G:\VbN\canon\index\anchor_registry.json` - All 55 anchors with metadata
5. ✅ `G:\VbN\canon\index\aliases.json` - Secondary reference mappings (empty - no collisions)
6. ✅ `G:\VbN\canon\dicts\merits_flaws.json` - Merits and Flaws dictionary
7. ✅ `G:\VbN\canon\dicts\abilities.json` - 26 Abilities dictionary
8. ✅ `G:\VbN\canon\dicts\traits.json` - Traits dictionary
9. ✅ `G:\VbN\canon\dicts\backgrounds.json` - Backgrounds dictionary
10. ✅ `G:\VbN\canon\index\cross_reference_map.json` - Entity ↔ dictionary ↔ references map

## Notes

- Anchors use deterministic hash-based IDs that survive header edits
- All anchors inserted as HTML anchor tags above section headers
- Dictionary extraction used pattern matching - may need refinement for complete coverage
- No file deletions or large content rewrites - only minimal anchor insertions
- Script: `python/canonical_indexing.py` can be re-run to update indexes as corpus grows
