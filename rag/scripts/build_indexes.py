#!/usr/bin/env python3
"""
Build concept index and update book index with statistics.
"""
import json
import yaml
from pathlib import Path
from typing import Dict, List, Any, Set
from collections import defaultdict

REPO_ROOT = Path(__file__).parent.parent.parent
RAG_DIR = REPO_ROOT / "rag"


def extract_context(content: str, term: str, context_size: int = 50) -> str:
    """Extract context around a term match."""
    content_lower = content.lower()
    term_lower = term.lower()
    
    # Find first occurrence
    index = content_lower.find(term_lower)
    if index == -1:
        return ""
    
    # Extract context
    start = max(0, index - context_size)
    end = min(len(content), index + len(term) + context_size)
    
    context = content[start:end]
    # Clean up context
    context = context.strip()
    if start > 0:
        context = "..." + context
    if end < len(content):
        context = context + "..."
    
    return context


def build_concept_index(chunks_file: Path, glossary_file: Path) -> Dict[str, Any]:
    """Build concept index mapping terms to chunk occurrences."""
    print("Building concept index...")
    
    # Load glossary
    with open(glossary_file, 'r', encoding='utf-8') as f:
        glossary_data = yaml.safe_load(f)
    
    # Create canonical lookup
    canonical_lookup = {}
    for entry in glossary_data.get('terms', []):
        canonical = entry['term']
        canonical_lookup[canonical] = entry
    
    # Build index: term -> occurrences
    concept_index = {}
    
    # Load chunks
    chunks = []
    with open(chunks_file, 'r', encoding='utf-8') as f:
        for line in f:
            chunk = json.loads(line)
            chunks.append(chunk)
    
    print(f"Processing {len(chunks)} chunks...")
    
    # Process each chunk
    for chunk in chunks:
        chunk_id = chunk['chunk_id']
        book_id = chunk['source_book']
        anchor = chunk.get('anchor', '')
        content = chunk.get('content', '')
        canonical_terms = chunk.get('canonical_terms', [])
        
        # Add occurrences for each canonical term
        for canonical_term in canonical_terms:
            if canonical_term not in concept_index:
                concept_index[canonical_term] = {
                    'canonical': canonical_term,
                    'occurrences': [],
                }
            
            # Extract context
            context = extract_context(content, canonical_term)
            
            occurrence = {
                'chunk_id': chunk_id,
                'book_id': book_id,
                'anchor': anchor,
                'context': context,
            }
            
            concept_index[canonical_term]['occurrences'].append(occurrence)
    
    print(f"Indexed {len(concept_index)} terms")
    
    # Sort occurrences by chunk_id for consistency
    for term, data in concept_index.items():
        data['occurrences'].sort(key=lambda x: x['chunk_id'])
    
    return concept_index


def update_book_index_with_stats(chunks_file: Path, book_index_file: Path) -> List[Dict[str, Any]]:
    """Update book index with chunk statistics."""
    print("Updating book index with statistics...")
    
    # Load book index
    with open(book_index_file, 'r', encoding='utf-8') as f:
        book_index = json.load(f)
    
    # Create book lookup
    book_lookup = {book['book_id']: book for book in book_index}
    
    # Load chunks
    chunks = []
    with open(chunks_file, 'r', encoding='utf-8') as f:
        for line in f:
            chunk = json.loads(line)
            chunks.append(chunk)
    
    # Calculate statistics per book
    book_stats = defaultdict(lambda: {
        'chunk_count': 0,
        'total_tokens': 0,
        'chunks': [],
        'terms': set(),
        'quality_flags': defaultdict(int),
    })
    
    for chunk in chunks:
        book_id = chunk['source_book']
        stats = book_stats[book_id]
        
        stats['chunk_count'] += 1
        stats['total_tokens'] += chunk.get('token_count_estimate', 0)
        stats['chunks'].append(chunk)
        stats['terms'].update(chunk.get('canonical_terms', []))
        
        for flag in chunk.get('quality_flags', []):
            stats['quality_flags'][flag] += 1
    
    # Update book index
    updated_books = []
    for book in book_index:
        book_id = book['book_id']
        stats = book_stats.get(book_id, {})
        
        # Update book metadata
        book['chunk_count'] = stats.get('chunk_count', 0)
        book['avg_chunk_size'] = (
            stats['total_tokens'] // stats['chunk_count']
            if stats.get('chunk_count', 0) > 0 else 0
        )
        book['terms_count'] = len(stats.get('terms', set()))
        
        # Create quality summary
        quality_summary = dict(stats.get('quality_flags', {}))
        book['quality_summary'] = quality_summary
        
        updated_books.append(book)
    
    return updated_books


def main():
    """Build concept index and update book index."""
    chunks_file = RAG_DIR / "derived" / "chunks" / "chunks.jsonl"
    glossary_file = RAG_DIR / "glossary.yml"
    book_index_file = RAG_DIR / "index" / "book_index.json"
    concept_index_file = RAG_DIR / "index" / "concept_index.json"
    
    # Build concept index
    concept_index = build_concept_index(chunks_file, glossary_file)
    
    # Write concept index
    print(f"Writing concept index to {concept_index_file}...")
    with open(concept_index_file, 'w', encoding='utf-8') as f:
        json.dump(concept_index, f, indent=2, ensure_ascii=False)
    
    print(f"Concept index created with {len(concept_index)} terms")
    
    # Update book index
    updated_book_index = update_book_index_with_stats(chunks_file, book_index_file)
    
    # Write updated book index
    print(f"Writing updated book index to {book_index_file}...")
    with open(book_index_file, 'w', encoding='utf-8') as f:
        json.dump(updated_book_index, f, indent=2, ensure_ascii=False)
    
    print("Index building complete!")


if __name__ == "__main__":
    main()
