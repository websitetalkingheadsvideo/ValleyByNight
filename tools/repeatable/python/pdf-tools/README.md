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
