#!/usr/bin/env python3
"""
Fix header capitalization: apply title case to markdown header lines (# through ######).
Normalizes OCR-style miscaps (oF -> of, introduCtion -> Introduction) then title-cases.
"""

import re
import sys
from pathlib import Path

# Minor words: lowercase in title case unless first/last word
MINOR_WORDS = frozenset(
    "a an the and but or nor in on at to by for of with as if when yet so".split()
)


def normalize_word(word: str) -> str:
    """First char upper, rest lower (fixes oF, introduCtion, etc.)."""
    if not word:
        return word
    return word[0].upper() + word[1:].lower()


def title_case_phrase(text: str) -> str:
    """Apply title case: capitalize major words, lowercase minor words (except first/last)."""
    # Split on whitespace, preserve boundaries
    parts = re.split(r"(\s+)", text)
    result = []
    words = [p for p in parts if p and not p.isspace()]
    word_list = [p for p in parts]
    i = 0
    word_index = 0
    while i < len(parts):
        chunk = parts[i]
        if chunk.isspace():
            result.append(chunk)
            i += 1
            continue
        # chunk is a word
        normalized = normalize_word(chunk)
        is_first = word_index == 0
        is_last = word_index == len(words) - 1
        if is_first or is_last:
            result.append(normalized)
        elif normalized.lower() in MINOR_WORDS:
            result.append(normalized.lower())
        else:
            result.append(normalized)
        word_index += 1
        i += 1
    return "".join(result)


def fix_headers_in_line(line: str) -> str:
    """If line is a markdown header, return line with title-cased heading text."""
    m = re.match(r"^(#+)\s+(.+)$", line)
    if not m:
        return line
    prefix = m.group(1)
    rest = m.group(2).rstrip()
    # Optional: trailing page numbers or metadata (e.g. "10", "24")
    trailing = ""
    if rest:
        trail_m = re.match(r"^(.+?)(\s+\d{2,3}\s*)$", rest)
        if trail_m:
            rest = trail_m.group(1).rstrip()
            trailing = trail_m.group(2)
    fixed = title_case_phrase(rest) + trailing
    return f"{prefix} {fixed}"


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python fix_header_capitalization.py <path_to.md>")
        sys.exit(1)
    path = Path(sys.argv[1]).resolve()
    if not path.exists():
        print(f"File not found: {path}")
        sys.exit(1)
    content = path.read_text(encoding="utf-8")
    lines = content.split("\n")
    out = []
    changed = 0
    for line in lines:
        new_line = fix_headers_in_line(line)
        out.append(new_line)
        if new_line != line:
            changed += 1
    path.write_text("\n".join(out) + ("\n" if content.endswith("\n") else ""), encoding="utf-8")
    print(f"Updated {path.name}: {changed} header lines corrected.")


if __name__ == "__main__":
    main()
