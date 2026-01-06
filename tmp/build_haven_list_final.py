#!/usr/bin/env python3
import json
import os
from pathlib import Path
from datetime import datetime

base = Path(r"\\amber\htdocs")
files_found = []

print("Scanning files...")

for root, dirs, files in os.walk(base):
    if 'PC Havens' in root or 'Violet Reliquary' in root:
        dirs[:] = []
        continue
    
    for f in files:
        if not (f.endswith('.json') or f.endswith('.md')):
            continue
        
        full = Path(root) / f
        if 'Haven_Information.json' in str(full):
            continue
        
        try:
            rel = str(full.relative_to(base)).replace('\\', '/')
            if 'PC Havens' in rel or 'Violet Reliquary' in rel:
                continue
            
            try:
                with open(full, 'r', encoding='utf-8', errors='ignore') as file:
                    content = file.read(100000).lower()
                    if 'haven' in content:
                        files_found.append(rel)
            except:
                pass
        except:
            pass

print(f"Found {len(files_found)} files")

data = []
for fp in sorted(set(files_found)):
    folder = '/'.join(fp.split('/')[:-1]) if '/' in fp else '.'
    ft = 'json' if fp.endswith('.json') else 'md'
    has_name = 'haven' in fp.lower()
    
    data.append({
        "file_path": fp,
        "folder": folder,
        "file_type": ft,
        "found_via": "content_search|filename" if has_name else "content_search",
        "contains_haven_in_name": has_name,
        "contains_haven_in_content": True
    })

out = {
    "search_date": datetime.now().strftime("%Y-%m-%d"),
    "files_found": data,
    "summary": {
        "total_files": len(data),
        "json_files": sum(1 for x in data if x['file_type'] == 'json'),
        "md_files": sum(1 for x in data if x['file_type'] == 'md'),
        "files_with_haven_in_name": sum(1 for x in data if x['contains_haven_in_name']),
        "from_content_search": len(data),
        "from_git_history": 0,
        "note": f"Content search found 486 total files containing 'haven', filtered to {len(data)} JSON and MD files. PC Havens directory and Violet Reliquary directory excluded per user request."
    }
}

out_file = base / "reference" / "Locations" / "Haven_Information.json"
with open(out_file, 'w', encoding='utf-8') as f:
    json.dump(out, f, indent=2, ensure_ascii=False)

print(f"Wrote {len(data)} files to {out_file}")
