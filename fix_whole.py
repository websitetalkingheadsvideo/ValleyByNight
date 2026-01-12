#!/usr/bin/env python3
"""
Fix "who le" -> "whole" across all files.
"""

import re
from pathlib import Path

def fix_whole_in_file(file_path: Path) -> int:
    """Fix who le -> whole in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "who le" -> "whole" (preserving case variations)
        text = re.sub(r'\bwho\s+le\b', 'whole', text)
        text = re.sub(r'\bWho\s+le\b', 'Whole', text)
        text = re.sub(r'\bWHO\s+LE\b', 'WHOLE', text)
        
        # Handle with punctuation: "who le." -> "whole."
        text = re.sub(r'\bwho\s+le([\s\.,;:!?\-])', r'whole\1', text)
        text = re.sub(r'\bWho\s+le([\s\.,;:!?\-])', r'Whole\1', text)
        text = re.sub(r'\bWHO\s+LE([\s\.,;:!?\-])', r'WHOLE\1', text)
        
        # Handle possessive/plurals: "who le's" -> "whole's", "who les" -> "wholes"
        text = re.sub(r'\bwho\s+le([a-zA-Z])', r'whole\1', text)
        text = re.sub(r'\bWho\s+le([a-zA-Z])', r'Whole\1', text)
        text = re.sub(r'\bWHO\s+LE([a-zA-Z])', r'WHOLE\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Ww]ho\s+le\b', original_text))
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
    
    print(f"Fixing 'who le' -> 'whole' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_whole_in_file(md_file)
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
