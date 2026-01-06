# C.W. Whitford Boons Implementation Plan

## Project Goal

Implement a system where **Charles "C.W." Whitford** has **exactly one boon** with **exactly 50% of all NPCs** in the database. Boon tiers are distributed as:
- **30% Major boons**
- **50% Minor boons**
- **20% Trivial boons**

All boons must be validated, logged with Harpy, and the system must be idempotent.

---

## Step-by-Step Implementation Plan

### Phase 1: Discovery & Analysis

#### Step 1.1: Verify C.W. Whitford in Database
- **Action**: Query `characters` table for `character_name = 'Charles "C.W." Whitford'` or `character_name LIKE '%Whitford%'`
- **Expected Result**: Character ID for C.W. Whitford
- **If Missing**: Import from `reference/Characters/CW Whitford.json` using existing import script
- **Files to Check**: 
  - `database/import_characters.php`
  - `reference/Characters/CW Whitford.json`

#### Step 1.2: Analyze NPC Database Structure
- **Action**: Query total NPC count: `SELECT COUNT(*) FROM characters WHERE player_name = 'NPC'`
- **Action**: Verify NPC data structure matches expected format
- **Files**: 
  - `reference/Scenes/Character Teasers/tracking/generate_missing_character_teasers.php` (reference for NPC query pattern)
  - `admin/admin_npc_briefing.php` (shows NPC query structure)

#### Step 1.3: Analyze Existing Boon System
- **Action**: Review `database/generate_misfortune_boons.php` as reference implementation
- **Key Patterns to Extract**:
  - NPC selection logic
  - Boon tier assignment (hash-based deterministic)
  - Boon description generation
  - Harpy logging integration
  - Database transaction handling
- **Files**: 
  - `database/generate_misfortune_boons.php`
  - `admin/api_boons.php`
  - `database/check_boons_table.php`

#### Step 1.4: Verify Boon Table Schema
- **Action**: Confirm `boons` table has required fields:
  - `id`, `creditor_id`, `debtor_id`, `boon_type`, `status`
  - `registered_with_harpy`, `date_registered`, `harpy_notes`
- **File**: `database/check_boons_table.php`

#### Step 1.5: Identify Harpy Character
- **Action**: Query for Harpy character (likely "Cordelia Fairchild")
- **Query**: `SELECT id FROM characters WHERE character_name = 'Cordelia Fairchild' AND player_name = 'NPC'`
- **Fallback**: Use "System" if Harpy not found

---

### Phase 2: Core Implementation

#### Step 2.1: Create Main Generation Script
- **File**: `database/generate_cw_whitford_boons.php`
- **Based On**: `database/generate_misfortune_boons.php`
- **Key Modifications**:
  1. Change character lookup from "Misfortune" to "Charles \"C.W.\" Whitford"
  2. Implement **50% NPC selection** instead of 100%
  3. Keep same boon tier distribution (5/25/70)
  4. Update boon descriptions to reflect C.W.'s character (Ventrue Primogen, power broker, real estate)

#### Step 2.2: Implement Deterministic 50% NPC Selection
- **Algorithm**: Hash-based selection using NPC ID + seed
- **Function**: `select_npcs_for_cw(array $npcs, string $seed = 'cw_whitford_boons_v1'): array`
- **Logic**:
  ```php
  function select_npcs_for_cw(array $npcs, string $seed = 'cw_whitford_boons_v1'): array {
      $selected = [];
      foreach ($npcs as $npc) {
          $hash = md5($npc['id'] . '_' . $seed);
          $value = hexdec(substr($hash, 0, 8)) % 100;
          if ($value < 50) { // 50% selection
              $selected[] = $npc;
          }
      }
      return $selected;
  }
  ```
- **Requirements**:
  - Must be deterministic (same NPCs selected on every run)
  - Must select exactly 50% (or as close as possible with odd numbers)
  - Must be stable across script executions

#### Step 2.3: Implement Boon Tier Distribution
- **Function**: `assign_boon_tier(int $npc_id, string $seed = 'cw_whitford_boons_v1'): string`
- **Distribution**:
  - 0-4: Major (5%)
  - 5-29: Minor (25%)
  - 30-99: Trivial (70%)
- **Based On**: Existing `assign_boon_tier()` from `generate_misfortune_boons.php`

#### Step 2.4: Generate C.W.-Specific Boon Descriptions
- **Function**: `generate_cw_boon_description(array $npc, string $boon_tier, int $cw_id): string`
- **Character Themes for C.W.**:
  - Ventrue Primogen
  - Real estate/land development
  - Political maneuvering
  - Power broker
  - Business/corporate influence
  - Strategic boon collection
- **Templates**: Create descriptions that reflect C.W.'s personality and methods
- **Reference**: `generate_boon_description()` in `generate_misfortune_boons.php` (lines 156-361)

#### Step 2.5: Implement Boon Creation Logic
- **Function**: `create_cw_boon($conn, int $creditor_id, int $debtor_id, string $boon_type, string $description, ?string $harpy_name, int $created_by_user_id): ?int`
- **Based On**: `create_boon()` from `generate_misfortune_boons.php` (lines 389-469)
- **Requirements**:
  - Use prepared statements
  - Set `registered_with_harpy` field
  - Set `date_registered` via UPDATE after INSERT
  - Set `harpy_notes` with auto-registration message
  - Return boon ID on success, null on failure

#### Step 2.6: Implement Existing Boon Check
- **Function**: `get_existing_cw_boons($conn, int $cw_id): array`
- **Query**: `SELECT debtor_id, boon_type, status FROM boons WHERE creditor_id = ? AND status = 'active'`
- **Purpose**: Prevent duplicates, allow idempotent re-runs

---

### Phase 3: Validation & Harpy Logging

#### Step 3.1: Implement Validation Logic
- **Function**: `validate_cw_boons($conn, int $cw_id, int $total_npc_count): array`
- **Checks**:
  1. C.W. has boons with exactly 50% of NPCs (or closest possible)
  2. No NPC in selected set has >1 active boon with C.W.
  3. No NPC outside selected set has an active boon with C.W.
  4. All boons use valid tiers ('trivial', 'minor', 'major', 'life')
  5. Distribution approximates 5/25/70 (within tolerance)
- **Output**: Validation report with pass/fail status and details

#### Step 3.2: Implement Harpy Logging
- **Integration**: Use existing `boons` table fields:
  - `registered_with_harpy`: Character name (e.g., "Cordelia Fairchild")
  - `date_registered`: Timestamp
  - `harpy_notes`: Auto-registration message
- **Function**: `log_boon_to_harpy($conn, int $boon_id, string $harpy_name, string $action): bool`
- **Actions**: `CREATED`, `UPDATED`, `REMOVED`, `SKIPPED_EXISTS`
- **Note**: No separate logging table needed - boons table IS the Harpy log

#### Step 3.3: Implement Reconciliation Check
- **Function**: `reconcile_harpy_log($conn, int $cw_id): array`
- **Purpose**: Verify all C.W. boons are properly registered with Harpy
- **Output**: List of any mismatches or missing registrations

---

### Phase 4: Main Script Implementation

#### Step 4.1: Create Main Execution Flow
- **File**: `database/generate_cw_whitford_boons.php`
- **Structure** (based on `generate_misfortune_boons.php`):
  1. Database connection
  2. Get C.W. Whitford ID
  3. Get all NPCs (excluding C.W.)
  4. **Select 50% of NPCs** (NEW)
  5. Get existing boons for C.W.
  6. Get Harpy character ID
  7. Assign boon tiers to selected NPCs
  8. Generate descriptions
  9. Create boons in transaction
  10. Validate results
  11. Generate final report

#### Step 4.2: Implement Transaction Safety
- **Pattern**: Use `mysqli_begin_transaction()`, `mysqli_commit()`, `mysqli_rollback()`
- **Error Handling**: Rollback on any failure, provide detailed error messages
- **Reference**: `generate_misfortune_boons.php` lines 571-671

#### Step 4.3: Implement Progress Reporting
- **Output**: Real-time progress with HTML formatting
- **Show**:
  - NPC selection count and percentage
  - Boon tier distribution
  - Creation progress
  - Validation results
  - Final summary report

---

### Phase 5: Testing & Documentation

#### Step 5.1: Create Test Script
- **File**: `database/test_cw_whitford_boons.php` (optional)
- **Tests**:
  - Re-running generator (idempotency)
  - Edge cases (NPCs lacking IDs, pre-existing boons)
  - Tier distribution confirmation
  - 50% selection stability

#### Step 5.2: Update Documentation
- **File**: Add to `database/` README or create `database/CW_WHITFORD_BOONS.md`
- **Content**:
  - 50% selection logic explanation
  - Boon tier distribution
  - How to re-run validation
  - Harpy logging process
  - Usage instructions

#### Step 5.3: Create CLI/Web Interface
- **Web**: Access via `database/generate_cw_whitford_boons.php`
- **CLI**: `php database/generate_cw_whitford_boons.php` (if needed)
- **Output**: HTML-formatted report (similar to Misfortune script)

---

## File Structure

```
database/
├── generate_cw_whitford_boons.php  (NEW - main script)
├── generate_misfortune_boons.php   (REFERENCE)
├── check_boons_table.php           (VERIFY SCHEMA)
└── import_characters.php           (IF C.W. NOT IN DB)

reference/Characters/
└── CW Whitford.json                (CHARACTER DATA)
```

---

## Key Differences from Misfortune Script

1. **NPC Selection**: 50% instead of 100%
2. **Character**: C.W. Whitford (Ventrue Primogen) instead of Misfortune (Malkavian Primogen)
3. **Boon Descriptions**: Reflect C.W.'s themes (real estate, politics, power brokering)
4. **Selection Algorithm**: Hash-based 50% selection function

---

## Validation Requirements

After implementation, verify:
- ✅ C.W. has boons with exactly 50% of NPCs
- ✅ No duplicates (each selected NPC has exactly 1 boon)
- ✅ No boons with non-selected NPCs
- ✅ All boons use valid tiers
- ✅ Distribution: ~5% Major, ~25% Minor, ~70% Trivial
- ✅ Script is idempotent (re-running doesn't create duplicates)
- ✅ Selection is stable (same NPCs selected on each run)
- ✅ All boons registered with Harpy

---

## Implementation Order

1. **Discovery** (Steps 1.1-1.5): Verify data, understand structure
2. **Core Functions** (Steps 2.1-2.6): Build selection, tier assignment, description generation
3. **Validation** (Steps 3.1-3.3): Implement checks and Harpy logging
4. **Main Script** (Steps 4.1-4.3): Assemble complete script
5. **Testing** (Steps 5.1-5.3): Test, document, deploy

---

## Notes

- **Harpy Logging**: Uses `boons` table fields directly (no separate table)
- **Idempotency**: Check for existing boons before creating
- **Deterministic Selection**: Hash-based algorithm ensures stability
- **Transaction Safety**: All boon creation in single transaction
- **Error Handling**: Detailed error messages, rollback on failure

---

## Ready for Implementation

This plan provides a complete roadmap for implementing the C.W. Whitford boons system. Each step builds on the previous, and the reference implementation (`generate_misfortune_boons.php`) provides proven patterns to follow.

**Next Action**: Begin with Phase 1 (Discovery) to verify C.W. Whitford exists in the database and understand the current state.

