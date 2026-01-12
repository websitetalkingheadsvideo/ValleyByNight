#!/usr/bin/env python3
"""
Fix "exp l a in" -> "explain" across all files.
"""

import re
from pathlib import Path

def fix_explain_in_file(file_path: Path) -> int:
    """Fix exp l a in -> explain in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix "exp l a in" -> "explain" (preserving case variations)
        text = re.sub(r'\bexp\s+l\s+a\s+in\b', 'explain', text)
        text = re.sub(r'\bExp\s+l\s+a\s+in\b', 'Explain', text)
        text = re.sub(r'\bEXP\s+L\s+A\s+IN\b', 'EXPLAIN', text)
        
        # Handle with punctuation: "exp l a in." -> "explain."
        text = re.sub(r'\bexp\s+l\s+a\s+in([\s\.,;:!?\-])', r'explain\1', text)
        text = re.sub(r'\bExp\s+l\s+a\s+in([\s\.,;:!?\-])', r'Explain\1', text)
        text = re.sub(r'\bEXP\s+L\s+A\s+IN([\s\.,;:!?\-])', r'EXPLAIN\1', text)
        
        # Handle possessive/plurals: "exp l a in's" -> "explain's", "exp l a ins" -> "explains"
        text = re.sub(r'\bexp\s+l\s+a\s+in([a-zA-Z])', r'explain\1', text)
        text = re.sub(r'\bExp\s+l\s+a\s+in([a-zA-Z])', r'Explain\1', text)
        text = re.sub(r'\bEXP\s+L\s+A\s+IN([a-zA-Z])', r'EXPLAIN\1', text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r'\b[Ee]xp\s+l\s+a\s+in\b', original_text))
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
    
    print(f"Fixing 'exp l a in' -> 'explain' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_explain_in_file(md_file)
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
