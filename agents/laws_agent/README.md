# VbN Laws Agent RAG System

A Retrieval-Augmented Generation (RAG) system for the Vampire: The Masquerade / Mind's Eye Theatre Laws Agent.

## Features

✅ **Hybrid Search**: Combines semantic embeddings with keyword search for best results
✅ **LM Studio Primary**: Uses local LM Studio for fast, free responses
✅ **Claude Fallback**: Automatically falls back to Claude API when LM Studio is unavailable
✅ **Multi-Book Support**: Designed to handle 40+ rulebooks
✅ **Conversation Memory**: Maintains context within user sessions
✅ **Source Citations**: Every answer includes page numbers and book references
✅ **Book Filtering**: Users can search within specific books
✅ **Optimized Database**: Uses MySQL with embeddings stored as binary blobs

## Architecture

```
User Question
    ↓
Generate Query Embedding (simple TF-IDF)
    ↓
Hybrid Search (Semantic + Keyword)
    ↓
Retrieve Top 5 Documents
    ↓
Build Context
    ↓
Try LM Studio (http://192.168.0.217:1234)
    ↓ (on failure)
Fallback to Claude API
    ↓
Return Answer + Sources
```

## Database Schema

### Tables:
1. **rag_books** - Metadata about each rulebook
2. **rag_documents** - Document chunks with FULLTEXT index
3. **rag_embeddings** - 1024-dimensional embeddings (binary)
4. **rag_conversations** - User chat history
5. **rag_user_preferences** - User settings

## Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- LM Studio running on http://192.168.0.217:1234
- Anthropic API key in .env file

### Step 1: Setup Database

```bash
cd /path/to/your/agents/folder
php setup_rag_database.php
```

This will:
- Drop old laws_agent tables
- Create new optimized RAG tables
- Set up indexes for fast searching

### Step 2: Import Data

```bash
php import_rag_data.php /path/to/rag_documents.json
```

This will:
- Read the JSON file (247 documents)
- Create book record in database
- Import all documents
- Generate embeddings for each document (using simple TF-IDF fallback)
- Takes ~1-2 minutes for the first book

**Expected output:**
```
=== RAG Data Import ===

Step 1: Loading JSON file...
  ✓ Loaded 247 documents

Step 2: Processing book metadata...
  Book: Laws of the Night - Vampire the Masquerade
  Code: LOTN-VTM
  Pages: 271
  Chunks: 247

Step 3: Creating book record...
  ✓ Book ID: 1

Step 4: Importing documents and generating embeddings...
  Progress: 247/247 (100.0%) - 4.2 docs/sec - ETA: 0 sec

=== Import Complete ===
  ✓ Successfully imported: 247 documents
  ⏱ Total time: 58.73 seconds
  📊 Average: 4.21 docs/sec
```

### Fixing spelling and mid-word caps in Books JSON

The Books JSON files may contain OCR-style artifacts: **mid-word uppercase** (e.g. `saBBat`, `masQuerade`, `aCCounting`) and **common spelling errors**. A Python script fixes these in place; then re-import to update the RAG database.

**What it does**

1. **Mid-word uppercase**: Only the first letter of each word stays uppercase; the rest are lowercased (e.g. `saBBat` → `Sabbat`, `destruCtion` → `destruction`).
2. **Spelling**: Applies a built-in list of common corrections (e.g. `teh` → `the`, `recieve` → `receive`). WoD/MET terms (Camarilla, Sabbat, Kindred, Toreador, etc.) are whitelisted and never changed.

**How to use**

1. **Fix the JSON files** (run from `Books/` or project root):
   ```bash
   cd agents/laws_agent/Books
   python fix_spelling_and_caps.py
   ```
   This updates every `*.json` in `Books/`; it only modifies the `content` field of each document. The script prints how many fields were updated per file.

2. **Update the RAG database** with the fixed content (and refreshed embeddings):
   - **CLI:** `php agents/laws_agent/import_books.php` (no browser; runs import only).
   - **Browser:** Log in, open [Import books](http://192.168.0.155/agents/laws_agent/import_books.php), wait for the run to finish.

**Files**

- `Books/fix_spelling_and_caps.py` – script (no extra dependencies).
- `Books/fix_ocr_artifacts.py` – separate script for other OCR fixes (doubled letters, word fusions, trailing garbage).

#### Import more books (browser)

When you have more RAG JSON books ready in `Books/`, run the browser importer to load (or update) all of them in one go:

- **URL:** http://192.168.0.155/agents/laws_agent/import_books.php
- **When:** After adding or updating `.json` files in `agents/laws_agent/Books/`
- **How:** Log in (same session as Laws Agent), open the URL, wait for the page to finish. It scans `Books/` for `*.json` (skips `backups/`), derives book metadata from each file, and imports documents + embeddings. Existing books (same `book_code`) are updated.
- **Link:** The Laws Agent index page has an "Import books" link in the status bar that goes to this URL.

### Step 3: Deploy Files

Copy the files to your agents folder:

```bash
# Assuming your agents folder is at V:\agents\

cp setup_rag_database.php V:\agents\
cp import_rag_data.php V:\agents\
cp rag_functions.php V:\agents\
cp api.php V:\agents\
cp index.php V:\agents\
```

### Step 4: Test

1. Navigate to http://192.168.0.155/agents/
2. Login with verified email
3. Ask a question!

## Adding More Books

To add additional books (you mentioned 40 total):

### Option 1: Single JSON File (Recommended)
If you have all books in one JSON file with consistent structure:

```bash
php import_rag_data.php /path/to/all_books.json
```

### Option 2: Multiple JSON Files
If each book is a separate file:

**Modify the import script** to accept multiple files:

```bash
# For each book
php import_rag_data.php /path/to/book1.json
php import_rag_data.php /path/to/book2.json
# ... etc
```

The script will automatically:
- Detect duplicate book codes and update instead of creating new records
- Create new book records for new book codes
- Generate embeddings for all new documents

### Book Identification

Make sure each book has unique metadata in the JSON:

```json
{
  "metadata": {
    "source": "Book Title Here",
    "category": "Core|Faction|Supplement|Blood Magic|Journal",
    "system": "MET-VTM|MET|VTM|MTA"
  }
}
```

The system will automatically create a `book_code` based on the source name.

## Configuration

### LM Studio (current UI / API)

**Starting the server (LM Studio updated):**

- **In the app:** Open the **Developer** tab (not "Local Server"). Toggle **"Start server"** at the top left. Default URL: `http://localhost:1234`.
- **From terminal:** `lms server start` (optional: `--port 3000`, `--cors` for web apps). If no port is set, it uses the last used port (often 1234).

**This project:**

- Endpoint: `http://192.168.0.217:1234/v1/chat/completions` (OpenAI-compatible). To change host/port: edit `rag_functions.php`, `query_lm_studio()`, `$lm_studio_url`.
- Model in code: `meta-llama-3.1-8b-instruct`. The **model name must match exactly** what is loaded in LM Studio (or the catalog id, e.g. `ibm/granite-4-micro`). If you use a different model, change the `model` key in the request in `rag_functions.php`.
- **Optional auth:** If you enable API token in LM Studio → Developer → Server settings, set `LM_STUDIO_API_TOKEN` in your environment; the code can send `Authorization: Bearer <token>` (see `rag_functions.php`).

### Claude API
- Requires `ANTHROPIC_API_KEY` in `.env` file
- Model: `claude-3-5-sonnet-20241022`
- Used only as fallback when LM Studio fails

### Search Parameters
- Default results per query: 5 documents
- Keyword weight: 40%
- Semantic weight: 60%
- To adjust: Edit `rag_functions.php`, line 124

## Embedding System

### Current Implementation
The system uses a simple TF-IDF based embedding as a fallback:
- 1024 dimensions
- Fast generation (~50 docs/sec)
- Good for keyword matching
- Works offline

### Upgrading to Better Embeddings (Optional)

For improved semantic search, consider:

1. **OpenAI Embeddings** (text-embedding-3-small)
   - 1536 dimensions
   - $0.02 per 1M tokens
   - Better semantic understanding

2. **Cohere Embeddings** (embed-english-v3.0)
   - 1024 dimensions
   - Free tier available
   - Good for document retrieval

3. **Local Models** (via Ollama/LM Studio)
   - nomic-embed-text
   - Free
   - Run locally
   - 768 dimensions

To implement better embeddings:
1. Update `create_simple_embedding()` in `import_rag_data.php`
2. Update `create_simple_embedding()` in `rag_functions.php`
3. Re-import all data

## Performance

### Current Stats (1 Book, 247 Documents)
- Database size: ~2.5 MB
- Import time: ~60 seconds
- Query time: ~2-5 seconds (LM Studio)
- Query time: ~3-8 seconds (Claude fallback)

### Projected (40 Books, ~10,000 Documents)
- Database size: ~100 MB (well within 1GB limit)
- Import time: ~40 minutes total
- Query time: Same (search is indexed)

## Troubleshooting

### LM Studio Not Responding
1. Start the server: **Developer** tab → toggle **"Start server"**, or run `lms server start`. Default: `http://localhost:1234`.
2. If this app uses another machine: ensure LM Studio is reachable at the URL in `rag_functions.php` (e.g. http://192.168.0.217:1234). Test: open http://192.168.0.217:1234/v1/models in a browser (or the same URL as in code).
3. Model name in code must match the model loaded in LM Studio (or its catalog id).
4. If you enabled API token in LM Studio, set `LM_STUDIO_API_TOKEN` and add the Bearer header in `query_lm_studio()`.
5. System will automatically fallback to Claude when LM Studio fails.

### Claude API Errors
1. Check `.env` file has `ANTHROPIC_API_KEY=your-key-here`
2. Verify API key at https://console.anthropic.com/
3. Check error logs

### No Search Results
1. Verify data was imported: `SELECT COUNT(*) FROM rag_documents;`
2. Check embeddings exist: `SELECT COUNT(*) FROM rag_embeddings;`
3. Try simpler questions to test

### Slow Queries
1. Check MySQL indexes are created (see setup_rag_database.php)
2. Consider upgrading embedding quality
3. Reduce `limit` parameter in search functions

## File Structure

```
agents/
├── index.php               # Main UI (updated)
├── api.php                 # API endpoint (new)
├── rag_functions.php       # Search & AI functions (new)
├── setup_rag_database.php  # Database setup (run once)
├── import_rag_data.php     # Data import (run per book)
└── README.md              # This file
```

## Database Maintenance

### View Statistics
```sql
-- Total documents
SELECT book_code, COUNT(*) as docs, SUM(LENGTH(content)) as bytes 
FROM rag_documents d 
JOIN rag_books b ON d.book_id = b.id 
GROUP BY book_code;

-- Most queried topics
SELECT LEFT(question, 50) as question, COUNT(*) as count
FROM rag_conversations 
GROUP BY LEFT(question, 50) 
ORDER BY count DESC 
LIMIT 10;
```

### Clear All Data (Fresh Start)
```bash
php setup_rag_database.php  # This drops and recreates tables
```

## Future Enhancements

- [ ] Upgrade to better embedding model
- [ ] Add document upload via web interface
- [ ] Implement semantic caching for common queries
- [ ] Add admin panel for managing books
- [ ] Export conversation history
- [ ] Support for images/diagrams from PDFs
- [ ] Multi-language support

## Support

For issues or questions, check:
1. Database logs in MySQL
2. PHP error logs
3. Browser console for frontend errors
4. LM Studio logs

## Credits

- Built for VbN game system
- Uses Anthropic Claude API
- Integrates with LM Studio
- Designed for 40+ book scalability
