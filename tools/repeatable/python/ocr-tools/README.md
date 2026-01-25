# OCR Tools

Tools for performing OCR (Optical Character Recognition) on image-based PDFs using Tesseract.

**Note:** These tools actually perform OCR. For cleaning up OCR-extracted text, use the tools in `text-cleanup-tools/`.

## Tools

### ocr_pdf.py

**Purpose:** Extracts text from image-based PDFs using Tesseract OCR.

**Usage:**
```bash
python tools/repeatable/python/ocr-tools/ocr_pdf.py <pdf_file> [output_file] [--lang=LANG] [--dpi=DPI]
```

**Options:**
- `--lang=LANG` - Tesseract language code (default: `eng`)
- `--dpi=DPI` - DPI for image conversion (default: `300`)

**Examples:**
```bash
# Basic usage
python tools/repeatable/python/ocr-tools/ocr_pdf.py book.pdf output.txt

# With custom language and DPI
python tools/repeatable/python/ocr-tools/ocr_pdf.py book.pdf output.txt --lang=eng --dpi=300

# Output to stdout
python tools/repeatable/python/ocr-tools/ocr_pdf.py book.pdf
```

**What it does:**
1. Converts PDF pages to images (using pdf2image)
2. Runs Tesseract OCR on each page image
3. Combines all extracted text with page markers
4. Saves to output file or prints to stdout

**Output format:**
```
=== PAGE 1 ===
[extracted text from page 1]

=== PAGE 2 ===
[extracted text from page 2]
...
```

**Dependencies:**
- Python 3.7+
- Tesseract OCR (installed system-wide)
- `pymupdf` package: `pip install pymupdf`

**Installation:**
```bash
# Install Python package (that's it!)
pip install pymupdf
```

**Use case:** Extract text from scanned PDFs or image-based PDFs that don't have selectable text.
