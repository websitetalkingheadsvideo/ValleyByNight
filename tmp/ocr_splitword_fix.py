#!/usr/bin/env python3
"""
OCR Split Word Cleanup - Single Chunk Processor
Processes one chunk (max 50 paragraphs) at a time with checkpoint support.
"""

import re
import sys
from pathlib import Path

# Common split word patterns found in OCR text
COMMON_SPLITS = {
    # Common words
    r'\bf\s+or\b': 'for',
    r'\bth\s+at\b': 'that',
    r'\bw\s+h\s+at\b': 'what',
    r'\bth\s+e\b': 'the',
    r'\ban\s+d\b': 'and',
    r'\bi\s+n\s+to\b': 'into',
    r'\bw\s+ith\b': 'with',
    r'\bw\s+ith\s+out\b': 'without',
    r'\bm\s+ay\b': 'may',
    r'\bc\s+an\b': 'can',
    r'\bi\s+s\b': 'is',
    r'\by\s+ou\b': 'you',
    r'\by\s+our\b': 'your',
    r'\bth\s+ey\b': 'they',
    r'\bth\s+is\b': 'this',
    r'\bth\s+en\b': 'then',
    r'\bth\s+ere\b': 'there',
    r'\bw\s+hich\b': 'which',
    r'\bw\s+hen\b': 'when',
    r'\bw\s+here\b': 'where',
    r'\bh\s+ow\b': 'how',
    r'\bh\s+as\b': 'has',
    r'\bh\s+ave\b': 'have',
    r'\bw\s+ill\b': 'will',
    r'\bh\s+ad\b': 'had',
    r'\ba\s+re\b': 'are',
    r'\bw\s+as\b': 'was',
    
    # Longer words
    r'\bw\s+ith\s+in\b': 'within',
    r'\bdo\s+w\s+n\b': 'down',
    r'\bag\s+a\s+in\b': 'again',
    r'\bacc\s+ele\s+rant\b': 'accelerant',
    r'\bexp\s+los\s+i\s+on\b': 'explosion',
    r'\bin\s+format\s+i\s+on\b': 'information',
    r'\bir\s+rita\s+ti\s+on\b': 'irritation',
    r'\bdo\s+or\b': 'door',
    r'\bw\s+om\s+an\b': 'woman',
    r'\bm\s+a\s+am\b': "ma'am",
    r'\bV\s+ampire\b': 'Vampire',
    r'\bGr\s+u\s+ide\b': 'Guide',
    r'\bV\s+auld\s+erie\b': 'Vaulderie',
    r'\bA\s+NARCH\b': 'ANARCH',
    r'\bA\s+ARCH\b': 'ANARCH',
    r'\bm\s+eal\s+bce\b': 'Guide',  # might need manual review
    r'\bEu\s+e\b': 'Eye',
    
    # World of Darkness terms
    r'\ban\s+arch\b': 'anarch',  # but careful - could be "an arch" as in architecture
    r'\bCama\s+rill\s+a\b': 'Camarilla',
    r'\bS\s+abb\s+at\b': 'Sabbat',
    r'\bDis\s+ci\s+pline\b': 'Discipline',
    r'\bAt\s+trib\s+utes\b': 'Attributes',
}

def find_checkpoint_marker(content):
    """Find existing checkpoint marker."""
    pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->'
    match = re.search(pattern, content)
    if match:
        return match.end()
    return None

def is_complete_marker(content):
    """Check if file is already complete."""
    pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*COMPLETE\s*-->'
    return bool(re.search(pattern, content))

def split_paragraphs(content):
    """Split content into paragraphs (separated by blank lines)."""
    # Split by double newline, preserve the structure
    parts = re.split(r'(\n\n+)', content)
    paragraphs = []
    current_para = ""
    
    for part in parts:
        if re.match(r'^\n\n+$', part):
            if current_para.strip():
                paragraphs.append(current_para)
                paragraphs.append(part)  # preserve the separator
            current_para = ""
        else:
            current_para += part
    
    if current_para.strip():
        paragraphs.append(current_para)
    
    return paragraphs

def fix_split_words(text):
    """Fix common OCR split words in text."""
    fixes_applied = []
    
    for pattern, replacement in COMMON_SPLITS.items():
        matches = list(re.finditer(pattern, text, re.IGNORECASE))
        if matches:
            # Apply from end to start to preserve positions
            for match in reversed(matches):
                original = match.group(0)
                text = text[:match.start()] + replacement + text[match.end():]
                fixes_applied.append(f"{original} → {replacement}")
    
    return text, fixes_applied

def process_chunk(filepath):
    """Process one chunk of the file."""
    filepath = Path(filepath)
    
    # Read file
    content = filepath.read_text(encoding='utf-8', errors='ignore')
    
    # Check if complete
    if is_complete_marker(content):
        print(f"File {filepath.name} is already marked as COMPLETE")
        return
    
    # Find checkpoint
    checkpoint_pos = find_checkpoint_marker(content)
    
    if checkpoint_pos:
        # Process from checkpoint
        before_checkpoint = content[:checkpoint_pos]
        after_checkpoint = content[checkpoint_pos:]
    else:
        # Process from beginning
        before_checkpoint = ""
        after_checkpoint = content
    
    # Split into paragraphs
    paragraphs = split_paragraphs(after_checkpoint)
    
    # Find actual paragraph content (not separators)
    para_contents = [p for p in paragraphs if p.strip() and not re.match(r'^\n\n+$', p)]
    
    # Process max 50 paragraphs
    chunks_to_process = para_contents[:50]
    
    if not chunks_to_process:
        print(f"No paragraphs to process in {filepath.name}")
        return
    
    # Fix split words in each paragraph
    all_fixes = []
    fixed_paragraphs = []
    
    for para in chunks_to_process:
        fixed, fixes = fix_split_words(para)
        fixed_paragraphs.append(fixed)
        all_fixes.extend(fixes)
    
    # Reconstruct content
    # Find the positions of the paragraphs we processed in the original
    processed_count = len(chunks_to_process)
    processed_text = after_checkpoint
    
    # Apply fixes to the processed section
    for i, para in enumerate(chunks_to_process):
        if para in processed_text:
            fixed = fixed_paragraphs[i]
            processed_text = processed_text.replace(para, fixed, 1)
    
    # Find where to insert checkpoint (after last processed paragraph)
    # Count paragraphs in processed section to find end
    remaining_paras = split_paragraphs(processed_text)
    remaining_contents = [p for p in remaining_paras if p.strip() and not re.match(r'^\n\n+$', p)]
    
    if len(remaining_contents) <= processed_count:
        # We've reached EOF
        final_content = before_checkpoint + processed_text
        # Remove old checkpoint if exists
        final_content = re.sub(r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->', '', final_content)
        # Add complete marker at end
        final_content += '\n\n<!-- OCR_SPLITWORD_PASS: COMPLETE -->\n'
    else:
        # Find position after processed_count paragraphs
        para_idx = 0
        insert_pos = 0
        for p in remaining_paras:
            if p.strip() and not re.match(r'^\n\n+$', p):
                para_idx += 1
                if para_idx == processed_count:
                    insert_pos = processed_text.find(p) + len(p)
                    break
        
        # Insert checkpoint
        checkpoint_marker = '\n\n<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->\n'
        processed_text = processed_text[:insert_pos] + checkpoint_marker + processed_text[insert_pos:]
        
        # Remove old checkpoint if exists
        processed_text = re.sub(r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->', '', processed_text, count=1)
        
        # Reconstruct
        final_content = before_checkpoint + processed_text
    
    # Write back
    filepath.write_text(final_content, encoding='utf-8')
    
    # Report
    print(f"\n=== PROCESSING REPORT ===")
    print(f"File: {filepath.name}")
    print(f"Paragraphs processed: {processed_count}")
    print(f"Fixes applied: {len(set(all_fixes))}")
    if all_fixes:
        print(f"\nSample fixes:")
        for fix in list(set(all_fixes))[:20]:  # Show first 20 unique fixes
            try:
                print(f"  {fix}")
            except UnicodeEncodeError:
                print(f"  {fix.encode('ascii', 'replace').decode('ascii')}")
    print(f"\nCheckpoint inserted/updated")
    print("=" * 40)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python ocr_splitword_fix.py <filepath>")
        sys.exit(1)
    
    process_chunk(sys.argv[1])
