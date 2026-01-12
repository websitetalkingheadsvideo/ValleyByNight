#!/usr/bin/env python3
"""
Fix "Aur a" -> "Aura" across all files.
"""

import re
from pathlib import Path

def fix_aura_in_file(file_path: Path) -> int:
    """Fix Aur a -> Aura in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Aur a" -> "Aura" (preserving case variations)
        text = re.sub(r'\bAur\s+a\b', 'Aura', text)
        text = re.sub(r'\baur\s+a\b', 'aura', text)
        text = re.sub(r'\bAUR\s+A\b', 'AURA', text)
        
        # Handle with punctuation: "Aur a." -> "Aura."
        text = re.sub(r'\bAur\s+a([\s\.,;:!?\-])', r'Aura\1', text)
        text = re.sub(r'\baur\s+a([\s\.,;:!?\-])', r'aura\1', text)
        text = re.sub(r'\bAUR\s+A([\s\.,;:!?\-])', r'AURA\1', text)
        
        # Handle possessive/plurals: "Aur a's" -> "Aura's", "Aur as" -> "Auras"
        text = re.sub(r'\bAur\s+a([a-zA-Z])', r'Aura\1', text)
        text = re.sub(r'\baur\s+a([a-zA-Z])', r'aura\1', text)
        text = re.sub(r'\bAUR\s+A([a-zA-Z])', r'AURA\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Aa]ur\s+a\b', original_text))
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
    
    print(f"Fixing 'Aur a' -> 'Aura' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_aura_in_file(md_file)
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
