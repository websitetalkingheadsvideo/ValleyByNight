#!/usr/bin/env python3
"""
Fix remaining split word patterns found in the second scan.
Focuses on actual split words, not legitimate phrases.
"""

import re
from pathlib import Path

# Remaining split word patterns to fix
REMAINING_FIXES = {
    # Actual split words (not phrases)
    r'\bto of fer\b': 'to offer',
    r'\bcond it i on\b': 'condition',
    r'\bapp lica ti on\b': 'application',
    r'\bill um in ate\b': 'illuminate',
    r'\bfor what ever\b': 'for whatever',
    r'\bto what ever\b': 'to whatever',
    r'\bdec is i on\b': 'decision',
    r'\bre do ing\b': 'redoing',
    r'\bis ola ted\b': 'isolated',
    r'\bthe not i on\b': 'the notion',
    r'\bthe fin al\b': 'the final',
    r'\bs way over\b': 'sway over',
    r'\bre vol uti on\b': 'revolution',
    r'\bfun c ti on\b': 'function',
    r'\bthe dura ti on\b': 'the duration',
    r'\bhe s it ate\b': 'hesitate',
    r'\bof fens ive\b': 'offensive',
    r'\bas not ed\b': 'as noted',
    r'\bof fic i ally\b': 'officially',
    r'\ba will ing\b': 'a willing',
    r'\bfor c ing\b': 'forcing',
    r'\bthe card in al\b': 'the cardinal',
    
    # Partial word fixes (be careful - these are parts of larger words)
    r'\biga ti on\b': 'igation',  # part of "investigation"
    r'\bthe or e tic\b': 'theoretic',  # part of "theoretically"
    r'\bin a ting\b': 'inating',  # part of "incriminating", "exterminating"
    r'\bthe m how ever\b': 'them however',
}

def fix_text(text: str) -> tuple[str, int]:
    """
    Fix split words in text.
    Returns (fixed_text, number_of_changes)
    """
    changes = 0
    fixed_text = text
    
    # Apply fixes in order (longer patterns first to avoid partial matches)
    sorted_fixes = sorted(REMAINING_FIXES.items(), key=lambda x: len(x[0]), reverse=True)
    
    for pattern, replacement in sorted_fixes:
        matches = list(re.finditer(pattern, fixed_text, re.IGNORECASE))
        if matches:
            # Replace in reverse order to preserve positions
            for match in reversed(matches):
                original = match.group(0)
                # Preserve case
                if original.isupper():
                    replacement_upper = replacement.upper()
                    fixed_text = fixed_text[:match.start()] + replacement_upper + fixed_text[match.end():]
                    changes += 1
                elif original[0].isupper():
                    replacement_title = replacement.capitalize()
                    fixed_text = fixed_text[:match.start()] + replacement_title + fixed_text[match.end():]
                    changes += 1
                else:
                    fixed_text = fixed_text[:match.start()] + replacement + fixed_text[match.end():]
                    changes += 1
    
    return fixed_text, changes

def process_file(file_path: Path) -> int:
    """Process a single file and return number of changes."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            original_text = f.read()
        
        fixed_text, changes = fix_text(original_text)
        
        if changes > 0:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(fixed_text)
        
        return changes
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return 0

def main():
    folder = Path('reference/Books_md_ready_fixed_cleaned_v2')
    
    if not folder.exists():
        print(f"Folder not found: {folder}")
        return
    
    print(f"Fixing remaining split words in {folder}...")
    print("=" * 80)
    print()
    
    total_changes = 0
    files_fixed = []
    
    for md_file in sorted(folder.glob('*.md')):
        changes = process_file(md_file)
        if changes > 0:
            files_fixed.append((md_file.name, changes))
            total_changes += changes
            print(f"Fixed {md_file.name}: {changes} changes")
    
    print()
    print("=" * 80)
    print(f"Done! Fixed {total_changes} instances across {len(files_fixed)} files.")
    print("=" * 80)

if __name__ == '__main__':
    main()
