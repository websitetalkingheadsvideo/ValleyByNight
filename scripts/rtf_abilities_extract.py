"""Extract table data from abilities.rtf and output JSON."""
import re
import json

def main():
    path = r'v:\reference\Books\abilities.rtf'
    with open(path, 'rb') as f:
        raw = f.read().decode('utf-8', errors='ignore')

    # Extract text between } and } - plain text runs (no backslash)
    # Allow letters, digits, space, comma, hyphen, period, apostrophe
    parts = re.findall(r'\}([A-Za-z][A-Za-z0-9\s,.\'-]*?)\}', raw)
    clean = [p.strip() for p in parts if len(p.strip()) > 1]

    # Skip RTF/formatting tokens and header/footer
    skip = {
        'ltrch', 'hich', 'dbch', 'loch', 'fcs', 'af', 'expnd', 'expndtw',
        'cf', 'par', 'plain', 'ql', 'sb', 'basedon', 'SHAPE', 'MERGEFORMAT',
        'PAGE', 'NUMPAGES', 'World', 'Darkness', 'Index', 'Database',
        'Saturday', 'September', 'Page', 'of', 'Abilities', 'Numbe'
    }
    tokens = [c for c in clean if c not in skip and not (c.isdigit() and len(c) < 4)]

    # Header from sample: Ability, Type, Ability, Title, Page Number
    # So 5 columns - but "Ability" appears twice (likely Ability | Type | Ability | Title | Page)
    # Assume: Column1=Ability name, Column2=Type, Column3=Ability (duplicate?), Title, Page
    # From first run we had: Ability, Type, Ability, Title, Page Numbe
    # So columns: Ability, Type, Ability, Title, Page Number
    # Likely the table is: Ability (name), Type (Talent/Skill/Knowledge), Title (description?), Page
    # Or: first row is header: Ability | Type | Title | Page (and one duplicate header?)
    # Print to see structure
    for i, t in enumerate(tokens[:150]):
        print(i, repr(t))

if __name__ == '__main__':
    main()
