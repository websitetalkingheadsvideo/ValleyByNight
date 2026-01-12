#!/usr/bin/env python3
"""
Fix "the se" -> "these" and "c aern" -> "caern" across all files.
"""

import re
from pathlib import Path

def fix_patterns_in_file(file_path: Path) -> int:
    """Fix the se -> these and c aern -> caern in a single file."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        changes = 0
        
        # Fix "the se" -> "these" (preserving case)
        text_before = text
        text = re.sub(r'\bthe\s+se\b', 'these', text)
        text = re.sub(r'\bThe\s+se\b', 'These', text)
        text = re.sub(r'\bTHE\s+SE\b', 'THESE', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Tt]he\s+se\b', original_text))
        
        # Fix "c aern" -> "caern" (preserving case)
        text_before = text
        text = re.sub(r'\bc\s+aern\b', 'caern', text)
        text = re.sub(r'\bC\s+aern\b', 'Caern', text)
        text = re.sub(r'\bC\s+AERN\b', 'CAERN', text)
        # Also handle "c aern's" -> "caern's"
        text = re.sub(r'\bc\s+aern([\'s])', r'caern\1', text)
        text = re.sub(r'\bC\s+aern([\'s])', r'Caern\1', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Cc]\s+aern\b', original_text))
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
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
    
    print(f"Fixing 'the se' -> 'these' and 'c aern' -> 'caern' in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_patterns_in_file(md_file)
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
