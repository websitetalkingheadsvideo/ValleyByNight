# PDF Tools

Tools for PDF extraction and processing.

## Tools

### extract_pdf_page.py

**Purpose:** Extracts a single page from a PDF file and outputs as text.

**Usage:**
```bash
python tools/repeatable/python/pdf-tools/extract_pdf_page.py <pdf_file> <page_number>
```

**Example:**
```bash
python tools/repeatable/python/pdf-tools/extract_pdf_page.py book.pdf 42
```

**Features:**
- Supports PyPDF2 and pdfplumber
- Better formatting with pdfplumber
- Page number validation
- Error handling

**Dependencies:**
- Python 3.7+
- PyPDF2 OR pdfplumber (at least one required)

### pdf_to_rag_json.py

**Purpose:** Converts a full PDF to RAG JSON (one chunk per page). Output matches the schema used by `agents/laws_agent/Books/*_rag.json`.

**Usage:**
```bash
python tools/repeatable/python/pdf-tools/pdf_to_rag_json.py <pdf_file> <output_json> [--source "Book Title"] [--book-code BOOK-CODE] [--content-type general]
```

**Example:**
```bash
python tools/repeatable/python/pdf-tools/pdf_to_rag_json.py "reference/Books/MET - VTM - Laws of Elysium (5012).pdf" agents/laws_agent/Books/laws_of_elysium_rag.json --source "MET - Laws of Elysium" --book-code MET-ELYSIUM
```

**Features:**
- One JSON object per page (id, page, chunk_index, total_chunks, content, content_type, metadata)
- Optional `--source` (default: PDF stem), `--book-code`, `--content-type`
- Uses pdfplumber or PyPDF2; no need to load the full PDF into chat

**Dependencies:**
- Python 3.7+
- PyPDF2 OR pdfplumber (at least one required)
