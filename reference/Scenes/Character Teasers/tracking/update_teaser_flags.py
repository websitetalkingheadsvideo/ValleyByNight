import json
import os
import re

# Load JSON
with open('missing-character-teasers.json', 'r', encoding='utf-8') as f:
    characters = json.load(f)

# Get all teaser files (exclude guide, tracking folder, and other non-teaser files)
teaser_dir = '..'
exclude_files = {'Valley_by_Night_Cinematic_Intro_Guide.md', 'Alessandro_Ledger_of_the_Dead_Revised.md'}
all_files = [f for f in os.listdir(teaser_dir) 
             if f.endswith('.md') and f not in exclude_files 
             and os.path.isfile(os.path.join(teaser_dir, f))]

def normalize_name(name):
    """Normalize character name for matching"""
    name = name.lower().strip()
    # Remove quotes and apostrophes
    name = re.sub(r"[''\"]", '', name)
    # Remove common suffixes like "the Gangrel"
    name = re.sub(r',\s*the\s+\w+$', '', name)
    # Extract meaningful parts
    parts = re.split(r'[\s,\']+', name)
    return ' '.join(p for p in parts if p)

def extract_name_from_filename(filename):
    """Extract character name from filename"""
    # Remove .md extension
    name = filename[:-3]
    # Handle numbered prefixes like 01_EddyValiant_Intro.md
    name = re.sub(r'^\d+_', '', name)
    name = re.sub(r'_Intro$', '', name)
    name = re.sub(r'_script$', '', name)
    name = re.sub(r'_teaser$', '', name)
    name = re.sub(r'_intros$', '', name)
    # Split camelCase or underscores
    name = re.sub(r'([a-z])([A-Z])', r'\1 \2', name)
    name = name.replace('_', ' ')
    return normalize_name(name)

def check_file_content(filepath, character_name):
    """Check if file content mentions the character"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read().lower()
        # Check for full name and variations
        normalized_char = normalize_name(character_name)
        parts = normalized_char.split()
        
        # Check for exact full name matches
        if len(parts) >= 2:
            first_last = f"{parts[0]} {parts[-1]}"
            if first_last in content:
                return True
        
        # Check if significant parts of name appear
        matches = sum(1 for part in parts if len(part) > 2 and part in content)
        return matches >= max(2, len(parts) * 0.7)  # At least 2 parts or 70% match
    except:
        return False

# Map characters with teasers
character_ids_with_teasers = set()

# Build filename to character mapping
for char in characters:
    char_normalized = normalize_name(char['name'])
    char_parts = char_normalized.split()
    char_first = char_parts[0] if char_parts else ''
    char_last = char_parts[-1] if len(char_parts) > 1 else ''
    char_first_last = f"{char_first} {char_last}" if char_last else char_first
    
    for filename in all_files:
        filepath = os.path.join(teaser_dir, filename)
        file_normalized = extract_name_from_filename(filename)
        file_parts = file_normalized.split()
        
        # Direct filename match
        if char_normalized == file_normalized or char_first_last in file_normalized:
            character_ids_with_teasers.add(char['id'])
            continue
            
        # Handle variations: nicknames, titles, etc.
        # For "Jax 'The Ghost Dealer'" - match both "Jax.md" and "Jax 'The Ghost Dealer'.md"
        if char_parts and file_parts:
            # Check if first name matches and it's substantial (>2 chars)
            if char_first == file_parts[0] and len(char_first) > 2:
                # Check file content to confirm
                if check_file_content(filepath, char['name']):
                    character_ids_with_teasers.add(char['id'])
                    continue
        
        # Check file content for character mentions (even if filename doesn't match)
        if check_file_content(filepath, char['name']):
            character_ids_with_teasers.add(char['id'])

# Special cases from multi-character files
multi_char_files = {
    'warner_barry_intros.md': [123, 101],  # Warner Jefferson and Barry Washington
}

for filename, ids in multi_char_files.items():
    if filename in all_files:
        for char_id in ids:
            character_ids_with_teasers.add(char_id)

# Special case: Barry Horowitz (from barry_intro_script.md) is NOT Barry Washington
# Only count barry_intro_script.md if it's actually Barry Washington in the content
barry_washington_id = 101
barry_horowitz_file = os.path.join(teaser_dir, 'barry_intro_script.md')
if os.path.exists(barry_horowitz_file):
    with open(barry_horowitz_file, 'r', encoding='utf-8') as f:
        content = f.read()
        # Only count if it mentions Barry Washington (it doesn't - it's Barry Horowitz)
        if 'barry washington' in content.lower():
            character_ids_with_teasers.add(barry_washington_id)
        # Don't remove if it was already added from another file (like warner_barry_intros.md)

# Update JSON
for char in characters:
    if char['id'] in character_ids_with_teasers:
        char['hasTeaser'] = True
    else:
        if 'hasTeaser' in char:
            char['hasTeaser'] = False
        else:
            char['hasTeaser'] = False

# Save updated JSON
with open('missing-character-teasers.json', 'w', encoding='utf-8') as f:
    json.dump(characters, f, indent=4, ensure_ascii=False)

print(f'Updated {len(character_ids_with_teasers)} characters with hasTeaser flag')
print(f'Character IDs with teasers: {sorted(character_ids_with_teasers)}')
print(f'Characters with teasers:')
for char in characters:
    if char.get('hasTeaser'):
        print(f"  - {char['id']}: {char['name']}")

