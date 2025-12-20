#!/usr/bin/env python3
"""
Extract Sabbat characters from All NPCs2025.gv3 and transform to character.json format.

This script:
1. Parses the Grapevine XML file
2. Extracts all vampire elements where sect="Sabbat"
3. Transforms each to match the character.json schema
4. Exports to Grapevine/Sabbat.json
5. Generates a Markdown report with Name and Clan
"""

import xml.etree.ElementTree as ET
import json
import re
from typing import Dict, List, Any, Optional, Set
from pathlib import Path

# Valid clan names from documentation
VALID_CLANS = {
    "Assamite", "Assamites",  # Note: XML uses both forms
    "Brujah",
    "Followers of Set",
    "Gangrel",
    "Giovanni",
    "Lasombra",
    "Malkavian",
    "Nosferatu",
    "Ravnos",
    "Toreador",
    "Tremere",
    "Tzimisce",
    "Ventrue",
    "Caitiff"
}

def normalize_clan_name(clan: str) -> str:
    """Normalize clan names to match schema standards."""
    clan = clan.strip()
    
    # Handle Antitribu variants
    if "Antitribu" in clan:
        # Keep Antitribu as part of clan name
        base_clan = clan.replace(" Antitribu", "").replace("Antitribu: ", "").replace(": Antitribu", "")
        if base_clan == "Gangrel":
            return "Gangrel Antitribu"
        elif base_clan == "Brujah":
            return "Brujah Antitribu"
        elif base_clan == "Toreador":
            return "Toreador Antitribu"
        elif base_clan == "Lasombra":
            return "Lasombra Antitribu"
        elif base_clan == "Ventrue":
            return "Ventrue Antitribu"
        elif base_clan.startswith("Serpents of the Light"):
            return "Antitribu: Serpents of the Light"
        else:
            return clan
    
    # Handle special cases
    if clan == "Assamites":
        return "Assamite"
    if clan == "Blood Brothers":
        return "Blood Brothers"  # Sabbat bloodline
    
    return clan

def parse_int_safe(value: Optional[str], default: int = 0) -> int:
    """Safely parse integer, return default if invalid."""
    if value is None or value == "":
        return default
    try:
        return int(value)
    except (ValueError, TypeError):
        return default

def extract_traits(traitlist_element: Optional[ET.Element]) -> List[str]:
    """Extract trait names from a traitlist element."""
    if traitlist_element is None:
        return []
    
    traits = []
    for trait in traitlist_element.findall("trait"):
        trait_name = trait.get("name", "").strip()
        if trait_name:
            # Remove value notations and parentheses content for clean names
            # Keep the base trait name
            traits.append(trait_name)
    return traits

def extract_abilities(traitlist_element: Optional[ET.Element]) -> List[Dict[str, Any]]:
    """Extract abilities from Abilities traitlist."""
    if traitlist_element is None:
        return []
    
    abilities = []
    for trait in traitlist_element.findall("trait"):
        name = trait.get("name", "").strip()
        if not name:
            continue
        
        # Parse ability format: "Ability Name" or "Ability: Specialization" or "Ability Name (Specialization)"
        ability_name = name
        specialization = None
        note = trait.get("note", "").strip()
        
        # Try to extract specialization from various formats
        if ":" in name:
            parts = name.split(":", 1)
            ability_name = parts[0].strip()
            specialization = parts[1].strip()
        elif "(" in name and ")" in name:
            match = re.match(r"(.+?)\s*\((.+?)\)", name)
            if match:
                ability_name = match.group(1).strip()
                specialization = match.group(2).strip()
        
        # Get level from val attribute, default to 0 if not present
        level = parse_int_safe(trait.get("val"), 1)
        if level == 0:
            level = 1  # Minimum level
        
        # Determine category (simplified - would need full mapping for accuracy)
        category = "Optional"  # Default
        physical_abilities = ["Athletics", "Brawl", "Dodge", "Melee", "Stealth", "Survival", "Animal Ken", "Crafts", "Firearms", "Drive"]
        social_abilities = ["Etiquette", "Expression", "Intimidation", "Leadership", "Performance", "Subterfuge", "Streetwise", "Empathy"]
        mental_abilities = ["Academics", "Computer", "Finance", "Investigation", "Law", "Linguistics", "Medicine", "Occult", "Politics", "Science", "Awareness"]
        
        ability_lower = ability_name.lower()
        if any(a.lower() in ability_lower for a in physical_abilities):
            category = "Physical"
        elif any(a.lower() in ability_lower for a in social_abilities):
            category = "Social"
        elif any(a.lower() in ability_lower for a in mental_abilities):
            category = "Mental"
        
        ability_obj = {
            "name": ability_name,
            "category": category,
            "level": level
        }
        
        if specialization:
            ability_obj["specialization"] = specialization
        elif note:
            ability_obj["specialization"] = note
        
        abilities.append(ability_obj)
    
    return abilities

def extract_disciplines(traitlist_element: Optional[ET.Element]) -> List[Dict[str, Any]]:
    """Extract disciplines from Disciplines traitlist."""
    if traitlist_element is None:
        return []
    
    disciplines_dict: Dict[str, Dict[str, Any]] = {}
    
    for trait in traitlist_element.findall("trait"):
        name = trait.get("name", "").strip()
        if not name:
            continue
        
        # Parse discipline format: "Discipline: Level X: Power Name" or "Discipline: Power Name"
        # Also handle: "Discipline: Power" with val indicating level
        parts = name.split(":", 2)
        
        discipline_name = parts[0].strip() if len(parts) > 0 else ""
        power_name = ""
        level = 1
        
        if len(parts) >= 2:
            level_part = parts[1].strip()
            # Try to extract level from "Level I", "Level 1", "Level II", etc.
            level_match = re.search(r"Level\s+(I{1,3}|IV|V|\d+)", level_part, re.IGNORECASE)
            if level_match:
                level_str = level_match.group(1)
                if level_str == "I":
                    level = 1
                elif level_str == "II":
                    level = 2
                elif level_str == "III":
                    level = 3
                elif level_str == "IV":
                    level = 4
                elif level_str == "V":
                    level = 5
                else:
                    level = parse_int_safe(level_str, 1)
                power_name = parts[2].strip() if len(parts) > 2 else level_part.replace(level_match.group(0), "").strip()
            else:
                power_name = level_part
                if len(parts) > 2:
                    power_name = parts[2].strip()
        elif len(parts) == 2:
            power_name = parts[1].strip()
        
        # Check if val attribute indicates level (for elder powers, etc.)
        val = trait.get("val")
        if val and parse_int_safe(val, 0) > 10:
            # This might be an elder power, skip for now or handle specially
            continue
        
        # Initialize discipline if not seen
        if discipline_name not in disciplines_dict:
            disciplines_dict[discipline_name] = {
                "name": discipline_name,
                "level": level,
                "powers": []
            }
        
        # Update discipline level to highest seen
        if level > disciplines_dict[discipline_name]["level"]:
            disciplines_dict[discipline_name]["level"] = level
        
        # Add power if we have one
        if power_name:
            power_obj = {
                "level": level,
                "power": power_name
            }
            disciplines_dict[discipline_name]["powers"].append(power_obj)
    
    # Convert to list and sort powers by level
    disciplines = []
    for disc in disciplines_dict.values():
        disc["powers"].sort(key=lambda x: x["level"])
        disciplines.append(disc)
    
    return disciplines

def extract_backgrounds(traitlist_element: Optional[ET.Element]) -> Dict[str, int]:
    """Extract backgrounds from Backgrounds traitlist."""
    if traitlist_element is None:
        return {}
    
    backgrounds = {}
    for trait in traitlist_element.findall("trait"):
        name = trait.get("name", "").strip()
        val = parse_int_safe(trait.get("val"), 0)
        if name and val > 0:
            backgrounds[name] = val
    
    return backgrounds

def extract_merits_flaws(merits_element: Optional[ET.Element], flaws_element: Optional[ET.Element]) -> List[Dict[str, Any]]:
    """Extract merits and flaws from traitlist elements."""
    merits_flaws = []
    
    for element, mf_type in [(merits_element, "merit"), (flaws_element, "flaw")]:
        if element is None:
            continue
        
        for trait in element.findall("trait"):
            name = trait.get("name", "").strip()
            if not name:
                continue
            
            # Parse cost from val attribute or note
            val = trait.get("val", "")
            note = trait.get("note", "")
            
            # Try to extract cost
            cost = 0
            if val:
                # Might be "2 or 5" format
                if " or " in val:
                    cost = parse_int_safe(val.split(" or ")[0], 1)
                else:
                    cost = parse_int_safe(val, 1)
            
            # Determine category (simplified)
            category = "Mental"  # Default
            name_lower = name.lower()
            if any(word in name_lower for word in ["strength", "dexterity", "stamina", "athletic", "brawny", "tough", "physical"]):
                category = "Physical"
            elif any(word in name_lower for word in ["charisma", "manipulation", "appearance", "social", "allure", "dignified"]):
                category = "Social"
            
            merit_flaw = {
                "name": name,
                "type": mf_type,
                "category": category,
                "cost": cost if mf_type == "merit" else -abs(cost),
                "description": note if note else ""
            }
            merits_flaws.append(merit_flaw)
    
    return merits_flaws

def extract_experience(exp_element: Optional[ET.Element]) -> Dict[str, int]:
    """Extract experience totals from experience element."""
    if exp_element is None:
        return {"xp_total": 0, "xp_spent": 0, "xp_available": 0}
    
    earned = parse_int_safe(exp_element.get("earned"), 0)
    unspent = parse_int_safe(exp_element.get("unspent"), 0)
    
    # Calculate spent
    spent = earned - unspent
    
    return {
        "xp_total": earned,
        "xp_spent": spent,
        "xp_available": unspent
    }

def transform_vampire(vampire_elem: ET.Element) -> Dict[str, Any]:
    """Transform a vampire XML element to character.json format."""
    attrs = vampire_elem.attrib
    
    # Extract basic info
    character_name = attrs.get("name", "").strip()
    if not character_name:
        raise ValueError("Vampire element missing name attribute")
    
    clan_raw = attrs.get("clan", "").strip()
    clan = normalize_clan_name(clan_raw) if clan_raw else "Unknown"
    
    generation = parse_int_safe(attrs.get("generation"), 13)
    sect = attrs.get("sect", "").strip()
    
    # Ensure sect is exactly "Sabbat"
    if sect != "Sabbat":
        raise ValueError(f"Character {character_name} has sect '{sect}', not 'Sabbat'")
    
    # Build character object following character.json schema
    character = {
        "id": 0,
        "user_id": 0,
        "character_name": character_name,
        "player_name": attrs.get("player", "ST/NPC").strip() or "ST/NPC",
        "chronicle": "Valley by Night",
        "nature": attrs.get("nature", "").strip() or "",
        "demeanor": attrs.get("demeanor", "").strip() or "",
        "concept": "",
        "clan": clan,
        "generation": generation,
        "sire": attrs.get("sire", "").strip() or "",
        "pc": 0 if attrs.get("npc", "").lower() == "yes" else (1 if attrs.get("player", "") else 0),
        "appearance": "",
        "appearance_detailed": {
            "short_summary": "",
            "detailed_description": ""
        },
        "biography": "",
        "notes": "",
        "equipment": "",
        "character_image": "",
        "status": attrs.get("status", "active").lower() or "active",
        "camarilla_status": "Sabbat",  # Set to Sabbat for these characters
        "sect": sect,
        "title": attrs.get("title", "").strip() or "",
        "epithet": "",
        "description_tags": [],
        "timeline": {
            "birthplace": "",
            "embrace_period": "",
            "notable_mortal_past": "",
            "world_war_two_experience": "",
            "arrival_in_phoenix": "",
            "bunker_acquisition": "",
            "years_in_bunker": ""
        },
        "domain_and_haven": {
            "primary_haven_name": "",
            "primary_haven_type": "",
            "location_notes": "",
            "bunker_history": "",
            "bunker_function": [],
            "false_public_assumptions": ""
        },
        "current_residents": {
            "nosferatu": [],
            "ghouls": []
        },
        "personality": {
            "tagline": "",
            "narrative": ""
        },
        "traits": {
            "Physical": [],
            "Social": [],
            "Mental": []
        },
        "negativeTraits": {
            "Physical": [],
            "Social": [],
            "Mental": []
        },
        "abilities": [],
        "specializations": {},
        "disciplines": [],
        "backgrounds": {},
        "backgroundDetails": {},
        "morality": {
            "path_name": attrs.get("path", "Humanity").strip() or "Humanity",
            "path_rating": parse_int_safe(attrs.get("pathtraits"), 7),
            "conscience": parse_int_safe(attrs.get("conscience"), 3),
            "self_control": parse_int_safe(attrs.get("selfcontrol"), 3),
            "courage": parse_int_safe(attrs.get("courage"), 3),
            "willpower_permanent": parse_int_safe(attrs.get("willpower"), 5),
            "willpower_current": parse_int_safe(attrs.get("willpower"), 5),
            "humanity": parse_int_safe(attrs.get("pathtraits"), 7) if attrs.get("path", "").lower() == "humanity" else 0
        },
        "merits_flaws": [],
        "status": {
            "sect_status": "",
            "clan_status": "",
            "city_status": "",
            "health_levels": parse_int_safe(attrs.get("physicalmax"), 7),
            "blood_pool_current": parse_int_safe(attrs.get("blood"), 10),
            "blood_pool_maximum": parse_int_safe(attrs.get("blood"), 10),
            "blood_per_turn": 1,
            "xp_total": 0,
            "xp_spent": 0,
            "xp_available": 0,
            "notes": ""
        },
        "coteries": [],
        "relationships": [],
        "rituals": [],
        "research_notes": {},
        "artifacts": [],
        "custom_data": None,
        "actingNotes": "",
        "agentNotes": "",
        "health_status": "",
        "morality_path": attrs.get("path", "Humanity").strip() or "Humanity",
        "created_at": "",
        "updated_at": ""
    }
    
    # Extract traits
    for traitlist in vampire_elem.findall("traitlist"):
        list_name = traitlist.get("name", "").strip()
        
        if list_name == "Physical":
            character["traits"]["Physical"] = extract_traits(traitlist)
        elif list_name == "Social":
            character["traits"]["Social"] = extract_traits(traitlist)
        elif list_name == "Mental":
            character["traits"]["Mental"] = extract_traits(traitlist)
        elif list_name == "Negative Physical":
            character["negativeTraits"]["Physical"] = extract_traits(traitlist)
        elif list_name == "Negative Social":
            character["negativeTraits"]["Social"] = extract_traits(traitlist)
        elif list_name == "Negative Mental":
            character["negativeTraits"]["Mental"] = extract_traits(traitlist)
        elif list_name == "Abilities":
            character["abilities"] = extract_abilities(traitlist)
            # Build specializations dict
            for ability in character["abilities"]:
                if "specialization" in ability:
                    character["specializations"][ability["name"]] = ability["specialization"]
        elif list_name == "Disciplines":
            character["disciplines"] = extract_disciplines(traitlist)
        elif list_name == "Backgrounds":
            character["backgrounds"] = extract_backgrounds(traitlist)
        elif list_name == "Merits":
            merits = extract_merits_flaws(traitlist, None)
            character["merits_flaws"].extend(merits)
        elif list_name == "Flaws":
            flaws = extract_merits_flaws(None, traitlist)
            character["merits_flaws"].extend(flaws)
        elif list_name == "Rituals":
            for trait in traitlist.findall("trait"):
                ritual_name = trait.get("name", "").strip()
                if ritual_name:
                    character["rituals"].append(ritual_name)
    
    # Extract experience
    exp_elem = vampire_elem.find("experience")
    if exp_elem is not None:
        exp_data = extract_experience(exp_elem)
        character["status"]["xp_total"] = exp_data["xp_total"]
        character["status"]["xp_spent"] = exp_data["xp_spent"]
        character["status"]["xp_available"] = exp_data["xp_available"]
    
    # Extract notes
    notes_elem = vampire_elem.find("notes")
    if notes_elem is not None and notes_elem.text:
        character["notes"] = notes_elem.text.strip()
        # Also add to status.notes if appropriate
        if character["status"]["notes"]:
            character["status"]["notes"] += "\n\n" + notes_elem.text.strip()
        else:
            character["status"]["notes"] = notes_elem.text.strip()
    
    # Extract equipment
    equipment_list = vampire_elem.find("traitlist[@name='Equipment']")
    if equipment_list is not None:
        equipment_items = []
        for trait in equipment_list.findall("trait"):
            item = trait.get("name", "").strip()
            if item:
                equipment_items.append(item)
        if equipment_items:
            character["equipment"] = ", ".join(equipment_items)
    
    # Extract coterie
    coterie_name = attrs.get("coterie", "").strip()
    if coterie_name:
        character["coteries"] = [{
            "coterie_name": coterie_name,
            "coterie_type": "Sabbat Pack",
            "role": character.get("title", ""),
            "description": "",
            "notes": ""
        }]
    
    return character

def main():
    """Main execution function."""
    input_file = Path("G:/VbN/Grapevine/All NPCs2025.gv3")
    output_json = Path("G:/VbN/Grapevine/Sabbat.json")
    output_md = Path("G:/VbN/Grapevine/Sabbat.md")
    
    print(f"Loading XML file: {input_file}")
    
    # Read and clean the file to handle encoding issues
    try:
        with open(input_file, "rb") as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading file: {e}")
        return
    
    # Try multiple encodings
    xml_content = None
    for encoding in ['utf-8', 'latin-1', 'cp1252', 'iso-8859-1']:
        try:
            xml_content = content.decode(encoding, errors='replace')
            break
        except Exception:
            continue
    
    if xml_content is None:
        print("Failed to decode file with any encoding")
        return
    
    # Clean problematic characters that break XML parsing
    xml_content = xml_content.replace('\x00', '')  # Remove null bytes
    # Replace invalid XML characters in attribute values (keep valid XML chars only)
    # This regex finds attribute values with invalid chars and replaces them with ?
    def clean_attr(match):
        quote = match.group(1)
        value = match.group(2)
        # Replace control chars and invalid XML chars except allowed ones
        cleaned = ''.join(c if (ord(c) >= 32 and ord(c) != 127) or c in '\t\n\r' else '?' for c in value)
        return f'{quote}{cleaned}{quote}'
    
    xml_content = re.sub(r'(note=")([^"]*?)(")', clean_attr, xml_content)
    
    # Write cleaned content to temp file for parsing
    import tempfile
    import os
    with tempfile.NamedTemporaryFile(mode='w', encoding='utf-8', suffix='.xml', delete=False) as tmp:
        tmp.write(xml_content)
        tmp_path = tmp.name
    
    try:
        print("Parsing XML structure...")
        # Use iterparse for memory efficiency with large files
        sabbat_vampires = []
        seen_names: Set[str] = set()
        
        for event, elem in ET.iterparse(tmp_path, events=('end',)):
            if elem.tag == 'vampire':
                sect = elem.get('sect', '').strip()
                if sect == 'Sabbat':
                    name = elem.get('name', '').strip()
                    if name and name not in seen_names:
                        seen_names.add(name)
                        try:
                            transformed = transform_vampire(elem)
                            sabbat_vampires.append(transformed)
                        except Exception as e:
                            print(f"Error transforming character '{name}': {e}")
                
                # Clear element to free memory
                elem.clear()
        
        # Cleanup temp file
        os.unlink(tmp_path)
        
    except Exception as e:
        print(f"Error parsing XML: {e}")
        import traceback
        traceback.print_exc()
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)
        return
    
    print(f"Found {len(sabbat_vampires)} unique Sabbat characters")
    
    # Write JSON output
    print(f"Writing JSON to {output_json}")
    with open(output_json, "w", encoding="utf-8") as f:
        json.dump(sabbat_vampires, f, indent=2, ensure_ascii=False)
    
    # Generate Markdown report
    print(f"Generating Markdown report: {output_md}")
    with open(output_md, "w", encoding="utf-8") as f:
        f.write("# Sabbat Characters Migration Report\n\n")
        f.write(f"**Total Characters:** {len(sabbat_vampires)}\n\n")
        f.write("## Character List\n\n")
        f.write("| Name | Clan |\n")
        f.write("|------|------|\n")
        
        notes = []
        
        for char in sorted(sabbat_vampires, key=lambda x: x["character_name"]):
            name = char["character_name"]
            clan = char["clan"]
            
            if not name:
                notes.append(f"- Character with missing name (clan: {clan})")
                name = "[Missing Name]"
            
            if not clan or clan == "Unknown":
                notes.append(f"- {name}: Missing or unknown clan")
                clan = "Unknown"
            
            f.write(f"| {name} | {clan} |\n")
        
        if notes:
            f.write("\n## Notes\n\n")
            for note in notes:
                f.write(f"{note}\n")
    
    print("Migration complete!")
    print(f"- JSON output: {output_json}")
    print(f"- Markdown report: {output_md}")
    print(f"- Total characters exported: {len(sabbat_vampires)}")

if __name__ == "__main__":
    main()

