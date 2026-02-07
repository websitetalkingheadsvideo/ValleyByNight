#!/usr/bin/env python3
"""
Post-process RAG JSON: apply OCR artifact fixes and spelling/caps fixes from Books.
Usage: python post_process_rag_json.py path/to/rag.json
Modifies file in place.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

# Add Books dir for imports
SCRIPT_DIR = Path(__file__).resolve().parent
REPO_ROOT = SCRIPT_DIR.parent.parent.parent
BOOKS_DIR = REPO_ROOT / "agents" / "laws_agent" / "Books"
if str(BOOKS_DIR) not in sys.path:
    sys.path.insert(0, str(BOOKS_DIR))

import fix_ocr_artifacts  # noqa: E402
import fix_spelling_and_caps  # noqa: E402
import fix_spelling_dict  # noqa: E402

# Curly/smart quotes -> straight ASCII (stops editor highlighting)
CURLY_TO_STRAIGHT = str.maketrans({
    "\u2019": "'",  # RIGHT SINGLE QUOTATION MARK
    "\u2018": "'",  # LEFT SINGLE QUOTATION MARK
    "\u201c": '"',  # LEFT DOUBLE QUOTATION MARK
    "\u201d": '"',  # RIGHT DOUBLE QUOTATION MARK
})


def clean_content(text: str) -> str:
    """Apply OCR fixes then spelling/caps fixes."""
    if not text or not isinstance(text, str):
        return text
    text = text.translate(CURLY_TO_STRAIGHT)
    text = fix_ocr_artifacts.clean_content(text)
    text = fix_spelling_and_caps.clean_content(text)
    text = fix_spelling_dict.clean_content(text)
    return text


def process_file(path: Path) -> tuple[int, int]:
    """Process one JSON file in place; return (entries, fields_updated)."""
    raw = path.read_text(encoding="utf-8")
    data = json.loads(raw)
    if not isinstance(data, list):
        return 0, 0
    updated = 0
    for item in data:
        if not isinstance(item, dict):
            continue
        if "content" in item and item["content"]:
            old = item["content"]
            new = clean_content(old)
            if new != old:
                item["content"] = new
                updated += 1
        meta = item.get("metadata")
        if isinstance(meta, dict) and meta.get("section_title"):
            old = meta["section_title"]
            new = clean_content(old)
            if new != old:
                meta["section_title"] = new
                updated += 1
    if updated > 0:
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    return len(data), updated


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python post_process_rag_json.py path/to/rag.json")
        sys.exit(1)
    path = Path(sys.argv[1]).resolve()
    if not path.exists():
        print(f"File not found: {path}", file=sys.stderr)
        sys.exit(1)
    n_entries, n_updates = process_file(path)
    print(f"Processed: {n_entries} docs, {n_updates} fields updated")


if __name__ == "__main__":
    main()
