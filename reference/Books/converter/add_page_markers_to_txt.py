#!/usr/bin/env python3
"""
Add <!-- PAGE N --> markers to OCR-extracted txt that uses
'Chapter X> N' or 'N  Clanbook: Toreador' style headers.

Usage:
  python add_page_markers_to_txt.py input.txt output.txt
"""

import re
import sys
from pathlib import Path


def add_page_markers(input_path: Path, output_path: Path) -> None:
    with open(input_path, "r", encoding="utf-8") as f:
        lines = f.readlines()

    # Section>	N  (e.g. "Introduction>	7", "Contents>	5", "Chapter Two>	19")
    pattern_section = re.compile(r">\s*(\d+)\s*$")
    # N	Clanbook...  (e.g. "20	Clanbook: Toreador", "8	4,_ Clonbook: Toreador")
    pattern_header = re.compile(r"^(\d+)\s+.*[Cc]l[ao]n?book", re.IGNORECASE)

    result: list[str] = []
    last_page = 0

    result.append("<!-- PAGE 1 -->\n")

    for line in lines:
        page_num: int | None = None
        m = pattern_section.search(line.rstrip())
        if m:
            page_num = int(m.group(1))
        else:
            m = pattern_header.match(line)
            if m:
                page_num = int(m.group(1))

        if page_num is not None and page_num > last_page:
            result.append(f"<!-- PAGE {page_num} -->\n")
            last_page = page_num

        result.append(line)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    with open(output_path, "w", encoding="utf-8") as f:
        f.writelines(result)

    print(f"Inserted markers up to page {last_page}, wrote {output_path}")


def main() -> None:
    if len(sys.argv) < 3:
        print("Usage: python add_page_markers_to_txt.py input.txt output.txt")
        sys.exit(1)
    add_page_markers(Path(sys.argv[1]), Path(sys.argv[2]))


if __name__ == "__main__":
    main()
