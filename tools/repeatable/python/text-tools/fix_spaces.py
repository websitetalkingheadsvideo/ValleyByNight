import wordninja
import re
import os

# CONFIGURATION
INPUT_FOLDER = 'G:/VbN/reference/Books_md_ready/Decks'
OUTPUT_FOLDER = 'G:/VbN/reference/Books_md_ready_fixed/Decks'

# Words that should NOT be split (wordninja might incorrectly split these)
NO_SPLIT_WORDS = {
    'submission', 'duration', 'investigation', 'concentration', 'concepti', 'narrator',
    'storyteller', 'submissive', 'animalism', 'auspex', 'discipline', 'physical',
    'mental', 'social', 'vampire', 'vampiric', 'werewolf', 'garou', 'gnosis',
    'rage', 'pathos', 'glamour', 'blood', 'willpower', 'persuasion', 'cocoon',
    'technology', 'homid', 'fianna', 'metis', 'lupus', 'ragabash', 'intermediate',
    'advanced', 'basic', 'beast', 'frenzy', 'lucidity', 'torpor', 'comatose',
    'sympathetic', 'injury', 'possess', 'possession', 'dominance', 'astral',
    'realms', 'feral', 'habits', 'instinct', 'behavior', 'patterns', 'negative',
    'positive', 'traits', 'challenge', 'static', 'retest', 'succeed', 'failure',
    'success', 'attack', 'defense', 'damage', 'health', 'corpus', 'lethal',
    'bashing', 'aggravated', 'environmental', 'hazards', 'stamina', 'magical',
    'immobilizes', 'protects', 'dissolves', 'otherwise', 'renew', 'abilities',
    'electronic', 'devices', 'mechanical', 'straightforward', 'complex',
    'jamming', 'cease', 'unchanged', 'resume', 'wears', 'domesticated',
    'supernatural', 'creatures', 'regardless', 'commands', 'contrary', 'actually',
    'harm', 'perturb', 'soul', 'inexplicable', 'depression', 'withdraw', 'effectively',
    'active', 'emotions', 'maintain', 'lucidity', 'extended', 'intensity', 'moments',
    'psychosis', 'memories', 'psychological', 'trauma', 'remains', 'ineffect'
}

def fix_common_ocr_errors(text):
    """Fix common OCR errors before processing."""
    # Fix common split words (OCR artifacts) - case insensitive
    fixes = [
        (r'\bf\s+or\b', 'for'),
        (r'\bth\s+at\b', 'that'),
        (r'\bwh\s+at\b', 'what'),
        (r'\bth\s+e\b', 'the'),
        (r'\ban\s+d\b', 'and'),
        (r'\bi\s+n\s+to\b', 'into'),
        (r'\bw\s+ith\b', 'with'),
        (r'\bw\s+ith\s+out\b', 'without'),
        (r'\bm\s+ay\b', 'may'),
        (r'\bc\s+an\b', 'can'),
        (r'\bi\s+s\b', 'is'),
        (r'\by\s+ou\b', 'you'),
        (r'\by\s+our\b', 'your'),
        (r'\bth\s+ey\b', 'they'),
        (r'\bth\s+is\b', 'this'),
        (r'\bth\s+en\b', 'then'),
        (r'\bth\s+ere\b', 'there'),
        (r'\bwh\s+ich\b', 'which'),
        (r'\bwh\s+en\b', 'when'),
        (r'\bwh\s+ere\b', 'where'),
        (r'\bh\s+ow\b', 'how'),
        (r'\bh\s+as\b', 'has'),
        (r'\bh\s+ave\b', 'have'),
        (r'\bw\s+ill\b', 'will'),
        (r'\bh\s+ad\b', 'had'),
        (r'\bt\s+he\b', 'the'),
        (r'\bn\s+or\b', 'nor'),
        (r'\ba\s+re\b', 'are'),
        (r'\bw\s+as\b', 'was'),
    ]
    for pattern, replacement in fixes:
        text = re.sub(pattern, replacement, text, flags=re.IGNORECASE)
    return text

def fix_common_joined_words(text):
    """Fix common joined word patterns that appear frequently."""
    # Specific patterns we see in the text
    patterns = [
        (r'\bcounton\b', 'count on'),
        (r'\btowardyou\b', 'toward you'),
        (r'\bcarryout\b', 'carry out'),
        (r'\btheanimal\b', 'the animal'),
        (r'\btheremainder\b', 'the remainder'),
        (r'\bfavorablyinclined\b', 'favorably inclined'),
        (r'\btoyoureckoning\b', 'to your reckoning'),
        (r'\btoforce\b', 'to force'),
        (r'\bintosubmission\b', 'into submission'),
        (r'\bwithouteyes\b', 'without eyes'),
        (r'\benoughofamind\b', 'enough of a mind'),
        (r'\bBeasttoconnect\b', 'Beast to connect'),
        (r'\blargerbirds\b', 'larger birds'),
        (r'\boveryourprey\b', 'over your prey'),
        (r'\bcowinghumans\b', 'cowing humans'),
        (r'\balikeintosubmission\b', 'alike into submission'),
        (r'\bbackintolucidity\b', 'back into lucidity'),
        (r'\bInsuchacase\b', 'In such a case'),
        (r'\breturnstolucidity\b', 'returns to lucidity'),
        (r'\binsteadof\b', 'instead of'),
        (r'\batwhichyou\b', 'at which you'),
        (r'\bonceyouhave\b', 'once you have'),
        (r'\bautomaticallyawareof\b', 'automatically aware of'),
        (r'\btranspiresaroundyour\b', 'transpires around your'),
        (r'\biftheanimal\b', 'if the animal'),
        (r'\bbodyisslain\b', 'body is slain'),
        (r'\byoursouleturnstoyourbody\b', 'your soul turns to your body'),
        (r'\byouentertorpor\b', 'you enter torpor'),
        (r'\bshouldyouchoose\b', 'should you choose'),
        (r'\btoleave\b', 'to leave'),
        (r'\banimal\'sbody\b', "animal's body"),
        (r'\bdeclarehisntntatthininf\b', 'declare his intent in the'),
        (r'\btheturn\b', 'the turn'),
        (r'\bFleeingtheanimalbody\b', 'Fleeing the animal body'),
        (r'\bdoesntrequire\b', "doesn't require"),
        (r'\banaction\b', 'an action'),
        (r'\bstillact\b', 'still act'),
        (r'\bnormallyinthe\b', 'normally in the'),
        (r'\bturnthatyouintend\b', 'turn that you intend'),
        (r'\btoreturntoyourbody\b', 'to return to your body'),
        (r'\bIfyouareinjured\b', 'If you are injured'),
        (r'\bwhileattmptingtoretutoyourwbody\b', 'while attempting to return to your body'),
        (r'\byoumustmake\b', 'you must make'),
        (r'\bonthe\b', 'on the'),
        (r'\bintothe\b', 'into the'),
        (r'\bwiththe\b', 'with the'),
        (r'\bfromthe\b', 'from the'),
        (r'\bforthe\b', 'for the'),
        (r'\bofthe\b', 'of the'),
        (r'\btothe\b', 'to the'),
    ]
    for pattern, replacement in patterns:
        text = re.sub(pattern, replacement, text, flags=re.IGNORECASE)
    return text

def should_try_split(word):
    """Determine if a word should be attempted for splitting."""
    word_lower = word.lower()
    
    # Skip words in blacklist
    if word_lower in NO_SPLIT_WORDS:
        return False
    
    # Skip if it's markdown syntax
    if re.match(r'^[#\-\*\[\]\(\)`]+$', word):
        return False
    
    # Skip if it's clearly a single word (has punctuation indicating it's complete)
    if len(word) < 8 and re.search(r'[.!?,;:\)\]\}\-\']', word):
        return False
    
    # Skip very short words (likely not joined)
    if len(word) < 8:
        return False
    
    # Skip if it looks like a proper noun or acronym (all caps short word)
    if len(word) <= 5 and word.isupper():
        return False
    
    # Only try to split words that are longer (likely to be joined)
    return len(word) >= 10

def split_word(word):
    """Attempt to split a word using wordninja, with validation."""
    if not should_try_split(word):
        return [word]
    
    try:
        split_words = wordninja.split(word)
        # Only use split if it produces multiple reasonable words
        if len(split_words) > 1:
            # Validate: original word should be close to combined length
            total_len = sum(len(w) for w in split_words)
            # Allow some variance (punctuation, etc.)
            if total_len <= len(word) + 2:  # Allow up to 2 chars difference
                # Check if any of the split words are in our blacklist
                split_lower = [w.lower() for w in split_words]
                if not any(w in NO_SPLIT_WORDS for w in split_lower):
                    return split_words
    except:
        pass
    
    return [word]

def split_words_in_text(text):
    """Use wordninja to split joined words intelligently."""
    words = text.split()
    fixed_words = []
    
    for word in words:
        split_result = split_word(word)
        fixed_words.extend(split_result)
    
    return ' '.join(fixed_words)

def fix_markdown_headers_in_text(text):
    """Fix markdown headers that appear mid-paragraph."""
    lines = text.split('\n')
    fixed_lines = []
    
    for line in lines:
        # Look for headers in the middle of text (not at line start)
        if re.search(r'[a-z]\s*##\s+[A-Z]', line) or re.search(r'\w\s+##\s+', line):
            # Split the line at headers - be more aggressive
            # Split on ## that appears after word characters
            parts = re.split(r'(\s+##\s+[^\n]+)', line)
            for i, part in enumerate(parts):
                if part.strip().startswith('##'):
                    # This is a header - ensure it's on its own line
                    if fixed_lines and fixed_lines[-1].strip():
                        fixed_lines.append('')  # Blank line before header
                    fixed_lines.append(part.strip())
                    fixed_lines.append('')  # Blank line after header
                elif part.strip():
                    fixed_lines.append(part.strip())
        else:
            fixed_lines.append(line)
    
    return '\n'.join(fixed_lines)

def fix_markdown_formatting(text):
    """Fix markdown formatting issues."""
    # First, fix headers in mid-paragraph
    text = fix_markdown_headers_in_text(text)
    
    lines = text.splitlines()
    fixed_lines = []
    
    for i, line in enumerate(lines):
        stripped = line.strip()
        
        # Handle markdown headers at start of line
        if stripped.startswith('#'):
            match = re.match(r'^(\#+\s+)(.+)', stripped)
            if match:
                prefix = match.group(1)
                content = match.group(2)
                fixed_content = split_words_in_text(content)
                fixed_lines.append(prefix + fixed_content)
                # Ensure blank line after header (unless next line is also a header)
                if i + 1 < len(lines) and not lines[i + 1].strip().startswith('#'):
                    if not fixed_lines or fixed_lines[-1] != '':
                        fixed_lines.append('')
            else:
                fixed_lines.append(line)
        # Handle list items
        elif stripped.startswith(('-', '*')) or re.match(r'^\s*\d+\.', stripped):
            match = re.match(r'^(\s*[-*\d.]+\s+)(.+)', line)
            if match:
                prefix = match.group(1)
                content = match.group(2)
                fixed_content = split_words_in_text(content)
                fixed_lines.append(prefix + fixed_content)
            else:
                fixed_lines.append(split_words_in_text(line))
        # Handle regular text
        else:
            if stripped:  # Only process non-empty lines
                fixed_lines.append(split_words_in_text(line))
            else:
                fixed_lines.append('')
    
    # Clean up excessive blank lines (more than 2 in a row)
    result_lines = []
    blank_count = 0
    for line in fixed_lines:
        if not line.strip():
            blank_count += 1
            if blank_count <= 2:
                result_lines.append('')
        else:
            blank_count = 0
            result_lines.append(line)
    
    return '\n'.join(result_lines)

def fix_text_spacing(text):
    """Main function to fix text spacing and formatting."""
    # First fix common OCR errors
    text = fix_common_ocr_errors(text)
    
    # Fix common joined word patterns
    text = fix_common_joined_words(text)
    
    # Then fix markdown formatting and word spacing
    text = fix_markdown_formatting(text)
    
    # Final cleanup: fix camelCase patterns
    text = re.sub(r'([a-z])([A-Z][a-z])', r'\1 \2', text)
    
    return text

def process_all_files():
    """Process all markdown files in the input folder."""
    if not os.path.exists(OUTPUT_FOLDER):
        os.makedirs(OUTPUT_FOLDER)

    print(f"Starting batch process in: {INPUT_FOLDER}")
    
    for filename in os.listdir(INPUT_FOLDER):
        if filename.endswith(".md") and not filename.endswith(".bak"):
            input_path = os.path.join(INPUT_FOLDER, filename)
            output_path = os.path.join(OUTPUT_FOLDER, filename)
            
            print(f"Processing: {filename}...")
            
            try:
                with open(input_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                fixed_content = fix_text_spacing(content)
                
                with open(output_path, 'w', encoding='utf-8') as f:
                    f.write(fixed_content)
                    
                print(f"  Completed: {filename}")
            except Exception as e:
                print(f"  Error processing {filename}: {e}")
                
    print(f"\nDone! All files saved to: {OUTPUT_FOLDER}")

if __name__ == "__main__":
    process_all_files()
