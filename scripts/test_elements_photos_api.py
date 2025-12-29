#!/usr/bin/env python3
"""Test Elements Photos API endpoints"""

import requests
import json
from pathlib import Path

# Load API key
ENV_FILE = Path(__file__).parent.parent / ".env"
api_key = None

with open(ENV_FILE, 'r') as f:
    for line in f:
        if line.startswith('ENVATO_API_KEY='):
            api_key = line.split('=', 1)[1].strip().strip('"\'')
            break

headers = {'Authorization': f'Bearer {api_key}'}
base_url = 'https://api.envato.com'

# Test Elements/Photos related endpoints
endpoints_to_test = [
    # Elements Photos endpoints
    ('/v1/elements/search/photos.json', {'term': 'katana'}),
    ('/v1/elements/photos/search.json', {'term': 'katana'}),
    ('/v1/elements/stock/photos.json', {'term': 'katana'}),
    ('/v1/stock/photos.json', {'term': 'katana'}),
    ('/v1/elements/search.json', {'term': 'katana', 'type': 'photos'}),
    ('/v1/elements/search.json', {'term': 'katana', 'category': 'photos'}),
    ('/v1/elements/photos.json', {'term': 'katana'}),
    
    # Discovery search with photos filter
    ('/v1/discovery/search/search/item.json', {'term': 'katana', 'category': 'stock-photos'}),
    ('/v1/discovery/search/search/item.json', {'term': 'katana', 'type': 'photo'}),
    ('/v1/discovery/search/search/item.json', {'term': 'katana', 'media_type': 'photo'}),
]

print("Testing Elements Photos API endpoints...")
print("=" * 60)

for endpoint, params in endpoints_to_test:
    url = base_url + endpoint
    try:
        r = requests.get(url, headers=headers, params=params, timeout=10)
        status = r.status_code
        
        if status == 200:
            data = r.json()
            total = data.get('total_hits', data.get('total', data.get('count', 0)))
            matches = data.get('matches', data.get('items', data.get('results', [])))
            print(f"[SUCCESS] {endpoint}")
            print(f"   Params: {params}")
            print(f"   Total results: {total}")
            print(f"   Matches returned: {len(matches)}")
            if matches:
                first = matches[0]
                print(f"   First result: {first.get('name', 'N/A')}")
                # Check for image URL
                if 'previews' in first:
                    print(f"   Has previews: Yes")
                if 'image_url' in first or 'thumbnail_url' in first:
                    print(f"   Has image URL: Yes")
            print()
        elif status == 404:
            print(f"[404] {endpoint}")
        elif status == 403:
            print(f"[403] {endpoint} (forbidden - might need different params)")
        elif status == 400:
            print(f"[400] {endpoint} (bad request)")
            try:
                error = r.json()
                print(f"   Error: {error.get('error', error)}")
            except:
                pass
        else:
            print(f"[{status}] {endpoint}")
    except Exception as e:
        print(f"[ERROR] {endpoint} - {e}")

print("=" * 60)

