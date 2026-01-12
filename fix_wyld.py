#!/usr/bin/env python3
"""
Fix "Wy ld" -> "Wyld" across all files.
"""

import re
from pathlib import Path

def fix_wyld_in_file(file_path: Path) -> int:
    """Fix Wy ld -> Wyld in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Wy ld" -> "Wyld" (preserving case variations)
        text = re.sub(r'\bWy\s+ld\b', 'Wyld', text)
        text = re.sub(r'\bwy\s+ld\b', 'wyld', text)
        text = re.sub(r'\bWY\s+LD\b', 'WYLD', text)
        
        # Handle with punctuation: "Wy ld." -> "Wyld."
        text = re.sub(r'\bWy\s+ld([\s\.,;:!?\-])', r'Wyld\1', text)
        text = re.sub(r'\bwy\s+ld([\s\.,;:!?\-])', r'wyld\1', text)
        text = re.sub(r'\bWY\s+LD([\s\.,;:!?\-])', r'WYLD\1', text)
        
        # Handle possessive/plurals: "Wy ld's" -> "Wyld's", "Wy lds" -> "Wylds"
        text = re.sub(r'\bWy\s+ld([a-zA-Z])', r'Wyld\1', text)
        text = re.sub(r'\bwy\s+ld([a-zA-Z])', r'wyld\1', text)
        text = re.sub(r'\bWY\s+LD([a-zA-Z])', r'WYLD\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Ww]y\s+ld\b', original_text))
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
    
    print(f"Fixing 'Wy ld' -> 'Wyld' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_wyld_in_file(md_file)
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
