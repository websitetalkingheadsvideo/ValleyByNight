# PDF to RAG Conversion System — Specification

**Purpose:** Convert text-extractable PDFs into RAG-ready JSON for Laws Agent import.  
**Scope:** Text PDFs only. OCR for image-based PDFs is out of scope (separate workflow).

---

## Output Schema (Laws Agent RAG JSON)

Each document in the output JSON array must match this structure:

```json
{
  "id": "doc_0",
  "page": 1,
  "chunk_index": 0,
  "total_chunks": 1,
  "content": "Page text content...",
  "content_type": "introduction",
  "metadata": {
    "source": "Clanbook: Ventrue (Revised Edition)",
    "page_number": 1,
    "section_title": null,
    "is_chunked": false,
    "chunk_position": "1/1",
    "book_code": "MET-CB-VENTRUE-REV"
  }
}
```

**Template:** `V:\agents\laws_agent\Books\clanbook_ventrue_rag.json`

**Chunking:** One document per page. `chunk_index: 0`, `total_chunks: 1`, `is_chunked: false` always.

---

## Pipeline Steps

| Step | Script | Input | Output |
|------|--------|-------|--------|
| 1 | extract_pdf_with_markers.py | PDF | `{slug}_raw.txt` |
| 2 | inspect_artifacts.py | _raw.txt | `{slug}_artifact_report.txt` |
| 3 | clean_artifacts_and_rejoin.py | _raw.txt | `{slug}_final.txt` |
| 4 | convert_to_rag_json.py | _final.txt | `{slug}_rag.json` |
| 5 | post_process_rag_json.py | _rag.json | _rag.json (in-place) |

**Output locations:** `{slug}_rag.json` → `V:\agents\laws_agent\Books\`. `{slug}_raw.txt`, `{slug}_artifact_report.txt`, `{slug}_final.txt` → `V:\agents\laws_agent\Books\backups\`.

**Post-processing (Step 5):** Applies (in order) `fix_ocr_artifacts`, `fix_spelling_and_caps`, and `fix_spelling_dict` from `V:\agents\laws_agent\Books\` to `content` and `metadata.section_title`. The spell-check pass uses standard English + `V:\reference\wod_vocabulary.md`; fixes fused words (e.g. hairor→hair or) and single-word misspellings (edit distance 1).

---

## Per-Book Config

**Location:** `V:\reference\Books\converter\config\{slug}.json`

```json
{
  "source_title": "Full Book Title (Edition)",
  "book_code": "ABC-MET",
  "page_ranges": [
    { "start": 1, "end": 10, "content_type": "introduction" },
    { "start": 11, "end": 60, "content_type": "character_creation" },
    { "start": 61, "end": 9999, "content_type": "rules" }
  ]
}
```

- `source_title`: Used in `metadata.source`
- `book_code`: Used in `metadata.book_code` (required for import)
- `page_ranges`: From TOC. First matching range wins.

---

## Content Types

Common values: `introduction`, `character_creation`, `storytelling`, `discipline_info`, `rules`, `clan_info`, `general`, `appendix`, `ghoul_info`, `ghoul_types`.

When no config or page_ranges: keyword-based classification applies.

---

## Import

```powershell
php V:\agents\laws_agent\import_rag_data.php "V:\agents\laws_agent\Books\book_rag.json"
# Or batch:
php V:\agents\laws_agent\import_rag_data.php V:\agents\laws_agent\Books
```

Importer expects: `id`, `page`, `chunk_index`, `total_chunks`, `content`, `content_type`, `metadata`.

---

## File Layout

| Path | Purpose |
|------|---------|
| `V:\reference\Books\**\*.pdf` | Input PDFs |
| `V:\reference\Books\converter\` | Scripts |
| `V:\reference\Books\converter\config\*.json` | Per-book config |
| `V:\reference\Books\converter\learned_patterns.txt` | Shared artifact regexes |
| `V:\agents\laws_agent\Books\` | Output (_rag.json only) |
| `V:\agents\laws_agent\Books\backups\` | Intermediate files (_raw, _artifact_report, _final) |

---

## Quick Start

```powershell
cd V:\reference\Books\converter
python run_pipeline.py --list
python run_pipeline.py --pdf "V:\reference\Books\path\to\book.pdf"
```
