"""
Search your Vampire rulebooks using semantic search
"""

import os
import openai
from supabase import create_client

# Credentials from environment (e.g. .env: SUPABASE_URL, SUPABASE_KEY, OPENAI_API_KEY)
SUPABASE_URL = os.environ.get("SUPABASE_URL", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")
OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY", "")  # Only needed if generating new embeddings

def search_rules(supabase, query_text, limit=5, threshold=0.7):
    """
    Search rulebooks using the pre-existing embeddings in your JSON files
    
    Note: This assumes you're using the embeddings already in your JSON files.
    If you want to search with NEW queries, you'll need to generate query embeddings.
    """
    
    print(f"\nSearching for: '{query_text}'")
    print("=" * 60)
    
    # For now, let's just do a simple text search
    # To do proper semantic search, we'd need to:
    # 1. Generate embedding for query_text
    # 2. Use pgvector to find similar embeddings
    
    # Simple text search version:
    result = supabase.table('lore_embeddings')\
        .select('*')\
        .ilike('content_text', f'%{query_text}%')\
        .limit(limit)\
        .execute()
    
    if not result.data:
        print("No results found.")
        return []
    
    print(f"\nFound {len(result.data)} results:\n")
    
    for i, item in enumerate(result.data, 1):
        print(f"{i}. [{item['source']}]")
        print(f"   {item['content_text'][:200]}...")
        if item.get('metadata'):
            meta = item['metadata']
            if isinstance(meta, dict):
                pages = f"Pages {meta.get('page_start', '?')}-{meta.get('page_end', '?')}"
                print(f"   {pages}")
        print()
    
    return result.data

def semantic_search_rules(supabase, query_text, limit=5, threshold=0.7):
    """
    Proper semantic search using embeddings
    Requires OpenAI API key to generate query embedding
    """
    
    # Generate embedding for the query
    openai.api_key = OPENAI_API_KEY
    
    response = openai.embeddings.create(
        input=query_text,
        model="text-embedding-ada-002"
    )
    query_embedding = response.data[0].embedding
    
    # Use the match_lore_embeddings function we created
    result = supabase.rpc('match_lore_embeddings', {
        'query_embedding': query_embedding,
        'match_threshold': threshold,
        'match_count': limit
    }).execute()
    
    print(f"\nSemantic search for: '{query_text}'")
    print("=" * 60)
    
    if not result.data:
        print("No results found.")
        return []
    
    print(f"\nFound {len(result.data)} results:\n")
    
    for i, item in enumerate(result.data, 1):
        print(f"{i}. [{item['source']}] (Similarity: {item['similarity']:.2f})")
        print(f"   {item['content_text'][:200]}...")
        print()
    
    return result.data

def browse_all_content(supabase):
    """Browse all imported content"""
    
    result = supabase.table('lore_embeddings')\
        .select('source, title')\
        .execute()
    
    print("\nAll imported content:")
    print("=" * 60)
    
    sources = {}
    for item in result.data:
        source = item['source']
        if source not in sources:
            sources[source] = 0
        sources[source] += 1
    
    for source, count in sources.items():
        print(f"  {source}: {count} chunks")
    
    print(f"\nTotal: {len(result.data)} chunks across {len(sources)} sources")

def main():
    print("=" * 60)
    print("Vampire Rulebook Search")
    print("=" * 60)
    
    # Connect
    supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
    
    # Browse content
    browse_all_content(supabase)
    
    print("\n" + "=" * 60)
    print("Search Examples:")
    print("=" * 60)
    
    # Example searches (simple text search)
    search_rules(supabase, "Samedi", limit=3)
    
    # To use semantic search, uncomment this and add your OpenAI key:
    # semantic_search_rules(supabase, "What are the weaknesses of the Samedi bloodline?", limit=3)

if __name__ == "__main__":
    main()
