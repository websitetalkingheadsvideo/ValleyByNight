"""
Delete duplicate rows from Supabase lore_embeddings.
Keeps one row per (title, content_text); deletes the rest.
"""

import os
from supabase import create_client

SUPABASE_URL = os.environ.get("SUPABASE_URL", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")

PAGE_SIZE = 1000
DELETE_BATCH = 200


def main():
    supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
    seen = {}
    duplicate_ids = []
    offset = 0

    print("Fetching rows to find duplicates...")
    while True:
        result = (
            supabase.table("lore_embeddings")
            .select("id, title, content_text")
            .range(offset, offset + PAGE_SIZE - 1)
            .execute()
        )
        rows = result.data or []
        if not rows:
            break
        for r in rows:
            key = (r.get("title"), r.get("content_text"))
            if key in seen:
                duplicate_ids.append(r["id"])
            else:
                seen[key] = r["id"]
        offset += PAGE_SIZE
        print(f"  ... {offset} rows scanned")
        if len(rows) < PAGE_SIZE:
            break

    n_dup = len(duplicate_ids)
    if n_dup == 0:
        print("No duplicates found.")
        return
    print(f"Found {n_dup} duplicate row(s). Deleting...")

    deleted = 0
    for i in range(0, n_dup, DELETE_BATCH):
        batch = duplicate_ids[i : i + DELETE_BATCH]
        supabase.table("lore_embeddings").delete().in_("id", batch).execute()
        deleted += len(batch)
        print(f"  Deleted {deleted}/{n_dup}")
    print(f"Done. Removed {n_dup} duplicate(s).")


if __name__ == "__main__":
    main()
