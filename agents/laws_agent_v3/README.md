# Laws Agent v3

`laws_agent_v3` uses the Cloudflare AI Search MCP server directly.

## Actual MCP Path

- Config entry: `laws-agent-v3` in `.cursor/mcp.json`
- Remote MCP URL: `https://autorag.mcp.cloudflare.com/mcp`
- Runtime server identifier may differ from the config key.
  In this workspace, the working runtime identifier was `project-0-v:-cloudflare-ai-search`.

## Working Tool Flow

1. Call `accounts_list`
2. Call `set_active_account` with:
   - `activeAccountIdParam: "76b8ec41079de44fa4c882753cb0f5e6"`
3. Call `search` with:
   - `rag_id: "laws-agent"`
   - `query: "<rules question>"`

## Important Notes

- The searchable AI Search name is `laws-agent`.
- The previously attempted direct REST path and the fake RAG name `ai-search-laws-agent` were wrong for this workspace.
- If tool calls fail, verify the runtime server identifier exposed by Cursor before assuming the `.cursor/mcp.json` key is the callable server name.
