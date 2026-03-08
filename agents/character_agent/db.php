<?php
declare(strict_types=1);

// character_agent uses Supabase only
require_once __DIR__ . '/../../includes/supabase_client.php';

/** @deprecated Do not use; character_agent uses Supabase directly. */
function vbn_get_connection(): void {
    throw new RuntimeException('MySQL removed. Character agent uses Supabase.');
}
