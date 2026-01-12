#!/usr/bin/env python3
"""
Fix "nd a" -> "and" across all files.
Also handles compound words like "age nd a" -> "agenda".
"""

import re
from pathlib import Path

def fix_nd_a_in_file(file_path: Path) -> int:
    """Fix nd a -> and in a single file, handling compound words."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # First, fix compound words where "nd a" is part of a larger word
        # "age nd a" -> "agenda"
        text = re.sub(r'\bage\s+nd\s+a\b', 'agenda', text)
        text = re.sub(r'\bAge\s+nd\s+a\b', 'Agenda', text)
        text = re.sub(r'\bAGE\s+ND\s+A\b', 'AGENDA', text)
        
        # Handle possessive: "age nd a's" -> "agenda's"
        text = re.sub(r'\bage\s+nd\s+a([\'s])', r'agenda\1', text)
        text = re.sub(r'\bAge\s+nd\s+a([\'s])', r'Agenda\1', text)
        text = re.sub(r'\bAGE\s+ND\s+A([\'s])', r'AGENDA\1', text)
        
        # Now fix standalone "nd a" -> "and"
        # But be careful not to match if it's part of a word we just fixed
        text = re.sub(r'\bnd\s+a\b', 'and', text)
        text = re.sub(r'\bNd\s+a\b', 'And', text)
        text = re.sub(r'\bND\s+A\b', 'AND', text)
        
        # Handle with punctuation: "nd a." -> "and."
        text = re.sub(r'\bnd\s+a([\s\.,;:!?\-])', r'and\1', text)
        text = re.sub(r'\bNd\s+a([\s\.,;:!?\-])', r'And\1', text)
        text = re.sub(r'\bND\s+A([\s\.,;:!?\-])', r'AND\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes - count both patterns
            changes = len(re.findall(r'\bnd\s+a\b', original_text))
            changes += len(re.findall(r'\bage\s+nd\s+a\b', original_text))
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
    
    print(f"Fixing 'nd a' -> 'and' (and 'age nd a' -> 'agenda') in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_nd_a_in_file(md_file)
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
