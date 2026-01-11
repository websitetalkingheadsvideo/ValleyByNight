#!/usr/bin/env python3
"""Process first chunk of Anarch Guide.md"""

import re
from pathlib import Path

filepath = Path("reference/Books_md_ready_fixed/Anarch Guide.md")
content = filepath.read_text(encoding='utf-8', errors='ignore')

# Split into paragraphs (by double newline)
paragraphs = content.split('\n\n')
print(f"Total paragraphs: {len(paragraphs)}")

# Process first 50 paragraphs
chunk = paragraphs[:50]
print(f"Processing first {len(chunk)} paragraphs")

# Common split patterns - using \s+ to match any whitespace
fixes = []
fix_count = 0

patterns = [
    (r'\bf\s+or\b', 'for'),
    (r'\bth\s+at\b', 'that'),
    (r'\bw\s+ith\s+in\b', 'within'),
    (r'\bw\s+ith\b', 'with'),
    (r'\bdo\s+w\s+n\b', 'down'),
    (r'\bnot\s+h\s+ing\b', 'nothing'),
    (r'\beu\s+er\b', 'ever'),
    (r'\bh\s+ing\b', 'thing'),
    (r'\bf\s+or\s+est\b', 'forest'),
    (r'\bm\s+a\s+\'\s*am\b', "ma'am"),
]

fixed_chunk = []
for para in chunk:
    original = para
    for pattern, replacement in patterns:
        matches = list(re.finditer(pattern, para, re.IGNORECASE))
        for match in reversed(matches):  # reverse to preserve positions
            fixes.append(f"{match.group(0)} → {replacement}")
            para = para[:match.start()] + replacement + para[match.end():]
            fix_count += 1
    fixed_chunk.append(para)

# Reconstruct
result = '\n\n'.join(fixed_chunk) + '\n\n<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->\n\n' + '\n\n'.join(paragraphs[50:])

filepath.write_text(result, encoding='utf-8')
print(f"\n=== PROCESSING REPORT ===")
print(f"File: {filepath.name}")
print(f"Paragraphs processed: {len(chunk)}")
print(f"Total fixes applied: {fix_count}")
print(f"Unique fix types: {len(set(fixes))}")
if fixes:
    print(f"\nFirst 20 fixes:")
    for fix in list(set(fixes))[:20]:
        try:
            print(f"  {fix}")
        except UnicodeEncodeError:
            safe_fix = fix.replace('→', '->')
            print(f"  {safe_fix}")
