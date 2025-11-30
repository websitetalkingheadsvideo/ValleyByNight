# MCP (Modular Content Pack) Usage Guide

This document explains how to use the MCP system to discover, load, and work with Modular Content Packs in the VbN project.

## Overview

MCPs (Modular Content Packs) are organized collections of documentation, rules, prompts, and assets that can be discovered and loaded through the database. The first MCP is the **Style Agent MCP**, which contains the complete Valley by Night Art Bible.

## Database Schema

### mcp_style_packs Table

Main registry table for MCP packs:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Primary key |
| `name` | VARCHAR(255) | Display name of the MCP |
| `slug` | VARCHAR(100) | Unique identifier (e.g., "style_agent_mcp") |
| `version` | VARCHAR(50) | Version number (e.g., "1.0.0") |
| `description` | TEXT | Description of the MCP contents |
| `filesystem_path` | VARCHAR(500) | Relative path from project root (e.g., "agents/style_agent") |
| `enabled` | BOOLEAN | Whether the MCP is active |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |
| `last_updated` | TIMESTAMP | Last content update timestamp |

### mcp_style_chapters Table (Optional)

Chapter-level metadata for MCPs:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Primary key |
| `mcp_pack_id` | INT | Foreign key to mcp_style_packs |
| `chapter_name` | VARCHAR(255) | Display name of the chapter |
| `chapter_file` | VARCHAR(500) | Filename of the chapter file |
| `chapter_number` | INT | Chapter number/order |
| `description` | TEXT | Chapter description |
| `tags` | VARCHAR(500) | Comma-separated tags |
| `display_order` | INT | Display order |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

## Basic Usage

### 1. Discover Available MCPs

Query the database to find all enabled MCPs:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

// Get all enabled MCPs
$query = "SELECT id, name, slug, version, description, filesystem_path 
          FROM mcp_style_packs 
          WHERE enabled = 1 
          ORDER BY name";

$result = mysqli_query($conn, $query);
$mcps = [];

while ($row = mysqli_fetch_assoc($result)) {
    $mcps[] = $row;
}

// Display MCPs
foreach ($mcps as $mcp) {
    echo "MCP: {$mcp['name']} (v{$mcp['version']})\n";
    echo "Path: {$mcp['filesystem_path']}\n";
    echo "Description: {$mcp['description']}\n\n";
}
?>
```

### 2. Load a Specific MCP by Slug

Load a specific MCP using its slug:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

$slug = 'style_agent_mcp';

// Get MCP metadata
$query = "SELECT * FROM mcp_style_packs WHERE slug = ? AND enabled = 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mcp = mysqli_fetch_assoc($result);

if ($mcp) {
    $mcp_path = __DIR__ . '/' . $mcp['filesystem_path'];
    
    // Verify path exists
    if (is_dir($mcp_path)) {
        echo "MCP found: {$mcp['name']}\n";
        echo "Version: {$mcp['version']}\n";
        echo "Path: {$mcp_path}\n";
    } else {
        echo "Error: MCP path does not exist: {$mcp_path}\n";
    }
} else {
    echo "Error: MCP not found or disabled\n";
}
?>
```

### 3. Load MCP Documentation

Load specific documentation files from an MCP:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

function load_mcp_file($slug, $filename) {
    global $conn;
    
    // Get MCP metadata
    $query = "SELECT filesystem_path FROM mcp_style_packs WHERE slug = ? AND enabled = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $mcp = mysqli_fetch_assoc($result);
    
    if (!$mcp) {
        return null;
    }
    
    $file_path = __DIR__ . '/' . $mcp['filesystem_path'] . '/' . $filename;
    
    if (file_exists($file_path)) {
        return file_get_contents($file_path);
    }
    
    return null;
}

// Load README
$readme = load_mcp_file('style_agent_mcp', 'README.md');
if ($readme) {
    echo $readme;
}

// Load RULES
$rules = load_mcp_file('style_agent_mcp', 'RULES.md');
if ($rules) {
    echo $rules;
}

// Load PROMPTS
$prompts = load_mcp_file('style_agent_mcp', 'PROMPTS.md');
if ($prompts) {
    echo $prompts;
}
?>
```

### 4. Load Art Bible Chapters

Load specific Art Bible chapters:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

function load_art_bible_chapter($chapter_file) {
    $mcp_path = __DIR__ . '/agents/style_agent/docs/' . $chapter_file;
    
    if (file_exists($mcp_path)) {
        return file_get_contents($mcp_path);
    }
    
    return null;
}

// Load Portrait System chapter
$portrait_rules = load_art_bible_chapter('Art_Bible_I_PORTRAIT_SYSTEM__.md');
if ($portrait_rules) {
    echo $portrait_rules;
}

// Load Cinematic System chapter
$cinematic_rules = load_art_bible_chapter('Art_Bible_II_CINEMATIC_SYSTEM__.md');
if ($cinematic_rules) {
    echo $cinematic_rules;
}
?>
```

### 5. Query Chapter Metadata

Get chapter information from the database:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

$mcp_slug = 'style_agent_mcp';

// Get MCP ID
$mcp_query = "SELECT id FROM mcp_style_packs WHERE slug = ?";
$stmt = mysqli_prepare($conn, $mcp_query);
mysqli_stmt_bind_param($stmt, 's', $mcp_slug);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mcp = mysqli_fetch_assoc($result);

if ($mcp) {
    // Get chapters
    $chapters_query = "SELECT chapter_name, chapter_file, chapter_number, description, tags 
                       FROM mcp_style_chapters 
                       WHERE mcp_pack_id = ? 
                       ORDER BY display_order";
    $chapter_stmt = mysqli_prepare($conn, $chapters_query);
    mysqli_stmt_bind_param($chapter_stmt, 'i', $mcp['id']);
    mysqli_stmt_execute($chapter_stmt);
    $chapters_result = mysqli_stmt_get_result($chapter_stmt);
    
    $chapters = [];
    while ($row = mysqli_fetch_assoc($chapters_result)) {
        $chapters[] = $row;
    }
    
    // Display chapters
    foreach ($chapters as $chapter) {
        echo "Chapter {$chapter['chapter_number']}: {$chapter['chapter_name']}\n";
        echo "File: {$chapter['chapter_file']}\n";
        echo "Tags: {$chapter['tags']}\n";
        echo "Description: {$chapter['description']}\n\n";
    }
}
?>
```

## Advanced Usage

### 6. MCP Helper Class

Create a reusable helper class:

```php
<?php
class MCPLoader {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getMCP($slug) {
        $query = "SELECT * FROM mcp_style_packs WHERE slug = ? AND enabled = 1";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $slug);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    public function getMCPPath($slug) {
        $mcp = $this->getMCP($slug);
        if ($mcp) {
            return __DIR__ . '/' . $mcp['filesystem_path'];
        }
        return null;
    }
    
    public function loadMCPFile($slug, $filename) {
        $mcp_path = $this->getMCPPath($slug);
        if ($mcp_path) {
            $file_path = $mcp_path . '/' . $filename;
            if (file_exists($file_path)) {
                return file_get_contents($file_path);
            }
        }
        return null;
    }
    
    public function getChapters($slug) {
        $mcp = $this->getMCP($slug);
        if (!$mcp) {
            return [];
        }
        
        $query = "SELECT * FROM mcp_style_chapters 
                  WHERE mcp_pack_id = ? 
                  ORDER BY display_order";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $mcp['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $chapters = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $chapters[] = $row;
        }
        
        return $chapters;
    }
    
    public function getAllEnabledMCPs() {
        $query = "SELECT * FROM mcp_style_packs WHERE enabled = 1 ORDER BY name";
        $result = mysqli_query($this->conn, $query);
        
        $mcps = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $mcps[] = $row;
        }
        
        return $mcps;
    }
}

// Usage
require_once __DIR__ . '/includes/connect.php';
$mcp_loader = new MCPLoader($conn);

// Get Style Agent MCP
$style_mcp = $mcp_loader->getMCP('style_agent_mcp');
if ($style_mcp) {
    echo "Found: {$style_mcp['name']} v{$style_mcp['version']}\n";
}

// Load README
$readme = $mcp_loader->loadMCPFile('style_agent_mcp', 'README.md');

// Get chapters
$chapters = $mcp_loader->getChapters('style_agent_mcp');
foreach ($chapters as $chapter) {
    echo "Chapter: {$chapter['chapter_name']}\n";
}

// Get all MCPs
$all_mcps = $mcp_loader->getAllEnabledMCPs();
foreach ($all_mcps as $mcp) {
    echo "MCP: {$mcp['name']}\n";
}
?>
```

### 7. Search Chapters by Tags

Search for chapters using tags:

```php
<?php
require_once __DIR__ . '/includes/connect.php';

function searchChaptersByTag($tag) {
    global $conn;
    
    $query = "SELECT c.*, p.name as mcp_name, p.slug as mcp_slug 
              FROM mcp_style_chapters c
              JOIN mcp_style_packs p ON c.mcp_pack_id = p.id
              WHERE p.enabled = 1 
              AND (c.tags LIKE ? OR c.chapter_name LIKE ?)
              ORDER BY c.display_order";
    
    $search_term = "%{$tag}%";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $chapters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $chapters[] = $row;
    }
    
    return $chapters;
}

// Search for portrait-related chapters
$portrait_chapters = searchChaptersByTag('portrait');
foreach ($portrait_chapters as $chapter) {
    echo "Found in {$chapter['mcp_name']}: {$chapter['chapter_name']}\n";
}
?>
```

## Pattern for Future MCPs

When creating a new MCP:

1. **Create the MCP folder structure** following the Style Agent MCP pattern:
   - `README.md` - Overview and usage
   - `RULES.md` - Key rules/constraints (if applicable)
   - `PROMPTS.md` - Prompt templates (if applicable)
   - `INDEX.md` - Chapter index (if applicable)
   - `docs/` - Main documentation files
   - `indexes/` - Index files (if applicable)

2. **Register in the database**:
   ```sql
   INSERT INTO mcp_style_packs (name, slug, version, description, filesystem_path, enabled)
   VALUES ('Your MCP Name', 'your_mcp_slug', '1.0.0', 'Description', 'path/to/mcp', 1);
   ```

3. **Optionally register chapters**:
   ```sql
   INSERT INTO mcp_style_chapters (mcp_pack_id, chapter_name, chapter_file, chapter_number, description, tags, display_order)
   VALUES (?, ?, ?, ?, ?, ?, ?);
   ```

4. **Follow the same usage patterns** shown in this document

## API Endpoints (Future)

Future API endpoints could include:

- `GET /api/mcps` - List all enabled MCPs
- `GET /api/mcps/{slug}` - Get specific MCP metadata
- `GET /api/mcps/{slug}/chapters` - Get chapters for an MCP
- `GET /api/mcps/{slug}/file/{filename}` - Get a specific file from an MCP

## Security Considerations

- Always validate MCP paths to prevent directory traversal
- Use prepared statements for all database queries
- Verify file paths exist before reading
- Sanitize user input when searching
- Check `enabled` flag before loading MCPs

## Examples

See the Style Agent MCP implementation:
- Location: `agents/style_agent/`
- Database entry: `mcp_style_packs` table, slug: `style_agent_mcp`
- Documentation: `agents/style_agent/README.md`

## Troubleshooting

### MCP Not Found
- Check that the MCP is enabled in the database
- Verify the slug is correct
- Check that the filesystem path exists

### File Not Found
- Verify the file path relative to the MCP root
- Check file permissions
- Ensure the file exists in the expected location

### Database Connection Issues
- Verify database connection in `includes/connect.php`
- Check that tables exist (`mcp_style_packs`, `mcp_style_chapters`)
- Run the migration script if tables don't exist

## Version History

- **1.0.0** (2025-01-26) - Initial MCP system with Style Agent MCP

