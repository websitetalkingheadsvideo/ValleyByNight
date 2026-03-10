# Using Cloudflare AI Search via MCP

To run a search from Cursor (or any MCP client) using the Cloudflare AI Search AutoRAG:

## MCP server

- **Server:** `project-0-v:-cloudflare-ai-search` (Cloudflare AutoRAG MCP at `https://autorag.mcp.cloudflare.com/mcp`)

## Tool: `ai_search`

- **Purpose:** AI Search over documents in an AutoRAG (vector store).
- **Parameters (both required):**
  - `rag_id` (string): ID of the AutoRAG to search. For this project use **`laws-agent`**.
  - `query` (string): Natural-language question or search phrase (e.g. a rules/lore question).

## Example

Search for “What best describes the Nosferatu?”:

- **rag_id:** `laws-agent`
- **query:** `What best describes the Nosferatu?`

The tool returns a single text answer synthesized from the RAG content (no raw chunks or citations in the response).

## Finding the tool schema

MCP tool descriptors live under the workspace `mcps` folder. For this server:

- `mcps/project-0-v-cloudflare-ai-search/tools/ai_search.json` — defines `rag_id` and `query` (both required).

## Related

- **Laws Agent v3 (same process):** The web UI and **POST** `api_query.php` use this exact process (rag_id `laws-agent`, query = user question). They call the same Cloudflare `ai-search` endpoint; API returns answer plus sources. Env: `CLOUDFLARE_API_TOKEN` or `CLOUDFLARE_EMAIL` + `CLOUDFLARE_API_KEY`; `CF_ACCOUNT_ID` (required for PHP); optional `LAWS_AGENT_RAG_NAME` or `CF_AUTORAG_NAME` (default `laws-agent`). Token must have **AI Search - Read** (or use “Copy API Token” from the AI Search page in the dashboard).
- README: [agents/laws_agent_v3/README.md](README.md).
