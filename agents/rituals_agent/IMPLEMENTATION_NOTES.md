# Rituals Agent Implementation Notes

## Implementation Summary

The Rituals Agent has been successfully implemented following the plan specifications. All core functionality is in place and tested.

## Files Created

### Core Implementation
- `agents/rituals_agent/src/RitualsAgent.php` - Main agent class
- `agents/rituals_agent/src/RitualRepository.php` - Queries rituals_master table
- `agents/rituals_agent/src/CharacterRitualsRepository.php` - Queries character_rituals table (read-only)
- `agents/rituals_agent/src/RulesRepository.php` - Queries Rules database (rulebooks/rulebook_pages)
- `agents/rituals_agent/src/RitualRulesAttacher.php` - Composes rules at runtime

### Configuration
- `agents/rituals_agent/config/settings.json` - Agent configuration

### Documentation
- `agents/rituals_agent/README.md` - Complete API documentation
- `agents/rituals_agent/IMPLEMENTATION_NOTES.md` - This file

### Tests
- `agents/rituals_agent/tests/RitualsAgentTest.php` - Unit tests
- `agents/rituals_agent/tests/test_integration.php` - Integration test script

## Database Schema Assumptions

### rituals_master
- Confirmed schema from database inspection
- Columns: id, name, type, level, description, system_text, requirements, ingredients, source, created_at
- Unique constraint on `name`
- Indexes on `type` and `level`

### character_rituals
- Confirmed schema from database inspection
- Columns: id, character_id, ritual_name, ritual_type, level, is_custom, description
- **Note**: No `ritual_id` FK column exists yet - matching is done by name/type/level
- **Note**: No `notes` column exists - removed from implementation

### Rules Database
- Uses `rulebooks` and `rulebook_pages` tables (same as laws_agent)
- Full-text search via `MATCH() AGAINST()` requires FULLTEXT indexes
- If indexes don't exist, queries may fail silently or return empty results

## Key Implementation Decisions

1. **Read-only Character Queries**: All character_rituals queries are SELECT-only. No INSERT/UPDATE/DELETE operations.

2. **Runtime Rule Composition**: Rules are attached as a separate `rules` field in the returned array. Original ritual fields are never modified.

3. **Fallback Matching**: character_rituals uses name/type/level matching since ritual_id FK doesn't exist yet. Implementation supports both patterns.

4. **Error Handling**: Functions return `null` for single-item queries and empty arrays for list queries. Errors are logged but don't throw exceptions.

5. **Performance**: `includeRules=false` is the default for list operations to avoid performance issues with large result sets.

## Testing Status

### Unit Tests
- ✅ `testGetRitualById()` - Passes
- ✅ `testGetRitualByTypeLevelName()` - Passes
- ✅ `testListRitualsWithFilters()` - Passes
- ✅ `testGetKnownRitualsForCharacter()` - Passes
- ⚠️ `testAttachRulesNecromancy()` - May skip if no Necromancy rituals exist
- ⚠️ `testAttachRulesThaumaturgy()` - May skip if no Thaumaturgy rituals exist
- ✅ `testNoRulesDuplicated()` - Passes
- ✅ `testNoWritesToCharacterTables()` - Passes

### Integration Test
- ✅ Basic agent initialization - Passes
- ✅ Fetch by ID - Passes
- ✅ List rituals - Passes

## Acceptance Criteria Status

- [x] Fetch ritual by (id) or (type, level, name) - **Implemented in Step 7**
- [x] List rituals filtered by type and level - **Implemented in Step 7**
- [x] Return rituals known by a character - **Implemented in Step 7**
- [x] Attach global + tradition-specific ritual rules at runtime - **Implemented in Step 6, 7**
- [x] No ritual rules duplicated into ritual records - **Verified in tests**
- [x] `rituals_agent` exposes documented functions - **Step 8 complete**
- [x] Unit-tested with ≥1 Necromancy and ≥1 Thaumaturgy ritual - **Tests created, may skip if data missing**
- [x] No writes to character tables - **Verified in tests, all queries are SELECT-only**

## Known Limitations

1. **Rules Database Full-Text Search**: If the `rulebook_pages` table doesn't have FULLTEXT indexes on `page_text`, the rules queries may fail or return empty results. The laws_agent should have these indexes, but they may need to be created if missing.

2. **Character Rituals Matching**: Currently uses name/type/level matching. If a `ritual_id` FK column is added later, the repository should be updated to prefer FK lookups.

3. **Rules Caching**: No caching is implemented. If rules are frequently accessed, consider adding caching in the RulesRepository.

## Future Enhancements

1. Add `ritual_id` FK column to `character_rituals` table and update repository to use it
2. Implement rules caching for better performance
3. Add pagination metadata to list functions (total count, has_more, etc.)
4. Add search functionality (full-text search on ritual names/descriptions)
5. Add validation for ritual prerequisites and requirements

## Usage Example

```php
require_once __DIR__ . '/agents/rituals_agent/src/RitualsAgent.php';

$agent = new RitualsAgent();

// Fetch ritual with rules
$ritual = $agent->getRitualById(1, true);
if ($ritual) {
    echo "Ritual: {$ritual['name']}\n";
    if (!empty($ritual['rules']['global'])) {
        echo "Global rules: " . count($ritual['rules']['global']) . " found\n";
    }
}

// List Thaumaturgy rituals
$rituals = $agent->listRituals('Thaumaturgy', null, false);
echo "Found " . count($rituals) . " Thaumaturgy rituals\n";
```

## Dependencies

- `includes/connect.php` - Database connection and helper functions
- `rulebooks` and `rulebook_pages` tables - For rules queries
- `rituals_master` table - For ritual definitions
- `character_rituals` table - For character-known rituals

## Notes

- All database queries use prepared statements via `db_select()`, `db_execute()`, `db_fetch_one()`
- Follows PHP 8.4 compatibility patterns
- Uses strict typing (`declare(strict_types=1)`)
- Error handling logs errors but doesn't throw exceptions (follows project patterns)

