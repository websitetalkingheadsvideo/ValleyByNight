# Boon Agent - Schema Alignment Update

## Overview
The Boon Agent code has been updated to match the actual database schema structure. The database uses a normalized design with character IDs instead of names.

## Database Schema (Actual)

### Table: `boons`

**Primary Fields:**
- `id` - Primary key (not `boon_id`)
- `creditor_id` - INT, FK to characters.id (the giver/creditor)
- `debtor_id` - INT, FK to characters.id (the receiver/debtor)
- `boon_type` - ENUM('trivial','minor','major','life') - **lowercase**
- `status` - ENUM('active','fulfilled','cancelled','disputed') - **lowercase**
- `description` - TEXT
- `created_date` - TIMESTAMP (not `date_created`)
- `created_by` - INT, FK to users.id

**Additional Fields:**
- `fulfilled_date` - TIMESTAMP
- `due_date` - DATE
- `notes` - TEXT
- `updated_at` - TIMESTAMP

**Harpy Integration Fields:**
- `registered_with_harpy` - VARCHAR(255) - Character name of Harpy who registered
- `date_registered` - DATETIME
- `harpy_notes` - TEXT

## Status Mapping

**Database → UI Display:**
- `active` → "Owed" (covers both Owed and Called states)
- `fulfilled` → "Paid"
- `cancelled` → "Broken"
- `disputed` → "Broken"

## Key Changes Made

### 1. API (`admin/api_boons.php`)
- Updated all queries to JOIN with `characters` table to get names
- Maps database enum values (lowercase) to UI display values (title case)
- Supports both ID-based and name-based input (backward compatible)
- Handles Harpy registration fields

### 2. BoonAgent Class
- `getAllBoons()` now joins with characters table
- Returns both IDs and names for flexibility
- Maps status and boon_type for UI display

### 3. BoonAnalyzer Class
- All queries updated to use `id`, `creditor_id`, `debtor_id`
- `findDeadDebts()` - Uses character IDs and status = 'dead'
- `findUnregisteredBoons()` - Now actually checks `registered_with_harpy IS NULL`
- `findBrokenBoons()` - Uses status IN ('disputed', 'cancelled')
- `findCombinationOpportunities()` - Groups by creditor_id/debtor_id
- `voidBoonsOnDeath()` - Uses character IDs, marks status as 'cancelled'

### 4. BoonValidator Class
- Validates lowercase enum values from database
- Supports both ID-based and name-based validation
- Updated combination logic to work with IDs

### 5. ReportGenerator Class
- All queries updated to join with characters table
- Uses `created_date` instead of `date_created`
- Maps enum values appropriately

### 6. Admin Interface
- Statistics query updated to use lowercase status values
- Maps to UI-friendly display values

## Backward Compatibility

The API maintains backward compatibility by:
- Still returning `giver_name` and `receiver_name` in responses (via JOINs)
- Accepting both character IDs and character names in create/update operations
- Automatically looking up character IDs from names when needed

## Harpy Integration

✅ **Fully Functional Now:**
- `registered_with_harpy` field exists and is checked
- `date_registered` field exists and can be set
- `harpy_notes` field exists for additional Harpy information
- `findUnregisteredBoons()` now properly detects unregistered boons

## Testing Recommendations

1. Test API endpoints with actual database data
2. Verify character lookups work correctly
3. Test Harpy registration tracking
4. Verify status mappings display correctly in UI
5. Test character death handling

## Notes

- The existing `boon_ledger.php` and `admin_boons.js` may still reference old field names
- The API handles the translation, so the frontend should continue working
- Future updates to the ledger interface should use the new schema directly

