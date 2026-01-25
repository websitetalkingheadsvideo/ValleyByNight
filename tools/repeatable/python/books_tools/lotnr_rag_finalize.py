#!/usr/bin/env python3
"""
LotNR RAG Corpus Finalizer.

Applies rule-based normalization to lotnr_lexicon.md, updates lotnr_chunks.jsonl
(lexicon chunks only), and lotnr_manifest.json. Uses pathlib; paths relative to
repository root. Run from repo root or pass --base.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path


def _repo_root() -> Path:
    # tools/repeatable/python/books_tools/<script> -> repo root is parents[4]
    return Path(__file__).resolve().parents[4]


# --- Normalization rules -----------------------------------------------------

SPECIFIC_ARTIFACTS = [
    ("Em-braced", "Embraced"),
    ("In-quisition", "Inquisition"),
    ("ruler-ship", "rulership"),
]

# Exclude from general hyphenation fix (proper compounds).
HYPHENATION_PRESERVE = {"Rock-Paper-Scissors"}


def _apply_specific(s: str) -> str:
    for old, new in SPECIFIC_ARTIFACTS:
        s = s.replace(old, new)
    return s


def _apply_general_hyphenation(s: str) -> tuple[str, int]:
    """Fix word-hyphen-word scan artifacts. Returns (new_string, fix_count)."""
    # Use placeholder to protect preserve phrases
    placeholders: dict[str, str] = {}
    work = s
    for phrase in HYPHENATION_PRESERVE:
        key = f"\x00HYPHEN_PRESERVE_{id(phrase)}\x00"
        placeholders[key] = phrase
        work = work.replace(phrase, key)

    pattern = re.compile(r"([a-zA-Z]+)-([a-zA-Z]+)")
    count = 0

    def repl(m: re.Match[str]) -> str:
        nonlocal count
        count += 1
        return m.group(1) + m.group(2)

    work = pattern.sub(repl, work)
    for k, v in placeholders.items():
        work = work.replace(k, v)
    return work, count


def _apply_clan_punctuation(term: str, definition: str) -> tuple[str, bool]:
    """If term is exactly 'Clan' and definition lacks . ! ?, append period."""
    if term != "Clan":
        return definition, False
    s = definition.strip()
    if not s:
        return definition, False
    if s[-1] in ".!?":
        return definition, False
    return definition.rstrip() + ".", True


def _normalize_definition(term: str, definition: str) -> tuple[str, int, int, bool]:
    """Returns (normalized_def, specific_count, hyphen_count, clan_changed)."""
    d = definition
    specific_count = 0
    for old, new in SPECIFIC_ARTIFACTS:
        n = d.count(old)
        if n:
            specific_count += n
            d = d.replace(old, new)

    d, hyphen_count = _apply_general_hyphenation(d)
    d, clan_changed = _apply_clan_punctuation(term, d)
    return d, specific_count, hyphen_count, clan_changed


def _parse_lexicon_entry(line: str) -> tuple[str, str] | None:
    m = re.match(r"^\*\*(.+?)\*\* — (.+)$", line)
    if not m:
        return None
    return m.group(1), m.group(2)


def normalize_lexicon(content: str) -> tuple[str, int, int, int]:
    """
    Normalize lexicon md content. Preserves structure.
    Returns (new_content, specific_count, hyphen_count, clan_fix_count).
    """
    lines = content.splitlines(keepends=True)
    out: list[str] = []
    total_specific = 0
    total_hyphen = 0
    clan_fixes = 0

    i = 0
    while i < len(lines):
        line = lines[i]
        entry = _parse_lexicon_entry(line.strip())
        if entry is not None:
            term, definition = entry
            term_clean = _apply_specific(term)
            norm_def, sc, hc, cf = _normalize_definition(term, definition)
            total_specific += sc
            total_hyphen += hc
            if cf:
                clan_fixes += 1
            if term != term_clean:
                total_specific += 1
            new_line = f"**{term_clean}** — {norm_def}\n"
            out.append(new_line)
            i += 1
            # Preserve trailing blank line after entry
            if i < len(lines) and lines[i].strip() == "":
                out.append(lines[i])
                i += 1
            continue
        out.append(line)
        i += 1

    return "".join(out), total_specific, total_hyphen, clan_fixes


def load_lexicon(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def build_title_to_line_from_normalized(norm_content: str) -> dict[str, str]:
    """Build mapping chunk title (term) -> normalized '**Term** — Definition' line."""
    title_to_line: dict[str, str] = {}
    for line in norm_content.splitlines():
        s = line.strip()
        entry = _parse_lexicon_entry(s)
        if entry is not None:
            term, _ = entry
            title_to_line[term] = s
    return title_to_line


def process_jsonl(
    jsonl_path: Path,
    title_to_line: dict[str, str],
) -> tuple[list[str], dict[str, int], int]:
    """
    Update lexicon chunk 'text' to normalized lines. Preserve all other chunks.
    Returns (updated lines, doc -> chunk_count, total_chunks).
    """
    doc_counts: dict[str, int] = {}
    updated_lines: list[str] = []
    with open(jsonl_path, "r", encoding="utf-8") as f:
        for raw in f:
            line = raw.rstrip("\n")
            if not line:
                updated_lines.append("")
                continue
            obj = json.loads(line)
            doc = obj.get("doc", "")
            doc_counts[doc] = doc_counts.get(doc, 0) + 1
            if doc == "lotnr_lexicon":
                title = obj.get("title", "")
                if title in title_to_line:
                    obj["text"] = title_to_line[title]
            updated_lines.append(json.dumps(obj, ensure_ascii=False))
    total = sum(doc_counts.values())
    return updated_lines, doc_counts, total


def update_manifest(
    manifest_path: Path,
    doc_counts: dict[str, int],
    total_chunks: int,
) -> None:
    data = json.loads(manifest_path.read_text(encoding="utf-8"))
    for d in data.get("documents", []):
        doc = d.get("doc", "")
        if doc in doc_counts:
            d["chunk_count"] = doc_counts[doc]
    data["total_chunks"] = total_chunks
    manifest_path.write_text(
        json.dumps(data, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )


def main() -> int:
    ap = argparse.ArgumentParser(description="LotNR RAG Corpus Finalizer")
    ap.add_argument(
        "--base",
        type=Path,
        default=None,
        help="Base dir (default: repo_root / reference / Books / LofNR)",
    )
    ap.add_argument(
        "--dry-run",
        action="store_true",
        help="Print stats only; do not overwrite files",
    )
    args = ap.parse_args()
    root = _repo_root()
    base = args.base or (root / "reference" / "Books" / "LofNR")
    lexicon_path = base / "lotnr_lexicon.md"
    jsonl_path = base / "lotnr_chunks.jsonl"
    manifest_path = base / "lotnr_manifest.json"

    for p in (lexicon_path, jsonl_path, manifest_path):
        if not p.is_file():
            print(f"Missing: {p}", file=sys.stderr)
            return 1

    # 1) Normalize lexicon
    content = load_lexicon(lexicon_path)
    norm_content, spec, hyp, clan = normalize_lexicon(content)
    entries_before = len([line for line in content.splitlines() if _parse_lexicon_entry(line.strip())])
    entries_after = len([line for line in norm_content.splitlines() if _parse_lexicon_entry(line.strip())])
    if entries_before != entries_after:
        print("Entry count changed; aborting.", file=sys.stderr)
        return 1

    if not args.dry_run:
        lexicon_path.write_text(norm_content, encoding="utf-8")

    # 2) Build title -> line from normalized lexicon
    title_to_line = build_title_to_line_from_normalized(norm_content)

    # 3) Update JSONL
    lines_before = sum(1 for _ in open(jsonl_path, "r", encoding="utf-8"))
    updated, doc_counts, total = process_jsonl(jsonl_path, title_to_line)
    if len(updated) != lines_before:
        print("JSONL line count changed; aborting.", file=sys.stderr)
        return 1

    if not args.dry_run:
        jsonl_path.write_text("\n".join(updated) + "\n", encoding="utf-8")

    # 4) Update manifest
    if not args.dry_run:
        update_manifest(manifest_path, doc_counts, total)

    # Validate JSONL (skip blank lines)
    for i, raw in enumerate(updated):
        if not raw:
            continue
        try:
            json.loads(raw)
        except json.JSONDecodeError as e:
            print(f"Invalid JSON at line {i + 1}: {e}", file=sys.stderr)
            return 1

    print("LotNR RAG Finalizer summary:")
    print(f"  Specific-artifact fixes: {spec}")
    print(f"  General hyphenation fixes: {hyp}")
    print(f"  Clan definition punctuation changed: {'yes' if clan else 'no'}")
    print(f"  Lexicon entries: {entries_before} (unchanged)")
    print(f"  JSONL lines: {len(updated)} (unchanged)")
    print(f"  Manifest: chunk_count/doc updated, total_chunks={total}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
