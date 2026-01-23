# XAMPP Server Information

## Dynamic IP Address

This site is hosted on a XAMPP server with a **dynamic IP address**. If the site goes down, it is likely because the IP address has changed.

**To fix:**
1. Check the current public IP address of the XAMPP server
2. Update the DNS A record in Cloudflare to point to the new IP address
3. Wait for DNS propagation (usually minutes to a few hours)

## Site Access

**Important:** You must access the site with `/index.php` in the URL:

- ✅ **Correct:** `http://vbn-game.com/index.php`
- ❌ **Incorrect:** `http://vbn-game.com/` (will show XAMPP dashboard)

The Apache `DirectoryIndex` is not configured to serve `index.php` as the default file.
