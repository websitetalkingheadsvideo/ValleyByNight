# Discipline Agent

The Discipline Agent validates and lists character disciplines for the Valley by Night project.

## ⚠️ CRITICAL: No Paths or Rituals

**This agent ONLY handles innate disciplines. It does NOT handle paths or rituals.**

- **Paths** are handled by [PathsAgent](../paths_agent/README.md)
- **Rituals** are handled by [RitualsAgent](../rituals_agent/README.md)
- **This agent** handles only innate disciplines from the `character_disciplines` table

## Purpose

The Discipline Agent provides:

1. **Discipline Listing** - List a character's innate disciplines (excludes paths/rituals)
2. **Dot Range Validation** - Validate discipline dots are within allowed range (0-5)
3. **Clan Access Validation** - Validate clan discipline access rules (in-clan vs out-of-clan)
4. **Power Eligibility Validation** - Validate that requested powers are eligible given discipline dots

## Installation

The agent is already integrated into the project. No additional installation is required.

## Usage

### Basic Usage

```php
require_once __DIR__ . '/agents/discipline_agent/src/DisciplineAgent.php';

// Create agent instance (uses default DB connection)
$agent = new DisciplineAgent();

// Or pass your own DB connection
$agent = new DisciplineAgent($conn);
```

### List Character Disciplines

```php
$result = $agent->listCharacterDisciplines($characterId);

// Returns:
// [
//     'disciplines' => [
//         [
//             'discipline_name' => 'Celerity',
//             'level' => 3,
//             'powers' => [...],
//             'power_count' => 5
//         ],
//         ...
//     ],
//     'summary' => [
//         'total_disciplines' => 3,
//         'total_powers' => 12,
//         'character_id' => 1
//     ]
// ]
```

### Validate Discipline Dots

```php
$updates = [
    'Celerity' => 3,
    'Potence' => 5,
    'Presence' => 0
];

$result = $agent->validateDisciplineDots($characterId, $updates);

// Returns:
// [
//     'isValid' => true,
//     'errors' => [],
//     'warnings' => []
// ]
```

### Validate Clan Access

```php
$result = $agent->validateClanDisciplineAccess($characterId, 'Celerity');

// Returns:
// [
//     'hasAccess' => true,
//     'isInClan' => true,
//     'restrictions' => []
// ]
```

### Validate Power Eligibility

```php
$requestedPowers = [
    'Celerity' => ['Quickness', 'Sprint'],
    'Potence' => ['Lethal Body']
];

$result = $agent->validateDisciplinePowers($characterId, $requestedPowers);

// Returns:
// [
//     'isValid' => true,
//     'errors' => [],
//     'eligiblePowers' => [...]
// ]
```

## Configuration

Configuration is stored in `config/settings.json`:

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
    "Brujah": ["Celerity", "Potence", "Presence"],
    "Gangrel": ["Animalism", "Fortitude", "Protean"],
    ...
  },
  "exclusion_patterns": {
    "path_prefixes": ["Path of"],
    "exclude_tables": ["character_paths", "character_rituals"]
  }
}
```

## Path/Ritual Exclusion

The agent uses multiple layers to ensure paths and rituals are excluded:

1. **Repository Level**: `DisciplineRepository` filters out any discipline names matching path patterns
2. **Validation Level**: `DisciplineValidator` rejects any discipline name that is a path/ritual
3. **Agent Level**: All public methods explicitly filter results

### Exclusion Patterns

- Discipline names starting with "Path of" are excluded
- Disciplines found in `character_paths` table are excluded
- Disciplines found in `character_rituals` table (as ritual types) are excluded

## Database Tables

The agent uses these tables:

- `character_disciplines` - Character discipline dots (innate disciplines only)
- `character_discipline_powers` - Character discipline powers
- `characters` - Character data (for clan lookup)

**The agent does NOT query:**
- `character_paths` (handled by PathsAgent)
- `character_rituals` (handled by RitualsAgent)

## Error Codes

The agent returns structured errors with codes:

- `INVALID_DISCIPLINE_TYPE` - Discipline is a path/ritual, not innate
- `INVALID_DOT_TYPE` - Dots must be integer
- `DOTS_BELOW_MINIMUM` - Dots below minimum (0)
- `DOTS_ABOVE_MAXIMUM` - Dots above maximum (5)
- `NO_DISCIPLINE_DOTS` - Character has no dots in discipline
- `POWER_NOT_FOUND` - Power does not exist for discipline
- `POWER_LEVEL_TOO_HIGH` - Power level exceeds discipline dots
- `OUT_OF_CLAN_RESTRICTED` - Out-of-clan discipline is restricted
- `OUT_OF_CLAN_WARNING` - Out-of-clan discipline (may have restrictions)

## Testing

Run unit tests:

```bash
php agents/discipline_agent/tests/DisciplineAgentTest.php
```

Run integration tests:

```bash
php agents/discipline_agent/tests/test_integration.php
```

## API Reference

### `listCharacterDisciplines(int $characterId): array`

List all innate disciplines for a character.

**Returns:** Array with `disciplines` and `summary` keys.

### `validateDisciplineDots(int $characterId, array $updates): array`

Validate discipline dot ranges.

**Parameters:**
- `$updates` - Array of `discipline_name => level` pairs

**Returns:** Array with `isValid`, `errors`, and `warnings` keys.

### `validateClanDisciplineAccess(int $characterId, string $disciplineName): array`

Validate clan access for a discipline.

**Returns:** Array with `hasAccess`, `isInClan`, and `restrictions` keys.

### `validateDisciplinePowers(int $characterId, array $requestedPowers): array`

Validate power eligibility.

**Parameters:**
- `$requestedPowers` - Array of `discipline_name => [power_names]` pairs

**Returns:** Array with `isValid`, `errors`, and `eligiblePowers` keys.

## Related Agents

- [PathsAgent](../paths_agent/README.md) - Handles Thaumaturgy paths and other paths
- [RitualsAgent](../rituals_agent/README.md) - Handles rituals (Thaumaturgy, Necromancy, etc.)
- [AbilityAgent](../ability_agent/README.md) - Handles character abilities

## License

Part of the Valley by Night project.

