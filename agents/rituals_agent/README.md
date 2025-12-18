# Rituals Agent

The Rituals Agent provides access to ritual definitions, character-known rituals, and ritual rules from the Rules database. It combines data from `rituals_master`, `character_rituals`, and the `rulebooks`/`rulebook_pages` tables.

## Key Principles

- **Read-only**: No writes to character tables
- **Runtime composition**: Rules are attached at response time, never stored in ritual records
- **Separation of concerns**: Ritual definitions, character state, and rules are kept separate

## Installation

The agent is automatically available when included in the project. It uses the standard database connection from `includes/connect.php`.

## Usage

### Basic Usage

```php
require_once __DIR__ . '/agents/rituals_agent/src/RitualsAgent.php';

// Create agent instance (uses default DB connection)
$agent = new RitualsAgent();

// Or pass a custom DB connection
$agent = new RitualsAgent($customDbConnection);
```

### Fetch Ritual by ID

```php
// Fetch ritual with rules attached (default)
$ritual = $agent->getRitualById(123, true);

// Fetch ritual without rules
$ritual = $agent->getRitualById(123, false);
```

### Fetch Ritual by Type, Level, and Name

```php
$ritual = $agent->getRitual('Thaumaturgy', 1, 'Communicate with Sire', true);
```

### List Rituals with Filters

```php
// List all Thaumaturgy rituals
$rituals = $agent->listRituals('Thaumaturgy', null, false);

// List level 1 rituals of any type
$rituals = $agent->listRituals(null, 1, false);

// List with rules attached (slower, use sparingly)
$rituals = $agent->listRituals('Necromancy', null, true);
```

### Get Character's Known Rituals

```php
// Get all rituals known by character ID 42
$rituals = $agent->getKnownRitualsForCharacter(42, true);
```

### Get Ritual Rules

```php
// Get global and Thaumaturgy-specific rules
$rules = $agent->getRitualRules('Thaumaturgy');

// Get only global rules
$rules = $agent->getRitualRules(null);
```

## Function Signatures

### `getRitualById(int $ritualId, bool $includeRules = true): ?array`

Fetches a ritual by its database ID.

**Parameters:**
- `$ritualId`: The ritual's database ID
- `$includeRules`: Whether to attach global and tradition-specific rules (default: true)

**Returns:**
- Array with ritual data and optional `rules` field, or `null` if not found

### `getRitual(string $type, int $level, string $name, bool $includeRules = true): ?array`

Fetches a ritual by its type, level, and name (composite key).

**Parameters:**
- `$type`: Ritual type (e.g., "Thaumaturgy", "Necromancy", "Assamite")
- `$level`: Ritual level (1-5)
- `$name`: Ritual name
- `$includeRules`: Whether to attach rules (default: true)

**Returns:**
- Array with ritual data and optional `rules` field, or `null` if not found

### `listRituals(?string $type = null, ?int $level = null, bool $includeRules = false, int $limit = 100, int $offset = 0): array`

Lists rituals with optional filters.

**Parameters:**
- `$type`: Filter by ritual type (optional)
- `$level`: Filter by ritual level (optional)
- `$includeRules`: Whether to attach rules (default: false for performance)
- `$limit`: Maximum number of results (default: 100)
- `$offset`: Offset for pagination (default: 0)

**Returns:**
- Array of ritual data arrays

### `getKnownRitualsForCharacter(int $characterId, bool $includeRules = true): array`

Returns all rituals known by a specific character.

**Parameters:**
- `$characterId`: The character's database ID
- `$includeRules`: Whether to attach rules (default: true)

**Returns:**
- Array of ritual data arrays with character-specific fields (`is_custom`)

### `getRitualRules(?string $tradition = null): array`

Returns ritual rules bundle (global and/or tradition-specific).

**Parameters:**
- `$tradition`: Tradition name for tradition-specific rules (optional)

**Returns:**
- Array with `global` and `tradition` keys, each containing arrays of rule excerpts

## Data Structures

### Ritual Array

```php
[
    'id' => 123,
    'name' => 'Communicate with Sire',
    'type' => 'Thaumaturgy',
    'level' => 1,
    'description' => 'Ritual description...',
    'system_text' => 'System mechanics...',
    'requirements' => 'Requirements...',
    'ingredients' => 'Ingredients...',
    'source' => 'Source book...',
    'created_at' => '2024-01-01 12:00:00',
    'rules' => [  // Only if includeRules=true
        'global' => [...],
        'tradition' => [...]
    ]
]
```

### Rules Array

```php
[
    'global' => [
        [
            'rulebook_id' => 1,
            'book_title' => 'Laws of the Night',
            'category' => 'Core',
            'system_type' => 'MET-VTM',
            'page_number' => 42,
            'page_text' => 'Full text...',
            'excerpt' => 'Excerpt...',
            'relevance' => 0.85
        ],
        // ... more rules
    ],
    'tradition' => [
        // Similar structure for tradition-specific rules
    ]
]
```

## Rule Attachment Behavior

Rules are attached at runtime and never stored in the ritual records. The `rules` field is added to the returned array, but the original ritual definition fields remain unchanged.

When `includeRules=true`:
- Global rules are always included (if available)
- Tradition-specific rules are included if the ritual has a `type` field
- Rules are fetched from the Rules database using full-text search
- Rules are returned as excerpts with metadata (book, page, relevance)

## Configuration

Configuration is stored in `config/settings.json`:

```json
{
  "enabled": true,
  "rules": {
    "enabled": true,
    "default_limit": 10,
    "cache_enabled": false
  },
  "rituals": {
    "default_limit": 100,
    "default_offset": 0
  }
}
```

## Database Schema

### rituals_master

- `id` (int, PK, auto_increment)
- `name` (varchar(200), UNIQUE, NOT NULL)
- `type` (varchar(32), NOT NULL, indexed)
- `level` (int, NOT NULL, indexed)
- `description` (text, NOT NULL)
- `system_text` (text, nullable)
- `requirements` (text, nullable)
- `ingredients` (text, nullable)
- `source` (varchar(100), nullable)
- `created_at` (timestamp)

### character_rituals

- `id` (int, PK, auto_increment)
- `character_id` (int, NOT NULL, indexed)
- `ritual_name` (varchar(100), NOT NULL)
- `ritual_type` (varchar(50), nullable)
- `level` (int, nullable)
- `is_custom` (tinyint(1), default 0)
- `description` (text, nullable)

**Note:** The `character_rituals` table currently uses name/type/level matching rather than a foreign key to `rituals_master.id`. The repository handles both patterns.

## Error Handling

- Functions return `null` for single-item queries when not found
- Functions return empty arrays `[]` for list queries when no results
- Database errors are logged via `error_log()` but do not throw exceptions
- Invalid parameters may result in empty results

## Performance Considerations

- Listing with `includeRules=true` can be slow for large result sets
- Rules queries use full-text search which may be slower on large databases
- Consider caching rules if frequently accessed
- Use `includeRules=false` for list operations when rules aren't needed

## Examples

### Example 1: Display Ritual with Rules

```php
$agent = new RitualsAgent();
$ritual = $agent->getRitualById(123, true);

if ($ritual) {
    echo "Ritual: {$ritual['name']}\n";
    echo "Type: {$ritual['type']} Level {$ritual['level']}\n";
    echo "Description: {$ritual['description']}\n";
    
    if (!empty($ritual['rules']['global'])) {
        echo "\nGlobal Rules:\n";
        foreach ($ritual['rules']['global'] as $rule) {
            echo "- {$rule['book_title']}, Page {$rule['page_number']}: {$rule['excerpt']}\n";
        }
    }
    
    if (!empty($ritual['rules']['tradition'])) {
        echo "\n{$ritual['type']} Rules:\n";
        foreach ($ritual['rules']['tradition'] as $rule) {
            echo "- {$rule['book_title']}, Page {$rule['page_number']}: {$rule['excerpt']}\n";
        }
    }
}
```

### Example 2: Character Ritual Sheet

```php
$agent = new RitualsAgent();
$rituals = $agent->getKnownRitualsForCharacter(42, false);

echo "Known Rituals:\n";
foreach ($rituals as $ritual) {
    $custom = $ritual['is_custom'] ? ' (Custom)' : '';
    echo "- {$ritual['name']} ({$ritual['type']} {$ritual['level']}){$custom}\n";
}
```

## Testing

See `tests/RitualsAgentTest.php` for unit tests. Run tests with:

```bash
phpunit agents/rituals_agent/tests/RitualsAgentTest.php
```

## See Also

- `rituals_agent.json` - Contract specification
- `agents/laws_agent/` - Similar agent pattern for rules database access
- `agents/boon_agent/` - Similar agent structure pattern

