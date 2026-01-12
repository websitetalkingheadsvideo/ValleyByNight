#!/usr/bin/env python3
"""
Smart fix for OCR fragments - merge with preceding word if it forms a valid word.
"""

import re
from pathlib import Path

# Common valid words
VALID_WORDS = {
    'appear', 'appears', 'appeared', 'appearing',
    'happen', 'happens', 'happened', 'happening',
    'matter', 'matters', 'mattered', 'mattering',
    'better', 'betters', 'bettered', 'bettering',
    'blood', 'bloods', 'blooded', 'blooding',
    'good', 'goods', 'gooded', 'gooding',
    'wood', 'woods', 'wooded', 'wooding',
    'stood', 'food', 'mood', 'hood', 'rood', 'flood', 'brood', 'strode',
    'been', 'seen', 'green', 'queen', 'screen', 'sheen', 'treen', 'preen', 'unseen',
    'middle', 'middles', 'middled', 'middling',
    'riddle', 'riddles', 'riddled', 'riddling',
    'fiddle', 'fiddles', 'fiddled', 'fiddling',
    'piddle', 'piddles', 'piddled', 'piddling',
    'twiddle', 'twiddles', 'twiddled', 'twiddling',
    'summer', 'summers', 'summered', 'summering',
    'hammer', 'hammers', 'hammered', 'hammering',
    'stammer', 'stammers', 'stammered', 'stammering',
    'jammer', 'jammers', 'jammere', 'jammere',
    'glimmer', 'glimmers', 'glimmered', 'glimmering',
    'affect', 'affects', 'affected', 'affecting',
    'effect', 'effects', 'effected', 'effecting',
    'dollar', 'dollars', 'dollared', 'dollaring',
    'collar', 'collars', 'collared', 'collaring',
    'cellar', 'cellars', 'cellared', 'cellaring',
    'pillar', 'pillars', 'pillared', 'pillaring',
    'feel', 'feels', 'felt', 'feeling',
    'wheel', 'wheels', 'wheeled', 'wheeling',
    'heel', 'heels', 'heeled', 'heeling',
    'keel', 'keels', 'keeled', 'keeling',
    'peel', 'peels', 'peeled', 'peeling',
    'reel', 'reels', 'reeled', 'reeling',
    'seel', 'seels', 'seeled', 'seeling',
    'steel', 'steels', 'steeled', 'steeling',
    'meet', 'meets', 'met', 'meeting',
    'feet', 'street', 'streets', 'street',
    'detect', 'detects', 'detected', 'detecting',
    'protect', 'protects', 'protected', 'protecting',
    'mess', 'messes', 'messed', 'messing',
    'pass', 'passes', 'passed', 'passing',
    'mass', 'masses', 'massed', 'massing',
    'grass', 'grasses', 'grassed', 'grassing',
    'class', 'classes', 'classed', 'classing',
    'litter', 'litters', 'littered', 'littering',
    'bitter', 'bitters', 'bittered', 'bittering',
    'fitter', 'fitters', 'fittered', 'fittering',
    'hitter', 'hitters', 'hittered', 'hittering',
    'sitter', 'sitters', 'sittered', 'sittering',
    'witter', 'witters', 'wittered', 'wittering',
    'traffic', 'traffics', 'trafficked', 'trafficking',
    'coffee', 'coffees',
    'suffer', 'suffers', 'suffered', 'suffering',
    'sniffer', 'sniffers', 'sniffered', 'sniffering',
    'immerse', 'immerses', 'immersed', 'immersing',
    'immersion', 'immersions',
    'immediate', 'immediately',
    'name', 'names', 'named', 'naming',
    'examine', 'examines', 'examined', 'examining',
    'assess', 'assesses', 'assessed', 'assessing',
    'versus',
}

def is_valid_word(word):
    """Check if word is valid."""
    return word.lower() in VALID_WORDS

# Specific transformations for common OCR errors
# Format: (preceding_pattern, fragment_pattern, transformation_function)
TRANSFORMATIONS = [
    # Simple merges (preceding + fragment = word)
    (r'\bdo\b', r'\bllar\b', lambda p, f: 'dollar'),
    (r'\buns\b', r'\been\b', lambda p, f: 'unseen'),
    
    # Transformations (need letter changes)
    (r'\bmid\b', r'\blle\b', lambda p, f: 'middle'),  # Mid + lle -> Middle
    (r'\ba\b', r'\bffc\b', lambda p, f: 'affect'),   # a + ffc -> affect
    (r'\ba\b', r'\bffe\b', lambda p, f: 'affect'),   # a + ffe -> affect
    (r'\bdet\b', r'\beet\b', lambda p, f: 'detect'), # det + eet -> detect
    (r'\bprot\b', r'\beet\b', lambda p, f: 'protect'), # prot + eet -> protect
    (r'\bst\b', r'\beet\b', lambda p, f: 'street'),  # st + eet -> street
    (r'\bster\b', r'\beet\b', lambda p, f: 'street'), # ster + eet -> street
    (r'\bhas\b', r'\been\b', lambda p, f: 'has been'), # has + een -> has been (two words)
    (r'\bhad\b', r'\been\b', lambda p, f: 'had been'), # had + een -> had been
    (r'\bis\b', r'\been\b', lambda p, f: 'is been'),   # is + een -> is been (or "is seen" - context dependent)
    (r'\bwell\b', r'\been\b', lambda p, f: 'well been'), # well + een -> well been
    (r'\bpoint\b', r'\been\b', lambda p, f: 'point been'), # point + een -> point been
    (r'\bof\b', r'\been\b', lambda p, f: 'of green'),  # of + een -> of green
    (r'\bma\b', r'\btter\b', lambda p, f: 'matter'),  # ma + tter -> matter
    (r'\bbe\b', r'\btter\b', lambda p, f: 'better'),  # be + tter -> better
    (r'\bno\b', r'\btter\b', lambda p, f: 'no matter'), # no + tter -> no matter
    (r'\ba\b', r'\bood\b', lambda p, f: 'a good'),    # a + ood -> a good
    (r'\band\b', r'\bood\b', lambda p, f: 'and good'), # and + ood -> and good
    (r'\ball\b', r'\bood\b', lambda p, f: 'all good'), # all + ood -> all good
    (r'\bno\b', r'\bood\b', lambda p, f: 'no good'),  # no + ood -> no good
    (r'\blittle\b', r'\bood\b', lambda p, f: 'little good'), # little + ood -> little good
    (r'\bbl\b', r'\bood\b', lambda p, f: 'blood'),    # bl + ood -> blood
    (r'\bg\b', r'\bood\b', lambda p, f: 'good'),      # g + ood -> good
    (r'\bw\b', r'\bood\b', lambda p, f: 'wood'),      # w + ood -> wood
    (r'\bst\b', r'\bood\b', lambda p, f: 'stood'),    # st + ood -> stood
    (r'\bf\b', r'\bood\b', lambda p, f: 'food'),      # f + ood -> food
    (r'\bm\b', r'\bood\b', lambda p, f: 'mood'),      # m + ood -> mood
    (r'\bh\b', r'\bood\b', lambda p, f: 'hood'),      # h + ood -> hood
    (r'\br\b', r'\bood\b', lambda p, f: 'rood'),      # r + ood -> rood
    (r'\bfl\b', r'\bood\b', lambda p, f: 'flood'),    # fl + ood -> flood
    (r'\bbr\b', r'\bood\b', lambda p, f: 'brood'),    # br + ood -> brood
    (r'\bstr\b', r'\bood\b', lambda p, f: 'strode'),  # str + ood -> strode
    (r'\bsu\b', r'\bmmer\b', lambda p, f: 'summer'),  # su + mmer -> summer
    (r'\bha\b', r'\bmmer\b', lambda p, f: 'hammer'),  # ha + mmer -> hammer
    (r'\bsta\b', r'\bmmer\b', lambda p, f: 'stammer'), # sta + mmer -> stammer
    (r'\bja\b', r'\bmmer\b', lambda p, f: 'jammer'),  # ja + mmer -> jammer
    (r'\bgl\b', r'\bmmer\b', lambda p, f: 'glimmer'),  # gl + mmer -> glimmer
    (r'\bf\b', r'\beel\b', lambda p, f: 'feel'),      # f + eel -> feel
    (r'\bwh\b', r'\beel\b', lambda p, f: 'wheel'),    # wh + eel -> wheel
    (r'\bhe\b', r'\beel\b', lambda p, f: 'heel'),     # he + eel -> heel
    (r'\bke\b', r'\beel\b', lambda p, f: 'keel'),     # ke + eel -> keel
    (r'\bpe\b', r'\beel\b', lambda p, f: 'peel'),     # pe + eel -> peel
    (r'\bre\b', r'\beel\b', lambda p, f: 'reel'),     # re + eel -> reel
    (r'\bse\b', r'\beel\b', lambda p, f: 'seel'),      # se + eel -> seel
    (r'\bste\b', r'\beel\b', lambda p, f: 'steel'),    # ste + eel -> steel
    (r'\bm\b', r'\beet\b', lambda p, f: 'meet'),      # m + eet -> meet
    (r'\bf\b', r'\beet\b', lambda p, f: 'feet'),      # f + eet -> feet
    (r'\bme\b', r'\beet\b', lambda p, f: 'meet'),      # me + eet -> meet
    (r'\bfe\b', r'\beet\b', lambda p, f: 'feet'),      # fe + eet -> feet
    (r'\bm\b', r'\bsse\b', lambda p, f: 'mess'),      # m + sse -> mess
    (r'\bme\b', r'\bsse\b', lambda p, f: 'mess'),     # me + sse -> mess
    (r'\bpa\b', r'\bsse\b', lambda p, f: 'pass'),      # pa + sse -> pass
    (r'\bma\b', r'\bsse\b', lambda p, f: 'mass'),     # ma + sse -> mass
    (r'\bgra\b', r'\bsse\b', lambda p, f: 'grass'),   # gra + sse -> grass
    (r'\bcla\b', r'\bsse\b', lambda p, f: 'class'),   # cla + sse -> class
    (r'\blit\b', r'\btter\b', lambda p, f: 'litter'), # lit + tter -> litter
    (r'\bbit\b', r'\btter\b', lambda p, f: 'bitter'), # bit + tter -> bitter
    (r'\bfit\b', r'\btter\b', lambda p, f: 'fitter'), # fit + tter -> fitter
    (r'\bhit\b', r'\btter\b', lambda p, f: 'hitter'), # hit + tter -> hitter
    (r'\bsit\b', r'\btter\b', lambda p, f: 'sitter'), # sit + tter -> sitter
    (r'\bwit\b', r'\btter\b', lambda p, f: 'witter'), # wit + tter -> witter
    (r'\bco\b', r'\bffe\b', lambda p, f: 'coffee'),   # co + ffe -> coffee
    (r'\btra\b', r'\bffc\b', lambda p, f: 'traffic'), # tra + ffc -> traffic
    (r'\brid\b', r'\blle\b', lambda p, f: 'riddle'),  # rid + lle -> riddle
    (r'\bfid\b', r'\blle\b', lambda p, f: 'fiddle'),  # fid + lle -> fiddle
    (r'\bpid\b', r'\blle\b', lambda p, f: 'piddle'),  # pid + lle -> piddle
    (r'\btwi\b', r'\blle\b', lambda p, f: 'twiddle'), # twi + lle -> twiddle
    (r'\bco\b', r'\bllar\b', lambda p, f: 'collar'),  # co + llar -> collar
    (r'\bce\b', r'\bllar\b', lambda p, f: 'cellar'),  # ce + llar -> cellar
    (r'\bpil\b', r'\bllar\b', lambda p, f: 'pillar'), # pil + llar -> pillar
    # Additional patterns from analysis
    (r'\bap\b', r'\bppe\b', lambda p, f: 'appear'),  # ap + ppe -> appear
    (r'\bap\b', r'\bppel\b', lambda p, f: 'appear'), # ap + ppel -> appear
    (r'\bhap\b', r'\bppen\b', lambda p, f: 'happen'), # hap + ppen -> happen
    (r'\bba\b', r'\bppen\b', lambda p, f: 'happen'),  # ba + ppen -> happen
    (r'\bhis\b', r'\bppen\b', lambda p, f: 'happen'), # his + ppen -> happen (partial)
    (r'\bloa\b', r'\bppen\b', lambda p, f: 'happen'), # loa + ppen -> happen (partial)
    (r'\bsee\b', r'\been\b', lambda p, f: 'seen'),   # see + een -> seen
    (r'\bgr\b', r'\been\b', lambda p, f: 'green'),   # gr + een -> green
    (r'\bqu\b', r'\been\b', lambda p, f: 'queen'),   # qu + een -> queen
    (r'\bscr\b', r'\been\b', lambda p, f: 'screen'), # scr + een -> screen
    (r'\bsh\b', r'\been\b', lambda p, f: 'sheen'),   # sh + een -> sheen
    (r'\btr\b', r'\been\b', lambda p, f: 'treen'),   # tr + een -> treen
    (r'\bpre\b', r'\been\b', lambda p, f: 'preen'),   # pre + een -> preen
    (r'\bun\b', r'\been\b', lambda p, f: 'unseen'),   # un + een -> unseen
    (r'\bmat\b', r'\btter\b', lambda p, f: 'matter'), # mat + tter -> matter
    (r'\bbet\b', r'\btter\b', lambda p, f: 'better'), # bet + tter -> better
    (r'\bsum\b', r'\bmmer\b', lambda p, f: 'summer'), # sum + mmer -> summer
    (r'\bham\b', r'\bmmer\b', lambda p, f: 'hammer'), # ham + mmer -> hammer
    (r'\bstam\b', r'\bmmer\b', lambda p, f: 'stammer'), # stam + mmer -> stammer
    (r'\bjam\b', r'\bmmer\b', lambda p, f: 'jammer'), # jam + mmer -> jammer
    (r'\bglim\b', r'\bmmer\b', lambda p, f: 'glimmer'), # glim + mmer -> glimmer
    (r'\baff\b', r'\bffc\b', lambda p, f: 'affect'),  # aff + ffc -> affect
    (r'\baff\b', r'\bffe\b', lambda p, f: 'affect'),  # aff + ffe -> affect
    (r'\beff\b', r'\bffc\b', lambda p, f: 'effect'),  # eff + ffc -> effect
    (r'\beff\b', r'\bffe\b', lambda p, f: 'effect'),  # eff + ffe -> effect
    (r'\bdol\b', r'\bllar\b', lambda p, f: 'dollar'), # dol + llar -> dollar
    (r'\bcol\b', r'\bllar\b', lambda p, f: 'collar'), # col + llar -> collar
    (r'\bcel\b', r'\bllar\b', lambda p, f: 'cellar'), # cel + llar -> cellar
    (r'\bpill\b', r'\bllar\b', lambda p, f: 'pillar'), # pill + llar -> pillar
    (r'\bfee\b', r'\beel\b', lambda p, f: 'feel'),    # fee + eel -> feel
    (r'\bwhe\b', r'\beel\b', lambda p, f: 'wheel'),   # whe + eel -> wheel
    (r'\bhee\b', r'\beel\b', lambda p, f: 'heel'),    # hee + eel -> heel
    (r'\bkee\b', r'\beel\b', lambda p, f: 'keel'),    # kee + eel -> keel
    (r'\bpee\b', r'\beel\b', lambda p, f: 'peel'),    # pee + eel -> peel
    (r'\bree\b', r'\beel\b', lambda p, f: 'reel'),    # ree + eel -> reel
    (r'\bsee\b', r'\beel\b', lambda p, f: 'seel'),     # see + eel -> seel
    (r'\bstee\b', r'\beel\b', lambda p, f: 'steel'),  # stee + eel -> steel
    (r'\bmee\b', r'\beet\b', lambda p, f: 'meet'),     # mee + eet -> meet
    (r'\bfee\b', r'\beet\b', lambda p, f: 'feet'),     # fee + eet -> feet
    (r'\bmess\b', r'\bsse\b', lambda p, f: 'mess'),    # mess + sse -> mess
    (r'\bpass\b', r'\bsse\b', lambda p, f: 'pass'),    # pass + sse -> pass
    (r'\bmass\b', r'\bsse\b', lambda p, f: 'mass'),    # mass + sse -> mass
    (r'\bgrass\b', r'\bsse\b', lambda p, f: 'grass'),  # grass + sse -> grass
    (r'\bclass\b', r'\bsse\b', lambda p, f: 'class'),  # class + sse -> class
    (r'\blitt\b', r'\btter\b', lambda p, f: 'litter'), # litt + tter -> litter
    (r'\bbitt\b', r'\btter\b', lambda p, f: 'bitter'), # bitt + tter -> bitter
    (r'\bfitt\b', r'\btter\b', lambda p, f: 'fitter'), # fitt + tter -> fitter
    (r'\bhitt\b', r'\btter\b', lambda p, f: 'hitter'), # hitt + tter -> hitter
    (r'\bsitt\b', r'\btter\b', lambda p, f: 'sitter'), # sitt + tter -> sitter
    (r'\bwitt\b', r'\btter\b', lambda p, f: 'witter'), # witt + tter -> witter
    (r'\bmidd\b', r'\blle\b', lambda p, f: 'middle'), # midd + lle -> middle
    (r'\bridd\b', r'\blle\b', lambda p, f: 'riddle'), # ridd + lle -> riddle
    (r'\bfidd\b', r'\blle\b', lambda p, f: 'fiddle'), # fidd + lle -> fiddle
    (r'\bpidd\b', r'\blle\b', lambda p, f: 'piddle'), # pidd + lle -> piddle
    (r'\btwid\b', r'\blle\b', lambda p, f: 'twiddle'), # twid + lle -> twiddle
]

def fix_fragments_in_text(text):
    """Fix fragments using transformation rules."""
    fixes_applied = []
    
    for preceding_pattern, fragment_pattern, transform_func in TRANSFORMATIONS:
        # Pattern: preceding + 1-3 spaces + fragment
        pattern = f'({preceding_pattern})(\\s{{1,3}})({fragment_pattern})\\b'
        
        def make_replace_func(trans_func):
            def replace_func(match):
                preceding = match.group(1)
                space = match.group(2)
                frag = match.group(3)
                
                # Apply transformation
                result = trans_func(preceding, frag)
                
                # Preserve capitalization
                if preceding[0].isupper():
                    result = result.capitalize()
                
                fixes_applied.append((preceding + space + frag, result))
                return result
            return replace_func
        
        text = re.sub(pattern, make_replace_func(transform_func), text, flags=re.IGNORECASE)
    
    return text, fixes_applied

def fix_file(filepath):
    """Fix fragments in a single file."""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        fixed_content, fixes = fix_fragments_in_text(content)
        
        if fixes:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(fixed_content)
            return True, fixes
        return False, []
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return False, []

def main():
    """Process all markdown files."""
    base_dir = Path('reference/Books_md_ready_fixed_cleaned_v2')
    
    if not base_dir.exists():
        print(f"Directory not found: {base_dir}")
        return
    
    files_processed = 0
    files_modified = 0
    all_fixes = []
    
    for md_file in sorted(base_dir.glob('*.md')):
        files_processed += 1
        modified, fixes = fix_file(md_file)
        if modified:
            files_modified += 1
            all_fixes.extend([(md_file.name, fix) for fix in fixes])
            print(f"Fixed {len(fixes)} fragments in: {md_file.name}")
    
    print(f"\n{'='*60}")
    print(f"Processed {files_processed} files, modified {files_modified} files")
    print(f"Total fixes applied: {len(all_fixes)}")
    
    if all_fixes:
        print(f"\nSample fixes:")
        for filename, (old, new) in all_fixes[:30]:
            print(f"  {filename}: '{old}' -> '{new}'")

if __name__ == '__main__':
    main()
