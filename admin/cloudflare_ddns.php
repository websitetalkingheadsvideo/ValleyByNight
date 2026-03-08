<?php
/**
 * Run Cloudflare DDNS check once (same as cron job). For testing or manual run.
 * Logs to tools/repeatable/cloudflare_ddns.log or admin/cloudflare_ddns.log
 */
require_once __DIR__ . '/../tools/repeatable/php/api-tools/cloudflare_ddns.php';
