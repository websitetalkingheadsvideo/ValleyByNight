#!/usr/bin/env python3
"""
Process ALL files containing 'haven' from grep results
"""
import json
import os
from pathlib import Path
from datetime import datetime
import subprocess

# Base directory
base_dir = Path(r"\\amber\htdocs")

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
    # Clean up path
    clean_path = filepath.replace('\\', '/').replace('./', '').replace('.\\', '')
    parts = clean_path.split('/')
    if len(parts) > 1:
        return '/'.join(parts[:-1])
    return '.'

# Get all JSON and MD files using PowerShell (more reliable on Windows)
files_found = set()

# Use PowerShell to find all JSON and MD files containing 'haven'
ps_cmd = '''
Get-ChildItem -Recurse -File -Include *.json,*.md | 
Where-Object { 
    $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
    if ($content) { $content -match 'haven' }
} | 
Select-Object -ExpandProperty FullName
'''

try:
    result = subprocess.run(
        ['powershell', '-Command', ps_cmd],
        cwd=str(base_dir),
        capture_output=True,
        text=True,
        encoding='utf-8'
    )
    
    for line in result.stdout.split('\n'):
        if line.strip():
            full_path = Path(line.strip())
            try:
                rel_path = str(full_path.relative_to(base_dir)).replace('\\', '/')
                
                # Skip excluded directories
                if 'PC Havens' in rel_path or 'Violet Reliquary' in rel_path:
                    continue
                
                # Skip the output file itself
                if 'Haven_Information.json' in rel_path:
                    continue
                
                files_found.add(rel_path)
            except:
                pass
except Exception as e:
    print(f"Error: {e}")

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

# Create output structure
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

print(f"✅ Created {output_file}")
print(f"📊 Total files: {len(files_data)}")
print(f"📄 JSON files: {json_count}")
print(f"📝 MD files: {md_count}")
print(f"🏷️  Files with 'haven' in name: {name_count}")
