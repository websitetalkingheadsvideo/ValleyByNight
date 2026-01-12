#!/usr/bin/env python3
"""Process all files in a folder automatically - processes all chunks until each file is complete"""

import re
import sys
from pathlib import Path

def process_file(filepath):
    """Process entire file in chunks until complete."""
    
    if not filepath.exists():
        print(f"File not found: {filepath}")
        return False
    
    # Skip empty files
    if filepath.stat().st_size == 0:
        print(f"Skipping empty file: {filepath.name}")
        return True
    
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
        try:
            content = filepath.read_text(encoding='utf-8', errors='ignore')
        except Exception as e:
            print(f"Error reading file: {e}")
            return False
        
        # Check if already complete
        if re.search(complete_pattern, content):
            print(f"File already marked as COMPLETE - skipping")
            return True
        
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
            try:
                filepath.write_text(result, encoding='utf-8')
            except Exception as e:
                print(f"Error writing file: {e}")
                return False
            
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
            
            try:
                filepath.write_text(result, encoding='utf-8')
            except Exception as e:
                print(f"Error writing file: {e}")
                return False
            
            # Progress update
            progress_pct = ((para_start_index + len(chunk)) / total_paras) * 100
            print(f"Chunk {chunk_count}: Processed {len(chunk)} paragraphs (indices {para_start_index + 1} to {para_start_index + len(chunk)})")
            print(f"  Fixes: {fix_count} | Unique patterns: {len(set(fixes))} | Progress: {progress_pct:.1f}%")
    
    print(f"\n{'='*60}")
    print(f"Completed: {filepath.name}")
    print(f"{'='*60}")
    return True

def process_folder(folder_path_str):
    """Process all .md and .txt files in a folder alphabetically."""
    folder_path = Path(folder_path_str)
    
    if not folder_path.exists() or not folder_path.is_dir():
        print(f"Folder not found: {folder_path}")
        return
    
    # Get all .md and .txt files, sorted alphabetically
    files = sorted([f for f in folder_path.iterdir() 
                   if f.is_file() and f.suffix.lower() in ['.md', '.txt']])
    
    if not files:
        print(f"No .md or .txt files found in {folder_path}")
        return
    
    print(f"\n{'='*70}")
    print(f"Processing folder: {folder_path}")
    print(f"Found {len(files)} files to process")
    print(f"{'='*70}\n")
    
    completed = 0
    skipped = 0
    failed = 0
    
    for i, filepath in enumerate(files, 1):
        print(f"\n[{i}/{len(files)}] {filepath.name}")
        result = process_file(filepath)
        
        if result:
            completed += 1
        else:
            failed += 1
    
    print(f"\n{'='*70}")
    print(f"FOLDER PROCESSING COMPLETE")
    print(f"{'='*70}")
    print(f"Files processed: {completed}")
    print(f"Files failed: {failed}")
    print(f"Total files: {len(files)}")
    print(f"{'='*70}\n")

if __name__ == '__main__':
    if len(sys.argv) < 2:
        folder_path = "reference/Books_md_ready_fixed_cleaned"
        print(f"No folder specified, using default: {folder_path}")
    else:
        folder_path = sys.argv[1]
    
    process_folder(folder_path)
