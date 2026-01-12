#!/usr/bin/env python3
"""
Fix "B reed" -> "Breed" and "Wh at" -> "What" across all files.
"""

import re
from pathlib import Path

def fix_patterns_in_file(file_path: Path) -> int:
    """Fix B reed -> Breed and Wh at -> What in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        changes = 0
        
        # Fix "B reed" -> "Breed" (preserving case)
        text_before = text
        text = re.sub(r'\bB\s+reed\b', 'Breed', text)
        text = re.sub(r'\bb\s+reed\b', 'breed', text)
        text = re.sub(r'\bB\s+REED\b', 'BREED', text)
        # Handle possessive/plurals: "B reed's" -> "Breed's", "B reeds" -> "Breeds"
        text = re.sub(r'\bB\s+reed([a-zA-Z])', r'Breed\1', text)
        text = re.sub(r'\bb\s+reed([a-zA-Z])', r'breed\1', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Bb]\s+reed\b', original_text))
        
        # Fix "Wh at" -> "What" (preserving case)
        text_before = text
        text = re.sub(r'\bWh\s+at\b', 'What', text)
        text = re.sub(r'\bwh\s+at\b', 'what', text)
        text = re.sub(r'\bWH\s+AT\b', 'WHAT', text)
        # Handle with punctuation: "Wh at." -> "What."
        text = re.sub(r'\bWh\s+at([\s\.,;:!?\-])', r'What\1', text)
        text = re.sub(r'\bwh\s+at([\s\.,;:!?\-])', r'what\1', text)
        text = re.sub(r'\bWH\s+AT([\s\.,;:!?\-])', r'WHAT\1', text)
        # Handle possessive/plurals: "Wh at's" -> "What's", "Wh ats" -> "Whats"
        text = re.sub(r'\bWh\s+at([a-zA-Z])', r'What\1', text)
        text = re.sub(r'\bwh\s+at([a-zA-Z])', r'what\1', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Ww]h\s+at\b', original_text))
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            return changes
        return 0
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return 0

def main():
    folder = Path('reference/Books_md_ready_fixed_cleaned_v2')
    
    if not folder.exists():
        print(f"Folder not found: {folder}")
        return
    
    print(f"Fixing 'B reed' -> 'Breed' and 'Wh at' -> 'What' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_patterns_in_file(md_file)
        if changes > 0:
            files_fixed.append((md_file.name, changes))
            total_changes += changes
            print(f"Fixed {md_file.name}: {changes} changes")
    
    print()
    print("=" * 60)
    print(f"Done! Fixed {total_changes} instances across {len(files_fixed)} files.")
    print("=" * 60)

if __name__ == '__main__':
    main()
