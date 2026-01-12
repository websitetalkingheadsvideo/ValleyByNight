#!/usr/bin/env python3
"""
Fix "d on' t" -> "don't" across all files.
"""

import re
from pathlib import Path

def fix_dont_in_file(file_path: Path) -> int:
    """Fix d on' t -> don't in a single file."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "d on' t" -> "don't" (preserving case variations)
        text = re.sub(r'\bd\s+on\s+\'\s+t\b', "don't", text)
        text = re.sub(r'\bD\s+on\s+\'\s+t\b', "Don't", text)
        text = re.sub(r'\bD\s+ON\s+\'\s+T\b', "DON'T", text)
        
        # Also handle "d on ' t" (with space before apostrophe)
        text = re.sub(r'\bd\s+on\s+\'\s+t\b', "don't", text)
        text = re.sub(r'\bD\s+on\s+\'\s+t\b', "Don't", text)
        
        # Handle with punctuation: "d on' t." -> "don't."
        text = re.sub(r'\bd\s+on\s+\'\s+t([\s\.,;:!?\-])', r"don't\1", text)
        text = re.sub(r'\bD\s+on\s+\'\s+t([\s\.,;:!?\-])', r"Don't\1", text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Dd]\s+on\s+\'\s+t\b', original_text))
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
    
    print(f"Fixing 'd on' t' -> 'don't' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_dont_in_file(md_file)
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
