"""
Import RAG JSON files into Supabase lore_embeddings table
"""

import json
import os
from pathlib import Path
from supabase import create_client

# Supabase credentials from environment (e.g. .env: SUPABASE_URL, SUPABASE_KEY)
SUPABASE_URL = os.environ.get("SUPABASE_URL", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")

# Path to your JSON files
JSON_FOLDER = "agents/laws_agent/Books"  # UPDATE THIS

def import_rag_file(supabase, json_path):
    """Import a single RAG JSON file"""
    
    print(f"Processing {json_path.name}...")
    
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Extract book info from metadata
    book_title = data['metadata'].get('title', json_path.stem)
    source = data['metadata'].get('source', json_path.name)
    
    chunks_imported = 0
    
    # Import each chunk
    for chunk in data['chunks']:
        try:
            # Prepare record for lore_embeddings table
            record = {
                'title': f"{book_title} - Chunk {chunk['chunk_id']}",
                'category': 'Rules',  # or 'Lore', 'Bloodlines', etc.
                'content_text': chunk['chunk_text'],
                'embedding': chunk['embedding'],
                'source': source,
                'metadata': {
                    'chunk_id': chunk['chunk_id'],
                    'page_start': chunk['metadata'].get('page_start'),
                    'page_end': chunk['metadata'].get('page_end'),
                    'book_title': book_title,
                    'section': chunk['metadata'].get('section', '')
                }
            }
            
            # Insert into Supabase
            supabase.table('lore_embeddings').insert(record).execute()
            chunks_imported += 1
            
        except Exception as e:
            print(f"  Error importing chunk {chunk['chunk_id']}: {e}")
    
    print(f"  ✓ Imported {chunks_imported} chunks from {book_title}")
    return chunks_imported

def main():
    print("=" * 60)
    print("RAG Data Import to Supabase")
    print("=" * 60)
    print()
    
    # Connect to Supabase
    print("Connecting to Supabase...")
    supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
    print("✓ Connected")
    print()
    
    # Find all JSON files
    json_folder = Path(JSON_FOLDER)
    json_files = list(json_folder.glob("*.json"))
    
    if not json_files:
        print(f"✗ No JSON files found in {json_folder}")
        print("Please update JSON_FOLDER path in the script")
        return
    
    print(f"Found {len(json_files)} JSON files")
    print()
    
    # Import each file
    total_chunks = 0
    for json_file in json_files:
        chunks = import_rag_file(supabase, json_file)
        total_chunks += chunks
    
    print()
    print("=" * 60)
    print(f"✓ Import Complete!")
    print(f"  Files processed: {len(json_files)}")
    print(f"  Total chunks imported: {total_chunks}")
    print("=" * 60)
    print()
    print("You can now search your rulebooks using semantic search!")

if __name__ == "__main__":
    main()
