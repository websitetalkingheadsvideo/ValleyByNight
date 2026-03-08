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

---

## cloudflare_ddns.php

**Purpose:** Dynamic DNS: get current public IPv4, compare to Cloudflare A record for the zone/name; update the record only if the IP changed. Keeps proxy (orange cloud) setting unchanged. For use with a dynamic IP and a daily cron job.

**Usage (CLI, e.g. cron):**

```bash
php tools/repeatable/php/api-tools/cloudflare_ddns.php
```

**Web (manual run):** `http://192.168.0.155/admin/cloudflare_ddns.php`

**Environment (.env):**

- **Auth (one of):** `CLOUDFLARE_API_TOKEN` **or** `CLOUDFLARE_EMAIL` + `CLOUDFLARE_API_KEY`
- **Optional:** `CLOUDFLARE_DDNS_ZONE` (default: `vbn-game.com`), `CLOUDFLARE_DDNS_NAME` (default: `vbn-game.com`)

**Behaviour:**

1. Resolve public IPv4 (icanhazip.com, then api.ipify.org, then ifconfig.me/ip).
2. Get zone and A record from Cloudflare API.
3. If public IP equals DNS record content → log "no_change", exit 0.
4. If different → PATCH the A record to the new IP (proxied unchanged), log "updated", exit 0.
5. On API/network error → log "ERROR: ...", exit 1.

**Log file:** `tools/repeatable/cloudflare_ddns.log` (or `admin/cloudflare_ddns.log` if `tools/repeatable` is missing). Each line: `YYYY-MM-DD HH:MM:SS <message>`.

**Cron (run once per day):**

```cron
0 6 * * * /usr/bin/php /path/to/htdocs/tools/repeatable/php/api-tools/cloudflare_ddns.php >> /path/to/htdocs/tools/repeatable/cloudflare_ddns.log 2>&1
```

Or on Windows (Task Scheduler): daily at 06:00, action "Start a program", program `php`, arguments `C:\xampp\htdocs\tools\repeatable\php\api-tools\cloudflare_ddns.php`.

**Dependencies:** PHP with curl. Same .env as other Cloudflare tools.
