#!/usr/bin/env python3
"""
Envato Photos Image Downloader
Downloads images from Envato Photos for items in the database
"""

import os
import sys
import json
import requests
import mysql.connector
from pathlib import Path
from typing import Dict, List, Optional, Tuple
from urllib.parse import quote

# Configuration
SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent
ENV_FILE = PROJECT_ROOT / ".env"
DOWNLOAD_DIR = PROJECT_ROOT / "uploads" / "Items"

def load_env_file(env_path: Path) -> Dict[str, str]:
    """Load environment variables from .env file"""
    env_vars: Dict[str, str] = {}
    
    if not env_path.exists():
        raise FileNotFoundError(f".env file not found at {env_path}")
    
    with open(env_path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            # Skip comments and empty lines
            if not line or line.startswith('#'):
                continue
            
            # Parse KEY=VALUE format
            if '=' in line:
                key, value = line.split('=', 1)
                key = key.strip()
                value = value.strip()
                # Remove quotes if present
                value = value.strip('"\'')
                env_vars[key] = value
    
    return env_vars

def get_database_connection(env_vars: Dict[str, str]) -> mysql.connector.MySQLConnection:
    """Create MySQL database connection"""
    required_vars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']
    missing = [var for var in required_vars if var not in env_vars]
    
    if missing:
        raise ValueError(f"Missing required environment variables: {', '.join(missing)}")
    
    try:
        conn = mysql.connector.connect(
            host=env_vars['DB_HOST'],
            user=env_vars['DB_USER'],
            password=env_vars['DB_PASSWORD'],
            database=env_vars['DB_NAME'],
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        return conn
    except mysql.connector.Error as e:
        raise ConnectionError(f"Database connection failed: {e}")

def get_items_from_database(conn: mysql.connector.MySQLConnection) -> List[Dict]:
    """Fetch all items from the database"""
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT id, name, image FROM items ORDER BY name ASC")
        items = cursor.fetchall()
        return items
    finally:
        cursor.close()

def search_envato_photos(query: str, api_key: str) -> Optional[Dict]:
    """Search Envato for Photos using the discovery search endpoint"""
    url = "https://api.envato.com/v1/discovery/search/search/item.json"
    headers = {
        "Authorization": f"Bearer {api_key}",
        "User-Agent": "VbN-Image-Finder/1.0"
    }
    
    # Search for Photos - the API may use different parameter names
    # Try common photo-related parameters
    photo_params_options = [
        {"term": query, "page": 1, "page_size": 20},  # No filter
        {"term": query, "category": "photos", "page": 1, "page_size": 20},
        {"term": query, "type": "photo", "page": 1, "page_size": 20},
        {"term": query, "media_type": "photo", "page": 1, "page_size": 20},
    ]
    
    for params in photo_params_options:
        try:
            response = requests.get(url, headers=headers, params=params, timeout=30)
            response.raise_for_status()
            data = response.json()
            
            # Check if we got photo results
            matches = data.get('matches', [])
            if matches:
                # Filter for items that look like photos/images
                photo_matches = []
                for match in matches:
                    classification = match.get('classification', '').lower()
                    # Look for photo/image related classifications
                    if any(term in classification for term in ['photo', 'image', 'stock', 'graphic']):
                        photo_matches.append(match)
                
                # If we found photo-like matches, use those
                if photo_matches:
                    data['matches'] = photo_matches[:10]
                    return data
                # Otherwise return all matches and let the caller filter
                return data
            
            # If no matches with this param set, try next
            if not matches and params != photo_params_options[-1]:
                continue
                
            return data
        except requests.exceptions.RequestException:
            # Try next parameter set
            continue
    
    # If all parameter sets failed, return None
    return None

def download_image(url: str, filepath: Path, api_key: str) -> bool:
    """Download image from URL"""
    headers = {
        "Authorization": f"Bearer {api_key}",
        "User-Agent": "VbN-Image-Downloader/1.0"
    }
    
    try:
        response = requests.get(url, headers=headers, stream=True, timeout=60)
        response.raise_for_status()
        
        # Ensure directory exists
        filepath.parent.mkdir(parents=True, exist_ok=True)
        
        # Download file
        with open(filepath, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        return True
    except requests.exceptions.RequestException as e:
        print(f"  ❌ Error downloading image: {e}")
        return False

def create_safe_filename(item_name: str) -> str:
    """Create a safe filename from item name"""
    # Remove or replace invalid filename characters
    safe_name = "".join(c for c in item_name if c.isalnum() or c in (' ', '-', '_')).strip()
    safe_name = safe_name.replace(' ', '_').replace('--', '-')
    safe_name = safe_name.lower()
    
    # Ensure it's not empty
    if not safe_name:
        safe_name = "item"
    
    return safe_name + '.jpg'

def update_item_image(conn: mysql.connector.MySQLConnection, item_id: int, image_filename: str) -> bool:
    """Update item's image field in database"""
    cursor = conn.cursor()
    try:
        cursor.execute(
            "UPDATE items SET image = %s WHERE id = %s",
            (image_filename, item_id)
        )
        conn.commit()
        return cursor.rowcount > 0
    except mysql.connector.Error as e:
        print(f"  ❌ Error updating database: {e}")
        conn.rollback()
        return False
    finally:
        cursor.close()

def process_item(item: Dict, api_key: str, conn: mysql.connector.MySQLConnection) -> Tuple[bool, str]:
    """Process a single item: search, download, and update database"""
    item_id = item['id']
    item_name = item['name']
    current_image = item.get('image', '')
    
    # Check if image already exists
    if current_image:
        image_path = DOWNLOAD_DIR / current_image
        if image_path.exists():
            return (True, f"⏭️  Skipping {item_name} - already has image: {current_image}")
    
    print(f"\n🔍 Processing: {item_name} (ID: {item_id})")
    
    # Search Envato Photos
    results = search_envato_photos(item_name, api_key)
    
    if not results:
        return (False, f"❌ No API response for {item_name}")
    
    # Check for matches
    matches = results.get('matches', [])
    if not matches or len(matches) == 0:
        return (False, f"❌ No results found for {item_name}")
    
    # Find the best match - prioritize image/graphic items
    best_match = None
    image_keywords = ['graphic', 'image', 'photo', 'illustration', 'icon', 'vector', 'png', 'jpg', 'jpeg']
    
    for match in matches:
        classification = match.get('classification', '').lower()
        name = match.get('name', '').lower()
        
        # Check if this looks like an image/graphic item
        is_image_like = any(keyword in classification or keyword in name for keyword in image_keywords)
        
        if is_image_like and not best_match:
            best_match = match
            break
    
    # If no image-like match found, use first result
    if not best_match:
        best_match = matches[0]
        print(f"  ⚠️  Using non-image result: {best_match.get('classification', 'unknown')} - {best_match.get('site', 'unknown')}")
    
    # Extract image URL from various possible fields
    image_url = None
    
    # Check previews object (common in Envato API)
    if 'previews' in best_match:
        previews = best_match['previews']
        # Try different preview types in order of preference
        for preview_type in ['icon_with_audio_preview', 'icon_with_video_preview', 'landscape_preview', 'square_preview', 'icon_url', 'thumbnail_preview']:
            if preview_type in previews:
                preview_data = previews[preview_type]
                if isinstance(preview_data, dict):
                    # Try icon_url first, then url, then any URL-like value
                    image_url = preview_data.get('icon_url') or preview_data.get('url') or preview_data.get('thumbnail_url')
                    if not image_url:
                        # Look for any value that looks like a URL
                        for key, value in preview_data.items():
                            if isinstance(value, str) and value.startswith('http'):
                                image_url = value
                                break
                elif isinstance(preview_data, str) and preview_data.startswith('http'):
                    image_url = preview_data
                if image_url:
                    break
    
    # Check direct image URL fields
    if not image_url:
        image_url = (
            best_match.get('preview_url') or 
            best_match.get('thumbnail_url') or 
            best_match.get('live_preview_url') or
            best_match.get('icon_url') or
            best_match.get('image_url')
        )
    
    # Check image_urls array
    if not image_url and 'image_urls' in best_match:
        image_urls = best_match['image_urls']
        if isinstance(image_urls, list) and len(image_urls) > 0:
            image_url = image_urls[0]
        elif isinstance(image_urls, dict):
            # Try common keys
            image_url = image_urls.get('thumbnail') or image_urls.get('preview') or image_urls.get('icon')
    
    if not image_url:
        # Log available fields for debugging
        available_fields = [k for k in best_match.keys() if 'image' in k.lower() or 'preview' in k.lower() or 'url' in k.lower()]
        return (False, f"❌ No image URL found for {item_name}. Available fields: {', '.join(available_fields[:5])}")
    
    # Create filename
    image_filename = create_safe_filename(item_name)
    filepath = DOWNLOAD_DIR / image_filename
    
    # Download image
    print(f"  ⬇️  Downloading to: {image_filename}")
    if not download_image(image_url, filepath, api_key):
        return (False, f"❌ Failed to download {item_name}")
    
    # Update database
    if update_item_image(conn, item_id, image_filename):
        return (True, f"✅ Successfully downloaded and updated {item_name}")
    else:
        return (False, f"⚠️  Downloaded but failed to update database for {item_name}")

def main() -> None:
    """Main execution function"""
    print("=" * 60)
    print("Envato Photos Image Downloader")
    print("=" * 60)
    
    # Load environment variables
    try:
        env_vars = load_env_file(ENV_FILE)
    except FileNotFoundError as e:
        print(f"❌ {e}")
        sys.exit(1)
    
    # Get API credentials
    api_key = env_vars.get('ENVATO_API_KEY')
    if not api_key:
        print("❌ ENVATO_API_KEY not found in .env file")
        sys.exit(1)
    
    # Connect to database
    try:
        conn = get_database_connection(env_vars)
        print("✅ Connected to database")
    except (ValueError, ConnectionError) as e:
        print(f"❌ {e}")
        sys.exit(1)
    
    # Get items
    try:
        items = get_items_from_database(conn)
        print(f"✅ Found {len(items)} items in database")
    except mysql.connector.Error as e:
        print(f"❌ Error fetching items: {e}")
        conn.close()
        sys.exit(1)
    
    if not items:
        print("⚠️  No items found in database")
        conn.close()
        sys.exit(0)
    
    # Process items
    print(f"\n📦 Processing {len(items)} items...")
    print("=" * 60)
    
    success_count = 0
    skip_count = 0
    error_count = 0
    
    for item in items:
        success, message = process_item(item, api_key, conn)
        print(f"  {message}")
        
        if success:
            if "Skipping" in message:
                skip_count += 1
            else:
                success_count += 1
        else:
            error_count += 1
    
    # Summary
    print("\n" + "=" * 60)
    print("Summary:")
    print(f"  ✅ Downloaded: {success_count}")
    print(f"  ⏭️  Skipped: {skip_count}")
    print(f"  ❌ Errors: {error_count}")
    print("=" * 60)
    
    conn.close()
    print("\n✅ Done!")

if __name__ == "__main__":
    main()

