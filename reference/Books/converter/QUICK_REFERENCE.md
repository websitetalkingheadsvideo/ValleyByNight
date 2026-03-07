# QUICK REFERENCE: Clan Books Processing

## One-Command Processing

```powershell
cd V:\reference\Books\converter
python run_pipeline.py --pdf "V:\reference\Books\Clan Books\MET - VTM - Clan Book Brujah (5001).pdf"
```

## Using the Batch Script

```powershell
cd V:\reference\Books\converter

# Test with one book first
.\process_clan_books.ps1 -TestOne

# Process all Clan Books
.\process_clan_books.ps1

# Skip OCR check (if you know PDFs are good)
.\process_clan_books.ps1 -SkipOCRCheck
```

## Manual Steps (When You Need Control)

### 1. Extract
```powershell
python extract_pdf_with_markers.py "input.pdf" "output_raw.txt"
```

### 2. Inspect
```powershell
python inspect_artifacts.py "output_raw.txt" "output_report.txt"
notepad output_report.txt  # Review artifacts
```

### 3. Clean
```powershell
python clean_artifacts_and_rejoin.py "output_raw.txt" "output_final.txt"
```

### 4. Convert
```powershell
python convert_to_rag_json.py "output_final.txt" "output_rag.json"
```

### 5. Import
```powershell
cd V:\agents\laws_agent
php import_rag_data.php "Books\output_rag.json"
```

## Common Issues

### Issue: [No text content] on all pages
**Problem:** PDF is image-based (scanned)  
**Solution:** OCR the PDF first:
- Adobe Acrobat Pro: Tools → Recognize Text
- Or: `ocrmypdf input.pdf output.pdf`

### Issue: Cursor times out
**Problem:** Large files + IDE timeout  
**Solution:** Run in PowerShell directly (more stable)

### Issue: Artifacts remain
**Problem:** Need custom patterns  
**Solution:** 
1. Check artifact_report.txt
2. Add patterns to learned_patterns.txt
3. Re-run clean step

## File Locations

```
V:\reference\Books\Clan Books\              ← Your PDFs here
V:\reference\Books\converter\               ← Scripts here
V:\agents\laws_agent\Books\                ← JSON output
  └── bookname_rag.json                    ← Import this
V:\agents\laws_agent\Books\backups\        ← Intermediate files
  ├── bookname_raw.txt                     ← Extracted text
  ├── bookname_artifact_report.txt         ← Artifact review
  └── bookname_final.txt                   ← Cleaned text
```

## Output Files Per Book

Each book creates 4 files:
1. **_raw.txt** (in backups/) - Extracted text with `<!-- PAGE N -->` markers
2. **_artifact_report.txt** (in backups/) - First line of each page (for review)
3. **_final.txt** (in backups/) - Cleaned, paragraph-rejoined text
4. **_rag.json** (in Books/) - Ready for database import

## Fastest Workflow

1. **Test one book:**
   ```powershell
   .\process_clan_books.ps1 -TestOne
   ```

2. **Check output quality:**
   ```powershell
   notepad "V:\agents\laws_agent\Books\backups\*_artifact_report.txt"
   notepad "V:\agents\laws_agent\Books\backups\*_final.txt"
   ```

3. **If good, process all:**
   ```powershell
   .\process_clan_books.ps1
   ```

4. **Import to database:**
   ```powershell
   cd V:\agents\laws_agent
   Get-ChildItem "Books\*_rag.json" | % { php import_rag_data.php $_.FullName }
   ```

## Time Estimates

- **Extract:** ~30 seconds per book
- **Inspect:** ~5 seconds
- **Clean:** ~10 seconds
- **Convert:** ~15 seconds
- **Total per book:** ~60 seconds

For 13 Clan Books: **~15 minutes total**

## Customization

### Add Book Config (Optional - Better Classification)

Create: `V:\reference\Books\converter\config\clan_book_brujah_5001.json`

```json
{
  "source_title": "Clan Book: Brujah (Mind's Eye Theatre)",
  "book_code": "MET-CB-BRUJAH",
  "page_ranges": [
    { "start": 1, "end": 10, "content_type": "introduction" },
    { "start": 11, "end": 60, "content_type": "character_creation" },
    { "start": 61, "end": 9999, "content_type": "storytelling" }
  ]
}
```

Use it:
```powershell
python run_pipeline.py --pdf "book.pdf" --config "config\clan_book_brujah_5001.json"
```

### Add Custom Patterns (If Artifacts Remain)

Edit: `V:\reference\Books\converter\learned_patterns.txt`

```regex
# Add patterns you find (one per line)
^C\s+lan\s+Bo+k$
^Bru\s+j\s+ah$
```

### Add OCR Replacements (If Content Has Garbled Words)

Edit: `V:\reference\Books\converter\learned_ocr_replacements.txt`

```
# Format: bad|good (one per line)
Dfvflodfr|Developer
```

Then re-run: `python post_process_rag_json.py path/to/book_rag.json`

**Learning:** After each conversion, add new findings to `learned_patterns.txt` and `learned_ocr_replacements.txt` so future books benefit.

## Success Criteria

✅ _raw.txt has text content (not all "[No text content]")  
✅ _artifact_report.txt shows actual book content  
✅ _final.txt is readable, proper paragraphs  
✅ _rag.json has documents with content  

## Need Help?

See: `Windows_PDF_Processing_Guide.md` for detailed troubleshooting
