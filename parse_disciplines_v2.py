#!/usr/bin/env python3
"""
Improved parser for the Mind's Eye Theatre Discipline Deck markdown file.
"""

import re
import json
from pathlib import Path
from typing import List, Dict, Tuple, Optional

# Section labels to skip
SKIP_LABELS = {
    'BASIC', 'INTERMEDIATE', 'ADVANCED', 'INTERM', 'ADV', 'BASICTHAUMATURGICAL RITUAL',
    'BASICTHAUMATURGICAL RITUALS', 'INTERM. NEC ROMANTIC RITUALS', 'ADV. NEC ROMANTIC RITUAL',
    'INTERM. TH A UMA TURGI CAL RITUALS', 'ADV. TH A UMA TURGI CAL RITUALS',
    "MIND'S EYE THEATRE DISC IP FINE DECK", "MIND'S EYE THEATRE DISCIPLINE DECK",
    "MIND'S EYE THE ATER DISC IP LINE DECK", "GAME STUDIO", "BASIC NEC ROMANTIC RITUALS",
    "BASIC TH A UMA TURGI CAL RITUALS", "IN TERM TH A UMA TURGI CAL RITUALS",
    # Discipline names (not powers)
    'ANIMALISM', 'AUSPEX', 'CELERITY', 'CHIMERSTRY', 'DEMENTATION', 'DOMINATE',
    'FORTITUDE', 'MELPOMINEE', 'OBFUSCATE', 'OBTENEBRATION', 'POTENCE', 'PRESENCE',
    'PROTEAN', 'QUIETUS', 'SEPULCHRE', 'SERPENTIS', 'THANATOSIS', 'OBEAH',
    'AUSDEX', 'DROTE AN', 'SEDULCHRE DATH NECROMANCY', 'ASH DATH NECROMANCY',
    'BONE DATH NECROMANCY'
}

def normalize_discipline_name(name: str) -> str:
    """Normalize discipline names with OCR errors."""
    mapping = {
        'Chi mers try': 'Chimerstry',
        'Chi\s*m\s*e\s*r\s*s\s*t\s*r\s*y': 'Chimerstry',
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
        'SEDULCHRE DATH NECROMANCY': 'Sepulchre Path Necromancy',
        'ASH DATH NECROMANCY': 'Ash Path Necromancy',
        'BONE DATH NECROMANCY': 'Bone Path Necromancy',
    }
    
    # Direct match
    if name in mapping:
        return mapping[name]
    
    # Partial match
    name_upper = name.upper()
    for key, value in mapping.items():
        if key.upper() in name_upper:
            return value
    
    # Clean up common patterns
    name = re.sub(r'\s+', ' ', name.strip())
    return name

def clean_description(text: str) -> str:
    """Clean up description text by removing embedded headers and section labels."""
    # Remove embedded headers (## ...)
    text = re.sub(r'##\s+[^\n]+', '', text)
    # Remove section labels
    for label in SKIP_LABELS:
        text = re.sub(rf'\b{re.escape(label)}\b', '', text, flags=re.IGNORECASE)
    # Clean up multiple spaces
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def extract_entry_info(header: str, body: str) -> Optional[Tuple[Dict, bool]]:
    """
    Extract information from a discipline/ritual entry.
    Returns (entry_dict, is_ritual) or None if should skip.
    """
    # Skip section labels
    header_upper = header.upper().strip()
    if header_upper in SKIP_LABELS or any(label in header_upper for label in SKIP_LABELS if len(label) > 5):
        return None
    
    is_ritual = 'ritual' in header.lower() or 'thaumaturgical' in header.lower() or 'necromantic' in header.lower() or 'nec romantic' in header.lower()
    
    # Extract page number
    page_match = re.search(r'p\.?\s*(\d+)', header + ' ' + body, re.IGNORECASE)
    page = int(page_match.group(1)) if page_match else None
    
    if is_ritual:
        # Parse ritual header
        # Pattern: Name Level Type Ritual p.page
        # Example: "Call of the Hungry Dead Basic Nec romantic Ritual: p.157"
        
        # Extract name (everything before level words)
        name_match = re.match(r'^(.+?)(?:\s+(?:Basic|Intermediate|Advanced|Basic\s+Th\s*a\s*u\s*m\s*a\s*t\s*u\s*r\s*g\s*i\s*c\s*a\s*l|Nec\s*r\s*o\s*m\s*a\s*n\s*t\s*i\s*c|Nec\s+\s*r\s*o\s+m\s+a\s+n\s+t\s+i\s+c)\s+Ritual)', header, re.IGNORECASE)
        if name_match:
            name = name_match.group(1).strip()
        else:
            # Fallback
            parts = header.split('Basic')[0].split('Intermediate')[0].split('Advanced')[0].split('p.')[0]
            name = parts.strip().rstrip(':').strip()
        
        # Extract level
        level = 'Basic'
        if 'Intermediate' in header or 'INTERM' in header:
            level = 'Intermediate'
        elif 'Advanced' in header or 'ADV' in header:
            level = 'Advanced'
        
        # Extract ritual type
        ritual_type = 'Thaumaturgical'
        if 'necromantic' in header.lower() or 'nec romantic' in header.lower():
            ritual_type = 'Necromantic'
        elif 'thaumaturgical' in header.lower() or 'th a uma turgi cal' in header.lower():
            ritual_type = 'Thaumaturgical'
        
        return {
            'name': name,
            'level': level,
            'type': ritual_type,
            'page': page,
            'description': clean_description(body)
        }, True
    
    else:
        # Parse discipline header
        # Pattern: Name Number Level Discipline p.page
        # Example: "Feral Whispers 1st Basic Animalism p.134"
        
        # Try standard pattern first
        pattern = r'^(.+?)\s+(\d+)(?:st|nd|rd|th)?\s+(Basic|Intermediate|Advanced)\s+(.+?)(?:\s*[:\s]+p?\.?\s*(\d+))?$'
        match = re.match(pattern, header, re.IGNORECASE)
        
        if match:
            name = match.group(1).strip()
            level_num = int(match.group(2))
            level = match.group(3).strip()
            discipline_raw = match.group(4).strip().rstrip(':')
            discipline = normalize_discipline_name(discipline_raw)
            
            return {
                'name': name,
                'level': level,
                'level_number': level_num,
                'discipline': discipline,
                'page': page if page else (int(match.group(5)) if match.group(5) else None),
                'description': clean_description(body)
            }, False
        
        # Fallback parsing
        # Extract name (before first number or level word)
        name_match = re.match(r'^(.+?)(?:\s+\d+(?:st|nd|rd|th)?\s+(?:Basic|Intermediate|Advanced))', header, re.IGNORECASE)
        if name_match:
            name = name_match.group(1).strip()
        else:
            # Try to extract name more carefully
            name = header.split('p.')[0].strip()
            for level_word in ['Basic', 'Intermediate', 'Advanced', '1st', '2nd']:
                if level_word in name:
                    name = name.split(level_word)[0].strip()
                    break
            name = name.rstrip(':').strip()
        
        # Extract level
        level = 'Basic'
        if 'Intermediate' in header or 'INTERM' in header:
            level = 'Intermediate'
        elif 'Advanced' in header or 'ADV' in header:
            level = 'Advanced'
        
        # Extract level number
        level_num_match = re.search(r'(\d+)(?:st|nd|rd|th)?', header)
        level_num = int(level_num_match.group(1)) if level_num_match else None
        
        # Extract discipline name from remaining header
        remaining = header[len(name):].strip() if name else header
        discipline_patterns = [
            r'(Animalism|Auspex|Celerity|Chimerstry|Dementation|Dominate|Fortitude|Melpominee|Obfuscate|Obtenebration|Potence|Presence|Protean|Quietus|Sepulchre|Serpentis|Thanatosis|Path of Blood|Lure of Flames|Movement of the Mind|Path of Conjuring|Hands of Destruction|Obeah|Sepulchre Path Necromancy|Ash Path Necromancy|Bone Path Necromancy)',
        ]
        
        discipline = 'Unknown'
        for pattern in discipline_patterns:
            disc_match = re.search(pattern, remaining, re.IGNORECASE)
            if disc_match:
                discipline = normalize_discipline_name(disc_match.group(1))
                break
        
        # If still unknown, try to infer from context
        if discipline == 'Unknown' and body:
            for pattern in discipline_patterns:
                disc_match = re.search(pattern, body[:500], re.IGNORECASE)
                if disc_match:
                    discipline = normalize_discipline_name(disc_match.group(1))
                    break
        
        return {
            'name': name,
            'level': level,
            'level_number': level_num,
            'discipline': discipline,
            'page': page,
            'description': clean_description(body)
        }, False

def parse_markdown_file(file_path: Path) -> Tuple[List[Dict], List[Dict]]:
    """Parse the markdown file and extract disciplines and rituals."""
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    lines = content.split('\n')
    disciplines = []
    rituals = []
    
    current_entry = None
    current_body = []
    
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        
        # Check for entry headers
        if line.startswith('## '):
            # Save previous entry
            if current_entry:
                result = extract_entry_info(current_entry, ' '.join(current_body))
                if result:
                    entry_dict, is_ritual = result
                    if entry_dict.get('name') and entry_dict['name'] not in SKIP_LABELS:
                        # Additional validation: skip if name looks like a section label
                        name_upper = entry_dict['name'].upper()
                        if name_upper not in SKIP_LABELS and len(entry_dict['name']) > 2:
                            if is_ritual:
                                rituals.append(entry_dict)
                            else:
                                disciplines.append(entry_dict)
            
            # Start new entry
            header = line[3:].strip()
            current_entry = header
            current_body = []
        
        elif current_entry and line:
            current_body.append(line)
        
        i += 1
    
    # Save last entry
    if current_entry:
        result = extract_entry_info(current_entry, ' '.join(current_body))
        if result:
            entry_dict, is_ritual = result
            if entry_dict.get('name') and entry_dict['name'] not in SKIP_LABELS:
                name_upper = entry_dict['name'].upper()
                if name_upper not in SKIP_LABELS and len(entry_dict['name']) > 2:
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
        # Create key from specified fields
        key_parts = []
        for field in key_fields:
            value = entry.get(field, '')
            if value:
                key_parts.append(str(value).upper().strip())
        key = tuple(key_parts)
        
        if key and key not in seen:
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
        print(f"\nSample disciplines:")
        for d in disciplines[:3]:
            print(f"  - {d['name']} ({d['discipline']}, {d['level']})")
    if rituals:
        print(f"\nSample rituals:")
        for r in rituals[:3]:
            print(f"  - {r['name']} ({r.get('type', 'Unknown')}, {r['level']})")

