#!/usr/bin/env python3
"""Process next chunk of Anarch Guide.md - resumes from latest checkpoint"""

import re
from pathlib import Path

filepath = Path("reference/Books_md_ready_fixed/Anarch Guide.md")
content = filepath.read_text(encoding='utf-8', errors='ignore')

# Find ALL checkpoints and use the LAST one
checkpoint_pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->'
checkpoint_matches = list(re.finditer(checkpoint_pattern, content))

if not checkpoint_matches:
    print("No checkpoint found - starting from beginning")
    start_pos = 0
else:
    # Use the last checkpoint
    checkpoint_match = checkpoint_matches[-1]
    start_pos = checkpoint_match.end()
    print(f"Latest checkpoint found at position {start_pos}")

# Split into paragraphs
full_paragraphs = content.split('\n\n')
print(f"Total paragraphs: {len(full_paragraphs)}")

# Find which paragraph the checkpoint is after
checkpoint_text = content[:start_pos]
checkpoint_paragraphs = checkpoint_text.split('\n\n')
para_start_index = len(checkpoint_paragraphs) - 1  # -1 because checkpoint is after this para
print(f"Resuming from paragraph {para_start_index + 1}")

# Process next 50 paragraphs after checkpoint
chunk = full_paragraphs[para_start_index + 1:para_start_index + 51]
print(f"Processing {len(chunk)} paragraphs (indices {para_start_index + 1} to {para_start_index + len(chunk)})")

if not chunk:
    print("No paragraphs remaining to process")
    exit(0)

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
    (r'\bacc\s+ele\s+rant\b', 'accelerant'),
    (r'\bexp\s+los\s+i\s+on\b', 'explosion'),
    (r'\bin\s+format\s+i\s+on\b', 'information'),
    (r'\bir\s+rita\s+ti\s+on\b', 'irritation'),
    (r'\bw\s+om\s+an\b', 'woman'),
    (r'\ban\s+arch\b', 'anarch'),
    (r'\bCama\s+rill\s+a\b', 'Camarilla'),
    (r'\bS\s+abb\s+at\b', 'Sabbat'),
    (r'\bDis\s+ci\s+pline\b', 'Discipline'),
    (r'\bAt\s+trib\s+utes\b', 'Attributes'),
]

fixed_chunk = []
for para in chunk:
    original = para
    for pattern, replacement in patterns:
        matches = list(re.finditer(pattern, para, re.IGNORECASE))
        for match in reversed(matches):  # reverse to preserve positions
            fixes.append(f"{match.group(0)} -> {replacement}")
            para = para[:match.start()] + replacement + para[match.end():]
            fix_count += 1
    fixed_chunk.append(para)

# Reconstruct: before checkpoint + checkpoint + processed chunk + checkpoint + remaining
before_checkpoint = '\n\n'.join(full_paragraphs[:para_start_index + 1])
processed_chunk = '\n\n'.join(fixed_chunk)
remaining = '\n\n'.join(full_paragraphs[para_start_index + 1 + len(chunk):])

# Check if we're at EOF
if para_start_index + 1 + len(chunk) >= len(full_paragraphs):
    # At end of file
    result = before_checkpoint + '\n\n' + processed_chunk + '\n\n<!-- OCR_SPLITWORD_PASS: COMPLETE -->\n'
    print("\nReached end of file - marking as COMPLETE")
else:
    # Insert new checkpoint after processed chunk
    checkpoint_marker = '<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->'
    result = before_checkpoint + '\n\n' + processed_chunk + '\n\n' + checkpoint_marker + '\n\n' + remaining
    # Remove ALL old checkpoints except the one we're keeping
    # Keep only the checkpoint we just added
    # Remove all other checkpoints
    old_checkpoints = list(re.finditer(checkpoint_pattern, result))
    for old_checkpoint in old_checkpoints[:-1]:  # Remove all but the last one
        result = result[:old_checkpoint.start()] + result[old_checkpoint.end():]

filepath.write_text(result, encoding='utf-8')

print(f"\n=== PROCESSING REPORT ===")
print(f"File: {filepath.name}")
print(f"Paragraphs processed: {len(chunk)} (indices {para_start_index + 1} to {para_start_index + len(chunk)})")
print(f"Total fixes applied: {fix_count}")
print(f"Unique fix types: {len(set(fixes))}")
if fixes:
    print(f"\nSample fixes (first 20):")
    for fix in list(set(fixes))[:20]:
        print(f"  {fix}")
print("=" * 40)
