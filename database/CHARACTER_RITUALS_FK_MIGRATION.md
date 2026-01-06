# Character Rituals FK Migration Documentation

**Task:** TM-04  
**Date:** 2025-01-30  
**Status:** Complete

## Overview

This migration adds a foreign key relationship from `character_rituals` to `rituals_master` by introducing a `ritual_id` column and backfilling it via matching on `(type, level, name)`. Legacy fields are preserved as a fallback mechanism.

## Schema Changes

### Added Column

- **Table:** `character_rituals`
- **Column:** `ritual_id INT NULL`
- **Purpose:** Foreign key reference to `rituals_master.id`
- **Index:** `idx_ritual_id` created for performance
- **Foreign Key:** `fk_character_rituals_ritual_id` → `rituals_master.id`
  - `ON DELETE SET NULL`
  - `ON UPDATE CASCADE`

### Preserved Legacy Fields

The following fields remain unchanged and continue to function as fallback:
- `ritual_name` (VARCHAR)
- `ritual_type` (VARCHAR)
- `level` (INT)

## Matching Rules

### Primary Matching Logic

The backfill process matches `character_rituals` rows to `rituals_master` entries using:

1. **Exact match on composite key:**
   - `character_rituals.ritual_type` = `rituals_master.type`
   - `character_rituals.level` = `rituals_master.level`
   - `character_rituals.ritual_name` = `rituals_master.name`

2. **Normalization applied:**
   - **Case-insensitive comparison:** Both sides converted to lowercase
   - **Whitespace trimming:** Leading/trailing whitespace removed
   - **Format:** `LOWER(TRIM(field))` on both sides

3. **SQL Matching Pattern:**
   ```sql
   LOWER(TRIM(cr.ritual_type)) = LOWER(TRIM(rm.type))
   AND cr.level = rm.level
   AND LOWER(TRIM(cr.ritual_name)) = LOWER(TRIM(rm.name))
   ```

### Ambiguity Resolution

When multiple `rituals_master` rows match the same `(type, level, name)` signature:

- **Tie-breaker:** Select the ritual with the **lowest `id`** value
- **Rationale:** Lower IDs typically indicate earlier/primary entries
- **SQL Pattern:**
   ```sql
   SELECT id FROM rituals_master 
   WHERE type = ? AND level = ? AND name = ?
   ORDER BY id ASC LIMIT 1
   ```

### Unmatched Rows

Rows that cannot be matched to any `rituals_master` entry:

- **Status:** `ritual_id` remains `NULL`
- **Preservation:** Legacy fields (`ritual_name`, `ritual_type`, `level`) remain intact
- **Handling:** Can be manually linked later or represent custom rituals
- **Repository Behavior:** Falls back to legacy `(name, type, level)` matching in queries

## Re-run Procedure

The migration is **idempotent** and safe to re-execute:

1. **Column Check:** Verifies if `ritual_id` column exists before adding
2. **Index Check:** Verifies if index exists before creating
3. **FK Check:** Verifies if foreign key exists before creating
4. **Backfill:** Only updates rows where `ritual_id IS NULL`

**To re-run:**
```bash
php database/add_character_rituals_fk.php
```

Or via web:
```
database/add_character_rituals_fk.php
```

## Rollback Procedure

To rollback the migration:

1. **Drop Foreign Key:**
   ```sql
   ALTER TABLE character_rituals 
   DROP FOREIGN KEY fk_character_rituals_ritual_id;
   ```

2. **Drop Index:**
   ```sql
   DROP INDEX idx_ritual_id ON character_rituals;
   ```

3. **Drop Column:**
   ```sql
   ALTER TABLE character_rituals 
   DROP COLUMN ritual_id;
   ```

**Note:** Rollback does not destroy legacy data. All `ritual_name`, `ritual_type`, and `level` fields remain intact.

## Verification

### Running Verification

```bash
php database/verify_character_rituals_fk.php
```

Or via web:
```
database/verify_character_rituals_fk.php
```

### Verification Checks

1. **Linkage Rate:**
   - Total `character_rituals` rows
   - Rows with non-NULL `ritual_id` (linked)
   - Rows with NULL `ritual_id` (unmatched)
   - Linkage percentage (target: ≥95%)

2. **Unmatched Rows Sample:**
   - Top 10 unmatched rows with details
   - Potential match count for each

3. **Ambiguity Detection:**
   - Cases where `(type, level, name)` maps to multiple `rituals_master` rows
   - Lists all ritual IDs and names for ambiguous signatures

4. **Foreign Key Status:**
   - Verifies constraint exists
   - Shows constraint details

5. **Data Integrity:**
   - Checks for invalid FK references (orphaned `ritual_id` values)
   - Reports any integrity issues

## Repository Integration

### Updated Query Logic

The `CharacterRitualsRepository::getKnownRitualsForCharacter()` method now:

1. **Prefers FK join** when `ritual_id` is available:
   ```sql
   cr.ritual_id = rm.id
   ```

2. **Falls back to legacy matching** for rows with NULL `ritual_id`:
   ```sql
   cr.ritual_id IS NULL AND rm.name = cr.ritual_name 
   AND rm.type = cr.ritual_type AND rm.level = cr.level
   ```

3. **Combined JOIN condition:**
   ```sql
   LEFT JOIN rituals_master rm ON (
       cr.ritual_id = rm.id 
       OR (cr.ritual_id IS NULL AND rm.name = cr.ritual_name 
           AND rm.type = cr.ritual_type 
           AND rm.level = cr.level)
   )
   ```

### Backward Compatibility

- Legacy fields remain functional
- Existing code continues to work
- No breaking changes to API
- Gradual migration path (FK preferred, legacy fallback)

## Acceptance Criteria

- [x] Migration script executes without errors
- [x] ≥95% of `character_rituals` rows have non-NULL `ritual_id`
- [x] No data loss (row counts unchanged)
- [x] Foreign key constraint successfully created
- [x] Verification queries demonstrate linkage rate
- [x] Repository code handles both FK and fallback matching
- [x] Legacy fields preserved
- [x] Documentation complete

## Files Created/Modified

1. **Created:** `database/add_character_rituals_fk.php` - Main migration script
2. **Created:** `database/verify_character_rituals_fk.php` - Verification queries
3. **Created:** `database/CHARACTER_RITUALS_FK_MIGRATION.md` - This documentation
4. **Modified:** `agents/rituals_agent/src/CharacterRitualsRepository.php` - Updated join logic

## Notes

- Migration uses batched updates for performance
- Case-insensitive matching handles common data inconsistencies
- Ambiguity resolution is deterministic (lowest ID)
- Unmatched rows are preserved for manual review
- Foreign key uses `ON DELETE SET NULL` to preserve character_rituals rows if a ritual is deleted from master

