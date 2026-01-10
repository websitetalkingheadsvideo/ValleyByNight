# Agents Directory - Agent Guide

## Package Identity

**Purpose**: Specialized agent modules for game mechanics (abilities, disciplines, paths, rituals, laws, etc.)  
**Tech**: PHP modules, some with Node.js MCP servers, MySQL integration  
**Location**: `agents/` directory with subdirectories per agent

## Setup & Run

### Agent Structure
Each agent follows a common structure:
```
agents/<agent_name>/
├── index.php              # Entry point
├── config/                # Configuration files
│   ├── config.json
│   └── config.php
├── src/                   # Source PHP files
├── tests/                 # Test files (if any)
├── README.md              # Agent-specific documentation
└── (agent-specific files)
```

### Development
```bash
# No special setup - each agent is self-contained
# Some agents have Node.js dependencies (e.g., laws_agent)
cd agents/laws_agent/scripts
npm install  # If package.json exists
```

## Patterns & Conventions

### Agent Types

#### 1. Display Agents (View-Only)
- **ability_agent**: Displays character abilities
- **paths_agent**: Displays character paths
- **discipline_agent**: Displays character disciplines
- **rituals_agent**: Displays rituals

**Pattern**:
```php
<?php
require_once __DIR__ . '/../../includes/connect.php';
// Display logic using database queries
?>
```

#### 2. Management Agents (CRUD)
- **character_agent**: Character management and reports
- **boon_agent**: Boon tracking and management
- **coterie_agent**: Coterie management

**Pattern**: Similar to admin API pattern (see `admin/AGENTS.md`)

#### 3. Specialized Agents
- **laws_agent**: Legal system with MCP server (Node.js)
- **map_agent**: Interactive map display
- **style_agent**: Style guide and documentation

### Naming Conventions
- **Display Files**: `<agent>_display.php` or `api_view_<resource>.php`
  - ✅ Example: `abilities_display.php`, `api_view_ability.php`
- **Config Files**: `config/config.php` and `config/config.json`
- **Source Files**: `src/<module>.php`
- **Test Files**: `tests/<module>_test.php`

### Configuration Pattern
Each agent has a `config/` directory:
```php
// config/config.php
<?php
$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
?>
```

### Database Integration
Agents use the root database connection:
```php
require_once __DIR__ . '/../../includes/connect.php';
// $conn available
```

## NPC Pressure System

### Overview

`current_pressure_level` is a social stress indicator for NPCs, representing political strain, social isolation, and breakdown of Camarilla norms — not insanity.

**Range**: 0–5

This value is authoritative, deterministic, and Storyteller-controlled. Agents read this value to adjust behavior; they do not change it autonomously unless explicitly permitted.

### Pressure Levels & Meaning

| Level | State | Description |
|-------|-------|-------------|
| 0–1 | Stable | NPC operates normally within Camarilla social economy |
| 2–3 | Strained | NPC is defensive, transactional, and increasingly selective |
| 4 | Unstable | NPC refuses boons, burns bridges, and becomes politically volatile |
| 5 | Rupture | NPC is socially ostracized; allies disengage; political exile emerges |

Pressure represents loss of social coping capacity, not loss of reason.

### Agent Responsibilities

#### Dialogue Agent

Modifies tone, options, and willingness to negotiate

**At >= 4:**
- Suppress boon negotiation branches
- Introduce confrontational or dismissive responses
- Enable ultimatums and social withdrawal

**At 5:**
- Dialogue becomes minimal, hostile, or purely transactional

#### Boon Agent

Governs participation in the boon economy

**At >= 4:**
- NPC refuses incoming boons
- NPC may still demand boons

**At 5:**
- NPC never initiates boons
- Only reacts under existential threat

#### Rumor Agent

Uses pressure as a sentiment modifier

**At >= 4:**
- Generate rumors framing NPC as dangerous, unstable, or radioactive
- Shift perception from political to social liability

**At 5:**
- Enable blacklisting and silent avoidance behaviors

#### Influence Agent

Converts sustained high pressure into:
- Gradual influence decay
- Reduced effectiveness of social actions
- Influence loss should be slow and visible, not sudden

#### Laws Agent

Ensures behavior escalation remains within canon rules

- Pressure escalation cannot force violations of core Laws
- Frenzy, derangements, or supernatural loss of control are out of scope

### Design Rules

**Pressure should rise only from clear, understandable causes:**
- Goal obstruction
- Public humiliation
- Clan pressure without support
- Unresolved boons or political traps

**Pressure must never rise from:**
- Random dice outcomes
- Player annoyance
- Single bad interactions

### Recovery & Decay

Pressure may decrease through:
- Public restoration of status
- Successful completion of primary goals
- Clan intervention
- PCs taking political risks on the NPC's behalf

Recovery should be difficult but possible, enabling long-form narrative arcs.

## Touch Points / Key Files

### Major Agents

#### Character Agent
- **Entry**: `agents/character_agent/characters.php`
- **API**: `agents/character_agent/api_get_report.php`
- **Config**: `agents/character_agent/config/config.php`
- **MCP Server**: `agents/character_agent/server.php` (if applicable)

#### Laws Agent
- **Entry**: `agents/laws_agent/index.php`
- **API**: `agents/laws_agent/api.php`
- **MCP Server**: `agents/laws_agent/scripts/mcp_laws_agent_v2.js` (Node.js)
- **Knowledge Base**: `agents/laws_agent/knowledge-base/*.md`

#### Discipline Agent
- **Entry**: `agents/discipline_agent/discipline_agent.json` (data file)
- **Display**: `agents/discipline_agent/src/discipline_display.php`
- **Tests**: `agents/discipline_agent/tests/`

#### Boon Agent
- **Entry**: `agents/boon_agent/viewer.php`
- **API**: `agents/boon_agent/api_get_boon_report.php`
- **Reports**: `agents/boon_agent/reports/`

### Agent-Specific READMEs
Each agent has its own README.md with specific documentation:
- `agents/ability_agent/README.md`
- `agents/discipline_agent/README.md`
- `agents/laws_agent/README.md`
- `agents/map_agent/README.md`
- `agents/paths_agent/README.md`
- `agents/rituals_agent/README.md`
- `agents/style_agent/README.md`

## JIT Index Hints

```bash
# Find agent entry points
rg -n "index\.php\|.*_display\.php" agents/

# Find agent API endpoints
rg -n "api_" agents/

# Find agent config files
find agents/ -name "config.php" -o -name "config.json"

# Find MCP servers (Node.js)
find agents/ -name "mcp_*.js" -o -name "server.php"

# Find agent tests
find agents/ -path "*/tests/*.php"
```

## Common Gotchas

- **Path Prefixes**: Agents are 2+ levels deep - use `__DIR__ . '/../../includes/` for root includes
- **MCP Servers**: Some agents (laws_agent) have Node.js MCP servers - check for `package.json`
- **Config Loading**: Always load config from `config/config.php`, not directly from JSON
- **Database Queries**: Use prepared statements via root `$conn` connection
- **Agent Isolation**: Each agent is self-contained - don't cross-reference agent internals

## Pre-PR Checks

```bash
# Verify PHP syntax in all agents
find agents/ -name "*.php" -exec php -l {} \;

# Check for prepared statements
rg -n "mysqli_query.*\$" agents/ | grep -v "prepare"

# Verify config files exist for agents with config/
find agents/ -type d -name "config" -exec sh -c 'test -f "$1/config.php" || test -f "$1/config.json"' _ {} \;
```
