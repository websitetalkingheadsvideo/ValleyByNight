# Misfortune Boons Implementation Plan

## Current State Analysis

### Data Model Understanding

**NPC Storage:**
- NPCs are stored in the `characters` table where `player_name = 'NPC'`
- Each character has an `id` (integer primary key) and `character_name` (string)
- Example query pattern: `SELECT * FROM characters WHERE player_name = 'NPC'`

**Misfortune Character:**
- Character name: "Misfortune"
- Stored in `characters` table (need to verify ID exists in database)
- Character is a Malkavian Primogen and known "boon collector"

**Boons Table Schema:**
- `id` - Primary key
- `creditor_id` - Integer, references `characters.id` (who is owed)
- `debtor_id` - Integer, references `characters.id` (who owes)
- `boon_type` - ENUM: 'trivial', 'minor', 'major', 'life'
- `status` - ENUM: 'active', 'fulfilled', 'cancelled', 'disputed'
- `description` - TEXT (optional)
- `created_date` - DATETIME
- `created_by` - Integer (user_id, nullable)
- `registered_with_harpy` - VARCHAR (Harpy name, nullable)
- `date_registered` - DATETIME (nullable)
- `harpy_notes` - TEXT (nullable)
- Additional fields: `fulfilled_date`, `due_date`, `notes`, `updated_at`

**Boon Direction Logic:**
- Since Misfortune is the "boon collector", he should be the **creditor** (creditor_id)
- NPCs should be the **debtors** (debtor_id)
- This means: NPCs owe Misfortune, Misfortune holds the boons

### Existing Code Patterns
- Database connection via `includes/connect.php`
- Helper functions available: `db_fetch_all()`, `db_execute()`, `db_transaction()`
- NPC query pattern exists in `reference/Scenes/Character Teasers/tracking/generate_missing_character_teasers.php`
- Boon API exists in `admin/api_boons.php` for CRUD operations

## Implementation Plan

### Phase 1: Discovery & Validation
1. **Query Misfortune's Character ID**
   - Verify Misfortune exists in database
   - Get stable character ID
   - Handle case where Misfortune doesn't exist yet

2. **Query All NPCs (Excluding Misfortune)**
   - Get all NPCs where `player_name = 'NPC'`
   - Exclude Misfortune from the list
   - Count total NPCs for distribution calculation

3. **Check Existing Boons**
   - Query existing boons where Misfortune is creditor or debtor
   - Identify NPCs that already have boons with Misfortune
   - Document any existing boons that need to be preserved or updated

### Phase 2: Boon Generation Logic
4. **Implement Deterministic Distribution Algorithm**
   - Target: 5% Major, 25% Minor, 70% Trivial
   - Use hash-based assignment (e.g., MD5 of NPC ID + seed) for consistency
   - Calculate exact counts for each tier
   - Assign boon types to NPCs deterministically

5. **Create Boon Generation Script**
   - Script: `database/generate_misfortune_boons.php`
   - Functions:
     - `get_misfortune_character_id($conn): ?int`
     - `get_all_npcs_excluding_misfortune($conn, $misfortune_id): array`
     - `get_existing_boons($conn, $misfortune_id): array`
     - `assign_boon_tiers($npcs, $seed = 'misfortune_boons'): array`
     - `create_boons($conn, $misfortune_id, $npc_boons): array`

### Phase 3: Validation System
6. **Implement Validation Logic**
   - Ensure exactly one boon per NPC
   - Validate boon types are valid (trivial/minor/major/life)
   - Calculate and report actual distribution percentages
   - Check for duplicate boons
   - Verify all required fields are populated

7. **Create Validation Script**
   - Script: `database/validate_misfortune_boons.php`
   - Functions:
     - `validate_boon_distribution($conn, $misfortune_id): array`
     - `check_one_boon_per_npc($conn, $misfortune_id): array`
     - `calculate_distribution_percentages($boons): array`
     - `generate_validation_report($validation_results): string`

### Phase 4: Harpy Logging
8. **Determine Harpy Registration**
   - Check if there's a canonical Harpy character (likely Cordelia Fairchild based on Misfortune's relationships)
   - If no Harpy found, use placeholder or system registration
   - Document Harpy registration process

9. **Implement Harpy Logging**
   - Update boon creation to set `registered_with_harpy`
   - Set `date_registered = NOW()`
   - Add descriptive `harpy_notes` for each boon
   - Ensure all boons are logged consistently

10. **Create Harpy Logging Functions**
    - `get_harpy_character_id($conn): ?int`
    - `register_boon_with_harpy($conn, $boon_id, $harpy_id, $notes = ''): bool`
    - `log_all_misfortune_boons($conn, $misfortune_id, $harpy_id): array`

### Phase 5: Main Execution Script
11. **Create Main Generation Script**
    - Script: `database/generate_misfortune_boons.php` (web-accessible)
    - Run discovery phase
    - Generate boons with idempotency check
    - Register with Harpy
    - Run validation
    - Display comprehensive report

12. **Idempotency Implementation**
    - Check for existing boons before creation
    - Skip NPCs that already have boons with Misfortune
    - Option to update existing boons if needed (with confirmation)

### Phase 6: Testing & Documentation
13. **Test Script Execution**
    - Run script with dry-run mode first
    - Verify distribution matches targets
    - Test idempotency (run twice, no duplicates)
    - Validate Harpy logging

14. **Create Documentation**
    - Update README or create `database/BOONS_GENERATION.md`
    - Document the 5/25/70 distribution
    - Explain Harpy logging process
    - Provide usage instructions

## Files to Create/Modify

### New Files:
1. `database/generate_misfortune_boons.php` - Main generation script
2. `database/validate_misfortune_boons.php` - Validation script
3. `database/BOONS_GENERATION.md` - Documentation

### Files to Review:
1. `admin/api_boons.php` - Reference for boon creation patterns
2. `reference/Scenes/Character Teasers/tracking/generate_missing_character_teasers.php` - NPC query patterns

## Technical Decisions

### Boon Direction
- **Decision**: Misfortune is creditor (creditor_id), NPCs are debtors (debtor_id)
- **Rationale**: Misfortune is the boon collector, holding boons from others

### Distribution Algorithm
- **Method**: Hash-based deterministic assignment
- **Implementation**: `md5($npc_id . '_' . $seed) % 100`
  - 0-4: Major (5%)
  - 5-29: Minor (25%)
  - 30-99: Trivial (70%)
- **Seed**: 'misfortune_boons_v1' (allows versioning)

### Harpy Registration
- **Primary Harpy**: Cordelia Fairchild (from Misfortune's relationships)
- **Fallback**: System registration with notes indicating automatic registration
- **Notes Format**: "Auto-registered by boon generation system. Generated [date]."

### Status
- **Default Status**: 'active' (boons are owed, not fulfilled)

### Idempotency
- **Strategy**: Check for existing boon before creating
- **Query**: `SELECT * FROM boons WHERE creditor_id = ? AND debtor_id = ?`
- **Behavior**: Skip if exists, create if missing

## Success Criteria

1. ✅ Misfortune has exactly one boon with every NPC (excluding himself)
2. ✅ Distribution is approximately 5% Major, 25% Minor, 70% Trivial (within 2% tolerance)
3. ✅ All boons are registered with Harpy (registered_with_harpy field populated)
4. ✅ Validation script confirms data integrity
5. ✅ Script is idempotent (can run multiple times safely)
6. ✅ Comprehensive logging and reporting

## Next Steps

1. Get approval for this plan
2. Implement Phase 1 (Discovery)
3. Implement Phase 2 (Generation)
4. Implement Phase 3 (Validation)
5. Implement Phase 4 (Harpy Logging)
6. Implement Phase 5 (Main Script)
7. Test and document

