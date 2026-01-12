#!/usr/bin/env python3
"""Remove all references to https://vbn.talkingheads.video/ from project files"""

import os
import re
from pathlib import Path

def remove_url_from_file(filepath):
    """Remove URL from a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        if 'https://vbn.talkingheads.video' in content:
            new_content = re.sub(r'https://vbn\.talkingheads\.video/', '', content)
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            return True
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
    return False

# Get project root
project_root = Path(__file__).parent

# File extensions to process
extensions = {'.php', '.md', '.json', '.txt', '.xml'}

# Directories to skip
skip_dirs = {'.git', 'node_modules', '__pycache__', '.venv', 'venv'}

count = 0
for root, dirs, files in os.walk(project_root):
    # Skip certain directories
    dirs[:] = [d for d in dirs if d not in skip_dirs]
    
    for file in files:
        if any(file.endswith(ext) for ext in extensions):
            filepath = Path(root) / file
            if remove_url_from_file(filepath):
                count += 1
                print(f"Updated: {filepath.relative_to(project_root)}")

print(f"\nTotal files updated: {count}")
