#!/usr/bin/env python3
"""Clean up the generated JSON files by fixing common issues."""

import json
import re
from pathlib import Path

def clean_name(name: str) -> str:
    """Clean up entry names."""
    # Remove trailing level words
    name = re.sub(r'\s+(Basic|Intermediate|Advanced|1st|2nd|is t|lst)\s*$', '', name, flags=re.IGNORECASE)
    # Remove trailing punctuation
    name = name.rstrip(':.,;').strip()
    return name

def clean_disciplines(disciplines):
    """Clean up discipline entries."""
    cleaned = []
    seen = set()
    
    for entry in disciplines:
        name = clean_name(entry['name'])
        
        # Skip if name looks like a section label
        name_upper = name.upper()
        if name_upper in ['BASIC', 'INTERMEDIATE', 'ADVANCED', 'INTERM', 'ADV'] or len(name) < 3:
            continue
        
        # Skip duplicates
        key = (name.upper(), entry.get('discipline', '').upper(), entry.get('level', '').upper())
        if key in seen:
            continue
        seen.add(key)
        
        # Fix level_number if it looks wrong (page number instead)
        if entry.get('level_number') and entry.get('page'):
            if entry['level_number'] == entry['page']:
                # Likely a mistake, try to extract from name
                level_match = re.search(r'(\d+)(?:st|nd|rd|th)', entry['name'], re.IGNORECASE)
                if level_match:
                    entry['level_number'] = int(level_match.group(1))
                else:
                    entry['level_number'] = None
        
        entry['name'] = name
        cleaned.append(entry)
    
    return cleaned

def clean_rituals(rituals):
    """Clean up ritual entries."""
    cleaned = []
    seen = set()
    
    for entry in rituals:
        name = clean_name(entry['name'])
        
        # Skip if name looks like a section label
        name_upper = name.upper()
        if name_upper in ['BASIC', 'INTERMEDIATE', 'ADVANCED'] or len(name) < 3:
            continue
        
        # Skip duplicates
        key = (name.upper(), entry.get('type', '').upper(), entry.get('level', '').upper())
        if key in seen:
            continue
        seen.add(key)
        
        # Remove level_number from rituals (not applicable)
        if 'level_number' in entry:
            del entry['level_number']
        
        entry['name'] = name
        cleaned.append(entry)
    
    return cleaned

if __name__ == '__main__':
    base_dir = Path('reference/Books_md_ready_fixed/Decks')
    
    # Read disciplines
    disciplines_file = base_dir / 'disciplines.json'
    with open(disciplines_file, 'r', encoding='utf-8') as f:
        disciplines = json.load(f)
    
    # Read rituals
    rituals_file = base_dir / 'rituals.json'
    with open(rituals_file, 'r', encoding='utf-8') as f:
        rituals = json.load(f)
    
    print(f"Before cleanup: {len(disciplines)} disciplines, {len(rituals)} rituals")
    
    # Clean
    disciplines = clean_disciplines(disciplines)
    rituals = clean_rituals(rituals)
    
    print(f"After cleanup: {len(disciplines)} disciplines, {len(rituals)} rituals")
    
    # Write back
    with open(disciplines_file, 'w', encoding='utf-8') as f:
        json.dump(disciplines, f, indent=2, ensure_ascii=False)
    
    with open(rituals_file, 'w', encoding='utf-8') as f:
        json.dump(rituals, f, indent=2, ensure_ascii=False)
    
    print(f"\nCleaned files written back to:")
    print(f"  - {disciplines_file}")
    print(f"  - {rituals_file}")

