#!/usr/bin/env python3
"""
Fix "Gai a" -> "Gaia" across all files.
"""

import re
from pathlib import Path

def fix_gaia_in_file(file_path: Path) -> int:
    """Fix Gai a -> Gaia in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "Gai a" -> "Gaia" (preserving case variations)
        text = re.sub(r'\bGai\s+a\b', 'Gaia', text)
        text = re.sub(r'\bgai\s+a\b', 'gaia', text)
        text = re.sub(r'\bGAI\s+A\b', 'GAIA', text)
        
        # Handle with punctuation: "Gai a." -> "Gaia."
        text = re.sub(r'\bGai\s+a([\s\.,;:!?\-])', r'Gaia\1', text)
        text = re.sub(r'\bgai\s+a([\s\.,;:!?\-])', r'gaia\1', text)
        text = re.sub(r'\bGAI\s+A([\s\.,;:!?\-])', r'GAIA\1', text)
        
        # Handle possessive/plurals: "Gai a's" -> "Gaia's", "Gai as" -> "Gaias"
        text = re.sub(r'\bGai\s+a([a-zA-Z])', r'Gaia\1', text)
        text = re.sub(r'\bgai\s+a([a-zA-Z])', r'gaia\1', text)
        text = re.sub(r'\bGAI\s+A([a-zA-Z])', r'GAIA\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Gg]ai\s+a\b', original_text))
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
    
    print(f"Fixing 'Gai a' -> 'Gaia' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_gaia_in_file(md_file)
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
