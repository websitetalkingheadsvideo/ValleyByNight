#!/usr/bin/env python3
"""Process first chunk of Apocalypse.md"""

import re
from pathlib import Path

filepath = Path("reference/Books_md_ready_fixed/Apocalypse.md")
content = filepath.read_text(encoding='utf-8', errors='ignore')

# Check if already complete
if '<!-- OCR_SPLITWORD_PASS: COMPLETE -->' in content:
    print("File already marked as COMPLETE")
    exit(0)

# Check for existing checkpoint
checkpoint_pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->'
checkpoint_match = re.search(checkpoint_pattern, content)

if checkpoint_match:
    print(f"Found existing checkpoint at position {checkpoint_match.end()}")
    start_pos = checkpoint_match.end()
    checkpoint_text = content[:start_pos]
    full_paragraphs = content.split('\n\n')
    checkpoint_paragraphs = checkpoint_text.split('\n\n')
    para_start_index = len(checkpoint_paragraphs) - 1
    print(f"Resuming from paragraph {para_start_index + 1}")
    paragraphs = full_paragraphs[para_start_index + 1:]
else:
    print("No checkpoint found - starting from beginning")
    paragraphs = content.split('\n\n')
    para_start_index = -1

print(f"Total paragraphs: {len(content.split('\n\n'))}")

# Process first 50 paragraphs (or remaining if less)
chunk = paragraphs[:50]
print(f"Processing {len(chunk)} paragraphs")

if not chunk:
    print("No paragraphs to process")
    exit(0)

# Common split patterns
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
    (r'\bth\s+e\b', 'the'),
    (r'\ban\s+d\b', 'and'),
    (r'\bw\s+h\s+at\b', 'what'),
    (r'\bw\s+hich\b', 'which'),
    (r'\bw\s+hen\b', 'when'),
    (r'\bw\s+here\b', 'where'),
    (r'\bh\s+ow\b', 'how'),
    (r'\bh\s+as\b', 'has'),
    (r'\bh\s+ave\b', 'have'),
    (r'\bw\s+ill\b', 'will'),
    (r'\bc\s+an\b', 'can'),
    (r'\bi\s+s\b', 'is'),
    (r'\ba\s+re\b', 'are'),
    (r'\bw\s+as\b', 'was'),
    (r'\bw\s+ith\s+out\b', 'without'),
    (r'\bag\s+a\s+in\b', 'again'),
    (r'\bdo\s+or\b', 'door'),
    (r'\ban\s+arch\b', 'anarch'),
    (r'\bCama\s+rill\s+a\b', 'Camarilla'),
    (r'\bS\s+abb\s+at\b', 'Sabbat'),
]

fixed_chunk = []
for para in chunk:
    for pattern, replacement in patterns:
        matches = list(re.finditer(pattern, para, re.IGNORECASE))
        for match in reversed(matches):
            fixes.append(f"{match.group(0)} -> {replacement}")
            para = para[:match.start()] + replacement + para[match.end():]
            fix_count += 1
    fixed_chunk.append(para)

# Reconstruct
full_paragraphs = content.split('\n\n')
if checkpoint_match:
    before_checkpoint = '\n\n'.join(full_paragraphs[:para_start_index + 1])
    remaining = '\n\n'.join(full_paragraphs[para_start_index + 1 + len(chunk):])
    processed_chunk = '\n\n'.join(fixed_chunk)
    
    if para_start_index + 1 + len(chunk) >= len(full_paragraphs):
        result = before_checkpoint + '\n\n' + processed_chunk + '\n\n<!-- OCR_SPLITWORD_PASS: COMPLETE -->\n'
        print("\nReached end of file - marking as COMPLETE")
    else:
        checkpoint_marker = '<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->'
        result = before_checkpoint + '\n\n' + processed_chunk + '\n\n' + checkpoint_marker + '\n\n' + remaining
        result = re.sub(checkpoint_pattern, '', result, count=1)
else:
    processed_chunk = '\n\n'.join(fixed_chunk)
    remaining = '\n\n'.join(paragraphs[50:])
    
    if len(paragraphs) <= 50:
        result = processed_chunk + '\n\n<!-- OCR_SPLITWORD_PASS: COMPLETE -->\n'
        print("\nReached end of file - marking as COMPLETE")
    else:
        checkpoint_marker = '<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->'
        result = processed_chunk + '\n\n' + checkpoint_marker + '\n\n' + remaining

filepath.write_text(result, encoding='utf-8')

print(f"\n=== PROCESSING REPORT ===")
print(f"File: {filepath.name}")
print(f"Paragraphs processed: {len(chunk)}")
print(f"Total fixes applied: {fix_count}")
print(f"Unique fix types: {len(set(fixes))}")
if fixes:
    print(f"\nSample fixes (first 20):")
    for fix in list(set(fixes))[:20]:
        print(f"  {fix}")
print("=" * 40)
