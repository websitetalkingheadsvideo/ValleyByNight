#!/usr/bin/env python3
"""
Insert #### **Keyword** subheaders before inline power names in LotNR-formatted.md.
Keywords are power names (Hand of Flame, Flame Bolt, etc.) that appear at the start
of a description with no line break. We insert #### **Keyword** and newline so they
are structural subheaders and explicit keywords (inside **) for RAG/chunking.

Usage: python add_keyword_subheaders.py [path_to.md]
Default path: same dir as script / LotNR-formatted.md
"""

import re
import sys
from pathlib import Path

# Sentence starters that typically begin a power description (space required after)
SENTENCE_STARTERS = (
    r"Your\s",
    r"With\s",
    r"By\s",
    r"When\s",
    r"You\s",
    r"A\s",
    r"The\s",
    r"Complicated\s",
    r"In\s",
    r"To\s",
    r"Over\s",
    r"Like\s",
    r"Just\s",
    r"After\s",
    r"Once\s",
    r"Gripping\s",
    r"Creating\s",
    r"Concentrating\s",
    r"Staring\s",
    r"Pointing\s",
    r"Touching\s",
    r"Expending\s",
    r"Shifting\s",
    r"Assuming\s",
    r"Drawing\s",
    r"Perhaps\s",
    r"This\s",
    r"These\s",
    r"Making\s",
    r"Using\s",
    r"Casting\s",
    r"Invoking\s",
    r"Exercising\s",
    r"Through\s",
    r"For\s",
    r"Without\s",
    r"Objects\s",
    r"Individuals\s",
    r"Most\s",
    r"Each\s",
    r"Any\s",
    r"All\s",
    r"No\s",
    r"Such\s",
    r"Summon\s",
    r"Magic\s",
    r"Reverse\s",
    r"Power\s",
    r"Decay\s",
    r"Gnarl\s",
    r"Acidic\s",
    r"Atrophy\s",
    r"Turn\s",
    r"Defense\s",
    r"Devil\s",
    r"Blood\s",
    r"Nectar\s",
    r"Umbra\s",
)

# 1–4 words in Title Case (keyword candidate)
KEYWORD_PATTERN = r"([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3})"
STARTERS_ALT = "|".join(SENTENCE_STARTERS)
# Match: keyword phrase + space + sentence starter (capture both)
PATTERN = re.compile(
    KEYWORD_PATTERN + r"\s+(" + STARTERS_ALT + r")",
    re.MULTILINE,
)

# Single-word keywords to skip (articles, common words, OCR fragments)
KEYWORD_BLACKLIST = frozenset(
    "The A An It This That So For Most In To As At By Or And If When With From Of "
    "Just After Once Over Like You Your Some Any Each All No Such "
    "Man Clan Fe Cy Ces Ct Thin Butes Ches Bletop Bilities Ciplines One Two Three "
    "Gaining Cophony Assign Chetypes Conjuring Courage Changing Converting Flaws "
    "Beast Dark Feral Beckoning Vampire Academics Butes Ches".split()
)

# Single-word power names we do want as keywords (otherwise require 2+ words)
POWER_WHITELIST = frozenset(
    "Possession Conditioning Fortitude Resilience Resistance Aegis Endurance "
    "Control Flight Repulse Manipulate Engulf Decay Atrophy".split()
)


def is_structural(line: str) -> bool:
    """True if line is a header or already a keyword subheader."""
    s = line.strip()
    return s.startswith("#") or s.startswith("#### **")


def should_process_line(prev_line: str, line: str) -> bool:
    """Process if this is a content paragraph (not structural) that may contain inline keywords."""
    if is_structural(line):
        return False
    # Only process non-empty content lines; optionally only after ### Basic/Intermediate/Advanced
    prev = (prev_line or "").strip()
    if prev.startswith("### ") and "Basic " in prev or "Intermediate " in prev or "Advanced " in prev:
        return True
    # Also process long paragraphs that might contain multiple power names (e.g. "Flame Bolt By ")
    if len(line) > 200 and PATTERN.search(line):
        return True
    return False


def replace_inline_keywords(text: str) -> str:
    """
    Replace each "Keyword SentenceStarter" with "\\n#### **Keyword**\\nSentenceStarter"
    Skip if keyword is blacklisted or already preceded by #### **Keyword** in same line.
    """
    result = []
    last_end = 0
    for m in PATTERN.finditer(text):
        keyword = m.group(1).strip()
        starter = m.group(2)
        # Skip blacklisted or require 2+ words unless whitelisted
        words = keyword.split()
        if len(words) == 1:
            if keyword in KEYWORD_BLACKLIST:
                continue
            if keyword not in POWER_WHITELIST:
                continue
        # Skip if we're right after an existing #### **Keyword** (avoid double-add)
        before = text[last_end : m.start()]
        if before.rstrip().endswith("**") and "#### **" in before:
            continue
        # Insert: newline + #### **Keyword** + newline + sentence starter
        result.append(text[last_end : m.start()])
        result.append("\n#### **")
        result.append(keyword)
        result.append("**\n")
        result.append(starter)
        last_end = m.end()
    result.append(text[last_end:])
    return "".join(result)


def main() -> None:
    script_dir = Path(__file__).resolve().parent
    path = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else script_dir / "LotNR-formatted.md"
    if not path.exists():
        print(f"File not found: {path}")
        sys.exit(1)

    content = path.read_text(encoding="utf-8")
    lines = content.split("\n")
    out = []
    prev = ""
    changes = 0
    for i, line in enumerate(lines):
        if should_process_line(prev, line):
            new_line = replace_inline_keywords(line)
            if new_line != line:
                changes += 1
            # Replacement may insert newlines; split so output stays line-based
            out.extend(new_line.split("\n"))
        else:
            out.append(line)
        prev = line

    path.write_text("\n".join(out) + ("\n" if content.endswith("\n") else ""), encoding="utf-8")
    print(f"Updated {path.name}: {changes} paragraph(s) modified.")


if __name__ == "__main__":
    main()
