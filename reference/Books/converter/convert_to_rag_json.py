#!/usr/bin/env python3
"""
Convert cleaned page-marked text into RAG JSON.

Usage:
  python convert_to_rag_json.py input_final.txt output_rag.json
  python convert_to_rag_json.py input_final.txt output_rag.json --config book_config.json

Config JSON (optional):
  {
    "source_title": "Liber des Goules (Mind's Eye Theatre)",
    "book_code": "LDG-MET",
    "page_ranges": [
      {"start": 1, "end": 11, "content_type": "introduction"},
      {"start": 21, "end": 36, "content_type": "ghoul_types"},
      {"start": 37, "end": 72, "content_type": "character_creation"},
      {"start": 73, "end": 98, "content_type": "storytelling"},
      {"start": 99, "end": 9999, "content_type": "appendix"}
    ]
  }
  Build page_ranges from the book's table of contents. Summaries in reference/Books_summaries can help.
"""

import json
import re
import sys
from pathlib import Path
from typing import Any

# Default keyword-based classification when no page_ranges in config
KEYWORD_MAP: list[tuple[list[str], str]] = [
    (["discipline", "power", "blood", "vitae"], "discipline_info"),
    (["blood bond", "regnant", "thrall", "ghoul"], "ghoul_info"),
    (["influence", "background", "status", "trait", "attribute", "ability", "merit", "flaw", "humanity"], "character_creation"),
    (["storyteller", "roleplay", "chronicle"], "storytelling"),
    (["chapter"], "general"),
]


def classify_by_ranges(page_num: int, page_ranges: list[dict[str, Any]]) -> str | None:
    for r in page_ranges:
        if r["start"] <= page_num <= r["end"]:
            return r["content_type"]
    return None


def classify_by_keywords(text: str) -> str:
    text_lower = text.lower()
    for keywords, content_type in KEYWORD_MAP:
        if any(kw in text_lower for kw in keywords):
            return content_type
    return "general"


def classify_content(
    text: str,
    page_num: int,
    page_ranges: list[dict[str, Any]] | None,
) -> str:
    if page_ranges:
        ct = classify_by_ranges(page_num, page_ranges)
        if ct:
            return ct
    return classify_by_keywords(text)


def extract_section_title(text: str) -> str | None:
    lines = text.strip().split("\n")
    for line in lines[:5]:
        line = line.strip()
        if not line:
            continue
        if "chapter" in line.lower():
            return line
        if line.isupper() and 5 < len(line) < 60:
            return line
        if line.istitle() and any(w in line for w in [":", "-", "The", "A ", "An "]) and len(line) < 80:
            return line
    for line in lines[:3]:
        s = line.strip()
        if 20 < len(s) < 100:
            return s[:60] + "..."
    return None


def split_into_chunks(text: str, max_tokens: int = 1000) -> list[str]:
    max_chars = max_tokens * 4
    if len(text) <= max_chars:
        return [text]
    paragraphs = text.split("\n\n")
    chunks: list[str] = []
    current_chunk: list[str] = []
    current_length = 0
    for para in paragraphs:
        para_len = len(para)
        if current_length + para_len > max_chars and current_chunk:
            chunks.append("\n\n".join(current_chunk))
            current_chunk = [para]
            current_length = para_len
        else:
            current_chunk.append(para)
            current_length += para_len + 2
    if current_chunk:
        chunks.append("\n\n".join(current_chunk))
    return chunks


def convert_to_rag_json(
    input_file: str,
    output_file: str,
    source_title: str,
    book_code: str | None,
    page_ranges: list[dict[str, Any]] | None,
) -> None:
    with open(input_file, "r", encoding="utf-8") as f:
        content = f.read()

    pages = re.split(r"<!-- PAGE (\d+) -->\n", content)
    documents: list[dict[str, Any]] = []
    doc_id = 0
    last_page = 0

    for i in range(1, len(pages), 2):
        if i + 1 >= len(pages):
            break
        page_num = int(pages[i])
        page_content = pages[i + 1].strip()
        if not page_content or page_content == "[No text content]":
            continue
        last_page = page_num
        content_type = classify_content(page_content, page_num, page_ranges)
        section_title = extract_section_title(page_content)
        chunks = split_into_chunks(page_content)
        for chunk_idx, chunk in enumerate(chunks):
            meta: dict[str, Any] = {
                "source": source_title,
                "page_number": page_num,
                "section_title": section_title,
                "is_chunked": len(chunks) > 1,
                "chunk_position": f"{chunk_idx + 1}/{len(chunks)}" if len(chunks) > 1 else "1/1",
            }
            if book_code:
                meta["book_code"] = book_code
            doc = {
                "id": f"doc_{doc_id}",
                "page": page_num,
                "chunk_index": chunk_idx,
                "total_chunks": len(chunks),
                "content": chunk,
                "content_type": content_type,
                "metadata": meta,
            }
            documents.append(doc)
            doc_id += 1

    Path(output_file).parent.mkdir(parents=True, exist_ok=True)
    with open(output_file, "w", encoding="utf-8") as f:
        json.dump(documents, f, indent=2, ensure_ascii=False)

    print(f"Conversion complete: {output_file}")
    print(f"  Documents: {len(documents)}, pages: 1–{last_page}")
    type_counts: dict[str, int] = {}
    for d in documents:
        ct = d["content_type"]
        type_counts[ct] = type_counts.get(ct, 0) + 1
    for ct, count in sorted(type_counts.items()):
        print(f"  {ct}: {count}")


def main() -> None:
    args = sys.argv[1:]
    if len(args) < 2:
        print("Usage: python convert_to_rag_json.py input_final.txt output_rag.json [--config book_config.json]")
        sys.exit(1)
    input_file = args[0]
    output_file = args[1]
    config_path: str | None = None
    i = 2
    while i < len(args):
        if args[i] == "--config" and i + 1 < len(args):
            config_path = args[i + 1]
            i += 2
        else:
            i += 1

    source_title = "Unknown"
    book_code: str | None = None
    page_ranges: list[dict[str, Any]] | None = None
    if config_path and Path(config_path).exists():
        with open(config_path, "r", encoding="utf-8") as f:
            cfg = json.load(f)
        source_title = cfg.get("source_title", source_title)
        book_code = cfg.get("book_code")
        page_ranges = cfg.get("page_ranges")
    else:
        # Derive source from output filename (e.g. liber_des_goules_rag.json -> Liber des Goules)
        stem = Path(output_file).stem.replace("_rag", "").replace("_", " ").title()
        if stem:
            source_title = stem

    convert_to_rag_json(input_file, output_file, source_title, book_code, page_ranges)


if __name__ == "__main__":
    main()
