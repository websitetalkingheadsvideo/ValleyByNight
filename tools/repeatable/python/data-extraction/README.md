# Data Extraction Tools

Tools for extracting data from various sources.

## Tools

### extract_locations_from_biographies.py

**Purpose:** Extracts specific, PC-visitable locations from character biographies with strict filtering.

**Usage:**
```bash
python tools/repeatable/python/data-extraction/extract_locations_from_biographies.py
```

**Features:**
- Strict filtering rules (zero false positives)
- Excludes natural terrain, political entities, vague macros
- Excludes real-world locations
- Filters out non-places

**Dependencies:** Python 3.7+ (json, os, re, datetime, pathlib, typing, collections)

---

### extract_history.py

**Purpose:** Extracts history/biography fields from .gv3 XML files.

**Usage:**
```bash
python tools/repeatable/python/data-extraction/extract_history.py
```

**Features:**
- Extracts notes, history, biography fields
- Handles CDATA sections
- Cleans XML content
- Outputs to JSON or text

**Dependencies:** Python 3.7+ (xml.etree.ElementTree, json, os, re, pathlib)
