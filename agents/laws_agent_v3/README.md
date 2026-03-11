# Laws Agent v3

`laws_agent_v3` answers rules/lore questions using **Cloudflare AI Search (AutoRAG) only**: same process as the MCP `ai_search` tool. No Workers AI.

## Process (same as MCP)

- **RAG:** `laws-agent` (configurable via `LAWS_AGENT_RAG_NAME` or `CF_AUTORAG_NAME`, default `laws-agent`)
- **Query:** The user’s natural-language question (optionally with previous Q/A for follow-up)
- **Endpoint:** Cloudflare `ai-search` (AutoRAG); returns synthesized answer (and sources in the web API)

See [MCP_AI_SEARCH_USAGE.md](MCP_AI_SEARCH_USAGE.md) for the MCP flow. The web API (`api_query.php`) uses the same process.

## Web Interface

- **URL**: `http://192.168.0.155/agents/laws_agent_v3/` (or your app base + `/agents/laws_agent_v3/`)
- **Access**: Any logged-in user
- **Features**: Ask VTM/MET rules questions, view answers with citations, "Ask for More Information" follow-up with conversation context

## API Endpoint

**POST** `api_query.php`

```json
{
  "question": "What are the Traditions?",
  "previous_question": "Optional - for follow-up",
  "previous_answer": "Optional - for follow-up"
}
```

**Response** (success):

```json
{
  "success": true,
  "question": "...",
  "answer": "...",
  "sources": [{"book": "...", "page": 1, "category": "...", "system": "..."}],
  "ai_model": "Cloudflare AI Search",
  "searched": true,
  "results_found": 10
}
```

## MCP (Cursor Integration)

- **Server:** `project-0-v:-cloudflare-ai-search` (Cloudflare AutoRAG MCP)
- **Tool:** `ai_search` with **rag_id:** `laws-agent`, **query:** natural-language question (e.g. "describe the Brujah")
- Same process as the web API; MCP returns only the synthesized answer (no sources in the tool response).

## Environment (.env)

**Required (Cloudflare):**

- **AI Search:** `CF_FUCKING_7th_API` or `CLOUDFLARE_API_TOKEN` – must be the token created **from the instance** (open your RAG, e.g. **laws-agent**, then Copy/Create API Token there; the main AI Search “+ Create” only creates new RAGs). A token from My Profile → API Tokens can verify but still return `[10000]` on the AI Search endpoint. See [MCP_AI_SEARCH_USAGE.md](MCP_AI_SEARCH_USAGE.md) (“Making the RAG work”).
- **Account resolution (if CF_ACCOUNT_ID not set):** `CLOUDFLARE_API_TOKEN` or `CLOUDFLARE_EMAIL` + `CLOUDFLARE_API_KEY`.

**Required for PHP:** `CF_ACCOUNT_ID` – if not set, the API tries to resolve it from the token (GET /accounts). Set in .env if resolution fails.

**Optional:** `LAWS_AGENT_RAG_NAME` or `CF_AUTORAG_NAME` (default `laws-agent`).

Use **GET** `api_query.php?debug=1` to see which env vars are set.
