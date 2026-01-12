#!/usr/bin/env python3
"""
Validate chunks and glossary against schemas and business rules.
"""
import json
import yaml
import jsonschema
from pathlib import Path
from typing import Dict, List, Any, Set
from collections import defaultdict

REPO_ROOT = Path(__file__).parent.parent.parent
RAG_DIR = REPO_ROOT / "rag"


def load_schema(schema_file: Path) -> Dict[str, Any]:
    """Load JSON schema."""
    with open(schema_file, 'r', encoding='utf-8') as f:
        return json.load(f)


def validate_chunks(chunks_file: Path, chunk_schema: Dict[str, Any], book_index: List[Dict[str, Any]], glossary_terms: Set[str]) -> Dict[str, Any]:
    """Validate all chunks."""
    print("Validating chunks...")
    
    validation_results = {
        'schema_errors': [],
        'duplicate_chunk_ids': [],
        'duplicate_anchors': defaultdict(list),
        'oversized_chunks': [],
        'undersized_chunks': [],
        'invalid_book_references': [],
        'invalid_glossary_references': [],
        'missing_heading_paths': [],
        'total_chunks': 0,
        'valid_chunks': 0,
    }
    
    # Create book ID lookup
    book_ids = {book['book_id'] for book in book_index}
    
    # Track chunk IDs and anchors
    chunk_ids_seen = set()
    anchors_by_book = defaultdict(set)
    
    # Load chunks
    chunks = []
    with open(chunks_file, 'r', encoding='utf-8') as f:
        for line_num, line in enumerate(f, start=1):
            try:
                chunk = json.loads(line)
                chunks.append(chunk)
                validation_results['total_chunks'] += 1
                
                # Schema validation
                try:
                    jsonschema.validate(instance=chunk, schema=chunk_schema)
                except jsonschema.exceptions.ValidationError as e:
                    validation_results['schema_errors'].append({
                        'chunk_id': chunk.get('chunk_id', 'unknown'),
                        'line': line_num,
                        'error': str(e),
                    })
                    continue
                
                chunk_id = chunk['chunk_id']
                book_id = chunk['source_book']
                anchor = chunk.get('anchor', '')
                heading_path = chunk.get('heading_path', [])
                token_count = chunk.get('token_count_estimate', 0)
                canonical_terms = chunk.get('canonical_terms', [])
                
                # Check for duplicate chunk IDs
                if chunk_id in chunk_ids_seen:
                    validation_results['duplicate_chunk_ids'].append(chunk_id)
                chunk_ids_seen.add(chunk_id)
                
                # Check for duplicate anchors within same book
                anchor_key = (book_id, anchor)
                if anchor in anchors_by_book[book_id]:
                    validation_results['duplicate_anchors'][book_id].append(anchor)
                anchors_by_book[book_id].add(anchor)
                
                # Check for oversized chunks
                if token_count > 1500:
                    validation_results['oversized_chunks'].append({
                        'chunk_id': chunk_id,
                        'token_count': token_count,
                    })
                
                # Check for undersized chunks
                if token_count < 50:
                    validation_results['undersized_chunks'].append({
                        'chunk_id': chunk_id,
                        'token_count': token_count,
                    })
                
                # Check for invalid book references
                if book_id not in book_ids:
                    validation_results['invalid_book_references'].append({
                        'chunk_id': chunk_id,
                        'book_id': book_id,
                    })
                
                # Check for invalid glossary references
                for term in canonical_terms:
                    if term not in glossary_terms:
                        validation_results['invalid_glossary_references'].append({
                            'chunk_id': chunk_id,
                            'term': term,
                        })
                
                # Check for missing heading paths (except intro chunks)
                if not heading_path and 'intro' not in chunk.get('title', '').lower():
                    validation_results['missing_heading_paths'].append({
                        'chunk_id': chunk_id,
                        'title': chunk.get('title', ''),
                    })
                
                validation_results['valid_chunks'] += 1
                
            except json.JSONDecodeError as e:
                validation_results['schema_errors'].append({
                    'line': line_num,
                    'error': f'JSON decode error: {e}',
                })
    
    return validation_results


def validate_glossary(glossary_file: Path, glossary_schema: Dict[str, Any]) -> Dict[str, Any]:
    """Validate glossary."""
    print("Validating glossary...")
    
    validation_results = {
        'schema_errors': [],
        'duplicate_terms': [],
        'empty_aliases': [],
        'total_terms': 0,
        'valid_terms': 0,
    }
    
    with open(glossary_file, 'r', encoding='utf-8') as f:
        glossary_data = yaml.safe_load(f)
    
    # Schema validation
    try:
        jsonschema.validate(instance=glossary_data, schema=glossary_schema)
    except jsonschema.exceptions.ValidationError as e:
        validation_results['schema_errors'].append(str(e))
        return validation_results
    
    terms_seen = set()
    
    for entry in glossary_data.get('terms', []):
        validation_results['total_terms'] += 1
        
        term = entry.get('term')
        aliases = entry.get('aliases', [])
        
        # Check for duplicate terms
        if term in terms_seen:
            validation_results['duplicate_terms'].append(term)
        terms_seen.add(term)
        
        # Check for empty aliases
        if not aliases:
            validation_results['empty_aliases'].append(term)
        
        validation_results['valid_terms'] += 1
    
    return validation_results


def main():
    """Run validation checks."""
    print("Starting validation...")
    
    chunks_file = RAG_DIR / "derived" / "chunks" / "chunks.jsonl"
    glossary_file = RAG_DIR / "glossary.yml"
    chunk_schema_file = RAG_DIR / "schema" / "chunk.schema.json"
    glossary_schema_file = RAG_DIR / "schema" / "glossary.schema.json"
    book_index_file = RAG_DIR / "index" / "book_index.json"
    reports_dir = RAG_DIR / "reports"
    
    # Load schemas
    chunk_schema = load_schema(chunk_schema_file)
    glossary_schema = load_schema(glossary_schema_file)
    
    # Load book index
    with open(book_index_file, 'r', encoding='utf-8') as f:
        book_index = json.load(f)
    
    # Load glossary terms
    with open(glossary_file, 'r', encoding='utf-8') as f:
        glossary_data = yaml.safe_load(f)
    glossary_terms = {entry['term'] for entry in glossary_data.get('terms', [])}
    
    # Validate chunks
    chunk_validation = validate_chunks(chunks_file, chunk_schema, book_index, glossary_terms)
    
    # Validate glossary
    glossary_validation = validate_glossary(glossary_file, glossary_schema)
    
    # Generate report
    report_file = reports_dir / "validation_report.md"
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write("# Validation Report\n\n")
        
        f.write("## Chunk Validation\n\n")
        f.write(f"- **Total chunks**: {chunk_validation['total_chunks']}\n")
        f.write(f"- **Valid chunks**: {chunk_validation['valid_chunks']}\n")
        f.write(f"- **Schema errors**: {len(chunk_validation['schema_errors'])}\n")
        f.write(f"- **Duplicate chunk IDs**: {len(chunk_validation['duplicate_chunk_ids'])}\n")
        f.write(f"- **Duplicate anchors (by book)**: {len(chunk_validation['duplicate_anchors'])}\n")
        f.write(f"- **Oversized chunks (>1500 tokens)**: {len(chunk_validation['oversized_chunks'])}\n")
        f.write(f"- **Undersized chunks (<50 tokens)**: {len(chunk_validation['undersized_chunks'])}\n")
        f.write(f"- **Invalid book references**: {len(chunk_validation['invalid_book_references'])}\n")
        f.write(f"- **Invalid glossary references**: {len(chunk_validation['invalid_glossary_references'])}\n")
        f.write(f"- **Missing heading paths**: {len(chunk_validation['missing_heading_paths'])}\n\n")
        
        if chunk_validation['schema_errors']:
            f.write("### Schema Errors\n\n")
            for error in chunk_validation['schema_errors'][:20]:  # Limit to first 20
                f.write(f"- {error}\n")
            if len(chunk_validation['schema_errors']) > 20:
                f.write(f"- ... and {len(chunk_validation['schema_errors']) - 20} more\n")
            f.write("\n")
        
        if chunk_validation['duplicate_chunk_ids']:
            f.write("### Duplicate Chunk IDs\n\n")
            for chunk_id in chunk_validation['duplicate_chunk_ids'][:20]:
                f.write(f"- `{chunk_id}`\n")
            f.write("\n")
        
        if chunk_validation['oversized_chunks']:
            f.write("### Oversized Chunks\n\n")
            for chunk in chunk_validation['oversized_chunks'][:20]:
                f.write(f"- `{chunk['chunk_id']}`: {chunk['token_count']} tokens\n")
            f.write("\n")
        
        f.write("## Glossary Validation\n\n")
        f.write(f"- **Total terms**: {glossary_validation['total_terms']}\n")
        f.write(f"- **Valid terms**: {glossary_validation['valid_terms']}\n")
        f.write(f"- **Schema errors**: {len(glossary_validation['schema_errors'])}\n")
        f.write(f"- **Duplicate terms**: {len(glossary_validation['duplicate_terms'])}\n")
        f.write(f"- **Empty aliases**: {len(glossary_validation['empty_aliases'])}\n\n")
        
        if glossary_validation['schema_errors']:
            f.write("### Schema Errors\n\n")
            for error in glossary_validation['schema_errors']:
                f.write(f"- {error}\n")
            f.write("\n")
        
        f.write("## Summary\n\n")
        
        total_errors = (
            len(chunk_validation['schema_errors']) +
            len(chunk_validation['duplicate_chunk_ids']) +
            len(chunk_validation['oversized_chunks']) +
            len(glossary_validation['schema_errors']) +
            len(glossary_validation['duplicate_terms'])
        )
        
        if total_errors == 0:
            f.write("✅ **All validation checks passed!**\n")
        else:
            f.write(f"⚠️ **Found {total_errors} validation issues**\n")
    
    print(f"Validation complete. Report written to {report_file}")


if __name__ == "__main__":
    main()
