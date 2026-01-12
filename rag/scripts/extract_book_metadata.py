#!/usr/bin/env python3
"""
Extract book metadata and create book_index.json.
"""
import os
import json
import re
from pathlib import Path
from typing import Dict, Any, List, Optional

REPO_ROOT = Path(__file__).parent.parent.parent
SOURCE_DIR = REPO_ROOT / "reference" / "Books_md_ready_fixed_cleaned"
RAG_DIR = REPO_ROOT / "rag"


def create_book_slug(filename: str) -> str:
    """Create a book ID slug from filename."""
    slug = filename.replace('.md', '')
    slug = re.sub(r'[^\w\s-]', '', slug)
    slug = re.sub(r'[-\s]+', '_', slug)
    slug = slug.lower()
    return slug


def extract_title_from_filename(filename: str) -> str:
    """Extract title from filename."""
    return filename.replace('.md', '').strip()


def detect_system(content: str) -> str:
    """Detect system from content."""
    systems = {
        "Mind's Eye Theatre": [
            r"Mind'?s?\s+Eye\s+Theatre",
            r"MET\s+",
        ],
        "World of Darkness": [
            r"World\s+of\s+Darkness",
            r"WOD",
        ],
        "Vampire: The Masquerade": [
            r"Vampire.*Masquerade",
            r"VTM",
        ],
        "Werewolf: The Apocalypse": [
            r"Werewolf.*Apocalypse",
        ],
        "Wraith: The Oblivion": [
            r"Wraith.*Oblivion",
        ],
        "Mage: The Ascension": [
            r"Mage.*Ascension",
            r"MTA",
        ],
    }
    
    detected = []
    for system_name, patterns in systems.items():
        for pattern in patterns:
            if re.search(pattern, content, re.IGNORECASE):
                detected.append(system_name)
                break
    
    # Return primary system (Mind's Eye Theatre takes precedence for these books)
    if "Mind's Eye Theatre" in detected:
        return "Mind's Eye Theatre"
    elif detected:
        return detected[0]
    return "Unknown"


def extract_credits(content: str) -> Dict[str, Any]:
    """Extract credits information."""
    credits_info = {
        'authors': [],
        'editors': [],
        'developers': [],
    }
    
    credits_match = re.search(r'##?\s*[Cc]redits?\s*\n(.*?)(?=\n##|\n#|$)', content, re.DOTALL)
    if not credits_match:
        return credits_info
    
    credits_text = credits_match.group(1)
    
    # Extract written by
    written_match = re.search(r'Written\s+by[:\s]+(.*?)(?:\n|Additional|Developed|Edited|Art|Layout|©)', credits_text, re.IGNORECASE | re.DOTALL)
    if written_match:
        authors_text = written_match.group(1).strip()
        # Split by common separators
        authors = re.split(r'[,;]\s*', authors_text)
        credits_info['authors'] = [a.strip() for a in authors if a.strip()]
    
    # Extract edited by
    edited_match = re.search(r'Edited\s+by[:\s]+(.*?)(?:\n|Written|Developed|Additional|Art|Layout|©)', credits_text, re.IGNORECASE | re.DOTALL)
    if edited_match:
        editors_text = edited_match.group(1).strip()
        editors = re.split(r'[,;]\s*', editors_text)
        credits_info['editors'] = [e.strip() for e in editors if e.strip()]
    
    # Extract developed by
    developed_match = re.search(r'Developed\s+by[:\s]+(.*?)(?:\n|Written|Edited|Additional|Art|Layout|©)', credits_text, re.IGNORECASE | re.DOTALL)
    if developed_match:
        devs_text = developed_match.group(1).strip()
        devs = re.split(r'[,;]\s*', devs_text)
        credits_info['developers'] = [d.strip() for d in devs if d.strip()]
    
    return credits_info


def extract_book_metadata(filepath: Path) -> Dict[str, Any]:
    """Extract metadata for a single book."""
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        lines = content.split('\n')
    
    filename = filepath.name
    book_slug = create_book_slug(filename)
    title = extract_title_from_filename(filename)
    
    # Try to get title from H1 if present
    h1_match = re.search(r'^#\s+(.+)$', content, re.MULTILINE)
    if h1_match and len(h1_match.group(1).strip()) > 5:  # Ignore very short H1s
        potential_title = h1_match.group(1).strip()
        # Ignore if it's just "Credits" or similar
        if potential_title.lower() not in ['credits', 'table of contents', 'contents']:
            title = potential_title
    
    system = detect_system(content)
    credits = extract_credits(content)
    
    metadata = {
        'book_id': book_slug,
        'title': title,
        'filename': str(filepath.relative_to(REPO_ROOT)),
        'system': system,
        'line_count': len(lines),
        'char_count': len(content),
        'authors': credits['authors'],
        'editors': credits['editors'],
        'developers': credits['developers'],
        'detection_notes': [],
    }
    
    # Add detection notes
    if not credits['authors'] and not credits['editors']:
        metadata['detection_notes'].append('No credits section found or parseable')
    
    if system == "Unknown":
        metadata['detection_notes'].append('System not clearly detected from content')
    
    return metadata


def main():
    """Extract metadata for all books and create book_index.json."""
    print("Extracting book metadata...")
    
    index_dir = RAG_DIR / "index"
    index_dir.mkdir(parents=True, exist_ok=True)
    
    # Get all markdown files
    md_files = sorted(SOURCE_DIR.glob("*.md"))
    
    book_index = []
    for filepath in md_files:
        print(f"Processing {filepath.name}...")
        metadata = extract_book_metadata(filepath)
        book_index.append(metadata)
    
    # Sort by book_id
    book_index.sort(key=lambda x: x['book_id'])
    
    # Write to JSON
    index_file = index_dir / "book_index.json"
    with open(index_file, 'w', encoding='utf-8') as f:
        json.dump(book_index, f, indent=2, ensure_ascii=False)
    
    print(f"Book index created with {len(book_index)} books: {index_file}")
    return book_index


if __name__ == "__main__":
    main()
