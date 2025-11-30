# Cursor Taskmaster Prompt  
# Cursor Taskmaster Prompt  
## Convert Valley by Night Art Bible into an MCP
I am creating a computer RPG based in Laws of the Night Revised. This is based on the World of Darkness Universe created by White Wolf. I want as faithful a reproduction of the game as possible. As an expert in Laws of the Night Revised, master storyteller, and experienced MCP designer, working as a planning and implementation assistant for the **Valley by Night (VbN)** project.

Your job is to use **Taskmaster** to create and execute a plan to turn the **Valley by Night Art Bible** into a reusable **Modular Content Pack (MCP)** that can be used by other VbN agents and tools **and** to add the necessary **database support** so this MCP can be discovered, referenced, and updated through the VbN database.

---

## 📌 Context

The Valley by Night Art Bible exists as:
- A single master Markdown file:
  - `Valley_by_Night_Art_Bible_Master_Edition.md`
- A collection of split chapter Markdown files:
  - `Art_Bible_00_Introduction.md`
  - `Art_Bible_I_[Portrait_System].md`
  - `Art_Bible_II_[Cinematic_System].md`
  - `Art_Bible_III_[Location_Architecture].md`
  - `Art_Bible_IV_[3D_Asset_System].md`
  - `Art_Bible_V_[UI_Web_Art_System].md`
  - `Art_Bible_VI_[Storyboards_Animatics].md`
  - `Art_Bible_VII_[Marketing_System].md`
  - `Art_Bible_VIII_[Floorplan_System].md`
  - `Art_Bible_IX_[Naming_Folder_Structure].md`
  - `Art_Bible_X_[Master_Index_Integration].md`
- Index files including:
  - `Valley_by_Night_Art_Bible_Enhanced_Index.md`

VbN uses **MCPs (Modular Content Packs)** for agents and subsystems (e.g. Laws of the Night MCP, Detectives MCP, etc.).

This new MCP should:
- Package all Art Bible content
- Be loadable by agents (e.g. Art/Style agents)
- Expose **database-backed metadata** so other systems can:
  - Discover that the Art System MCP exists
  - Retrieve version info, paths, and tags from the DB
  - Potentially attach other records (e.g. art assets, characters, locations) to this MCP via foreign keys or linking tables

---

## 🎯 Objectives

Use **Taskmaster** to:

1. **Locate** the Art Bible files in the project directories.
2. **Design a complete folder structure** for a new MCP:
   - Create the structure inside: `agents/style_agent/`
   - With subfolders for `docs`, `rules`, `prompts`, `indexes`, etc.
3. **Determine which files should be included** as-is and which require restructuring.
4. **Design database support** for the Art System MCP, including:
   - A table (or tables) to register MCPs (e.g. `mcp_packs` or similar)
   - Core fields such as:
     - `id`
     - `name` (e.g. "Style_Agent_MCP")
     - `slug` or `code`
     - `version`
     - `description`
     - `filesystem_path`
     - `enabled` (boolean)
     - `last_updated`
   - Optional tables/fields for:
     - Chapter-level metadata (e.g. linking chapter files to DB rows)
     - Tags or categories (e.g. `art`, `style`, `ui`, `cinematic`)
5. **Create a Taskmaster plan** that:
   - Includes discovery, design, implementation, and validation steps for BOTH:
     - MCP file/folder structure
     - Database schema changes and integration
   - Avoids breaking existing code paths or DB constraints
6. **Implement the MCP structure**, including:
   - Creating the MCP folder and subfolders
   - Copying/renaming Art Bible chapters
   - Creating new MCP documents:
     - `README.md`
     - `RULES.md` (distilled constraints and aesthetic rules)
     - `PROMPTS.md` (reusable prompts and meta-prompts)
     - `INDEX.md` (links to chapters + usage notes)
7. **Implement the database updates**, including:
   - Creating or updating SQL migration files for the MCP table(s)
   - Ensuring the schema is compatible with existing DB conventions
   - Adding a default row for the Art System MCP with:
     - Name
     - Slug/code
     - Version
     - Description
     - Filesystem path to MCP root
   - (Optional) Inserting rows representing chapters or sections, if appropriate.
8. **Document how other agents can use this MCP and its DB integration**, including:
   - How an agent can:
     - Query the DB to discover MCP packs
     - Look up the path, version, and enabled state of `Art_System_MCP`
     - Load specific docs (e.g. Portrait rules, Cinematic rules) from the MCP
   - How future MCPs should follow the same pattern.

---

## 🧠 Taskmaster Requirements

### 1. **Plan Phase (MANDATORY)**

Use Taskmaster to create a detailed plan before modifying files or database schema. The plan should include:

- **Discovery Steps**
  - Find where the Art Bible files are stored.
  - Inspect the existing database schema and MCP-related tables, if any.
- **Design Steps**
  - Propose the MCP folder structure.
  - Propose the DB table structure for MCP registration.
  - Decide on naming conventions for MCP entries and any related tables.
- **Implementation Steps**
  - File operations (create folders, move/copy files, generate new docs).
  - SQL migration or schema update scripts.
  - Any PHP/agent code that needs to read from the new MCP tables.
- **Validation Steps**
  - How you will confirm:
    - The MCP folder is complete and correctly structured.
    - The DB schema is valid and applied.
    - The MCP entry for the Art Bible is present and correctly populated.

### 2. **Clarification & Safety Rules**

- If multiple plausible folder locations exist, pick the one that:
  - Matches existing MCPs
  - Minimizes disruptive changes
- If multiple DB approaches are possible:
  - Prefer the solution that:
    - Keeps the schema clean and extensible
    - Does not conflict with existing tables or naming
- Never:
  - Drop or truncate existing tables without explicit instruction
  - Hardcode credentials
- All assumptions must be documented in:
  - The MCP `README.md`
  - Comments in SQL migration files (where appropriate)

### 3. **Implementation Phase**

- Execute the Taskmaster-approved plan step-by-step.
- Scope file operations to:
  - MCP directories
  - Supporting docs
- Scope DB operations to:
  - New tables or carefully updated existing tables
- Ensure **original Art Bible files remain intact** (no destructive edits).

### 4. **Validation Phase**

At the end, verify and document:

- The MCP folder exists (e.g. `agents/Art_System_MCP/`) with:
  - All chapter files
  - `README.md`
  - `RULES.md`
  - `PROMPTS.md`
  - `INDEX.md`
- The DB has:
  - A table (e.g. `mcp_packs`) with a row for the Art System MCP.
  - Any additional tables decided upon (e.g. `mcp_chapters`).
- There is sample or actual code (PHP/agent side) demonstrating:
  - How to query the MCP table
  - How to load MCP metadata for `Art_System_MCP`
- The usage of this MCP is documented for:
  - Art/Style agents
  - Other agents that might need to enforce or reference visual rules.

---

## 📦 Expected Deliverables

By the end of this Taskmaster flow, you should have:

1. A **Taskmaster plan** persisted somewhere appropriate (e.g. `notes/` or `docs/`).
2. A new **MCP folder**, e.g.:
   - `agents/Art_System_MCP/`
3. Inside that folder:
   - `README.md` — what the Art System MCP is, how/when to use it.
   - `RULES.md` — distilled key constraints, do/don’t rules, aesthetic manifesto.
   - `PROMPTS.md` — all reusable prompts and meta-prompts relevant to art style.
   - `INDEX.md` — listing all chapter files and their roles.
   - All Art Bible chapter `.md` files and optional enhanced index.
4. Database integration:
   - Migration or schema file(s) to create/update MCP tables.
   - A row in the MCP table registering `Art_System_MCP` with:
     - Name, slug/code, version, description, filesystem path, enabled flag.
   - Optional additional metadata rows (chapters/tags) if designed that way.
5. Documentation for **other agents and developers** explaining:
   - How to look up MCPs via the database.
   - How to locate the Art System MCP on disk.
   - How to load and apply the style rules from this MCP when generating or validating art.

---

## 🚦 Begin

**Use Taskmaster now to:**
1. Create the detailed plan  
2. Show the plan  
3. Execute the plan step-by-step, including file operations and DB updates  
4. Document how the Art System MCP and DB integration should be used going forward

