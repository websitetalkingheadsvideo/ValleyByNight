# PDF to RAG JSON Conversion Process

## Overview
This document describes the process for converting OCR-extracted PDFs with artifacts into clean, page-marked JSON files suitable for RAG (Retrieval-Augmented Generation) systems.

## Prerequisites
- Python 3 with pdfplumber library
- Original PDF file
- Target output: JSON with document chunks, page numbers, and content classification

## Step-by-Step Process

### Step 1: Extract Text from PDF with Page Markers

**Script: extract_with_page_markers.py**

```python
import pdfplumber

def extract_pdf_with_page_markers(pdf_path, output_path):
    """Extract text from PDF with <!-- PAGE X --> markers"""
    
    with pdfplumber.open(pdf_path) as pdf:
        total_pages = len(pdf.pages)
        print(f"Total pages in PDF: {total_pages}")
        
        with open(output_path, 'w', encoding='utf-8') as out:
            for i, page in enumerate(pdf.pages, start=1):
                # Write page marker
                out.write(f"<!-- PAGE {i} -->\n")
                
                # Extract text from page
                try:
                    text = page.extract_text()
                    if text and text.strip():
                        out.write(text)
                        out.write("\n")
                    else:
                        out.write(f"[No text content]\n")
                except Exception as e:
                    out.write(f"[Error: {e}]\n")
                
                out.write("\n")
```

**Key Points:**
- Use `pdfplumber` instead of PyPDF2 for better OCR text extraction
- Add `<!-- PAGE X -->` markers to preserve page boundaries
- Handle pages with no text content gracefully
- Always verify the PDF actually has the expected number of pages

### Step 2: Identify OCR Artifacts

**Manual Process:**
1. Review the extracted text file
2. Look for patterns like:
   - Repeated characters: `i i i i`, `I I I I`, `∎ ∎ ∎`
   - Garbled headers: `Tiber t es Goules` (should be "Liber des Goules")
   - Page decoration fragments: `,AnPIe1.`, `L f i3`
   - Standalone letters or symbols: `L`, `if`, `∎`
   - Mixed character patterns: `~11~~1f P01~1)~il1)1H`

3. Check lines immediately after `<!-- PAGE X -->` markers - these often contain artifacts

4. Use commands to find artifacts:
```bash
# Find lines after page markers
awk '/^<!-- PAGE/ {page=$0; getline; getline; if (length($0) < 80) print page " >>> " $0}' file.txt

# Find short lines with suspicious patterns
grep -E "(iber|ties|I I I|i i i)" file.txt | grep -v "actual content patterns"
```

### Step 3: Clean OCR Artifacts

**Script: complete_clean.py**

This script has two phases:
1. Remove artifact lines (BEFORE paragraph rejoining)
2. Rejoin paragraphs (AFTER artifact removal)

```python
import re

def clean_and_rejoin(input_file, output_file):
    """Clean artifacts then rejoin paragraphs"""
    
    with open(input_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    # PHASE 1: Remove artifact lines
    cleaned_lines = []
    artifact_patterns = [
        # Build comprehensive list based on manual inspection
        r'^[Ii1 ]+$',  # Lines with only i, I, or 1 characters
        r'^[Ii1 ∎]+$',  # Mixed with symbols
        r'^[LI]$',  # Standalone letters
        # ... add all patterns found in inspection
    ]
    
    for line in lines:
        stripped = line.strip()
        
        # Always keep page markers
        if stripped.startswith('<!-- PAGE'):
            cleaned_lines.append(line)
            continue
        
        # Check if line is an artifact
        is_artifact = False
        for pattern in artifact_patterns:
            if re.match(pattern, stripped, re.IGNORECASE):
                is_artifact = True
                break
        
        if not is_artifact:
            cleaned_lines.append(line)
    
    # PHASE 2: Rejoin paragraphs
    result_lines = []
    current_paragraph = []
    
    for line in cleaned_lines:
        stripped = line.strip()
        
        # Page markers always separate
        if stripped.startswith('<!-- PAGE'):
            if current_paragraph:
                result_lines.append(' '.join(current_paragraph))
                current_paragraph = []
            result_lines.append(line.rstrip())
            continue
        
        # Empty line = paragraph break
        if not stripped:
            if current_paragraph:
                result_lines.append(' '.join(current_paragraph))
                current_paragraph = []
            result_lines.append('')
            continue
        
        # Handle hyphenated words at line breaks
        if current_paragraph and current_paragraph[-1].endswith('-'):
            current_paragraph[-1] = current_paragraph[-1][:-1]
            current_paragraph.append(stripped)
        else:
            current_paragraph.append(stripped)
    
    # Flush remaining paragraph
    if current_paragraph:
        result_lines.append(' '.join(current_paragraph))
    
    # Clean up excessive blank lines
    result = '\n'.join(result_lines)
    result = re.sub(r'\n\n\n+', '\n\n', result)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(result)
```

**Critical Rules:**
1. Remove artifacts BEFORE rejoining paragraphs
2. Each artifact pattern must match a COMPLETE line (use `^` and `$`)
3. Test patterns with Python's `re.match()` before adding them
4. Be specific - avoid overly broad patterns that might remove real content
5. Always keep `<!-- PAGE X -->` markers

### Step 4: Convert to RAG JSON Format

**Script: convert_to_rag_json.py**

```python
import json
import re

def classify_content(text, page_num):
    """Classify content based on keywords and page ranges"""
    text_lower = text.lower()
    
    # Use table of contents to determine page ranges for each section
    if page_num <= 11:
        return 'introduction' if 'chapter' in text_lower else 'story'
    if 21 <= page_num <= 36:
        return 'ghoul_types'
    if 37 <= page_num <= 72:
        return 'character_creation'
    if 73 <= page_num <= 98:
        return 'storytelling'
    if page_num >= 99:
        return 'animal_ghouls'
    
    # Keyword-based classification as fallback
    if any(word in text_lower for word in ['discipline', 'power']):
        return 'discipline_info'
    return 'general'

def extract_section_title(text):
    """Extract section title from first few lines"""
    lines = text.strip().split('\n')
    
    for line in lines[:5]:
        line = line.strip()
        if not line:
            continue
        
        # Check for chapter headers
        if 'chapter' in line.lower():
            return line
        
        # All-caps titles (but not too long)
        if line.isupper() and 5 < len(line) < 60:
            return line
    
    return None

def split_into_chunks(text, max_tokens=1000):
    """Split text into chunks by paragraphs"""
    max_chars = max_tokens * 4  # Rough estimate: 1 token ≈ 4 chars
    
    if len(text) <= max_chars:
        return [text]
    
    paragraphs = text.split('\n\n')
    chunks = []
    current_chunk = []
    current_length = 0
    
    for para in paragraphs:
        para_len = len(para)
        
        if current_length + para_len > max_chars and current_chunk:
            chunks.append('\n\n'.join(current_chunk))
            current_chunk = [para]
            current_length = para_len
        else:
            current_chunk.append(para)
            current_length += para_len + 2
    
    if current_chunk:
        chunks.append('\n\n'.join(current_chunk))
    
    return chunks

def convert_to_rag_json(input_file, output_file):
    """Convert page-marked text to RAG JSON"""
    
    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Split by page markers
    pages = re.split(r'<!-- PAGE (\d+) -->\n', content)
    
    documents = []
    doc_id = 0
    
    # pages alternates: [content_before_first], page_num, content, page_num, content...
    for i in range(1, len(pages), 2):
        if i + 1 >= len(pages):
            break
        
        page_num = int(pages[i])
        page_content = pages[i + 1].strip()
        
        if not page_content or page_content == '[No text content]':
            continue
        
        content_type = classify_content(page_content, page_num)
        section_title = extract_section_title(page_content)
        chunks = split_into_chunks(page_content)
        
        for chunk_idx, chunk in enumerate(chunks):
            doc = {
                "id": f"doc_{doc_id}",
                "page": page_num,
                "chunk_index": chunk_idx,
                "total_chunks": len(chunks),
                "content": chunk,
                "content_type": content_type,
                "metadata": {
                    "source": "Book Title Here",
                    "page_number": page_num,
                    "section_title": section_title,
                    "is_chunked": len(chunks) > 1,
                    "chunk_position": f"{chunk_idx + 1}/{len(chunks)}"
                }
            }
            documents.append(doc)
            doc_id += 1
    
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(documents, f, indent=2, ensure_ascii=False)
```

## Common OCR Artifact Patterns

### Header/Footer Decorations
```python
r'^[Ii1 ]+$'           # i i i i, I I I I
r'^[Ii1 ∎]+$'          # Mixed with symbols
r'^∎+$'                # Just symbols
```

### Garbled Text
```python
r'^[BbBt ]+t\s+f\s+Gh$'                          # "B t f Gh"
r'^~+-?hap\d+es\s+\d+\s+krai\s+kr\s+w~+ic~n$'   # "~-hap1es 3 krai kr"
r'^,AnPIe\d+\.$'                                  # ",AnPIe1."
r'^\d+T[\'"]?\s+[A-Z][a-z]+\s+ag[A-Z]+\s+[a-z]$' # "1T' Lieh agiI g"
```

### Partial Words/Fragments
```python
r'^[liI1]+\s*/\s*[liI1\s]+$'  # "l / l l l"
r'^if$'                        # Standalone "if"
r'^-?Pool$'                    # "-Pool"
r'^ell\s*\.$'                  # "ell ."
```

### Garbled Headers (Book-Specific)
```python
# For "Liber des Goules" - adjust for your book title
r'^[TLIi1]+iber\s+[tudcs]\s*[eiI]*\s*[us]+\s+[\'"\(]?[iI\(]?[co0Ocu]+[li1]+e?s?'
r'^liber\s+\[\s*us\s+[DU]+\s*es'
r'^[iI]+\s*i?[UuOo]+er\s+[GgD][oO0][IiLl1]+[li1]+e?\d*$'
```

## Testing and Validation

### Verify Page Count
```bash
pdfinfo your_file.pdf | grep Pages
grep -c "<!-- PAGE" output.txt
```

### Check for Remaining Artifacts
```bash
# Look at lines immediately after page markers
awk '/^<!-- PAGE/ {page=$0; getline; getline; print page " >>> " substr($0,1,80)}' file.txt | less

# Search for suspicious patterns
grep -E "(i i i|I I I|∎|~|iber)" file.txt | grep -v "legitimate text patterns"
```

### Validate JSON
```python
import json

with open('output.json', 'r') as f:
    data = json.load(f)
    print(f"Documents: {len(data)}")
    print(f"Pages covered: {max(d['page'] for d in data)}")
    print(f"Content types: {set(d['content_type'] for d in data)}")
```

## Troubleshooting

### Problem: Pattern doesn't match artifact
**Solution:** Test the pattern in Python:
```python
import re
pattern = r'^your_pattern$'
test_string = "the artifact line"
if re.match(pattern, test_string):
    print("MATCH")
else:
    print("NO MATCH - adjust pattern")
```

### Problem: Artifacts remain after paragraph rejoining
**Solution:** Artifacts were joined into paragraphs. Must remove artifacts BEFORE rejoining.

### Problem: Real content removed
**Solution:** Pattern is too broad. Make it more specific with anchors (`^`, `$`) and exact character classes.

### Problem: PDF shows different page count than pdfplumber
**Solution:** Check if the PDF file is complete:
```bash
ls -lh file.pdf  # Check file size
pdfinfo file.pdf # Check metadata
```
Try re-downloading or re-uploading the PDF.

## Checklist for Next Book

- [ ] Upload PDF and verify page count with `pdfinfo`
- [ ] Extract text with page markers using pdfplumber
- [ ] Manually inspect first 20 pages for artifact patterns
- [ ] Check lines after `<!-- PAGE X -->` markers specifically
- [ ] Create artifact pattern list (start with common patterns above)
- [ ] Test each pattern with `re.match()` before adding
- [ ] Run cleaning script (artifacts first, then paragraph rejoining)
- [ ] Verify specific problem pages are clean
- [ ] Update content classification for new book (check TOC for page ranges)
- [ ] Update `source` field in JSON metadata
- [ ] Convert to RAG JSON
- [ ] Validate JSON structure and content
- [ ] Present both .json and .txt files to user

## Files Created

1. `{bookname}_with_page_markers.txt` - Raw extraction with page markers
2. `{bookname}_final.txt` - Cleaned text with page markers  
3. `{bookname}_rag.json` - RAG-ready JSON format

## Important Notes

- **Order matters**: Always remove artifacts BEFORE rejoining paragraphs
- **Test incrementally**: Add a few patterns, test, add more
- **Be conservative**: Better to manually review a few artifacts than accidentally remove real content
- **Page markers are sacred**: Never remove `<!-- PAGE X -->` markers
- **Each book is different**: Artifact patterns vary by OCR quality and original document formatting
