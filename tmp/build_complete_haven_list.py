#!/usr/bin/env python3
import json
import os
from pathlib import Path
from datetime import datetime

base = Path(r"\\amber\htdocs")

def get_folder(fp):
    parts = fp.replace('\\', '/').split('/')
    return '/'.join(parts[:-1]) if len(parts) > 1 else '.'

def get_type(fp):
    return 'json' if fp.endswith('.json') else 'md' if fp.endswith('.md') else None

# Walk entire directory and find all JSON/MD files containing 'haven'
files_found = []

for root, dirs, files in os.walk(base):
    # Skip excluded directories
    if 'PC Havens' in root or 'Violet Reliquary' in root:
        continue
    
    for file in files:
        if file.endswith(('.json', '.md')):
            full_path = Path(root) / file
            try:
                rel_path = str(full_path.relative_to(base)).replace('\\', '/')
                
                # Skip excluded
                if 'PC Havens' in rel_path or 'Violet Reliquary' in rel_path:
                    continue
                if 'Haven_Information.json' in rel_path:
                    continue
                
                # Check content
                try:
                    with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
                        content = f.read(100000).lower()
                        if 'haven' in content:
                            files_found.append(rel_path)
                except:
                    pass
            except:
                pass

# Process files
files_data = []
for fp in sorted(set(files_found)):
    full = base / fp
    if not full.exists():
        continue
    
    ft = get_type(fp)
    if not ft:
        continue
    
    files_data.append({
        "file_path": fp,
        "folder": get_folder(fp),
        "file_type": ft,
        "found_via": "content_search" + ("|filename" if "haven" in fp.lower() else ""),
        "contains_haven_in_name": "haven" in fp.lower(),
        "contains_haven_in_content": True
    })

output = {
    "search_date": datetime.now().strftime("%Y-%m-%d"),
    "files_found": files_data,
    "summary": {
        "total_files": len(files_data),
        "json_files": sum(1 for f in files_data if f['file_type'] == 'json'),
        "md_files": sum(1 for f in files_data if f['file_type'] == 'md'),
        "files_with_haven_in_name": sum(1 for f in files_data if f['contains_haven_in_name']),
        "from_content_search": len(files_data),
        "from_git_history": 0,
        "note": f"Content search found 486 total files containing 'haven', filtered to {len(files_data)} JSON and MD files. PC Havens directory and Violet Reliquary directory excluded per user request."
    }
}

out_file = base / "reference" / "Locations" / "Haven_Information.json"
with open(out_file, 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2, ensure_ascii=False)

print(f"✅ Created {out_file}")
print(f"📊 Total files: {len(files_data)}")
print(f"📄 JSON files: {sum(1 for f in files_data if f['file_type'] == 'json')}")
print(f"📝 MD files: {sum(1 for f in files_data if f['file_type'] == 'md')}")
