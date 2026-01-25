# Books Tools

### scan_books_ocr_report.py

**Purpose:** Find PDFs in `reference/Books` that have no matching summary in `reference/Books_summaries`, run `extract_full_pdf_text` on each, and record whether they need OCR (image-based). Writes `reference/Books/books_ocr.md`.

**Usage:**

```text
python tools/repeatable/python/books_tools/scan_books_ocr_report.py [--books-dir DIR] [--summaries-dir DIR] [--output PATH] [--extract-script PATH]
```

**Defaults:** `--books-dir` = `reference/Books`, `--summaries-dir` = `reference/Books_summaries`, `--output` = `reference/Books/books_ocr.md`.

**Use case:** Identify books missing summaries and which of those require OCR before summarization.
