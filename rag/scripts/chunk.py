#!/usr/bin/env python3
"""
Chunk markdown files into retrieval-optimized segments.
"""
import json
import re
import hashlib
from pathlib import Path
from typing import List, Dict, Any, Tuple, Optional
from dataclasses import dataclass

REPO_ROOT = Path(__file__).parent.parent.parent
SOURCE_DIR = REPO_ROOT / "reference" / "Books_md_ready_fixed_cleaned"
RAG_DIR = REPO_ROOT / "rag"


def estimate_tokens(text: str) -> int:
    """Estimate token count (rough: ~4 chars per token)."""
    return len(text) // 4


def create_slug(text: str) -> str:
    """Create a URL-friendly slug from text."""
    # Convert to lowercase
    slug = text.lower()
    # Replace spaces and special chars with hyphens
    slug = re.sub(r'[^\w\s-]', '', slug)
    slug = re.sub(r'[-\s]+', '-', slug)
    # Remove leading/trailing hyphens
    slug = slug.strip('-')
    return slug or "untitled"


def generate_chunk_id(book_id: str, heading_path: List[str], start_line: int, end_line: int) -> str:
    """Generate deterministic chunk ID."""
    path_str = "|".join(heading_path)
    content_str = f"{path_str}|{start_line}|{end_line}"
    hash_obj = hashlib.md5(content_str.encode('utf-8'))
    hash_hex = hash_obj.hexdigest()[:8]
    return f"{book_id}_{hash_hex}_{start_line}"


@dataclass
class Heading:
    """Represents a markdown heading."""
    level: int
    text: str
    line_num: int


def parse_headings(content: str) -> List[Heading]:
    """Parse all headings from markdown content."""
    headings = []
    lines = content.split('\n')
    
    for i, line in enumerate(lines, start=1):
        # Match markdown headings: # Heading, ## Heading, etc.
        match = re.match(r'^(#{1,6})\s+(.+)$', line)
        if match:
            level = len(match.group(1))
            text = match.group(2).strip()
            headings.append(Heading(level=level, text=text, line_num=i))
    
    return headings


def get_heading_path(headings: List[Heading], current_line: int) -> List[str]:
    """Get the path of headings leading to the current line."""
    path = []
    for heading in headings:
        if heading.line_num > current_line:
            break
        # If this heading is at a higher or equal level, replace the last item at that level
        while path and len(path) >= heading.level:
            path.pop()
        # Add this heading
        if len(path) < heading.level:
            path.extend([''] * (heading.level - len(path) - 1))
            path.append(heading.text)
        else:
            path[heading.level - 1] = heading.text
    
    return [h for h in path if h]  # Remove empty strings


def clean_content_line(line: str) -> str:
    """Clean a line of content (remove page markers, normalize)."""
    # Remove page markers
    line = re.sub(r'\[Page \d+\]', '', line)
    # Remove HTML page breaks (but keep structure)
    line = re.sub(r'<div style="page-break-after: always;"></div>', '', line)
    return line.strip()


def detect_content_tags(content: str) -> List[str]:
    """Detect content tags based on keywords and patterns."""
    tags = []
    content_lower = content.lower()
    
    # Mechanics/rules
    if any(kw in content_lower for kw in ['trait', 'discipline', 'roll', 'difficulty', 'dice', 'pool', 'cost']):
        tags.append('mechanics')
    
    # Faction
    if any(kw in content_lower for kw in ['camarilla', 'sabbat', 'anarch', 'clan', 'sect']):
        tags.append('faction')
    
    # Fiction/narrative
    if any(kw in content_lower for kw in ['he said', 'she said', 'character', 'dialogue']):
        tags.append('fiction')
    
    # Reference
    if any(kw in content_lower for kw in ['table', 'chart', 'summary', 'reference']):
        tags.append('reference')
    
    # Default
    if not tags:
        tags.append('lore')
    
    return tags


def detect_quality_flags(content: str, token_count: int) -> List[str]:
    """Detect quality issues in chunk."""
    flags = []
    
    # OCR noise detection (spacing issues)
    if re.search(r'\b\w+\s+\w+\s+\w+\b.*\b\w+\s+\w+\s+\w+\b', content):
        # Check for suspicious spacing patterns
        if re.search(r'\b\w{1,3}\s+\w{1,3}\b', content):
            flags.append('ocr_noise')
    
    # Size flags
    if token_count > 1500:
        flags.append('oversized_chunk')
    elif token_count < 50:
        flags.append('undersized_chunk')
    
    # Missing context (very short chunks)
    if len(content.split()) < 20:
        flags.append('missing_context')
    
    return flags


def chunk_file(filepath: Path, book_metadata: Dict[str, Any]) -> List[Dict[str, Any]]:
    """Chunk a single markdown file."""
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        lines = content.split('\n')
    
    book_id = book_metadata['book_id']
    system = book_metadata.get('system', 'Unknown')
    
    # Parse headings
    headings = parse_headings(content)
    
    chunks = []
    current_chunk_lines = []
    current_chunk_start = 1
    current_heading_path = []
    last_heading = None
    
    TARGET_MIN_TOKENS = 300
    TARGET_MAX_TOKENS = 900
    HARD_MAX_TOKENS = 1500
    
    i = 0
    while i < len(lines):
        line = lines[i]
        line_num = i + 1
        
        # Check if this line is a heading
        heading_match = re.match(r'^(#{1,3})\s+(.+)$', line)
        
        if heading_match:
            # We've hit a heading - decide whether to finalize current chunk
            heading_level = len(heading_match.group(1))
            heading_text = heading_match.group(2).strip()
            
            # Get current heading path
            current_heading_path = get_heading_path(headings, line_num)
            
            # If we have content in current chunk, check if we should finalize
            if current_chunk_lines:
                chunk_text = '\n'.join(current_chunk_lines)
                token_count = estimate_tokens(chunk_text)
                
                # Finalize chunk if:
                # 1. We have enough content (above minimum)
                # 2. OR we hit a top-level heading (H1) and have any content
                # 3. OR we're at hard max
                should_finalize = False
                if token_count >= TARGET_MIN_TOKENS:
                    should_finalize = True
                elif heading_level == 1 and token_count > 50:
                    should_finalize = True
                elif token_count >= HARD_MAX_TOKENS:
                    should_finalize = True
                
                if should_finalize:
                    # Create chunk from current content
                    chunk_text = '\n'.join(current_chunk_lines)
                    chunk_heading_path = get_heading_path(headings, current_chunk_start)
                    chunk_title = chunk_heading_path[-1] if chunk_heading_path else "Introduction"
                    
                    chunk_anchor = create_slug(chunk_title)
                    chunk_id = generate_chunk_id(book_id, chunk_heading_path, current_chunk_start, line_num - 1)
                    
                    token_count = estimate_tokens(chunk_text)
                    tags = detect_content_tags(chunk_text)
                    quality_flags = detect_quality_flags(chunk_text, token_count)
                    
                    chunk = {
                        'chunk_id': chunk_id,
                        'source_path': str(filepath.relative_to(REPO_ROOT)),
                        'source_book': book_id,
                        'title': chunk_title,
                        'heading_path': chunk_heading_path,
                        'anchor': chunk_anchor,
                        'start_line': current_chunk_start,
                        'end_line': line_num - 1,
                        'system': system,
                        'tags': tags,
                        'canonical_terms': [],  # Will be populated later
                        'aliases_matched': [],  # Will be populated later
                        'quality_flags': quality_flags,
                        'token_count_estimate': token_count,
                        'content': chunk_text.strip(),
                    }
                    chunks.append(chunk)
                    
                    # Reset for new chunk
                    current_chunk_lines = []
            
            # Add heading to new chunk (if not starting fresh)
            if not current_chunk_lines:
                current_chunk_start = line_num
                last_heading = (heading_level, heading_text)
            
            # Add heading line to chunk
            cleaned_line = clean_content_line(line)
            if cleaned_line:
                current_chunk_lines.append(cleaned_line)
        
        else:
            # Regular content line
            cleaned_line = clean_content_line(line)
            if cleaned_line:
                current_chunk_lines.append(cleaned_line)
            
            # Check if current chunk is getting too large
            if current_chunk_lines:
                chunk_text = '\n'.join(current_chunk_lines)
                token_count = estimate_tokens(chunk_text)
                
                # If we're way over max and hit a natural break point (empty line), split
                if token_count > TARGET_MAX_TOKENS * 1.5:
                    # Look for a good split point (empty line, list end, etc.)
                    # For now, just continue - we'll split at next heading
                    pass
        
        i += 1
    
    # Finalize last chunk if it exists
    if current_chunk_lines:
        chunk_text = '\n'.join(current_chunk_lines)
        chunk_heading_path = get_heading_path(headings, current_chunk_start)
        chunk_title = chunk_heading_path[-1] if chunk_heading_path else "Conclusion"
        
        chunk_anchor = create_slug(chunk_title)
        chunk_id = generate_chunk_id(book_id, chunk_heading_path, current_chunk_start, len(lines))
        
        token_count = estimate_tokens(chunk_text)
        tags = detect_content_tags(chunk_text)
        quality_flags = detect_quality_flags(chunk_text, token_count)
        
        chunk = {
            'chunk_id': chunk_id,
            'source_path': str(filepath.relative_to(REPO_ROOT)),
            'source_book': book_id,
            'title': chunk_title,
            'heading_path': chunk_heading_path,
            'anchor': chunk_anchor,
            'start_line': current_chunk_start,
            'end_line': len(lines),
            'system': system,
            'tags': tags,
            'canonical_terms': [],
            'aliases_matched': [],
            'quality_flags': quality_flags,
            'token_count_estimate': token_count,
            'content': chunk_text.strip(),
        }
        chunks.append(chunk)
    
    # Handle anchor collisions (same anchor within same book)
    anchor_counts = {}
    for chunk in chunks:
        anchor = chunk['anchor']
        if anchor in anchor_counts:
            anchor_counts[anchor] += 1
            chunk['anchor'] = f"{anchor}-{anchor_counts[anchor]}"
        else:
            anchor_counts[anchor] = 1
    
    return chunks


def main():
    """Chunk all books and create chunks.jsonl."""
    print("Starting chunking process...")
    
    # Load book index
    book_index_file = RAG_DIR / "index" / "book_index.json"
    with open(book_index_file, 'r', encoding='utf-8') as f:
        book_index = json.load(f)
    
    # Create book lookup
    book_lookup = {book['book_id']: book for book in book_index}
    
    # Create chunks directory
    chunks_dir = RAG_DIR / "derived" / "chunks"
    chunks_dir.mkdir(parents=True, exist_ok=True)
    
    # Chunk each book
    all_chunks = []
    for book_metadata in book_index:
        filename = Path(book_metadata['filename']).name
        filepath = SOURCE_DIR / filename
        
        if not filepath.exists():
            print(f"Warning: File not found: {filepath}")
            continue
        
        print(f"Chunking {filename}...")
        chunks = chunk_file(filepath, book_metadata)
        all_chunks.extend(chunks)
        print(f"  Created {len(chunks)} chunks")
    
    # Write to JSONL
    chunks_file = chunks_dir / "chunks.jsonl"
    with open(chunks_file, 'w', encoding='utf-8') as f:
        for chunk in all_chunks:
            f.write(json.dumps(chunk, ensure_ascii=False) + '\n')
    
    print(f"\nChunking complete. Created {len(all_chunks)} total chunks.")
    print(f"Output: {chunks_file}")


if __name__ == "__main__":
    main()
