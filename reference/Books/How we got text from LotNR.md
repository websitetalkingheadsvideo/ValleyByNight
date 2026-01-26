# How We Successfully Integrated Laws of the Night Revised into the WoD Glossary & RAG Pipeline

This document summarizes the process, decisions, and lessons learned while converting Laws of the Night Revised (LotNR) into a structured, authoritative source for glossary entries, FAQs, and Retrieval-Augmented Generation (RAG). The same workflow can now be applied to other books.

## 1. The Initial Problem

We started with several challenges common to WoD sourcebooks:

- PDFs with poor OCR quality
- Text that was selectable but not reliably copyable
- Good summaries, but missing:
  - formal definitions
  - mechanical language
  - clan weaknesses and rules text
- A glossary that existed, but lacked a clear canonical authority

Early summaries (e.g., Toreador) captured flavor but not rules, leading to gaps and inconsistencies.

## 2. Key Insight: Summaries ≠ Definitions

A crucial realization:

- Summaries are compression tasks (high-level, interpretive, lossy)
- Definitions are reconstruction tasks (precise, declarative, canonical)

Trying to "summarize harder" will never replace extracting actual rulebook definitions.

This reframed the goal:

- Treat the rulebook as a lexical authority, not a narrative source.

## 3. Breakthrough: Structurally Usable Text

Once we obtained a version of LotNR where:

- Text was selectable
- Headings were detectable
- A Lexicon section existed

…the problem shifted from OCR recovery to structural normalization.

The key was not perfect prose, but recoverable structure:

- chapters
- section headers
- term–definition patterns

## 4. Strategy Shift: Normalize, Don't Rewrite

We adopted a strict rule:

**Never rewrite prose. Only normalize structure and obvious OCR damage.**

This led to a 2-phase approach:

### Phase A — Structural Cleanup

- Normalize headers (#, ##, ###)
- Reconstruct paragraphs
- Fix obvious OCR artifacts (hyphenation, dropped letters)
- Preserve original wording and order

### Phase B — Lexicon Extraction

- Treat the Lexicon as primary authority
- Extract only bounded Lexicon sections
- Convert entries into: **Term** — Definition
- Apply anti-contamination rules to prevent fiction from leaking in

This produced a clean `lotnr_lexicon.md`.

## 5. Preparing for RAG (Without Overengineering)

We then made the content RAG-ready by:

- Splitting the book into three logical documents:
  - rules
  - lexicon
  - fiction
- Chunking by headers and paragraphs
- Giving lexicon entries one chunk per term
- Adding stable IDs and metadata (breadcrumbs, source)

Crucially:

- Lexicon chunks were treated as authoritative
- Rules and fiction were kept separate to avoid retrieval noise

## 6. Iterative Hardening (Fixing Real Problems)

During validation, we encountered and fixed real-world issues:

- Fiction accidentally matching definition patterns
- Merged lexicon entries (e.g., Generation / Gehenna)
- Minor OCR artifacts
- Missing punctuation in definitions

Each fix followed the same principle:

- Rule-based, minimal, repeatable, and consistent across all outputs.
- No subjective edits. No reinterpretation.

## 7. Integration into the Glossary

Once LotNR was clean:

- It was added to `WoD_Glossary.md` as a primary source
- Glossary entries now explicitly say: "From Laws of the Night Revised (Lexicon)"
- Other books are layered as supplementary, not competing definitions

This solved:

- authority conflicts
- vague definitions
- inconsistency across splats

## 8. UI Reality Check (Glossary Page)

When viewing the live glossary:

- The data and structure were correct
- The remaining issues were UX, not content:
  - search returns categories, not specific terms
  - long vertical layouts during search

This confirmed the pipeline was sound:

- Problems now are presentation-level, not data-level.

## 9. Final Outcome

At the end of this process, we achieved:

- A clean, authoritative lexicon
- A RAG-ready rules spine
- A glossary grounded in actual rule text
- A repeatable pipeline that:
  - scales to other books
  - avoids hallucination
  - preserves WoD's intentional ambiguity where appropriate

## 10. The Repeatable Pattern (For Other Books)

For each future book:

1. Obtain selectable text (OCR if needed)
2. Normalize structure, not prose
3. Identify and extract:
   - Lexicon / Glossary sections (primary)
   - Rules text (secondary)
   - Fiction (tertiary or excluded)
4. Apply strict bounds and anti-contamination rules
5. Chunk with metadata
6. Integrate as:
   - Primary authority if it defines terms
   - Supplementary context otherwise
7. Only then worry about UI or summaries

## Core Principle (The One-Line Lesson)

**Let the books define terms in their own words; let summaries explain them later.**

That principle is what made this work — and it's what will make the next book easier.
