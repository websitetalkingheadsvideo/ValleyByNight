---
name: vbn-db-character-portrait-mcp
description: Generate a Valley by Night character portrait image using 速推AI and style_agent MCPs, with full character data from the Supabase database (including portrait_name). Use when the user asks to create or generate an image for a specified character using DB data and the 速推AI + style_agent MCPs.
---

# VbN Database Character Portrait (MCP)

## Goal

Generate a character portrait image for a **specified character** using:
1. **Full character data from Supabase** (via app APIs or provided JSON), including `portrait_name` to guide the prompt and output filename.
2. **style_agent MCP** to build or refine the image prompt in VbN style.
3. **速推AI MCP** to produce the image.

Save the image under `images-generated/` and optionally document the prompt in a sidecar `.txt`. Optionally assign the result to the character via existing tooling.

## When to Use

- User asks to "create an image for [character name/ID] using the database" or "generate a portrait from Supabase using 速推AI and style_agent".
- User specifies a character (by ID, name, or by providing a character JSON) and wants an AI-generated portrait using MCPs and DB data.

## Inputs

- **Character identifier**: Character ID (numeric or UUID), character name, or a character JSON object in context.
- **Data source**: Prefer fetching from the app so `portrait_name` and all DB fields are available when possible.

## Outputs

1. **Image**: `images-generated/<slugified_character_name>_<YYYYMMDD-HHMMSS>.png` (e.g. 1024×1024 square, format per 速推AI).
2. **Sidecar** (optional): `images-generated/<same_base>.txt` with `final_prompt_used`, `negative_prompt_used`, character id/name, and `portrait_name`/output filename.
3. **Optional**: User can then assign the image to the character in DB using `tools/repeatable/php/database-tools/assign_character_image.php` (sets `portrait_name` / character_image and copies into `uploads/characters/`).

---

## Process (execute in order)

### 1. Get full character data (including portrait_name)

Use **one** of the following. Prefer sources that expose `portrait_name` so it can inform the prompt and output filename.

| Source | When | portrait_name |
|--------|------|----------------|
| **Admin View Character API** | You have admin access and character ID (UUID). | Raw DB row in backend includes it; response has `character_image` (resolved filename). Use `character_image` or character name for output filename. |
| **Load Character API** (player) | Logged-in player and character ID. | Not in response; use `character_image` and `character_name` for output filename. |
| **Character JSON in context** | User attached or pasted a character JSON. | Use `portrait_name` or `character_image` if present for output filename and prompt hint. |
| **Supabase MCP** (if available) | Project has Supabase MCP and you have table access. | Query `characters` by `id` or `character_name`; row includes `portrait_name`. |

- **App base URL**: `http://192.168.0.155` (see root [AGENTS.md](../../../AGENTS.md)).
- **Admin API**: `GET http://192.168.0.155/admin/view_character_api.php?id=<character_id>` (admin session; returns full character + traits, abilities, disciplines, etc.).
- **Player API**: `GET http://192.168.0.155/load_character.php?id=<character_id>` (player session; same structure but no raw `portrait_name` in response; use `character.character_image` and `character.character_name`).

Build a **single character payload** for the next step: at least `character_name`, `appearance`, `clan`, `concept`, `biography`, `notes`; plus `portrait_name` or `character_image` when available (for filename and prompt), and morality/disciplines/traits if present.

### 2. Use portrait_name in the prompt

- **Output filename**: Prefer `portrait_name` if present (e.g. `"Alessandro Vescari.png"`), otherwise derive from `character_name` (e.g. slugified `alessandro-vescari_20260315-123456.png`).
- **Prompt hint**: Include in the prompt context that the portrait is for the character and, if `portrait_name` is set, that the intended portrait filename is so-and-so (helps consistency with existing assets).

### 3. Build/refine prompt with style_agent MCP

- **MCP server**: `project-0-v:-style-agent` (style_agent).
- **Action**: Call the style_agent tool that builds or refines an image prompt (e.g. from Art Bible / portrait system docs). If the MCP exposes a tool like `build_portrait_prompt` or `refine_prompt`, call it with:
  - Character summary: name, clan, concept, appearance (and optionally biography/notes).
  - Portrait context: `portrait_name` or `character_image` as desired filename/style hint.
  - VbN constraints: cinematic neo-noir, 1994 Arizona, night scene for vampires (see sibling skill [vbn-character-portrait-generator](../vbn-character-portrait-generator) for time-of-day and morality rules if you need to align).
- **Docs**: If the style_agent is loaded from DB (`connect_to_style_agent_mcp.php` in project root), use its docs (e.g. `Art_Bible_I_PORTRAIT_SYSTEM__.md`, `PROMPTS.md`) to shape the prompt if no single “build prompt” tool exists.
- **Output**: Obtain a **final positive prompt** and a **negative prompt** for the image model.

### 4. Generate image with 速推AI MCP

- **MCP server**: `user-速推AI` (or the 速推AI server name as configured).
- **Action**: Call the 速推AI image-generation tool with:
  - The positive prompt from step 3.
  - The negative prompt from step 3.
  - Size/format as required by the tool (e.g. 1024×1024).
- **Schema**: Before calling, list/read the 速推AI MCP tool descriptor to use the correct parameter names and options.

### 5. Save outputs

- **Image**: Write the generated image bytes to `images-generated/<slug>_<YYYYMMDD-HHMMSS>.png`. Slug = character name, lowercased, spaces → hyphens, non-alphanumerics removed except hyphen.
- **Sidecar**: Optionally write `images-generated/<same_base>.txt` with:
  - `final_prompt_used`
  - `negative_prompt_used`
  - `character_id` / `character_name`
  - `portrait_name` or output filename used

### 6. Optional: assign image to character in DB

- Put the image in `uploads/characters/` with the chosen filename (e.g. `portrait_name` or `Character Name.png`).
- Update the database: Supabase uses `characters.portrait_name` (see [reference/Characters/Images/AGENTS.md](../../../reference/Characters/Images/AGENTS.md)). Either:
  - Run the PHP tool: `php tools/repeatable/php/database-tools/assign_character_image.php --id=<character_id> --image="<filename>" [--source=images-generated/...]` (see that script and [tools/repeatable/php/database-tools/README.md](../../../tools/repeatable/php/database-tools/README.md)), or
  - Run SQL in Supabase: `UPDATE characters SET portrait_name = '...' WHERE character_name = '...';` (use `character_name` in WHERE; `id` is UUID).

---

## Data to include in the prompt (from DB/API)

Use all available fields to enrich the prompt; especially:

- **Identity**: `character_name`, `portrait_name` (or `character_image`), `clan`, `concept`, `generation`, `sire`
- **Visual**: `appearance` (primary), `equipment`, `notes` (if they describe look)
- **Tone**: `nature`, `demeanor`, `biography`, traits, disciplines, morality (humanity/path)
- **VbN**: Night-only for vampires unless notes/biography say otherwise; morality-driven subtle vampiric cues (see [vbn-character-portrait-generator](../vbn-character-portrait-generator) for the humanity table and style lock).

---

## MCP tool discovery

- **style_agent**: Check MCP tools under `project-0-v:-style-agent` (e.g. in `mcps/project-0-v:-style-agent/tools/` or via Cursor’s MCP tool list). Read the tool schema before calling.
- **速推AI**: Check MCP tools under `user-速推AI` (or `user-AI`). Read the tool schema for the image-generation tool (parameters for prompt, negative_prompt, size, etc.).

---

## Failure / missing data

- If **character not found** (API 404 or empty): Report clearly; do not invent a character.
- If **portrait_name** is missing: Use `character_name` for slug and output filename; prompt still uses all other DB fields.
- If **style_agent** has no “build prompt” tool: Compose the prompt from character payload and VbN rules (noir, night, morality) and optionally pass it to a “refine” tool if available.
- If **速推AI** fails: Surface the error; do not substitute a different generator unless the user asks.

---

## References

- [AGENTS.md](../../../AGENTS.md) – app base URL, Supabase, conventions.
- [reference/Characters/Images/AGENTS.md](../../../reference/Characters/Images/AGENTS.md) – portrait assignment, `portrait_name`, SQL/PHP tools.
- [vbn-character-portrait-generator](../vbn-character-portrait-generator) – VbN prompt construction, morality table, night rule, negative prompt.
- [admin/AGENTS.md](../../../admin/AGENTS.md) – view_character_api, character portraits.
- [tools/repeatable/php/database-tools/README.md](../../../tools/repeatable/php/database-tools/README.md) – assign_character_image.php.
