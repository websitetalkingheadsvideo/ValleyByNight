# Windows Guide: Processing Clan Books PDFs

## System Analysis

Your PDF conversion system has:
- **5 Python scripts** for the pipeline (extract → inspect → clean → convert)
- **Built-in artifact patterns** (37 common OCR issues)
- **Learned patterns** file for reusable patterns across books
- **Per-book config** system for proper content classification
- **Full automation** via run_pipeline.py

## Issue: The Long Night PDF

The uploaded "MET - DA - The Long Night (5008).pdf" appears to be **image-based** with no extractable text. This requires OCR before processing. See "OCR Prerequisites" below.

---

## Prerequisites for Windows

### 1. Install Python & Dependencies

```powershell
# Check Python version (need 3.8+)
python --version

# Install required library
pip install pdfplumber
```

### 2. Verify Your Setup

```powershell
# Navigate to converter directory
cd V:\reference\Books\converter

# Test if scripts are accessible
python run_pipeline.py --help
```

---

## OCR Prerequisites (For Image-Based PDFs)

If PDFs are scanned images (like The Long Night), you need OCR first:

### Option A: Adobe Acrobat Pro (Recommended)
1. Open PDF in Adobe Acrobat Pro
2. Tools → Recognize Text → In This File
3. Settings: Language=English, Output=Searchable Image
4. Save as new PDF with "_OCR" suffix
5. Use OCR'd version in pipeline

### Option B: Free OCR Tools
```powershell
# Install Tesseract OCR
choco install tesseract

# Install OCRmyPDF
pip install ocrmypdf

# OCR a PDF
ocrmypdf "input.pdf" "output_ocr.pdf" --language eng
```

---

## Processing Clan Books: Step-by-Step

### Quick Start (One Book at a Time)

```powershell
cd V:\reference\Books\converter

# List all Clan Books
python run_pipeline.py --list --books-dir "V:\reference\Books\Clan Books"

# Process one Clan Book
python run_pipeline.py --pdf "V:\reference\Books\Clan Books\MET - VTM - Clan Book Brujah (5001).pdf"
```

**Output location:** `V:\agents\laws_agent\Books\`
- `clan_book_brujah_5001_raw.txt` - Extracted text with page markers
- `clan_book_brujah_5001_artifact_report.txt` - First lines of each page (for reviewing artifacts)
- `clan_book_brujah_5001_final.txt` - Cleaned text
- `clan_book_brujah_5001_rag.json` - RAG-ready JSON

### Batch Processing All Clan Books

```powershell
cd V:\reference\Books\converter

# Create a batch script
@"
`$books = Get-ChildItem "V:\reference\Books\Clan Books\*.pdf"
foreach (`$book in `$books) {
    Write-Host "Processing: `$(`$book.Name)" -ForegroundColor Green
    python run_pipeline.py --pdf `$book.FullName
    Write-Host "Completed: `$(`$book.Name)`n" -ForegroundColor Cyan
}
"@ | Out-File process_all_clan_books.ps1

# Run the batch
.\process_all_clan_books.ps1
```

---

## Advanced: Custom Configurations

### Per-Book Config (For Better Content Classification)

Create config files for accurate content classification based on table of contents.

**Example:** `V:\reference\Books\converter\config\clan_book_brujah_5001.json`

```json
{
  "source_title": "Clan Book: Brujah (Mind's Eye Theatre)",
  "book_code": "MET-CB-BRUJAH",
  "page_ranges": [
    { "start": 1, "end": 10, "content_type": "introduction" },
    { "start": 11, "end": 30, "content_type": "clan_history" },
    { "start": 31, "end": 60, "content_type": "character_creation" },
    { "start": 61, "end": 80, "content_type": "disciplines" },
    { "start": 81, "end": 100, "content_type": "storytelling" },
    { "start": 101, "end": 9999, "content_type": "appendix" }
  ]
}
```

Then use it:
```powershell
python run_pipeline.py --pdf "path\to\book.pdf" --config "config\clan_book_brujah_5001.json"
```

### Per-Book Artifact Patterns

If a book has unique OCR artifacts:

**Example:** `V:\agents\laws_agent\Books\artifact_patterns\clan_book_brujah_patterns.txt`

```regex
# Clan Book Brujah specific patterns
^Bru\s+j\s+ah$
^C\s+lan\s+Bo+k$
^[Ii1]+\s+Br[uv].*ah
```

Then use it:
```powershell
python run_pipeline.py --pdf "path\to\book.pdf" --patterns "path\to\patterns.txt"
```

---

## Manual 5-Step Process (For Fine Control)

When you want to review artifacts before cleaning:

### Step 1: Extract
```powershell
python extract_pdf_with_markers.py "V:\reference\Books\Clan Books\book.pdf" "V:\agents\laws_agent\Books\book_raw.txt"
```

### Step 2: Inspect
```powershell
python inspect_artifacts.py "V:\agents\laws_agent\Books\book_raw.txt" "V:\agents\laws_agent\Books\book_artifact_report.txt"

# Review the report
notepad "V:\agents\laws_agent\Books\book_artifact_report.txt"
```

### Step 3: Add Patterns (if needed)
Look at artifact report. If you see patterns, add to:
- `learned_patterns.txt` (for all future books)
- Or create per-book patterns file

### Step 4: Clean
```powershell
python clean_artifacts_and_rejoin.py "V:\agents\laws_agent\Books\book_raw.txt" "V:\agents\laws_agent\Books\book_final.txt"

# With custom patterns:
python clean_artifacts_and_rejoin.py "V:\agents\laws_agent\Books\book_raw.txt" "V:\agents\laws_agent\Books\book_final.txt" --patterns "patterns\book_patterns.txt"
```

### Step 5: Convert to JSON
```powershell
python convert_to_rag_json.py "V:\agents\laws_agent\Books\book_final.txt" "V:\agents\laws_agent\Books\book_rag.json"

# With config:
python convert_to_rag_json.py "V:\agents\laws_agent\Books\book_final.txt" "V:\agents\laws_agent\Books\book_rag.json" --config "config\book_config.json"
```

---

## Troubleshooting

### Problem: "No text content" on all pages
**Cause:** PDF is image-based, no extractable text
**Solution:** Use OCR (see "OCR Prerequisites" section above)

### Problem: Cursor times out
**Cause:** Processing large PDFs can take time
**Solution:** 
- Run in PowerShell directly (more stable)
- Process one book at a time
- Use `--skip-convert` to do extract/clean first, then convert later

```powershell
# Faster: Do extract/clean first
python run_pipeline.py --pdf "book.pdf" --skip-convert

# Later: Just convert to JSON (fast)
python convert_to_rag_json.py "output\book_final.txt" "output\book_rag.json"
```

### Problem: Too many/wrong artifacts removed
**Solution:** Review patterns, make them more specific
- Always use `^` and `$` anchors
- Test patterns: `python -c "import re; print(re.match(r'pattern', 'test_string'))"`

### Problem: Artifacts remain after cleaning
**Solution:** 
1. Check artifact report: `notepad book_artifact_report.txt`
2. Identify patterns
3. Add to `learned_patterns.txt`
4. Re-run clean step only

---

## Workflow Recommendations

### For Clan Books Collection:

**Phase 1: OCR Check**
```powershell
# Test extract one book first
python extract_pdf_with_markers.py "V:\reference\Books\Clan Books\first_book.pdf" "test_output.txt"

# Check if text extracted
Select-String -Path "test_output.txt" -Pattern "[No text content]" | Measure-Object
```

If many "[No text content]" → Need OCR first

**Phase 2: Test One Book Completely**
```powershell
python run_pipeline.py --pdf "V:\reference\Books\Clan Books\first_book.pdf"
```

Review outputs:
- artifact_report.txt - Look for patterns
- final.txt - Check quality
- rag.json - Verify structure

**Phase 3: Add Patterns & Configs**
Based on first book:
- Add artifact patterns to `learned_patterns.txt`
- Create configs for proper classification (optional)

**Phase 4: Batch Process Remaining**
```powershell
# Process all Clan Books
Get-ChildItem "V:\reference\Books\Clan Books\*.pdf" | ForEach-Object {
    Write-Host "Processing: $($_.Name)"
    python run_pipeline.py --pdf $_.FullName
}
```

---

## Import to Laws Agent

After processing, import JSON into database:

```powershell
cd V:\agents\laws_agent

# Import one book
php import_rag_data.php "Books\clan_book_brujah_5001_rag.json"

# Import all processed books
Get-ChildItem "Books\*_rag.json" | ForEach-Object {
    Write-Host "Importing: $($_.Name)"
    php import_rag_data.php $_.FullName
}
```

---

## Performance Tips

### Speed Up Processing

1. **Skip inspection step** (if you trust learned patterns):
   ```powershell
   # Modify run_pipeline.py to skip inspection in batch mode
   ```

2. **Parallel processing** (PowerShell 7+):
   ```powershell
   $books = Get-ChildItem "V:\reference\Books\Clan Books\*.pdf"
   $books | ForEach-Object -Parallel {
       python run_pipeline.py --pdf $_.FullName
   } -ThrottleLimit 4
   ```

3. **Process in stages**:
   ```powershell
   # Stage 1: Extract all (slow)
   # Stage 2: Clean all (fast)
   # Stage 3: Convert all (fast)
   ```

---

## Next Steps

1. **Verify OCR status** of Clan Books:
   ```powershell
   python extract_pdf_with_markers.py "V:\reference\Books\Clan Books\any_book.pdf" "test.txt"
   # Check test.txt for actual text vs "[No text content]"
   ```

2. **If OCR needed**: Process all PDFs with Adobe Acrobat or OCRmyPDF

3. **Process first book**: Test entire pipeline on one Clan Book

4. **Review & refine**: Check outputs, add patterns as needed

5. **Batch process**: Run all Clan Books

6. **Import**: Load JSON files into Laws Agent database

---

## File Locations Summary

```
V:\reference\Books\Clan Books\          ← Input PDFs
V:\reference\Books\converter\           ← Scripts
V:\reference\Books\converter\config\    ← Book configs (optional)
V:\reference\Books\converter\learned_patterns.txt  ← Shared patterns
V:\agents\laws_agent\Books\            ← Output files
V:\agents\laws_agent\Books\artifact_patterns\  ← Per-book patterns (optional)
```

## Expected Output Per Book

For each Clan Book, you get 4 files:
1. `{slug}_raw.txt` - 194 pages with markers (or however many pages)
2. `{slug}_artifact_report.txt` - First line of each page (for review)
3. `{slug}_final.txt` - Cleaned, paragraph-rejoined text
4. `{slug}_rag.json` - RAG-ready JSON for database import

---

## Questions or Issues?

Common issues and solutions documented above. The system is designed to handle:
- ✅ OCR artifacts (garbled headers, symbols, partial text)
- ✅ Page preservation (<!-- PAGE N --> markers)
- ✅ Content classification (via configs or keywords)
- ✅ Chunking for RAG (1000 token chunks)
- ✅ Batch processing (entire folder)

Main limitation: **Requires text-extractable PDFs**. If PDFs are images, OCR first.
