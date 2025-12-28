#!/usr/bin/env python3
"""
Parse the Mind's Eye Theatre Discipline Deck markdown file
and convert it into JSON files for disciplines and rituals.
"""

import re
import json
from pathlib import Path
from typing import List, Dict, Tuple

def normalize_discipline_name(name: str) -> str:
    """Normalize discipline names with OCR errors."""
    mapping = {
        'Chi mers try': 'Chimerstry',
        'De ment ation': 'Dementation',
        'do min ate': 'Dominate',
        'for titude': 'Fortitude',
        'Me lpo mine e': 'Melpominee',
        'Ob ten eb ration': 'Obtenebration',
        'do TENCE': 'Potence',
        'DRESENCE': 'Presence',
        'DROTE AN': 'Protean',
        'SEDULCHRE': 'Sepulchre',
        'SERDENTIS': 'Serpentis',
        'th an atosis': 'Thanatosis',
        'DATH OF BLOOD': 'Path of Blood',
        'MOVEMENT OF THE MIND': 'Movement of the Mind',
        'DATH OF CONJURING': 'Path of Conjuring',
        'HANDS OF DESTRUCTI ON': 'Hands of Destruction',
        'OBEAH': 'Obeah',
        'BASIC AUSpEX': 'Auspex',
        'BASIC CELERITY': 'Celerity',
        'BASIC CHI MERS TRY': 'Chimerstry',
        'BASIC DE MENT ATION': 'Dementation',
        'BASIC do Min aTE': 'Dominate',
        'BASIC for TITUDE': 'Fortitude',
        'BASIC OBFUSCATE': 'Obfuscate',
        'BASIC OB TEN E BRAT I ON': 'Obtenebration',
        'BASIC do TENCE': 'Potence',
        'BASIC DRESENCE': 'Presence',
        'BASIC DROTE AN': 'Protean',
        'BASIC QUIETUS': 'Quietus',
        'BASIC SEDULCHRE DATH NECROMANCY': 'Sepulchre Path Necromancy',
        'BASIC ASH DATH NECROMANCY': 'Ash Path Necromancy',
        'BASIC BONE DATH NECROMANCY': 'Bone Path Necromancy',
        'BASIC OBEAH': 'Obeah',
        'BASIC LURE OF FLAMES': 'Lure of Flames',
        'BASIC MOVEMENT OF THE MIND': 'Movement of the Mind',
        'BASIC DATH OF CONJURING': 'Path of Conjuring',
        'BASIC HANDS OF DESTRUCTI ON': 'Hands of Destruction',
    }
    # Try direct match first
    if name in mapping:
        return mapping[name]
    # Try case-insensitive partial match
    for key, value in mapping.items():
        if key.lower() in name.lower():
            return value
    # Clean up common OCR errors
    name = re.sub(r'\s+', ' ', name)  # Multiple spaces to single
    return name.strip()

def extract_entry_info(header: str, body: str) -> Tuple[Dict, bool]:
    """
    Extract information from a discipline/ritual entry.
    Returns (entry_dict, is_ritual).
    """
    is_ritual = 'ritual' in header.lower() or 'thaumaturgical' in header.lower() or 'necromantic' in header.lower() or 'nec romantic' in header.lower()
    
    # Extract page number
    page_match = re.search(r'p\.?\s*(\d+)', header + ' ' + body, re.IGNORECASE)
    page = int(page_match.group(1)) if page_match else None
    
    # Pattern for discipline headers: Name Number Level Discipline p.page
    # Example: "Feral Whispers 1st Basic Animalism p.134"
    discipline_pattern = r'^(.+?)\s+(\d+)(?:st|nd|rd|th)?\s+(Basic|Intermediate|Advanced)\s+(.+?)(?:\s+p?\.?\s*\d+)?$'
    match = re.match(discipline_pattern, header, re.IGNORECASE)
    
    if match and not is_ritual:
        name = match.group(1).strip()
        level_num = int(match.group(2))
        level = match.group(3).strip()
        discipline = normalize_discipline_name(match.group(4).strip())
        
        return {
            'name': name,
            'level': level,
            'level_number': level_num,
            'discipline': discipline,
            'page': page,
            'description': body.strip()
        }, False
    
    # Pattern for ritual headers
    ritual_patterns = [
        r'^(.+?)\s+(Basic|Intermediate|Advanced)\s+(?:Th\s*a\s*u\s*m\s*a\s*t\s*u\s*r\s*g\s*i\s*c\s*a\s*l|Nec\s*r\s*o\s*m\s*a\s*n\s*t\s*i\s*c|Nec\s+\s*r\s*o\s+m\s+a\s+n\s+t\s+i\s+c)\s+Ritual(?:\s+p?\.?\s*(\d+))?$',
        r'^(.+?)\s+(Basic|Intermediate|Advanced)\s+Necromantic\s+Ritual(?:\s+p?\.?\s*(\d+))?$',
        r'^(.+?)\s+(?:Basic|Intermediate|Advanced)\s+(?:Thaumaturgical|Necromantic)\s+Ritual(?:\s+p?\.?\s*(\d+))?$',
    ]
    
    for pattern in ritual_patterns:
        match = re.match(pattern, header, re.IGNORECASE)
        if match:
            name = match.group(1).strip()
            level = match.group(2).strip()
            # Try to extract ritual type
            ritual_type = 'Thaumaturgical' if 'thaumaturgical' in header.lower() or 'th a uma turgi cal' in header.lower() else 'Necromantic'
            
            return {
                'name': name,
                'level': level,
                'type': ritual_type,
                'page': page,
                'description': body.strip()
            }, True
    
    # Fallback: try to parse more flexibly
    # Extract name (everything before number or level word)
    name_match = re.match(r'^(.+?)(?:\s+\d+(?:st|nd|rd|th)?\s+(Basic|Intermediate|Advanced))', header, re.IGNORECASE)
    if name_match:
        name = name_match.group(1).strip()
    else:
        # Just take first part
        name = header.split('p.')[0].split('Basic')[0].split('Intermediate')[0].split('Advanced')[0].strip()
        # Remove trailing punctuation
        name = re.sub(r'[:\.]\s*$', '', name).strip()
    
    # Extract level
    level = 'Basic'
    if 'Intermediate' in header or 'INTERM' in header:
        level = 'Intermediate'
    elif 'Advanced' in header or 'ADV' in header:
        level = 'Advanced'
    
    # Extract level number
    level_num_match = re.search(r'(\d+)(?:st|nd|rd|th)?', header)
    level_num = int(level_num_match.group(1)) if level_num_match else None
    
    if is_ritual:
        ritual_type = 'Thaumaturgical' if 'thaumaturgical' in header.lower() or 'th a uma turgi cal' in header.lower() else 'Necromantic'
        return {
            'name': name,
            'level': level,
            'level_number': level_num,
            'type': ritual_type,
            'page': page,
            'description': body.strip()
        }, True
    else:
        # Try to extract discipline name from remaining header
        remaining = header[len(name):].strip()
        discipline_match = re.search(r'(Animalism|Auspex|Celerity|Chimerstry|Chi\s*m\s*e\s*r\s*s\s*t\s*r\s*y|Dementation|De\s*m\s*e\s*n\s*t\s*a\s*t\s*i\s*o\s*n|Dominate|do\s*m\s*i\s*n\s*a\s*t\s*e|Fortitude|for\s*t\s*i\s*t\s*u\s*d\s*e|Melpominee|Me\s*l\s*p\s*o\s*m\s*i\s*n\s*e\s*e|Obfuscate|Obtenebration|Ob\s*t\s*e\s*n\s*e\s*b\s*r\s*a\s*t\s*i\s*o\s*n|Potence|do\s*T\s*E\s*N\s*C\s*E|Presence|DRESENCE|Protean|DROTE\s*AN|Quietus|QUIETUS|Sepulchre|SEDULCHRE|Serpentis|SERDENTIS|Thanatosis|th\s*a\s*n\s*a\s*t\s*o\s*s\s*i\s*s|Path\s+of\s+Blood|DATH\s+OF\s+BLOOD|Lure\s+of\s+Flames|Movement\s+of\s+the\s+Mind|MOVEMENT\s+OF\s+THE\s+MIND|Path\s+of\s+Conjuring|DATH\s+OF\s+CONJURING|Hands\s+of\s+Destruction|HANDS\s+OF\s+DESTRUCTI\s+ON|Obeah|OBEAH|Ash\s+Path|Bone\s+Path)', remaining, re.IGNORECASE)
        discipline = normalize_discipline_name(discipline_match.group(1)) if discipline_match else 'Unknown'
        
        return {
            'name': name,
            'level': level,
            'level_number': level_num,
            'discipline': discipline,
            'page': page,
            'description': body.strip()
        }, False

def parse_markdown_file(file_path: Path) -> Tuple[List[Dict], List[Dict]]:
    """Parse the markdown file and extract disciplines and rituals."""
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Split into lines
    lines = content.split('\n')
    
    disciplines = []
    rituals = []
    current_entry = None
    current_body = []
    
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        
        # Check for entry headers (## followed by content)
        if line.startswith('## '):
            # Save previous entry if exists
            if current_entry:
                entry_dict, is_ritual = extract_entry_info(current_entry, ' '.join(current_body))
                if entry_dict['name'] and entry_dict['name'] not in ['BASIC', 'INTERMEDIATE', 'ADVANCED', 'INTERM', 'ADV']:
                    if is_ritual:
                        rituals.append(entry_dict)
                    else:
                        disciplines.append(entry_dict)
            
            # Start new entry
            header = line[3:].strip()  # Remove "## "
            
            # Skip section labels
            if header.upper() in ['BASIC', 'INTERMEDIATE', 'ADVANCED', 'INTERM', 'ADV', "MIND'S EYE THEATRE DISC IP FINE DECK", "MIND'S EYE THEATRE DISCIPLINE DECK", "MIND'S EYE THE ATER DISC IP LINE DECK", "MIND'S EYE THE ATER DISC IP LINE DECK", "GAME STUDIO", "MIND'S EYE THE ATER DISC IP FINE DECK"]:
                current_entry = None
                current_body = []
                i += 1
                continue
            
            # Skip if it's just a discipline name header
            if header.upper() in ['ANIMALISM', 'AUSPEX', 'CELERITY', 'CHIMERSTRY', 'DEMENTATION', 'DOMINATE', 'FORTITUDE', 'MELPOMINEE', 'OBFUSCATE', 'OBTENEBRATION', 'POTENCE', 'PRESENCE', 'PROTEAN', 'QUIETUS', 'SEPULCHRE', 'SERPENTIS', 'THANATOSIS']:
                current_entry = None
                current_body = []
                i += 1
                continue
            
            current_entry = header
            current_body = []
        
        elif current_entry and line:
            # Add to current body
            current_body.append(line)
        
        i += 1
    
    # Don't forget the last entry
    if current_entry:
        entry_dict, is_ritual = extract_entry_info(current_entry, ' '.join(current_body))
        if entry_dict['name'] and entry_dict['name'] not in ['BASIC', 'INTERMEDIATE', 'ADVANCED']:
            if is_ritual:
                rituals.append(entry_dict)
            else:
                disciplines.append(entry_dict)
    
    return disciplines, rituals

def deduplicate_entries(entries: List[Dict], key_fields: List[str]) -> List[Dict]:
    """Remove duplicate entries based on key fields."""
    seen = set()
    unique = []
    
    for entry in entries:
        key = tuple(entry.get(field, '') for field in key_fields)
        if key not in seen and entry.get('name'):
            seen.add(key)
            unique.append(entry)
    
    return unique

if __name__ == '__main__':
    input_file = Path(r"reference/Books_md_ready_fixed/Decks/Mind's Eye Theatre Discipline Deck (1).md")
    
    print(f"Parsing {input_file}...")
    disciplines, rituals = parse_markdown_file(input_file)
    
    print(f"\nFound {len(disciplines)} discipline entries")
    print(f"Found {len(rituals)} ritual entries")
    
    # Deduplicate
    disciplines = deduplicate_entries(disciplines, ['name', 'discipline', 'level'])
    rituals = deduplicate_entries(rituals, ['name', 'type', 'level'])
    
    print(f"\nAfter deduplication:")
    print(f"  {len(disciplines)} disciplines")
    print(f"  {len(rituals)} rituals")
    
    # Write JSON files
    output_dir = Path('reference/Books_md_ready_fixed/Decks')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    disciplines_file = output_dir / 'disciplines.json'
    rituals_file = output_dir / 'rituals.json'
    
    with open(disciplines_file, 'w', encoding='utf-8') as f:
        json.dump(disciplines, f, indent=2, ensure_ascii=False)
    
    with open(rituals_file, 'w', encoding='utf-8') as f:
        json.dump(rituals, f, indent=2, ensure_ascii=False)
    
    print(f"\nOutput written to:")
    print(f"  - {disciplines_file}")
    print(f"  - {rituals_file}")
    
    # Print some examples
    if disciplines:
        print(f"\nSample discipline: {disciplines[0]['name']} ({disciplines[0]['discipline']})")
    if rituals:
        print(f"Sample ritual: {rituals[0]['name']} ({rituals[0].get('type', 'Unknown')})")
