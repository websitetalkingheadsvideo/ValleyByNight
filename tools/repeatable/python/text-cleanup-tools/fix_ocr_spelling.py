#!/usr/bin/env python3
"""
OCR Spelling Correction Script
Fixes OCR spelling errors in markdown files while preserving game terminology.

Uses pattern-based OCR fixes first, then spell checker for obvious errors.
"""

import re
import os
from pathlib import Path
from typing import Dict, List, Tuple, Set
from collections import defaultdict

try:
    from spellchecker import SpellChecker
    HAS_SPELLCHECKER = True
except ImportError:
    HAS_SPELLCHECKER = False


# Game terminology dictionary - words that should NOT be corrected
GAME_TERMS = {
    # Clans
    'Brujah', 'Ventrue', 'Toreador', 'Nosferatu', 'Malkavian', 'Tremere',
    'Gangrel', 'Lasombra', 'Tzimisce', 'Assamite', 'Ravnos', 'Setite',
    'Giovanni', 'Salubri', 'Cappadocian',
    
    # Sects
    'Camarilla', 'Sabbat', 'Anarch', 'Cainite', 'Kindred',
    
    # Disciplines
    'Celerity', 'Potence', 'Presence', 'Fortitude', 'Obfuscate', 'Animalism',
    'Dominate', 'Auspex', 'Thaumaturgy', 'Protean', 'Quietus', 'Serpentis',
    'Chimerstry', 'Necromancy', 'Obeah', 'Valeren', 'Vicissitude',
    
    # Game terms (including plurals and variations)
    'Embrace', 'Embraced', 'Embraces', 'Embracing',
    'Childe', 'Childer', 'Childes', 'Sire', 'Sires', 'Sired', 'Siring',
    'Elder', 'Elders', 'Fledgling', 'Fledglings', 'Neonate', 'Neonates',
    'Antediluvian', 'Antediluvians', 'Justicar', 'Justicars',
    'Prince', 'Princes', 'Primogen', 'Primogens', 'Harpy', 'Harpies',
    'Whip', 'Whips', 'Rötschreck', 'Frenzy', 'Frenzied', 'Frenzies',
    'Masquerade', 'Elysium', 'Conclave', 'Conclaves',
    'Beast', 'Beasts', 'Hunger', 'Hungers', 'Vitae', 'Generation', 'Generations',
    'Blood Bond', 'Blood Bonds', 'Ghoul', 'Ghouls',
    'Clanmate', 'Clanmates', 'Sectmate', 'Sectmates',
    'Diablerie', 'Diablerist', 'Diablerize', 'Diablerized', 'Diablerizes',
    'Domitor', 'Domitors',
    
    # Locations
    'Thorns', 'Carthage',
    
    # Other
    'Mind\'s Eye', 'Storyteller', 'Storytellers', 'Storytelling',
}


# Common abbreviations/acronyms to skip
SKIP_WORDS = {
    'IPOs', 'IPO', 'IRS', 'ISBN', 'FAQ', 'FAQs', 'LARP', 'MET', 'VTM',
    'WWW', 'SMG', 'SWAT', 'FBI', 'CEO', 'CEOs', 'CD', 'CDs', 'DJ', 'DJs',
    'ATMs', 'ATM',
}


# Pattern-based OCR replacements (applied first, more reliable)
OCR_REPLACEMENTS = [
    # GLYPH patterns - OCR artifacts for special characters
    (r'GLYPH\s+lt\s+\d+\s+gt\s*', "'"),  # GLYPH lt 213 gt -> apostrophe
    (r'GLYPH\s*lt\s*\d+\s*gt\s*', "'"),  # GLYPHlt213gt -> apostrophe (no spaces)
    (r'GLYPH\s+lt\s+\d+\s+gt', "'"),     # GLYPH lt 213 gt (no trailing space)
    
    # Split words - common OCR errors
    (r'\bt\s+s\s+he\b', 'the'),
    (r'\bt\s+he\b', 'the'),
    (r'\bw\s+ith\b', 'with'),
    (r'\bw\s+ithout\b', 'without'),
    (r'\bw\s+hen\b', 'when'),
    (r'\bw\s+here\b', 'where'),
    (r'\bw\s+hat\b', 'what'),
    (r'\bw\s+ho\b', 'who'),
    (r'\bw\s+hy\b', 'why'),
    (r'\bw\s+ill\b', 'will'),
    (r'\bw\s+ould\b', 'would'),
    (r'\bw\s+as\b', 'was'),
    (r'\bw\s+ere\b', 'were'),
    (r'\bw\s+h\s+h\s+ho\s+untsthe\s+unters\b', 'who hunts the hunters'),
    (r'\bw\s+h\s+h\s+ho\s+unts\s+the\s+hunters\b', 'who hunts the hunters'),
    
    # Theatre/Theater split words
    (r'\bthe\s+ater\b', 'Theatre'),
    (r'\bthe\s+at\s+re\b', 'Theatre'),
    (r'\bThe\s+ater\b', 'Theatre'),
    (r'\bThe\s+at\s+re\b', 'Theatre'),
    (r'\bthe\s+at\s+rer\b', 'Theatre'),  # "the at rer" -> "Theatre"
    (r'\bthe\s+at\s+resetting\b', 'theatre setting'),  # "the at resetting" -> "theatre setting"
    
    # Specific OCR errors found in text
    (r'\baBBat\b', 'Sabbat'),
    (r'\ba\s+BBat\b', 'the Sabbat'),
    (r'\btook\s+threw\b', 'took their'),
    (r'\btook\s+their\s+their\b', 'took their'),  # Fix duplicate from replacement
    (r'\bbattle\s+ground\s+to\s+a\b', 'battle came to a'),
    (r'\bground\s+to\s+a\s+stalemate\b', 'came to a stalemate'),
    (r'\bem\s+Bra\s+Ce\b', 'Embrace'),
    (r'\bthe\s+em\s+Bra\s+Ce\b', 'the Embrace'),
    (r'\baCCounting\b', 'Accounting'),
    (r'\bthe\s+aCCounting\b', 'the Accounting'),
    
    # Title case fixes for section headers (line-start patterns)
    (r'^the Embrace ', 'The Embrace ', re.MULTILINE),
    (r'^the Sabbat ', 'The Sabbat ', re.MULTILINE),
    (r'^all the rest ', 'All the Rest ', re.MULTILINE),
    (r'^who hunts the hunters ', 'Who Hunts the Hunters ', re.MULTILINE),
    (r'^the First tradition:', 'The First tradition:', re.MULTILINE),
    (r'^the Fourth tradition:', 'The Fourth tradition:', re.MULTILINE),
    (r'^the Fifth tradition:', 'The Fifth tradition:', re.MULTILINE),
    (r'^the Generation Spread ', 'The Generation Spread ', re.MULTILINE),
    (r'^the Clans$', 'The Clans', re.MULTILINE),
    (r'^the Bloodlines$', 'The Bloodlines', re.MULTILINE),
    (r'^the Flaws ', 'The Flaws ', re.MULTILINE),
    
    # Severely corrupted titles
    (r'^i t n … n hese ights ', 'In These Nights ', re.MULTILINE),
    (r'u F n : C p nited nderthe inal ights rossover owers ', 'Crossover Powers ', re.MULTILINE),
    (r'\bFourth\s+tradition:\s+the\s+aCCounting\b', 'Fourth tradition: the Accounting'),
    
    # Name corrections (author/character names)
    (r'\bRand i Jo Bruner\b', 'Randi Jo Bruner'),
    (r'\bJ as on Carl\b', 'Jason Carl'),
    (r'\bJ as on Fe lds te in\b', 'Jason Feldstein'),
    (r'\bFe lds te in\b', 'Feldstein'),
    (r'\bPeter Wood worth\b', 'Peter Woodworth'),
    (r'\bDiane Pir on Gel m an\b', 'Diane Piron-Gelman'),
    (r'\bAar on Vos s\b', 'Aaron Voss'),
    (r'\bMatt Mil berger\b', 'Matt Milberger'),
    (r'\bLa ur a Rubles\b', 'Laura Rubles'),
]


def build_custom_dictionary(text: str, known_terms: Set[str]) -> Set[str]:
    """Build custom dictionary from text and known terms."""
    dictionary = set(known_terms)
    dictionary.update(SKIP_WORDS)
    
    # Extract capitalized words (likely proper nouns/terms)
    capitalized_words = re.findall(r'\b[A-Z][a-z]+\b', text)
    dictionary.update(capitalized_words)
    
    # Extract multi-word capitalized phrases
    phrases = re.findall(r'\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b', text)
    for phrase in phrases:
        # Add both full phrase and individual words
        dictionary.add(phrase)
        dictionary.update(phrase.split())
    
    return dictionary


def apply_ocr_replacements(text: str) -> str:
    """Apply pattern-based OCR fixes (most reliable)."""
    for item in OCR_REPLACEMENTS:
        # Handle tuples with flags (pattern, replacement, flags) or without (pattern, replacement)
        if len(item) == 3:
            pattern, replacement, flags = item
            text = re.sub(pattern, replacement, text, flags=flags)
        else:
            pattern, replacement = item
            text = re.sub(pattern, replacement, text, flags=re.IGNORECASE)
    
    # Clean up duplicate words that might have been created
    text = re.sub(r'\b(\w+)\s+\1\b', r'\1', text, flags=re.IGNORECASE)
    
    # Fix double apostrophes from GLYPH removal (with or without spaces)
    text = re.sub(r"'\s*'", "'", text)  # Double apostrophe (with optional space) -> single
    
    # Fix common patterns after GLYPH removal
    text = re.sub(r"Mind's\s+Eye\s+the\s+ater", "Mind's Eye Theatre", text, flags=re.IGNORECASE)
    text = re.sub(r"Mind's\s+Eye\s+the\s+at\s+re", "Mind's Eye Theatre", text, flags=re.IGNORECASE)
    text = re.sub(r"Mind\s+Eye\s+the\s+ater", "Mind's Eye Theatre", text, flags=re.IGNORECASE)
    text = re.sub(r"Mind\s+Eye\s+the\s+at\s+re", "Mind's Eye Theatre", text, flags=re.IGNORECASE)
    text = re.sub(r"Eye\s+the\s+at\s+rer", "Eye Theatre", text, flags=re.IGNORECASE)
    text = re.sub(r"Eye\s+the\s+at\s+resetting", "Eye Theatre setting", text, flags=re.IGNORECASE)
    text = re.sub(r"Mind\s+'s\s+Eye\s+theatre", "Mind's Eye Theatre", text, flags=re.IGNORECASE)
    
    return text


def find_obvious_spelling_errors(text: str, spell_checker, custom_dict: Set[str]) -> List[Tuple[str, str]]:
    """
    Find obvious spelling errors using spell checker.
    Only corrects words that are clearly wrong, not ambiguous cases.
    Very conservative - only fixes obvious errors.
    """
    errors = []
    
    if not spell_checker:
        return errors
    
    # Extract unique words with their context
    # Only check words 5+ chars (skip short truncated words)
    words = set(re.findall(r'\b[a-zA-Z]{5,}\b', text))
    
    # Build lowercase dictionary for fast lookup
    dict_lower = {term.lower(): term for term in custom_dict}
    
    for word in words:
        word_lower = word.lower()
        
        # Skip if in custom dictionary
        if word_lower in dict_lower:
            continue
        
        # Skip all-caps abbreviations
        if word.isupper() and len(word) <= 5:
            continue
        
        # Skip words with mixed case (likely proper nouns)
        if word[0].isupper() and any(c.isupper() for c in word[1:]):
            continue
        
        # Skip words ending in common suffixes that might be truncated (conservative)
        if word_lower.endswith(('act', 'char', 'accom', 'activ', 'apprecia')):
            continue
        
        # Check if word is misspelled
        if word_lower not in spell_checker:
            # Get suggestion
            suggested = spell_checker.correction(word_lower)
            
            # Only correct if suggestion is clearly different and reasonable
            if suggested and suggested != word_lower and len(suggested) >= 5:
                # Check if suggestion is actually in dictionary
                if suggested in spell_checker:
                    # Only add if the correction makes sense:
                    # - Similar length (within 3 chars)
                    # - Starts with same letter
                    # - Not too short (avoid short truncations)
                    length_diff = abs(len(suggested) - len(word))
                    if (length_diff <= 3 and 
                        suggested[0].lower() == word[0].lower() and
                        len(suggested) >= 4):
                        # Preserve capitalization
                        if word[0].isupper():
                            suggested = suggested.capitalize()
                        errors.append((word, suggested))
    
    return errors


def process_file(input_path: Path, output_path: Path = None, 
                dry_run: bool = False, interactive: bool = False,
                custom_dict: Set[str] = None) -> Dict:
    """Process a single file for OCR spelling errors."""
    if output_path is None:
        output_path = input_path
    
    # Read file
    try:
        with open(input_path, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
    except Exception as e:
        return {'error': f"Error reading file: {e}", 'corrections': 0}
    
    original_content = content
    
    # Initialize spell checker
    spell_checker = None
    if HAS_SPELLCHECKER:
        spell_checker = SpellChecker()
    
    # Build custom dictionary
    if custom_dict is None:
        custom_dict = set(GAME_TERMS)
    custom_dict = build_custom_dictionary(content, custom_dict)
    
    # Step 1: Apply pattern-based OCR replacements (most reliable)
    content = apply_ocr_replacements(content)
    
    # Step 2: Find obvious spelling errors
    errors = find_obvious_spelling_errors(content, spell_checker, custom_dict)
    
    # Build corrections dictionary
    corrections = {}
    for word, suggested in errors:
        if word not in corrections:
            corrections[word] = suggested
    
    # Apply spell checker corrections
    if corrections:
        sorted_corrections = sorted(corrections.items(), key=lambda x: len(x[0]), reverse=True)
        for incorrect, correct in sorted_corrections:
            pattern = r'\b' + re.escape(incorrect) + r'\b'
            content = re.sub(pattern, correct, content)
    
    # Check if any changes were made
    pattern_changes = content != original_content
    total_changes = len(corrections)
    
    if dry_run:
        # Show what would be changed
        print(f"\n=== {input_path.name} ===")
        if pattern_changes:
            print(f"Pattern-based fixes applied (OCR replacements)")
        if corrections:
            print(f"Spell checker corrections ({len(corrections)} words):")
            for word, suggested in sorted(corrections.items()):
                print(f"  '{word}' -> '{suggested}'")
        else:
            print("No spelling errors found")
        return {'corrections': total_changes, 'changes': corrections, 'dry_run': True}
    
    # Write corrected content
    if not interactive or input(f"\nApply {total_changes} corrections to {input_path.name}? (y/n): ").lower() == 'y':
        try:
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(content)
            return {'corrections': total_changes, 'changes': corrections}
        except Exception as e:
            return {'error': f"Error writing file: {e}", 'corrections': 0}
    
    return {'corrections': 0, 'skipped': True}


def main():
    """Main entry point."""
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Fix OCR spelling errors in markdown files'
    )
    parser.add_argument(
        'input',
        type=str,
        help='Input file or directory'
    )
    parser.add_argument(
        '--output',
        type=str,
        default=None,
        help='Output file or directory (default: overwrite input)'
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Show what would be changed without modifying files'
    )
    parser.add_argument(
        '--interactive',
        action='store_true',
        help='Ask before applying corrections'
    )
    parser.add_argument(
        '--ext',
        type=str,
        default='.md',
        help='File extension to process (default: .md)'
    )
    
    args = parser.parse_args()
    
    # Check for spell checker
    if not HAS_SPELLCHECKER:
        print("Warning: pyspellchecker not installed. Only pattern-based fixes will be applied.")
        print("Install for full spell checking: pip install pyspellchecker")
    
    input_path = Path(args.input)
    
    if input_path.is_file():
        # Process single file
        output_path = Path(args.output) if args.output else input_path
        result = process_file(input_path, output_path, args.dry_run, 
                            args.interactive)
        if 'error' in result:
            print(f"Error: {result['error']}")
        elif result.get('corrections', 0) > 0 or not args.dry_run:
            if args.dry_run:
                pass  # Already printed
            else:
                print(f"Corrected {result.get('corrections', 0)} words")
    
    elif input_path.is_dir():
        # Process directory
        output_dir = Path(args.output) if args.output else input_path
        
        files = list(input_path.glob(f'*{args.ext}'))
        if not files:
            print(f"No {args.ext} files found in {input_path}")
            return
        
        print(f"Found {len(files)} files to process")
        
        total_corrections = 0
        for file_path in files:
            if output_dir != input_path:
                output_path = output_dir / file_path.name
            else:
                output_path = file_path
            
            result = process_file(file_path, output_path, args.dry_run,
                                args.interactive)
            if 'error' in result:
                print(f"Error processing {file_path.name}: {result['error']}")
            else:
                total_corrections += result.get('corrections', 0)
        
        print(f"\nTotal: {total_corrections} words corrected across {len(files)} files")
    
    else:
        print(f"Error: {input_path} does not exist")


if __name__ == '__main__':
    main()
