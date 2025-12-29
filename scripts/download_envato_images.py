#!/usr/bin/env python3
"""
Envato Photos Image Downloader
Downloads images from Envato Photos for items in the database
"""

import os
import sys
import json
import re
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

def get_size_priority(size_key: str, preview_type: str) -> int:
    """Get priority score for image size (higher = larger/better)
    
    Returns priority score where:
    - 100+ = Very large (full, huge, original)
    - 80-99 = Large
    - 60-79 = Medium-large
    - 40-59 = Medium
    - 20-39 = Small-medium
    - 0-19 = Small/thumbnail
    """
    key_lower = size_key.lower()
    type_lower = preview_type.lower()
    
    # Very large sizes (priority 100+)
    if any(term in key_lower for term in ['full', 'original', 'huge', 'max', 'maximum']):
        return 100
    if any(term in type_lower for term in ['full', 'original', 'huge']):
        return 95
    
    # Large sizes (priority 80-99)
    if any(term in key_lower for term in ['large', 'big', 'xl', 'xxl']):
        return 85
    if any(term in type_lower for term in ['large', 'big', 'landscape']):
        return 80
    
    # Medium-large (priority 60-79)
    if any(term in key_lower for term in ['medium_large', 'med_large', 'l']):
        return 70
    if any(term in type_lower for term in ['square', 'preview']):
        return 65
    
    # Medium (priority 40-59)
    if 'medium' in key_lower or 'med' in key_lower:
        return 50
    if 'medium' in type_lower:
        return 45
    
    # Small-medium (priority 20-39)
    if any(term in key_lower for term in ['small', 's']):
        return 30
    if 'small' in type_lower:
        return 25
    
    # Small/thumbnail (priority 0-19)
    if any(term in key_lower for term in ['thumb', 'icon', 'tiny']):
        return 10
    if any(term in type_lower for term in ['thumb', 'icon']):
        return 5
    
    # Default for unknown sizes - assume medium
    return 50

def try_upgrade_preview_url(url: str) -> str:
    """Try to upgrade preview URL to larger size if possible
    
    Some Envato preview URLs can be modified to get larger sizes.
    This attempts common URL modifications for Envato's CDN patterns.
    """
    if not url or not isinstance(url, str):
        return url
    
    upgraded = url
    
    # Envato-specific URL patterns:
    # - /previews-dam/ might have size variants
    # - /thumb/ or /thumbnail/ in path indicates small size
    # - Size might be in query params or path
    
    # Replace size indicators in path
    upgraded = upgraded.replace('/thumbnail/', '/large/')
    upgraded = upgraded.replace('/thumb/', '/large/')
    upgraded = upgraded.replace('/small/', '/large/')
    upgraded = upgraded.replace('/medium/', '/large/')
    upgraded = upgraded.replace('/preview/', '/large/')
    
    # Try removing size restrictions from Envato CDN URLs
    # Pattern: .../previews-dam/... might become .../large/... or remove size param
    if '/previews-dam/' in upgraded:
        # Try replacing with larger size path
        upgraded = upgraded.replace('/previews-dam/', '/large/')
    
    # Try modifying query parameters
    if '?' in upgraded:
        # Remove or modify size parameters
        if 'size=' in upgraded:
            upgraded = upgraded.replace('size=thumb', 'size=large')
            upgraded = upgraded.replace('size=small', 'size=large')
            upgraded = upgraded.replace('size=medium', 'size=large')
        elif 'w=' in upgraded or 'h=' in upgraded:
            # Try to increase dimensions
            upgraded = re.sub(r'w=\d+', 'w=2000', upgraded)
            upgraded = re.sub(r'h=\d+', 'h=2000', upgraded)
    else:
        # Add size parameter if URL doesn't have query params
        upgraded = upgraded + '?size=large'
    
    return upgraded

def download_image(url: str, filepath: Path, api_key: str) -> Tuple[bool, Optional[str], Optional[Tuple[int, int]]]:
    """Download image from URL and validate dimensions
    
    Returns:
        Tuple of (success: bool, error_message: Optional[str], dimensions: Optional[Tuple[int, int]])
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
        
        # Check actual image dimensions to verify quality
        dimensions = None
        try:
            with Image.open(filepath) as img:
                width, height = img.size
                dimensions = (width, height)
                file_size_kb = filepath.stat().st_size / 1024
                print(f"  📏 Image size: {width}x{height}px ({file_size_kb:.1f} KB)")
                
                # Warn if image seems too small (likely a thumbnail)
                if width < 500 or height < 500:
                    print(f"  ⚠️  Warning: Image appears to be a thumbnail ({width}x{height}px)")
                elif file_size_kb < 50:
                    print(f"  ⚠️  Warning: File size is very small ({file_size_kb:.1f} KB), may be compressed/low quality")
        except Exception as e:
            print(f"  ⚠️  Could not verify image dimensions: {e}")
        
        return (True, None, dimensions)
        
    except requests.exceptions.RequestException as e:
        return (False, f"Error downloading image: {e}", None)

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
    """Process a single item: search, download, and update database
    
    Note: This function downloads preview images from Envato. For full-size/high-resolution
    images, you may need to use the Envato download endpoint (/v1/market/catalog/item) which
    requires a valid subscription/license. Preview images are typically 800-1200px on the longest side.
    """
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
        # Envato typically provides a dozen or more sizes - we need to find ALL of them
        image_url = None
        all_available_urls = []  # Store all URLs with their size info
        
        # Check previews object (common in Envato API)
        if 'previews' in best_match:
            previews = best_match['previews']
            print(f"  🔍 Found previews object with {len(previews)} preview types")
            
            # Collect ALL available URLs from ALL preview types
            for preview_type, preview_data in previews.items():
                # Skip icons and audio/video previews
                if 'icon' in preview_type.lower() or 'audio' in preview_type.lower() or 'video' in preview_type.lower():
                    continue
                
                urls_from_this_preview = []
                
                if isinstance(preview_data, dict):
                    # Extract ALL URLs from this preview dict
                    # Envato previews can have multiple URLs (large_url, small_url, etc.) with dimensions
                    
                    # Check for large_url with dimensions
                    if 'large_url' in preview_data and isinstance(preview_data['large_url'], str):
                        width = preview_data.get('large_width')
                        height = preview_data.get('large_height')
                        priority = (width or 0) + 1000  # Large URLs get bonus
                        urls_from_this_preview.append({
                            'url': preview_data['large_url'],
                            'preview_type': preview_type,
                            'size_key': 'large_url',
                            'width': width,
                            'height': height,
                            'priority': priority
                        })
                    
                    # Check for small_url
                    if 'small_url' in preview_data and isinstance(preview_data['small_url'], str):
                        width = preview_data.get('small_width') or preview_data.get('width')
                        height = preview_data.get('small_height') or preview_data.get('height')
                        priority = (width or 0)  # Small URLs get base priority
                        urls_from_this_preview.append({
                            'url': preview_data['small_url'],
                            'preview_type': preview_type,
                            'size_key': 'small_url',
                            'width': width,
                            'height': height,
                            'priority': priority
                        })
                    
                    # Check for other URL fields
                    for key in ['url', 'thumbnail_url', 'icon_url', 'preview_url']:
                        if key in preview_data and isinstance(preview_data[key], str) and preview_data[key].startswith('http'):
                            # Check if we already added this URL
                            if not any(u['url'] == preview_data[key] for u in urls_from_this_preview):
                                width = preview_data.get(f'{key.replace("_url", "")}_width') or preview_data.get('width')
                                height = preview_data.get(f'{key.replace("_url", "")}_height') or preview_data.get('height')
                                priority = (width or 0) if width else get_size_priority(key, preview_type)
                                urls_from_this_preview.append({
                                    'url': preview_data[key],
                                    'preview_type': preview_type,
                                    'size_key': key,
                                    'width': width,
                                    'height': height,
                                    'priority': priority
                                })
                elif isinstance(preview_data, str) and preview_data.startswith('http'):
                    urls_from_this_preview.append({
                        'url': preview_data,
                        'preview_type': preview_type,
                        'size_key': 'direct',
                        'priority': get_size_priority(preview_type, preview_type)
                    })
                
                all_available_urls.extend(urls_from_this_preview)
            
            # Don't select yet - we'll collect from all sources first
        
        # Check direct image URL fields (add ALL to our collection, including thumbnails)
        direct_url_fields = [
            'full_preview_url', 'large_preview_url', 'huge_preview_url',
            'preview_url', 'live_preview_url', 'image_url',
            'medium_preview_url', 'small_preview_url',
            'thumbnail_url', 'thumb_url'  # Include but with low priority
        ]
        for field in direct_url_fields:
            url_value = best_match.get(field)
            if isinstance(url_value, str) and url_value.startswith('http'):
                all_available_urls.append({
                    'url': url_value,
                    'preview_type': 'direct_field',
                    'size_key': field,
                    'priority': get_size_priority(field, field)
                })
        
        # Check image_urls array (Envato provides array of objects with name, url, width, height)
        if 'image_urls' in best_match:
            image_urls = best_match['image_urls']
            if isinstance(image_urls, list):
                for url_obj in image_urls:
                    if isinstance(url_obj, dict):
                        # Extract URL and dimensions
                        url_value = url_obj.get('url')
                        width = url_obj.get('width', 0)
                        height = url_obj.get('height', 0)
                        name = url_obj.get('name', 'unknown')
                        
                        if isinstance(url_value, str) and url_value.startswith('http'):
                            # Priority based on actual width (larger = higher priority)
                            # Use width as primary priority, with bonus for non-cropped (w prefix vs c prefix)
                            priority = width
                            if name.startswith('w'):  # Width-based (non-cropped) gets bonus
                                priority += 1000
                            elif name.startswith('c'):  # Crop-based gets lower priority
                                priority += 500
                            
                            all_available_urls.append({
                                'url': url_value,
                                'preview_type': 'image_urls',
                                'size_key': name,
                                'width': width,
                                'height': height,
                                'priority': priority
                            })
                    elif isinstance(url_obj, str) and url_obj.startswith('http'):
                        # Fallback: if it's just a string URL
                        all_available_urls.append({
                            'url': url_obj,
                            'preview_type': 'image_urls_array',
                            'size_key': 'string_url',
                            'priority': 50
                        })
            elif isinstance(image_urls, dict):
                # Handle dict format (less common)
                for key, url_value in image_urls.items():
                    if isinstance(url_value, str) and url_value.startswith('http'):
                        all_available_urls.append({
                            'url': url_value,
                            'preview_type': 'image_urls_dict',
                            'size_key': key,
                            'priority': get_size_priority(key, key)
                        })
        
        # Now select the best URL from all collected sources
        if all_available_urls:
            # Sort by priority (highest first)
            sorted_urls = sorted(all_available_urls, key=lambda x: x['priority'], reverse=True)
            
            # Log all available sizes
            print(f"  📋 Found {len(sorted_urls)} available image sizes from all sources:")
            for idx, url_info in enumerate(sorted_urls[:20], 1):  # Show top 20
                # Show dimensions if available
                dims_str = ""
                aspect_str = ""
                if 'width' in url_info and 'height' in url_info:
                    dims_str = f" {url_info['width']}x{url_info['height']}px"
                    # Determine aspect ratio
                    if url_info['width'] and url_info['height']:
                        ratio = url_info['width'] / url_info['height']
                        if abs(ratio - 1.0) < 0.05:  # Within 5% of square
                            aspect_str = " [SQUARE]"
                        elif ratio > 1.0:
                            aspect_str = " [LANDSCAPE]"
                        else:
                            aspect_str = " [PORTRAIT]"
                print(f"     {idx}. {url_info['preview_type']}/{url_info['size_key']}{dims_str}{aspect_str} (priority: {url_info['priority']:.0f})")
            if len(sorted_urls) > 20:
                print(f"     ... and {len(sorted_urls) - 20} more")
            
            # First, try to find square images (width == height, within 5% tolerance)
            square_images = []
            for url_info in sorted_urls:
                if 'width' in url_info and 'height' in url_info and url_info['width'] and url_info['height']:
                    ratio = url_info['width'] / url_info['height']
                    if abs(ratio - 1.0) < 0.05:  # Within 5% of square (1:1)
                        square_images.append(url_info)
            
            if square_images:
                # Sort squares by width (largest first)
                square_images.sort(key=lambda x: x.get('width', 0), reverse=True)
                best_url_info = square_images[0]
                image_url = best_url_info['url']
                dims_str = f" ({best_url_info['width']}x{best_url_info['height']}px)"
                print(f"  ✅ Selected SQUARE: {best_url_info['preview_type']}/{best_url_info['size_key']}{dims_str} (largest square available)")
            else:
                # No square found, look for landscape (width > height)
                landscape_images = []
                for url_info in sorted_urls:
                    if 'width' in url_info and 'height' in url_info and url_info['width'] and url_info['height']:
                        if url_info['width'] > url_info['height']:
                            landscape_images.append(url_info)
                
                if landscape_images:
                    # Sort landscapes by width (largest first)
                    landscape_images.sort(key=lambda x: x.get('width', 0), reverse=True)
                    best_url_info = landscape_images[0]
                    image_url = best_url_info['url']
                    dims_str = f" ({best_url_info['width']}x{best_url_info['height']}px)"
                    print(f"  ✅ Selected LANDSCAPE: {best_url_info['preview_type']}/{best_url_info['size_key']}{dims_str} (largest landscape available)")
                else:
                    # Fallback: use highest priority (might be portrait or unknown)
                    best_url_info = sorted_urls[0]
                    image_url = best_url_info['url']
                    dims_str = ""
                    if 'width' in best_url_info and 'height' in best_url_info:
                        dims_str = f" ({best_url_info['width']}x{best_url_info['height']}px)"
                    print(f"  ⚠️  Selected (no square/landscape found): {best_url_info['preview_type']}/{best_url_info['size_key']}{dims_str}")
        
        if not image_url:
            # Log available fields for debugging
            available_fields = [k for k in best_match.keys() if 'image' in k.lower() or 'preview' in k.lower() or 'url' in k.lower()]
            print(f"  ⚠️  No image URL found in this match. Available fields: {', '.join(available_fields[:5])}")
            continue  # Try next match
        
        # Ensure image_url is a string (it might be a dict in some edge cases)
        if not isinstance(image_url, str):
            print(f"  🔍 Image URL is a dict, extracting largest available size...")
            # Try to extract string URL from dict, prioritizing larger sizes
            if isinstance(image_url, dict):
                # Log available keys for debugging
                available_keys = list(image_url.keys())
                print(f"  📋 Available keys in image_url dict: {', '.join(available_keys)}")
                
                # Try to find the largest size - check for size indicators
                # Priority: large/full/huge > medium > preview > url > thumbnail
                size_priority_keys = [
                    'large_url', 'full_url', 'huge_url', 'big_url',
                    'large', 'full', 'huge', 'big',
                    'medium_url', 'medium',
                    'preview_url', 'preview',
                    'url',
                    'thumbnail_url', 'thumbnail', 'thumb'
                ]
                
                extracted_url = None
                for key in size_priority_keys:
                    if key in image_url:
                        value = image_url[key]
                        if isinstance(value, str) and value.startswith('http'):
                            extracted_url = value
                            print(f"  ✅ Extracted URL from key '{key}'")
                            break
                
                # If no priority key found, look for any HTTP URL in the dict
                if not extracted_url:
                    for key, value in image_url.items():
                        if isinstance(value, str) and value.startswith('http'):
                            # Skip obvious thumbnail/small sizes
                            if 'thumbnail' not in key.lower() and 'thumb' not in key.lower() and 'icon' not in key.lower():
                                extracted_url = value
                                print(f"  ✅ Extracted URL from key '{key}'")
                                break
                
                # Last resort: use any URL found
                if not extracted_url:
                    for key, value in image_url.items():
                        if isinstance(value, str) and value.startswith('http'):
                            extracted_url = value
                            print(f"  ⚠️  Using URL from key '{key}' (may be small)")
                            break
                
                if extracted_url:
                    image_url = extracted_url
                else:
                    print(f"  ❌ Could not extract URL from dict, trying next match...")
                    continue  # Try next match
            else:
                image_url = str(image_url)
        
        # Try to upgrade URL to larger size if it looks like a thumbnail
        original_url = image_url
        if isinstance(image_url, str) and ('thumbnail' in image_url.lower() or 'thumb' in image_url.lower()):
            upgraded_url = try_upgrade_preview_url(image_url)
            if upgraded_url != image_url:
                print(f"  🔄 Attempting to upgrade thumbnail URL to larger size...")
                image_url = upgraded_url
        
        # Final validation: ensure image_url is a valid string URL
        if not isinstance(image_url, str) or not image_url.startswith('http'):
            print(f"  ⚠️  Invalid image URL (type: {type(image_url).__name__}, value: {str(image_url)[:50]}), trying next match...")
            continue  # Try next match
        
        # Download using Envato item name
        envato_name = best_match.get('name', 'item')
        image_filename = create_safe_filename(envato_name)
        final_filepath = DOWNLOAD_DIR / image_filename
        
        # Download image
        print(f"  ⬇️  Downloading from: {image_url[:80]}...")
        success, error_msg, dimensions = download_image(image_url, final_filepath, api_key)
        
        # If download failed and we upgraded the URL, try original URL
        if not success and image_url != original_url:
            print(f"  🔄 Upgraded URL failed, trying original URL...")
            success, error_msg, dimensions = download_image(original_url, final_filepath, api_key)
        
        if not success:
            print(f"  ⚠️  {error_msg}, trying next match...")
            continue  # Try next match
        
        # Reject thumbnails - if image is too small, try next match
        if dimensions:
            width, height = dimensions
            if width < 500 or height < 500:
                print(f"  ❌ Rejecting thumbnail ({width}x{height}px), trying next match...")
                final_filepath.unlink()  # Delete the thumbnail
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

