"""
Import RAG JSON files into Supabase lore_embeddings table
"""

import os
from supabase import create_client

SUPABASE_URL = os.environ.get("SUPABASE_URL", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")


def main():
    try:
        supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
        supabase.table("lore_embeddings").select("*").limit(1).execute()
        print("Connected")
    except Exception as e:
        print(e)


if __name__ == "__main__":
    main()
