#!/usr/bin/env python3
"""
Fix OCR-style mixed-case words in a markdown file.
Words like diFFiCulties, introduCtion, disCiplines -> Difficulties, Introduction, Disciplines.
Pattern: word contains lowercase followed by uppercase (wrong); normalize to first letter upper, rest lower.
"""

import re
import sys
from pathlib import Path


def normalize_mixed_case_word(match: re.Match) -> str:
    """Normalize word to first letter upper, rest lower."""
    word = match.group(0)
    if not word:
        return word
    return word[0].upper() + word[1:].lower()


def fix_mixed_case(text: str) -> str:
    """Find words with lowercase-then-uppercase (OCR error) and normalize."""
    # Word that contains at least one lowercase followed by uppercase
    pattern = re.compile(r"\b[A-Za-z'][a-z]+[A-Z][a-zA-Z']*\b")
    return pattern.sub(normalize_mixed_case_word, text)


def main() -> None:
    script_dir = Path(__file__).resolve().parent
    path = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else script_dir / "LotNR-formatted.md"
    if not path.exists():
        print(f"File not found: {path}")
        sys.exit(1)

    content = path.read_text(encoding="utf-8")
    new_content = fix_mixed_case(content)
    if new_content == content:
        print("No mixed-case words found.")
        sys.exit(0)

    path.write_text(new_content, encoding="utf-8")
    # Count changes (approximate: count differing lines)
    old_lines = content.splitlines()
    new_lines = new_content.splitlines()
    changes = sum(1 for a, b in zip(old_lines, new_lines) if a != b)
    if len(old_lines) != len(new_lines):
        changes += abs(len(old_lines) - len(new_lines))
    print(f"Updated {path.name}: normalized mixed-case words (approx. {changes} lines touched).")


if __name__ == "__main__":
    main()
