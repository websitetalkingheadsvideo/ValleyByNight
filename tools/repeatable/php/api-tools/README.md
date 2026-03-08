# API Tools

Reusable PHP tools that call external APIs (e.g. Cloudflare).

## cloudflare_dns_proxy_status.php

**Purpose:** List Cloudflare zones and DNS records and show whether each record is **proxied** (orange cloud) or DNS-only. Use this to confirm your site is behind Cloudflare so you can host with a dynamic IP: traffic hits Cloudflare, which connects to your origin; when your IP changes, update the A record in Cloudflare.

**Usage:**

```bash
# All zones
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php

# One zone only
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php --zone=vbn-game.com

# Help
php tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php --help
```

**Web:** Open in browser (same directory as script):

- `tools/repeatable/php/api-tools/cloudflare_dns_proxy_status.php`
- Optional: `?zone=vbn-game.com` to filter zone

**Inputs:**

- `CLOUDFLARE_API_TOKEN` – from `.env` (project root) or environment. Use an API Token with **Zone: Zone Read**, **DNS: DNS Read**.
- `--zone=NAME` (CLI) or `?zone=NAME` (web) – optional; only show that zone.

**Output:** For each zone, a list of DNS records with Type, Name, Content, and **Proxied** (yes = orange cloud). No DB or file writes.

**Dependencies:** PHP 7.4+, `file_get_contents` with HTTP (allow_url_fopen). No database.
