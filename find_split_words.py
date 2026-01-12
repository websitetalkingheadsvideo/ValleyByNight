#!/usr/bin/env python3
"""
Find potential split word errors based on patterns we've already fixed.
This script looks for words that appear to be incorrectly split with spaces.
"""

import re
from pathlib import Path
from collections import defaultdict

def find_potential_split_words(text: str) -> list:
    """
    Find potential split words by looking for patterns:
    1. Short word fragments (1-4 chars) separated by spaces
    2. Sequences that when combined form common English words
    3. Patterns similar to ones we've already fixed
    """
    potential_errors = []
    
    # Pattern 1: 2-4 short words (1-4 chars each) separated by single spaces
    # This catches patterns like "con ven t ions", "or ga niz", etc.
    pattern1 = r'\b([a-zA-Z]{1,4})\s+([a-zA-Z]{1,4})\s+([a-zA-Z]{1,4})(?:\s+([a-zA-Z]{1,4}))?\b'
    
    for match in re.finditer(pattern1, text):
        groups = [g for g in match.groups() if g]
        if len(groups) >= 3:
            combined = ''.join(groups)
            original = match.group(0)
            
            # Only flag if:
            # 1. Combined word is 6+ characters (likely a real word)
            # 2. Has vowels (likely a word, not just consonants)
            # 3. Not already in our known fixes list
            if len(combined) >= 6 and any(c in 'aeiouAEIOU' for c in combined):
                # Check if it looks like a common word pattern
                # Words ending in -tion, -sion, -ing, -ed, -er, -ly are common
                common_suffixes = ['tion', 'sion', 'ing', 'ed', 'er', 'ly', 'al', 'ic', 'ous', 'ive', 'ate', 'ize']
                if any(combined.lower().endswith(suffix) for suffix in common_suffixes):
                    potential_errors.append({
                        'pattern': original,
                        'combined': combined,
                        'line': text[:match.start()].count('\n') + 1,
                        'context': get_context(text, match.start(), match.end())
                    })
    
    # Pattern 2: Two-word splits that form common words
    # Like "in to" (should be "into"), "with in" (should be "within")
    pattern2 = r'\b([a-zA-Z]{2,5})\s+([a-zA-Z]{1,4})\b'
    
    common_two_word_fixes = {
        'in to': 'into',
        'with in': 'within',
        'with out': 'without',
        'for ward': 'forward',
        'back ward': 'backward',
        'up on': 'upon',
        'a part': 'apart',
        'a side': 'aside',
        'a way': 'away',
        'a gain': 'again',
        'a bout': 'about',
        'a round': 'around',
        'a cross': 'across',
        'a long': 'along',
        'a mong': 'among',
        'a live': 'alive',
        'a lone': 'alone',
        'a loud': 'aloud',
        'a sleep': 'asleep',
        'a wake': 'awake',
    }
    
    for match in re.finditer(pattern2, text):
        original = match.group(0).lower()
        if original in common_two_word_fixes:
            potential_errors.append({
                'pattern': match.group(0),
                'combined': common_two_word_fixes[original],
                'line': text[:match.start()].count('\n') + 1,
                'context': get_context(text, match.start(), match.end())
            })
    
    # Pattern 3: Three-word splits that form common words
    # Like "the m selves" (should be "themselves")
    pattern3 = r'\b([a-zA-Z]{1,3})\s+([a-zA-Z]{1,3})\s+([a-zA-Z]{2,6})\b'
    
    common_three_word_fixes = {
        'the m selves': 'themselves',
        'the ir selves': 'themselves',
        'the m selves': 'themselves',
        'for get ten': 'forgotten',
        'be com ing': 'becoming',
        'be fore': 'before',
        'be hind': 'behind',
        'be low': 'below',
        'be side': 'beside',
        'be tween': 'between',
        'be yond': 'beyond',
    }
    
    for match in re.finditer(pattern3, text):
        original = match.group(0).lower()
        if original in common_three_word_fixes:
            potential_errors.append({
                'pattern': match.group(0),
                'combined': common_three_word_fixes[original],
                'line': text[:match.start()].count('\n') + 1,
                'context': get_context(text, match.start(), match.end())
            })
    
    return potential_errors

def get_context(text: str, start: int, end: int, context_chars: int = 50) -> str:
    """Get context around a match."""
    context_start = max(0, start - context_chars)
    context_end = min(len(text), end + context_chars)
    context = text[context_start:context_end]
    # Replace newlines with spaces for display
    context = context.replace('\n', ' ')
    # Highlight the match
    match_text = text[start:end]
    context = context.replace(match_text, f"**{match_text}**", 1)
    return context.strip()

def analyze_file(file_path: Path) -> list:
    """Analyze a single file for potential split words."""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
        
        errors = find_potential_split_words(text)
        return errors
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return []

def main():
    folder = Path('reference/Books_md_ready_fixed_cleaned_v2')
    
    if not folder.exists():
        print(f"Folder not found: {folder}")
        return
    
    print(f"Scanning {folder} for potential split word errors...")
    print("=" * 80)
    print()
    
    all_errors = defaultdict(list)
    total_errors = 0
    
    for md_file in sorted(folder.glob('*.md')):
        errors = analyze_file(md_file)
        if errors:
            all_errors[md_file.name] = errors
            total_errors += len(errors)
            print(f"{md_file.name}: {len(errors)} potential errors")
    
    print()
    print("=" * 80)
    print(f"Total: {total_errors} potential split word errors found across {len(all_errors)} files")
    print("=" * 80)
    print()
    
    # Show detailed results
    if all_errors:
        print("\nDETAILED RESULTS:")
        print("=" * 80)
        
        # Group by pattern
        pattern_counts = defaultdict(int)
        pattern_examples = defaultdict(list)
        
        for filename, errors in all_errors.items():
            for error in errors:
                pattern = error['pattern'].lower()
                pattern_counts[pattern] += 1
                if len(pattern_examples[pattern]) < 3:  # Keep up to 3 examples per pattern
                    pattern_examples[pattern].append({
                        'file': filename,
                        'line': error['line'],
                        'context': error['context']
                    })
        
        # Sort by frequency
        sorted_patterns = sorted(pattern_counts.items(), key=lambda x: x[1], reverse=True)
        
        print(f"\nTop patterns found (showing first 50):")
        print("-" * 80)
        
        for pattern, count in sorted_patterns[:50]:
            print(f"\n'{pattern}' appears {count} times")
            print(f"  Suggested fix: '{pattern_examples[pattern][0].get('combined', '???')}'")
            print(f"  Examples:")
            for example in pattern_examples[pattern][:2]:
                print(f"    - {example['file']}:{example['line']}")
                print(f"      {example['context'][:100]}...")
        
        # Save to file
        output_file = Path('potential_split_words_report.txt')
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write("POTENTIAL SPLIT WORD ERRORS REPORT\n")
            f.write("=" * 80 + "\n\n")
            f.write(f"Total potential errors: {total_errors}\n")
            f.write(f"Files with errors: {len(all_errors)}\n\n")
            
            for pattern, count in sorted_patterns:
                f.write(f"\n'{pattern}': {count} occurrences\n")
                for example in pattern_examples[pattern]:
                    f.write(f"  {example['file']}:{example['line']} - {example['context']}\n")
        
        print(f"\n\nFull report saved to: {output_file}")
    else:
        print("No potential split word errors found!")

if __name__ == '__main__':
    main()
