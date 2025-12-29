#!/usr/bin/env python3
"""
Fetch Envato catalog item JSON and save to file
This helps debug the API response structure to find unwatermarked download URLs

Usage:
    python scripts/fetch_envato_json.py <item_id>
    python scripts/fetch_envato_json.py <item_name>  # Will search and use first result
"""

import os
import sys
import json
import requests
from pathlib import Path

# Load API key from .env file
def load_env_file(file_path: Path) -> dict:
    """Load environment variables from .env file"""
    env_vars = {}
    if not file_path.exists():
        raise FileNotFoundError(f".env file not found at {file_path}")
    
    with open(file_path, 'r') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, value = line.split('=', 1)
                env_vars[key.strip()] = value.strip().strip('"').strip("'")
    return env_vars

try:
    env_vars = load_env_file(Path('.env'))
    API_KEY = env_vars.get('ENVATO_API_KEY')
except FileNotFoundError:
    API_KEY = os.getenv('ENVATO_API_KEY')

if not API_KEY:
    print("ERROR: ENVATO_API_KEY not found in .env file or environment variables")
    sys.exit(1)

# Create Envanto directory if it doesn't exist
envanto_dir = Path("Envanto")
envanto_dir.mkdir(exist_ok=True)

# Get item ID or name from command line
if len(sys.argv) > 1:
    search_term = sys.argv[1]
    
    # Check if it's numeric (item ID) or text (item name to search)
    if search_term.isdigit():
        item_id = search_term
    else:
        # Search for the item first
        print(f"Searching for: {search_term}")
        search_url = "https://api.envato.com/v1/discovery/search/search/item"
        search_params = {
            "term": search_term,
            "site": "photodune.net"
        }
        search_headers = {
            "Authorization": f"Bearer {API_KEY}",
            "User-Agent": "VbN-JSON-Fetcher/1.0"
        }
        
        try:
            search_response = requests.get(search_url, headers=search_headers, params=search_params, timeout=30)
            search_response.raise_for_status()
            search_data = search_response.json()
            
            matches = search_data.get('matches', [])
            if not matches:
                print(f"ERROR: No matches found for '{search_term}'")
                sys.exit(1)
            
            # Filter for photo/image matches (skip bullets when searching for guns)
            photo_matches = []
            for match in matches:
                classification = match.get('classification', '').lower()
                match_name = match.get('name', '').lower()
                
                # Skip non-photo items
                if any(excluded in classification for excluded in ['sound', 'audio', 'video', 'template', 'code', '3d']):
                    continue
                
                # Skip pure bullets when searching for guns
                if 'bullet' in match_name and not any(term in match_name for term in ['gun', 'pistol', 'revolver', 'handgun']):
                    continue
                
                # Accept if has photo classification or previews
                has_photo = any(term in classification for term in ['photo', 'image', 'stock'])
                has_previews = 'previews' in match and match['previews']
                if has_photo or has_previews:
                    photo_matches.append(match)
            
            if not photo_matches:
                print(f"ERROR: No photo/image matches found")
                sys.exit(1)
            
            # Use first photo match (prefer ones without "bullet" in name)
            best_match = None
            for match in photo_matches:
                if 'bullet' not in match.get('name', '').lower():
                    best_match = match
                    break
            
            if not best_match:
                best_match = photo_matches[0]
            
            item_id = str(best_match.get('id') or best_match.get('item_id', ''))
            print(f"Found item: {best_match.get('name', 'Unknown')} (ID: {item_id})")
            
            # Also save the search result JSON to see what data we already have
            search_output_file = envanto_dir / f"search_result_{item_id}.json"
            with open(search_output_file, 'w', encoding='utf-8') as f:
                json.dump(best_match, f, indent=2, ensure_ascii=False)
            print(f"Also saved search result to: {search_output_file}")
        except Exception as e:
            print(f"ERROR: Error searching: {e}")
            sys.exit(1)
else:
    print("ERROR: Usage: python scripts/fetch_envato_json.py <item_id_or_name>")
    print("   Example: python scripts/fetch_envato_json.py '9mm pistol'")
    print("   Or provide an item ID from search results")
    sys.exit(1)

print(f"Fetching catalog item JSON for item ID: {item_id}")

# Make API request - try different endpoints
# For unlimited downloads, we might need user purchases/downloads endpoint
endpoints_to_try = [
    ("https://api.envato.com/v3/market/catalog/item", {"id": item_id}),
    ("https://api.envato.com/v1/market/catalog/item", {"id": item_id}),
    ("https://api.envato.com/v1/market/catalog/item", {"id": item_id, "site": "photodune.net"}),
    # Try user's purchases endpoint - might have download URLs for unlimited accounts
    ("https://api.envato.com/v3/market/buyer/purchases", {}),
    ("https://api.envato.com/v1/market/buyer/purchases", {}),
]

data = None
response_data = None
successful_endpoint = None

for url, params in endpoints_to_try:
    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "User-Agent": "VbN-JSON-Fetcher/1.0",
        "Accept": "application/json"
    }
    
    try:
        print(f"Trying endpoint: {url} with params: {params}")
        response = requests.get(url, headers=headers, params=params, timeout=30)
        
        # Save the response (even if it's an error) to see what we're getting
        try:
            response_data = response.json()
        except:
            response_data = {"error": "Not JSON", "status_code": response.status_code, "text": response.text[:500]}
        
        # Save this response to file
        endpoint_name = url.split('/')[-1]  # Get 'item' from the URL
        response_file = envanto_dir / f"catalog_api_response_{item_id}_{endpoint_name}.json"
        with open(response_file, 'w', encoding='utf-8') as f:
            json.dump({
                "endpoint": url,
                "params": params,
                "status_code": response.status_code,
                "response": response_data
            }, f, indent=2, ensure_ascii=False)
        print(f"Saved response to: {response_file}")
        
        if response.status_code == 200:
            data = response_data
            successful_endpoint = url
            print(f"Success with endpoint: {url}")
            break
        else:
            print(f"Failed with status {response.status_code}")
    except Exception as e:
        print(f"Error with endpoint {url}: {e}")
        # Save error response too
        error_file = envanto_dir / f"catalog_api_error_{item_id}.json"
        with open(error_file, 'w', encoding='utf-8') as f:
            json.dump({"endpoint": url, "params": params, "error": str(e)}, f, indent=2)
        continue

if not data:
    print("ERROR: All endpoints returned non-200 status codes.")
    print("Check the saved JSON files in Envanto/ folder to see what responses we got.")
    sys.exit(1)

# Data should already be fetched above
if data:
    print(f"Successfully fetched JSON response")
    print(f"Response keys: {list(data.keys())}")
    
    # Save JSON to file
    output_file = envanto_dir / f"catalog_item_{item_id}.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
    
    print(f"Saved JSON to: {output_file}")
    print(f"File size: {output_file.stat().st_size / 1024:.1f} KB")
    
    # Print a summary of the structure
    if 'catalog_item' in data:
        item_data = data['catalog_item']
    elif 'item' in data:
        item_data = data['item']
    else:
        item_data = data
    
    print(f"\nItem data keys: {list(item_data.keys())[:20]}")
    
    # Check for download-related keys
    download_keys = [k for k in item_data.keys() if 'download' in k.lower() or 'image' in k.lower() or 'url' in k.lower()]
    if download_keys:
        print(f"Download/image related keys: {download_keys[:10]}")

