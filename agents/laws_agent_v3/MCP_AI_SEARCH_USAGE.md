# Cloudflare AI Search (laws-agent)

## Tokens (two kinds)

| Token | Use | Where |
|-------|-----|--------|
| **Service API token** | Indexing (R2, Vectorize, Workers AI). Created automatically when you create the instance via dashboard. Cloudflare docs say it can be managed at instance → **Settings** → **General** → **Service API Token** — that section is not visible in all accounts/dashboard versions. |
| **AI Search API token** | Your REST/MCP requests (query) | **My Profile → API Tokens** → Create Token → **Account** → **AI Search** → Read (+ Edit) |

Put the **AI Search API token** in `.env` as `CLOUDFLARE_API_TOKEN` or `CF_FUCKING_7th_API`. You do **not** create extra profile API tokens for indexing.

## Supported file formats (R2 source)

**Limit:** 4 MB per file. Larger or unsupported types are skipped and show as errors.

**Plain text (indexed as-is):** `.txt` `.rst` `.log` `.ini` `.conf` `.env` `.toml` `.md` `.markdown` `.mdx` `.tex` `.json` `.yaml` `.yml` `.css` `.js` `.php` `.py` `.rb` `.java` `.c` `.cpp` `.h` `.go` `.rs` `.swift` `.dart` `.sh` `.bat` `.ps1` `.sgml` `.el`

**Rich (converted to markdown for indexing):** `.pdf` `.html` `.htm` `.xml` `.docx` `.xlsx` `.xls` `.xlsm` `.xlsb` `.odt` `.ods` `.csv` `.numbers` `.jpeg` `.jpg` `.png` `.webp` `.svg`

Source: [R2 data source](https://developers.cloudflare.com/ai-search/configuration/data-source/r2/). If the dashboard says "can't display markdown", that's a UI bug — **Markdown (`.md`, `.markdown`, `.mdx`) is supported** for indexing.

## REST API (PHP)

`POST https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/autorag/rags/{AUTORAG_NAME}/ai-search`  
Headers: `Authorization: Bearer {AI_SEARCH_API_TOKEN}`, `Content-Type: application/json`  
Body: `{"query": "...", "rewrite_query": false, "max_num_results": 10, "ranking_options": {"score_threshold": 0.1}}`

## MCP

- **Server:** `project-0-v:-cloudflare-ai-search` (or `https://autorag.mcp.cloudflare.com/mcp`)
- **Tool:** `ai_search` — params: `rag_id` (use `laws-agent`), `query` (string)

## Sync vs indexing (what the job actually does)

**Per Cloudflare docs:** **Sync Index** is supposed to trigger the full pipeline: scan data source → ingest files → convert to markdown → chunk → embed (Workers AI) → store in Vectorize. It is not only an "existence check".

**What we see in the job log:** The log often shows only "Starting indexing data source...", "Getting source page/batch 1", "Checking if files still exists" (batches of 10), then "Finished checking file existence". There are no log lines for ingestion, chunking, or embedding. So either (1) the pipeline fails right after the existence check and the UI does not show why, or (2) the existence check fails for every file (e.g. R2 path or permissions wrong) so the job never gets to actually index. The "N Errored" count is the number of files that failed; the dashboard does not show the reason per file in the job modal.

## Indexing broken (zero files, N errors)

**In the dashboard only:**

1. **Jobs** — Open the latest job and read the log. It usually does not show why each file failed.
2. **Overview** — Look for **Indexed Items** or a list of documents; that may show which files failed and sometimes a status or reason.
3. **Settings** (or **Data source**) — Check that the R2 bucket name and path/prefix are correct and point at the folder that has your files. If the path is wrong, the job sees nothing (or the wrong files) and indexing never runs.
4. **File size** — Over 4 MB or unsupported format → file is skipped (see supported formats above).

If the dashboard never shows a clear reason for the errors, the only remaining option is **Cloudflare support** (e.g. open a ticket and give them the instance name, that you get "N Errored" and the job log only shows "Checking if files still exists", and ask how to get the actual failure reason or fix indexing).

## Related

- Web API: **POST** `api_query.php` (same process). Env: `CF_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` or `CF_FUCKING_7th_API`; optional `LAWS_AGENT_RAG_NAME` (default `laws-agent`).
- [README](README.md)
