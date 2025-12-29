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
from PIL import Image

# Configuration
SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent
ENV_FILE = PROJECT_ROOT / ".env"
DOWNLOAD_DIR = PROJECT_ROOT / "uploads" / "Items"

# Limit number of items to process (set to 1 for testing, None for all)
MAX_ITEMS_TO_PROCESS = 1

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

def clean_search_query(query: str) -> str:
    """Clean search query by removing problematic characters and normalizing"""
    # Remove leading/trailing periods and other problematic characters
    cleaned = query.strip()
    # Remove leading periods (e.g., ".38" -> "38")
    cleaned = cleaned.lstrip('.')
    # Replace multiple spaces with single space
    cleaned = ' '.join(cleaned.split())
    return cleaned

def search_envato_photos(query: str, api_key: str) -> Optional[Dict]:
    """Search Envato for Photos using the discovery search endpoint"""
    url = "https://api.envato.com/v1/discovery/search/search/item.json"
    headers = {
        "Authorization": f"Bearer {api_key}",
        "User-Agent": "VbN-Image-Finder/1.0"
    }
    
    # Clean the query first
    cleaned_query = clean_search_query(query)
    print(f"  🔍 Original query: '{query}' -> Cleaned: '{cleaned_query}'")
    
    # Search for Photos - use photodune.net site filter
    photo_params_options = [
        {"term": cleaned_query, "site": "photodune.net", "page": 1, "page_size": 20},
    ]
    
    last_error = None
    
    for i, params in enumerate(photo_params_options):
        try:
            response = requests.get(url, headers=headers, params=params, timeout=30)
            response.raise_for_status()
            data = response.json()
            
            # Check if we got photo results
            matches = data.get('matches', [])
            if matches:
                print(f"  🔍 Attempt {i+1}: Found {len(matches)} matches")
                print(f"  📋 All matches:")
                for idx, match in enumerate(matches, 1):
                    classification = match.get('classification', 'N/A')
                    name = match.get('name', 'N/A')
                    print(f"     {idx}. {classification} - {name}")
                
                # Filter for photos/images - accept items with previews (they're images)
                # Use ONLY classification field (unified marketplace, no site filtering)
                photo_matches = []
                # REQUIRED: Must contain one of these terms in classification OR have previews
                required_photo_terms = ['photo', 'image', 'stock', 'graphic', 'illustration', 'graphics']
                # REJECT: Anything with these terms
                excluded_terms = ['sound', 'audio', 'music', 'video', 'footage', 'template', 'code', '3d', 'codecanyon', 'themeforest', 'videohive', 'audiojungle']
                
                for match in matches:
                    classification = match.get('classification', '').lower()
                    
                    # REJECT: Skip anything with excluded terms
                    if any(excluded in classification for excluded in excluded_terms):
                        continue
                    
                    # ACCEPT: Has photo/image term in classification OR has previews (items with previews are images)
                    has_photo_term = any(term in classification for term in required_photo_terms)
                    has_previews = 'previews' in match and match['previews']
                    
                    if has_photo_term or has_previews:
                        photo_matches.append(match)
                
                # If we found photo matches, use those
                if photo_matches:
                    print(f"  ✅ Found {len(photo_matches)} photo/image matches (filtered from {len(matches)} total)")
                    data['matches'] = photo_matches[:10]
                    return data
                else:
                    print(f"  ⚠️  No photo/image matches found (all {len(matches)} results were non-photo items)")
                
                # If still no matches, try next parameter set
                continue
            
            # If no matches with this param set, try next
            if not matches:
                print(f"  ⚠️  Attempt {i+1}: No matches returned")
                continue
        except requests.exceptions.HTTPError as e:
            last_error = f"HTTP {response.status_code}: {response.text[:200]}"
            if i == len(photo_params_options) - 1:
                print(f"  ⚠️  API Error (last attempt): {last_error}")
            continue
        except requests.exceptions.RequestException as e:
            last_error = str(e)
            if i == len(photo_params_options) - 1:
                print(f"  ⚠️  Request Error (last attempt): {last_error}")
            continue
        except Exception as e:
            last_error = f"Unexpected error: {str(e)}"
            if i == len(photo_params_options) - 1:
                print(f"  ⚠️  {last_error}")
            continue
    
    # If all parameter sets failed, log summary
    print(f"  ❌ Tried {len(photo_params_options)} different search strategies, none returned usable photo results")
    if last_error:
        print(f"  ⚠️  Last error: {last_error}")
    return None

def download_image(url: str, filepath: Path, api_key: str) -> Tuple[bool, Optional[str]]:
    """Download image from URL and validate dimensions
    
    Returns:
        Tuple of (success: bool, error_message: Optional[str])
    """
    headers = {
        "Authorization": f"Bearer {api_key}",
        "User-Agent": "VbN-Image-Downloader/1.0"
    }
    
    try:
        response = requests.get(url, headers=headers, stream=True, timeout=60)
        response.raise_for_status()
        
        # Ensure directory exists
        filepath.parent.mkdir(parents=True, exist_ok=True)
        
        # Download file directly
        with open(filepath, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        # TODO: Uncomment when ready to validate dimensions
        # # Validate image dimensions
        # try:
        #     with Image.open(filepath) as img:
        #         width, height = img.size
        #         if width < min_width or height < min_height:
        #             filepath.unlink()  # Delete the too-small image
        #             return (False, f"Image too small: {width}x{height} (minimum: {min_width}x{min_height})")
        #         print(f"  ✅ Image dimensions: {width}x{height}")
        # except Exception as e:
        #     return (False, f"Invalid image file: {str(e)}")
        
        return (True, None)
        
    except requests.exceptions.RequestException as e:
        return (False, f"Error downloading image: {e}")

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

# TODO: Uncomment when ready to update database
# def update_item_image(conn: mysql.connector.MySQLConnection, item_id: int, image_filename: str) -> bool:
#     """Update item's image field in database"""
#     cursor = conn.cursor()
#     try:
#         cursor.execute(
#             "UPDATE items SET image = %s WHERE id = %s",
#             (image_filename, item_id)
#         )
#         conn.commit()
#         return cursor.rowcount > 0
#     except mysql.connector.Error as e:
#         print(f"  ❌ Error updating database: {e}")
#         conn.rollback()
#         return False
#     finally:
#         cursor.close()

def process_item(item: Dict, api_key: str, conn: mysql.connector.MySQLConnection) -> Tuple[bool, str]:
    """Process a single item: search, download, and update database"""
    item_id = item['id']
    item_name = item['name']
    # TODO: Uncomment when ready to skip existing images
    # current_image = item.get('image', '')
    # 
    # # Check if image already exists
    # if current_image:
    #     image_path = DOWNLOAD_DIR / current_image
    #     if image_path.exists():
    #         return (True, f"⏭️  Skipping {item_name} - already has image: {current_image}")
    
    print(f"\n🔍 Processing: {item_name} (ID: {item_id})")
    
    # Search Envato Photos
    results = search_envato_photos(item_name, api_key)
    
    if not results:
        return (False, f"❌ No API response for {item_name}")
    
    # Check for matches
    matches = results.get('matches', [])
    if not matches or len(matches) == 0:
        return (False, f"❌ No results found for {item_name}")
    
    # Find the best matches - ONLY photo/image items
    # Use ONLY classification field (unified marketplace)
    # Also try to match item name better (e.g., "gun" vs "bullets")
    # Score all matches and try them in order until one passes dimension check
    # REQUIRED photo terms in classification
    required_photo_terms = ['photo', 'image', 'stock', 'graphic', 'illustration', 'graphics']
    # REJECT these terms
    excluded_classifications = ['sound', 'audio', 'music', 'video', 'footage', 'template', 'code', '3d', 'codecanyon', 'themeforest', 'videohive', 'audiojungle']
    
    # Extract key terms from item name for better matching
    item_name_lower = item_name.lower()
    item_terms = set(item_name_lower.split())
    
    # Score and sort all valid PHOTO matches only
    scored_matches = []
    for match in matches:
        classification = match.get('classification', '').lower()
        match_name = match.get('name', '').lower()
        
        # REJECT: Skip anything with excluded terms
        if any(excluded in classification for excluded in excluded_classifications):
            continue
        
        # ACCEPT: Has photo/image term in classification OR has previews (items with previews are images)
        has_photo_term = any(term in classification for term in required_photo_terms)
        has_previews = 'previews' in match and match['previews']
        
        if not has_photo_term and not has_previews:
            continue
        
        # Score matches: prefer items that match item name terms
        score = 0
        
        # Base score for being a photo
        score += 10
        
        # Bonus for name matching (e.g., "revolver" in match name)
        match_terms = set(match_name.split())
        matching_terms = item_terms.intersection(match_terms)
        score += len(matching_terms) * 5
        
        # Penalty for unrelated terms (e.g., "bullets" when searching for "revolver")
        if 'bullet' in match_name and ('gun' in item_name_lower or 'revolver' in item_name_lower or 'pistol' in item_name_lower):
            score -= 10
        
        # Prefer photo/stock photo classifications
        if 'photo' in classification or 'stock' in classification:
            score += 5
        
        scored_matches.append((score, match))
    
    # Sort by score (highest first)
    scored_matches.sort(key=lambda x: x[0], reverse=True)
    
    # If no matches found, return error
    if not scored_matches:
        return (False, f"❌ No suitable photo/image results found for {item_name} (only audio/video/code/3d results available)")
    
    # Try matches in order until one passes dimension check
    for score, best_match in scored_matches[:5]:  # Try top 5 matches
        print(f"  🎯 Trying match (score: {score}): {best_match.get('name', 'Unknown')[:50]}")
        
        # Extract image URL from various possible fields
        # Skip icon URLs - those are just logos, not actual photos
        image_url = None
        
        # Check previews object (common in Envato API)
        # Prioritize larger preview types for better quality images
        if 'previews' in best_match:
            previews = best_match['previews']
            # Skip icon_with_audio_preview and icon_with_video_preview - those are logos
            # Only use preview types that are actual images
            # Prioritize larger previews: landscape > square > thumbnail
            for preview_type in ['landscape_preview', 'square_preview', 'preview_url', 'thumbnail_preview']:
                if preview_type in previews:
                    preview_data = previews[preview_type]
                    if isinstance(preview_data, dict):
                        # Prefer url/preview_url over icon_url (icon_url is usually just a logo)
                        image_url = preview_data.get('url') or preview_data.get('preview_url') or preview_data.get('thumbnail_url')
                        if not image_url:
                            # Look for any value that looks like a URL (but skip icon_url)
                            for key, value in preview_data.items():
                                if key != 'icon_url' and isinstance(value, str) and value.startswith('http'):
                                    image_url = value
                                    break
                    elif isinstance(preview_data, str) and preview_data.startswith('http'):
                        image_url = preview_data
                    if image_url:
                        break
        
        # Check direct image URL fields (never use icon_url - it's just logos)
        if not image_url:
            image_url = (
                best_match.get('preview_url') or 
                best_match.get('thumbnail_url') or 
                best_match.get('live_preview_url') or
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
            print(f"  ⚠️  No image URL found in this match. Available fields: {', '.join(available_fields[:5])}")
            continue  # Try next match
        
        # Download using Envato item name
        envato_name = best_match.get('name', 'item')
        image_filename = create_safe_filename(envato_name)
        final_filepath = DOWNLOAD_DIR / image_filename
        
        # Download image
        print(f"  ⬇️  Downloading: {envato_name[:50]}...")
        success, error_msg = download_image(image_url, final_filepath, api_key)
        if not success:
            print(f"  ⚠️  {error_msg}, trying next match...")
            continue  # Try next match
        
        print(f"  ✅ Saved as: {image_filename}")
        return (True, f"✅ Successfully downloaded {item_name}")
        
        # TODO: Uncomment when ready to rename and update database
        # # Now rename to database item name
        # image_filename = create_safe_filename(item_name)
        # final_filepath = DOWNLOAD_DIR / image_filename
        # 
        # # Delete existing file if it exists
        # if final_filepath.exists():
        #     try:
        #         final_filepath.unlink()
        #     except PermissionError:
        #         print(f"  ⚠️  Cannot replace existing file (locked): {image_filename}, trying next match...")
        #         temp_filepath.unlink()  # Clean up temp file
        #         continue
        # 
        # # Rename temp file to final name
        # try:
        #     temp_filepath.replace(final_filepath)
        #     print(f"  ✅ Saved as: {image_filename}")
        # except Exception as e:
        #     print(f"  ⚠️  Failed to rename file: {e}, trying next match...")
        #     temp_filepath.unlink()  # Clean up temp file
        #     continue
        # 
        # # Update database
        # if update_item_image(conn, item_id, image_filename):
        #     return (True, f"✅ Successfully downloaded and updated {item_name}")
        # else:
        #     return (False, f"⚠️  Downloaded but failed to update database for {item_name}")
    
    # If we tried all matches and none worked
    return (False, f"❌ Tried {len(scored_matches[:5])} matches, none passed dimension check or had valid image URLs")

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
    
    # Get items - only crowbar
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, name, image FROM items WHERE name LIKE %s ORDER BY name ASC", ("%crowbar%",))
        items = cursor.fetchall()
        cursor.close()
        print(f"✅ Found {len(items)} items matching 'crowbar' in database")
    except mysql.connector.Error as e:
        print(f"❌ Error fetching items: {e}")
        conn.close()
        sys.exit(1)
    
    if not items:
        print("⚠️  No items found in database")
        conn.close()
        sys.exit(0)
    
    # Limit items to process if MAX_ITEMS_TO_PROCESS is set
    items_to_process = items
    if MAX_ITEMS_TO_PROCESS is not None and MAX_ITEMS_TO_PROCESS > 0:
        items_to_process = items[:MAX_ITEMS_TO_PROCESS]
        print(f"\n⚠️  Processing limited to {MAX_ITEMS_TO_PROCESS} item(s) for testing")
        print(f"   (Total items in database: {len(items)})")
    
    # Process items
    print(f"\n📦 Processing {len(items_to_process)} items...")
    print("=" * 60)
    
    success_count = 0
    # TODO: Uncomment when ready to track skipped items
    # skip_count = 0
    error_count = 0
    
    for item in items_to_process:
        success, message = process_item(item, api_key, conn)
        print(f"  {message}")
        
        if success:
            # TODO: Uncomment when ready to track skipped items
            # if "Skipping" in message:
            #     skip_count += 1
            # else:
            #     success_count += 1
            success_count += 1
        else:
            error_count += 1
    
    # Summary
    print("\n" + "=" * 60)
    print("Summary:")
    print(f"  ✅ Downloaded: {success_count}")
    # TODO: Uncomment when ready to track skipped items
    # print(f"  ⏭️  Skipped: {skip_count}")
    print(f"  ❌ Errors: {error_count}")
    print("=" * 60)
    
    conn.close()
    print("\n✅ Done!")

if __name__ == "__main__":
    main()

