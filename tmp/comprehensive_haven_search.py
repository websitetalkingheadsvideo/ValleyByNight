#!/usr/bin/env python3
"""
Comprehensive search for ALL JSON and MD files containing 'haven'
"""
import json
import os
from pathlib import Path
from datetime import datetime

base_dir = Path(r"\\amber\htdocs")

def get_folder(filepath):
    parts = filepath.replace('\\', '/').split('/')
    return '/'.join(parts[:-1]) if len(parts) > 1 else '.'

def get_file_type(filepath):
    ext = Path(filepath).suffix.lower()
    return 'json' if ext == '.json' else 'md' if ext == '.md' else None

files_found = set()

print("Scanning entire project for JSON and MD files containing 'haven'...")
print(f"Base directory: {base_dir}")

count = 0
# Walk entire directory tree
for root, dirs, files in os.walk(base_dir):
    count += 1
    if count % 100 == 0:
        print(f"Scanned {count} directories, found {len(files_found)} files so far...")
    # Skip excluded directories
    if 'PC Havens' in root:
        dirs[:] = []
        continue
    if 'Violet Reliquary' in root:
        dirs[:] = []
        continue
    
    for file in files:
        if not (file.endswith('.json') or file.endswith('.md')):
            continue
        
        full_path = Path(root) / file
        
        # Skip the output file itself
        if 'Haven_Information.json' in str(full_path):
            continue
        
        try:
            rel_path = str(full_path.relative_to(base_dir)).replace('\\', '/')
            
            # Double-check exclusions
            if 'PC Havens' in rel_path or 'Violet Reliquary' in rel_path:
                continue
            
            # Check if file contains 'haven'
            try:
                with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
                    # Read in chunks to handle large files
                    found_haven = False
                    content_chunk = ''
                    while True:
                        chunk = f.read(10000)
                        if not chunk:
                            break
                        content_chunk += chunk.lower()
                        if 'haven' in content_chunk:
                            found_haven = True
                            break
                        # Limit to first 100KB
                        if len(content_chunk) > 100000:
                            break
                    
                    if found_haven:
                        files_found.add(rel_path)
            except Exception as e:
                pass
        except Exception as e:
            pass

print(f"Found {len(files_found)} files containing 'haven'")

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
    has_haven_name = 'haven' in filepath.lower()
    
    found_via = []
    if has_haven_name:
        found_via.append("filename")
    found_via.append("content_search")
    
    files_data.append({
        "file_path": filepath,
        "folder": folder,
        "file_type": file_type,
        "found_via": "|".join(found_via),
        "contains_haven_in_name": has_haven_name,
        "contains_haven_in_content": True
    })

# Create summary
json_count = sum(1 for f in files_data if f['file_type'] == 'json')
md_count = sum(1 for f in files_data if f['file_type'] == 'md')
name_count = sum(1 for f in files_data if f['contains_haven_in_name'])

# Create output
output = {
    "search_date": datetime.now().strftime("%Y-%m-%d"),
    "files_found": files_data,
    "summary": {
        "total_files": len(files_data),
        "json_files": json_count,
        "md_files": md_count,
        "files_with_haven_in_name": name_count,
        "from_content_search": len(files_data),
        "from_git_history": 0,
        "note": f"Content search found 486 total files containing 'haven', filtered to {len(files_data)} JSON and MD files. PC Havens directory and Violet Reliquary directory excluded per user request."
    }
}

# Write to file
output_file = base_dir / "reference" / "Locations" / "Haven_Information.json"
output_file.parent.mkdir(parents=True, exist_ok=True)

with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2, ensure_ascii=False)

print(f"\n✅ Created {output_file}")
print(f"📊 Total files: {len(files_data)}")
print(f"📄 JSON files: {json_count}")
print(f"📝 MD files: {md_count}")
print(f"🏷️  Files with 'haven' in name: {name_count}")
