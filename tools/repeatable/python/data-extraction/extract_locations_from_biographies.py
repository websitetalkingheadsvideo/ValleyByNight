#!/usr/bin/env python3
"""
Extract specific, PC-visitable locations from character biographies.
Strict filtering rules applied - zero false positives.
"""

import json
import os
import re
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Set, Tuple, Optional
from collections import defaultdict

# Exclusion patterns - locations to NEVER include
NATURAL_TERRAIN = {
    'forest', 'desert', 'valley', 'river bed', 'riverbed', 'plains', 'woods',
    'cliff', 'cliffs', 'mountain', 'mountains', 'dune', 'dunes', 'canyon',
    'canyons', 'ravine', 'hills', 'hill', 'meadow', 'meadows', 'swamp',
    'wetlands', 'tundra', 'glacier', 'volcano', 'beach', 'coast', 'shore'
}

# Political/social entities (NOT locations)
POLITICAL_ENTITIES = {
    'anarch', 'sabbat', 'camarilla', 'faction', 'sect', 'clan',
    'primogen', 'prince', 'sheriff', 'harpy', 'scourge', 'keeper of ellysium',
    'court', 'domain', 'territory'
}

CATEGORY_GENERICS = {
    'hospitals', 'estates', 'nightclubs', 'clinics', 'schools', 'bars',
    'offices', 'labs', 'apartments', 'warehouses', 'factories', 'shops',
    'stores', 'restaurants', 'cafes', 'hotels', 'motels', 'theaters'
}

VAGUE_MACRO = {
    'the city', 'the capital', 'the district', 'the neighborhood',
    'the area', 'the region', 'the territory', 'the zone'
}

REAL_WORLD_LOCATIONS = {
    'barcelona', 'new york', 'london', 'tokyo', 'paris', 'rome', 'cairo',
    'venice', 'san francisco', 'boston', 'phoenix', 'arizona', 'flagstaff',
    'tombstone', 'mexico', 'united states', 'america', 'europe', 'asia'
}

NON_PLACES = {
    'dreams', 'memory', 'memories', 'the void', 'inside his mind',
    'inside her mind', 'the abyss', 'limbo', 'nowhere'
}

# Patterns to identify location mentions - more specific to avoid false positives
LOCATION_PATTERNS = [
    # Named specific locations (Chantry, Elysium, Haven, Agency with proper names) - HIGH PRIORITY
    r'\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s+(?:Chantry|Elysium|Haven|Agency|Agencies|Office|Offices|Suite|Tower|Building|Warehouse|Hospital|Clinic|Lab|Laboratory|Morgue|Jail|Prison|Estate|Mansion|Apartment|Loft|Theater|Theatre|Club|Nightclub|Bar|Restaurant|Cafe|Motel|Hotel|School|University|College|Barn|Garage|Workshop|Studio|Gallery|Museum|Library|Archive|Archives|Temple)\b',
    # "Elysium" as standalone location (mentioned in context)
    r'\b(?:at|in|to|from|within)\s+(Elysium)\b',
    # Specific building/place patterns with definite article
    r'\bthe\s+([a-z]+(?:\s+[a-z]+)*)\s+(?:hospital|clinic|lab|laboratory|morgue|jail|prison|office|offices|warehouse|estate|mansion|apartment|loft|theater|theatre|club|nightclub|bar|restaurant|cafe|motel|hotel|school|university|college|barn|garage|workshop|studio|gallery|museum|library|archive|archives|shelter|bunker|facility|plant|factory|mill|dock|docks|station|terminal|depot|hangar|stadium|arena|hall|church|temple|monastery|convent|abbey|crypt|mausoleum|cemetery|graveyard)\b',
    # Just "the [location_type]" when clearly a specific instance with action verbs
    r'\b(?:in|at|to|from|near|outside|inside|within|beyond|beside|behind|within|sheltered\s+in|sheltered\s+at|sheltered\s+within|stayed\s+in|stayed\s+at|lived\s+in|lived\s+at|worked\s+at|worked\s+in|ran\s+from|operated\s+from|dies\s+at|present\s+at|slipping\s+in)\s+the\s+([a-z]+(?:\s+[a-z]+)*)\s+(?:hospital|clinic|lab|office|warehouse|estate|mansion|apartment|theater|club|bar|school|museum|library|shelter|bunker|facility|station|garage|workshop|barn|restaurant|hotel|motel)\b',
    # "the [specific place name]" patterns (conservative - needs proper noun, 2-3 words max)
    r'\bthe\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\b',
]

def normalize_location_name(name: str) -> str:
    """Normalize location name for comparison."""
    # Remove leading "the", "a", "an"
    name = re.sub(r'^(the|a|an)\s+', '', name, flags=re.IGNORECASE)
    # Trim whitespace
    name = name.strip()
    return name

def is_natural_terrain(location: str) -> bool:
    """Check if location is natural terrain."""
    location_lower = location.lower()
    for terrain in NATURAL_TERRAIN:
        if terrain in location_lower:
            return True
    return False

def is_category_generic(location: str) -> bool:
    """Check if location is a category/generic reference."""
    location_lower = location.lower()
    # If it's just a category word without specificity, exclude
    words = location_lower.split()
    if len(words) == 1 and words[0] in CATEGORY_GENERICS:
        return True
    # Check for plural categories
    for category in CATEGORY_GENERICS:
        if location_lower == category:
            return True
    return False

def is_vague_macro(location: str) -> bool:
    """Check if location is vague macro-location."""
    location_lower = location.lower().strip()
    return location_lower in VAGUE_MACRO

def is_real_world_location(location: str) -> bool:
    """Check if location is real-world Earth location."""
    location_lower = location.lower()
    for rw_loc in REAL_WORLD_LOCATIONS:
        if rw_loc in location_lower:
            return True
    # Check for city/country patterns that look real-world
    if re.match(r'^[A-Z][a-z]+,?\s+[A-Z][a-z]+$', location):
        # Could be "City, State" format - exclude unless part of fictional world
        return True
    return False

def is_non_place(location: str) -> bool:
    """Check if location is a non-place (metaphorical/abstract)."""
    location_lower = location.lower()
    for non_place in NON_PLACES:
        if non_place in location_lower:
            return True
    # Check for metaphorical patterns
    if any(word in location_lower for word in ['dream', 'memory', 'mind', 'void', 'abyss']):
        return True
    return False

def extract_location_candidates(text: str) -> List[Tuple[str, int, str]]:
    """
    Extract potential location mentions from text.
    Returns list of (location, start_pos, context_snippet).
    """
    candidates = []
    
    # Split into sentences for better context
    sentences = re.split(r'[.!?]\s+', text)
    
    for sentence in sentences:
        if len(sentence.strip()) < 10:  # Skip very short fragments
            continue
        
        # Try each pattern on the sentence
        for pattern in LOCATION_PATTERNS:
            for match in re.finditer(pattern, sentence):
                location = match.group(1).strip()
                start_pos = match.start()
                
                # Skip if location is too short or too long (likely not a real location name)
                if len(location) < 3 or len(location) > 60:
                    continue
                
                # Skip if it looks like a sentence fragment (contains verbs like "to remove", "to be")
                if re.search(r'\b(to|of|for|with|by)\s+(?:remove|be|do|have|get|make|take|give|find|see|know|think|say|go|come|become|hire|discover)', location, re.IGNORECASE):
                    continue
                
                # Special handling for "Elysium" - must be in context of being at/in a place
                if location == 'Elysium':
                    # Check if it's actually used as a location (at Elysium, in Elysium, etc.)
                    if not re.search(r'\b(?:at|in|to|from|within|demonstrations\s+at|entertainment\s+at)\s+Elysium\b', context, re.IGNORECASE):
                        continue
                
                # Use the sentence as context
                context = sentence.strip()
                
                candidates.append((location, start_pos, context))
    
    return candidates

def validate_location(location: str, context: str, full_text: str) -> Tuple[bool, str, Optional[str]]:
    """
    Validate if a location candidate meets all requirements.
    Returns (is_valid, reasoning, uncertainty_reason).
    """
    location = normalize_location_name(location)
    location_lower = location.lower()
    
    # Rule 0: Must not be a political/social entity or job title
    # Check if location contains political entity keywords
    if any(entity in location_lower for entity in POLITICAL_ENTITIES):
        # Exception: if it's clearly a location with a modifier like "Anarch territory" -> exclude
        # But "the Camarilla court" -> exclude (court is political, not physical)
        if any(word in location_lower for word in ['free state', 'faction', 'sect', 'court', 'domain']):
            return False, "Political/social entity (not a physical location)", None
        # Check for patterns like "Los Angeles Anarch" (city + political entity) - exclude
        if re.search(r'\b(anarch|sabbat|camarilla|faction|sect)\b', location_lower) and len(location_lower.split()) >= 2:
            # Check if it's a city name followed by political entity
            words = location_lower.split()
            if len(words) >= 2 and words[-1] in ['anarch', 'sabbat', 'camarilla', 'faction', 'sect']:
                return False, "Political/social entity reference (not a physical location)", None
        # Check for job titles like "As Toreador Primogen" or "Tremere Primogen"
        if location_lower.startswith(('as ', 'to be ', 'became ')):
            return False, "Job title or role (not a location)", None
        if any(title in location_lower for title in ['primogen', 'sheriff', 'harpy', 'prince', 'keeper']):
            # Unless it's "the Sheriff's Office" or similar
            if "office" not in location_lower and "station" not in location_lower:
                return False, "Job title or role (not a location)", None
    
    # Rule 0.5: Must not be an event, action, or time period
    event_keywords = ['war', 'age', 'period', 'era', 'revolution', 'conflict', 'battle', 'embrace']
    if any(keyword in location_lower for keyword in event_keywords):
        # Check if it's clearly an event like "Second World War" or "Gilded Age" or "the Embrace"
        if re.match(r'^(first|second|third|fourth|fifth|world|great|gilded|victorian|medieval|renaissance|embrace)', location_lower):
            return False, "Event, action, or time period (not a physical location)", None
    
    # Rule 0.6: Must not be a clan name or character name
    # Common clan names that might be captured
    clan_names = ['assamite', 'assamites', 'brujah', 'tremere', 'ventrue', 'toreador', 'nosferatu',
                  'malkavian', 'gangrel', 'ravnos', 'setite', 'setites', 'giovanni', 'daughters',
                  'followers of set', 'caitiff']
    if location_lower in clan_names or location_lower in ['fairchild', 'daughters']:
        return False, "Clan name or character/family name (not a location)", None
    
    # Rule 0.7: Must not be a single-word artifact or object
    # Check if it's clearly an object like "Ball" (from "Ball of Truth")
    if len(location.split()) == 1 and location_lower in ['ball', 'door', 'truth']:
        return False, "Object or artifact name (not a location)", None
    
    # Rule 0.8: Must not be a real-world country
    real_world_countries = ['egypt', 'mexico', 'italy', 'france', 'spain', 'germany', 'england', 
                           'russia', 'china', 'japan', 'india', 'australia', 'canada']
    if location_lower in real_world_countries:
        return False, "Real-world country (not a specific in-world location)", None
    
    # Rule 0.9: Must not be concepts, networks, or abstract terms
    abstract_terms = ['jyhad', 'kindred', 'blood', 'clan', 'sect', 'path', 'embrace', 
                     'shrecknet', 'anarch', 'siren', 'kings', 'camarilla']
    if location_lower in abstract_terms:
        return False, "Concept, network, abstract term, or game mechanic (not a physical location)", None
    
    # Rule 0.10: Must not be geographic regions (unless specifically a location name)
    geographic_regions = ['southwest', 'west', 'east', 'north', 'south', 'midwest', 'northeast', 'southeast']
    if location_lower in geographic_regions:
        return False, "Geographic region (not a specific visitable location)", None
    
    # Rule 0.11: Must not be discipline powers or paths
    if location_lower in ['path', 'blood', 'mercury', 'conjuring', 'dehydrate']:
        # Check if context mentions "Path of" which indicates a Thaumaturgy path
        if 'path of' in context.lower() or 'the path' in context.lower():
            return False, "Thaumaturgy path or discipline (not a physical location)", None
    
    # Rule 1: Must be specific instance (not category)
    if is_category_generic(location):
        return False, "Category/generic reference (not specific instance)", None
    
    # Rule 2: Must not be natural terrain
    if is_natural_terrain(location):
        return False, "Natural terrain (not a visitable location)", None
    
    # Rule 3: Must not be vague macro-location
    if is_vague_macro(location):
        return False, "Vague macro-location (too broad)", None
    
    # Rule 4: Must not be real-world Earth location
    if is_real_world_location(location):
        # Exception: Phoenix and Arizona might be part of fictional world
        if 'phoenix' in location_lower and ('chantry' in location_lower or 'elysium' in location_lower):
            # "Phoenix Chantry" is valid
            pass
        else:
            return False, "Real-world Earth location", None
    
    # Rule 5: Must not be non-place
    if is_non_place(location):
        return False, "Non-place (metaphorical/abstract)", None
    
    # Additional check: Must contain location indicators
    location_indicators = [
        'hospital', 'clinic', 'lab', 'office', 'warehouse', 'estate', 'mansion',
        'apartment', 'theater', 'club', 'bar', 'school', 'museum', 'library',
        'chantry', 'elysium', 'haven', 'bunker', 'facility', 'station', 'dock',
        'shelter', 'garage', 'workshop', 'barn', 'restaurant', 'cafe', 'hotel',
        'motel', 'jail', 'prison', 'morgue', 'cemetery', 'church', 'temple'
    ]
    
    # If it's a single word without location indicator, likely not a location
    words = location_lower.split()
    if len(words) == 1 and not any(indicator in location_lower for indicator in location_indicators):
        # Check if it's a proper noun (capitalized) - might be a place name
        if not location[0].isupper():
            return False, "Single word without location indicator (likely not a place)", None
    
    # Additional validation: Check for specificity indicators
    specificity_indicators = [
        'the hospital', 'the estate', 'the nightclub', 'the warehouse',
        'the docks', 'the old barn', 'the apartment', 'the office',
        'the motel', 'the sheriff\'s office', 'the jail', 'the morgue',
        'the lab', 'the clinic', 'the research facility', 'the school'
    ]
    
    location_lower = location.lower()
    has_specificity = any(indicator in context.lower() for indicator in specificity_indicators)
    
    # Check for proper nouns (likely specific)
    has_proper_noun = bool(re.match(r'^[A-Z]', location))
    
    # Check for descriptive modifiers (likely specific)
    has_modifiers = any(word in location_lower for word in ['old', 'new', 'abandoned', 'hidden', 'secret', 'private', 'corporate'])
    
    # Uncertainty checks
    uncertainty_reasons = []
    
    # If it's just a single word and no specificity, uncertain
    if len(location.split()) == 1 and not has_proper_noun and not has_specificity:
        uncertainty_reasons.append("Single word without clear specificity")
    
    # If context doesn't clearly indicate a physical place
    if not any(word in context.lower() for word in ['at', 'in', 'to', 'from', 'near', 'inside', 'within', 'visit', 'went', 'arrived', 'left']):
        uncertainty_reasons.append("Context doesn't clearly indicate a visitable place")
    
    # Valid if passes all exclusion rules
    is_valid = True
    reasoning = "Specific, physical, PC-visitable location"
    
    if uncertainty_reasons:
        uncertainty_reason = "; ".join(uncertainty_reasons)
    else:
        uncertainty_reason = None
    
    return is_valid, reasoning, uncertainty_reason

def process_character_file(filepath: Path) -> Dict:
    """Process a single character JSON file."""
    try:
        # Read file with error handling for control characters
        with open(filepath, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
        
        # Fix common JSON issues before parsing
        # Remove trailing commas before closing braces/brackets
        import re
        content = re.sub(r',(\s*[}\]])', r'\1', content)
        
        # Escape newlines in string values (but not in keys)
        # This is tricky - we'll do a simple fix for unescaped newlines in quoted strings
        # Replace newlines that are inside quotes but not escaped
        lines = content.split('\n')
        fixed_lines = []
        in_string = False
        for line in lines:
            # Simple heuristic: if line doesn't end with , or } or ], might be continuation
            # But this is complex, so let's just try to fix obvious cases
            if '",,' in line:
                line = line.replace('",,', '",')
            fixed_lines.append(line)
        content = '\n'.join(fixed_lines)
        
        # Try to parse JSON
        char_data = json.loads(content)
    except json.JSONDecodeError as e:
        # Try manual fixes for known problematic files
        try:
            # For Ardvark.json - remove trailing commas
            if 'Ardvark' in filepath.name:
                content = re.sub(r',+(\s*"[^"]*":)', r'\1', content)  # Remove multiple commas
                content = re.sub(r',(\s*[}\]])', r'\1', content)  # Remove trailing commas
            # For Misfortune.json - escape newlines in biography field
            if 'Misfortune' in filepath.name:
                # Find the biography field and escape newlines
                bio_match = re.search(r'"biography"\s*:\s*"([^"]*(?:\n[^"]*)*)"', content, re.DOTALL)
                if bio_match:
                    bio_content = bio_match.group(1)
                    bio_content_escaped = bio_content.replace('\n', '\\n').replace('\r', '\\r')
                    content = content.replace(bio_match.group(0), f'"biography": "{bio_content_escaped}"')
            
            char_data = json.loads(content)
        except Exception as e2:
            # Last resort: try to load as JSON5 or just skip problematic fields
            return {'error': f'JSON decode error (original: {str(e)}, retry: {str(e2)})', 'character_name': filepath.stem}
    except Exception as e:
        return {'error': str(e), 'character_name': filepath.stem}
    
    character_name = char_data.get('character_name', filepath.stem)
    results = {
        'character_name': character_name,
        'source_file': filepath.name,
        'valid_locations': [],
        'uncertain_locations': [],
        'excluded_candidates': []
    }
    
    # Extract text from biography and timeline fields
    text_fields = {
        'biography': char_data.get('biography', ''),
        'notes': char_data.get('notes', ''),
    }
    
    # Add timeline fields
    timeline = char_data.get('timeline', {})
    if isinstance(timeline, dict):
        for key, value in timeline.items():
            if isinstance(value, str) and value:
                text_fields[f'timeline.{key}'] = value
    
    # Process each text field
    for field_name, text in text_fields.items():
        if not text or not isinstance(text, str):
            continue
        
        # Extract candidates
        candidates = extract_location_candidates(text)
        
        for location, pos, context in candidates:
            # Validate
            is_valid, reasoning, uncertainty = validate_location(location, context, text)
            
            if not is_valid:
                results['excluded_candidates'].append({
                    'candidate': location,
                    'reason': reasoning,
                    'field': field_name,
                    'context': context[:200]  # Truncate for storage
                })
                continue
            
            # Determine if uncertain
            if uncertainty:
                location_entry = {
                    'name': normalize_location_name(location),
                    'field': field_name,
                    'excerpt': context[:200],
                    'context': context,
                    'reasoning': reasoning,
                    'uncertainty': uncertainty
                }
                results['uncertain_locations'].append(location_entry)
            else:
                location_entry = {
                    'name': normalize_location_name(location),
                    'field': field_name,
                    'excerpt': context[:200],
                    'context': context,
                    'reasoning': reasoning
                }
                results['valid_locations'].append(location_entry)
    
    return results

def deduplicate_locations(all_results: List[Dict]) -> Dict:
    """Deduplicate and merge location entries."""
    # Group by normalized name
    location_groups = defaultdict(lambda: {
        'name': None,
        'evidence': [],
        'uncertain_count': 0,
        'valid_count': 0
    })
    
    for result in all_results:
        char_name = result['character_name']
        source_file = result['source_file']
        
        # Process valid locations
        for loc in result.get('valid_locations', []):
            name = loc['name']
            location_groups[name]['name'] = name
            location_groups[name]['valid_count'] += 1
            location_groups[name]['evidence'].append({
                'character': char_name,
                'source_file': source_file,
                'field': loc['field'],
                'excerpt': loc['excerpt'],
                'context': loc['context']
            })
        
        # Process uncertain locations
        for loc in result.get('uncertain_locations', []):
            name = loc['name']
            location_groups[name]['name'] = name
            location_groups[name]['uncertain_count'] += 1
            location_groups[name]['evidence'].append({
                'character': char_name,
                'source_file': source_file,
                'field': loc['field'],
                'excerpt': loc['excerpt'],
                'context': loc['context'],
                'uncertainty': loc.get('uncertainty')
            })
    
    return dict(location_groups)

def main():
    """Main extraction process."""
    script_dir = Path(__file__).parent
    project_root = script_dir.parent
    characters_dir = project_root / 'reference' / 'Characters' / 'Added to Database'
    output_file = project_root / 'reference' / 'Locations' / 'locations_to_add.json'
    
    # Get all character JSON files
    character_files = list(characters_dir.glob('*.json'))
    print(f"Found {len(character_files)} character files")
    
    # Process all files
    all_results = []
    excluded_candidates = []
    
    for char_file in character_files:
        print(f"Processing {char_file.name}...")
        result = process_character_file(char_file)
        if 'error' not in result:
            all_results.append(result)
            excluded_candidates.extend([
                {**exc, 'source_file': char_file.name}
                for exc in result.get('excluded_candidates', [])
            ])
        else:
            print(f"  Error: {result['error']}")
    
    # Deduplicate locations
    location_groups = deduplicate_locations(all_results)
    
    # Build output structure
    locations = []
    uncertain_locations = []
    
    for name, group in location_groups.items():
        # Determine confidence and status
        if group['uncertain_count'] > 0 and group['valid_count'] == 0:
            validation_status = 'uncertain'
            confidence = 'low'
        elif group['uncertain_count'] > 0:
            validation_status = 'uncertain'
            confidence = 'medium'
        else:
            validation_status = 'valid'
            confidence = 'high' if len(group['evidence']) > 1 else 'medium'
        
        # Determine location type from context
        location_type = 'Unknown'
        type_indicators = {
            'Chantry': 'Chantry',
            'Elysium': 'Elysium',
            'Haven': 'Haven',
            'Office': 'Business',
            'Hospital': 'Business',
            'Clinic': 'Business',
            'Lab': 'Business',
            'Morgue': 'Business',
            'Jail': 'Business',
            'Sheriff': 'Business',
            'Estate': 'Haven',
            'Mansion': 'Haven',
            'Apartment': 'Haven',
            'Loft': 'Haven',
            'Nightclub': 'Business',
            'Bar': 'Business',
            'Restaurant': 'Business',
            'Theater': 'Business',
            'School': 'Business',
            'University': 'Business',
            'Warehouse': 'Business'
        }
        
        for indicator, ltype in type_indicators.items():
            if indicator.lower() in name.lower():
                location_type = ltype
                break
        
        # Get reasoning from first evidence
        reasoning = group['evidence'][0].get('context', '')[:200]
        
        location_entry = {
            'name': name,
            'type': location_type,
            'description': '',
            'confidence': confidence,
            'validation_status': validation_status,
            'reasoning': f"Extracted from character biographies. Mentioned {len(group['evidence'])} time(s). {reasoning}",
            'evidence': group['evidence'],
            'source_field': group['evidence'][0]['field'],
            'open_questions': []
        }
        
        if validation_status == 'uncertain':
            uncertain_locations.append(location_entry)
            location_entry['open_questions'].append(
                "Verify this is a specific, PC-visitable location rather than a category or metaphorical reference."
            )
        else:
            locations.append(location_entry)
    
    # Sort by name
    locations.sort(key=lambda x: x['name'])
    uncertain_locations.sort(key=lambda x: x['name'])
    
    # Build final output
    output = {
        'metadata': {
            'title': 'Locations to Add',
            'description': 'Locations extracted from character biographies and histories following strict filtering rules',
            'generated': datetime.now().isoformat(),
            'extraction_method': 'biography_text_mining',
            'total_locations': len(locations) + len(uncertain_locations),
            'valid_locations': len(locations),
            'uncertain_locations': len(uncertain_locations)
        },
        'summary': {
            'total_extracted': len(location_groups),
            'valid_locations': len(locations),
            'uncertain_locations': len(uncertain_locations),
            'excluded_candidates': len(excluded_candidates),
            'characters_scanned': len(all_results)
        },
        'locations': locations + uncertain_locations,
        'excluded_candidates': excluded_candidates[:50],  # Limit to first 50 for size
        'open_questions_for_review': [
            'Review uncertain locations for false positives',
            'Verify location types are accurate',
            'Check for duplicate locations with different names',
            'Validate that all locations are specific instances (not categories)'
        ]
    }
    
    # Write output
    output_file.parent.mkdir(parents=True, exist_ok=True)
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, ensure_ascii=False)
    
    print(f"\nExtraction complete!")
    print(f"Valid locations: {len(locations)}")
    print(f"Uncertain locations: {len(uncertain_locations)}")
    print(f"Excluded candidates: {len(excluded_candidates)}")
    print(f"Output written to: {output_file}")

if __name__ == '__main__':
    main()
