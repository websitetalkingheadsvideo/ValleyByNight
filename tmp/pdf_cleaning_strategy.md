# PDF Text Cleaning Strategy

## Identified Artifacts

Based on analysis of files in `reference/Books_md_ready/`, the following PDF extraction artifacts were identified:

### 1. **Broken Line Breaks**
- **Problem**: Sentences split across multiple lines incorrectly
- **Example**: 
  ```
  Why in the name ofall that is(or isnot) holy have you adopted this overcomplicatedsystemofnumbers
  ```
- **Should be**: 
  ```
  Why in the name of all that is (or is not) holy have you adopted this overcomplicated system of numbers
  ```

### 2. **Missing Spaces Between Words**
- **Problem**: Words incorrectly concatenated
- **Examples**: 
  - `Whyin` → `Why in`
  - `overcomplicatedsystemofnumbers` → `overcomplicated system of numbers`
  - `allthat` → `all that`
  - `is(or` → `is (or`

### 3. **Extra/Multiple Spaces**
- **Problem**: Inconsistent spacing, especially around common words
- **Examples**: 
  - `o f   the` → `of the`
  - `the  entire` → `the entire`
  - Multiple spaces between words

### 4. **Header/Footer Noise**
- **Problem**: Page numbers, order numbers, and copyright info scattered throughout
- **Examples**: 
  - `Sean Carter (order #19521)` appearing repeatedly
  - `735 PARK NORTH BLVD. SUITE 128`
  - `CLARKSTON, GA 30021`
  - Standalone page numbers

### 5. **Isolated Single Characters**
- **Problem**: Lines containing only single characters (likely OCR noise)
- **Examples**: 
  - Lines with just `I`, `A`, `u`, `c`, etc.
  - These are often page breaks or formatting artifacts

### 6. **Image Placeholders**
- **Problem**: HTML-style image placeholders left in text
- **Example**: `<!-- image -->` tags throughout documents

### 7. **Hyphenation Issues**
- **Problem**: Words broken with hyphens at line endings not rejoined
- **Example**: 
  ```
  some-
  thing
  ```
  Should be: `something`

### 8. **Broken Paragraphs**
- **Problem**: Paragraphs split across multiple lines incorrectly
- **Example**: Each sentence on its own line when they should be grouped

### 9. **Encoding Errors**
- **Problem**: Special characters mangled during extraction
- **Examples**: 
  - Smart quotes/apostrophes as `â€™`
  - Em dashes as `â€"`
  - Other Unicode encoding issues

### 10. **Inconsistent Capitalization**
- **Problem**: ALL CAPS sections mixed with normal text (may be intentional for headers)
- **Note**: This may be intentional for section headers, so handled conservatively

## Cleaning Strategy

### Processing Order (Critical)

1. **Remove Image Placeholders** - Simple pattern matching, no side effects
2. **Fix Encoding Issues** - Character-level fixes before word-level processing
3. **Remove Header/Footer Noise** - Remove known patterns before line processing
4. **Fix Hyphenation** - Rejoin words before spacing fixes
5. **Fix Missing Spaces** - Add spaces between incorrectly joined words
6. **Fix Extra Spaces** - Normalize spacing after adding missing spaces
7. **Rejoin Broken Lines** - Merge lines that should be together
8. **Remove Isolated Characters** - Clean up noise lines
9. **Clean Paragraph Structure** - Final formatting pass

### Key Principles

- **Preserve Semantic Meaning**: Never change the actual content, only formatting
- **Conservative Approach**: When in doubt, preserve the original
- **Context-Aware**: Some fixes require understanding context (e.g., acronyms vs. missing spaces)
- **Reversible**: Create backups before processing

## Implementation

The cleaning script (`scripts/clean_pdf_text.py`) implements all these fixes with:

- **Configurable processing**: Can process entire directories or individual files
- **Backup creation**: Automatically creates `.bak` files before overwriting
- **Output options**: Can write to new directory or overwrite originals
- **Error handling**: Gracefully handles encoding issues and file errors

## Usage

```bash
# Process all .md files in reference/Books_md_ready/, create backups
python scripts/clean_pdf_text.py reference/Books_md_ready/

# Process and write to new directory
python scripts/clean_pdf_text.py reference/Books_md_ready/ --output-dir reference/Books_md_cleaned/

# Process without backups
python scripts/clean_pdf_text.py reference/Books_md_ready/ --no-backup

# Process .txt files instead
python scripts/clean_pdf_text.py reference/Books_md_ready/ --ext .txt
```

## Expected Results

After cleaning, text should:
- Have proper spacing between words
- Have paragraphs properly formed (not every sentence on its own line)
- Be free of header/footer noise
- Have image placeholders removed
- Have proper punctuation spacing
- Be readable as clean, professionally edited plain text

## Limitations

- **Context-dependent fixes**: Some fixes (like distinguishing acronyms from missing spaces) may need manual review
- **Intentional formatting**: Some "artifacts" may be intentional (e.g., ALL CAPS headers)
- **Complex layouts**: Multi-column layouts may not rejoin correctly
- **Tables/Structured Data**: May need special handling

## Next Steps

1. Run script on sample files to verify results
2. Review cleaned output for any missed patterns
3. Refine rules based on results
4. Process entire directory
5. Manual review of critical sections

