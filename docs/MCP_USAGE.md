# MCP (Modular Content Pack) Usage Guide

This document explains how to discover and load Modular Content Packs in the VbN project using the shared Supabase client.

## Overview

MCPs are organized collections of documentation, rules, prompts, and assets. The first MCP is the Style Agent MCP, which contains the Valley by Night Art Bible.

## Registry Tables

### `mcp_style_packs`

Main registry table for MCP packs:

| Field | Purpose |
|-------|---------|
| `id` | Primary key |
| `name` | Display name |
| `slug` | Unique identifier |
| `version` | Version string |
| `description` | Short description |
| `filesystem_path` | Relative project path |
| `enabled` | Whether the pack is active |

### `mcp_style_chapters`

Optional chapter metadata:

| Field | Purpose |
|-------|---------|
| `mcp_pack_id` | Parent MCP pack |
| `chapter_name` | Display name |
| `chapter_file` | Filename |
| `chapter_number` | Sort order |
| `description` | Summary |
| `tags` | Search tags |
| `display_order` | UI ordering |

## Basic Usage

### Discover enabled MCPs

```php
<?php
require_once __DIR__ . '/includes/supabase_client.php';

$mcps = supabase_table_get('mcp_style_packs', [
    'select' => 'id,name,slug,version,description,filesystem_path',
    'enabled' => 'eq.true',
    'order' => 'name.asc'
]);

foreach ($mcps as $mcp) {
    echo "MCP: {$mcp['name']} (v{$mcp['version']})\n";
    echo "Path: {$mcp['filesystem_path']}\n";
    echo "Description: {$mcp['description']}\n\n";
}
```

### Load one MCP by slug

```php
<?php
require_once __DIR__ . '/includes/supabase_client.php';

$rows = supabase_table_get('mcp_style_packs', [
    'select' => 'id,name,version,filesystem_path',
    'slug' => 'eq.style_agent_mcp',
    'enabled' => 'eq.true',
    'limit' => '1'
]);

$mcp = $rows[0] ?? null;

if ($mcp !== null) {
    $mcpPath = __DIR__ . '/' . $mcp['filesystem_path'];
    if (is_dir($mcpPath)) {
        echo "MCP found: {$mcp['name']}\n";
        echo "Version: {$mcp['version']}\n";
        echo "Path: {$mcpPath}\n";
    }
}
```

### Load MCP files from disk

```php
<?php
function load_mcp_file(array $mcp, string $filename): ?string
{
    $filePath = __DIR__ . '/' . $mcp['filesystem_path'] . '/' . $filename;

    if (!is_file($filePath)) {
        return null;
    }

    $contents = file_get_contents($filePath);
    return $contents === false ? null : $contents;
}
```

### Load chapter metadata

```php
<?php
require_once __DIR__ . '/includes/supabase_client.php';

$mcpRows = supabase_table_get('mcp_style_packs', [
    'select' => 'id',
    'slug' => 'eq.style_agent_mcp',
    'enabled' => 'eq.true',
    'limit' => '1'
]);

$mcp = $mcpRows[0] ?? null;
$chapters = [];

if ($mcp !== null) {
    $chapters = supabase_table_get('mcp_style_chapters', [
        'select' => 'chapter_name,chapter_file,chapter_number,description,tags',
        'mcp_pack_id' => 'eq.' . (int) $mcp['id'],
        'order' => 'display_order.asc'
    ]);
}
```

## Helper Pattern

```php
<?php
class MCPLoader
{
    public function getMcp(string $slug): ?array
    {
        $rows = supabase_table_get('mcp_style_packs', [
            'select' => 'id,name,slug,version,filesystem_path',
            'slug' => 'eq.' . $slug,
            'enabled' => 'eq.true',
            'limit' => '1'
        ]);

        return $rows[0] ?? null;
    }

    public function getChapters(int $mcpId): array
    {
        return supabase_table_get('mcp_style_chapters', [
            'select' => 'chapter_name,chapter_file,chapter_number,description,tags',
            'mcp_pack_id' => 'eq.' . $mcpId,
            'order' => 'display_order.asc'
        ]);
    }
}
```

## Future MCP Pattern

1. Create the MCP folder structure.
2. Register the pack in `mcp_style_packs`.
3. Optionally register chapter metadata in `mcp_style_chapters`.
4. Load metadata with Supabase and load file contents from disk.

## Security Notes

- Validate MCP paths before reading files.
- Keep MCP metadata reads on the shared Supabase client.
- Verify `enabled` before exposing a pack.
- Sanitize any user-provided search input before using it in filters.

## Troubleshooting

### MCP Not Found

- Check that the pack is enabled in `mcp_style_packs`.
- Verify the slug.
- Check that the filesystem path exists.

### File Not Found

- Verify the file path relative to the MCP root.
- Check file permissions.
- Ensure the file exists in the expected location.

### Registry Issues

- Verify Supabase environment variables are loaded.
- Check that `mcp_style_packs` and `mcp_style_chapters` exist in the project schema.

