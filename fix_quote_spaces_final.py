#!/usr/bin/env python3
"""
Fix spaces around single quotes in phrases - comprehensive fix.
' of the Wyrm ' -> 'of the Wyrm'
is' of the Wyrm ' -> is 'of the Wyrm'
"""

import re
from pathlib import Path

def fix_quote_spaces_in_file(file_path: Path) -> int:
    """Fix spaces around single quotes in phrases."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Pattern 1: space + quote + space + content + space + quote + space
        # ' of the Wyrm ' -> 'of the Wyrm'
        text = re.sub(r"\s+'\s+([^']+?)\s+'\s+", r" '\1' ", text)
        
        # Pattern 2: word directly followed by quote with spaces inside
        # is' of the Wyrm ' -> is 'of the Wyrm'
        text = re.sub(r"([a-zA-Z])'\s+([^']+?)\s+'(\s)", r"\1 '\2'\3", text)
        
        # Pattern 3: quote with spaces inside, followed directly by word
        # ' of the Wyrm 'and -> 'of the Wyrm' and
        text = re.sub(r"(\s)'\s+([^']+?)\s+'([a-zA-Z])", r"\1'\2' \3", text)
        
        # Pattern 4: at end of sentence
        text = re.sub(r"\s+'\s+([^']+?)\s+'\s*([\.,;:!?])", r" '\1'\2", text)
        text = re.sub(r"([a-zA-Z])'\s+([^']+?)\s+'([\.,;:!?])", r"\1 '\2'\3", text)
        
        # Pattern 5: at start after punctuation
        text = re.sub(r"([\.,;:!?])\s+'\s+([^']+?)\s+'(\s)", r"\1 '\2'\3", text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes
            changes = len(re.findall(r"\s+'\s+[^']+\s+'\s+", original_text))
            changes += len(re.findall(r"[a-zA-Z]'\s+[^']+\s+'", original_text))
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
    
    print(f"Fixing spaces around single quotes in {folder}...")
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = fix_quote_spaces_in_file(md_file)
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
