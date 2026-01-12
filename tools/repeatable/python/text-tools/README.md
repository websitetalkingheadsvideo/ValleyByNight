# Text Tools

Tools for text processing and cleanup.

## Tools

### fix_spaces.py

**Purpose:** Fixes spacing issues in text files (split words, OCR errors).

**Usage:**
```bash
python tools/repeatable/python/text-tools/fix_spaces.py
```

**Features:**
- Fixes common OCR errors
- Splits concatenated words using wordninja
- Preserves game-specific terminology
- Configurable input/output folders

**Dependencies:**
- Python 3.7+
- `wordninja` package

**Configuration:** Edit INPUT_FOLDER and OUTPUT_FOLDER variables in script

---

### remove_old_url.py

**Purpose:** Removes references to `https://vbn.talkingheads.video/` from project files.

**Usage:**
```bash
python tools/repeatable/python/text-tools/remove_old_url.py
```

**Features:**
- Processes PHP, Markdown, JSON, TXT, XML files
- Skips .git, node_modules, venv directories
- Safe file processing with error handling

**Dependencies:** Python 3.7+ (os, re, pathlib)
