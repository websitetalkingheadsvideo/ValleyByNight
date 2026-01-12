#!/usr/bin/env python3
"""
Fix "Wy rm" -> "Wyrm" and "wy rm" -> "wyrm" across all files.
"""

import re
from pathlib import Path

def fix_wyrm_in_file(file_path: Path) -> int:
    """Fix Wy rm -> Wyrm in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Wy rm" -> "Wyrm" (preserving case variations)
        # Match: Wy rm, wy rm, WY RM, etc.
        # Handle all cases: standalone, with punctuation, with trailing letters (plurals/compounds)
        text = re.sub(r'\bWy\s+rm\b', 'Wyrm', text)
        text = re.sub(r'\bwy\s+rm\b', 'wyrm', text)
        text = re.sub(r'\bWY\s+RM\b', 'WYRM', text)
        
        # Handle with punctuation: "Wy rm." -> "Wyrm."
        text = re.sub(r'\bWy\s+rm([\s\.,;:!?\-])', r'Wyrm\1', text)
        text = re.sub(r'\bwy\s+rm([\s\.,;:!?\-])', r'wyrm\1', text)
        text = re.sub(r'\bWY\s+RM([\s\.,;:!?\-])', r'WYRM\1', text)
        
        # Handle plurals/compounds: "Wy rms" -> "Wyrms", "Wy rm-" -> "Wyrm-"
        # Match "Wy rm" followed by a letter (for plural/compound words)
        text = re.sub(r'\bWy\s+rm([a-zA-Z])', r'Wyrm\1', text)
        text = re.sub(r'\bwy\s+rm([a-zA-Z])', r'wyrm\1', text)
        text = re.sub(r'\bWY\s+RM([a-zA-Z])', r'WYRM\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Ww]y\s+rm\b', original_text))
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
    
    print(f"Fixing 'Wy rm' -> 'Wyrm' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_wyrm_in_file(md_file)
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
