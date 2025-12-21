# VbN Database Cleanup - EXECUTION LOG
**Version:** 0.8.63 (shorthand: 0863)
**Date:** 2025-12-21 10:43:52
**Mode:** EXECUTION

## Step 4: Backup Creation

**Affected character IDs:** 42, 47, 48, 50, 52, 57, 68, 70, 87, 92, 102, 125, 130

Backing up related table rows...
✓ Backup written to: G:\VbN/reference/world/_summaries/DB_CLEANUP_0863_BACKUP.sql

## Step 5: Executing Changes

### 5A) Fixing Invalid Sire Values

- ID 70 (Alessandro Vescari): sire changed from '' → NULL
- ID 125 (Roxanne Murphy): sire changed from '' → NULL
- ID 48 (Duke Tiki): sire changed from 'bob' → NULL
- ID 50 (Sabine): sire changed from 'both' → NULL
- ID 52 (Sebastian): sire changed from 'both' → NULL
- ID 42 (Rembrandt Jones): sire changed from 'during' → NULL
- ID 102 (Mr. Harold Ashby): sire changed from 'him' → NULL
- ID 92 (Adrian Leclair): sire changed from 'in' → NULL
- ID 57 (Betty): sire changed from 'in' → NULL
- ID 47 (Cordelia Fairchild): sire changed from 'in' → NULL
- ID 68 (Étienne Duvalier): sire changed from 'in' → NULL

**Total sire updates:** 11

### 5B) Kerry Case Handling

- ID 130 (Kerry, the Desert-Wandering Gangrel): sire changed from '0' → NULL

**Note:** Kerry entries have different names ('Kerry, the Gangrel' vs 'Kerry, the Desert-Wandering Gangrel'), so they are treated as separate characters. Only invalid sire values were fixed.

✓ Transaction committed successfully

## Step 6: Verification

**Remaining invalid sire values:** 0
✓ All invalid sire placeholders have been cleaned up

**Kerry entries status:**
- ID 87 (Kerry, the Gangrel): sire = 'Old Tom'
- ID 130 (Kerry, the Desert-Wandering Gangrel): sire = NULL

## Step 7: Expected Impact on Summaries

**Characters that will change in next summary run:**

The following characters had their sire values normalized to NULL:
- Alessandro Vescari (ID: 70)
- Roxanne Murphy (ID: 125)
- Duke Tiki (ID: 48)
- Sabine (ID: 50)
- Sebastian (ID: 52)
- Rembrandt Jones (ID: 42)
- Mr. Harold Ashby (ID: 102)
- Adrian Leclair (ID: 92)
- Betty (ID: 57)
- Cordelia Fairchild (ID: 47)
- Étienne Duvalier (ID: 68)

These characters will appear in summaries with 'Unknown' or empty sire field instead of placeholder text.
Kerry entries were checked and invalid sire values fixed where found.
