#!/usr/bin/env python3
"""
PDF Text Cleaning Script
Cleans common PDF extraction artifacts from text files.

Artifacts handled:
1. Broken line breaks (rejoin split sentences)
2. Missing spaces between words
3. Extra/multiple spaces
4. Header/footer noise (page numbers, order numbers)
5. Isolated single characters
6. Image placeholders
7. Hyphenation issues
8. Encoding errors
9. Broken paragraphs
"""

import re
import os
from pathlib import Path
from typing import List, Tuple


def remove_image_placeholders(text: str) -> str:
    """Remove HTML-style image placeholders."""
    text = re.sub(r'<!--\s*image\s*-->', '', text, flags=re.IGNORECASE)
    return text


def remove_header_footer_noise(text: str) -> str:
    """Remove common header/footer patterns."""
    # Remove order numbers like "Sean Carter (order #19521)"
    text = re.sub(r'Sean\s+Carter\s*\(order\s*#\d+\)', '', text, flags=re.IGNORECASE)
    text = re.sub(r'\(order\s*#\d+\)', '', text)
    
    # Remove standalone page numbers (lines with just numbers)
    text = re.sub(r'^\d+\s*$', '', text, flags=re.MULTILINE)
    
    # Remove copyright/address blocks that appear repeatedly
    text = re.sub(r'735\s+[PARK\s+]*NORTH\s+BLVD[\.]?\s*SUITE\s*\d+', '', text, flags=re.IGNORECASE)
    text = re.sub(r'CLARKSTON,\s*GA\s*\d+', '', text, flags=re.IGNORECASE)
    
    return text


def remove_isolated_characters(text: str) -> str:
    """Remove lines containing only single characters or very short noise."""
    lines = text.split('\n')
    cleaned_lines = []
    
    for line in lines:
        stripped = line.strip()
        # Skip lines with only 1-2 characters (likely noise)
        # But keep legitimate single-character lines like "I" at start of sentences
        # We'll be more conservative - only remove if it's clearly noise
        if len(stripped) <= 1 and stripped and stripped not in ['I', 'A', 'a']:
            continue
        # Skip lines that are just punctuation or whitespace
        if stripped and re.match(r'^[^\w\s]+$', stripped):
            continue
        cleaned_lines.append(line)
    
    return '\n'.join(cleaned_lines)


def fix_hyphenation(text: str) -> str:
    """Rejoin words broken by hyphens at line endings."""
    # Pattern: word ending with hyphen, newline, then continuation
    # Match hyphenated words split across lines
    text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
    
    # Also handle cases where hyphen is followed by space and newline
    text = re.sub(r'(\w+)\s*-\s*\n\s*(\w+)', r'\1\2', text)
    
    return text


def fix_missing_spaces(text: str) -> str:
    """Add missing spaces between words that are incorrectly joined."""
    # Common short words that often get concatenated
    common_words = [
        'of', 'the', 'and', 'or', 'is', 'are', 'was', 'were', 'has', 'have', 
        'had', 'do', 'does', 'did', 'can', 'could', 'will', 'would', 'should',
        'that', 'this', 'with', 'for', 'from', 'not', 'all', 'but', 'you',
        'your', 'they', 'them', 'their', 'there', 'then', 'than', 'what',
        'when', 'where', 'which', 'who', 'why', 'how', 'into', 'onto', 'upon'
    ]
    
    # Fix patterns like "ofall" -> "of all" (common word + lowercase word)
    for word in common_words:
        # Word followed by lowercase letter (missing space)
        text = re.sub(rf'\b{word}([a-z])', rf'{word} \1', text, flags=re.IGNORECASE)
        # Word followed by uppercase letter (missing space)
        text = re.sub(rf'\b{word}([A-Z])', rf'{word} \1', text, flags=re.IGNORECASE)
    
    # Fix "is(or" -> "is (or", "isnot" -> "is not"
    text = re.sub(r'\bis\(', r'is (', text, flags=re.IGNORECASE)
    text = re.sub(r'\bisnot\b', r'is not', text, flags=re.IGNORECASE)
    
    # Fix patterns like "Whyin" -> "Why in" (word ending in lowercase, next word starts uppercase)
    text = re.sub(r'([a-z])([A-Z][a-z])', r'\1 \2', text)
    
    # Fix patterns like "We,asStorytellers" -> "We, as Storytellers"
    text = re.sub(r',([A-Z][a-z])', r', \1', text)
    text = re.sub(r',([a-z])', r', \1', text)
    
    # Fix specific known compound words that got joined
    # Be very specific to avoid breaking legitimate words
    known_compounds = [
        (r'Masqueradebreaches', r'Masquerade breaches'),
        (r'overcomplicatedsystem', r'overcomplicated system'),
        (r'systemofnumbers', r'system of numbers'),
        (r'proportionsofnumbers', r'proportions of numbers'),
        (r'numberstoeachother', r'numbers to each other'),
        (r'eachotherand', r'each other and'),
        (r'eachotherand([a-z])', r'each other and \1'),
        (r'statisticsthat', r'statistics that'),
    ]
    
    for pattern, replacement in known_compounds:
        text = re.sub(pattern, replacement, text, flags=re.IGNORECASE)
    
    # Fix broken contractions: "do n't" -> "don't", "the y" -> "they", etc.
    text = re.sub(r'\bdo\s+n\'?t\b', r"don't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bthe\s+y\b', r'they', text, flags=re.IGNORECASE)
    text = re.sub(r'\bwere\s+n\'?t\b', r"weren't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bare\s+n\'?t\b', r"aren't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bis\s+n\'?t\b', r"isn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bwas\s+n\'?t\b', r"wasn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bhave\s+n\'?t\b', r"haven't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bhas\s+n\'?t\b', r"hasn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bwould\s+n\'?t\b', r"wouldn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bcould\s+n\'?t\b', r"couldn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bshould\s+n\'?t\b', r"shouldn't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bwill\s+n\'?t\b', r"won't", text, flags=re.IGNORECASE)
    text = re.sub(r'\bcan\s+n\'?t\b', r"can't", text, flags=re.IGNORECASE)
    
    # Fix very long concatenated words (20+ chars) - likely multiple words joined
    # Only break at uppercase boundaries to be safe
    text = re.sub(r'([a-z]{15,})([A-Z][a-z]{3,})', r'\1 \2', text)
    
    return text


def fix_extra_spaces(text: str) -> str:
    """Normalize multiple spaces to single space."""
    # Replace 2+ spaces with single space
    text = re.sub(r' {2,}', ' ', text)
    
    # Fix spaces around punctuation (but preserve intentional spacing)
    text = re.sub(r'\s+([,\.;:!?])', r'\1', text)
    text = re.sub(r'([,\.;:!?])\s+', r'\1 ', text)
    
    # Fix "o f   the" -> "of the" (common PDF artifact)
    text = re.sub(r'\bo\s+f\s+', 'of ', text, flags=re.IGNORECASE)
    text = re.sub(r'\bo\s+f\s+the\b', 'of the', text, flags=re.IGNORECASE)
    
    return text


def rejoin_broken_lines(text: str) -> str:
    """Rejoin lines that were incorrectly split."""
    lines = text.split('\n')
    rejoined_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i].strip()
        
        # Skip empty lines for now
        if not line:
            rejoined_lines.append('')
            i += 1
            continue
        
        # If line doesn't end with sentence-ending punctuation, try to rejoin
        if not re.search(r'[.!?:]$', line):
            # Look ahead to see if next non-empty line should be joined
            j = i + 1
            while j < len(lines) and not lines[j].strip():
                j += 1
            
            if j < len(lines):
                next_line = lines[j].strip()
                # If next line starts with lowercase (not a new sentence), rejoin
                if next_line and next_line[0].islower():
                    line = line + ' ' + next_line
                    i = j + 1
                else:
                    i += 1
            else:
                i += 1
        else:
            i += 1
        
        rejoined_lines.append(line)
    
    return '\n'.join(rejoined_lines)


def fix_encoding_issues(text: str) -> str:
    """Fix common encoding errors."""
    # Common PDF encoding issues
    replacements = {
        'â€™': "'",  # Smart apostrophe
        'â€œ': '"',  # Opening quote
        'â€': '"',   # Closing quote
        'â€"': '—',  # Em dash
        'â€"': '–',  # En dash
        'â€¢': '•',  # Bullet
        'â€¦': '…',  # Ellipsis
    }
    
    for old, new in replacements.items():
        text = text.replace(old, new)
    
    return text


def clean_paragraphs(text: str) -> str:
    """Clean up paragraph structure."""
    # Remove excessive blank lines (more than 2 consecutive)
    text = re.sub(r'\n{3,}', '\n\n', text)
    
    # Ensure proper spacing around headers (##)
    text = re.sub(r'\n+##', '\n\n##', text)
    
    # Clean up spacing around list items
    text = re.sub(r'\n+-\s+', '\n- ', text)
    
    return text


def clean_text(text: str) -> str:
    """Apply all cleaning transformations in order."""
    # Order matters - do more specific fixes first
    
    # 1. Remove image placeholders
    text = remove_image_placeholders(text)
    
    # 2. Fix encoding issues
    text = fix_encoding_issues(text)
    
    # 3. Remove header/footer noise
    text = remove_header_footer_noise(text)
    
    # 4. Fix hyphenation
    text = fix_hyphenation(text)
    
    # 5. Fix missing spaces
    text = fix_missing_spaces(text)
    
    # 6. Fix extra spaces
    text = fix_extra_spaces(text)
    
    # 7. Rejoin broken lines
    text = rejoin_broken_lines(text)
    
    # 8. Remove isolated characters
    text = remove_isolated_characters(text)
    
    # 9. Clean paragraph structure
    text = clean_paragraphs(text)
    
    # Final pass: trim each line
    lines = [line.rstrip() for line in text.split('\n')]
    text = '\n'.join(lines)
    
    # Remove trailing whitespace
    text = text.rstrip()
    
    return text


def process_file(input_path: Path, output_path: Path = None, backup: bool = True) -> None:
    """Process a single file."""
    if output_path is None:
        output_path = input_path
    
    # Read file
    try:
        with open(input_path, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading {input_path}: {e}")
        return
    
    # Create backup if requested
    if backup and input_path == output_path:
        backup_path = input_path.with_suffix(input_path.suffix + '.bak')
        try:
            with open(backup_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Backup created: {backup_path}")
        except Exception as e:
            print(f"Warning: Could not create backup: {e}")
    
    # Clean content
    cleaned = clean_text(content)
    
    # Write cleaned content
    try:
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(cleaned)
        print(f"Cleaned: {output_path}")
    except Exception as e:
        print(f"Error writing {output_path}: {e}")


def main():
    """Main entry point."""
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Clean PDF extraction artifacts from text files'
    )
    parser.add_argument(
        'input_dir',
        type=str,
        help='Directory containing files to clean'
    )
    parser.add_argument(
        '--output-dir',
        type=str,
        default=None,
        help='Output directory (default: overwrite input files)'
    )
    parser.add_argument(
        '--no-backup',
        action='store_true',
        help='Do not create backup files'
    )
    parser.add_argument(
        '--ext',
        type=str,
        default='.md',
        help='File extension to process (default: .md)'
    )
    
    args = parser.parse_args()
    
    input_dir = Path(args.input_dir)
    if not input_dir.exists():
        print(f"Error: Directory {input_dir} does not exist")
        return
    
    output_dir = Path(args.output_dir) if args.output_dir else None
    if output_dir and not output_dir.exists():
        output_dir.mkdir(parents=True, exist_ok=True)
    
    # Find all files with specified extension
    files = list(input_dir.glob(f'*{args.ext}'))
    
    if not files:
        print(f"No {args.ext} files found in {input_dir}")
        return
    
    print(f"Found {len(files)} files to process")
    
    for file_path in files:
        if output_dir:
            output_path = output_dir / file_path.name
        else:
            output_path = file_path
        
        process_file(file_path, output_path, backup=not args.no_backup)
    
    print(f"\nProcessed {len(files)} files")


if __name__ == '__main__':
    main()

