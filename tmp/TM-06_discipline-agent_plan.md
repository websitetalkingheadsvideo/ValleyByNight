# TM-06 Discipline Agent Implementation Plan

## Findings Summary

### Database Schema
- **Table**: `character_disciplines`
  - Structure: `character_id`, `discipline_name`, `level` (0-5)
  - Stores innate disciplines only (paths/rituals are in separate tables)
- **Table**: `character_discipline_powers`
  - Structure: `character_id`, `discipline_name`, `power_name`, `level`
  - Stores individual discipline powers
- **Separate Systems**:
  - `character_paths` - Paths (Thaumaturgy paths, etc.) - handled by PathsAgent
  - `character_rituals` - Rituals - handled by RitualsAgent
  - These are **completely separate** from `character_disciplines`

### Existing Agent Pattern
- **Structure**: `agents/{agent_name}/src/`, `config/`, `tests/`
- **Example**: `agents/ability_agent/`, `agents/rituals_agent/`, `agents/paths_agent/`
- **Pattern**: Main Agent class + Repository classes + Validator classes
- **Config**: JSON-based settings in `config/settings.json`

### Clan Discipline Access Rules
- Defined in `js/modules/systems/DisciplineSystem.js` (lines 27-43)
- Also in `reference/mechanics/clans/Clan_Disciplines.MD`
- Each clan has 3 clan disciplines (in-clan)
- Out-of-clan disciplines can be learned but may have restrictions

### Discipline Categories (for exclusion logic)
- **Clan Disciplines**: Animalism, Auspex, Celerity, Dominate, Fortitude, Obfuscate, Potence, Presence, Protean
- **Blood Sorcery**: Thaumaturgy, Necromancy, Koldunic Sorcery (these are disciplines, NOT paths)
- **Advanced Disciplines**: Obtenebration, Chimerstry, Dementation, Quietus, Vicissitude, Serpentis, etc.
- **Paths**: Stored in `character_paths` table (e.g., "Path of Blood", "Path of Geomancy") - EXCLUDE
- **Rituals**: Stored in `character_rituals` table - EXCLUDE

### Current Validation Logic
- `includes/save_character.php` (lines 254-305): Basic level validation (1-5)
- `js/modules/systems/DisciplineSystem.js`: Client-side validation and clan access rules
- No centralized server-side validation agent

---

## Numbered Implementation Plan

### 1. Create Discipline Agent Directory Structure
**Files to create:**
- `agents/discipline_agent/src/DisciplineAgent.php` - Main agent class
- `agents/discipline_agent/src/DisciplineRepository.php` - Database queries for character disciplines
- `agents/discipline_agent/src/DisciplinePowersRepository.php` - Database queries for discipline powers
- `agents/discipline_agent/src/DisciplineValidator.php` - Validation logic (dots, clan access, power eligibility)
- `agents/discipline_agent/src/ClanAccessRepository.php` - Clan discipline access rules
- `agents/discipline_agent/config/settings.json` - Configuration (clan mappings, validation rules)
- `agents/discipline_agent/config/index.php` - Security wrapper
- `agents/discipline_agent/tests/DisciplineAgentTest.php` - Unit tests
- `agents/discipline_agent/tests/test_integration.php` - Integration tests
- `agents/discipline_agent/README.md` - Documentation

### 2. Define Public API for Discipline Agent
**Method signatures:**
```php
// DisciplineAgent.php
class DisciplineAgent {
    /**
     * List a character's innate disciplines (excludes paths/rituals)
     * @param int $characterId
     * @return array {disciplines: array, summary: array}
     */
    public function listCharacterDisciplines(int $characterId): array
    
    /**
     * Validate discipline dot ranges and constraints
     * @param int $characterId
     * @param array $updates {discipline_name: level, ...}
     * @return array {isValid: bool, errors: array, warnings: array}
     */
    public function validateDisciplineDots(int $characterId, array $updates): array
    
    /**
     * Validate clan access rules for a discipline
     * @param int $characterId
     * @param string $disciplineName
     * @return array {hasAccess: bool, isInClan: bool, restrictions: array}
     */
    public function validateClanDisciplineAccess(int $characterId, string $disciplineName): array
    
    /**
     * Validate discipline power eligibility
     * @param int $characterId
     * @param array $requestedPowers {discipline_name: [power_names], ...}
     * @return array {isValid: bool, errors: array, eligiblePowers: array}
     */
    public function validateDisciplinePowers(int $characterId, array $requestedPowers): array
}
```

### 3. Implement Repository Layer (DisciplineRepository.php)
**Methods:**
- `getCharacterDisciplines(int $characterId): array` - Get all disciplines for character
- `getDisciplineLevel(int $characterId, string $disciplineName): ?int` - Get specific discipline level
- `isInnateDiscipline(string $disciplineName): bool` - Check if discipline is innate (not path/ritual)
- **CRITICAL**: Filter out any entries that match path/ritual patterns:
  - Exclude names starting with "Path of"
  - Exclude names in `character_paths` table
  - Exclude names in `character_rituals` table
  - Only return disciplines from `character_disciplines` table

### 4. Implement Discipline Powers Repository (DisciplinePowersRepository.php)
**Methods:**
- `getCharacterDisciplinePowers(int $characterId, ?string $disciplineName = null): array`
- `getEligiblePowers(int $characterId, string $disciplineName, int $level): array`
- **CRITICAL**: Only query `character_discipline_powers` table, never paths/rituals

### 5. Implement Clan Access Repository (ClanAccessRepository.php)
**Methods:**
- `getClanDisciplines(string $clanName): array` - Get in-clan disciplines for a clan
- `isInClanDiscipline(string $clanName, string $disciplineName): bool`
- `getClanName(int $characterId): ?string` - Get character's clan
- Load clan mappings from config or hardcoded (from DisciplineSystem.js pattern)

### 6. Implement Validation Logic (DisciplineValidator.php)
**Features:**
- **Dot Range Validation**:
  - Level must be integer between 0 and 5
  - Reject non-integers, negative values, values > 5
  - Validate type (must be int, not string "5")
- **Clan Access Validation**:
  - Check if discipline is in-clan for character's clan
  - Flag out-of-clan disciplines (may be allowed but flagged)
  - Check for special restrictions (e.g., Caitiff can learn any)
- **Power Eligibility Validation**:
  - Power level must not exceed discipline level
  - Power must exist for that discipline
  - Validate power names against canonical power list
- **Path/Ritual Exclusion**:
  - Explicitly filter out any discipline names that match path patterns
  - Cross-reference with `character_paths` and `character_rituals` to ensure exclusion
  - Add tests to prove paths/rituals never leak through

### 7. Define Configuration (config/settings.json)
**Config structure:**
```json
{
  "enabled": true,
  "validation": {
    "min_dots": 0,
    "max_dots": 5,
    "strict_mode": false,
    "allow_out_of_clan": true
  },
  "clan_disciplines": {
    "Assamite": ["Celerity", "Obfuscate", "Quietus"],
    "Brujah": ["Celerity", "Potence", "Presence"],
    "Gangrel": ["Animalism", "Fortitude", "Protean"],
    "Malkavian": ["Auspex", "Dementation", "Obfuscate"],
    "Nosferatu": ["Animalism", "Obfuscate", "Potence"],
    "Toreador": ["Auspex", "Celerity", "Presence"],
    "Tremere": ["Auspex", "Dominate", "Thaumaturgy"],
    "Ventrue": ["Dominate", "Fortitude", "Presence"],
    "Caitiff": []
  },
  "exclusion_patterns": {
    "path_prefixes": ["Path of"],
    "exclude_tables": ["character_paths", "character_rituals"]
  },
  "logging": {
    "enabled": true,
    "level": "info"
  }
}
```

### 8. Implement Main Agent Class (DisciplineAgent.php)
**Structure:**
- Follow pattern from AbilityAgent, RitualsAgent, PathsAgent
- Initialize repositories in constructor
- Load config from settings.json
- Implement all public API methods
- Add logging for validation failures
- **CRITICAL**: Add explicit comments/documentation stating "NO PATHS OR RITUALS"

### 9. Add Path/Ritual Exclusion Tests
**Test cases (CRITICAL for acceptance criteria):**
- ✅ Test that `listCharacterDisciplines()` never returns path entries
- ✅ Test that paths in `character_paths` table are excluded
- ✅ Test that rituals in `character_rituals` table are excluded
- ✅ Test that discipline names starting with "Path of" are excluded
- ✅ Test that agent methods never query `character_paths` or `character_rituals` tables
- ✅ Test that DTOs/return types cannot represent paths/rituals
- ✅ Test that even if path data exists in `character_disciplines` (data corruption), it's filtered out

### 10. Add Unit Tests (DisciplineAgentTest.php)
**Test cases:**
- ✅ Valid discipline dots (0-5)
- ✅ Invalid discipline dots (negative, >5, non-integer, string "5")
- ✅ In-clan discipline access validation
- ✅ Out-of-clan discipline access validation
- ✅ Power eligibility (power level <= discipline level)
- ✅ Invalid power names
- ✅ Clan discipline mapping correctness
- ✅ Character with no disciplines returns empty array
- ✅ Character with multiple disciplines returns all
- ✅ Path/ritual exclusion (see section 9)

### 11. Add Integration Test (test_integration.php)
**Test scenarios:**
- Sample character with disciplines, paths, and rituals
- Verify only disciplines are returned (paths/rituals excluded)
- Verify dot validation works in character save workflow
- Verify clan access rules are enforced
- Verify power eligibility is checked correctly

### 12. Create Agent JSON Contract (discipline_agent.json)
**Following rituals_agent.json pattern:**
- Define agent purpose: "Validates and lists character disciplines (innate only, excludes paths/rituals)"
- Define data sources: `character_disciplines`, `character_discipline_powers`
- Define functions: `listCharacterDisciplines`, `validateDisciplineDots`, `validateClanDisciplineAccess`, `validateDisciplinePowers`
- **Explicitly state**: "This agent does NOT handle paths or rituals. Use PathsAgent and RitualsAgent for those."

### 13. Documentation (README.md)
**Sections:**
- What the Discipline Agent does (and what it doesn't do - no paths/rituals)
- Input/output examples
- How to use in character creation/update workflows
- Configuration reference
- API reference
- **Explicit warning**: "This agent excludes paths and rituals. Use PathsAgent and RitualsAgent for those systems."

### 14. Integration Points (Future - not in this task)
**Potential integration locations:**
- `includes/save_character.php` - Use agent for validation before saving
- `admin/api_disciplines.php` - Use agent for API responses
- Character creation workflow - Use agent for validation
- **Note**: Integration is out of scope for TM-06, but agent should be ready for integration

---

## Implementation Order

1. **Phase 1: Core Infrastructure**
   - Create directory structure
   - Implement repositories (DisciplineRepository, DisciplinePowersRepository, ClanAccessRepository)
   - Create config/settings.json with clan mappings
   - Add path/ritual exclusion logic to repositories

2. **Phase 2: Validation & Agent**
   - Implement DisciplineValidator
   - Implement DisciplineAgent main class
   - Wire together all components
   - Add explicit path/ritual exclusion checks

3. **Phase 3: Testing**
   - Write unit tests (including path/ritual exclusion tests)
   - Write integration tests
   - Verify all acceptance criteria met

4. **Phase 4: Documentation**
   - Write README.md
   - Create discipline_agent.json contract
   - Add code comments emphasizing "no paths/rituals"

---

## Acceptance Criteria Verification

✅ **Agent can list a character's disciplines**
- Implementation: `listCharacterDisciplines()` method
- Test: Unit test for character with multiple disciplines

✅ **Agent validates dot ranges**
- Implementation: `validateDisciplineDots()` with 0-5 range check
- Test: Unit tests for valid (0-5) and invalid (negative, >5, non-integer) values

✅ **Agent distinguishes innate disciplines from paths/rituals (excludes paths/rituals)**
- Implementation: Explicit filtering in repositories, exclusion patterns in config
- Test: Comprehensive tests proving paths/rituals never returned (section 9)

✅ **Discipline Agent active**
- Implementation: Agent class created, wired, ready for use
- Test: Integration test shows agent can be instantiated and used

✅ **No path or ritual data exposed**
- Implementation: No queries to path/ritual tables, explicit filtering, DTOs don't include path/ritual fields
- Test: Tests prove exclusion (section 9)

---

## Files Summary

**New files (11):**
- `agents/discipline_agent/src/DisciplineAgent.php`
- `agents/discipline_agent/src/DisciplineRepository.php`
- `agents/discipline_agent/src/DisciplinePowersRepository.php`
- `agents/discipline_agent/src/DisciplineValidator.php`
- `agents/discipline_agent/src/ClanAccessRepository.php`
- `agents/discipline_agent/config/settings.json`
- `agents/discipline_agent/config/index.php`
- `agents/discipline_agent/tests/DisciplineAgentTest.php`
- `agents/discipline_agent/tests/test_integration.php`
- `agents/discipline_agent/README.md`
- `agents/discipline_agent/discipline_agent.json`

**Modified files (0):**
- No existing files need modification for TM-06 (integration is future work)

---

## Critical Implementation Notes

### Path/Ritual Exclusion Strategy

1. **Repository Level**:
   - `DisciplineRepository::getCharacterDisciplines()` - Only query `character_disciplines` table
   - Add WHERE clause to exclude any discipline names matching path patterns
   - Cross-check against `character_paths` table to ensure no overlap
   - Cross-check against `character_rituals` table to ensure no overlap

2. **Validation Level**:
   - `DisciplineValidator` should reject any discipline name that:
     - Starts with "Path of"
     - Exists in `character_paths` table
     - Exists in `character_rituals` table
     - Matches any pattern in exclusion config

3. **Agent Level**:
   - All public methods should explicitly filter results
   - Add logging when path/ritual data is detected (for debugging)
   - Return empty arrays/errors rather than leaking path/ritual data

4. **Testing Level**:
   - Create test data with paths/rituals in `character_disciplines` (simulating corruption)
   - Verify agent filters them out
   - Verify no queries are made to path/ritual tables

---

## Next Steps

**STOP HERE FOR APPROVAL**

Once approved, proceed with implementation in Plan Mode following the numbered steps above.

**Key Reminders:**
- NO paths or rituals in any agent output
- Explicit exclusion logic at multiple layers
- Comprehensive tests proving exclusion
- Documentation clearly states "no paths/rituals"

