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

- Laws Agent v3 also uses Cloudflare AI Search via PHP in `api_query.php` (REST API) when `CF_AUTORAG_NAME` is set in `.env`.
- README: [agents/laws_agent_v3/README.md](README.md).
