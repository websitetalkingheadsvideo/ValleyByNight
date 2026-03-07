#!/usr/bin/env python3
"""
Step 2: Inspect raw extracted text for OCR artifacts.
Shows the line immediately after each <!-- PAGE X --> for review.
Usage: python inspect_artifacts.py input_raw.txt [output_report.txt]
"""

import re
import sys
from pathlib import Path


def inspect_raw(raw_path: str, report_path: str | None) -> None:
    raw_path = Path(raw_path)
    if not raw_path.exists():
        raise FileNotFoundError(f"Input file not found: {raw_path}")

    with open(raw_path, "r", encoding="utf-8") as f:
        lines = f.readlines()

    page_marker = re.compile(r"^<!-- PAGE (\d+) -->\s*$")
    report_lines: list[str] = []

    i = 0
    while i < len(lines):
        line = lines[i]
        stripped = line.rstrip()
        m = page_marker.match(stripped)
        if m:
            page_num = m.group(1)
            # Next line is often blank; the one after is first content (often artifact)
            next_line = ""
            j = i + 1
            while j < len(lines) and not lines[j].strip():
                j += 1
            if j < len(lines):
                next_line = lines[j].rstrip()[:100]
            report_lines.append(f"<!-- PAGE {page_num} --> >>> {next_line}")
        i += 1

    report_text = "\n".join(report_lines)
    if report_path:
        out = Path(report_path)
        out.parent.mkdir(parents=True, exist_ok=True)
        with open(out, "w", encoding="utf-8") as f:
            f.write(report_text)
        print(f"Report written to {out} ({len(report_lines)} pages)")
    else:
        print(report_text)


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python inspect_artifacts.py input_raw.txt [output_report.txt]")
        sys.exit(1)
    raw_path = sys.argv[1]
    report_path = sys.argv[2] if len(sys.argv) > 2 else None
    inspect_raw(raw_path, report_path)


if __name__ == "__main__":
    main()
