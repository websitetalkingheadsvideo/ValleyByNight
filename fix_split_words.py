#!/usr/bin/env python3
"""
Fix incorrectly split words in markdown files.
Words that were split by OCR/text processing (e.g., "con ven t ions" -> "conventions")
"""

import re
import os
from pathlib import Path
from typing import List, Tuple, Dict

# Known split word patterns - word fragments that should be combined
KNOWN_SPLITS: Dict[str, str] = {
    # Common patterns found
    r'con ven t ions': 'conventions',
    r'or ga niz at ions': 'organizations',
    r'or ga niz action': 'organization',
    r'tr adit i on': 'tradition',
    r'tele vis i on': 'television',
    r'do cu men ted': 'documented',
    r'do cu men t': 'document',
    r'in format i on': 'information',
    r'i mag in at i on': 'imagination',
    r'N arr at or': 'Narrator',
    r'found at i on': 'foundation',
    r'person as': 'personas',
    r'person a': 'persona',
    r'all ow s': 'allows',
    r'all ow': 'allow',
    r'all ies': 'allies',
    r'for got ten': 'forgotten',
    r'for got': 'forgot',
    r'do u bt': 'doubt',
    r'se at': 'seat',
    r'for ms': 'forms',
    r'for m': 'form',
    r'civil iz at i on': 'civilization',
    r'disse min ate': 'disseminate',
    r'all owing': 'allowing',
    r'ide a': 'idea',
    r'act i on': 'action',
    r'medi at or': 'mediator',
    r'gre at': 'great',
    r'rem a in': 'remain',
    r'with in': 'within',
    r'in to': 'into',
    r'get her': 'gather',
    r'the m selves': 'themselves',
    r'the ir': 'their',
    r'the re': 'there',
    r'of ten': 'often',
    r'for ward': 'forward',
    r'break down': 'breakdown',
    r'were wolves': 'werewolves',
    r'c ulm in action': 'culmination',
    r'Garo u': 'Garo u',  # Keep as is - might be proper name
    r'Garo u': 'Garou',
    r'c ulm in': 'culmin',
    r'who le sale': 'wholesale',
    r'ly or ien ted': 'oriented',
    r'secular ly or ien ted': 'secularly oriented',
    r'do es': 'does',
    r'do esn': "doesn",
    r'do ne': 'done',
    r'se me': 'some',
    r'se me thing': 'something',
    r'with in': 'within',
    r'for ced': 'forced',
    r'term in al': 'terminal',
    r'are as': 'areas',
    r'for ces': 'forces',
    r'kin e': 'kindred',  # Context-dependent, but likely
    r'out right': 'outright',
    r'to get her': 'together',
    r'other sects': 'others',  # Context-dependent
    r'with out': 'without',
    r'with in': 'within',
    r'back se at': 'backseat',
    r'mufti medi a': 'multimedia',
    r'stereo and cable': 'stereo and cable',  # Keep
    r'do l by': 'Dolby',  # Proper name
    r'do l by stereo': 'Dolby stereo',
    r'back se at': 'backseat',
    r'for ms': 'forms',
    r'tele vis i on': 'television',
    r'radio film': 'radio film',  # Keep
    r'mufti medi a': 'multimedia',
    r'civil iz at i on': 'civilization',
    r'disse min ate': 'disseminate',
    r'in format i on': 'information',
    r'all owing': 'allowing',
    r'ide a': 'idea',
    r'act i on': 'action',
    r'medi at or': 'mediator',
    r'gre at': 'great',
    r'rem a in': 'remain',
    r'with in': 'within',
    r'in to': 'into',
    r'get her': 'gather',
    r'for got ten': 'forgotten',
    r'for got': 'forgot',
    r'do u bt': 'doubt',
    r'se at': 'seat',
    r'for ms': 'forms',
    r'for m': 'form',
    r'civil iz at i on': 'civilization',
    r'disse min ate': 'disseminate',
    r'all owing': 'allowing',
    r'ide a': 'idea',
    r'act i on': 'action',
    r'medi at or': 'mediator',
    r'gre at': 'great',
    r'rem a in': 'remain',
    r'with in': 'within',
    r'in to': 'into',
    r'get her': 'gather',
    r'Daunt a in': 'Dauntain',
    r'fa e': 'fae',
    r'see lie': 'seelie',
    r'Un see lie': 'Unseelie',
    r'See lie': 'Seelie',
    r'aspect ed': 'aspected',
    r'fa e': 'fae',
    r'Dre aming': 'Dreaming',
    r'Dre': 'Dream',
    r'Assam ites': 'Assamites',
    r'diable rie': 'diablerie',
    r'Bru jah': 'Brujah',
    r'Treme re': 'Tremere',
    r'Au spex': 'Auspex',
    r'Tzi misc e': 'Tzimisce',
    r'Ven true': 'Ventrue',
    r'not or io us': 'notorious',
    r'are as': 'areas',
    r'for ces': 'forces',
    r'kin e': 'kindred',
    r'out right': 'outright',
    r'to get her': 'together',
    r'with out': 'without',
    r'with in': 'within',
    r'for ced': 'forced',
    r'term in al': 'terminal',
    r'do es': 'does',
    r'do esn': "doesn",
    r'do ne': 'done',
    r'se me': 'some',
    r'se me thing': 'something',
}

def find_split_word_patterns(text: str) -> List[Tuple[str, str]]:
    """
    Heuristically find split words that aren't in the known list.
    Looks for sequences of 1-4 character words separated by single spaces.
    """
    fixes: List[Tuple[str, str]] = []
    
    # Pattern: 2-4 short words (1-4 chars each) separated by spaces
    # But not at start/end of line, and not if it's clearly multiple words
    pattern = r'\b([a-zA-Z]{1,4})\s+([a-zA-Z]{1,4})\s+([a-zA-Z]{1,4})(?:\s+([a-zA-Z]{1,4}))?\b'
    
    for match in re.finditer(pattern, text):
        groups = [g for g in match.groups() if g]
        if len(groups) >= 3:
            combined = ''.join(groups)
            # Only suggest if combined word is 6+ chars (likely a real word)
            if len(combined) >= 6:
                original = match.group(0)
                # Skip if it's in our known list (already handled)
                if original not in KNOWN_SPLITS:
                    # Check if it looks like a real word (has vowels)
                    if any(c in 'aeiouAEIOU' for c in combined):
                        fixes.append((original, combined))
    
    return fixes

def fix_text(text: str, dry_run: bool = False) -> Tuple[str, List[str]]:
    """
    Fix split words in text.
    Returns (fixed_text, list_of_changes)
    """
    changes: List[str] = []
    fixed_text = text
    
    # Apply known fixes
    for pattern, replacement in KNOWN_SPLITS.items():
        # Use word boundaries to avoid partial matches
        regex = r'\b' + re.escape(pattern) + r'\b'
        matches = list(re.finditer(regex, fixed_text, re.IGNORECASE))
        if matches:
            for match in reversed(matches):  # Reverse to preserve positions
                original = match.group(0)
                # Preserve case
                if original.isupper():
                    replacement_upper = replacement.upper()
                    fixed_text = fixed_text[:match.start()] + replacement_upper + fixed_text[match.end():]
                    changes.append(f"  {original} -> {replacement_upper}")
                elif original[0].isupper():
                    replacement_title = replacement.capitalize()
                    fixed_text = fixed_text[:match.start()] + replacement_title + fixed_text[match.end():]
                    changes.append(f"  {original} -> {replacement_title}")
                else:
                    fixed_text = fixed_text[:match.start()] + replacement + fixed_text[match.end():]
                    changes.append(f"  {original} -> {replacement}")
    
    return fixed_text, changes

def process_file(file_path: Path, dry_run: bool = False) -> Tuple[int, List[str]]:
    """Process a single file and return number of changes and list of changes."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            original_text = f.read()
        
        fixed_text, changes = fix_text(original_text, dry_run)
        
        if changes and not dry_run:
            with open(file_path, 'w', encoding='utf-8', errors='ignore') as f:
                f.write(fixed_text)
        
        return len(changes), changes
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return 0, []

def main():
    folder = Path('reference/Books_md_ready_fixed_cleaned_v2')
    
    if not folder.exists():
        print(f"Folder not found: {folder}")
        return
    
    print(f"Scanning {folder} for split words...")
    print()
    
    # First pass: dry run to show what would be fixed
    print("=" * 60)
    print("DRY RUN - Showing what would be fixed:")
    print("=" * 60)
    print()
    
    total_changes = 0
    files_with_changes = []
    
    for md_file in sorted(folder.glob('*.md')):
        num_changes, changes = process_file(md_file, dry_run=True)
        if num_changes > 0:
            files_with_changes.append((md_file, num_changes, changes))
            total_changes += num_changes
            print(f"{md_file.name}: {num_changes} fixes")
            # Show first 5 changes per file
            for change in changes[:5]:
                print(change)
            if len(changes) > 5:
                print(f"  ... and {len(changes) - 5} more")
            print()
    
    print("=" * 60)
    print(f"Total: {total_changes} fixes across {len(files_with_changes)} files")
    print("=" * 60)
    print()
    
    if total_changes == 0:
        print("No split words found to fix.")
        return
    
    # Auto-apply (can be changed to ask for confirmation if needed)
    import sys
    auto_apply = '--yes' in sys.argv or '-y' in sys.argv
    
    if not auto_apply:
        # Ask for confirmation
        try:
            response = input("Apply these fixes? (yes/no): ").strip().lower()
            if response not in ['yes', 'y']:
                print("Cancelled.")
                return
        except (EOFError, KeyboardInterrupt):
            print("\nCancelled.")
            return
    
    # Second pass: apply fixes
    print()
    print("=" * 60)
    print("Applying fixes...")
    print("=" * 60)
    print()
    
    total_applied = 0
    for md_file, num_changes, _ in files_with_changes:
        num_applied, _ = process_file(md_file, dry_run=False)
        total_applied += num_applied
        print(f"Fixed {md_file.name}: {num_applied} changes")
    
    print()
    print("=" * 60)
    print(f"Done! Applied {total_applied} fixes.")
    print("=" * 60)

if __name__ == '__main__':
    main()
