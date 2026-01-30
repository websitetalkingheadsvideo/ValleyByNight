#!/usr/bin/env python3
"""
Comprehensive fix for common split word patterns found in the files.
Based on the patterns discovered by find_split_words.py
"""

import re
from pathlib import Path

# Common split word patterns and their fixes
# Only include actual split words, not legitimate phrases
COMMON_FIXES = {
    # Most common split word patterns
    r'\bup on\b': 'upon',
    r'\bvamp i ric\b': 'vampiric',
    r'\bfor tun at ely\b': 'fortunately',
    r'\bor ig in al\b': 'original',
    r'\bin it i ate\b': 'initiate',
    r'\bat tent i on\b': 'attention',
    r'\bmani pula ti on\b': 'manipulation',
    r'\bdo min ate\b': 'dominate',
    r'\bor ien ted\b': 'oriented',
    r'\bsitu at i on\b': 'situation',
    r'\bc rim in al\b': 'criminal',
    r'\bcrea ti on\b': 'creation',
    r'\bof fic i al\b': 'official',
    r'\badd it i on\b': 'addition',
    r'\bor gan i zed\b': 'organized',
    r'\bfin an cia l\b': 'financial',
    r'\bor ig in ally\b': 'originally',
    r'\bdo d g ing\b': 'dodging',
    r'\bwh at ever\b': 'whatever',
    r'\bor gan ize\b': 'organize',
    r'\bdisc ret i on\b': 'discretion',
    r'\bdo min a ted\b': 'dominated',
    r'\bper miss i on\b': 'permission',
    r'\bof fe ring\b': 'offering',
    r'\bloc at i on\b': 'location',
    r'\bpre stat i on\b': 'prestation',
    r'\ball u ring\b': 'alluring',
    r'\bcan not\b': 'cannot',
    
    # Patterns with context (need to be more careful)
    r'\bof vamp i ric\b': 'of vampiric',
    r'\bthe vamp i ric\b': 'the vampiric',
    r'\bto eli min ate\b': 'to eliminate',
    r'\bto do min ate\b': 'to dominate',
    r'\bthe for mer\b': 'the former',
    r'\bthe for mati on\b': 'the formation',
    
    # Partial word fixes (be careful with these)
    r'\bor ig in\b': 'origin',  # but might break "original" - handle carefully
    r'\bin at i on\b': 'ination',  # part of "determination", "organization", etc.
    r'\biz at i on\b': 'ization',  # part of "organization", "realization", etc.
    r'\big in al\b': 'iginal',  # part of "original"
    r'\bput at i on\b': 'putation',  # part of "reputation"
    r'\bthe s it ate\b': 'the situate',  # might need context

    # LotNR-style mid-word splits (capital fragment)
    r'\bQui Ck\b': 'Quick',
    r'\bdis Ciplines\b': 'Disciplines',
    r'\bhierar Chy\b': 'hierarchy',
    r'\boF\b': 'of',
    r'\bpath oF\b': 'path of',
    r'\bChara Cter\b': 'Character',
    r'\bpro Cess\b': 'process',
    r'\bMoral ity\b': 'Morality',
    r'\bso Ft-hearted\b': 'soft-hearted',
    r'\bovemento Fthe\b': 'Movement of the',
    r'\bind ego otus\b': 'Mind',  # "ind ego otus" -> "Mind" in "Movement of the Mind" context
    r'\bF a o Cusing Bilities\b': 'Focusing Abilities',
}

def fix_text(text: str) -> tuple[str, int]:
    """
    Fix split words in text.
    Returns (fixed_text, number_of_changes)
    """
    changes = 0
    fixed_text = text
    
    # Apply fixes in order (longer patterns first to avoid partial matches)
    sorted_fixes = sorted(COMMON_FIXES.items(), key=lambda x: len(x[0]), reverse=True)
    
    for pattern, replacement in sorted_fixes:
        # Pattern already includes word boundaries or is a regex
        # Use it directly
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

def main() -> None:
    import sys
    default_folder = Path('reference/Books_md_ready_fixed_cleaned_v2')
    path = Path(sys.argv[1]) if len(sys.argv) > 1 else default_folder

    if not path.exists():
        print(f"Path not found: {path}")
        return

    if path.is_file():
        targets = [path]
        label = str(path)
    else:
        targets = sorted(path.glob('*.md'))
        label = str(path)

    print(f"Fixing common split words in {label}...")
    print("=" * 80)
    print()

    total_changes = 0
    files_fixed: list[tuple[str, int]] = []

    for md_file in targets:
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
