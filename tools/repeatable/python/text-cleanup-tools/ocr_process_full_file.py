#!/usr/bin/env python3
"""Process entire file automatically - processes all chunks until complete"""

import re
import sys
from pathlib import Path

def process_file(filepath_str):
    """Process entire file in chunks until complete."""
    filepath = Path(filepath_str)
    
    if not filepath.exists():
        print(f"File not found: {filepath}")
        return
    
    print(f"\n{'='*60}")
    print(f"Processing: {filepath.name}")
    print(f"{'='*60}\n")
    
    chunk_count = 0
    total_fixes = 0
    
    # Common split patterns
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
    
    checkpoint_pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*DONE_TO_HERE\s*-->'
    complete_pattern = r'<!--\s*OCR_SPLITWORD_PASS:\s*COMPLETE\s*-->'
    
    while True:
        # Read current state
        content = filepath.read_text(encoding='utf-8', errors='ignore')
        
        # Check if already complete
        if re.search(complete_pattern, content):
            print(f"\nFile already marked as COMPLETE")
            break
        
        # Find latest checkpoint
        checkpoint_matches = list(re.finditer(checkpoint_pattern, content))
        
        if checkpoint_matches:
            checkpoint_match = checkpoint_matches[-1]
            start_pos = checkpoint_match.end()
            checkpoint_text = content[:start_pos]
        else:
            start_pos = 0
            checkpoint_text = ""
        
        # Split into paragraphs
        full_paragraphs = content.split('\n\n')
        total_paras = len(full_paragraphs)
        
        # Find which paragraph the checkpoint is after
        checkpoint_paragraphs = checkpoint_text.split('\n\n')
        para_start_index = len(checkpoint_paragraphs) - 1
        
        # Process next 50 paragraphs
        chunk = full_paragraphs[para_start_index + 1:para_start_index + 51]
        
        if not chunk:
            print("No paragraphs remaining to process")
            break
        
        chunk_count += 1
        fixes = []
        fix_count = 0
        
        # Fix split words in chunk
        fixed_chunk = []
        for para in chunk:
            for pattern, replacement in patterns:
                matches = list(re.finditer(pattern, para, re.IGNORECASE))
                for match in reversed(matches):
                    fixes.append(f"{match.group(0)} -> {replacement}")
                    para = para[:match.start()] + replacement + para[match.end():]
                    fix_count += 1
            fixed_chunk.append(para)
        
        total_fixes += fix_count
        
        # Reconstruct
        before_checkpoint = '\n\n'.join(full_paragraphs[:para_start_index + 1])
        processed_chunk = '\n\n'.join(fixed_chunk)
        remaining = '\n\n'.join(full_paragraphs[para_start_index + 1 + len(chunk):])
        
        # Check if we're at EOF
        if para_start_index + 1 + len(chunk) >= total_paras:
            # At end of file
            result = before_checkpoint + '\n\n' + processed_chunk + '\n\n<!-- OCR_SPLITWORD_PASS: COMPLETE -->\n'
            filepath.write_text(result, encoding='utf-8')
            
            print(f"Chunk {chunk_count}: Processed {len(chunk)} paragraphs (indices {para_start_index + 1} to {para_start_index + len(chunk)})")
            print(f"  Fixes: {fix_count} | Unique patterns: {len(set(fixes))}")
            print(f"\nFILE COMPLETE - All {total_paras} paragraphs processed")
            print(f"Total chunks: {chunk_count}")
            print(f"Total fixes applied: {total_fixes}")
            break
        else:
            # Insert new checkpoint
            checkpoint_marker = '<!-- OCR_SPLITWORD_PASS: DONE_TO_HERE -->'
            result = before_checkpoint + '\n\n' + processed_chunk + '\n\n' + checkpoint_marker + '\n\n' + remaining
            
            # Remove all old checkpoints except the one we just added
            old_checkpoints = list(re.finditer(checkpoint_pattern, result))
            for old_checkpoint in old_checkpoints[:-1]:
                result = result[:old_checkpoint.start()] + result[old_checkpoint.end():]
            
            filepath.write_text(result, encoding='utf-8')
            
            # Progress update
            progress_pct = ((para_start_index + len(chunk)) / total_paras) * 100
            print(f"Chunk {chunk_count}: Processed {len(chunk)} paragraphs (indices {para_start_index + 1} to {para_start_index + len(chunk)})")
            print(f"  Fixes: {fix_count} | Unique patterns: {len(set(fixes))} | Progress: {progress_pct:.1f}%")
    
    print(f"\n{'='*60}")
    print(f"Completed: {filepath.name}")
    print(f"{'='*60}")

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python ocr_process_full_file.py <filepath>")
        print("Example: python ocr_process_full_file.py 'reference/Books_md_ready_fixed/Apocalypse.md'")
        sys.exit(1)
    
    process_file(sys.argv[1])
