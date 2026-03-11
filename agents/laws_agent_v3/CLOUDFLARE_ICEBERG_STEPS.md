# Cloudflare R2 + Iceberg: Actual Step Count

**What it is:** Iceberg on Cloudflare = **R2 Data Catalog**. It lets you use Apache Iceberg table format on an R2 bucket and query with Spark, DuckDB, PyIceberg, Snowflake, Trino, StarRocks, etc. **This is not AI Search / RAG.** It's for analytics/data-lake style tables.

If Cloudflare is prompting you to "add Iceberg," they're talking about enabling **R2 Data Catalog** on a bucket. Below is the real step count from the [get-started](https://developers.cloudflare.com/r2/data-catalog/get-started) and related docs.

---

## Prerequisites (before step 1)

- Node.js 16.17+ (for Wrangler)
- Cloudflare account
- If you follow the Python path: [uv](https://docs.astral.sh/uv/getting-started/installation/) and Python

---

## Minimum path (get-started tutorial): **6 steps**

| Step | What you do | Notes |
|------|-------------|--------|
| 1 | Create R2 bucket | Dashboard or `npx wrangler r2 bucket create <name>` |
| 2 | Enable data catalog on that bucket | Dashboard: bucket → Settings → R2 Data Catalog → Enable. Or `npx wrangler r2 bucket catalog enable <name>`. **Save Catalog URI and Warehouse name.** |
| 3 | Create R2 API token | Dashboard → R2 → Manage API tokens → Create API token → Admin Read & Write. Token must have **both** R2 storage and R2 Data Catalog. **Save the token value.** |
| 4 | Install uv | If using the Python path: install [uv](https://docs.astral.sh/uv/getting-started/installation/) |
| 5 | Install deps + create project | `mkdir ... ; cd ... ; uv init ; uv add marimo pyiceberg pyarrow pandas` |
| 6 | Wire up a client | Create notebook/script, plug in Catalog URI, Warehouse, Token; connect with PyIceberg (or another engine). See [config examples](https://developers.cloudflare.com/r2/data-catalog/config-examples/). |

So: **at least 6 steps**, and step 5–6 are “install toolchain + write/run code.” They don’t tell you up front that you need Node + (optionally) uv/Python and a code snippet.

---

## If you use a different engine

Each engine has its own config page. So “enable Iceberg” can turn into:

- **PyIceberg** – get-started above
- **Spark (Scala or PySpark)** – extra Spark setup + config
- **DuckDB** – DuckDB install + config
- **Snowflake** – Snowflake side + R2/catalog config
- **Apache Trino** – Trino install + config
- **StarRocks** – StarRocks install + config

So the **real** step count is **6+ for the minimal path**, then **add whatever that engine needs** (install, config, credentials). Still no single “total steps” number in the docs.

---

## Optional (more steps)

- **Compaction** – Dashboard or Wrangler; may need another API token (service credential).
- **Snapshot expiration** – Wrangler 4.56+; extra commands.
- **Local uploads** – `npx wrangler r2 bucket catalog local-uploads enable <bucket>`.

---

## Summary

- **Rough total:** **6+ steps** for the minimal “bucket + catalog + token + one client” path; more if you add compaction, snapshot expiry, or a heavier engine (Spark, Snowflake, etc.).
- **Not the same as AI Search:** Iceberg/R2 Data Catalog is for Iceberg tables and query engines, not for the RAG/laws-agent indexing you were doing.
- **References:** [Get started](https://developers.cloudflare.com/r2/data-catalog/get-started), [Manage catalogs](https://developers.cloudflare.com/r2/data-catalog/manage-catalogs), [Config examples (engines)](https://developers.cloudflare.com/r2/data-catalog/config-examples/).
