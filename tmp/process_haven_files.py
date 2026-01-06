#!/usr/bin/env python3
"""
Process all files containing 'haven' and create comprehensive Haven_Information.json
"""
import json
import os
from pathlib import Path
from datetime import datetime

# Base directory
base_dir = Path(r"\\amber\htdocs")

# All files that contain 'haven' (from grep search - 484 files)
# We need to filter to only JSON and MD files
all_haven_files = []

# Known files with haven in name
known_haven_files = {
    "reference/Locations/valley_by_night_havens.json",
    "data/havens.json",
    "tmp/session_report_2025-12-03-pc-havens-system.md",
    "reference/Locations/PC Havens/Anarch/Anarch Haven.json",
    "reference/Locations/PC Havens/Camarilla Haven.json",
    "reference/Locations/PC Havens/Malkavian Haven.json",
    "reference/Locations/PC Havens/Gangrel Haven.json",
    "reference/Locations/PC Havens/Toreador Haven.json",
    "reference/Locations/PC Havens/Tremere Haven.json",
    "reference/Locations/PC Havens/Ventrue Haven.json",
    "reference/Locations/PC Havens/Brujah Haven.json",
    "reference/Locations/PC Havens/Followers of Set Haven.json",
    "reference/Locations/PC Havens/Nosferatu Haven.json",
    "reference/Locations/PC Havens/Giovanni Haven.json",
    "Prompts/Haven_Desription.md",
    "reference/Locations/PC Havens/PC_Haven_Plots.md",
    "reference/Locations/Mesa Storm Drains.json",
    "reference/Locations/The Bunker - Computer Room.json",
    "reference/Locations/The Warrens.json"
}

def has_haven_in_name(filepath):
    """Check if filepath contains 'haven' (case insensitive)"""
    return 'haven' in filepath.lower()

def get_file_type(filepath):
    """Determine file type from extension"""
    ext = Path(filepath).suffix.lower()
    if ext == '.json':
        return 'json'
    elif ext == '.md':
        return 'md'
    return None

def get_folder(filepath):
    """Extract folder name from filepath"""
    parts = filepath.replace('\\', '/').split('/')
    if len(parts) > 1:
        return '/'.join(parts[:-1])
    return '.'

# Walk through entire project to find JSON and MD files that contain 'haven'
# We'll check files in key directories
key_directories = [
    base_dir / "reference" / "Locations",
    base_dir / "data",
    base_dir / "database",
    base_dir / "tmp",
    base_dir / "Prompts",
    base_dir / "reference" / "Characters",
    base_dir / "reference" / "world",
    base_dir / "canon",
]

files_found = set()

# First, add all known haven files
for filepath in known_haven_files:
    full_path = base_dir / filepath
    if full_path.exists():
        files_found.add(filepath)

# Now search through key directories for JSON and MD files
for directory in key_directories:
    if not directory.exists():
        continue
    
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith(('.json', '.md')):
                full_path = Path(root) / file
                try:
                    rel_path = str(full_path.relative_to(base_dir)).replace('\\', '/')
                    # Check if file contains 'haven' by reading a sample
                    try:
                        with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
                            content = f.read(10000).lower()  # Read first 10KB
                            if 'haven' in content:
                                files_found.add(rel_path)
                    except:
                        pass
                except:
                    pass

# Also check root level files
for file in os.listdir(base_dir):
    if file.endswith(('.json', '.md')):
        full_path = base_dir / file
        if full_path.is_file():
            try:
                with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read(10000).lower()
                    if 'haven' in content:
                        files_found.add(file)
            except:
                pass

# Compile file data
files_data = []
for filepath in sorted(files_found):
    full_path = base_dir / filepath
    if not full_path.exists():
        continue
    
    file_type = get_file_type(filepath)
    if not file_type:
        continue
    
    folder = get_folder(filepath)
    has_haven_name = has_haven_in_name(filepath)
    
    # Determine how it was found
    found_via = []
    if filepath in known_haven_files:
        found_via.append("known_haven_file")
    if has_haven_name:
        found_via.append("filename")
    found_via.append("content_search")
    
    files_data.append({
        "file_path": filepath,
        "folder": folder,
        "file_type": file_type,
        "found_via": "|".join(found_via),
        "contains_haven_in_name": has_haven_name,
        "contains_haven_in_content": True  # All files in our list contain haven
    })

# Create summary
json_count = sum(1 for f in files_data if f['file_type'] == 'json')
md_count = sum(1 for f in files_data if f['file_type'] == 'md')
name_count = sum(1 for f in files_data if f['contains_haven_in_name'])

# Create output structure
output = {
    "search_date": datetime.now().strftime("%Y-%m-%d"),
    "files_found": files_data,
    "summary": {
        "total_files": len(files_data),
        "json_files": json_count,
        "md_files": md_count,
        "files_with_haven_in_name": name_count,
        "from_content_search": len(files_data)
    }
}

# Write to file
output_file = base_dir / "reference" / "Locations" / "Haven_Information.json"
output_file.parent.mkdir(parents=True, exist_ok=True)

with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2, ensure_ascii=False)

print(f"✅ Created {output_file}")
print(f"📊 Total files: {len(files_data)}")
print(f"📄 JSON files: {json_count}")
print(f"📝 MD files: {md_count}")
print(f"🏷️  Files with 'haven' in name: {name_count}")
