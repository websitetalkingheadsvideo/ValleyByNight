"""
Compare Books/*.json chunk counts vs Supabase lore_embeddings.
Reports per-file: JSON count, DB count, status (ok / missing / extra).
"""

import json
import os
from pathlib import Path

from supabase import create_client

SUPABASE_URL = os.environ.get("SUPABASE_URL", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")

SCRIPT_DIR = Path(__file__).resolve().parent
BOOKS_DIR = SCRIPT_DIR / "Books"
PAGE_SIZE = 1000


def title_prefix_from_stem(stem: str) -> str:
    """Match importer logic: stem → book title prefix for 'title like prefix%'."""
    s = stem.replace("_rag", "").replace("_", " ").title()
    return s + " - "


def count_json_chunks(path: Path) -> int:
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)
    return len(data) if isinstance(data, list) else 0


def count_db_for_prefix(supabase, prefix: str) -> int:
    n = 0
    offset = 0
    while True:
        r = (
            supabase.table("lore_embeddings")
            .select("id")
            .ilike("title", prefix + "%")
            .range(offset, offset + PAGE_SIZE - 1)
            .execute()
        )
        rows = r.data or []
        n += len(rows)
        if len(rows) < PAGE_SIZE:
            break
        offset += PAGE_SIZE
    return n


def main():
    supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
    json_files = sorted(BOOKS_DIR.glob("*.json"))
    if not json_files:
        print(f"No JSON files in {BOOKS_DIR}")
        return

    print("File                          | JSON  | DB    | Status")
    print("-" * 60)
    total_json = 0
    total_db = 0
    missing = []

    for path in json_files:
        stem = path.stem
        prefix = title_prefix_from_stem(stem)
        json_count = count_json_chunks(path)
        db_count = count_db_for_prefix(supabase, prefix)
        total_json += json_count
        total_db += db_count

        if db_count < json_count:
            status = f"MISSING {json_count - db_count}"
            missing.append((path.name, json_count, db_count))
        elif db_count > json_count:
            status = f"EXTRA {db_count - json_count}"
        else:
            status = "ok"

        print(f"{path.name:30} | {json_count:5} | {db_count:5} | {status}")

    print("-" * 60)
    print(f"{'TOTAL':30} | {total_json:5} | {total_db:5}")

    if missing:
        print("\nMissing content (import these):")
        for name, want, have in missing:
            print(f"  {name}: {want - have} chunks missing (have {have}, want {want})")
    else:
        print("\nAll JSON content is present in the database.")


if __name__ == "__main__":
    main()
