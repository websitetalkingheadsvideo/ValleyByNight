# Book Conversion Runbook: PDF → RAG JSON

**Input:** PDFs under `V:\reference\Books` (any subfolder).  
**Output:** `V:\agents\laws_agent\Books` — `{slug}_rag.json`. Intermediate files in `Books\backups\`: `{slug}_raw.txt`, `{slug}_artifact_report.txt`, `{slug}_final.txt`.

All scripts live in `V:\reference\Books\converter`. Run from repo root or converter dir; use absolute paths on Windows.

---

## Quick start (one book, full pipeline)

```powershell
cd V:\reference\Books\converter

# List PDFs
python run_pipeline.py --list

# Process one PDF (extract → inspect → clean → convert)
python run_pipeline.py --pdf "V:\reference\Books\MET - VTM - Liber des Goules (5006).pdf"
```

Output: `{slug}_rag.json` in `V:\agents\laws_agent\Books`; `{slug}_raw.txt`, `{slug}_artifact_report.txt`, `{slug}_final.txt` in `Books\backups\`. Slug is derived from the PDF filename (e.g. `met_vtm_liber_des_goules_5006`).

---

## Five-step process (manual control)

Use this when you want to run steps separately (e.g. review artifacts, add patterns, then re-run clean/convert).

### Step 1: Extract

```powershell
python extract_pdf_with_markers.py "path\to\new_book.pdf" "V:\agents\laws_agent\Books\backups\new_book_raw.txt"
```

### Step 2: Inspect for artifacts

```powershell
python inspect_artifacts.py "V:\agents\laws_agent\Books\backups\new_book_raw.txt" "V:\agents\laws_agent\Books\backups\new_book_artifact_report.txt"
```

Open the report: each line is `<!-- PAGE N --> >>> first content line`. Artifacts often appear right after page markers (garbled headers, `i i i i`, symbols, short junk lines).

### Step 3: Update artifact patterns (if needed)

- **A — Common + learned first:** Run clean with no extra patterns. Built-in patterns plus `V:\reference\Books\converter\learned_patterns.txt` (one regex per line; add patterns from previous books here).
- **B — Per-book patterns:** If artifacts remain, add regexes to a per-book file (e.g. `V:\agents\laws_agent\Books\artifact_patterns\new_book_patterns.txt`) and pass with `--patterns` in Step 4. Consider copying useful new patterns into `learned_patterns.txt` for future books.

### Step 4: Clean

```powershell
python clean_artifacts_and_rejoin.py "V:\agents\laws_agent\Books\backups\new_book_raw.txt" "V:\agents\laws_agent\Books\backups\new_book_final.txt"
# With per-book patterns:
python clean_artifacts_and_rejoin.py "V:\agents\laws_agent\Books\backups\new_book_raw.txt" "V:\agents\laws_agent\Books\backups\new_book_final.txt" --patterns "V:\agents\laws_agent\Books\artifact_patterns\new_book_patterns.txt"
# For large files that timeout, use --fast (skips inline/split-word fixes; post-process handles remaining artifacts):
python clean_artifacts_and_rejoin.py "V:\agents\laws_agent\Books\backups\new_book_raw.txt" "V:\agents\laws_agent\Books\backups\new_book_final.txt" --fast
```

### Step 5: Convert to JSON

```powershell
python convert_to_rag_json.py "V:\agents\laws_agent\Books\backups\new_book_final.txt" "V:\agents\laws_agent\Books\new_book_rag.json"
# With book config (title, book_code, TOC page ranges):
python convert_to_rag_json.py "V:\agents\laws_agent\Books\backups\new_book_final.txt" "V:\agents\laws_agent\Books\new_book_rag.json" --config "V:\reference\Books\converter\config\new_book.json"
```

### Step 6: Post-process (OCR + spelling fixes)

```powershell
python post_process_rag_json.py "V:\agents\laws_agent\Books\new_book_rag.json"
```

Runs `fix_ocr_artifacts` and `fix_spelling_and_caps` from Books. Uses `learned_ocr_replacements.txt` for learned fixes. Modifies JSON in place.

---

## Book config (TOC / classification)

Most books have a table of contents. Build a config JSON so chunks get the right `content_type` by page range.

**Location:** `V:\reference\Books\converter\config\{slug}.json` (or pass `--config path`).

**Format:**

```json
{
  "source_title": "Full Book Title (Edition)",
  "book_code": "ABC-MET",
  "page_ranges": [
    { "start": 1, "end": 10, "content_type": "introduction" },
    { "start": 11, "end": 50, "content_type": "character_creation" },
    { "start": 51, "end": 9999, "content_type": "rules" }
  ]
}
```

- **source_title:** Used in RAG JSON `metadata.source` and for display.
- **book_code:** Optional; used in `metadata.book_code` (e.g. for `import_rag_data.php`).
- **page_ranges:** From the book’s TOC. Overlapping ranges: first match wins. Content types are free-form (e.g. `introduction`, `character_creation`, `storytelling`, `rules`, `appendix`).

**Summaries:** `V:\reference\Books_summaries` has `.md` summaries for many books. Use them to map section names to page ranges when building `page_ranges`.

---

## Batch (many books)

1. List PDFs: `python run_pipeline.py --list`
2. For each book, run:  
   `python run_pipeline.py --pdf "V:\reference\Books\...\book.pdf"`  
   Optionally add `--config` and `--patterns` once you have config/pattern files for that book.
3. After first pass, review `backups\*_artifact_report.txt` and `backups\*_final.txt`; add patterns and re-run clean + convert where needed.

---

## File layout

| Path | Purpose |
|------|--------|
| `V:\reference\Books\**\*.pdf` | Input PDFs |
| `V:\reference\Books_summaries\*.md` | Book summaries (for TOC) |
| `V:\reference\Books\converter\` | Scripts and runbook |
| `V:\reference\Books\converter\learned_patterns.txt` | Shared artifact regexes for clean step (add as you find new ones) |
| `V:\reference\Books\converter\learned_ocr_replacements.txt` | Learned OCR string replacements for post-process (add after each conversion) |
| `V:\reference\Books\converter\config\*.json` | Per-book config (title, book_code, page_ranges) |
| `V:\agents\laws_agent\Books\` | Output: `*_rag.json` |
| `V:\agents\laws_agent\Books\backups\` | Intermediate: `*_raw.txt`, `*_artifact_report.txt`, `*_final.txt` |

---

## Learning from every conversion

The converter learns from each run. After reviewing outputs, add findings so future books benefit.

### 1. Line-level artifacts (clean step)

When `*_artifact_report.txt` or `*_final.txt` shows junk lines (garbled headers, stray symbols, page fragments):

- Add regex patterns to `V:\reference\Books\converter\learned_patterns.txt` (one per line).
- Re-run clean and convert for that book.
- Consider copying useful per-book patterns into `learned_patterns.txt` for all future books.

### 2. OCR replacements (post-process step)

When `*_rag.json` content has garbled words or phrases (e.g. `Dfvflodfr` instead of `Developer`):

- Add exact replacements to `V:\reference\Books\converter\learned_ocr_replacements.txt`.
- Format: `bad_text|corrected_text` (one per line; `|` is the delimiter).
- Re-run post-process: `python post_process_rag_json.py path/to/book_rag.json`
- These replacements apply to all future conversions.

Example additions:

```
# From MET Journal 2
Dfvflodfr|Developer
syetheatrejournal|Eye Theatre Journal
```

### 3. Regex / structural fixes

Complex fixes (leading fragments, encoding glitches, regex patterns) live in `V:\agents\laws_agent\Books\fix_ocr_artifacts.py`. Add there when simple `find|replace` is insufficient.

---

## Import into Laws Agent

After conversion, import the JSON into the RAG DB:

```powershell
php V:\agents\laws_agent\import_rag_data.php "V:\agents\laws_agent\Books\new_book_rag.json"
```

Ensure the JSON has `metadata.source` (and optionally `metadata.book_code`); the importer uses these for book identity.

---

## Adding an OCR step (in-pipeline)

The converter is **up to date** and **ready to add an OCR process**. Today:

- **Detection:** `check_pdf_text.py` marks PDFs as `text` or `ocr` (sample first 5 pages; &lt;200 alphanumeric chars → ocr). `process_clan_books.ps1` can run this and warn before processing.
- **Docs:** Windows guide and QUICK_REFERENCE describe **external** OCR (Adobe Acrobat Pro or `ocrmypdf`), then run the pipeline on the OCR’d PDF.
- **Post-process:** `post_process_rag_json.py` and `learned_ocr_replacements.txt` already fix OCR-style garbled text in the final JSON.

**To add OCR inside the pipeline:**

1. **Before Step 1 (Extract):** For each PDF, run `check_pdf_text.py` (or a quick pdfplumber probe). If result is `ocr`:
   - Run OCR to get raw text with page boundaries. Options:
     - **A.** `tools/repeatable/python/ocr-tools/ocr_pdf.py` — outputs text with `=== PAGE N ===`; convert that to `<!-- PAGE N -->` and write `{slug}_raw.txt`, then **skip Extract** and run from Step 2 (Inspect) onward.
     - **B.** `ocrmypdf input.pdf output.pdf` — produces a searchable PDF; then run Extract as usual (no pipeline change).
2. **Normalize markers (if using ocr_pdf.py):** Replace `=== PAGE N ===` with `<!-- PAGE N -->` in the OCR output so `inspect_artifacts.py` and `clean_artifacts_and_rejoin.py` see the same format as PDF extract. A small script or a `--from-ocr` path in `run_pipeline.py` can do this.
3. **Pipeline change:** In `run_pipeline.py`, before calling the extract script: if PDF is image-based, call the OCR step, write `raw_path`, then jump to Step 2 (Inspect). Optional: add a `--force-ocr` flag to always OCR even when text is present (e.g. for re-OCR).

**Dependencies for in-repo OCR:** Tesseract installed; `pymupdf` (and optionally `Pillow`) for `tools/repeatable/python/ocr-tools/ocr_pdf.py`. See `tools/repeatable/python/ocr-tools/README.md`.
