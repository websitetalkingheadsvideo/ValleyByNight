# Standard Book Processing Directions (LotNR Method)

Use this procedure for all remaining books.

## 1. Generate a Summary First (Required)

Create a book-level summary from the original PDF.

The summary must:

- Capture rules intent, not layout
- Preserve official terminology
- Identify systems, exceptions, and edge cases
- Do not attempt formatting or cleanup at this stage

**Output:** `/reference/Books_summaries/<BookName>_summary.md`

## 2. Perform Mechanical OCR Cleanup (No Interpretation)

Clean the raw OCR text before any normalization.

**Required fixes:**

- Reconstruct paragraph structure
- Remove layout-induced line breaks
- Fix broken hyphenation (word- break → wordbreak)
- Fix split words (Em- braced → Embraced)
- Remove duplicated characters or scan noise

**Rules:**

- ❌ Do not rewrite sentences
- ❌ Do not improve prose
- ❌ Do not change meaning

**Output:** `/reference/Books_md_clean/<BookName>/raw_clean.md`

## 3. Normalize Structure (Not Wording)

Restructure the cleaned text into consistent Markdown.

- Apply predictable headings
- Use consistent section order
- Convert lists and tables cleanly
- Preserve original wording as much as possible

**Rules:**

- No paraphrasing
- No summarizing
- No rule changes

**Output:** `/reference/Books_md_clean/<BookName>/<BookName>.md`

## 4. Validate Against the Summary

Compare the cleaned Markdown to the summary, not the PDF.

**Confirm:**

- All major systems exist
- No rules were dropped
- No contradictions introduced

**If conflict exists:**

- The summary defines intent
- The Markdown must be corrected to match it

## 5. Final Acceptance Criteria

A book is considered complete when:

- Summary is accurate and readable
- Markdown is structurally consistent
- Rules intent matches the summary
- Text is usable by agents and databases

Perfect visual fidelity is not required.

## Guiding Principle (Do Not Skip)

Extract meaning first. Rebuild structure second. Never attempt lossless conversion.
