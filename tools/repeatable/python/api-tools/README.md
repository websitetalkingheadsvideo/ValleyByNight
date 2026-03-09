# API Tools

Tools for API interaction and data fetching.

## Tools

### download_envato_images.py

**Purpose:** Downloads images from Envato Photos for items in the project data store.

**Usage:**
```bash
python tools/repeatable/python/api-tools/download_envato_images.py
```

**Features:**
- Connects to the configured Supabase-backed project data
- Fetches images from Envato API
- Downloads and processes images
- Tracks progress in tracking file

**Dependencies:**
- Python 3.7+
- `requests` package
- `PIL` (Pillow) package
- `.env` file with `ENVATO_API_KEY` and any required project API credentials

**Configuration:** Requires `.env` file with API keys and project credentials

---

### fetch_envato_json.py

**Purpose:** Fetches Envato catalog item JSON and saves to file.

**Usage:**
```bash
python tools/repeatable/python/api-tools/fetch_envato_json.py <item_id_or_name>
```

**Example:**
```bash
python tools/repeatable/python/api-tools/fetch_envato_json.py "Sword"
```

**Features:**
- Searches Envato catalog
- Fetches item JSON data
- Saves to file for debugging
- API key from .env file

**Dependencies:**
- Python 3.7+
- `requests` package
- `.env` file with `ENVATO_API_KEY`
