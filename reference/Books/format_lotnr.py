#!/usr/bin/env python3
"""
RAG Prep + Indexing Editor for Laws of the Night Revised
Transforms LotNR-formatted.md into RAG-ready corpus with clean documents and chunks.
"""

import re
import json
from pathlib import Path
from typing import List, Dict, Tuple, Optional

# Configuration
SOURCE_FILE = Path("v:/reference/Books/LotNR-formatted.md")
OUTPUT_DIR = Path("v:/reference/Books/LofNR")
OUTPUT_DIR.mkdir(exist_ok=True)

# Output files
RULES_FILE = OUTPUT_DIR / "lotnr_rules.md"
LEXICON_FILE = OUTPUT_DIR / "lotnr_lexicon.md"
FICTION_FILE = OUTPUT_DIR / "lotnr_fiction.md"
CHUNKS_FILE = OUTPUT_DIR / "lotnr_chunks.jsonl"
MANIFEST_FILE = OUTPUT_DIR / "lotnr_manifest.json"


def normalize_text(text: str) -> str:
    """
    Apply minimal normalization fixes:
    A) De-hyphenate line-break splits
    B) Fix common OCR errors
    C) Normalize repeated spaces
    D) Reconstruct paragraphs (conservative - only obvious breaks)
    """
    lines = text.split('\n')
    normalized_lines = []
    
    for line in lines:
        # B) Fix common OCR errors
        line = re.sub(r'\bhe amarilla\b', 'the Camarilla', line, flags=re.IGNORECASE)
        line = re.sub(r'\bhe aBBat\b', 'the Sabbat', line, flags=re.IGNORECASE)
        line = re.sub(r'\bHe Orld Of Arkness\b', 'The World Of Darkness', line, flags=re.IGNORECASE)
        line = re.sub(r'\bhe Orld Of Arkness\b', 'the World Of Darkness', line, flags=re.IGNORECASE)
        
        # C) Normalize repeated spaces within line
        line = re.sub(r' {2,}', ' ', line)
        
        normalized_lines.append(line)
    
    # Join lines and fix hyphenation
    text = '\n'.join(normalized_lines)
    
    # A) Fix hyphenated words split across lines
    # Pattern: word ending with hyphen, newline, then continuation
    text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
    
    # D) Reconstruct paragraphs - conservative approach
    # Only join lines that are clearly broken (no punctuation at end, next line starts lowercase)
    lines = text.split('\n')
    reconstructed = []
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if not line:
            # Blank line - preserve as paragraph break
            reconstructed.append('')
            i += 1
        elif line.startswith('#'):
            # Heading - keep as-is
            reconstructed.append(line)
            i += 1
        elif i + 1 < len(lines):
            next_line = lines[i + 1].strip()
            if not next_line:
                # Next line is blank - keep current line as paragraph end
                reconstructed.append(line)
                i += 1
            elif next_line.startswith('#'):
                # Next line is heading - keep current line as-is
                reconstructed.append(line)
                i += 1
            elif (not re.search(r'[.!?:]$', line) and 
                  next_line and 
                  next_line[0].islower() and
                  not next_line.startswith('(') and
                  not next_line.startswith('"') and
                  not next_line.startswith("'")):
                # Current line doesn't end with punctuation AND next line starts lowercase
                # This is likely a broken paragraph - join them
                paragraph = [line]
                i += 1
                while i < len(lines):
                    next_line = lines[i].strip()
                    if not next_line:
                        break
                    if next_line.startswith('#'):
                        break
                    # Stop if we hit sentence-ending punctuation or a new sentence start
                    if (re.search(r'[.!?:]$', paragraph[-1]) or
                        (next_line and next_line[0].isupper() and not next_line.startswith('('))):
                        break
                    paragraph.append(next_line)
                    i += 1
                reconstructed.append(' '.join(paragraph))
            else:
                # Keep line as-is
                reconstructed.append(line)
                i += 1
        else:
            reconstructed.append(line)
            i += 1
    
    return '\n'.join(reconstructed)


def extract_fiction_section(lines: List[str]) -> Tuple[List[str], List[str]]:
    """Extract fiction section (Rendezvous: A Cautionary Tale) and return remaining lines."""
    fiction_lines = []
    remaining_lines = []
    in_fiction = False
    
    i = 0
    while i < len(lines):
        line = lines[i]
        
        # Check if we're starting fiction
        if re.match(r'^#+\s*Rendezvous:?\s*A\s*Cautionary\s*Tale', line, re.IGNORECASE):
            in_fiction = True
            fiction_lines.append(line)
            i += 1
            continue
        
        # Check if we're ending fiction - stop at CHAPTER ONE or Chapter One
        if in_fiction:
            # Check for chapter heading that marks end of fiction
            if re.match(r'^#+\s*CHAPTER\s+ONE|^#+\s*Chapter\s+one|^#+\s*Chapter\s+One|^#+\s*Chapter\s+1:', line, re.IGNORECASE):
                in_fiction = False
                remaining_lines.append(line)
                i += 1
                continue
            # Also stop if we hit "Mind's Eye Theatre" credits section (before chapter)
            if re.match(r'^Mind\'s\s+Eye\s+Theatre', line, re.IGNORECASE) and i > 100:
                # Check if next significant line is a chapter heading
                j = i + 1
                while j < min(i + 50, len(lines)):
                    if lines[j].strip() and re.match(r'^#+\s*CHAPTER|^#+\s*Chapter', lines[j], re.IGNORECASE):
                        in_fiction = False
                        # Include the Mind's Eye Theatre line in remaining (it's front matter)
                        remaining_lines.append(line)
                        i += 1
                        continue
                    j += 1
            fiction_lines.append(line)
            i += 1
        else:
            remaining_lines.append(line)
            i += 1
    
    return fiction_lines, remaining_lines


def extract_lexicon_section(lines: List[str]) -> Tuple[List[str], List[str]]:
    """Extract lexicon and MET terms sections, format entries, return remaining lines."""
    lexicon_lines = []
    remaining_lines = []
    in_lexicon = False
    lexicon_started = False
    
    i = 0
    while i < len(lines):
        line = lines[i]
        
        # Check if we're starting lexicon
        if re.match(r'^lexiCon|^#+\s*Lexicon', line, re.IGNORECASE):
            in_lexicon = True
            lexicon_started = True
            lexicon_lines.append("# Lexicon")
            lexicon_lines.append("")
            i += 1
            continue
        
        # Check if we're in MET terms section
        if re.match(r'^mind\'s\s+eye\s+theatre\s+terms|^#+\s*Mind\'s\s+Eye\s+Theatre\s+Terms', line, re.IGNORECASE):
            if not lexicon_started:
                lexicon_lines.append("# Lexicon")
                lexicon_lines.append("")
            lexicon_lines.append("")
            lexicon_lines.append("## Mind's Eye Theatre Terms")
            lexicon_lines.append("")
            in_lexicon = True
            i += 1
            continue
        
        if in_lexicon:
            # Check for end of lexicon (next major chapter)
            if re.match(r'^#+\s*CHAPTER\s+TWO|^#+\s*Chapter\s+two|^#+\s*Chapter\s+Two|^#+\s*Elysium', line, re.IGNORECASE):
                in_lexicon = False
                remaining_lines.append(line)
                i += 1
                continue
            
            # Format lexicon entries
            # Pattern: Term — Definition or Term - Definition
            # Handle cases where definition might continue on next line
            if re.match(r'^[A-Z][^—\-]*[—\-]\s+', line) or (line.strip() and ('—' in line or re.match(r'^[A-Z][^—\-]*\s+[—\-]\s*', line))):
                # Format as: **Term** — Definition
                match = re.match(r'^([^—\-]+?)\s*[—\-]\s*(.+)$', line)
                if match:
                    term = match.group(1).strip()
                    definition = match.group(2).strip()
                    
                    # Check if definition continues on next line(s)
                    j = i + 1
                    while j < len(lines) and j < i + 5:  # Check up to 4 more lines for continuation
                        next_line = lines[j].strip()
                        if not next_line:
                            break
                        # If next line doesn't start with capital letter and dash (new entry), it's continuation
                        if not re.match(r'^[A-Z][^—\-]*\s*[—\-]', next_line):
                            definition += ' ' + next_line
                            j += 1
                        else:
                            break
                    i = j - 1  # Skip processed continuation lines
                    
                    # Clean up term and definition
                    term = ' '.join(term.split())
                    definition = ' '.join(definition.split())
                    lexicon_lines.append(f"**{term}** — {definition}")
                    lexicon_lines.append("")
                elif '—' in line or re.search(r'\s+[—\-]\s*', line):
                    # Try alternative pattern
                    parts = re.split(r'\s+[—\-]\s+', line, maxsplit=1)
                    if len(parts) == 2:
                        term = parts[0].strip()
                        definition = parts[1].strip()
                        
                        # Check for continuation
                        j = i + 1
                        while j < len(lines) and j < i + 5:
                            next_line = lines[j].strip()
                            if not next_line:
                                break
                            if not re.match(r'^[A-Z][^—\-]*\s*[—\-]', next_line):
                                definition += ' ' + next_line
                                j += 1
                            else:
                                break
                        i = j - 1
                        
                        if term and definition:
                            term = ' '.join(term.split())
                            definition = ' '.join(definition.split())
                            lexicon_lines.append(f"**{term}** — {definition}")
                            lexicon_lines.append("")
                    else:
                        lexicon_lines.append(line)
            else:
                # Keep other lines as-is (headings, etc.)
                lexicon_lines.append(line)
            i += 1
        else:
            remaining_lines.append(line)
            i += 1
    
    return lexicon_lines, remaining_lines


def normalize_chapter_headings(lines: List[str]) -> List[str]:
    """Normalize chapter headings to consistent format."""
    normalized = []
    chapter_titles = {
        '1': 'Introduction',
        '2': 'The Clans',
        '3': 'Character Creation and Traits',
        '4': 'Disciplines',
        '5': 'Rules, Systems and Drama',
        '6': 'Storytelling',
        '7': 'Allies and Antagonists'
    }
    seen_chapters = set()
    
    for line in lines:
        # Skip table of contents entries (they have page numbers and aren't headings)
        if (not line.strip().startswith('#') and 
            re.search(r'\s+\d{2,3}$', line.strip()) and
            re.search(r'Chapter', line, re.IGNORECASE)):
            # Likely a TOC entry - skip it
            continue
        
        match = re.match(r'^#+\s*(?:CHAPTER\s+)?(?:Chapter\s+)?(one|two|three|four|five|six|seven|1|2|3|4|5|6|7)[:\s]+(.+)$', line, re.IGNORECASE)
        if match:
            num_str = match.group(1).lower()
            title = match.group(2).strip()
            
            # Convert number word to digit
            num_map = {'one': '1', 'two': '2', 'three': '3', 'four': '4', 
                      'five': '5', 'six': '6', 'seven': '7'}
            num = num_map.get(num_str, num_str)
            
            # Skip duplicate chapter headings
            if num in seen_chapters:
                continue
            seen_chapters.add(num)
            
            # Use canonical title if available, otherwise clean up the extracted title
            if num in chapter_titles:
                title = chapter_titles[num]
            else:
                # Clean up title - remove page numbers, extra spaces
                title = re.sub(r'\s+\d+$', '', title)  # Remove trailing page numbers
                title = ' '.join(title.split())  # Normalize spaces
                # Capitalize properly
                title_words = title.split()
                title = ' '.join(word.capitalize() if word.islower() else word for word in title_words)
            
            normalized.append(f"# Chapter {num}: {title}")
        else:
            normalized.append(line)
    return normalized


def create_chunks(doc_type: str, content: str, doc_name: str) -> List[Dict]:
    """Create chunks from document content."""
    chunks = []
    lines = content.split('\n')
    
    if doc_type == "lexicon":
        # One chunk per lexicon entry
        current_entry = None
        entry_text = []
        section_num = 1
        
        for line in lines:
            if line.startswith('**') and '—' in line:
                # Save previous entry if exists
                if current_entry:
                    term_slug = re.sub(r'[^\w\s-]', '', current_entry).lower().replace(' ', '-')
                    chunks.append({
                        "id": f"lotnr_lexicon::term::{term_slug}",
                        "doc": "lotnr_lexicon",
                        "title": current_entry,
                        "breadcrumbs": ["Lexicon"],
                        "chunk_type": "lexicon_entry",
                        "text": '\n'.join(entry_text),
                        "source_file": "LotNR-formatted.md"
                    })
                
                # Start new entry
                match = re.match(r'\*\*([^*]+)\*\*', line)
                if match:
                    current_entry = match.group(1)
                    entry_text = [line]
            elif line.strip() and current_entry:
                entry_text.append(line)
            elif not line.strip() and current_entry and entry_text:
                # Blank line after entry - save it
                if current_entry:
                    term_slug = re.sub(r'[^\w\s-]', '', current_entry).lower().replace(' ', '-')
                    chunks.append({
                        "id": f"lotnr_lexicon::term::{term_slug}",
                        "doc": "lotnr_lexicon",
                        "title": current_entry,
                        "breadcrumbs": ["Lexicon"],
                        "chunk_type": "lexicon_entry",
                        "text": '\n'.join(entry_text),
                        "source_file": "LotNR-formatted.md"
                    })
                    current_entry = None
                    entry_text = []
        
        # Save last entry
        if current_entry and entry_text:
            term_slug = re.sub(r'[^\w\s-]', '', current_entry).lower().replace(' ', '-')
            chunks.append({
                "id": f"lotnr_lexicon::term::{term_slug}",
                "doc": "lotnr_lexicon",
                "title": current_entry,
                "breadcrumbs": ["Lexicon"],
                "chunk_type": "lexicon_entry",
                "text": '\n'.join(entry_text),
                "source_file": "LotNR-formatted.md"
            })
    
    elif doc_type == "fiction":
        # Chunk by ## sections, or by ~900 token breaks
        current_section = None
        section_lines = []
        section_num = 1
        breadcrumbs = []
        
        for line in lines:
            if line.startswith('#'):
                # Save previous section
                if current_section and section_lines:
                    text = '\n'.join(section_lines)
                    # Check if we need to split by token count (~900 tokens ≈ ~675 words)
                    words = len(text.split())
                    if words > 675:
                        # Split into parts
                        paragraphs = text.split('\n\n')
                        part_text = []
                        part_num = 1
                        for para in paragraphs:
                            if len(' '.join(part_text + [para]).split()) > 675:
                                # Save current part
                                if part_text:
                                    chunks.append({
                                        "id": f"lotnr_fiction::sec{section_num:02d}::part{part_num}",
                                        "doc": "lotnr_fiction",
                                        "title": current_section,
                                        "breadcrumbs": breadcrumbs.copy(),
                                        "chunk_type": "paragraph_group",
                                        "text": '\n\n'.join(part_text),
                                        "source_file": "LotNR-formatted.md"
                                    })
                                    part_num += 1
                                    part_text = []
                                part_text.append(para)
                            else:
                                part_text.append(para)
                        # Save last part
                        if part_text:
                            chunks.append({
                                "id": f"lotnr_fiction::sec{section_num:02d}::part{part_num}",
                                "doc": "lotnr_fiction",
                                "title": current_section,
                                "breadcrumbs": breadcrumbs.copy(),
                                "chunk_type": "paragraph_group",
                                "text": '\n\n'.join(part_text),
                                "source_file": "LotNR-formatted.md"
                            })
                    else:
                        chunks.append({
                            "id": f"lotnr_fiction::sec{section_num:02d}",
                            "doc": "lotnr_fiction",
                            "title": current_section,
                            "breadcrumbs": breadcrumbs.copy(),
                            "chunk_type": "section",
                            "text": text,
                            "source_file": "LotNR-formatted.md"
                        })
                    section_num += 1
                
                # Start new section
                if line.startswith('##'):
                    current_section = line.replace('#', '').strip()
                    breadcrumbs = [current_section]
                    section_lines = [line]
                elif line.startswith('#'):
                    current_section = line.replace('#', '').strip()
                    breadcrumbs = [current_section]
                    section_lines = [line]
            else:
                if current_section:
                    section_lines.append(line)
        
        # Save last section
        if current_section and section_lines:
            text = '\n'.join(section_lines)
            words = len(text.split())
            if words > 675:
                # Split into parts
                paragraphs = text.split('\n\n')
                part_text = []
                part_num = 1
                for para in paragraphs:
                    if len(' '.join(part_text + [para]).split()) > 675:
                        if part_text:
                            chunks.append({
                                "id": f"lotnr_fiction::sec{section_num:02d}::part{part_num}",
                                "doc": "lotnr_fiction",
                                "title": current_section,
                                "breadcrumbs": breadcrumbs.copy(),
                                "chunk_type": "paragraph_group",
                                "text": '\n\n'.join(part_text),
                                "source_file": "LotNR-formatted.md"
                            })
                            part_num += 1
                            part_text = []
                        part_text.append(para)
                    else:
                        part_text.append(para)
                if part_text:
                    chunks.append({
                        "id": f"lotnr_fiction::sec{section_num:02d}::part{part_num}",
                        "doc": "lotnr_fiction",
                        "title": current_section,
                        "breadcrumbs": breadcrumbs.copy(),
                        "chunk_type": "paragraph_group",
                        "text": '\n\n'.join(part_text),
                        "source_file": "LotNR-formatted.md"
                    })
            else:
                chunks.append({
                    "id": f"lotnr_fiction::sec{section_num:02d}",
                    "doc": "lotnr_fiction",
                    "title": current_section,
                    "breadcrumbs": breadcrumbs.copy(),
                    "chunk_type": "section",
                    "text": text,
                    "source_file": "LotNR-formatted.md"
                })
    
    else:  # rules
        # Chunk by ## sections, split if >900 tokens
        current_chapter = "0"
        current_section = None
        section_lines = []
        section_num = 1
        breadcrumbs = []
        
        for line in lines:
            # Check for chapter heading
            chapter_match = re.match(r'^#\s+Chapter\s+(\d+):', line)
            if chapter_match:
                current_chapter = chapter_match.group(1)
                breadcrumbs = [line.replace('#', '').strip()]
                if current_section and section_lines:
                    # Save previous section before chapter change
                    text = '\n'.join(section_lines)
                    words = len(text.split())
                    if words > 675:
                        paragraphs = text.split('\n\n')
                        part_text = []
                        part_num = 1
                        for para in paragraphs:
                            if len(' '.join(part_text + [para]).split()) > 675:
                                if part_text:
                                    chunks.append({
                                        "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                                        "doc": "lotnr_rules",
                                        "title": current_section,
                                        "breadcrumbs": breadcrumbs.copy(),
                                        "chunk_type": "paragraph_group",
                                        "text": '\n\n'.join(part_text),
                                        "source_file": "LotNR-formatted.md"
                                    })
                                    part_num += 1
                                    part_text = []
                                part_text.append(para)
                            else:
                                part_text.append(para)
                        if part_text:
                            chunks.append({
                                "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                                "doc": "lotnr_rules",
                                "title": current_section,
                                "breadcrumbs": breadcrumbs.copy(),
                                "chunk_type": "paragraph_group",
                                "text": '\n\n'.join(part_text),
                                "source_file": "LotNR-formatted.md"
                            })
                    else:
                        chunks.append({
                            "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}",
                            "doc": "lotnr_rules",
                            "title": current_section,
                            "breadcrumbs": breadcrumbs.copy(),
                            "chunk_type": "section",
                            "text": text,
                            "source_file": "LotNR-formatted.md"
                        })
                    section_num += 1
                    section_lines = []
                
                current_section = line.replace('#', '').strip()
                section_lines = [line]
                continue
            
            # Check for ## section heading
            if line.startswith('##'):
                # Save previous section
                if current_section and section_lines:
                    text = '\n'.join(section_lines)
                    words = len(text.split())
                    if words > 675:
                        paragraphs = text.split('\n\n')
                        part_text = []
                        part_num = 1
                        for para in paragraphs:
                            if len(' '.join(part_text + [para]).split()) > 675:
                                if part_text:
                                    chunks.append({
                                        "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                                        "doc": "lotnr_rules",
                                        "title": current_section,
                                        "breadcrumbs": breadcrumbs.copy(),
                                        "chunk_type": "paragraph_group",
                                        "text": '\n\n'.join(part_text),
                                        "source_file": "LotNR-formatted.md"
                                    })
                                    part_num += 1
                                    part_text = []
                                part_text.append(para)
                            else:
                                part_text.append(para)
                        if part_text:
                            chunks.append({
                                "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                                "doc": "lotnr_rules",
                                "title": current_section,
                                "breadcrumbs": breadcrumbs.copy(),
                                "chunk_type": "paragraph_group",
                                "text": '\n\n'.join(part_text),
                                "source_file": "LotNR-formatted.md"
                            })
                    else:
                        chunks.append({
                            "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}",
                            "doc": "lotnr_rules",
                            "title": current_section,
                            "breadcrumbs": breadcrumbs.copy(),
                            "chunk_type": "section",
                            "text": text,
                            "source_file": "LotNR-formatted.md"
                        })
                    section_num += 1
                
                # Start new section
                current_section = line.replace('#', '').strip()
                section_breadcrumbs = breadcrumbs + [current_section] if breadcrumbs else [current_section]
                breadcrumbs = section_breadcrumbs
                section_lines = [line]
            else:
                if current_section:
                    section_lines.append(line)
        
        # Save last section
        if current_section and section_lines:
            text = '\n'.join(section_lines)
            words = len(text.split())
            if words > 675:
                paragraphs = text.split('\n\n')
                part_text = []
                part_num = 1
                for para in paragraphs:
                    if len(' '.join(part_text + [para]).split()) > 675:
                        if part_text:
                            chunks.append({
                                "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                                "doc": "lotnr_rules",
                                "title": current_section,
                                "breadcrumbs": breadcrumbs.copy(),
                                "chunk_type": "paragraph_group",
                                "text": '\n\n'.join(part_text),
                                "source_file": "LotNR-formatted.md"
                            })
                            part_num += 1
                            part_text = []
                        part_text.append(para)
                    else:
                        part_text.append(para)
                if part_text:
                    chunks.append({
                        "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}::part{part_num}",
                        "doc": "lotnr_rules",
                        "title": current_section,
                        "breadcrumbs": breadcrumbs.copy(),
                        "chunk_type": "paragraph_group",
                        "text": '\n\n'.join(part_text),
                        "source_file": "LotNR-formatted.md"
                    })
            else:
                chunks.append({
                    "id": f"lotnr_rules::ch{current_chapter}::sec{section_num:02d}",
                    "doc": "lotnr_rules",
                    "title": current_section,
                    "breadcrumbs": breadcrumbs.copy(),
                    "chunk_type": "section",
                    "text": text,
                    "source_file": "LotNR-formatted.md"
                })
    
    return chunks


def main():
    """Main processing function."""
    print(f"Reading source file: {SOURCE_FILE}")
    with open(SOURCE_FILE, 'r', encoding='utf-8') as f:
        content = f.read()
    
    print("Applying normalization...")
    normalized = normalize_text(content)
    
    print("Splitting into documents...")
    lines = normalized.split('\n')
    
    # Extract fiction
    fiction_lines, remaining_lines = extract_fiction_section(lines)
    
    # Extract lexicon
    lexicon_lines, rules_lines = extract_lexicon_section(remaining_lines)
    
    # Remove TOC entries (lines with "Chapter" and ending with 2-3 digit page numbers)
    # These appear before the actual chapter content starts
    rules_lines_cleaned = []
    in_toc = True
    for i, line in enumerate(rules_lines):
        # Stop TOC when we hit the first actual chapter heading (CHAPTER ONE or similar)
        if re.match(r'^#+\s*CHAPTER\s+ONE|^#+\s*Chapter\s+1:', line, re.IGNORECASE):
            in_toc = False
        
        # Skip TOC entries (they have page numbers at the end)
        if in_toc and line.strip() and re.search(r'Chapter', line, re.IGNORECASE):
            # Check if it ends with a page number (2-3 digits)
            if re.search(r'\s+\d{2,3}\s*$', line):
                continue
            # Also skip if it's a malformed chapter heading in TOC format
            if re.match(r'^#\s+Chapter.*\d{2,3}', line):
                continue
        
        rules_lines_cleaned.append(line)
    
    rules_lines = rules_lines_cleaned
    rules_lines = normalize_chapter_headings(rules_lines)
    
    # Remove any remaining TOC-style entries (they have page numbers or are malformed)
    final_rules = []
    for line in rules_lines:
        # Skip lines that are clearly TOC entries (have page numbers at end)
        if line.strip().startswith('#') and re.search(r'Chapter', line, re.IGNORECASE):
            # Check if it ends with just digits (page number)
            if re.search(r'\s+\d{2,3}\s*$', line) and not re.match(r'^#\s+Chapter\s+\d+:\s+[A-Z][^0-9]*$', line):
                continue
            # Skip malformed entries like "ChapterFive:rules,systemsand drama 186"
            if re.search(r'Chapter[^:]*:.*\d{2,3}', line) and not re.match(r'^#\s+Chapter\s+\d+:\s+[A-Z]', line):
                continue
        final_rules.append(line)
    rules_lines = final_rules
    
    # Write documents
    print("Writing documents...")
    with open(FICTION_FILE, 'w', encoding='utf-8') as f:
        f.write('\n'.join(fiction_lines))
    
    with open(LEXICON_FILE, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lexicon_lines))
    
    with open(RULES_FILE, 'w', encoding='utf-8') as f:
        f.write('\n'.join(rules_lines))
    
    # Create chunks
    print("Creating chunks...")
    all_chunks = []
    
    fiction_content = '\n'.join(fiction_lines)
    fiction_chunks = create_chunks("fiction", fiction_content, "lotnr_fiction")
    all_chunks.extend(fiction_chunks)
    
    lexicon_content = '\n'.join(lexicon_lines)
    lexicon_chunks = create_chunks("lexicon", lexicon_content, "lotnr_lexicon")
    all_chunks.extend(lexicon_chunks)
    
    rules_content = '\n'.join(rules_lines)
    rules_chunks = create_chunks("rules", rules_content, "lotnr_rules")
    all_chunks.extend(rules_chunks)
    
    # Write chunks JSONL
    print("Writing chunks JSONL...")
    with open(CHUNKS_FILE, 'w', encoding='utf-8') as f:
        for chunk in all_chunks:
            f.write(json.dumps(chunk, ensure_ascii=False) + '\n')
    
    # Count chunks by document
    rules_count = len([c for c in all_chunks if c['doc'] == 'lotnr_rules'])
    lexicon_count = len([c for c in all_chunks if c['doc'] == 'lotnr_lexicon'])
    fiction_count = len([c for c in all_chunks if c['doc'] == 'lotnr_fiction'])
    
    # Create manifest
    manifest = {
        "corpus_name": "LotNR RAG Corpus",
        "created_from": "LotNR-formatted.md",
        "documents": [
            {
                "doc": "lotnr_rules",
                "path": str(RULES_FILE).replace('\\', '/'),
                "chunk_count": rules_count,
                "notes": "Rules + setting text; excludes fiction and lexicon."
            },
            {
                "doc": "lotnr_lexicon",
                "path": str(LEXICON_FILE).replace('\\', '/'),
                "chunk_count": lexicon_count,
                "notes": "Lexicon + Mind's Eye Theatre terms; one chunk per entry."
            },
            {
                "doc": "lotnr_fiction",
                "path": str(FICTION_FILE).replace('\\', '/'),
                "chunk_count": fiction_count,
                "notes": "Fiction segments; primarily 'Rendezvous: A Cautionary Tale'."
            }
        ],
        "total_chunks": len(all_chunks)
    }
    
    print("Writing manifest...")
    with open(MANIFEST_FILE, 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    
    print(f"\nComplete!")
    print(f"  Rules: {rules_count} chunks")
    print(f"  Lexicon: {lexicon_count} chunks")
    print(f"  Fiction: {fiction_count} chunks")
    print(f"  Total: {len(all_chunks)} chunks")


if __name__ == "__main__":
    main()
