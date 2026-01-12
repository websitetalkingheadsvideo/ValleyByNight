#!/usr/bin/env python3
"""
Fix "Philo do x" -> "Philodox" across all files.
"""

import re
from pathlib import Path

def fix_philodox_in_file(file_path: Path) -> int:
    """Fix Philo do x -> Philodox in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Philo do x" -> "Philodox" (preserving case variations)
        text = re.sub(r'\bPhilo\s+do\s+x\b', 'Philodox', text)
        text = re.sub(r'\bphilo\s+do\s+x\b', 'philodox', text)
        text = re.sub(r'\bPHILO\s+DO\s+X\b', 'PHILODOX', text)
        
        # Handle with punctuation: "Philo do x." -> "Philodox."
        text = re.sub(r'\bPhilo\s+do\s+x([\s\.,;:!?\-])', r'Philodox\1', text)
        text = re.sub(r'\bphilo\s+do\s+x([\s\.,;:!?\-])', r'philodox\1', text)
        text = re.sub(r'\bPHILO\s+DO\s+X([\s\.,;:!?\-])', r'PHILODOX\1', text)
        
        # Handle possessive/plurals: "Philo do x's" -> "Philodox's", "Philo do xs" -> "Philodoxs"
        text = re.sub(r'\bPhilo\s+do\s+x([a-zA-Z])', r'Philodox\1', text)
        text = re.sub(r'\bphilo\s+do\s+x([a-zA-Z])', r'philodox\1', text)
        text = re.sub(r'\bPHILO\s+DO\s+X([a-zA-Z])', r'PHILODOX\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Pp]hilo\s+do\s+x\b', original_text))
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
    
    print(f"Fixing 'Philo do x' -> 'Philodox' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_philodox_in_file(md_file)
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
