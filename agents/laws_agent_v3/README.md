# Laws Agent v3

`laws_agent_v3` uses Cloudflare only: AI Search (AutoRAG) for retrieval when configured, Workers AI for answer synthesis.

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
  "ai_model": "claude-sonnet-4-20250514"
}
```

## MCP (Cursor Integration)

- Config entry: `laws-agent-v3` in `.cursor/mcp.json`
- Remote MCP URL: `https://autorag.mcp.cloudflare.com/mcp`
- Runtime server identifier may differ from the config key.
  In this workspace, the working runtime identifier was `project-0-v:-cloudflare-ai-search`.

### MCP Tool Flow

1. Call `accounts_list`
2. Call `set_active_account` with `activeAccountIdParam: "76b8ec41079de44fa4c882753cb0f5e6"`
3. Call `search` with `rag_id: "laws-agent"`, `query: "<rules question>"`

## Environment (.env)

**Required (Cloudflare):**

- `CLOUDFLARE_API_TOKEN` – API token with Workers AI access

**Optional:** `CF_ACCOUNT_ID` – if not set, the API will try to resolve it from the token (GET /accounts). Set it in .env if resolution fails.

**Optional (for rulebook RAG):** If `CF_AUTORAG_NAME` is set, answers use Cloudflare AI Search context. If not set, the LLM answers without rulebook citations.

- `CF_AUTORAG_NAME` (e.g. `ai-search-laws-agent`)

**Optional:** `CF_WORKERS_AI_MODEL` (default `@cf/meta/llama-3.1-8b-instruct`).

Use **GET** `api_query.php?debug` to see which vars are set.
