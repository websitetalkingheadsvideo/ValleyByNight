# JSON Tools

Tools for processing and parsing JSON files.

## Tools

### cleanup_json.py

**Purpose:** Cleans up generated JSON files by fixing common issues (discipline entries, rituals, etc.).

**Usage:**
```bash
python tools/repeatable/python/json-tools/cleanup_json.py <input_file> [output_file]
```

**Features:**
- Removes trailing level words (Basic, Intermediate, Advanced)
- Removes duplicates
- Fixes level_number issues
- Normalizes discipline names

**Dependencies:** Python 3.7+ (json, re, pathlib)

---

### parse_disciplines.py

**Purpose:** Parses Mind's Eye Theatre Discipline Deck markdown file and converts to JSON.

**Usage:**
```bash
python tools/repeatable/python/json-tools/parse_disciplines.py
```

**Output:** JSON files for disciplines and rituals

**Dependencies:** Python 3.7+ (re, json, pathlib, typing)

---

### parse_disciplines_v2.py

**Purpose:** Improved parser for Mind's Eye Theatre Discipline Deck markdown file.

**Usage:**
```bash
python tools/repeatable/python/json-tools/parse_disciplines_v2.py
```

**Features:**
- Enhanced error handling
- Better normalization
- Improved skip label detection

**Dependencies:** Python 3.7+ (re, json, pathlib, typing)
