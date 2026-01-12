# MCP Tools

Tools for managing and verifying MCP (Model Context Protocol) configuration files and directory structures.

## Tools

### check_mcp_json.php

**Purpose:** Validates the `.cursor/mcp.json` file for JSON syntax errors.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/check_mcp_json.php
```

**Output:** Displays JSON validation status and structure if valid, or error details if invalid.

**Dependencies:** None (uses PHP built-in JSON functions)

---

### fix_mcp_json_paths.php

**Purpose:** Fixes network path references in `.cursor/mcp.json` to use mapped drive `V:\`.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/fix_mcp_json_paths.php
```

**Features:**
- Creates backup of original file before modifying
- Validates JSON after modification
- Replaces various network path formats with `V:\`

**Dependencies:** None (modifies `.cursor/mcp.json` file)

**Output Files:**
- Creates backup: `.cursor/mcp.json.backup.YYYY-MM-DD_HHMMSS`

---

### verify_mcp_structure.php

**Purpose:** Verifies MCP directory structure for FTP upload (checks Style Agent structure).

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/verify_mcp_structure.php
```

**Features:**
- Checks required directories (docs, indexes, rules, prompts)
- Verifies required root files (README.md, RULES.md, PROMPTS.md, INDEX.md)
- Lists documentation and index files
- Provides FTP upload instructions

**Output:** HTML output showing verification results

**Dependencies:** None (reads filesystem structure)

---

### create_mcp_directories.php

**Purpose:** Creates missing MCP directories for Style Agent.

**Usage:**
```bash
php tools/repeatable/php/mcp-tools/create_mcp_directories.php
```

**Features:**
- Creates required directories (docs, rules, prompts)
- Verifies directory structure
- Displays current permissions

**Dependencies:** None (creates directories)

**Output:** HTML output showing created directories and verification

---

## Common Use Cases

1. **Setting up MCP structure:** Run `create_mcp_directories.php` first, then `verify_mcp_structure.php`
2. **Fixing path issues:** Run `fix_mcp_json_paths.php` when paths need updating
3. **Debugging configuration:** Run `check_mcp_json.php` to validate JSON syntax
