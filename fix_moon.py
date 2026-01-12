#!/usr/bin/env python3
"""
Fix "Mo on" -> "Moon" across all files.
"""

import re
from pathlib import Path

def fix_moon_in_file(file_path: Path) -> int:
    """Fix Mo on -> Moon in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Mo on" -> "Moon" (preserving case variations)
        text = re.sub(r'\bMo\s+on\b', 'Moon', text)
        text = re.sub(r'\bmo\s+on\b', 'moon', text)
        text = re.sub(r'\bMO\s+ON\b', 'MOON', text)
        
        # Handle with punctuation: "Mo on." -> "Moon."
        text = re.sub(r'\bMo\s+on([\s\.,;:!?\-])', r'Moon\1', text)
        text = re.sub(r'\bmo\s+on([\s\.,;:!?\-])', r'moon\1', text)
        text = re.sub(r'\bMO\s+ON([\s\.,;:!?\-])', r'MOON\1', text)
        
        # Handle possessive/plurals: "Mo on's" -> "Moon's", "Mo ons" -> "Moons"
        text = re.sub(r'\bMo\s+on([a-zA-Z])', r'Moon\1', text)
        text = re.sub(r'\bmo\s+on([a-zA-Z])', r'moon\1', text)
        text = re.sub(r'\bMO\s+ON([a-zA-Z])', r'MOON\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Mm]o\s+on\b', original_text))
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
    
    print(f"Fixing 'Mo on' -> 'Moon' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_moon_in_file(md_file)
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
