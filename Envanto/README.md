# Envato Image Downloader

This directory contains information and scripts for downloading images from Envato Photos to use as item images in the VbN database.

## Setup

1. **Install Python dependencies:**
   ```bash
   pip install -r scripts/requirements.txt
   ```

2. **Verify credentials are in `.env`:**
   - `ENVATO_API_KEY` - Your Envato Personal Token
   - `ENVATO_USERNAME` - Your Envato username
   - Database credentials (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`)

## Usage

Run the download script:

```bash
python scripts/download_envato_images.py
```

## What It Does

1. **Connects to the database** and fetches all items from the `items` table
2. **Searches Envato Photos** for each item by name
3. **Downloads the best match** (first result) for each item
4. **Saves images** to `uploads/Items/` directory
5. **Updates the database** with the new image filename
6. **Skips items** that already have images

## Output

- Images are saved as `item_name.jpg` in `uploads/Items/`
- Database `items.image` field is updated with the filename
- Script provides progress updates and a summary at the end

## Notes

- The script uses the Envato API discovery search endpoint
- Images are downloaded from Envato's photo library
- The script respects rate limits and includes error handling
- Items with existing images are automatically skipped

## Troubleshooting

- **API errors**: Verify your API key is valid and has the correct permissions
- **Database errors**: Check your database credentials in `.env`
- **Download failures**: Check your internet connection and Envato API status

