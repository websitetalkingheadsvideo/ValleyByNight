<?php
declare(strict_types=1);

// db.php - Style Agent uses Supabase for mcp_style_packs; no MySQL.
require_once __DIR__ . '/../../includes/supabase_client.php';

/** @deprecated Use supabase_table_get('mcp_style_packs', ...) instead. */
function vbn_get_connection(): void {
    throw new RuntimeException('MySQL removed. Style Agent uses Supabase.');
}
