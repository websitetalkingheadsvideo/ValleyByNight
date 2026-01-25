# How to Scan a PDF and Create a Book Summary

This guide explains how to extract text from a PDF and write a summary that matches the style of the existing summaries in `reference/Books_summaries`.

---

## Part 1: Extracting Text from the PDF

### 1.1 Full-text extraction (primary workflow)

Use the project’s full-PDF extractor:

```text
python V:\tools\repeatable\python\extract_full_pdf_text.py <path_to_pdf> [output_file]
```

- **Input:** Path to the PDF (absolute recommended, e.g. `V:\path\to\book.pdf`).
- **Output:** If you omit `output_file`, text is printed to stdout. To save to a file, pass a second argument.

**Typical usage:** Save extracted text under `V:\tmp\` with a descriptive name:

```text
python V:\tools\repeatable\python\extract_full_pdf_text.py "V:\path\to\My_Book.pdf" V:\tmp\my_book_extracted.txt
```

The script uses **pdfplumber** if available (preferred), otherwise **PyPDF2**. Install at least one:

```text
pip install pdfplumber   # preferred
# or
pip install PyPDF2
```

Extracted output uses page markers:

```text
=== PAGE 1 ===
...text...
=== PAGE 2 ===
...text...
```

### 1.2 Image-based or scanned PDFs

If you get very little or garbled text (e.g. OCR’d scans, image-only PDFs):

- The script warns when extracted text is very short.
- Use the **OCR tool** to extract text from image-based PDFs:

  ```text
  python V:\tools\repeatable\python\ocr-tools\ocr_pdf.py "V:\path\to\My_Book.pdf" V:\tmp\my_book_extracted.txt
  ```
- Optionally run **`clean_pdf_text.py`** on the extracted/OCR’d file to remove common artifacts before summarizing:

  ```text
  python V:\tools\repeatable\python\text-cleanup-tools\clean_pdf_text.py V:\tmp\my_book_extracted.txt V:\tmp\my_book_cleaned.txt
  ```

### 1.3 Single-page extraction

For debugging or single-page work, use the PDF page extractor:

```text
python V:\tools\repeatable\python\pdf-tools\extract_pdf_page.py <pdf_file> <page_number>
```

---

## Part 2: Summary Style (Books_summaries)

Summaries in `reference/Books_summaries` follow a consistent structure and tone. Match these when creating a new summary.

### 2.1 Document structure

1. **Title (H1)**  
   - Single `#` heading with the **book title** as it is commonly referred to (e.g. `# Laws of the Night`, `# Prince's Primer`).

2. **Opening paragraph**  
   - **2–4 sentences** in third person.  
   - State: what the document is, its purpose, and main focus.  
   - No “Purpose and Scope” heading here; that’s a separate section.

3. **Purpose and Scope (optional)**  
   - `## Purpose and Scope`  
   - **Bullet list** of what the book contains, assumes, or requires (e.g. “Not a substitute for Laws of the Night”, “Assumes familiarity with MET basics”, “Focuses on X”).

4. **Chapters and sections**  
   - **H2** for major parts (chapters, “Introduction”, “Chapter Two: …”, etc.).  
   - **H3** for subsections.  
   - **Follow the book’s structure**; don’t reorganize by theme unless the source does.

5. **Content within sections**  
   - **Bullet points** for lists (rules, clans, traits, etc.).  
   - **Bold** for key terms, names, discipline names, and similar.  
   - Short **prose** for narrative or explanatory bits.  
   - Be **concise** and factual.

### 2.2 Tone and conventions

- **Third person, present tense** where appropriate (“The book provides…”, “This supplement expands…”).
- **Informational only** — no meta-commentary (“This summary covers…”) or opinions.
- **Stick to what’s in the book** — no extra lore or interpretation.
- **Names and terms** — use the same spelling and capitalization as the source (e.g. “Kindred”, “Elysium”, “Masquerade”).

### 2.3 Examples

**Good opening:**

```markdown
# Laws of the Night

The document is the core rulebook for Mind's Eye Theatre Vampire: The Masquerade, providing all the essential rules, systems, and information needed to play and run live-action roleplaying games in the World of Darkness.
```

**Good “Purpose and Scope”:**

```markdown
## Purpose and Scope

- This book contains all the information needed to start playing and telling stories in Mind's Eye Theatre.
- It is based on the tabletop creation Vampire: The Masquerade, updated for live-action play.
- It provides elegantly simple rules designed to be easy to play and easier to start.
```

**Good section with bullets and bold:**

```markdown
### The Traditions

The inviolate rules of Kindred existence:

1. **The Masquerade**: Thou shalt not reveal thy true nature to those not of the Blood.
2. **The Domain**: Thy domain is thy concern. All others owe thee respect while in it.
3. **The Progeny**: Thou shalt sire another only with permission of thine elder.
```

### 2.4 Reference summaries to mimic

- **Rulebooks:** `Laws_of_the_Night.md`, `Laws_of_Elysium.md`  
- **Guides/supplements:** `Camarilla_Guide_MET.md`, `Anarch_Guide_MET.md`, `Prince's_Primer.md`  
- **Short reference:** `Reference Guide.md`  
- **Journals/anthologies:** `MET_Journal_1.md`, `MET_Journal_2.md`

---

## Part 3: End-to-end workflow

1. **Extract**  
   Run `extract_full_pdf_text.py` on the PDF; save to `V:\tmp\<name>_extracted.txt`. If the PDF is image-based, use `ocr_pdf.py` instead. Optionally run `clean_pdf_text.py` to clean up artifacts.

2. **Read and outline**  
   Skim the extracted text. Note chapter titles, major sections, and key concepts. Build an outline that mirrors the book.

3. **Draft the summary**  
   - Add H1 title and opening paragraph.  
   - Add “Purpose and Scope” if useful.  
   - Add H2/H3 sections following the outline.  
   - Fill each section with bullets and short prose, using **bold** for important terms.

4. **Save**  
   Write the summary to `V:\reference\Books_summaries\<Book_Name>.md`. Use the same naming style as existing files (e.g. `Anarch_Guide_MET.md`, `Laws_of_the_Night.md`).

---

## Summary

| Step | Tool / location | Output |
|------|------------------|--------|
| Extract PDF | `extract_full_pdf_text.py` | `V:\tmp\<name>_extracted.txt` |
| OCR (if image PDF) | `ocr_pdf.py` in `ocr-tools` | `V:\tmp\<name>_extracted.txt` |
| Clean (optional) | `clean_pdf_text.py` in `text-cleanup-tools` | `V:\tmp\<name>_cleaned.txt` |
| Write summary | — | `V:\reference\Books_summaries\<Book_Name>.md` |

Match the **structure** (H1 → intro → Purpose and Scope → chapters/sections) and **tone** (third person, factual, concise) of existing `Books_summaries` when you write.
