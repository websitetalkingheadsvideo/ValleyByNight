"""Extract abilities table from PDF and output JSON.

Usage: python extract_abilities_pdf.py [path_to_pdf] [output_path]
Output: abilities.json in same dir as PDF (or specified path)
"""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

try:
    import pdfplumber
except ImportError:
    print("Install pdfplumber: pip install pdfplumber")
    sys.exit(1)

# First words that typically start BOOK titles, not ability names - treat as continuation
_BOOK_STARTERS = frozenset(
    w.lower()
    for w in (
        "Guide", "Changeling", "Vampire", "Mage", "Werewolf", "Wraith", "Hunter",
        "Mummy", "World", "Book", "Clanbook", "Tradition", "Players", "Storytellers",
        "Dark", "Sorcerers", "Kindred", "Hunter", "Doomslayers", "Blood", "Dirty",
        "Land", "Project", "Digital", "Freak", "Ghouls", "Guildbook", "Halls",
        "Hierarchy", "Inquisition", "Kinfolk", "Kithbook", "Libellus", "Liege",
        "Mediums", "Nobles", "Quick", "Renegades", "Rage", "Rokea", "Sabbat",
        "Subsidiaries", "Technomancer", "Uktena", "Veil", "Wolves", "Denizens",
        "Sorcerer", "Sorcerer,"
        "Ascension", "Anarch", "Cainite", "Crusade", "Ends", "Fianna", "Stargazers",
        "Shadow", "Ashen", "Chicago", "Corax", "Croatan", "Gurahl", "Mokolé",
        "Nagah", "Bastet", "Rage", "Dark", "Familiar",
    )
)


def _split_ability_and_book(line: str, page_num: int) -> tuple[str | None, str]:
    """
    Split 'Ability Book Title Page' or 'Book Title Page' into (ability, book).
    Returns (None, book) for continuation lines (no new ability).
    """
    content = line[: line.rfind(str(page_num))].strip().rstrip()
    if not content:
        return None, ""

    parts = content.split()
    if len(parts) == 1:
        return None, content

    first = parts[0].rstrip(":")
    if first.lower() in _BOOK_STARTERS:
        return None, content

    _BAD_BOOK_START = frozenset("of a the and to in".split())

    for n in range(1, min(4, len(parts))):
        ability_part = " ".join(parts[:n]).rstrip(":")
        book_part = " ".join(parts[n:])
        if len(book_part) < 3:
            continue
        if book_part.split()[0].lower() in _BAD_BOOK_START:
            continue
        if ability_part.lower() in _BOOK_STARTERS:
            continue
        book_first = book_part.split()[0].rstrip(":").lower()
        if book_first not in _BOOK_STARTERS:
            continue
        if ":" in book_part or "Ed." in book_part or len(book_part.split()) >= 2:
            return ability_part, book_part

    return None, content


def extract_and_parse_pdf(pdf_path: str) -> dict:
    """Extract text from PDF and parse into structured abilities JSON."""
    result: dict[str, list[dict]] = {
        "Talents": [],
        "Skills": [],
        "Knowledges": [],
    }

    current_type: str | None = None
    current_ability: str | None = None

    def add_source(ability: str, book: str, page: int) -> None:
        nonlocal current_ability
        if not current_type:
            return
        current_ability = ability
        entry_list = result[current_type]
        existing = next(
            (e for e in entry_list if e["ability"] == ability),
            None,
        )
        src = {"book": book.strip(), "page": page}
        if existing:
            existing["sources"].append(src)
        else:
            entry_list.append({"ability": ability, "sources": [src]})

    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            text = page.extract_text() or ""
            for line in text.splitlines():
                line = line.strip()
                if not line:
                    continue
                if "Saturday" in line or ("Page " in line and " of " in line):
                    continue
                if re.match(r"^--\s*\d+\s+of\s+\d+\s*--", line):
                    continue
                if "Ability Type" in line or "Title" in line and "Page" in line:
                    continue

                if line == "Talents":
                    current_type = "Talents"
                    continue
                if line == "Skills":
                    current_type = "Skills"
                    continue
                if line == "Knowledges":
                    current_type = "Knowledges"
                    continue

                parts = line.split()
                if len(parts) < 2 or not parts[-1].isdigit():
                    continue

                page_num = int(parts[-1])
                ability, book = _split_ability_and_book(line, page_num)

                if ability:
                    add_source(ability, book, page_num)
                elif current_ability and book:
                    add_source(current_ability, book, page_num)

    _fix_ability_book(result)
    return result


def _fix_ability_book(data: dict) -> None:
    """Correct known misparsed ability/book splits."""
    fixes = [
        ("Artist Mage: The", "Sorcerers Crusade", "Artist", "Mage: The Sorcerers Crusade"),
        ("Assbeating Subsidiaries: A", "Guide to Pentex", "Assbeating", "Subsidiaries: A Guide to Pentex"),
        ("Assimilation Liege,", "Lord, and Lackey", "Assimilation", "Liege, Lord, and Lackey"),
    ]
    for wrong_ability, wrong_book, right_ability, right_book in fixes:
        for category in data.values():
            for entry in category:
                if entry["ability"] == wrong_ability and entry["sources"]:
                    src = entry["sources"][0]
                    if src["book"] == wrong_book:
                        entry["ability"] = right_ability
                        src["book"] = right_book
                    break


def main() -> None:
    pdf_path = (
        sys.argv[1]
        if len(sys.argv) > 1
        else r"C:\Users\paris\Downloads\abilities.pdf"
    )
    out_path = Path(pdf_path).parent / "abilities.json"
    if len(sys.argv) > 2:
        out_path = Path(sys.argv[2])

    pdf_path = Path(pdf_path)
    if not pdf_path.exists():
        print(f"File not found: {pdf_path}")
        sys.exit(1)

    data = extract_and_parse_pdf(str(pdf_path))

    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

    print(
        f"Wrote {len(data['Talents'])} talents, "
        f"{len(data['Skills'])} skills, {len(data['Knowledges'])} knowledges "
        f"to {out_path}"
    )


if __name__ == "__main__":
    main()
