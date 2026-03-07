#!/usr/bin/env python3
"""Minimal clean for WOD Laws of Judgment - footer removal and stretched letters only."""
import re
from pathlib import Path

INPUT = Path(r"V:\agents\laws_agent\Books\backups\wod_laws_of_judgment_5099_raw.txt")
OUTPUT = Path(r"V:\agents\laws_agent\Books\backups\wod_laws_of_judgment_5099_final.txt")

# Compiled patterns for artifact lines
PATTERNS = [
    re.compile(r"^robert wheeler \(order #\d+\)(\s+[\d.]+)?$", re.I),
    re.compile(r"^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$"),
    re.compile(r"^(([A-Za-z])\2{4,}\s*)+$"),  # stretched letters
]

def main():
    lines = INPUT.read_text(encoding="utf-8").splitlines()
    cleaned = []
    for line in lines:
        s = line.strip()
        if s.startswith("<!-- PAGE") or not s:
            cleaned.append(line.rstrip())
            continue
        if any(p.match(s) for p in PATTERNS):
            continue
        cleaned.append(line.rstrip())

    # Rejoin paragraphs
    result = []
    para = []
    for line in cleaned:
        s = line.strip()
        if s.startswith("<!-- PAGE"):
            if para:
                result.append(" ".join(para))
                para = []
            result.append(line)
            continue
        if not s:
            if para:
                result.append(" ".join(para))
                para = []
            result.append("")
            continue
        if para and para[-1].endswith("-"):
            para[-1] = para[-1][:-1]
        para.append(s)
    if para:
        result.append(" ".join(para))

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text("\n".join(result), encoding="utf-8")
    print(f"Done: {OUTPUT}")

if __name__ == "__main__":
    main()
