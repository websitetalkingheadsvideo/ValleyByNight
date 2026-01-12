#!/usr/bin/env python3
"""
Match glossary terms against chunks and populate canonical_terms and aliases_matched.
"""
import json
import yaml
import re
from pathlib import Path
from typing import Dict, List, Set, Any, Tuple

REPO_ROOT = Path(__file__).parent.parent.parent
RAG_DIR = REPO_ROOT / "rag"


def load_glossary() -> Dict[str, Dict[str, Any]]:
    """Load glossary and create lookup maps."""
    glossary_file = RAG_DIR / "glossary.yml"
    
    with open(glossary_file, 'r', encoding='utf-8') as f:
        glossary_data = yaml.safe_load(f)
    
    # Create maps: alias -> canonical term
    alias_to_canonical = {}
    canonical_to_entry = {}
    
    for entry in glossary_data.get('terms', []):
        canonical = entry['term']
        canonical_to_entry[canonical] = entry
        
        # Map all aliases to canonical
        for alias in entry.get('aliases', []):
            # Normalize alias for matching
            normalized_alias = re.escape(alias.lower())
            alias_to_canonical[normalized_alias] = canonical
    
    return {
        'alias_to_canonical': alias_to_canonical,
        'canonical_to_entry': canonical_to_entry,
    }


def find_terms_in_content(content: str, glossary_maps: Dict) -> Tuple[List[str], List[str]]:
    """Find glossary terms in content."""
    content_lower = content.lower()
    canonical_terms_found = set()
    aliases_matched = []
    
    alias_to_canonical = glossary_maps['alias_to_canonical']
    
    # Match each alias (case-insensitive, word boundaries)
    for alias_pattern, canonical in alias_to_canonical.items():
        # Use word boundaries to avoid partial matches
        pattern = r'\b' + alias_pattern + r'\b'
        matches = re.findall(pattern, content_lower, re.IGNORECASE)
        
        if matches:
            canonical_terms_found.add(canonical)
            # Store the actual matched text
            for match in matches:
                if match not in aliases_matched:
                    aliases_matched.append(match)
    
    return sorted(canonical_terms_found), aliases_matched


def main():
    """Match glossary terms against all chunks."""
    print("Loading glossary...")
    glossary_maps = load_glossary()
    print(f"Loaded {len(glossary_maps['canonical_to_entry'])} canonical terms")
    
    # Load chunks
    chunks_file = RAG_DIR / "derived" / "chunks" / "chunks.jsonl"
    print(f"Loading chunks from {chunks_file}...")
    
    chunks = []
    with open(chunks_file, 'r', encoding='utf-8') as f:
        for line in f:
            chunk = json.loads(line)
            chunks.append(chunk)
    
    print(f"Processing {len(chunks)} chunks...")
    
    # Match terms in each chunk
    updated_chunks = []
    for i, chunk in enumerate(chunks):
        if (i + 1) % 500 == 0:
            print(f"  Processed {i + 1}/{len(chunks)} chunks...")
        
        content = chunk.get('content', '')
        canonical_terms, aliases_matched = find_terms_in_content(content, glossary_maps)
        
        chunk['canonical_terms'] = canonical_terms
        chunk['aliases_matched'] = aliases_matched
        
        updated_chunks.append(chunk)
    
    # Write updated chunks
    print(f"Writing updated chunks to {chunks_file}...")
    with open(chunks_file, 'w', encoding='utf-8') as f:
        for chunk in updated_chunks:
            f.write(json.dumps(chunk, ensure_ascii=False) + '\n')
    
    print("Glossary matching complete!")


if __name__ == "__main__":
    main()
