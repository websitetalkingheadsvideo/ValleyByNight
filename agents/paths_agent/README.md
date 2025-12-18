# Paths Agent

Paths Agent for Valley by Night provides access to path definitions, path powers, and character path ratings.

## Features

- **List paths by type**: Filter paths by type (e.g., "Necromancy", "Thaumaturgy")
- **Get path powers**: Retrieve all powers for a given path
- **Get character paths**: Get all paths known by a character with their ratings
- **Rating gate evaluation**: Determine if a character can use a path power based on rating

## Data Sources

The agent reads **only** from these database tables:
- `paths_master` - Path definitions
- `path_powers` - Path powers and their requirements
- `character_paths` - Character path ratings

## Challenge Metadata

All responses include challenge metadata (TM-03) with:
- Challenge code and name
- Sources read (subset of allowed sources)
- Gating type (rating-only)
- For `canUsePathPower`: Additional fields including `requiredRating`, `characterRating`, `powerId`, `pathId`

## Usage

### Direct PHP Usage

```php
require_once 'agents/paths_agent/src/PathsAgent.php';

$agent = new PathsAgent();

// List paths by type
$result = $agent->listPathsByType('Necromancy', 10, 0);

// Get powers for a path
$powers = $agent->getPathPowers($pathId);

// Get character paths
$characterPaths = $agent->getCharacterPaths($characterId);

// Check if character can use a power
$canUse = $agent->canUsePathPower($characterId, $powerId);
```

### API Endpoint

Access via `admin/api_paths.php` with admin authentication:

- `?action=list_paths&type=Necromancy` - List paths
- `?action=get_powers&path_id=4` - Get path powers
- `?action=get_character_paths&character_id=26` - Get character paths
- `?action=can_use_power&character_id=26&power_id=16` - Check power usage

## Testing

Run integration tests:
```bash
php agents/paths_agent/tests/test_integration.php
```

Run unit tests:
```bash
php agents/paths_agent/tests/PathsAgentTest.php
```

## Constraints

- ✅ Read-only access to `paths_master`, `path_powers`, `character_paths`
- ✅ No ritual logic included
- ✅ Challenge metadata in all responses
- ✅ Rating gate only (no cooldowns, costs, etc.)

## Implementation

- **Task**: TM-03: Paths Agent Core Implementation
- **Dependencies**: TM-02 (paths database completion)
- **Status**: Complete

