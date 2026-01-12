#!/usr/bin/env python3
"""
Fix spaces around single quotes in phrases.
' of the Wyrm ' -> 'of the Wyrm'
"""

import re
from pathlib import Path

def fix_quote_spaces_in_file(file_path: Path) -> int:
    """Fix spaces around single quotes in phrases."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        
        # Fix pattern: space + quote + space + content + space + quote + space
        # This matches phrases like ' of the Wyrm ' -> 'of the Wyrm'
        # We want to remove spaces immediately before opening quote and after closing quote
        
        # Pattern: space(s) + ' + space(s) + content + space(s) + ' + space(s)
        # Replace with: space + ' + content (trimmed) + ' + space
        # This preserves word boundaries but removes spaces around quotes
        
        # Match phrases with spaces around quotes, but preserve the surrounding context
        # \s+'\s+([^']+?)\s+'\s+ -> ' \1' 
        text = re.sub(r"(\S)\s+'\s+([^']+?)\s+'\s+(\S)", r"\1 '\2' \3", text)
        
        # Handle at end of sentence/line
        text = re.sub(r"(\S)\s+'\s+([^']+?)\s+'\s*([\.,;:!?])", r"\1 '\2'\3", text)
        
        # Handle at start of sentence/line  
        text = re.sub(r"([\.,;:!?])\s+'\s+([^']+?)\s+'\s+(\S)", r"\1 '\2' \3", text)
        
        # Handle standalone phrases (with spaces on both sides)
        text = re.sub(r"\s+'\s+([^']+?)\s+'\s+", r" '\1' ", text)
        
        # Handle edge case: word directly followed by quote with space after opening quote
        # e.g., "is' of the Wyrm '" -> "is 'of the Wyrm'"
        text = re.sub(r"(\S)'\s+([^']+?)\s+'(\s)", r"\1 '\2'\3", text)
        
        # Handle edge case: quote with space before closing quote, followed by word
        # e.g., "' of the Wyrm 'and" -> "'of the Wyrm' and"
        text = re.sub(r"(\s)'\s+([^']+?)\s+'(\S)", r"\1'\2' \3", text)
        
        # Handle case where there's no space before opening quote but space after it
        # e.g., "is' of the Wyrm '" -> "is 'of the Wyrm'"
        text = re.sub(r"([a-zA-Z])'\s+([^']+?)\s+'(\s)", r"\1 '\2'\3", text)
        
        if text != original_text:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(text)
            # Count changes - count how many quoted phrases with spaces we fixed
            original_matches = len(re.findall(r"\s+'\s+[^']+\s+'\s+", original_text))
            return original_matches
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
