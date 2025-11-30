# Cursor Taskmaster Prompt  
## Convert Valley by Night Art Bible into an MCP
I am creating a computer RPG based in Laws of the Night Revised. This is based on the World of Darkness Universe created by White Wolf. I want as faithful a reproduction of the game as possible. As an expert in Laws of the Night Revised, master storyteller, and experienced MCP designer, working as a planning and implementation assistant for the **Valley by Night (VbN)** project.

Your job is to use **Taskmaster** to create and execute a plan to turn the **Valley by Night Art Bible** into a reusable **Modular Content Pack (MCP)** that can be used by other VbN agents and tools.

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

Your job is to **locate these files in the repo**, design a clean **MCP folder**, and integrate all Art Bible content.

---

## 🎯 Objectives

Use **Taskmaster** to:

1. **Locate** the Art Bible files in the project directories.
2. **Design a complete folder structure** for a new MCP:
   - Suggested: `agents/Art_System_MCP/`
   - With subfolders for `docs`, `rules`, `prompts`, `indexes`, etc.
3. **Determine which files should be included** as-is and which require restructuring.
4. **Create a Taskmaster plan** that:
   - Includes discovery, design, implementation, validation steps.
   - Avoids breaking existing code paths.
5. **Implement the plan**, including:
   - Creating the MCP folder
   - Copying/renaming Art Bible chapters
   - Creating new MCP documents (`README.md`, `RULES.md`, `PROMPTS.md`, etc.)
6. **Document how other agents can use this MCP**, e.g.:
   - Art Agent
   - Character Agent
   - Marketing Agent
   - Location Agent
   - Cinematic Agent

---

## 🧠 Taskmaster Requirements

### 1. **Plan Phase (MANDATORY)**
Use Taskmaster to create a detailed plan before modifying files, including:
- Directory discovery
- MCP folder architecture
- Naming conventions
- Dependencies and referencing strategy
- Risk mitigation

### 2. **Clarification Rules**
- If multiple file paths exist, pick the most consistent with other MCPs.
- If assumptions are necessary, document them clearly in the MCP README.

### 3. **Implementation Phase**
- Execute the plan step-by-step.
- Do not delete original Art Bible files.
- All MCP content must be self-contained.

### 4. **Validation Phase**
- Verify the MCP:
  - Has all chapters
  - Includes metadata
  - Includes prompt and rule summaries
  - Is discoverable by other agents
- Produce a short usage guide inside the MCP.

---

## 📦 Expected Deliverables

1. A Taskmaster-generated plan stored in the repo.
2. An MCP folder named something like:
   - `agents/Art_System_MCP/`
3. Inside it:
   - `README.md`
   - `RULES.md`
   - `PROMPTS.md`
   - `INDEX.md`
   - All chapter `.md` files
   - Optional: `manifest.json`
4. Clear instructions for agents explaining:
   - How to load the MCP
   - How to reference specific chapters
   - How to use MCP prompts for enforcing stylistic consistency

---

## 🚦 Begin

**Use Taskmaster now to:**
1. Create the plan  
2. Display the plan  
3. Execute the plan step-by-step

