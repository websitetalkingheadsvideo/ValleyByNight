# OCR Tools

Tools for OCR processing and text cleanup.

## Tools

### fix_ocr_spelling.py

**Purpose:** Fixes OCR spelling errors in markdown files while preserving game terminology.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/fix_ocr_spelling.py <input_dir> [--output-dir=<dir>] [--dry-run]
```

**Features:**
- Pattern-based OCR fixes
- Spell checker for obvious errors
- Preserves game-specific terminology
- Configurable options

**Dependencies:**
- Python 3.7+ (argparse)
- `spellchecker` package (optional)

---

### clean_pdf_text.py

**Purpose:** Cleans common PDF extraction artifacts from text files.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/clean_pdf_text.py <input_file> [output_file]
```

**Features:**
- Removes image placeholders
- Removes header/footer noise
- Removes isolated characters
- Fixes hyphenation issues
- Encoding error handling

**Dependencies:** Python 3.7+ (re, os, pathlib, typing)

---

### ocr_process_folder.py

**Purpose:** Processes a folder of OCR files.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/ocr_process_folder.py
```

**Dependencies:** Python 3.7+

---

### clean_ocr_markdown.py

**Purpose:** Cleans OCR markdown files.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/clean_ocr_markdown.py [--input-dir=<dir>] [--output-dir=<dir>]
```

**Dependencies:**
- Python 3.7+ (argparse)

---

### ocr_process_full_file.py

**Purpose:** Processes full OCR files.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/ocr_process_full_file.py
```

**Dependencies:** Python 3.7+
