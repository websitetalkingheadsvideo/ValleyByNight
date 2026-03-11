# Cloudflare AI Search (laws-agent)

## Tokens (two kinds)

| Token | Use | Where |
|-------|-----|--------|
| **Service API token** | Indexing (R2, Vectorize, Workers AI) | Instance → **Settings** → **General** → **Service API Token** → edit → **Create a new token** → **Save** |
| **AI Search API token** | Your REST/MCP requests (query) | **My Profile → API Tokens** → Create Token → **Account** → **AI Search** → Read (+ Edit) |

Put the **AI Search API token** in `.env` as `CLOUDFLARE_API_TOKEN` or `CF_FUCKING_7th_API`.

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

## Indexing broken (zero files, N errors)

1. **Jobs tab** on the instance — check the latest job log for per-file errors.
2. **Service API token** — recreate in **Settings** → **General** → **Service API Token** (edit → Create a new token → Save). Then **Sync Index**.
3. **Data source** — R2 bucket/keys or website URL still valid.
4. **File size** — over 4 MB or unsupported format → skipped (see supported formats above).

## Related

- Web API: **POST** `api_query.php` (same process). Env: `CF_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` or `CF_FUCKING_7th_API`; optional `LAWS_AGENT_RAG_NAME` (default `laws-agent`).
- [README](README.md)
