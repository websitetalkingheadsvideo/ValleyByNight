# PHP Tools

Reusable PHP tools for MCP management, database operations, and data reporting.

## Categories

### [MCP Tools](mcp-tools/README.md)
Tools for managing and verifying MCP (Model Context Protocol) configuration files and directory structures.

### [Database Tools](database-tools/README.md)
Reusable tools for database operations including imports, exports, audits, and maintenance.

### [Data Tools](data-tools/README.md)
Tools for generating reports, summaries, and data analysis from the database.

### [API Tools](api-tools/README.md)
Tools that call external APIs (e.g. Cloudflare DNS proxy status).

## Common Dependencies

All PHP tools require:
- PHP 7.4+
- Database connection (via `includes/connect.php`)
- See individual tool README files for specific dependencies

## Database Connection

All database tools require database connection configured in `../../includes/connect.php`. Ensure database credentials are properly configured before running tools.
