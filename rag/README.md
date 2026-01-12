# RAG Preparation Pipeline

This directory contains the RAG (Retrieval-Augmented Generation) preparation pipeline for Mind's Eye Theatre/World of Darkness rulebook markdown files.

## Overview

The pipeline processes 43 markdown rulebook files from `reference/Books_md_ready_fixed_cleaned/`, normalizes their structure, builds a shared controlled vocabulary (glossary), produces chunked outputs with metadata, generates cross-book concept indexes, and documents the process for repeatability.

## Folder Structure

```
rag/
├── README.md                    # This file
├── config/                      # Configuration files (exclusions, etc.)
├── schema/                      # JSON Schema definitions
│   ├── chunk.schema.json       # Chunk metadata schema
│   └── glossary.schema.json    # Glossary entry schema
├── glossary.yml                 # Controlled vocabulary with canonical terms and aliases
├── index/                       # Indexes
│   ├── book_index.json         # Book metadata and statistics
│   └── concept_index.json      # Concept-to-chunk mappings
├── derived/                     # Generated outputs (regeneratable)
│   ├── chunks/                 # Chunked documents
│   │   └── chunks.jsonl        # All chunks in JSONL format
│   └── clean/                  # Optional: cleaned source copies (if needed)
├── reports/                     # Analysis and validation reports
│   ├── corpus_audit.md         # Corpus discovery findings
│   ├── term_variants.md        # OCR artifact detection
│   ├── chunking_stats.md       # Chunking statistics
│   └── validation_report.md    # Validation results
└── scripts/                     # Pipeline scripts
    ├── discovery.py            # Corpus discovery
    ├── extract_book_metadata.py # Book metadata extraction
    ├── build_glossary.py       # Glossary building
    ├── chunk.py                # Chunking script
    ├── match_glossary.py       # Glossary term matching
    ├── build_indexes.py        # Index generation
    ├── validate.py             # Validation checks
    └── generate_stats.py       # Statistics generation
```

## Quick Start

### Prerequisites

- Python 3.8+
- Required packages:
  ```bash
  pip install pyyaml jsonschema
  ```

### Running the Pipeline

The pipeline is designed to be run in order:

1. **Discovery**: Inventory source documents
   ```bash
   python rag/scripts/discovery.py
   ```

2. **Book Metadata**: Extract book metadata
   ```bash
   python rag/scripts/extract_book_metadata.py
   ```

3. **Glossary Building**: Build controlled vocabulary
   ```bash
   python rag/scripts/build_glossary.py
   ```

4. **Chunking**: Split documents into chunks
   ```bash
   python rag/scripts/chunk.py
   ```

5. **Glossary Matching**: Match terms in chunks
   ```bash
   python rag/scripts/match_glossary.py
   ```

6. **Index Building**: Build concept and book indexes
   ```bash
   python rag/scripts/build_indexes.py
   ```

7. **Validation**: Validate output
   ```bash
   python rag/scripts/validate.py
   ```

8. **Statistics**: Generate statistics
   ```bash
   python rag/scripts/generate_stats.py
   ```

## Pipeline Components

### 1. Discovery Phase

**Script**: `scripts/discovery.py`

Analyzes the source corpus and generates a discovery report.

**Output**: `reports/corpus_audit.md`

**What it does**:
- Scans source directory for markdown files
- Analyzes file structure (headings, page breaks, credits)
- Detects system tags and metadata
- Documents findings and risks

### 2. Book Metadata Extraction

**Script**: `scripts/extract_book_metadata.py`

Extracts metadata for each book and creates the book index.

**Output**: `index/book_index.json`

**What it does**:
- Extracts titles from filenames and headings
- Detects system (Mind's Eye Theatre, World of Darkness, etc.)
- Extracts credits (authors, editors, developers)
- Creates book IDs (slugs)

### 3. Glossary Building

**Script**: `scripts/build_glossary.py`

Builds a controlled vocabulary from the corpus.

**Output**: `glossary.yml`, `reports/term_variants.md`

**What it does**:
- Extracts candidate terms from headings, bold text, capitalized phrases
- Detects OCR artifacts (spacing splits, case variants)
- Creates canonical terms with aliases
- Documents variant patterns

### 4. Chunking

**Script**: `scripts/chunk.py`

Splits documents into retrieval-optimized chunks.

**Output**: `derived/chunks/chunks.jsonl`

**Chunking Strategy**:
- **Boundaries**: Prefer splits at H1, H2, H3 headings
- **Size targets**: 300-900 tokens per chunk (aim), 50-1500 tokens (range)
- **Chunk IDs**: Deterministic format: `{book_slug}_{hash}_{start_line}`
- **Anchors**: URL-friendly slugs from headings
- **Metadata**: Heading paths, quality flags, token counts

### 5. Glossary Matching

**Script**: `scripts/match_glossary.py`

Matches glossary terms against chunk content.

**Output**: Updates `derived/chunks/chunks.jsonl`

**What it does**:
- Matches canonical terms and aliases in chunk content
- Populates `canonical_terms` and `aliases_matched` fields
- Uses word-boundary matching (case-insensitive)

### 6. Index Building

**Script**: `scripts/build_indexes.py`

Builds concept and book indexes.

**Output**: `index/concept_index.json`, updated `index/book_index.json`

**What it does**:
- Creates concept index mapping terms to chunk occurrences
- Includes context snippets (50 chars before/after)
- Updates book index with chunk statistics

### 7. Validation

**Script**: `scripts/validate.py`

Validates output against schemas and business rules.

**Output**: `reports/validation_report.md`

**Validation Rules**:
- Schema validation (JSON Schema)
- No duplicate chunk IDs
- No duplicate anchors within same book
- Chunk size validation (50-1500 tokens)
- Valid book references
- Valid glossary references
- Heading paths present (except intro chunks)

### 8. Statistics

**Script**: `scripts/generate_stats.py`

Generates chunking statistics.

**Output**: `reports/chunking_stats.md`

**Statistics**:
- Overall token statistics (min, max, mean, median, stdev)
- Size distribution
- Quality flags distribution
- Tags distribution
- Per-book statistics
- Outliers

## Configuration

### Exclusions

Source documents are filtered to include only:
- `reference/Books_md_ready_fixed_cleaned/*.md` (43 files)

Excluded:
- `archive/` - Historical versions
- `reference/Books_md/`, `Books_md_clean/`, `Books_md_ready/`, `Books_md_ready_fixed/` - Earlier processing stages
- `reference/Scenes/`, `Characters/`, `Locations/` - Game content, not rulebooks

## Updating the Glossary

To add or modify glossary entries:

1. Edit `glossary.yml`
2. Run glossary matching: `python rag/scripts/match_glossary.py`
3. Rebuild indexes: `python rag/scripts/build_indexes.py`
4. Validate: `python rag/scripts/validate.py`

### Glossary Entry Format

```yaml
terms:
  - term: "Camarilla"
    aliases: ["camarilla", "CAMARILLA", "Cam arilla"]  # Include canonical term and variants
    short_definition: "The primary sect of vampires"
    related_terms: ["Sabbat", "Anarch", "Prince"]
    tags: ["faction", "sect"]
    source_examples: []
```

## Regenerating Chunks

If source files change:

1. Run chunking: `python rag/scripts/chunk.py`
2. Match glossary: `python rag/scripts/match_glossary.py`
3. Rebuild indexes: `python rag/scripts/build_indexes.py`
4. Validate: `python rag/scripts/validate.py`
5. Generate statistics: `python rag/scripts/generate_stats.py`

**Note**: Chunk IDs are deterministic - unchanged source files will produce the same chunk IDs.

## Troubleshooting

### Chunking Issues

**Problem**: Chunks are too large or too small

**Solution**: Adjust chunking parameters in `scripts/chunk.py`:
- `TARGET_MIN_TOKENS`: Minimum target size (default: 300)
- `TARGET_MAX_TOKENS`: Maximum target size (default: 900)
- `HARD_MAX_TOKENS`: Hard maximum (default: 1500)

**Problem**: Chunks split mid-section

**Solution**: This is expected behavior. Chunks split at headings when size limits are exceeded. Review chunking logic if needed.

### Glossary Issues

**Problem**: Terms not matching in chunks

**Solution**:
1. Check if term aliases are correct in `glossary.yml`
2. Verify word-boundary matching in `scripts/match_glossary.py`
3. Check for OCR artifacts (see `reports/term_variants.md`)

**Problem**: Too many or too few terms

**Solution**: Adjust frequency threshold in `scripts/build_glossary.py`:
- `min_frequency`: Minimum occurrence count (default: 3)

### Validation Issues

**Problem**: Schema validation errors

**Solution**:
1. Check schema files in `schema/`
2. Verify chunk format matches schema
3. Review validation report for specific errors

**Problem**: Duplicate chunk IDs

**Solution**: This should not happen with deterministic IDs. If it occurs, check:
1. Hash collision (extremely rare)
2. Source file changes between runs
3. Chunk ID generation logic

### Performance Issues

**Problem**: Scripts run slowly

**Solution**:
- Processing 2,788 chunks takes time (~1-2 minutes for full pipeline)
- Consider parallelizing if needed
- Use streaming for very large datasets

## Data Formats

### Chunk Format (JSONL)

Each line in `chunks.jsonl` is a JSON object:

```json
{
  "chunk_id": "lotnr_abc123def_45",
  "source_path": "reference/Books_md_ready_fixed_cleaned/LotNR.md",
  "source_book": "lotnr",
  "title": "Introduction",
  "heading_path": ["Chapter One", "Introduction"],
  "anchor": "introduction",
  "start_line": 45,
  "end_line": 120,
  "system": "Mind's Eye Theatre",
  "tags": ["mechanics", "reference"],
  "canonical_terms": ["Camarilla", "Kindred"],
  "aliases_matched": ["camarilla", "kindred"],
  "quality_flags": [],
  "token_count_estimate": 650,
  "content": "Full chunk text content..."
}
```

### Glossary Format (YAML)

```yaml
terms:
  - term: "Camarilla"
    aliases: ["camarilla", "CAMARILLA"]
    short_definition: "The primary sect of vampires"
    related_terms: ["Sabbat", "Anarch"]
    tags: ["faction", "sect"]
    source_examples: []
```

### Concept Index Format (JSON)

```json
{
  "Camarilla": {
    "canonical": "Camarilla",
    "occurrences": [
      {
        "chunk_id": "lotnr_abc123def_45",
        "book_id": "lotnr",
        "anchor": "introduction",
        "context": "...the Camarilla maintains the Masquerade..."
      }
    ]
  }
}
```

## Dependencies

- **Python**: 3.8+
- **pyyaml**: YAML parsing for glossary
- **jsonschema**: Schema validation

Install dependencies:

```bash
pip install pyyaml jsonschema
```

## Version Control

**Tracked in Git**:
- `schema/` - JSON Schema definitions
- `glossary.yml` - Glossary file
- `index/` - Index files
- `README.md` - Documentation
- `scripts/` - Pipeline scripts
- `reports/` - Reports

**Not Tracked** (add to `.gitignore`):
- `derived/` - Regeneratable outputs (can be deleted and recreated)

## Risk Management

**Non-Destructive**:
- Source files in `reference/Books_md_ready_fixed_cleaned/` are **NOT modified**
- All outputs go to `rag/` directory
- Pipeline is deterministic (same inputs → same outputs)

**Rollback**:
- Delete `rag/` folder to start fresh
- Regenerate from source files
- All processing is repeatable

**Risks**:
- **Low risk**: Creating `rag/` folder structure and derived outputs
- **Medium risk**: Glossary normalization may miss some variants (iterative process)
- **Low risk**: Chunking may occasionally split tables/lists (validate and adjust rules)
- **No risk**: Source files untouched

## Success Criteria

✅ All 43 books indexed in `book_index.json`
✅ Glossary with 3,800+ canonical terms and aliases
✅ All books chunked with stable IDs (2,788 chunks)
✅ Concept index mapping terms to chunks
✅ Validation passes (check `reports/validation_report.md`)
✅ Documentation complete and actionable
✅ Pipeline can be re-run deterministically

## License

This pipeline is for processing game rulebooks. See project license for details.
