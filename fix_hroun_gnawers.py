#!/usr/bin/env python3
"""
Fix "hro un" -> "hroun" and "Gnaw ers" -> "Gnawers" across all files.
"""

import re
from pathlib import Path

def fix_patterns_in_file(file_path: Path) -> int:
    """Fix hro un -> hroun and Gnaw ers -> Gnawers in a single file, preserving case."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        original_text = text
        changes = 0
        
        # Fix "hro un" -> "hroun" (preserving case)
        # This is part of "Ahroun" so we need to be careful
        text_before = text
        # First handle "A hro un" -> "Ahroun" (before fixing standalone "hro un")
        text = re.sub(r'\bA\s+hro\s+un\b', 'Ahroun', text)
        text = re.sub(r'\ba\s+hro\s+un\b', 'ahroun', text)
        # Then fix standalone "hro un" -> "hroun"
        text = re.sub(r'\bhro\s+un\b', 'hroun', text)
        text = re.sub(r'\bHro\s+un\b', 'Hroun', text)
        text = re.sub(r'\bHRO\s+UN\b', 'HROUN', text)
        # Also handle "A hroun" -> "Ahroun" (in case "hro un" was already fixed to "hroun")
        text = re.sub(r'\bA\s+hroun\b', 'Ahroun', text)
        text = re.sub(r'\ba\s+hroun\b', 'ahroun', text)
        # Handle possessive/plurals: "hro un's" -> "hroun's"
        text = re.sub(r'\bhro\s+un([a-zA-Z])', r'hroun\1', text)
        text = re.sub(r'\bHro\s+un([a-zA-Z])', r'Hroun\1', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Hh]ro\s+un\b', original_text))
            changes += len(re.findall(r'\bA\s+hroun\b', original_text))
        
        # Fix "Gnaw ers" -> "Gnawers" (preserving case)
        text_before = text
        text = re.sub(r'\bGnaw\s+ers\b', 'Gnawers', text)
        text = re.sub(r'\bgnaw\s+ers\b', 'gnawers', text)
        text = re.sub(r'\bGNAW\s+ERS\b', 'GNAWERS', text)
        # Handle possessive: "Gnaw ers'" -> "Gnawers'"
        text = re.sub(r'\bGnaw\s+ers([\'s])', r'Gnawers\1', text)
        text = re.sub(r'\bgnaw\s+ers([\'s])', r'gnawers\1', text)
        if text != text_before:
            changes += len(re.findall(r'\b[Gg]naw\s+ers\b', original_text))
        
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
    
    print(f"Fixing 'hro un' -> 'hroun' and 'Gnaw ers' -> 'Gnawers' in {folder}...")
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
