# Ability Agent

The Ability Agent validates and maps ability data from external/source formats into the project's canonical ability schema. It provides validation, alias resolution, deprecation handling, and integration with the character import workflow.

## Features

- **Validates ability names and categories** against canonical definitions in the `abilities` table
- **Maps aliases** to canonical ability names (e.g., "Alertness" → "Awareness")
- **Handles deprecated abilities** with replacement suggestions
- **Derives missing categories** from canonical definitions
- **Fuzzy matching** for typo correction
- **Case and whitespace normalization**
- **Structured issue reporting** with severity levels (error, warning, info)

## What the Ability Agent Does

The Ability Agent acts as a data validation and transformation layer for character abilities during import. It:

1. **Normalizes** source ability data (handles 3 different input formats)
2. **Validates** ability names and categories against the canonical `abilities` table
3. **Maps** aliases to canonical names (configured in `config/settings.json`)
4. **Flags** deprecated abilities with replacement suggestions
5. **Reports** structured issues (unknown abilities, category mismatches, etc.)
6. **Outputs** canonical format for database storage

## Installation

The Ability Agent is automatically loaded when used in the character import workflow. No additional installation steps are required.

## Configuration

Configuration is stored in `config/settings.json`. Key settings:

```json
{
  "validation": {
    "strict_mode": false,           // If true, reject invalid abilities
    "allow_unknown": true,          // If false, reject unknown abilities
    "auto_replace_deprecated": false, // If true, auto-replace deprecated abilities
    "fuzzy_threshold": 0.8          // Similarity threshold for fuzzy matching (0.0-1.0)
  },
  "aliases": {
    "Alertness": "Awareness",       // Map alias to canonical name
    "Gunplay": "Firearms"
  },
  "deprecations": {
    "Old Ability Name": {
      "replacement": "New Ability Name",
      "category": "Physical",
      "reason": "Renamed in V5"
    }
  },
  "normalization": {
    "case_sensitive": false,        // Case-insensitive matching
    "trim_whitespace": true,        // Trim whitespace
    "fuzzy_matching": true          // Enable fuzzy matching
  }
}
```

### Adding Aliases

Edit `config/settings.json` and add entries to the `aliases` object:

```json
"aliases": {
  "Old Name": "Canonical Name",
  "Another Alias": "Another Canonical"
}
```

### Adding Deprecations

Edit `config/settings.json` and add entries to the `deprecations` object:

```json
"deprecations": {
  "Deprecated Ability": {
    "replacement": "New Ability Name",
    "category": "Physical",
    "reason": "Explanation of why it was deprecated"
  }
}
```

## Usage

### In Character Import Workflow

The Ability Agent is automatically used by `database/import_characters.php` during character import:

```php
// The importAbilities() function uses the agent internally
importAbilities($conn, $character_id, $characterData, $importIssues);
```

Issues are collected in the `$importIssues` array and reported in the import summary.

### Direct Usage

```php
require_once 'agents/ability_agent/src/AbilityAgent.php';

$agent = new AbilityAgent($conn); // Uses default config

// Validate a single ability
$result = $agent->validate([
    'name' => 'Athletics',
    'category' => 'Physical',
    'level' => 3
]);

// Map a source ability (with aliases/deprecations)
$result = $agent->map([
    'name' => 'Alertness', // Will be mapped to 'Awareness'
    'level' => 2
]);

// Process multiple abilities
$result = $agent->processAbilities([
    ['name' => 'Athletics', 'category' => 'Physical', 'level' => 3],
    ['name' => 'Alertness', 'level' => 2],
    ['name' => 'Firearms', 'category' => 'Physical', 'level' => 4]
]);

// Get canonical abilities
$physicalAbilities = $agent->getCanonicalAbilities('Physical');
$allAbilities = $agent->getCanonicalAbilities();
```

### Return Types

#### Validation Result

```php
[
    'isValid' => bool,
    'normalizedAbility' => [
        'name' => string,
        'category' => string|null,
        'level' => int,
        'specialization' => string|null
    ],
    'issues' => [
        [
            'code' => string,        // 'UNKNOWN_ABILITY', 'CATEGORY_MISMATCH', etc.
            'severity' => string,    // 'error', 'warning', 'info'
            'message' => string,
            'metadata' => array      // Additional context
        ]
    ]
]
```

#### Mapping Result

```php
[
    'canonicalAbility' => array|null, // Canonical ability object or null if unmappable
    'issues' => array                  // Same structure as validation issues
]
```

#### Process Result

```php
[
    'mappedAbilities' => array,        // Array of canonical ability objects
    'allIssues' => array,              // Aggregated issues with source references
    'summary' => [
        'total' => int,
        'valid' => int,
        'invalid' => int,
        'deprecated' => int,
        'mapped' => int,
        'unknown' => int
    ]
]
```

## Input/Output Examples

### Example 1: Valid Ability

**Input:**
```php
['name' => 'Athletics', 'category' => 'Physical', 'level' => 3]
```

**Output:**
```php
[
    'isValid' => true,
    'normalizedAbility' => [
        'name' => 'Athletics',
        'category' => 'Physical',
        'level' => 3,
        'specialization' => null
    ],
    'issues' => []
]
```

### Example 2: Alias Mapping

**Input:**
```php
['name' => 'Alertness', 'level' => 2]
```

**Output:**
```php
[
    'canonicalAbility' => [
        'name' => 'Awareness',  // Mapped from alias
        'category' => 'Optional',
        'level' => 2,
        'specialization' => null
    ],
    'issues' => [
        [
            'code' => 'ALIAS_MAPPED',
            'severity' => 'info',
            'message' => "Alias 'Alertness' mapped to canonical name 'Awareness'",
            'metadata' => [
                'source_name' => 'Alertness',
                'canonical_name' => 'Awareness',
                'confidence' => 1.0
            ]
        ]
    ]
]
```

### Example 3: Unknown Ability

**Input:**
```php
['name' => 'Nonexistent Ability', 'category' => 'Physical', 'level' => 1]
```

**Output:**
```php
[
    'isValid' => true,  // If allow_unknown = true
    'normalizedAbility' => [
        'name' => 'Nonexistent Ability',  // Used as-is
        'category' => 'Physical',
        'level' => 1,
        'specialization' => null
    ],
    'issues' => [
        [
            'code' => 'UNKNOWN_ABILITY',
            'severity' => 'warning',
            'message' => "Unknown ability: 'Nonexistent Ability' not found in canonical abilities",
            'metadata' => [
                'source_name' => 'Nonexistent Ability',
                'source_category' => 'Physical',
                'confidence' => 0.0
            ]
        ]
    ]
]
```

### Example 4: Category Mismatch

**Input:**
```php
['name' => 'Athletics', 'category' => 'Social', 'level' => 3]
```

**Output:**
```php
[
    'isValid' => true,
    'normalizedAbility' => [
        'name' => 'Athletics',
        'category' => 'Physical',  // Corrected from 'Social'
        'level' => 3,
        'specialization' => null
    ],
    'issues' => [
        [
            'code' => 'CATEGORY_MISMATCH',
            'severity' => 'warning',
            'message' => "Category mismatch: ability 'Athletics' is in category 'Physical', not 'Social'",
            'metadata' => [
                'source_name' => 'Athletics',
                'source_category' => 'Social',
                'canonical_name' => 'Athletics',
                'canonical_category' => 'Physical',
                'confidence' => 1.0
            ]
        ]
    ]
]
```

## Issue Codes

| Code | Severity | Description |
|------|----------|-------------|
| `MISSING_NAME` | error | Ability name is required but missing |
| `UNKNOWN_ABILITY` | warning/error | Ability not found in canonical definitions |
| `CATEGORY_MISMATCH` | warning | Provided category doesn't match canonical category |
| `ALIAS_MAPPED` | info | Alias was mapped to canonical name |
| `DEPRECATED_ABILITY` | warning | Ability is deprecated with replacement available |
| `FUZZY_MATCH` | info | Ability matched using fuzzy matching (typo correction) |
| `AMBIGUOUS_MAPPING` | warning | Multiple potential matches found |
| `CATEGORY_DERIVED` | info | Category was derived from canonical definition |
| `LEVEL_OUT_OF_BOUNDS` | warning | Level outside valid range for ability |

## How Import Workflow Consumes It

The character import workflow (`database/import_characters.php`) uses the Ability Agent automatically:

1. **Normalizes** source abilities from any of the 3 supported formats
2. **Processes** through AbilityAgent (validation + mapping)
3. **Stores** canonical format in `character_abilities` table
4. **Collects** issues for reporting in import summary
5. **Reports** issues in CLI/web output with summary statistics

The integration is transparent - existing import code continues to work, but now with validation and mapping.

## Running Tests

### Unit Tests

```bash
php agents/ability_agent/tests/AbilityAgentTest.php
```

Tests cover:
- Valid ability validation
- Category mismatch detection
- Deprecated ability handling
- Unknown ability flagging
- Alias mapping
- Case normalization
- Whitespace normalization
- Category derivation
- Batch processing

### Integration Test

```bash
php agents/ability_agent/tests/test_integration.php
```

Tests the full integration with character import workflow:
- Character JSON import
- Ability validation and mapping
- Database storage verification
- Issue reporting
- Cleanup

## Architecture

```
AbilityAgent (main class)
├── AbilityRepository (database queries)
│   ├── getCanonicalAbility()
│   ├── getAllCanonicalAbilities()
│   ├── searchAbilities() (fuzzy matching)
│   └── getCategoryForAbility()
├── AbilityValidator (validation logic)
│   ├── validate() (exact/fuzzy matching)
│   └── normalizeName() / normalizeCategory()
└── AbilityMapper (mapping logic)
    ├── map() (aliases + deprecations)
    ├── resolveAlias()
    ├── checkDeprecation()
    └── deriveCategory()
```

## Database Schema

### Canonical Abilities Table (`abilities`)
- `id` - Primary key
- `name` - Ability name (unique per category)
- `category` - Physical, Social, Mental, or Optional
- `display_order` - Display order within category
- `description` - Ability description
- `min_level` - Minimum valid level (default 0)
- `max_level` - Maximum valid level (default 5)

### Character Abilities Table (`character_abilities`)
- `character_id` - Foreign key to characters
- `ability_name` - Ability name (canonical)
- `ability_category` - Category (canonical)
- `level` - Ability level (1-5)
- `specialization` - Optional specialization

## Troubleshooting

### Issue: Unknown abilities not being flagged

Check `config/settings.json`:
- `allow_unknown` should be `true` to allow unknown abilities
- If `strict_mode` is `true`, unknown abilities will cause validation to fail

### Issue: Aliases not mapping

Verify:
- Alias is defined in `config/settings.json` → `aliases`
- Alias name matches exactly (case-insensitive if `case_sensitive: false`)
- JSON syntax is valid (no trailing commas)

### Issue: Deprecated abilities not being replaced

Check:
- Deprecation is defined in `config/settings.json` → `deprecations`
- `auto_replace_deprecated` is `true` for automatic replacement
- If `false`, deprecations are only flagged as warnings

### Issue: Fuzzy matching not working

Verify:
- `fuzzy_matching` is `true` in config
- `fuzzy_threshold` is set appropriately (0.7-0.9 recommended)
- Ability name is similar enough to canonical name (Levenshtein distance)

## Contributing

When adding new features:

1. Update `config/settings.json` if adding new configuration options
2. Add unit tests in `AbilityAgentTest.php`
3. Update this README with examples
4. Test integration with character import workflow

## License

Part of the Valley by Night project.

