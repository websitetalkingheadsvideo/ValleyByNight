#!/usr/bin/env python3
"""
Regenerate ONLY lexicon extraction for LotNR RAG Prep pipeline.
Fixes contamination issues by using strict boundary detection.
"""

import re
import json
from pathlib import Path
from typing import List, Dict, Tuple, Optional

# Configuration
SOURCE_FILE = Path("v:/reference/Books/LotNR-formatted.md")
OUTPUT_DIR = Path("v:/reference/Books/LofNR")

# Output files (only regenerate these)
LEXICON_FILE = OUTPUT_DIR / "lotnr_lexicon.md"
CHUNKS_FILE = OUTPUT_DIR / "lotnr_chunks.jsonl"
MANIFEST_FILE = OUTPUT_DIR / "lotnr_manifest.json"

# Input files (use as ground truth)
RULES_FILE = OUTPUT_DIR / "lotnr_rules.md"
FICTION_FILE = OUTPUT_DIR / "lotnr_fiction.md"


def normalize_text_minimal(text: str) -> str:
    """Minimal normalization: collapse spaces, fix hyphenation."""
    # Collapse multiple spaces
    text = re.sub(r' {2,}', ' ', text)
    # Fix hyphenated words split across lines
    text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
    return text


def find_lexicon_bounds(lines: List[str]) -> Tuple[Optional[int], Optional[int], Optional[int], Optional[int]]:
    """
    Find strict boundaries for Lexicon and MET Terms sections.
    Returns: (lexicon_start, lexicon_end, met_start, met_end) line indices
    """
    lexicon_start = None
    lexicon_end = None
    met_start = None
    met_end = None
    
    # Find Lexicon section
    for i, line in enumerate(lines):
        # Check if this line is a Lexicon heading/marker
        normalized = line.strip().lower()
        if re.match(r'^#*\s*lexicon\s*$', normalized) or normalized == 'lexicon' or normalized == 'lexicon':
            lexicon_start = i
            # Determine heading level if it's a heading
            heading_level = len(re.match(r'^#+', line).group()) if re.match(r'^#+', line) else None
            
            # Find end: MET terms section, next heading of same or higher level, or clear content break
            for j in range(i + 1, len(lines)):
                next_line = lines[j].strip()
                if not next_line:
                    continue
                
                # Check for MET terms section FIRST (this ends lexicon)
                normalized_next = next_line.lower()
                if (len(next_line) < 35 and 
                    "mind" in normalized_next and 
                    "eye" in normalized_next and 
                    "theatre" in normalized_next and 
                    "terms" in normalized_next and
                    not next_line.startswith('#') and
                    '—' not in next_line and
                    '-' not in next_line[1:]):
                    lexicon_end = j
                    break
                
                # Check for heading
                heading_match = re.match(r'^(#+)\s+', next_line)
                if heading_match:
                    next_level = len(heading_match.group(1))
                    if heading_level is None or next_level <= heading_level:
                        # Found end boundary
                        lexicon_end = j
                        break
                
                # Check for clear content break (fiction starting)
                if re.match(r'^Elysium\s+had\s+closed', next_line, re.IGNORECASE):
                    lexicon_end = j
                    break
            
            break
    
    # Find MET Terms section (should be right after lexicon ends)
    for i, line in enumerate(lines):
        line_stripped = line.strip()
        normalized = line_stripped.lower()
        
        # Check if this line is the MET terms marker
        # It should be a short line containing "mind" "eye" "theatre" "terms"
        if (len(line_stripped) < 35 and  # Short line
            "mind" in normalized and 
            "eye" in normalized and 
            "theatre" in normalized and 
            "terms" in normalized and
            not line_stripped.startswith('#') and  # Not a heading
            '—' not in line_stripped and  # Not an entry
            '-' not in line_stripped[1:]):  # Not an entry with dash
            met_start = i
            heading_level = None  # Not a heading
            
            # Find end: next heading of same or higher level, or clear content break
            for j in range(i + 1, len(lines)):
                next_line = lines[j].strip()
                if not next_line:
                    continue
                
                # Check for heading
                heading_match = re.match(r'^(#+)\s+', next_line)
                if heading_match:
                    next_level = len(heading_match.group(1))
                    if heading_level is None or next_level <= heading_level:
                        met_end = j
                        break
                
                # Check for clear content break (fiction starting)
                if re.match(r'^Elysium\s+had\s+closed', next_line, re.IGNORECASE):
                    met_end = j
                    break
                
                # Check for chapter heading
                if re.match(r'^#+\s*CHAPTER|^#+\s*Chapter', next_line, re.IGNORECASE):
                    met_end = j
                    break
            
            break
    
    return lexicon_start, lexicon_end, met_start, met_end


def is_valid_lexicon_entry(term: str, definition: str) -> bool:
    """
    Anti-contamination filter: reject entries that look like fiction/narrative.
    """
    term_clean = term.strip()
    
    # Reject if term is too long (likely a sentence)
    if len(term_clean) > 40:
        return False
    
    # Reject if term contains sentence-ending punctuation
    if re.search(r'[.!?]', term_clean):
        return False
    
    # Reject if term contains 2+ commas (likely a sentence)
    if term_clean.count(',') >= 2:
        return False
    
    # Reject if term contains newlines (shouldn't happen after reconstruction, but check)
    if '\n' in term_clean:
        return False
    
    # Reject if definition is missing or too short (likely incomplete)
    if not definition or len(definition.strip()) < 5:
        return False
    
    return True


def extract_and_format_lexicon_entries(lines: List[str], start_idx: int, end_idx: int) -> List[str]:
    """
    Extract lexicon entries from bounded section and format them.
    """
    if start_idx is None or end_idx is None:
        return []
    
    section_lines = lines[start_idx:end_idx]
    entries = []
    current_entry = None
    current_term = None
    current_def = None
    
    i = 0
    while i < len(section_lines):
        line = section_lines[i].strip()
        
        # Skip empty lines and section headers
        if not line or re.match(r"^#+\s*(Lexicon|mind['']?s\s+eye\s+theatre\s+terms)", line, re.IGNORECASE):
            i += 1
            continue
        
        # Check if this line starts a new entry (Term — Definition pattern)
        # Pattern 1: Term — Definition (with em dash or double hyphen)
        # Must start with capital letter (new term)
        # Handle both spaced and unspaced dashes
        # Also check if line contains multiple entries (e.g., "Term1 — Def1. Term2 — Def2")
        match = re.match(r'^([A-Z][^—\-]{1,40}?)\s*[—\-]+\s*(.+)$', line)
        if match:
            # Save previous entry if exists
            if current_term and current_def:
                if is_valid_lexicon_entry(current_term, current_def):
                    entries.append(f"**{current_term}** — {current_def}")
                current_entry = None
                current_term = None
                current_def = None
            
            # Start new entry
            term = match.group(1).strip()
            definition = match.group(2).strip()
            
            # Check if definition contains another entry (pattern: ". Word — Definition")
            # Look for sentence-ending punctuation followed by capital letter and dash
            split_match = re.search(r'\.\s+([A-Z][^—\-]{1,40}?)\s*[—\-]+\s*(.+)$', definition)
            if split_match:
                # Split into two entries - include the period in first definition
                # The match starts at the period, so everything before it is the first definition
                period_pos = split_match.start()
                first_def = definition[:period_pos + 1].strip()  # Include period
                second_term = split_match.group(1).strip()
                second_def = split_match.group(2).strip()
                
                # Save first entry
                if is_valid_lexicon_entry(term, first_def):
                    entries.append(f"**{term}** — {first_def}")
                
                # Start second entry
                current_term = second_term
                current_def = second_def
            else:
                current_term = term
                current_def = definition
            
            i += 1
            
            # Check for continuation on next lines
            while i < len(section_lines):
                next_line = section_lines[i].strip()
                if not next_line:
                    break
                # If next line starts a new entry (capital letter + dash), stop
                if re.match(r'^[A-Z][^—\-]{1,40}?\s*[—\-]+\s*', next_line):
                    break
                # Otherwise, it's a continuation
                current_def += ' ' + next_line
                i += 1
            continue
        
        # Pattern 2: Already formatted **Term** — Definition
        match = re.match(r'^\*\*([^*]+)\*\*\s*[—\-]+\s*(.+)$', line)
        if match:
            if current_term and current_def:
                if is_valid_lexicon_entry(current_term, current_def):
                    entries.append(f"**{current_term}** — {current_def}")
            
            current_term = match.group(1).strip()
            current_def = match.group(2).strip()
            
            # Check for continuation
            i += 1
            while i < len(section_lines):
                next_line = section_lines[i].strip()
                if not next_line:
                    break
                if re.match(r'^\*\*[^*]+\*\*|^[A-Z][^—\-]*\s*[—\-]+\s+', next_line):
                    break
                current_def += ' ' + next_line
                i += 1
            continue
        
        # If we have a current entry, this might be a continuation
        if current_term and current_def:
            # Check if this looks like a new entry (capital letter + dash pattern)
            if re.match(r'^[A-Z][^—\-]{1,40}?\s*[—\-]+\s*', line):
                # This is a new entry - save current and start new
                if is_valid_lexicon_entry(current_term, current_def):
                    entries.append(f"**{current_term}** — {current_def}")
                # Parse new entry
                match = re.match(r'^([A-Z][^—\-]{1,40}?)\s*[—\-]+\s*(.+)$', line)
                if match:
                    current_term = match.group(1).strip()
                    current_def = match.group(2).strip()
                    i += 1
                    # Check for continuation
                    while i < len(section_lines):
                        next_line = section_lines[i].strip()
                        if not next_line:
                            break
                        if re.match(r'^[A-Z][^—\-]{1,40}?\s*[—\-]+\s*', next_line):
                            break
                        current_def += ' ' + next_line
                        i += 1
                    continue
            else:
                # It's a continuation
                current_def += ' ' + line
                i += 1
                continue
        
        i += 1
    
    # Save last entry
    if current_term and current_def:
        if is_valid_lexicon_entry(current_term, current_def):
            entries.append(f"**{current_term}** — {current_def}")
    
    # Normalize entries
    normalized_entries = []
    for entry in entries:
        # Normalize spaces
        entry = re.sub(r' {2,}', ' ', entry)
        
        # Fix specific known scan artifacts first (these are clearly broken words)
        entry = re.sub(r'Em-braced', 'Embraced', entry, flags=re.IGNORECASE)
        entry = re.sub(r'In-quisition', 'Inquisition', entry, flags=re.IGNORECASE)
        entry = re.sub(r'ruler-ship', 'rulership', entry, flags=re.IGNORECASE)
        
        # Fix other hyphenation artifacts (word-hyphen-space-word or word-hyphen-word patterns)
        # Only fix if it looks like a line-break artifact (common words split)
        entry = re.sub(r'(\w+)-\s+(\w+)', r'\1\2', entry)  # With space
        entry = re.sub(r'(\w{2,})-\s*([a-z]{2,})', r'\1\2', entry)  # Without space, lowercase continuation
        
        # Fix incomplete definitions - add period to Clan definition if missing
        if re.match(r'\*\*Clan\*\*', entry) and not entry.rstrip().endswith('.'):
            entry = entry.rstrip() + '.'
        
        normalized_entries.append(entry)
    
    return normalized_entries


def create_lexicon_chunks(lexicon_content: str) -> List[Dict]:
    """Create chunks from lexicon content - one per entry."""
    chunks = []
    lines = lexicon_content.split('\n')
    current_section = "Lexicon"
    
    for line in lines:
        line = line.strip()
        if not line:
            continue
        
        # Check for section heading
        if re.match(r"^##\s+Mind['']?s\s+Eye\s+Theatre\s+Terms", line, re.IGNORECASE):
            current_section = "Mind's Eye Theatre Terms"
            continue
        elif re.match(r'^#\s+Lexicon', line, re.IGNORECASE):
            current_section = "Lexicon"
            continue
        
        # Check for lexicon entry
        match = re.match(r'^\*\*([^*]+)\*\*\s*[—\-]+\s*(.+)$', line)
        if match:
            term = match.group(1).strip()
            definition = match.group(2).strip()
            
            # Create term slug
            term_slug = re.sub(r'[^\w\s-]', '', term).lower()
            term_slug = re.sub(r'\s+', '-', term_slug)
            
            chunks.append({
                "id": f"lotnr_lexicon::term::{term_slug}",
                "doc": "lotnr_lexicon",
                "title": term,
                "breadcrumbs": [current_section],
                "chunk_type": "lexicon_entry",
                "text": line,
                "source_file": "LotNR-formatted.md"
            })
    
    return chunks


def read_existing_chunks() -> Tuple[List[Dict], List[Dict]]:
    """Read existing rules and fiction chunks from JSONL file."""
    rules_chunks = []
    fiction_chunks = []
    
    if CHUNKS_FILE.exists():
        with open(CHUNKS_FILE, 'r', encoding='utf-8') as f:
            for line in f:
                if line.strip():
                    try:
                        chunk = json.loads(line)
                        doc_type = chunk.get('doc', '')
                        if doc_type == 'lotnr_rules':
                            rules_chunks.append(chunk)
                        elif doc_type == 'lotnr_fiction':
                            fiction_chunks.append(chunk)
                    except json.JSONDecodeError:
                        continue
    
    return rules_chunks, fiction_chunks


def main():
    """Main regeneration function."""
    print("Reading source file...")
    with open(SOURCE_FILE, 'r', encoding='utf-8') as f:
        source_lines = f.readlines()
    
    # Find lexicon bounds
    print("Finding lexicon boundaries...")
    lexicon_start, lexicon_end, met_start, met_end = find_lexicon_bounds(source_lines)
    
    print(f"Lexicon bounds: {lexicon_start} to {lexicon_end}")
    print(f"MET Terms bounds: {met_start} to {met_end}")
    
    if lexicon_start is None:
        print("ERROR: Could not find Lexicon section!")
        return
    
    # Extract and format lexicon entries
    print("Extracting lexicon entries...")
    lexicon_entries = extract_and_format_lexicon_entries(source_lines, lexicon_start, lexicon_end)
    
    # Extract MET terms entries
    met_entries = []
    if met_start is not None and met_end is not None:
        print("Extracting MET Terms entries...")
        met_entries = extract_and_format_lexicon_entries(source_lines, met_start, met_end)
    
    # Build lexicon document
    print("Building lexicon document...")
    lexicon_lines = ["# Lexicon", ""]
    
    # Add intro text if present (skip the "lexiCon" marker line)
    if lexicon_start is not None:
        intro_start = lexicon_start + 1
        intro_end = min(lexicon_start + 5, lexicon_end if lexicon_end else len(source_lines))
        intro_text = []
        for i in range(intro_start, intro_end):
            line = source_lines[i].strip()
            if re.match(r'^[A-Z][^—\-]*\s*[—\-]+\s+', line):
                break
            if line and not re.match(r'^lexicon', line, re.IGNORECASE):
                intro_text.append(line)
        if intro_text:
            lexicon_lines.extend(intro_text)
            lexicon_lines.append("")
    
    # Add lexicon entries
    for entry in lexicon_entries:
        lexicon_lines.append(entry)
        lexicon_lines.append("")
    
    # Add MET Terms section
    if met_entries:
        lexicon_lines.append("")
        lexicon_lines.append("## Mind's Eye Theatre Terms")
        lexicon_lines.append("")
        for entry in met_entries:
            lexicon_lines.append(entry)
            lexicon_lines.append("")
    
    # Write lexicon file
    print("Writing lexicon file...")
    with open(LEXICON_FILE, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lexicon_lines))
    
    # Create lexicon chunks
    print("Creating lexicon chunks...")
    lexicon_content = '\n'.join(lexicon_lines)
    lexicon_chunks = create_lexicon_chunks(lexicon_content)
    
    print(f"  Lexicon entries kept: {len(lexicon_entries) + len(met_entries)}")
    
    # Read existing rules and fiction chunks (preserve them)
    print("Reading existing rules and fiction chunks...")
    rules_chunks, fiction_chunks = read_existing_chunks()
    
    # Combine all chunks
    all_chunks = rules_chunks + lexicon_chunks + fiction_chunks
    
    # Write chunks JSONL
    print("Writing chunks JSONL...")
    with open(CHUNKS_FILE, 'w', encoding='utf-8') as f:
        for chunk in all_chunks:
            f.write(json.dumps(chunk, ensure_ascii=False) + '\n')
    
    # Update manifest
    print("Updating manifest...")
    manifest = {
        "corpus_name": "LotNR RAG Corpus",
        "created_from": "LotNR-formatted.md",
        "documents": [
            {
                "doc": "lotnr_rules",
                "path": "reference\\Books\\LofNR\\lotnr_rules.md",
                "chunk_count": len(rules_chunks),
                "notes": "Rules + setting text; excludes fiction and lexicon."
            },
            {
                "doc": "lotnr_lexicon",
                "path": "reference\\Books\\LofNR\\lotnr_lexicon.md",
                "chunk_count": len(lexicon_chunks),
                "notes": "Lexicon + Mind's Eye Theatre terms; one chunk per entry."
            },
            {
                "doc": "lotnr_fiction",
                "path": "reference\\Books\\LofNR\\lotnr_fiction.md",
                "chunk_count": len(fiction_chunks),
                "notes": "Fiction segments; primarily 'Rendezvous: A Cautionary Tale'."
            }
        ],
        "total_chunks": len(all_chunks)
    }
    
    with open(MANIFEST_FILE, 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    
    print(f"\nComplete!")
    print(f"  Rules chunks: {len(rules_chunks)}")
    print(f"  Lexicon chunks: {len(lexicon_chunks)}")
    print(f"  Fiction chunks: {len(fiction_chunks)}")
    print(f"  Total chunks: {len(all_chunks)}")


if __name__ == "__main__":
    main()
