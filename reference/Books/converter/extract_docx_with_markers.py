#!/usr/bin/env python3
"""
Step 1: Extract text from DOCX with page markers.

Output format matches PDF extractor: <!-- PAGE N --> markers with text between.
Uses hard page breaks (Ctrl+Enter) when present; otherwise splits by ~3000 chars
(approx one book page).

Usage: python extract_docx_with_markers.py input.docx output.txt
"""

from __future__ import annotations

import sys
from pathlib import Path

from docx import Document
from docx.oxml.ns import qn
from docx.oxml.text.paragraph import CT_P
from docx.oxml.table import CT_Tbl
from docx.table import Table
from docx.text.paragraph import Paragraph


def _run_has_page_break(run) -> bool:
    """Detect hard page break (w:br w:type='page') in a run."""
    for br in run._element.findall(qn("w:br")):
        if br.get(qn("w:type")) == "page":
            return True
    return False


def _paragraph_has_page_break(paragraph) -> bool:
    """Check if paragraph contains a hard page break before its text."""
    for run in paragraph.runs:
        if _run_has_page_break(run):
            return True
    return False


def _text_before_page_break(paragraph) -> tuple[str, str]:
    """
    Split paragraph text at page break. Returns (before_break, after_break).
    If no break, returns (full_text, "").
    """
    parts_before: list[str] = []
    parts_after: list[str] = []
    found_break = False
    for run in paragraph.runs:
        if found_break:
            parts_after.append(run.text)
        elif _run_has_page_break(run):
            parts_before.append(run.text)
            found_break = True
        else:
            parts_before.append(run.text)
    return ("".join(parts_before), "".join(parts_after))


def _collect_text_from_block(block) -> str:
    """Extract text from a paragraph or table."""
    if hasattr(block, "text"):
        return block.text or ""
    if isinstance(block, Table):
        rows: list[str] = []
        for row in block.rows:
            cells = [cell.text.strip() for cell in row.cells]
            rows.append(" | ".join(cells))
        return "\n".join(rows)
    return ""


def extract_docx_with_page_markers(docx_path: str | Path, output_path: str | Path) -> None:
    """Extract text from DOCX with <!-- PAGE N --> markers."""
    docx_path = Path(docx_path)
    output_path = Path(output_path)

    doc = Document(str(docx_path))
    page_num = 1
    current_page_text: list[str] = []
    approx_chars_per_page = 3000
    total_chars_in_page = 0
    page_breaks_found = False

    def flush_page() -> None:
        nonlocal page_num, current_page_text, total_chars_in_page
        text = "\n".join(current_page_text).strip()
        with open(output_path, "a", encoding="utf-8") as out:
            out.write(f"<!-- PAGE {page_num} -->\n")
            if text:
                out.write(text)
                out.write("\n")
            else:
                out.write("[No text content]\n")
            out.write("\n")
        page_num += 1
        current_page_text = []
        total_chars_in_page = 0

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text("", encoding="utf-8")

    # Walk body elements in document order (paragraphs and tables)
    body = doc.element.body
    for child in body.iterchildren():
        if isinstance(child, CT_P):
            paragraph = Paragraph(child, doc._body)
            if _paragraph_has_page_break(paragraph):
                page_breaks_found = True
                before, after = _text_before_page_break(paragraph)
                if before.strip():
                    current_page_text.append(before.strip())
                    total_chars_in_page += len(before)
                flush_page()
                if after.strip():
                    current_page_text = [after.strip()]
                    total_chars_in_page = len(after)
            else:
                text = paragraph.text.strip()
                if text:
                    current_page_text.append(text)
                    total_chars_in_page += len(text)
                    if not page_breaks_found and total_chars_in_page >= approx_chars_per_page:
                        flush_page()
        elif isinstance(child, CT_Tbl):
            table = Table(child, doc._body)
            text = _collect_text_from_block(table)
            if text:
                current_page_text.append(text)
                total_chars_in_page += len(text)
                if not page_breaks_found and total_chars_in_page >= approx_chars_per_page:
                    flush_page()

    if current_page_text:
        flush_page()

    total_pages = page_num - 1
    print(f"Extraction complete: {total_pages} pages")
    print(f"Output saved to: {output_path}")
    if not page_breaks_found:
        print("  (No hard page breaks found; split by ~3000 chars)")


def main() -> None:
    if len(sys.argv) != 3:
        print("Usage: python extract_docx_with_markers.py input.docx output.txt")
        sys.exit(1)
    docx_path = sys.argv[1]
    output_path = sys.argv[2]
    extract_docx_with_page_markers(docx_path, output_path)


if __name__ == "__main__":
    main()
