#!/usr/bin/env python3
"""
Extract history/biography fields from .gv3 XML files.
Looks for fields like: notes, history, biography, bio, background, etc.
"""

import xml.etree.ElementTree as ET
import json
import os
import re
from pathlib import Path

def clean_xml_content(content):
    """Clean XML content to handle encoding issues."""
    # Replace common problematic characters
    content = content.replace('', '')
    # Remove control characters except newlines and tabs
    content = re.sub(r'[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]', '', content)
    return content

def extract_text_content(element):
    """Extract text content from an element, handling CDATA sections."""
    if element is None:
        return ""
    
    text = element.text or ""
    tail = element.tail or ""
    
    # Handle CDATA sections
    if text.strip():
        return text.strip()
    
    # Get all text from children
    all_text = []
    for child in element:
        child_text = extract_text_content(child)
        if child_text:
            all_text.append(child_text)
    
    return "\n".join(all_text) if all_text else text.strip()

def find_history_fields(character_element):
    """Find all potential history/biography fields in a character element."""
    history_fields = {}
    
    # List of potential field names (case-insensitive matching)
    potential_fields = [
        'notes', 'history', 'biography', 'bio', 'background',
        'historynotes', 'biographynotes', 'characterhistory',
        'characterbiography', 'backstory', 'story'
    ]
    
    # Check direct child elements
    for child in character_element:
        tag_name = child.tag.lower()
        
        # Check if this tag matches any potential field name
        for field_name in potential_fields:
            if field_name in tag_name or tag_name in field_name:
                content = extract_text_content(child)
                if content:
                    # Use the actual tag name from XML (preserve case)
                    history_fields[child.tag] = content
    
    return history_fields

def get_character_name(character_element):
    """Extract character name from element attributes."""
    name = character_element.get('name', '')
    # Clean up XML entity references
    name = name.replace('&quot;', '"').replace('&amp;', '&').replace('&lt;', '<').replace('&gt;', '>')
    return name

def process_gv3_file(file_path):
    """Process a single .gv3 file and extract all history/biography entries."""
    print(f"Processing {file_path}...")
    
    # Try to read and clean the file first
    try:
        with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
        
        # Clean problematic characters
        content = clean_xml_content(content)
        
        # Try parsing
        try:
            root = ET.fromstring(content)
        except ET.ParseError as e:
            print(f"  XML parse error: {e}")
            print(f"  Attempting incremental parsing...")
            # Try incremental parsing by extracting character blocks
            return process_incremental(file_path, content)
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return []
    
    entries = []
    
    # Find all character elements (vampire, wraith, mortal, werewolf, mage, etc.)
    character_types = ['vampire', 'wraith', 'mortal', 'werewolf', 'mage', 
                      'changeling', 'demon', 'fera', 'hunter', 'kuei-jin',
                      'mummy', 'character', 'various']
    
    for char_type in character_types:
        for character in root.findall(f'.//{char_type}'):
            name = get_character_name(character)
            if not name:
                continue
            
            history_fields = find_history_fields(character)
            
            if history_fields:
                entry = {
                    'character_name': name,
                    'character_type': char_type,
                    'source_file': os.path.basename(file_path),
                    'fields': history_fields
                }
                entries.append(entry)
                print(f"  Found history/biography for: {name}")
    
    return entries

def process_incremental(file_path, content):
    """Process file incrementally by finding character blocks with regex."""
    entries = []
    
    # Pattern to find character elements with their content
    # This regex finds opening tags for character types and captures until closing tag
    character_pattern = r'<(vampire|wraith|mortal|werewolf|mage|changeling|demon|fera|hunter|kuei-jin|mummy|character|various)\s+([^>]*?)>(.*?)</\1>'
    
    character_types = ['vampire', 'wraith', 'mortal', 'werewolf', 'mage', 
                      'changeling', 'demon', 'fera', 'hunter', 'kuei-jin',
                      'mummy', 'character', 'various']
    
    for char_type in character_types:
        # More specific pattern for each character type
        pattern = rf'<{char_type}\s+([^>]*?)>(.*?)</{char_type}>'
        
        for match in re.finditer(pattern, content, re.DOTALL):
            attrs_str = match.group(1)
            char_content = match.group(2)
            
            # Extract name from attributes
            name_match = re.search(r'name="([^"]*)"', attrs_str)
            if not name_match:
                continue
            
            name = name_match.group(1)
            name = name.replace('&quot;', '"').replace('&amp;', '&').replace('&lt;', '<').replace('&gt;', '>')
            
            # Look for history/biography fields in the character content
            history_fields = {}
            
            # Pattern to find notes, history, biography fields
            field_patterns = [
                (r'<notes>(.*?)</notes>', 'notes'),
                (r'<history>(.*?)</history>', 'history'),
                (r'<biography>(.*?)</biography>', 'biography'),
                (r'<bio>(.*?)</bio>', 'bio'),
                (r'<background>(.*?)</background>', 'background'),
            ]
            
            for field_pattern, field_name in field_patterns:
                field_match = re.search(field_pattern, char_content, re.DOTALL)
                if field_match:
                    field_content = field_match.group(1).strip()
                    # Remove CDATA markers if present
                    field_content = re.sub(r'<!\[CDATA\[(.*?)\]\]>', r'\1', field_content, flags=re.DOTALL)
                    if field_content:
                        history_fields[field_name] = field_content.strip()
            
            if history_fields:
                entry = {
                    'character_name': name,
                    'character_type': char_type,
                    'source_file': os.path.basename(file_path),
                    'fields': history_fields
                }
                entries.append(entry)
                print(f"  Found history/biography for: {name}")
    
    return entries

def main():
    """Main function to process all .gv3 files and create history.json."""
    grapevine_dir = Path(__file__).parent
    output_file = grapevine_dir / 'history.json'
    
    # Find all .gv3 files
    gv3_files = list(grapevine_dir.glob('*.gv3'))
    
    if not gv3_files:
        print("No .gv3 files found in Grapevine directory")
        return
    
    print(f"Found {len(gv3_files)} .gv3 file(s)")
    
    all_entries = []
    
    for gv3_file in gv3_files:
        entries = process_gv3_file(gv3_file)
        all_entries.extend(entries)
    
    # Create output structure
    output_data = {
        'extraction_date': str(Path(__file__).stat().st_mtime),
        'total_characters': len(all_entries),
        'characters': all_entries
    }
    
    # Write to JSON file
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(output_data, f, indent=2, ensure_ascii=False)
    
    print(f"\nExtraction complete!")
    print(f"Total characters with history/biography: {len(all_entries)}")
    print(f"Output written to: {output_file}")

if __name__ == '__main__':
    main()

