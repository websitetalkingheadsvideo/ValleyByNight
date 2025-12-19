# TM-05 Ability Agent Implementation Plan

## Findings Summary

### Canonical Ability Schema
- **Table**: `abilities` 
- **Location**: Defined in `database/create_abilities_table.php`
- **Structure**: `id, name, category (Physical/Social/Mental/Optional), display_order, description, min_level, max_level`
- **Count**: 32 canonical abilities (8 Physical, 9 Social, 10 Mental, 5 Optional)

### Character Import Workflow
- **Entry Point**: `database/import_characters.php`
- **Function**: `importAbilities()` (lines 418-515)
- **Current Behavior**: Handles 3 formats but has NO validation, mapping, or deprecation support
- **Integration Point**: Called at line 877 during character import

### Existing Agent Pattern
- **Structure**: `agents/{agent_name}/src/`, `config/`, `tests/`
- **Example**: `agents/rituals_agent/` with `RitualsAgent.php` main class + repositories
- **Config**: JSON-based settings in `config/settings.json`

---

## Numbered Implementation Plan

### 1. Create Ability Agent Directory Structure
**Files to create:**
- `agents/ability_agent/src/AbilityAgent.php` - Main agent class
- `agents/ability_agent/src/AbilityRepository.php` - Database queries for canonical abilities
- `agents/ability_agent/src/AbilityValidator.php` - Validation logic (exact matches, aliases, normalization)
- `agents/ability_agent/src/AbilityMapper.php` - Mapping logic (aliases → canonical, deprecated → replacements)
- `agents/ability_agent/config/settings.json` - Configuration (aliases, deprecations, validation rules)
- `agents/ability_agent/config/index.php` - Security wrapper
- `agents/ability_agent/tests/AbilityAgentTest.php` - Unit tests
- `agents/ability_agent/tests/test_integration.php` - Integration test with import workflow
- `agents/ability_agent/README.md` - Documentation

### 2. Define Public API for Ability Agent
**Method signatures:**
```php
// AbilityAgent.php
class AbilityAgent {
    public function validate(array $sourceAbility): ValidationResult
    public function map(array $sourceAbility): MappingResult  
    public function processAbilities(array $sourceAbilities): ProcessResult
    public function getCanonicalAbilities(?string $category = null): array
}

// Return types:
class ValidationResult {
    public bool $isValid;
    public array $normalizedAbility; // Canonical format: {name, category, level, specialization}
    public array $issues; // Array of {code, severity, message, metadata}
}

class MappingResult {
    public ?array $canonicalAbility; // null if unmappable
    public array $issues; // Same structure as ValidationResult
}

class ProcessResult {
    public array $mappedAbilities; // Array of canonical ability objects
    public array $allIssues; // Aggregated issues with source ability reference
    public array $summary; // {total: int, valid: int, invalid: int, deprecated: int, mapped: int}
}
```

### 3. Implement Error/Warning Model
**Issue structure:**
```php
[
    'code' => string, // 'UNKNOWN_ABILITY', 'DEPRECATED_ABILITY', 'CATEGORY_MISMATCH', 'AMBIGUOUS_MAPPING', 'ALIAS_MAPPED'
    'severity' => 'error' | 'warning' | 'info',
    'message' => string,
    'metadata' => [
        'source_name' => string,
        'source_category' => string|null,
        'canonical_name' => string|null,
        'canonical_category' => string|null,
        'replacement' => string|null, // For deprecated abilities
        'confidence' => float // 0.0-1.0 for fuzzy matches
    ]
]
```

### 4. Define Alias and Deprecation Configuration
**Config file structure** (`config/settings.json`):
```json
{
  "enabled": true,
  "validation": {
    "strict_mode": false,
    "allow_unknown": true,
    "auto_replace_deprecated": false
  },
  "aliases": {
    "Alertness": ["Awareness"],  // Old name → New name
    "Gunplay": ["Firearms"],
    "Archery": ["Firearms"]
  },
  "deprecations": {
    "Old Ability Name": {
      "replacement": "New Ability Name",
      "category": "Physical",
      "reason": "Renamed in V5"
    }
  },
  "normalization": {
    "case_sensitive": false,
    "trim_whitespace": true,
    "fuzzy_matching": true
  },
  "logging": {
    "enabled": true,
    "level": "info"
  }
}
```

### 5. Implement Core Validation Logic (AbilityValidator.php)
**Features:**
- Exact name match against `abilities` table
- Case-insensitive matching (configurable)
- Whitespace normalization
- Category validation (check if ability belongs to claimed category)
- Fuzzy matching for typos (Levenshtein distance, threshold configurable)
- Specialization extraction (if provided in source format)

### 6. Implement Mapping Logic (AbilityMapper.php)
**Features:**
- Alias resolution (look up in config aliases)
- Deprecated ability replacement (look up in config deprecations)
- Category derivation (if not provided, look up from abilities table)
- Ambiguity detection (multiple matches, flag for manual review)
- Confidence scoring for fuzzy matches

### 7. Implement Repository Layer (AbilityRepository.php)
**Methods:**
- `getCanonicalAbility(string $name, ?string $category = null): ?array`
- `getAllCanonicalAbilities(?string $category = null): array`
- `getAbilitiesByCategory(string $category): array`
- `searchAbilities(string $query, ?string $category = null): array` (for fuzzy matching)
- `isValidAbility(string $name, string $category): bool`

### 8. Integrate into Character Import Workflow
**Modify** `database/import_characters.php`:
- Import AbilityAgent at top of file
- Replace `importAbilities()` function (lines 418-515) with enhanced version:
  ```php
  function importAbilities(mysqli $conn, int $character_id, array $data): array {
      $agent = new AbilityAgent($conn);
      $issues = [];
      
      // Normalize source abilities format to array of {name, category?, level}
      $sourceAbilities = normalizeSourceAbilities($data['abilities']);
      
      // Process through agent
      $result = $agent->processAbilities($sourceAbilities);
      
      // Store mapped abilities (only valid ones if strict mode)
      saveMappedAbilities($conn, $character_id, $result->mappedAbilities);
      
      // Return issues for reporting
      return $result->allIssues;
  }
  ```
- Add `$importIssues` tracking in `importCharacterFile()` function
- Surface issues in import summary/output

### 9. Add Unit Tests (AbilityAgentTest.php)
**Test cases:**
- ✅ Valid ability name + valid category
- ✅ Valid ability name + wrong category (flag mismatch)
- ✅ Deprecated ability (flag + replacement suggested/applied)
- ✅ Unknown ability (flag invalid)
- ✅ Alias mapping (maps to canonical)
- ✅ Case normalization ("athletics" → "Athletics")
- ✅ Whitespace normalization ("  Athletics  " → "Athletics")
- ✅ Fuzzy matching (typo correction)
- ✅ Category derivation (missing category filled from canonical)
- ✅ Ambiguous mapping (multiple matches flagged)

### 10. Add Integration Test (test_integration.php)
**Test scenarios:**
- Sample character JSON import uses the agent
- Verify abilities are validated and mapped correctly
- Verify issues are captured and reported
- Verify database stores canonical format
- Test all 3 source formats (array of objects, array of strings, category object)

### 11. Documentation (README.md)
**Sections:**
- What the Ability Agent does
- Input/output examples (including issue structures)
- How to add aliases/deprecations (edit `config/settings.json`)
- How import workflow consumes it
- How to run tests
- Configuration reference
- API reference

### 12. Create Fallback Mechanisms
**For missing canonical definitions:**
- If `abilities` table is empty or ability not found:
  - Log warning to error log
  - Return issue with `UNKNOWN_ABILITY` code
  - Optionally create TODO marker in code/config
- If config file missing/invalid:
  - Use empty aliases/deprecations arrays (log warning)
  - Continue with basic validation only

---

## Implementation Order

1. **Phase 1: Core Infrastructure**
   - Create directory structure
   - Implement AbilityRepository (read from abilities table)
   - Create config/settings.json with initial aliases/deprecations
   
2. **Phase 2: Validation & Mapping**
   - Implement AbilityValidator (exact matches, normalization)
   - Implement AbilityMapper (aliases, deprecations)
   - Wire together in AbilityAgent main class
   
3. **Phase 3: Integration**
   - Modify import_characters.php to use AbilityAgent
   - Add issue reporting to import workflow
   - Test with sample character JSON files
   
4. **Phase 4: Testing & Documentation**
   - Write unit tests
   - Write integration test
   - Write README.md
   - Verify all acceptance criteria met

---

## Acceptance Criteria Verification

✅ **Agent validates ability names and categories**
- Implementation: AbilityValidator checks against abilities table
- Test: Unit test for valid/invalid names and categories

✅ **Agent flags invalid or deprecated abilities**
- Implementation: Issues array with codes/severity
- Test: Unit tests for unknown/deprecated/category mismatch

✅ **Agent assists character conversion**
- Implementation: processAbilities() normalizes and maps to canonical format
- Test: Integration test with import workflow

✅ **Definition of Done**
- Implementation: README.md with usage examples
- Integration: Modified import_characters.php calls agent
- Test: All test cases pass

---

## Files Summary

**New files (9):**
- `agents/ability_agent/src/AbilityAgent.php`
- `agents/ability_agent/src/AbilityRepository.php`
- `agents/ability_agent/src/AbilityValidator.php`
- `agents/ability_agent/src/AbilityMapper.php`
- `agents/ability_agent/config/settings.json`
- `agents/ability_agent/config/index.php`
- `agents/ability_agent/tests/AbilityAgentTest.php`
- `agents/ability_agent/tests/test_integration.php`
- `agents/ability_agent/README.md`

**Modified files (1):**
- `database/import_characters.php` (enhance importAbilities function, add issue tracking)

---

## Next Steps

**STOP HERE FOR APPROVAL**

Once approved, proceed with implementation in Plan Mode following the numbered steps above.

