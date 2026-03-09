# Style Agent MCP (Modular Content Pack)

## Overview

The Style Agent MCP packages the complete **Valley by Night Art Bible** - a comprehensive visual style guide for the VbN project. This MCP provides all rules, constraints, aesthetic guidelines, and prompt templates needed for generating and validating art assets that align with the gothic-noir, Phoenix 1994 aesthetic.

## What This MCP Contains

- **Complete Art Bible Documentation** - All 11 chapters covering portrait systems, cinematic rules, location architecture, 3D assets, UI/web art, storyboards, marketing materials, floorplans, naming conventions, and integration guidelines
- **Distilled Rules** - Key aesthetic constraints and do/don't guidelines in `RULES.md`
- **Reusable Prompts** - All prompt templates extracted and organized in `PROMPTS.md`
- **Chapter Index** - Quick reference guide to all chapters and their purposes in `INDEX.md`

## File Structure

```
agents/style_agent/
├── README.md                    # This file
├── RULES.md                     # Distilled aesthetic rules and constraints
├── PROMPTS.md                   # Reusable prompt templates
├── INDEX.md                     # Chapter index and usage guide
├── docs/                        # All Art Bible chapter files
│   ├── Valley_by_Night_Art_Bible_Master_Edition.md
│   ├── Art_Bible_I_PORTRAIT_SYSTEM__.md
│   ├── Art_Bible_II_CINEMATIC_SYSTEM__.md
│   └── ... (all 11 chapters)
├── indexes/                     # Index files
│   ├── Valley_by_Night_Art_Bible_Index.md
│   └── Valley_by_Night_Art_Bible_Enhanced_Index.md
└── config/                      # Optional configuration (for consistency with other agents)
```

## How Agents Can Use This MCP

### 1. Metadata Discovery

Use the shared Supabase client to discover and load MCP metadata:

```php
require_once __DIR__ . '/../../includes/supabase_client.php';

$rows = supabase_table_get('mcp_style_packs', [
    'select' => 'filesystem_path,version',
    'slug' => 'eq.style_agent_mcp',
    'enabled' => 'eq.true',
    'limit' => '1'
]);
$mcp = $rows[0] ?? null;

if ($mcp) {
    $mcp_path = $mcp['filesystem_path'];
    $mcp_version = $mcp['version'];
    // Load MCP content from filesystem
}
```

### 2. Loading Art Bible Content

Once you have the MCP path, load specific chapters:

```php
$art_bible_path = __DIR__ . '/../../' . $mcp['filesystem_path'];
$portrait_rules = file_get_contents($art_bible_path . '/docs/Art_Bible_I_PORTRAIT_SYSTEM__.md');
$cinematic_rules = file_get_contents($art_bible_path . '/docs/Art_Bible_II_CINEMATIC_SYSTEM__.md');
```

### 3. Using Distilled Rules

For quick reference, use the distilled `RULES.md`:

```php
$rules = file_get_contents($art_bible_path . '/RULES.md');
// Parse and apply rules for art generation/validation
```

### 4. Using Prompt Templates

Load prompt templates from `PROMPTS.md`:

```php
$prompts = file_get_contents($art_bible_path . '/PROMPTS.md');
// Extract specific prompts for AI art generation
```

## Metadata Registration

This MCP is registered in the project data via the `mcp_style_packs` table:

- **Name**: Style Agent MCP
- **Slug**: `style_agent_mcp`
- **Version**: 1.0.0
- **Filesystem Path**: `agents/style_agent`
- **Enabled**: TRUE

See `docs/MCP_USAGE.md` in the project root for detailed MCP loading examples.

## When to Use This MCP

- **Art Generation**: When generating character portraits, cinematic scenes, location art, or UI elements
- **Style Validation**: When validating that generated art matches VbN aesthetic guidelines
- **Prompt Engineering**: When creating prompts for AI art generation tools
- **Asset Review**: When reviewing art assets for style compliance
- **Documentation**: When referencing visual style guidelines for the project

## Key Aesthetic Principles

The Art Bible enforces these core principles:

1. **Gothic-Noir Visual Style** - Dark, moody, atmospheric
2. **Phoenix 1994 Setting** - Period-accurate details and environments
3. **Emotional Restraint** - Subtle, controlled expressions and movement
4. **Clan-Specific Motifs** - Visual cues unique to each vampire clan
5. **Consistent Color Palette** - Gothic Black, Dusk Brown-Black, Blood Red, Parchment Light, Muted Gold, Teal Moonlight
6. **Technical Standards** - Specific resolutions, formats, and quality requirements

## Version Information

- **Current Version**: 1.0.0
- **Last Updated**: 2025-01-26
- **Registry Table**: `mcp_style_packs`
- **Maintained By**: VbN Development Team

## Related Documentation

- `RULES.md` - Quick reference for key constraints
- `PROMPTS.md` - All reusable prompt templates
- `INDEX.md` - Chapter-by-chapter guide
- `docs/` - Full Art Bible chapters
- `../docs/MCP_USAGE.md` - Database integration guide

## Future MCPs

This MCP serves as a template for future MCPs. When creating new MCPs:

1. Create the MCP folder structure
2. Register in `mcp_style_packs` table
3. Follow the same documentation pattern (README, RULES, PROMPTS, INDEX)
4. Use the same database integration pattern

