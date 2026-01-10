# Game Design Analysis System

This folder contains game design documents from other games/systems that may provide inspiration for Valley by Night development.

## Analysis System

The `analyze_game_design.py` script automatically analyzes markdown documents in this folder and extracts VbN-relevant elements.

### Running the Analysis

**Manual execution:**
```bash
python analyze_game_design.py
```

**When new files are added:**
The script will automatically detect and analyze any new `.md` files in this folder (excluding the analysis script and index file itself).

### Output

The script generates `Game Design Analysis Index.md` which contains:
- Minimal system description for each document
- VbN-relevant elements extracted from each document
- Transferability assessment (Fully/Partially/Not Transferable)

### Adding New Documents

1. Add your game design document as a `.md` file in this folder
2. Run `analyze_game_design.py`
3. Review the updated `Game Design Analysis Index.md`

The script will automatically include the new document in the master index.

### File Structure

- `*.md` - Game design documents to analyze
- `analyze_game_design.py` - Analysis script
- `Game Design Analysis Index.md` - Generated master index (auto-updated)
- `README.md` - This file
