#!/usr/bin/env python3
"""
Check if PDF has extractable text or needs OCR.
Sample first 5 pages; if total alphanumeric chars > 200, consider text-searchable.
Output: one line per PDF: path<TAB>text|ocr
"""
import sys
from pathlib import Path

try:
    import pdfplumber
except ImportError:
    print("Error: pdfplumber required. pip install pdfplumber", file=sys.stderr)
    sys.exit(1)

MIN_CHARS = 200
MAX_PAGES = 5


def has_extractable_text(pdf_path: str) -> bool:
    path = Path(pdf_path)
    if not path.exists() or not path.suffix.lower() == ".pdf":
        return False
    try:
        with pdfplumber.open(str(path)) as pdf:
            total = 0
            for i, page in enumerate(pdf.pages):
                if i >= MAX_PAGES:
                    break
                text = page.extract_text()
                if text:
                    total += sum(1 for c in text if c.isalnum())
                if total >= MIN_CHARS:
                    return True
        return total >= MIN_CHARS
    except Exception:
        return False


def main():
    if len(sys.argv) < 2:
        print("Usage: python check_pdf_text.py <pdf1> [pdf2 ...]")
        sys.exit(1)
    for p in sys.argv[1:]:
        result = "text" if has_extractable_text(p) else "ocr"
        print(f"{p}\t{result}")


if __name__ == "__main__":
    main()
