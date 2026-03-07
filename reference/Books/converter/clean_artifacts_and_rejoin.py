#!/usr/bin/env python3
"""
Complete cleaning process:
1. Remove artifact lines (common + learned + optional per-book patterns)
2. Rejoin paragraphs

Usage:
  python clean_artifacts_and_rejoin.py input_raw.txt output_final.txt
  python clean_artifacts_and_rejoin.py input_raw.txt output_final.txt --patterns book_patterns.txt
  python clean_artifacts_and_rejoin.py input_raw.txt output_final.txt --learned learned_patterns.txt
  python clean_artifacts_and_rejoin.py input_raw.txt output_final.txt --fast
      Skip inline artifact removal and split-word fixes (faster for large files; post-process handles remaining artifacts).
"""

import re
import sys
from pathlib import Path

# Built-in common patterns (each must match a COMPLETE line)
COMMON_PATTERNS: list[str] = [
    r"^[Ii1 ]+$",
    r"^[Ii1 ∎]+$",
    r"^[LI]$",
    r"^[BbBt ]+t\s+f\s+Gh$",
    r"^~+-?hap\d+es\s+\d+\s+krai\s+kr\s+w~+ic~n$",
    r"^[Ii1 ]+\[+$",
    r"^\++f+$",
    r"^f\s+[iI]\d+$",
    r"^[iI]+\s*i?[UuOo]+er\s+[GgD][oO0][IiLl1]+[li1]+e?\d*$",
    r"^c\s+GOU$",
    r"^[TIL]+[li1]*ber\s+[cdt]\s+es$",
    r"^I[VW]+cr\s+[flit]+s\s+@ouLt$",
    r"^T[iI]+le\s+Gk?\d+\s+[liI1\s]+$",
    r"^\d+\s+\d+\s+\{?$",
    r"^GL\s+J$",
    r"^Animal$",
    r"^be\s+r\s+Li\s+Credits",
    r"^,AnPIe\d+\.$",
    r"^\d+T[\'\"]?\s+[A-Z][a-z]+\s+ag[A-Z]+\s+[a-z]$",
    r"^L\s+f\s+[iI]\d+$",
    r"^[liI1]+\s*/\s*[liI1\s]+$",
    r"^if$",
    r"^I\s+ibtr\s+acs\s+\d+\s*t$",
    r"^-?Pool\s+ell\s*\.$",
    r"^-?Pool$",
    r"^ell\s*\.$",
    r"^The\s+of\s+Ghouls$",
    r"^[TLIi1]+iber\s+[tudcs]\s*[eiI]*\s*[us]+\s+[\'\"\(]?[iI\(]?[co0Ocu]+[li1]+e?s?",
    r"^liber\s+\[\s*us\s+[DU]+\s*es",
    r"^I\s+1_iber\s+ties\s+G0\s+Tee\s+I",
    r"^~+\d+~+~+\d+f+\s+P\d+",
    r"^[Ii1 ~\[\]\(\)∎]+$",
    r"^\d+\s+[Ii1 ]+$",
    r"^I\s+L~\d+\s+~er\s+\d+",
    r"^[LIOC]\d+wp\d+er\s+\d+\.",
    r"^O\s+[\"\']PI,\s+\d+\s+w[\'\"]ir",
    r"^∎+$",
]


def load_pattern_file(path: Path) -> list[str]:
    out: list[str] = []
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            s = line.strip()
            if s and not s.startswith("#"):
                out.append(s)
    return out


def gather_patterns(
    script_dir: Path,
    patterns_path: str | None,
    learned_path: str | None,
) -> list[str]:
    combined: list[str] = list(COMMON_PATTERNS)
    # Learned patterns (from previous books) in converter/learned_patterns.txt
    learned_file = script_dir / "learned_patterns.txt"
    if learned_path:
        learned_file = Path(learned_path)
    if learned_file.exists():
        combined.extend(load_pattern_file(learned_file))
    # Per-book patterns
    if patterns_path:
        p = Path(patterns_path)
        if p.exists():
            combined.extend(load_pattern_file(p))
    return combined


def clean_and_rejoin(
    input_file: str,
    output_file: str,
    artifact_patterns: list[str],
    fast: bool = False,
) -> None:
    with open(input_file, "r", encoding="utf-8") as f:
        lines = f.readlines()

    compiled = [re.compile(p, re.IGNORECASE) for p in artifact_patterns]

    print("Step 1: Removing artifacts...")
    cleaned_lines: list[str] = []
    removed = 0

    for line in lines:
        stripped = line.strip()
        if stripped.startswith("<!-- PAGE"):
            cleaned_lines.append(line)
            continue
        if not stripped:
            cleaned_lines.append(line)
            continue

        is_artifact = False
        for pat in compiled:
            if pat.match(stripped):
                is_artifact = True
                break
        if not is_artifact and len(stripped) < 50:
            has_lib = bool(re.search(r"[1IiTlf]+\s*[ifl]?[bf]e?r", stripped, re.IGNORECASE))
            has_gou = bool(re.search(r"[GgD\(][o0Ocu]+[li1]+e?s?", stripped, re.IGNORECASE))
            if (has_lib or has_gou) and len(stripped.split()) <= 5:
                is_artifact = True
        if is_artifact:
            removed += 1
        else:
            cleaned_lines.append(line)

    print(f"  Removed {removed} artifact lines (patterns: {len(artifact_patterns)})")

    print("Step 2: Rejoining paragraphs...")
    result_lines: list[str] = []
    current_paragraph: list[str] = []

    for line in cleaned_lines:
        stripped = line.strip()
        if stripped.startswith("<!-- PAGE"):
            if current_paragraph:
                result_lines.append(" ".join(current_paragraph))
                current_paragraph = []
            result_lines.append(line.rstrip())
            continue
        if not stripped:
            if current_paragraph:
                result_lines.append(" ".join(current_paragraph))
                current_paragraph = []
            result_lines.append("")
            continue
        if current_paragraph and current_paragraph[-1].endswith("-"):
            current_paragraph[-1] = current_paragraph[-1][:-1]
        current_paragraph.append(stripped)

    if current_paragraph:
        result_lines.append(" ".join(current_paragraph))

    result = "\n".join(result_lines)
    result = re.sub(r"\n\n\n+", "\n\n", result)

    if fast:
        Path(output_file).parent.mkdir(parents=True, exist_ok=True)
        with open(output_file, "w", encoding="utf-8") as f:
            f.write(result)
        print(f"Complete! Output saved to: {output_file} (fast mode, inline fixes skipped)")
        return

    print("Step 3: Removing inline artifacts...")
    # Fix artifacts WITHIN text (not just whole lines)
    inline_fixes = [
        # Stretched-letter artifact (e.g. Bbbbb Yyyyy Ttttthhhhheeeee -> By The)
        (r'([A-Za-z])\1{4,}', r'\1'),
        # Spaced out letters
        (r'C\s+l\s+a\s+n\s+B\s+o\s+o\s+K\s*:?', 'Clanbook:'),
        (r'C\s+o\s*:', 'Chapter:'),
        # Common fragments
        (r'\bt\s+C\s+w\s+', ''),
        (r'\ba\s+the\s+d?versary\b', ''),
        (r'\bhe\s+e[A-Z][a-z]+\s+[a-zA-Z]+\s+[oO][a-zA-Z]+\b', ''),
        (r'\bt\s+s\s+C\b', ''),
        (r'\bC\s+s\s+t\b', ''),
        # Page footers
        (r'Clanbook:\s+[a-z]+\s+\d+', ''),
        (r'Chapter\s+[a-z]+:\s+[a-z\s]+\d+$', ''),
    ]
    
    inline_removed = 0
    for pattern, replacement in inline_fixes:
        before_len = len(result)
        result = re.sub(pattern, replacement, result, flags=re.MULTILINE)
        inline_removed += before_len - len(result)
    
    # Clean up extra spaces
    result = re.sub(r' +', ' ', result)
    result = re.sub(r'\n\n\n+', '\n\n', result)
    
    print(f"  Removed ~{inline_removed} characters of inline artifacts")
    
    print("Step 4: Fixing split words...")
    # Fix hyphenated words that got split across lines
    # Pattern: word + space + ending
    word_ending_patterns = [
        # -ing words
        (r'\b([a-z]{3,})([bcdfghjklmnpqrstvwxz])\s+(ing)\b', r'\1\2\3'),
        # -tion, -sion
        (r'\b([a-z]{3,})\s+(tion|sion)\b', r'\1\2'),
        # -ment
        (r'\b([a-z]{3,})\s+(ment)\b', r'\1\2'),
        # -ly
        (r'\b([a-z]{3,})([bcdfghjklmnpqrstvwxyz])\s+(ly)\b', r'\1\2\3'),
        # -ed
        (r'\b([a-z]{3,})([bcdfghjklmnpqrstvwxyz])\s+(ed)\b', r'\1\2\3'),
        # -er, -or
        (r'\b([a-z]{3,})([bcdfghjklmnpqrstvwxyz])\s+(er|or)\b', r'\1\2\3'),
        # -able, -ible
        (r'\b([a-z]{3,})\s+(able|ible)\b', r'\1\2'),
        # -ness
        (r'\b([a-z]{3,})\s+(ness)\b', r'\1\2'),
        # -less, -ful
        (r'\b([a-z]{3,})\s+(less|ful)\b', r'\1\2'),
        # -ous, -ious
        (r'\b([a-z]{3,})\s+(ous|ious)\b', r'\1\2'),
        # -ive, -ative
        (r'\b([a-z]{3,})\s+(ive|ative)\b', r'\1\2'),
        # -al, -ial
        (r'\b([a-z]{3,})\s+(al|ial)\b', r'\1\2'),
        # -ry, -ty, -cy
        (r'\b([a-z]{3,})\s+(ry|ty|cy)\b', r'\1\2'),
        # Common splits mid-word
        (r'\b([a-z]{3,})\s+([a-z]{2,5})ly\b', r'\1\2ly'),
        (r'\b([a-z]{3,})\s+([a-z]{2,5})tion\b', r'\1\2tion'),
    ]
    
    splits_fixed = 0
    for pattern, replacement in word_ending_patterns:
        before_len = len(result)
        result = re.sub(pattern, replacement, result, flags=re.IGNORECASE)
        splits_fixed += (before_len - len(result))
    
    # Final cleanup
    result = re.sub(r' +', ' ', result)
    
    print(f"  Fixed ~{splits_fixed} characters of split words")

    Path(output_file).parent.mkdir(parents=True, exist_ok=True)
    with open(output_file, "w", encoding="utf-8") as f:
        f.write(result)
    print(f"Complete! Output saved to: {output_file}")


def main() -> None:
    args = sys.argv[1:]
    if len(args) < 2:
        print(
            "Usage: python clean_artifacts_and_rejoin.py input_raw.txt output_final.txt [--patterns file] [--learned file]"
        )
        sys.exit(1)
    input_file = args[0]
    output_file = args[1]
    patterns_path: str | None = None
    learned_path: str | None = None
    fast = False
    i = 2
    while i < len(args):
        if args[i] == "--patterns" and i + 1 < len(args):
            patterns_path = args[i + 1]
            i += 2
        elif args[i] == "--learned" and i + 1 < len(args):
            learned_path = args[i + 1]
            i += 2
        elif args[i] == "--fast":
            fast = True
            i += 1
        else:
            i += 1

    script_dir = Path(__file__).resolve().parent
    combined = gather_patterns(script_dir, patterns_path, learned_path)
    clean_and_rejoin(input_file, output_file, combined, fast=fast)


if __name__ == "__main__":
    main()
