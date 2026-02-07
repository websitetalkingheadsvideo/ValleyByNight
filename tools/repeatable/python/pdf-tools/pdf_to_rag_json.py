#!/usr/bin/env python3
"""
Convert a full PDF to RAG JSON (one chunk per page).

Output matches the schema used by agents/laws_agent/Books/*_rag.json:
  id, page, chunk_index, total_chunks, content, content_type, metadata.

Usage:
  python pdf_to_rag_json.py <pdf_file> <output_json> [--source "Book Title"] [--book-code BOOK-CODE] [--content-type general]

Run from repo root or use absolute paths. Uses pathlib; no machine-specific paths.
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

try:
    import pdfplumber
    HAS_PDFPLUMBER = True
except ImportError:
    HAS_PDFPLUMBER = False

try:
    import PyPDF2
    HAS_PYPDF2 = True
except ImportError:
    HAS_PYPDF2 = False


def extract_pages_pdfplumber(pdf_path: Path) -> list[tuple[int, str]]:
    """Extract (page_num, text) for each page. page_num is 1-based."""
    result: list[tuple[int, str]] = []
    with pdfplumber.open(pdf_path) as pdf:
        for page_num, page in enumerate(pdf.pages, 1):
            text = page.extract_text()
            result.append((page_num, text or ""))
    return result


def extract_pages_pypdf2(pdf_path: Path) -> list[tuple[int, str]]:
    """Extract (page_num, text) for each page. page_num is 1-based."""
    result: list[tuple[int, str]] = []
    with open(pdf_path, "rb") as f:
        reader = PyPDF2.PdfReader(f)
        for i, page in enumerate(reader.pages, 1):
            text = page.extract_text()
            result.append((i, text or ""))
    return result


def build_rag_entries(
    pages: list[tuple[int, str]],
    source: str,
    book_code: str | None,
    content_type: str,
) -> list[dict]:
    """Build RAG JSON objects (one per page)."""
    total = len(pages)
    entries: list[dict] = []
    for idx, (page_num, text) in enumerate(pages):
        doc_id = f"doc_{idx}"
        meta: dict = {
            "source": source,
            "page_number": page_num,
            "section_title": None,
            "is_chunked": False,
            "chunk_position": "1/1",
        }
        if book_code:
            meta["book_code"] = book_code
        entries.append({
            "id": doc_id,
            "page": page_num,
            "chunk_index": 0,
            "total_chunks": 1,
            "content": text.strip(),
            "content_type": content_type,
            "metadata": meta,
        })
    return entries


def main() -> int:
    ap = argparse.ArgumentParser(
        description="Convert a full PDF to RAG JSON (one chunk per page).",
    )
    ap.add_argument("pdf_file", type=Path, help="Path to PDF file")
    ap.add_argument("output_json", type=Path, help="Path to output JSON file")
    ap.add_argument(
        "--source",
        type=str,
        default=None,
        help="Source/book title for metadata (default: PDF stem)",
    )
    ap.add_argument(
        "--book-code",
        type=str,
        default=None,
        help="Optional book code for metadata (e.g. MET-DARK-EPICS)",
    )
    ap.add_argument(
        "--content-type",
        type=str,
        default="general",
        help="content_type for all chunks (default: general)",
    )
    args = ap.parse_args()

    pdf_path = args.pdf_file.resolve()
    out_path = args.output_json.resolve()
    source = args.source or pdf_path.stem.replace("_", " ").replace("-", " ").title()

    if not pdf_path.is_file():
        print(f"Error: PDF not found: {pdf_path}", file=sys.stderr)
        return 1

    if not HAS_PDFPLUMBER and not HAS_PYPDF2:
        print(
            "Error: No PDF library. Install one of: pip install pdfplumber  # or  pip install PyPDF2",
            file=sys.stderr,
        )
        return 1

    if HAS_PDFPLUMBER:
        pages = extract_pages_pdfplumber(pdf_path)
    else:
        pages = extract_pages_pypdf2(pdf_path)

    total_pages = len(pages)
    if total_pages == 0:
        print("Error: No pages in PDF", file=sys.stderr)
        return 1

    entries = build_rag_entries(
        pages,
        source=source,
        book_code=args.book_code,
        content_type=args.content_type,
    )

    out_path.parent.mkdir(parents=True, exist_ok=True)
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(entries, f, ensure_ascii=False, indent=2)

    print(f"Wrote {total_pages} chunks to {out_path}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
